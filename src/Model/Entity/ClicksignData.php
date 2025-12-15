<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class ClicksignData extends Entity
{
    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'envelope_id' => true,
        'created' => true,
        'updated' => true,
    ];
}
