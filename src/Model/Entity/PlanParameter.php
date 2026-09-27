<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * PlanParameter Entity
 *
 * Linha única com os números do plano que antes viviam no código: as taxas
 * estavam cravadas na procedure `simulacao_previdencia` e o mínimo numa
 * constante de [[SimulatorController]].
 *
 * Os percentuais são guardados como percentual (16.00, e não 0.16) porque é
 * assim que o admin digita e lê; quem precisa da fração usa
 * survivorsPensionRate() / disabilityRetirementRate(), para que a divisão por
 * 100 exista num lugar só.
 *
 * Os pisos não são o resultado da fórmula — são o mínimo que o admin pode
 * digitar ao ajustar o valor de um risco à mão, e só valem para risco
 * contratado.
 *
 * @property int $id
 * @property string $minimum_monthly_contribution
 * @property string $survivors_pension_percent
 * @property string $disability_retirement_percent
 * @property string $survivors_pension_floor
 * @property string $disability_retirement_floor
 * @property int $resume_link_days
 */
class PlanParameter extends Entity
{
    protected array $_accessible = [
        'minimum_monthly_contribution' => true,
        'survivors_pension_percent' => true,
        'disability_retirement_percent' => true,
        'survivors_pension_floor' => true,
        'disability_retirement_floor' => true,
        'resume_link_days' => true,
        'created' => true,
        'updated' => true,
    ];

    public function minimumMonthlyContribution(): float
    {
        return (float)$this->minimum_monthly_contribution;
    }

    public function survivorsPensionRate(): float
    {
        return (float)$this->survivors_pension_percent / 100;
    }

    public function disabilityRetirementRate(): float
    {
        return (float)$this->disability_retirement_percent / 100;
    }

    public function resumeLinkDays(): int
    {
        return (int)$this->resume_link_days;
    }

    public function survivorsPensionFloor(): float
    {
        return (float)$this->survivors_pension_floor;
    }

    public function disabilityRetirementFloor(): float
    {
        return (float)$this->disability_retirement_floor;
    }
}
