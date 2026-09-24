<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PlanParametersTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class PlanParametersTableTest extends TestCase
{
    protected array $fixtures = [
        'app.PlanParameters',
    ];

    private PlanParametersTable $PlanParameters;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \App\Model\Table\PlanParametersTable $table */
        $table = TableRegistry::getTableLocator()->get('PlanParameters');
        $this->PlanParameters = $table;
    }

    private function save(array $changes): \App\Model\Entity\PlanParameter
    {
        $parameters = $this->PlanParameters->patchEntity($this->PlanParameters->current(), $changes + [
            'minimum_monthly_contribution' => '100.00',
            'survivors_pension_percent' => '16.00',
            'disability_retirement_percent' => '10.00',
            'survivors_pension_floor' => '16.00',
            'disability_retirement_floor' => '10.00',
        ]);

        $this->PlanParameters->save($parameters);

        return $parameters;
    }

    public function testCurrentReturnsTheSeededRow(): void
    {
        $parameters = $this->PlanParameters->current();

        $this->assertSame(100.0, $parameters->minimumMonthlyContribution());
        $this->assertSame(0.16, $parameters->survivorsPensionRate());
        $this->assertSame(0.10, $parameters->disabilityRetirementRate());
    }

    public function testValidChangeIsAccepted(): void
    {
        $parameters = $this->save([
            'minimum_monthly_contribution' => '200.00',
            'survivors_pension_percent' => '20.00',
            'survivors_pension_floor' => '40.00',
        ]);

        $this->assertEmpty($parameters->getErrors());
        $this->assertSame(0.20, $this->PlanParameters->current()->survivorsPensionRate());
    }

    /**
     * A aposentadoria recebe o que sobra: somando 100% ou mais ela ficaria
     * zerada ou negativa, e a simulação devolveria saldo negativo sem erro.
     */
    public function testPercentagesCannotReachOneHundredTogether(): void
    {
        $parameters = $this->save([
            'survivors_pension_percent' => '70.00',
            'disability_retirement_percent' => '30.00',
        ]);

        $this->assertArrayHasKey('sumUnderOneHundred', $parameters->getError('disability_retirement_percent'));
    }

    /**
     * Um piso acima do que a própria taxa produz na contribuição mínima seria
     * contraditório: uma adesão no mínimo, calculada pela fórmula, já nasceria
     * violando o limite configurado para ela mesma.
     */
    public function testFloorCannotExceedWhatThePercentageYieldsAtTheMinimum(): void
    {
        // 16% de R$ 100,00 = R$ 16,00; um piso de R$ 20,00 é inalcançável.
        $parameters = $this->save(['survivors_pension_floor' => '20.00']);

        $this->assertArrayHasKey('reachableAtMinimum', $parameters->getError('survivors_pension_floor'));
    }

    public function testFloorExactlyAtWhatThePercentageYieldsIsAccepted(): void
    {
        $parameters = $this->save([
            'minimum_monthly_contribution' => '200.00',
            'survivors_pension_floor' => '32.00',
        ]);

        $this->assertEmpty($parameters->getErrors());
    }

    public function testValuesMustBePositive(): void
    {
        $this->assertNotEmpty($this->save(['minimum_monthly_contribution' => '0'])->getError('minimum_monthly_contribution'));
        $this->assertNotEmpty($this->save(['survivors_pension_percent' => '0'])->getError('survivors_pension_percent'));
        $this->assertNotEmpty($this->save(['disability_retirement_floor' => '0'])->getError('disability_retirement_floor'));
    }
}
