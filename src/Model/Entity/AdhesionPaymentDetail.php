<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class AdhesionPaymentDetail extends Entity
{
    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'due_date' => true,
        'total_contribution' => true,
        'payment_type' => true,
        'account_holder_name' => true,
        'cpf' => true,
        'bank_number' => true,
        'bank_name' => true,
        'branch_number' => true,
        'account_number' => true,
        'created' => true,
        'modified' => true,
        'adhesion_initial_data' => true,
    ];
}
