<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AddressesFixture
 */
class AddressesFixture extends TestFixture
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
                'cep' => 'Lorem i',
                'address' => 'Lorem ipsum dolor sit amet',
                'number' => 'Lorem ip',
                'complement' => 'Lorem ipsum dolor sit amet',
                'neighborhood' => 'Lorem ipsum dolor sit amet',
                'city' => 'Lorem ipsum dolor sit amet',
                'state' => 'Lo',
                'created' => 1761003196,
                'modified' => 1761003196,
            ],
        ];
        parent::init();
    }
}
