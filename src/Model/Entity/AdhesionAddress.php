<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AdhesionAddress extends Entity
{
    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'cep' => true,
        'address' => true,
        'number' => true,
        'complement' => true,
        'neighborhood' => true,
        'city' => true,
        'state' => true,
        'created' => true,
        'modified' => true,
        'adhesion_initial_data' => true,
    ];
}
