<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AdhesionDependent extends Entity
{
    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'name' => true,
        'cpf' => true,
        'birth_date' => true,
        'kinship' => true,
        'participation' => true,
        'created' => true,
        'modified' => true,
        'adhesion_initial_data' => true,
    ];
}
