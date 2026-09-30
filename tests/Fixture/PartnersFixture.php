<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class PartnersFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'name' => 'Corretora Parceira',
                'color' => '#0d6efd',
                'active' => true,
                'is_association' => false,
                'created' => '2026-01-01 00:00:00',
                'modified' => '2026-01-01 00:00:00',
            ],
            [
                'id' => 2,
                'name' => 'Sindilojas Exemplo',
                'color' => '#28a745',
                'active' => true,
                'is_association' => true,
                'company_name' => 'Sindicato do Comércio Varejista Exemplo',
                'company_cnpj' => '12.345.678/0001-99',
                'created' => '2026-01-01 00:00:00',
                'modified' => '2026-01-01 00:00:00',
            ],
        ];
        parent::init();
    }
}
