<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * O card de resumo da listagem de adesões: conta todas as adesões
 * filtradas, não só a página, com as mesmas regras das colunas.
 */
class AdhesionsSummaryTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.PlanParameters',
        'app.Partners',
        'app.PromotionalCodes',
        'app.Brokers',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->session([
            'Auth' => TableRegistry::getTableLocator()->get('Users')->get(1),
        ]);

        // O resumo conta a tabela inteira: adesões deixadas por outros
        // testes (que não truncam essa tabela) entrariam no total. As filhas
        // vão junto pelo ON DELETE CASCADE.
        TableRegistry::getTableLocator()->get('AdhesionInitialDatas')->deleteAll([]);

        // Por corretor, em novembro: só iniciada; aguardando pagamento;
        // assinada e paga no último minuto do mês. Em dezembro: Pix gerado e
        // não pago. Sem corretor: uma concluída e oito só iniciadas, para o
        // total passar da página de 10.
        $this->createAdhesion('2026-11-01 10:00:00', ['broker_id' => 1]);
        $this->createAdhesion('2026-11-15 12:00:00', ['broker_id' => 1], completed: true, payment: true);
        $paid = $this->createAdhesion('2026-11-30 23:59:00', ['broker_id' => 1], completed: true, payment: true);
        $this->insert('clicksign_data', $paid, ['envelope_id' => 'env-1', 'status' => 'signed']);
        $this->insert('pix_transactions', $paid, ['txid' => 'tx-1', 'paid' => true]);
        $unpaid = $this->createAdhesion('2026-12-01 00:00:00', ['broker_id' => 1], completed: true, payment: true);
        $this->insert('pix_transactions', $unpaid, ['txid' => 'tx-2', 'paid' => false]);

        $this->createAdhesion('2026-10-10 09:00:00', [], completed: true);
        for ($i = 0; $i < 8; $i++) {
            $this->createAdhesion('2026-10-11 09:00:00');
        }
    }

    private function createAdhesion(string $created, array $data = [], bool $completed = false, bool $payment = false): int
    {
        $adhesions = TableRegistry::getTableLocator()->get('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity($data + [
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'email' => 'fulano@exemplo.test',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);
        $adhesions->updateAll(['created' => $created], ['id' => $adhesion->id]);

        if ($completed) {
            $this->insert('adhesion_other_informations', $adhesion->id, ['category' => 'outros']);
        }

        if ($payment) {
            $this->insert('adhesion_payment_details', $adhesion->id, ['payment_type' => 'pix']);
        }

        return (int)$adhesion->id;
    }

    private function insert(string $table, int $adhesionId, array $values): void
    {
        TableRegistry::getTableLocator()->get('AdhesionInitialDatas')->getConnection()
            ->insert($table, ['adhesion_initial_data_id' => $adhesionId] + $values, ['paid' => 'boolean']);
    }

    private function assertSummary(array $expected): void
    {
        $this->assertResponseOk();
        $this->assertSame($expected, $this->viewVariable('summary'));
    }

    public function testSummaryCountsEveryFilteredAdhesionNotJustThePage(): void
    {
        $this->get('/admin/adhesions');

        $this->assertCount(10, $this->viewVariable('adhesions'));
        $this->assertSummary(['total' => 13, 'completed' => 4, 'signed' => 1, 'paid' => 1, 'awaitingPayment' => 2]);
        $this->assertNull($this->viewVariable('summaryTitle'));
        $this->assertResponseContains('Aguardando pagamento');
    }

    public function testSummaryFollowsTheBrokerFilter(): void
    {
        $this->get('/admin/adhesions?brokerId=1');

        $this->assertSummary(['total' => 4, 'completed' => 3, 'signed' => 1, 'paid' => 1, 'awaitingPayment' => 2]);
        $this->assertSame('Corretora Parceira - João da Silva', $this->viewVariable('summaryTitle'));
    }

    /**
     * "Até" inclui o dia inteiro: a adesão das 23:59 do dia 30 entra, a da
     * meia-noite do dia 1º não.
     */
    public function testPeriodFilterIncludesTheWholeLastDay(): void
    {
        $this->get('/admin/adhesions?brokerId=1&dateFrom=2026-11-01&dateUntil=2026-11-30');

        $this->assertSummary(['total' => 3, 'completed' => 2, 'signed' => 1, 'paid' => 1, 'awaitingPayment' => 1]);
        $this->assertCount(3, $this->viewVariable('adhesions'));
        $this->assertSame(
            'Corretora Parceira - João da Silva · 01/11/2026 a 30/11/2026',
            $this->viewVariable('summaryTitle')
        );
        // O formulário volta com o filtro aplicado.
        $this->assertResponseContains('value="2026-11-30"');
    }

    public function testInvalidDateIsIgnored(): void
    {
        $this->get('/admin/adhesions?dateFrom=30/11/2026');

        $this->assertSummary(['total' => 13, 'completed' => 4, 'signed' => 1, 'paid' => 1, 'awaitingPayment' => 2]);
    }

    public function testEmptyResultCountsZero(): void
    {
        $this->get('/admin/adhesions?dateFrom=2030-01-01');

        $this->assertSummary(['total' => 0, 'completed' => 0, 'signed' => 0, 'paid' => 0, 'awaitingPayment' => 0]);
    }
}
