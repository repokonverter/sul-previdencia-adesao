<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * As duas regras que protegem o plano contra o que o admin pode fazer nele:
 * baixar a contribuição de um risco abaixo do mínimo, e religar um risco numa
 * adesão sem Declaração Pessoal de Saúde.
 */
class AdhesionPlansTableTest extends TestCase
{
    protected array $fixtures = [
        'app.PlanParameters',
    ];

    private function table(string $name): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($name);
    }

    private function createAdhesion(bool $withStatement = false): int
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);

        if ($withStatement) {
            $this->table('AdhesionProponentStatements')->saveOrFail(
                $this->table('AdhesionProponentStatements')->newEntity([
                    'adhesion_initial_data_id' => $adhesion->id,
                    'health_problem' => false,
                ])
            );
        }

        return (int)$adhesion->id;
    }

    private function createPlan(int $adhesionId, array $data = []): \Cake\Datasource\EntityInterface
    {
        $plans = $this->table('AdhesionPlans');
        $plan = $plans->newEntity($data + [
            'adhesion_initial_data_id' => $adhesionId,
            'monthly_survivors_pension_contribution' => '160.00',
            'monthly_disability_retirement_contribution' => '100.00',
            'has_survivors_pension' => true,
            'has_disability_retirement' => true,
        ]);
        $plans->saveOrFail($plan);

        return $plan;
    }

    public function testLoweringAContractedRiskBelowItsFloorIsRejected(): void
    {
        $plans = $this->table('AdhesionPlans');
        $plan = $this->createPlan($this->createAdhesion());

        $saved = $plans->save($plans->patchEntity($plan, [
            'monthly_survivors_pension_contribution' => '10.00',
        ]));

        $this->assertFalse((bool)$saved);
        $this->assertArrayHasKey('aboveFloor', $plan->getError('monthly_survivors_pension_contribution'));
        $this->assertStringContainsString('R$ 16,00', implode(' ', $plan->getError('monthly_survivors_pension_contribution')));
    }

    public function testExactlyTheFloorIsAccepted(): void
    {
        $plans = $this->table('AdhesionPlans');
        $plan = $this->createPlan($this->createAdhesion());

        $this->assertNotFalse($plans->save($plans->patchEntity($plan, [
            'monthly_survivors_pension_contribution' => '16.00',
            'monthly_disability_retirement_contribution' => '10.00',
        ])));
    }

    /**
     * Risco removido não tem piso: a contribuição dele é zero por definição.
     */
    public function testARemovedRiskMayContributeNothing(): void
    {
        $plans = $this->table('AdhesionPlans');
        $plan = $this->createPlan($this->createAdhesion());

        $this->assertNotFalse($plans->save($plans->patchEntity($plan, [
            'has_survivors_pension' => false,
            'monthly_survivors_pension_contribution' => '0.00',
        ])));
    }

    public function testTurningARiskBackOnWithoutAHealthDeclarationIsRejected(): void
    {
        $plans = $this->table('AdhesionPlans');
        $plan = $this->createPlan($this->createAdhesion(), ['has_survivors_pension' => false]);

        $saved = $plans->save($plans->patchEntity($plan, [
            'has_survivors_pension' => true,
            'monthly_survivors_pension_contribution' => '160.00',
        ]));

        $this->assertFalse((bool)$saved);
        $this->assertArrayHasKey('healthDeclarationRequired', $plan->getError('has_survivors_pension'));
    }

    public function testTurningARiskBackOnWithAHealthDeclarationIsAccepted(): void
    {
        $plans = $this->table('AdhesionPlans');
        $plan = $this->createPlan($this->createAdhesion(withStatement: true), ['has_survivors_pension' => false]);

        $this->assertNotFalse($plans->save($plans->patchEntity($plan, [
            'has_survivors_pension' => true,
            'monthly_survivors_pension_contribution' => '160.00',
        ])));
    }

    /**
     * No formulário público o plano nasce com os dois riscos e a declaração
     * vem na etapa seguinte. Exigir a declaração aqui travaria toda adesão
     * nova, que é o oposto do que a regra existe para fazer.
     */
    public function testANewPlanDoesNotNeedADeclarationYet(): void
    {
        $this->assertNotFalse($this->createPlan($this->createAdhesion()));
    }

    /**
     * Mexer no plano sem tocar nos riscos não precisa de declaração: a regra
     * só olha o risco que passou de removido para contratado.
     */
    public function testEditingOtherFieldsDoesNotDemandADeclaration(): void
    {
        $plans = $this->table('AdhesionPlans');
        $plan = $this->createPlan($this->createAdhesion(), ['has_survivors_pension' => false]);

        $this->assertNotFalse($plans->save($plans->patchEntity($plan, ['benefit_entry_age' => 70])));
    }
}
