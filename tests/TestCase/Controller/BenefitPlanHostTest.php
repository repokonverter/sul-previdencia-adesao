<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestSuite\SecondBenefitPlanTrait;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * O subdomínio decide o plano de benefícios do lado público: formulário,
 * simulador, códigos e adesões ficam no plano do host em que nasceram.
 *
 * Ver docs/plano-multiplano-maispremium.md.
 */
class BenefitPlanHostTest extends TestCase
{
    use IntegrationTestTrait;
    use SecondBenefitPlanTrait;

    protected array $fixtures = [
        'app.PlanParameters',
        'app.Partners',
        'app.PromotionalCodes',
        'app.Brokers',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSecondBenefitPlan();
        $this->enableCsrfToken();
    }

    private function json(): array
    {
        return json_decode((string)$this->_response->getBody(), true);
    }

    /**
     * Os headers de configRequest() valem para uma requisição só; o host de
     * onHost() fica até o fim do teste.
     */
    private function saveInitialData(array $data = []): array
    {
        $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);
        $this->post('/registrations/save', $data + [
            'initialData' => ['name' => 'Fulano de Tal', 'phone' => '48999999999'],
        ]);

        $this->assertResponseOk();

        return $this->json();
    }

    public function testAnUnknownHostHasNoPublicPages(): void
    {
        Configure::write('App.defaultBenefitPlan', null);

        try {
            $this->onHost('konverter.com.br');
            $this->get('/');
            $this->assertResponseCode(404);

            // O healthcheck do Kamal e o admin não são de plano nenhum.
            $this->onHost('143.95.173.13');
            $this->get('/up');
            $this->assertResponseOk();

            $this->onHost('konverter.com.br');
            $this->get('/admin/users/login');
            $this->assertResponseOk();
        } finally {
            Configure::write('App.defaultBenefitPlan', 'plenoprev');
        }
    }

    public function testWwwGoesToThePlanHostKeepingPathAndQuery(): void
    {
        $this->onHost('www.' . self::SECOND_PLAN_HOST);
        $this->get('/?promo=ABC');

        $this->assertResponseCode(301);
        $this->assertHeader('Location', 'https://' . self::SECOND_PLAN_HOST . '/?promo=ABC');
    }

    public function testTheHomeOffersOnlyTheHostsPlan(): void
    {
        $this->onSecondPlanHost();
        $this->get('/');

        $this->assertResponseOk();
        $this->assertResponseContains('value="maispremium"');
        $this->assertResponseNotContains('value="plenoprev"');
    }

    public function testANewAdhesionBelongsToTheHostsPlan(): void
    {
        $this->onSecondPlanHost();
        $result = $this->saveInitialData();

        $this->assertTrue($result['success']);
        $this->assertSame(
            self::SECOND_PLAN,
            TableRegistry::getTableLocator()->get('AdhesionInitialDatas')->get($result['initialDataId'])->benefit_plan
        );

        $this->onHost(null);
        $result = $this->saveInitialData();

        $this->assertSame(
            'plenoprev',
            TableRegistry::getTableLocator()->get('AdhesionInitialDatas')->get($result['initialDataId'])->benefit_plan
        );
    }

    public function testAnAdhesionIsNotContinuedFromTheOtherPlansHost(): void
    {
        $created = $this->saveInitialData();

        $this->onSecondPlanHost();
        $result = $this->saveInitialData([
            'initialDataId' => $created['initialDataId'],
            'storageUuid' => $created['storageUuid'],
            'initialData' => ['name' => 'Outro Nome', 'phone' => '48999999999'],
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(
            'Fulano de Tal',
            TableRegistry::getTableLocator()->get('AdhesionInitialDatas')->get($created['initialDataId'])->name
        );
    }

    /**
     * O código é único no sistema, mas no host do outro plano é como se não
     * existisse -- e não fica gravado na adesão de lá.
     */
    public function testAPromotionalCodeOnlyWorksOnItsPlansHost(): void
    {
        $this->onSecondPlanHost();
        $this->get('/promotional-codes/validate?code=PARCEIRO10');

        $this->assertResponseOk();
        $this->assertSame('not_found', $this->json()['reason']);

        $result = $this->saveInitialData([
            'initialData' => ['name' => 'Fulano', 'phone' => '48999999999', 'promotionalCode' => 'PARCEIRO10', 'brokerId' => 1],
        ]);

        $adhesion = TableRegistry::getTableLocator()->get('AdhesionInitialDatas')->get($result['initialDataId']);
        $this->assertNull($adhesion->promotional_code_id);
        $this->assertNull($adhesion->broker_id);
    }

    public function testTheSimulatorUsesTheHostsParameters(): void
    {
        $query = http_build_query(['date' => '1985-03-10', 'value' => '200']);

        $this->get('/simulator/recalculate?' . $query);
        $this->assertTrue($this->json()['success'], 'No PlenoPrev o mínimo é R$ 100,00.');

        $this->onSecondPlanHost();
        $this->get('/simulator/recalculate?' . $query);
        $this->assertFalse($this->json()['success']);
        $this->assertStringContainsString('R$ 250,00', $this->json()['message']);
    }

    public function testAResumeLinkMovesToTheAdhesionsHost(): void
    {
        $adhesions = TableRegistry::getTableLocator()->get('AdhesionInitialDatas');
        $adhesion = $adhesions->saveOrFail($adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'benefit_plan' => 'plenoprev',
            'name' => 'Fulano de Tal',
        ]));
        $token = $adhesions->issueResumeToken($adhesion, 7);

        $this->onSecondPlanHost();
        $this->get("/proposta/$token");

        // PLENOPREV_BASE_URL do .env de desenvolvimento muda o host.
        $this->assertRedirect(Configure::read('BenefitPlans.plenoprev.baseUrl') . "/proposta/$token");
    }
}
