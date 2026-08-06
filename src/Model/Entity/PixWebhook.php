<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class PixWebhook extends Entity
{
    protected array $_accessible = [
        'chave' => true,
        'token' => true,
        'url' => true,
        'created' => true,
        'updated' => true,
    ];
}
