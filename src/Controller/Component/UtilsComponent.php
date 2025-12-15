<?php

declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;

class UtilsComponent extends Component
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function formatCpf($value) {
        $cpf = preg_replace('/\D/', '', $value);

        if (strlen($cpf) === 11)
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);

        return $value;
    }
}
