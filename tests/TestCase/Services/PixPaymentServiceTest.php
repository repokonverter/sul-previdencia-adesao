<?php

declare(strict_types=1);

namespace App\Test\TestCase\Services;

use App\Services\PixGateway;
use App\Services\PixPaymentService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * O PixPaymentService fala com o banco do plano por PixGateway: aqui, um
 * banco de mentira que só guarda o que recebeu.
 */
class PixPaymentServiceTest extends TestCase
{
    private function gateway(): PixGateway
    {
        return new class implements PixGateway {
            /** @var array<string, array> */
            public array $created = [];

            /** @var array<string, string> */
            public array $statuses = [];

            public function forAdhesion(?int $adhesionId): static
            {
                return $this;
            }

            public function createCobWithTxid(string $txid, array $data): array
            {
                $this->created[$txid] = $data;
                $this->statuses[$txid] = 'ATIVA';

                return ['status' => 'ATIVA', 'brcode' => "brcode-$txid"];
            }

            public function getCob(string $txid): array
            {
                return ['status' => $this->statuses[$txid] ?? 'REMOVIDA_PELO_PSP', 'brcode' => "brcode-$txid"];
            }

            public function configureWebhook(string $chave, string $webhookUrl): array
            {
                return [];
            }

            public function deleteWebhook(string $chave): array
            {
                return [];
            }
        };
    }

    private function service(PixGateway $gateway, string $provider): PixPaymentService
    {
        return new PixPaymentService(
            $gateway,
            $provider,
            "chave-$provider",
            'MAISPREMIUM',
            TableRegistry::getTableLocator()->get('PixTransactions')
        );
    }

    private function createAdhesion(): int
    {
        $adhesions = TableRegistry::getTableLocator()->get('AdhesionInitialDatas');

        return $adhesions->saveOrFail($adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'benefit_plan' => 'plenoprev',
            'name' => 'Fulano de Tal',
        ]))->id;
    }

    public function testTheChargeCarriesThePlansKeyPrefixAndBank(): void
    {
        $gateway = $this->gateway();
        $adhesionId = $this->createAdhesion();

        $result = $this->service($gateway, 'bancodobrasil')->resolve($adhesionId, '12345678909', 'Fulano', 150.0);

        $this->assertSame('active', $result['status']);
        $this->assertStringStartsWith('MAISPREMIUM', $result['txid']);
        $this->assertSame('chave-bancodobrasil', $gateway->created[$result['txid']]['chave']);

        $transaction = TableRegistry::getTableLocator()->get('PixTransactions')->find()
            ->where(['txid' => $result['txid']])
            ->firstOrFail();
        $this->assertSame('bancodobrasil', $transaction->provider);
    }

    /**
     * O txid só tem significado no banco que o emitiu: perguntar a outro
     * banco por ele daria "não encontrada" e faria a página gerar Pix novo
     * por cima de um que talvez já tenha sido pago.
     */
    public function testAChargeFromAnotherBankIsNotLookedUpInThisOne(): void
    {
        $sicoob = $this->gateway();
        $adhesionId = $this->createAdhesion();
        $first = $this->service($sicoob, 'sicoob')->resolve($adhesionId, '12345678909', 'Fulano', 150.0);

        $other = $this->gateway();
        $this->assertSame(['found' => false], $this->service($other, 'bancodobrasil')->confirmIfPaid($first['txid']));

        $second = $this->service($other, 'bancodobrasil')->resolve($adhesionId, '12345678909', 'Fulano', 150.0);
        $this->assertNotSame($first['txid'], $second['txid']);
        $this->assertArrayHasKey($second['txid'], $other->created);
    }
}
