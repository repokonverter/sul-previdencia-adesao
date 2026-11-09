<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Controller\Admin\UsersController;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\UsersController Test Case
 *
 * @link \App\Controller\Admin\UsersController
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableCsrfToken();
        $this->session([
            'Auth' => TableRegistry::getTableLocator()->get('Users')->get(1),
        ]);

        // A fixture grava ids fixos sem avançar a sequence; o próximo INSERT
        // tentaria o id 1.
        TableRegistry::getTableLocator()->get('Users')->getConnection()
            ->execute("SELECT setval(pg_get_serial_sequence('users', 'id'), (SELECT MAX(id) FROM users))");
    }

    public function testAddSavesTheRole(): void
    {
        $this->post('/admin/users/add', [
            'name' => 'Nova Consulta',
            'email' => 'nova@exemplo.test',
            'password' => 'segredo123',
            'role' => 'user',
        ]);

        $this->assertRedirect(['action' => 'index']);
        $user = TableRegistry::getTableLocator()->get('Users')->findByEmail('nova@exemplo.test')->firstOrFail();
        $this->assertSame('user', $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function testAddRejectsAnUnknownRole(): void
    {
        $this->post('/admin/users/add', [
            'name' => 'Nova Consulta',
            'email' => 'nova@exemplo.test',
            'password' => 'segredo123',
            'role' => 'root',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Perfil inválido.');
        $this->assertFalse(TableRegistry::getTableLocator()->get('Users')->exists(['email' => 'nova@exemplo.test']));
    }

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\Admin\UsersController::index()
     */
    public function testIndex(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\Admin\UsersController::view()
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\Admin\UsersController::add()
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\Admin\UsersController::edit()
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\Admin\UsersController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
