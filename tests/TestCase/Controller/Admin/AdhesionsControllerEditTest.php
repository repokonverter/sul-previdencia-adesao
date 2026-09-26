<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Services\AdhesionAuditor;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * Cobre a trilha de auditoria da edição de adesão no admin: quem mexeu, o que
 * mudou, e a marca de que o plano passou a ter valor ajustado à mão.
 */
class AdhesionsControllerEditTest extends TestCase
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

    private function table(string $name): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($name);
    }

    /**
     * @return array{0: int, 1: int} [adhesionId, planId]
     */
    private function createAdhesion(): array
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'email' => 'fulano@exemplo.test',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);

        $plans = $this->table('AdhesionPlans');
        $plan = $plans->newEntity([
            'adhesion_initial_data_id' => $adhesion->id,
            'benefit_entry_age' => 65,
            'monthly_retirement_contribution' => '740.00',
            'monthly_survivors_pension_contribution' => '160.00',
            'survivors_pension_insured_capital' => '1000.00',
            'monthly_disability_retirement_contribution' => '100.00',
            'disability_retirement_insured_capital' => '1000.00',
        ]);
        $plans->saveOrFail($plan);

        return [(int)$adhesion->id, (int)$plan->id];
    }

    private function auditsFor(int $adhesionId): array
    {
        return $this->table('AdhesionAudits')->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])
            ->orderBy(['created' => 'DESC', 'id' => 'DESC'])
            ->toArray();
    }

    public function testEditingThePlanRecordsWhoChangedWhatAndMarksTheOverride(): void
    {
        [$adhesionId, $planId] = $this->createAdhesion();

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'name' => 'Fulano de Tal',
            'adhesion_plan' => [
                'id' => $planId,
                'benefit_entry_age' => 65,
                'monthly_retirement_contribution' => '790.00',
                'monthly_survivors_pension_contribution' => '110.00',
                'survivors_pension_insured_capital' => '1000.00',
                'monthly_disability_retirement_contribution' => '100.00',
                'disability_retirement_insured_capital' => '1000.00',
            ],
        ]);

        $this->assertRedirect(['action' => 'view', $adhesionId]);

        $audits = $this->auditsFor($adhesionId);
        $this->assertCount(1, $audits);

        $changes = $audits[0]->changeList();
        $this->assertSame(
            ['160.00', '110.00'],
            $changes['adhesion_plan.monthly_survivors_pension_contribution']
        );
        $this->assertSame(1, $audits[0]->user_id);
        $this->assertNotEmpty($audits[0]->user_name);

        $plan = $this->table('AdhesionPlans')->get($planId);
        $this->assertTrue($plan->admin_overridden);
        $this->assertSame(1, $plan->admin_overridden_by_user_id);
        $this->assertNotNull($plan->admin_overridden_at);
    }

    /**
     * Editar um dado cadastral não é ajuste de valor: a adesão fica auditada,
     * mas o plano não passa a contar como negociado à mão — senão o passo do
     * Plano travaria para o cliente por causa de uma correção de telefone.
     */
    public function testEditingOutsideThePlanDoesNotMarkTheOverride(): void
    {
        [$adhesionId, $planId] = $this->createAdhesion();

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'name' => 'Beltrano de Tal',
            'phone' => '48988888888',
        ]);

        $this->assertRedirect(['action' => 'view', $adhesionId]);

        $changes = $this->auditsFor($adhesionId)[0]->changeList();
        $this->assertSame(['Fulano de Tal', 'Beltrano de Tal'], $changes['name']);
        $this->assertArrayHasKey('phone', $changes);

        $this->assertFalse($this->table('AdhesionPlans')->get($planId)->admin_overridden);
    }

    public function testSavingWithoutChangingAnythingRecordsNothing(): void
    {
        [$adhesionId] = $this->createAdhesion();

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'name' => 'Fulano de Tal',
            'email' => 'fulano@exemplo.test',
            'phone' => '48999999999',
        ]);

        $this->assertRedirect(['action' => 'view', $adhesionId]);
        $this->assertSame([], $this->auditsFor($adhesionId));
    }

    public function testHistoryTabShowsTheChange(): void
    {
        [$adhesionId, $planId] = $this->createAdhesion();

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'adhesion_plan' => [
                'id' => $planId,
                'monthly_survivors_pension_contribution' => '110.00',
            ],
        ]);

        $this->get("/admin/adhesions/view/$adhesionId?tab=audits");

        $this->assertResponseOk();
        $this->assertResponseContains('Histórico de alterações');
        $this->assertResponseContains('Plano › Monthly survivors pension contribution');
        $this->assertResponseContains('ajustados manualmente');
    }

    public function testSnapshotContainsEverythingTheEditFormCanTouch(): void
    {
        [$adhesionId] = $this->createAdhesion();

        $snapshot = AdhesionAuditor::snapshot(
            $this->table('AdhesionInitialDatas')->get($adhesionId, contain: AdhesionAuditor::CONTAINS)
        );

        // Os campos economicamente sensíveis precisam estar no retrato, senão
        // uma alteração neles passaria despercebida pela trilha.
        $this->assertArrayHasKey('adhesion_plan.monthly_survivors_pension_contribution', $snapshot);
        $this->assertArrayHasKey('adhesion_plan.has_survivors_pension', $snapshot);
        $this->assertArrayHasKey('adhesion_dependents.total', $snapshot);
    }
}
