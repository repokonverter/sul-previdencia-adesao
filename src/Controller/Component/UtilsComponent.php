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

    public function formatCEP($value) {
        $cep = preg_replace('/\D/', '', $value);

        if (strlen($cep) === 8)
            return preg_replace('/(\d{2})(\d{3})(\d{3})/', '$1.$2-$3', $cep);

        return $value;
    }

    public function monthName($month) {
        $months = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        return $months[$month];
    }
}
