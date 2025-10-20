<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PeopleFixture
 */
class PeopleFixture extends TestFixture
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
                'marital_status' => 'Lorem ipsum dolor ',
                'gender' => 'Lorem ip',
                'email' => 'Lorem ipsum dolor sit amet',
                'phone' => 'Lorem ipsum dolor ',
                'is_legal_representative' => 1,
                'created' => 1761003178,
                'modified' => 1761003178,
            ],
        ];
        parent::init();
    }
}
