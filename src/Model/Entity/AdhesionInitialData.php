<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AdhesionInitialData extends Entity
{
    protected array $_accessible = [
        'id' => true,
        'storage_uuid' => true,
        'name' => true,
        'email' => true,
        'phone' => true,
        'created' => true,
        'modified' => true,
        'adhesion_personal_data' => true,
        'adhesion_plans' => true,
        'adhesion_addresses' => true,
        'adhesion_other_information' => true,
        'adhesion_documents' => true,
        'adhesion_dependents' => true,
        'clicksign_data' => true,
        'pix_transaction' => true,
    ];
}
