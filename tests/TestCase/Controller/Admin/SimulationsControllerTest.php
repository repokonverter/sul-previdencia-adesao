<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Services\PlanSimulator;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;

/**
 * A simulação avulsa do admin: um plano em PDF para um cliente sem adesão,
 * sem gravar nada. Os endpoints de projeção e "Recalcular" também servem a
 * aba Plano do editar adesão.
 */
class SimulationsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.PlanParameters',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableCsrfToken();
        $this->session([
            'Auth' => TableRegistry::getTableLocator()->get('Users')->get(1),
        ]);
    }

    private function yearsAgo(int $years): string
    {
        return (new DateTimeImmutable("-$years years"))->format('Y-m-d');
    }

    private function getJson(string $url, array $query): array
    {
        $this->get($url . '?' . http_build_query($query));
        $this->assertResponseOk();

        return json_decode((string)$this->_response->getBody(), true);
    }

    /**
     * @return array<string, mixed>
     */
    private function simulation(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Ciclana de Souza',
            'birth_date' => $this->yearsAgo(35),
            'benefit_entry_age' => '65',
            'has_survivors_pension' => '1',
            'has_disability_retirement' => '1',
            'monthly_retirement_contribution' => '740,00',
            'monthly_survivors_pension_contribution' => '160,00',
            'monthly_disability_retirement_contribution' => '100,00',
        ];
    }

    public function testTheMenuLeadsToTheSimulation(): void
    {
        $this->get('/admin/simulations');

        $this->assertResponseOk();
        $this->assertResponseContains('href="/admin/simulations"');
        $this->assertResponseContains('name="birth_date"');
        $this->assertResponseContains('id="planProjectionChart"');
        $this->assertResponseContains('id="btnPlanPdf"');
        $this->assertResponseContains('name="benefit_entry_age"');
        $this->assertResponseContains('value="65"');
    }

    public function testRequiresLogin(): void
    {
        $this->session(['Auth' => null]);

        $this->get('/admin/simulations');

        $this->assertRedirectContains('/admin/users/login');
    }

    public function testPdfDownloadsTheSimulationWithoutSavingAnything(): void
    {
        $adhesions = TableRegistry::getTableLocator()->get('AdhesionInitialDatas');
        $before = $adhesions->find()->count();

        $this->post('/admin/simulations/pdf', $this->simulation());

        $this->assertResponseOk();
        $this->assertContentType('application/pdf');
        $this->assertHeaderContains('Content-Disposition', 'simulacao-plano-ciclana-de-souza.pdf');
        $this->assertStringStartsWith('%PDF', (string)$this->_response->getBody());
        $this->assertSame($before, $adhesions->find()->count());
    }

    public function testInvalidSimulationComesBackFilledWithTheErrors(): void
    {
        $this->post('/admin/simulations/pdf', $this->simulation([
            'name' => '',
            'benefit_entry_age' => '70',
            'monthly_survivors_pension_contribution' => '5,00',
        ]));

        $this->assertResponseOk();
        $this->assertContentType('text/html');
        $this->assertResponseContains('Informe o nome do cliente.');
        $this->assertResponseContains('A idade para entrada em benefício deve ser no máximo 65 anos.');
        $this->assertResponseContains('A contribuição de pensão por morte não pode ser menor que R$ 16,00.');
        $this->assertResponseContains('value="740,00"');
    }

    public function testTotalBelowTheMinimumIsRejected(): void
    {
        $this->post('/admin/simulations/pdf', $this->simulation([
            'monthly_retirement_contribution' => '10,00',
            'has_survivors_pension' => '0',
            'has_disability_retirement' => '0',
        ]));

        $this->assertResponseContains('A contribuição total mínima é de R$ 100,00.');
    }

    public function testAClientAlreadyAtRetirementAgeIsRejected(): void
    {
        $this->post('/admin/simulations/pdf', $this->simulation(['birth_date' => $this->yearsAgo(65)]));

        $this->assertResponseContains('O cliente já atingiu a idade máxima de entrada em benefício (65 anos).');
    }

    /**
     * Um risco desmarcado não é cobrado pelo piso, e entra como zero mesmo
     * que um valor tenha vindo no campo.
     */
    public function testUncheckedRiskIsNeitherCheckedNorCharged(): void
    {
        $this->post('/admin/simulations/pdf', $this->simulation([
            'has_survivors_pension' => '0',
            'monthly_survivors_pension_contribution' => '5,00',
        ]));

        $this->assertContentType('application/pdf');
    }

    public function testProjectionProjectsTheValuesOnScreen(): void
    {
        $body = $this->getJson('/admin/simulations/projection', [
            'date' => $this->yearsAgo(35),
            'retirementContribution' => '2220',
            'survivorsPensionContribution' => '480',
            'disabilityRetirementContribution' => '300',
        ]);

        $this->assertTrue($body['success']);
        $this->assertTrue($body['hasProjection']);
        $this->assertEqualsWithDelta(2220.0, $body['retirementContribution'], 0.001);
        $this->assertCount(3, $body['scenarios']);
        $this->assertSame(65, end($body['series']['ages']));
    }

    public function testProjectionRequiresTheBirthDate(): void
    {
        $body = $this->getJson('/admin/simulations/projection', ['retirementContribution' => '500']);

        $this->assertFalse($body['success']);
    }

    public function testRecalculateGivesTheDefaultShareToContractedRisksStillAtZero(): void
    {
        $body = $this->getJson('/admin/simulations/recalculate', [
            'date' => $this->yearsAgo(35),
            'totalContribution' => '1.000,00',
            'hasSurvivorsPension' => 1,
            'survivorsPensionContribution' => '0',
            'hasDisabilityRetirement' => 1,
            'disabilityRetirementContribution' => '0',
        ]);

        $this->assertTrue($body['success']);
        $this->assertEqualsWithDelta(740.0, $body['retirementContribution'], 0.001);
        $this->assertEqualsWithDelta(160.0, $body['survivorsPensionContribution'], 0.001);
        $this->assertEqualsWithDelta(100.0, $body['disabilityRetirementContribution'], 0.001);
        $this->assertGreaterThan(0, $body['survivorsPensionInsuredCapital']);
        $this->assertGreaterThan(0, $body['disabilityRetirementInsuredCapital']);
    }

    public function testRecalculateKeepsWhatTheAdminTyped(): void
    {
        $body = $this->getJson('/admin/simulations/recalculate', [
            'date' => $this->yearsAgo(35),
            'totalContribution' => '1000',
            'hasSurvivorsPension' => 1,
            'survivorsPensionContribution' => '50',
            'hasDisabilityRetirement' => 0,
            'disabilityRetirementContribution' => '100',
        ]);

        $this->assertTrue($body['success']);
        $this->assertEqualsWithDelta(950.0, $body['retirementContribution'], 0.001);
        $this->assertEqualsWithDelta(50.0, $body['survivorsPensionContribution'], 0.001);
        $this->assertEqualsWithDelta(0.0, $body['disabilityRetirementContribution'], 0.001);
        $this->assertEqualsWithDelta(0.0, $body['disabilityRetirementInsuredCapital'], 0.001);
    }

    public function testRecalculateRefusesATotalBelowTheMinimum(): void
    {
        $body = $this->getJson('/admin/simulations/recalculate', [
            'date' => $this->yearsAgo(35),
            'totalContribution' => '50',
        ]);

        $this->assertFalse($body['success']);
        $this->assertSame('A contribuição total mínima é de R$ 100,00.', $body['message']);
    }

    public function testRecalculateRefusesRisksAboveTheTotal(): void
    {
        $body = $this->getJson('/admin/simulations/recalculate', [
            'date' => $this->yearsAgo(35),
            'totalContribution' => '200',
            'hasSurvivorsPension' => 1,
            'survivorsPensionContribution' => '300',
        ]);

        $this->assertFalse($body['success']);
        $this->assertSame('A contribuição total é menor que a soma das contribuições de risco.', $body['message']);
    }

    public function testUnderTheMinimumRiskAgeThereIsNoRisk(): void
    {
        $body = $this->getJson('/admin/simulations/recalculate', [
            'date' => $this->yearsAgo(PlanSimulator::MINIMUM_RISK_AGE - 1),
            'totalContribution' => '1000',
            'hasSurvivorsPension' => 1,
            'hasDisabilityRetirement' => 1,
        ]);

        $this->assertTrue($body['success']);
        $this->assertEqualsWithDelta(1000.0, $body['retirementContribution'], 0.001);
    }
}
