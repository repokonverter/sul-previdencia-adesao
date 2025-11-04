<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AdhesionPensionScheme extends Entity
{
    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'pension_scheme' => true,
        'name' => true,
        'cpf' => true,
        'kinship' => true,
        'created' => true,
        'modified' => true,
        'adhesion_initial_data' => true,
    ];
}
