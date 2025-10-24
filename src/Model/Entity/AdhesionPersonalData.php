<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AdhesionPersonalData extends Entity
{
    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'plan_for' => true,
        'name' => true,
        'cpf' => true,
        'birth_date' => true,
        'nacionality' => true,
        'gender' => true,
        'marital_status' => true,
        'number_children' => true,
        'mother_name' => true,
        'father_name' => true,
        'name_legal_representative' => true,
        'cpf_legal_representative' => true,
        'affiliation_legal_representative' => true,
        'created' => true,
        'modified' => true,
        'adhesion_initial_data' => true,
    ];
}
