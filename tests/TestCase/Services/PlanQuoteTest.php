<?php

declare(strict_types=1);

namespace App\Test\TestCase\Services;

use App\Model\Entity\AdhesionInitialData;
use App\Model\Entity\AdhesionPersonalData;
use App\Model\Entity\AdhesionPlan;
use App\Services\PlanQuote;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;

class PlanQuoteTest extends TestCase
{
    private function adhesion(array $plan = [], array $personalData = []): AdhesionInitialData
    {
        return new AdhesionInitialData([
            'id' => 42,
            'name' => 'Nome do cadastro inicial',
            'adhesion_personal_data' => new AdhesionPersonalData($personalData + [
                'name' => 'Fulana da Silva',
                'birth_date' => new Date('1985-03-10'),
            ]),
            'adhesion_plan' => new AdhesionPlan($plan + [
                'benefit_entry_age' => 65,
                'monthly_retirement_contribution' => '740.00',
                'monthly_survivors_pension_contribution' => '160.00',
                'survivors_pension_insured_capital' => '1000.00',
                'monthly_disability_retirement_contribution' => '100.00',
                'disability_retirement_insured_capital' => '2000.00',
            ]),
        ]);
    }

    public function testFromAdhesionReadsTheSavedPlan(): void
    {
        $quote = PlanQuote::fromAdhesion($this->adhesion());

        $this->assertSame('Fulana da Silva', $quote->clientName);
        $this->assertSame('1985-03-10', $quote->birthDate);
        $this->assertSame(65, $quote->benefitEntryAge);
        $this->assertSame(1000.0, $quote->totalContribution());
        $this->assertSame(2000.0, $quote->disabilityRetirementInsuredCapital);
        $this->assertSame(42, $quote->adhesionId);
    }

    /**
     * A flag diz se o risco existe: o valor que ficou na coluna de um risco
     * desmarcado não entra na conta nem no PDF.
     */
    public function testAnUncheckedRiskCountsAsZero(): void
    {
        $quote = PlanQuote::fromAdhesion($this->adhesion(['has_survivors_pension' => false]));

        $this->assertFalse($quote->hasSurvivorsPension);
        $this->assertSame(0.0, $quote->survivorsPensionContribution);
        $this->assertSame(0.0, $quote->survivorsPensionInsuredCapital);
        $this->assertSame(840.0, $quote->totalContribution());
    }

    public function testWithoutBirthDateThereIsNoQuote(): void
    {
        $this->assertNull(PlanQuote::fromAdhesion($this->adhesion(personalData: ['birth_date' => null])));
    }
}
