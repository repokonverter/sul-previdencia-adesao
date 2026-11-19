<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class BrokersFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'partner_id' => 1,
                'name' => 'João da Silva',
                'cpf' => '52998224725',
                'token' => 'joaoabc23456',
                'active' => true,
                'created' => '2026-01-01 00:00:00',
                'updated' => '2026-01-01 00:00:00',
            ],
            [
                'id' => 2,
                'partner_id' => 1,
                'name' => 'Maria Inativa',
                'cpf' => null,
                'token' => 'mariabc23456',
                'active' => false,
                'created' => '2026-01-01 00:00:00',
                'updated' => '2026-01-01 00:00:00',
            ],
        ];
        parent::init();
    }
}
