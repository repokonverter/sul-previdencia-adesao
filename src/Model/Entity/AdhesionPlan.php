<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AdhesionPlan extends Entity
{
    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'benefit_entry_age' => true,
        'monthly_contribuition_amount' => true,
        'value_founding_contribution' => true,
        'insured_capital' => true,
        'contribution' => true,
        'created' => true,
        'modified' => true,
        'adhesion_initial_data' => true,
    ];
}
