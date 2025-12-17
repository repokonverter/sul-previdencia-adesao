<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class PixTransaction extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'txid' => true,
        'created' => true,
        'modified' => true,
        'adhesion_initial_data' => true,
        'paid' => true,
        'payment_date' => true,
        'amount' => true,
    ];
}
