<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AdhesionPlan extends Entity
{
    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'benefit_entry_age' => true,
        'monthly_retirement_contribution' => true,
        'monthly_survivors_pension_contribution' => true,
        'survivors_pension_insured_capital' => true,
        'monthly_disability_retirement_contribution' => true,
        'disability_retirement_insured_capital' => true,
        'created' => true,
        'modified' => true,
        'adhesion_initial_data' => true,
    ];
}
