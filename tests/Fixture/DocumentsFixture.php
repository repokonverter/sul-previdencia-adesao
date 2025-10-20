<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * DocumentsFixture
 */
class DocumentsFixture extends TestFixture
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
                'type' => 'Lorem ipsum dolor sit amet',
                'file_path' => 'Lorem ipsum dolor sit amet',
                'issue_date' => '2025-10-20',
                'issuer' => 'Lorem ipsum dolor sit amet',
                'created' => 1761003227,
                'modified' => 1761003227,
            ],
        ];
        parent::init();
    }
}
