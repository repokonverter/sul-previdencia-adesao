<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AddressesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AddressesTable Test Case
 */
class AddressesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AddressesTable
     */
    protected $Addresses;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Addresses',
        'app.Subscriptions',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Addresses') ? [] : ['className' => AddressesTable::class];
        $this->Addresses = $this->getTableLocator()->get('Addresses', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Addresses);

        parent::tearDown();
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\AddressesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
