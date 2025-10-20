<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * DependentsFixture
 */
class DependentsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'subscription_id' => 1,
                'name' => 'Lorem ipsum dolor sit amet',
                'cpf' => 'Lorem ipsum ',
                'birth_date' => '2025-10-20',
                'kinship' => 'Lorem ipsum dolor sit amet',
                'created' => 1761003216,
                'modified' => 1761003216,
            ],
        ];
        parent::init();
    }
}
