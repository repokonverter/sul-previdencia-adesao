<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use DateTimeImmutable;

/**
 * A aba Plano do admin com gráfico e cenários, e o PDF da simulação que o
 * admin manda ao cliente.
 */
class AdhesionsPlanSimulationTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.PlanParameters',
        'app.Partners',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->session([
            'Auth' => TableRegistry::getTableLocator()->get('Users')->get(1),
        ]);
    }

    private function table(string $name): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($name);
    }

    private function createAdhesion(?string $birthDate = null, ?int $associationPartnerId = null): int
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulana da Silva',
            'email' => 'fulana@exemplo.test',
            'phone' => '48999999999',
        ]);
        $adhesion->set('association_partner_id', $associationPartnerId);
        $adhesions->saveOrFail($adhesion);

        if ($birthDate !== null) {
            $this->table('AdhesionPersonalDatas')->saveOrFail($this->table('AdhesionPersonalDatas')->newEntity([
                'adhesion_initial_data_id' => $adhesion->id,
                'plan_for' => 'Titular',
                'name' => 'Fulana da Silva',
                'cpf' => '123.456.789-09',
                'birth_date' => $birthDate,
                'nacionality' => 'Brasileira',
            ]));
        }

        $this->table('AdhesionPlans')->saveOrFail($this->table('AdhesionPlans')->newEntity([
            'adhesion_initial_data_id' => $adhesion->id,
            'benefit_entry_age' => 65,
            'monthly_retirement_contribution' => '740.00',
            'monthly_survivors_pension_contribution' => '160.00',
            'survivors_pension_insured_capital' => '1000.00',
            'monthly_disability_retirement_contribution' => '100.00',
            'disability_retirement_insured_capital' => '1000.00',
        ]));

        return (int)$adhesion->id;
    }

    private function yearsAgo(int $years): string
    {
        return (new DateTimeImmutable("-$years years"))->format('Y-m-d');
    }

    public function testViewShowsTheProjectionAndThePdfButton(): void
    {
        $id = $this->createAdhesion($this->yearsAgo(35));

        $this->get("/admin/adhesions/view/$id?tab=plan");

        $this->assertResponseOk();
        $this->assertResponseContains('id="planProjectionChart"');
        $this->assertResponseContains("/admin/adhesions/plan-pdf/$id");
        $this->assertResponseContains('1.000,00');
    }

    public function testViewShowsThePartnerWhenThereIsAnAssociation(): void
    {
        $id = $this->createAdhesion($this->yearsAgo(35), associationPartnerId: 2);

        $this->get("/admin/adhesions/view/$id?tab=plan");

        $this->assertResponseOk();
        $this->assertResponseContains('em parceria com');
        $this->assertResponseContains('Sindilojas Exemplo');
    }

    public function testEditShowsTheProjection(): void
    {
        $id = $this->createAdhesion($this->yearsAgo(35));

        $this->get("/admin/adhesions/edit/$id");

        $this->assertResponseOk();
        $this->assertResponseContains('id="planProjectionChart"');
        $this->assertResponseContains('adhesions\\/plan-projection');
    }

    public function testPlanPdfDownloadsTheSimulation(): void
    {
        $id = $this->createAdhesion($this->yearsAgo(35), associationPartnerId: 2);

        $this->get("/admin/adhesions/plan-pdf/$id");

        $this->assertResponseOk();
        $this->assertContentType('application/pdf');
        $this->assertHeaderContains('Content-Disposition', "simulacao-plano-fulana-da-silva-$id.pdf");
        $this->assertStringStartsWith('%PDF', (string)$this->_response->getBody());
    }

    public function testPlanPdfWithoutBirthDateGoesBackToThePlanTab(): void
    {
        $id = $this->createAdhesion();

        $this->get("/admin/adhesions/plan-pdf/$id");

        $this->assertRedirect(['action' => 'view', $id, '?' => ['tab' => 'plan']]);
        $this->assertFlashMessage('A simulação exige o plano e a data de nascimento preenchidos.');
    }

    public function testPlanProjectionProjectsTheValuesOnScreen(): void
    {
        $this->get('/admin/adhesions/plan-projection?' . http_build_query([
            'date' => $this->yearsAgo(35),
            'retirementContribution' => '2220',
            'survivorsPensionContribution' => '480',
            'disabilityRetirementContribution' => '300',
        ]));

        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);

        $this->assertTrue($body['success']);
        $this->assertTrue($body['hasProjection']);
        $this->assertEqualsWithDelta(2220.0, $body['retirementContribution'], 0.001);
        $this->assertCount(3, $body['scenarios']);
        $this->assertSame(65, end($body['series']['ages']));
    }

    public function testPlanProjectionRequiresTheBirthDate(): void
    {
        $this->get('/admin/adhesions/plan-projection?retirementContribution=500');

        $this->assertResponseOk();
        $this->assertFalse(json_decode((string)$this->_response->getBody(), true)['success']);
    }

    public function testPlanPdfRequiresLogin(): void
    {
        $this->session(['Auth' => null]);
        $id = $this->createAdhesion($this->yearsAgo(35));

        $this->get("/admin/adhesions/plan-pdf/$id");

        $this->assertRedirectContains('/admin/users/login');
    }
}
