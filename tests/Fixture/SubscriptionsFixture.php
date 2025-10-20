<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SubscriptionsFixture
 */
class SubscriptionsFixture extends TestFixture
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
                'plan_type' => 'Lorem ipsum dolor sit amet',
                'plan_value' => 1.5,
                'periodicity' => 'Lorem ipsum dolor ',
                'payment_method' => 'Lorem ipsum dolor sit amet',
                'created' => 1761003162,
                'modified' => 1761003162,
            ],
        ];
        parent::init();
    }
}
