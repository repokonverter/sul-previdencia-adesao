<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestSuite\SecondBenefitPlanTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * Um admin só para todos os planos: lista e filtra por plano, e o plano de
 * adesões e parceiros é escolhido no cadastro e não muda depois.
 */
class BenefitPlansAdminTest extends TestCase
{
    use IntegrationTestTrait;
    use SecondBenefitPlanTrait;

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

        $this->configureSecondBenefitPlan();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->session(['Auth' => $this->table('Users')->get(1)]);

        // A fixture grava ids fixos sem avançar a sequence.
        foreach (['partners', 'promotional_codes'] as $table) {
            $this->table('Partners')->getConnection()
                ->execute("SELECT setval(pg_get_serial_sequence('$table', 'id'), (SELECT MAX(id) FROM $table))");
        }
    }

    private function table(string $name): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($name);
    }

    private function createAdhesion(string $benefitPlan, string $name): int
    {
        $adhesions = $this->table('AdhesionInitialDatas');

        return $adhesions->saveOrFail($adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'benefit_plan' => $benefitPlan,
            'name' => $name,
        ]))->id;
    }

    public function testTheListingShowsAndFiltersThePlan(): void
    {
        $this->createAdhesion('plenoprev', 'Cliente do PlenoPrev');
        $this->createAdhesion(self::SECOND_PLAN, 'Cliente do MaisPremium');

        $this->get('/admin/adhesions');
        $this->assertResponseOk();
        $this->assertResponseContains('Cliente do PlenoPrev');
        $this->assertResponseContains('Cliente do MaisPremium');
        $this->assertSame(2, $this->viewVariable('summary')['total']);

        $this->get('/admin/adhesions?benefitPlan=' . self::SECOND_PLAN);
        $this->assertResponseNotContains('Cliente do PlenoPrev');
        $this->assertResponseContains('Cliente do MaisPremium');
        $this->assertSame(1, $this->viewVariable('summary')['total']);
        $this->assertSame('MaisPremium', $this->viewVariable('summaryTitle'));
    }

    public function testTheDashboardCountsEachPlan(): void
    {
        $this->createAdhesion('plenoprev', 'Um');
        $this->createAdhesion(self::SECOND_PLAN, 'Dois');
        $this->createAdhesion(self::SECOND_PLAN, 'Três');

        $this->get('/admin');

        $this->assertResponseOk();
        $summaries = $this->viewVariable('summaries');
        $this->assertSame(1, $summaries['plenoprev']['counts']['total']);
        $this->assertSame(2, $summaries[self::SECOND_PLAN]['counts']['total']);
    }

    public function testAnAdhesionCreatedInTheAdminTakesTheChosenPlan(): void
    {
        $this->post('/admin/adhesions/add', [
            'benefit_plan' => self::SECOND_PLAN,
            'name' => 'Criada no Admin',
            'email' => 'criada@exemplo.test',
            'phone' => '48999999999',
        ]);

        $this->assertRedirect(['action' => 'index']);
        $adhesion = $this->table('AdhesionInitialDatas')->find()->where(['name' => 'Criada no Admin'])->firstOrFail();
        $this->assertSame(self::SECOND_PLAN, $adhesion->benefit_plan);
    }

    public function testEditingNeverMovesAnAdhesionToTheOtherPlan(): void
    {
        $id = $this->createAdhesion('plenoprev', 'Fulano');

        $this->post("/admin/adhesions/edit/$id", [
            'benefit_plan' => self::SECOND_PLAN,
            'name' => 'Fulano',
        ]);

        $this->assertSame('plenoprev', $this->table('AdhesionInitialDatas')->get($id)->benefit_plan);
    }

    public function testAPartnerIsRegisteredInOnePlanAndItsLinksUseThatHost(): void
    {
        $this->post('/admin/partners/add', [
            'benefit_plan' => self::SECOND_PLAN,
            'name' => 'Parceiro MP',
            'color' => '#123456',
            'active' => '1',
        ]);

        $partner = $this->table('Partners')->find()->where(['name' => 'Parceiro MP'])->firstOrFail();
        $this->assertSame(self::SECOND_PLAN, $partner->benefit_plan);

        $codes = $this->table('PromotionalCodes');
        $codes->saveOrFail($codes->newEntity(['partner_id' => $partner->id, 'code' => 'MAIS10', 'active' => true]));

        $this->get("/admin/partners/view/{$partner->id}");
        $this->assertResponseContains('https://' . self::SECOND_PLAN_HOST . '/?promo=MAIS10');

        $this->post("/admin/partners/edit/{$partner->id}", [
            'benefit_plan' => 'plenoprev',
            'name' => 'Parceiro MP',
            'color' => '#123456',
            'active' => '1',
        ]);
        $this->assertSame(self::SECOND_PLAN, $this->table('Partners')->get($partner->id)->benefit_plan);
    }

    public function testEachPlanHasItsOwnParameterScreen(): void
    {
        $this->get('/admin/plan-parameters/edit/' . self::SECOND_PLAN);
        $this->assertResponseOk();
        $this->assertResponseContains('value="250,00"');

        $this->get('/admin/plan-parameters/edit/outroplano');
        $this->assertResponseCode(404);
    }

    public function testTheScreensWithAPlanSelectorRender(): void
    {
        foreach (['/admin/adhesions/add', '/admin/partners/add', '/admin/associations/add', '/admin/pix-webhooks/add'] as $url) {
            $this->get($url);
            $this->assertResponseOk($url);
            $this->assertResponseContains('<option value="maispremium">MaisPremium</option>', $url);
        }

        foreach (['/admin/partners', '/admin/pix-webhooks'] as $url) {
            $this->get($url);
            $this->assertResponseOk($url);
        }
    }
}
