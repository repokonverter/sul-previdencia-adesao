<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class PromotionalCodesFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'partner_id' => 1,
                'code' => 'PARCEIRO10',
                'active' => true,
                'created' => '2026-01-01 00:00:00',
                'modified' => '2026-01-01 00:00:00',
            ],
            [
                'id' => 2,
                'partner_id' => 2,
                'code' => 'SINDILOJAS2026',
                'active' => true,
                'created' => '2026-01-01 00:00:00',
                'modified' => '2026-01-01 00:00:00',
            ],
        ];
        parent::init();
    }
}
