<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AdhesionOtherInformation extends Entity
{
    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'mainOccupation' => true,
        'category' => true,
        'brazilian_resident' => true,
        'politically_exposed' => true,
        'obligation_other_countries' => true,
        'created' => true,
        'modified' => true,
        'adhesion_initial_data' => true,
    ];
}
