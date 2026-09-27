<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AdhesionDocument extends Entity
{
    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'type' => true,
        // Faltava, e por isso "Outro tipo de documento" nunca chegava ao banco:
        // patchEntity descarta em silêncio o que não está declarado aqui.
        'type_other' => true,
        'document_number' => true,
        'issue_date' => true,
        'issuer' => true,
        'place_birth' => true,
        'created' => true,
        'modified' => true,
        'adhesion_initial_data' => true,
    ];
}
