<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class ClicksignWebhook extends Entity
{
    protected array $_accessible = [
        'clicksign_webhook_id' => true,
        'token' => true,
        'url' => true,
        'created' => true,
        'updated' => true,
    ];
}
