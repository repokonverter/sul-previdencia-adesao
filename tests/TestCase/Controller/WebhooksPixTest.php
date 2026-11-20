<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestSuite\SecondBenefitPlanTrait;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * O webhook Pix de cada banco chega na rota dele (/sicoob/..., /bancodobrasil/...)
 * e reconfirma o pagamento na conta do plano em que foi cadastrado. Aqui o
 * segundo plano recebe pelo Banco do Brasil, com a API mockada.
 */
class WebhooksPixTest extends TestCase
{
    use HttpClientTrait;
    use IntegrationTestTrait;
    use SecondBenefitPlanTrait;

    private const BB = 'https://bb.test/pix/v2';

    protected array $fixtures = [
        'app.PlanParameters',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSecondBenefitPlan();
        Configure::write('BenefitPlans.' . self::SECOND_PLAN . '.pix', ['provider' => 'bancodobrasil', 'config' => 'BancoDoBrasilTest']);
        Configure::write('BancoDoBrasilTest', [
            'baseUrl' => self::BB,
            'authUrl' => 'https://bb.test/oauth/token',
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
            'appKey' => 'APPKEY',
            'pixKey' => 'chave-bb',
        ]);
    }

    protected function tearDown(): void
    {
        Configure::delete('BancoDoBrasilTest');

        parent::tearDown();
    }

    private function table(string $name): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($name);
    }

    private function registerWebhook(string $benefitPlan): string
    {
        $token = bin2hex(random_bytes(20));
        $webhooks = $this->table('PixWebhooks');
        $webhooks->saveOrFail($webhooks->newEntity([
            'chave' => 'chave',
            'token' => $token,
            'url' => 'https://example.test/' . $token,
            'benefit_plan' => $benefitPlan,
        ]));

        return $token;
    }

    private function createCharge(string $provider): string
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $adhesions->saveOrFail($adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'benefit_plan' => self::SECOND_PLAN,
            'name' => 'Fulano de Tal',
        ]));

        $txid = 'MAISPREMIUM' . strtoupper(bin2hex(random_bytes(8)));
        $transactions = $this->table('PixTransactions');
        $transactions->saveOrFail($transactions->newEntity([
            'adhesion_initial_data_id' => $adhesion->id,
            'txid' => $txid,
            'attempt' => 1,
            'provider' => $provider,
            'amount' => 150,
        ]));

        return $txid;
    }

    private function postPix(string $url, string $txid): void
    {
        $this->configRequest(['headers' => ['Content-Type' => 'application/json']]);
        $this->post($url, (string)json_encode(['pix' => [['txid' => $txid, 'valor' => '150.00']]]));
    }

    public function testABancoDoBrasilNotificationConfirmsThePayment(): void
    {
        $token = $this->registerWebhook(self::SECOND_PLAN);
        $txid = $this->createCharge('bancodobrasil');

        $this->mockClientPost('https://bb.test/oauth/token', $this->newClientResponse(200, ['Content-Type: application/json'], '{"access_token":"t"}'));
        $this->mockClientGet(
            self::BB . "/cob/$txid?gw-dev-app-key=APPKEY",
            $this->newClientResponse(200, ['Content-Type: application/json'], '{"status":"CONCLUIDA"}')
        );

        $this->postPix("/bancodobrasil/webhook/$token/pix", $txid);

        $this->assertResponseCode(200);
        $this->assertTrue($this->table('PixTransactions')->find()->where(['txid' => $txid])->firstOrFail()->paid);
    }

    /**
     * O token do webhook do BB chegando pela rota do Sicoob (ou o contrário)
     * é tratado como desconhecido: o banco da rota tem que ser o da conta.
     */
    public function testAWebhookOnlyAnswersOnItsBanksRoute(): void
    {
        $bbToken = $this->registerWebhook(self::SECOND_PLAN);
        $sicoobToken = $this->registerWebhook('plenoprev');

        $this->postPix("/sicoob/webhook/$bbToken/pix", 'QUALQUER');
        $this->assertResponseCode(404);

        $this->postPix("/bancodobrasil/webhook/$sicoobToken/pix", 'QUALQUER');
        $this->assertResponseCode(404);
    }

    public function testAnUnknownTokenIsRejected(): void
    {
        $this->postPix('/bancodobrasil/webhook/token-que-nao-existe/pix', 'QUALQUER');

        $this->assertResponseCode(404);
    }
}
