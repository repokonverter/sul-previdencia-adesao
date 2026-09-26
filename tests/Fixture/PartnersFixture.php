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
                'declaration_title' => 'DECLARAÇÃO DE COMPARECIMENTO',
                'declaration_institution_name' => 'SINDILOJAS',
                'declaration_body' => 'Texto de comparecimento do Sindilojas.',
                'created' => '2026-01-01 00:00:00',
                'modified' => '2026-01-01 00:00:00',
            ],
        ];
        parent::init();
    }
}
