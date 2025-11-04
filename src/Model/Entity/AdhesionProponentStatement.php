<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AdhesionProponentStatement extends Entity
{
    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'health_problem' => true,
        'health_problem_obs' => true,
        'heart_disease' => true,
        'heart_disease_obs' => true,
        'suffered_organ_defects' => true,
        'suffered_organ_defects_obs' => true,
        'surgery' => true,
        'surgery_obs' => true,
        'away' => true,
        'away_obs' => true,
        'practices_parachuting' => true,
        'practices_parachuting_obs' => true,
        'smoker' => true,
        'smoker_type' => true,
        'smoker_type_obs' => true,
        'smoker_qty' => true,
        'weight' => true,
        'height' => true,
        'gripe' => true,
        'gripe_obs' => true,
        'covid' => true,
        'covid_obs' => true,
        'covid_sequelae' => true,
        'covid_sequelae_obs' => true,
        'created' => true,
        'modified' => true,
        'adhesion_initial_data' => true,
    ];
}
