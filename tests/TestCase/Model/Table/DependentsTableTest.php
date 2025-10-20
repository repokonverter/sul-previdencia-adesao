<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\DependentsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\DependentsTable Test Case
 */
class DependentsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\DependentsTable
     */
    protected $Dependents;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Dependents',
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
        $config = $this->getTableLocator()->exists('Dependents') ? [] : ['className' => DependentsTable::class];
        $this->Dependents = $this->getTableLocator()->get('Dependents', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Dependents);

        parent::tearDown();
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\DependentsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
