<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Entity\Partner;
use Cake\Datasource\EntityInterface;

/**
 * Um plano a apresentar ao cliente: quem, quanto e com quais riscos.
 *
 * Existe para que o PDF da simulação e a projeção não dependam de uma adesão.
 * A aba Plano monta o seu a partir do que está gravado (fromAdhesion()); a
 * simulação avulsa do admin, a partir do formulário (PlanSimulationForm),
 * sem gravar nada. Risco não contratado já chega aqui com contribuição e
 * capital zerados, então quem consome não repete essa regra.
 */
final class PlanQuote
{
    public function __construct(
        public readonly string $clientName,
        public readonly string $birthDate,
        public readonly ?int $benefitEntryAge,
        public readonly float $retirementContribution,
        public readonly bool $hasSurvivorsPension,
        public readonly float $survivorsPensionContribution,
        public readonly float $survivorsPensionInsuredCapital,
        public readonly bool $hasDisabilityRetirement,
        public readonly float $disabilityRetirementContribution,
        public readonly float $disabilityRetirementInsuredCapital,
        public readonly ?Partner $partner = null,
        public readonly ?int $adhesionId = null,
    ) {
    }

    /**
     * Nulo enquanto a adesão não tem plano ou data de nascimento: sem idade
     * não há prazo até os 65.
     */
    public static function fromAdhesion(EntityInterface $adhesion): ?self
    {
        $personalData = $adhesion->get('adhesion_personal_data');
        $plan = $adhesion->get('adhesion_plan');
        $birthDate = $personalData?->birth_date?->format('Y-m-d');

        if ($birthDate === null || $plan === null) {
            return null;
        }

        [$retirement, $survivorsPension, $disabilityRetirement] = PlanSimulator::savedPlanContributions($plan);
        $hasSurvivorsPension = $plan->has_survivors_pension !== false;
        $hasDisabilityRetirement = $plan->has_disability_retirement !== false;

        return new self(
            clientName: (string)($personalData->name ?? $adhesion->get('name') ?? ''),
            birthDate: $birthDate,
            benefitEntryAge: $plan->benefit_entry_age === null ? null : (int)$plan->benefit_entry_age,
            retirementContribution: $retirement,
            hasSurvivorsPension: $hasSurvivorsPension,
            survivorsPensionContribution: $survivorsPension,
            survivorsPensionInsuredCapital: $hasSurvivorsPension ? (float)$plan->survivors_pension_insured_capital : 0.0,
            hasDisabilityRetirement: $hasDisabilityRetirement,
            disabilityRetirementContribution: $disabilityRetirement,
            disabilityRetirementInsuredCapital: $hasDisabilityRetirement ? (float)$plan->disability_retirement_insured_capital : 0.0,
            partner: $adhesion->get('association_partner'),
            adhesionId: $adhesion->get('id') === null ? null : (int)$adhesion->get('id'),
        );
    }

    public function age(): int
    {
        return PlanSimulator::ageOn($this->birthDate);
    }

    public function totalContribution(): float
    {
        return $this->retirementContribution + $this->survivorsPensionContribution + $this->disabilityRetirementContribution;
    }
}
