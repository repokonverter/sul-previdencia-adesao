<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Services\ResendService;
use App\Test\TestSuite\RecordingMailer;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Cache\Cache;
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

    private RecordingMailer $mailer;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear('throttle');
        $this->mailer = new RecordingMailer();
        $this->mockService(ResendService::class, fn () => $this->mailer);

        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
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

        $user = TableRegistry::getTableLocator()->get('Users')->findByEmail('nova@exemplo.test')->firstOrFail();
        $this->assertRedirect(['action' => 'view', $user->id]);
        $this->assertSame('user', $user->role);
        $this->assertFalse($user->isAdmin());
    }

    /**
     * O admin não define a senha de ninguém: a enviada é ignorada, o usuário
     * nasce sem senha conhecida e recebe o convite.
     */
    public function testAddSendsAnInviteAndIgnoresAnyPassword(): void
    {
        $this->post('/admin/users/add', [
            'name' => 'Nova Consulta',
            'email' => 'nova@exemplo.test',
            'password' => 'escolhida pelo admin 123',
            'role' => 'user',
        ]);

        $user = TableRegistry::getTableLocator()->get('Users')->findByEmail('nova@exemplo.test')->firstOrFail();
        $this->assertFalse((new DefaultPasswordHasher())->check('escolhida pelo admin 123', $user->password));
        $this->assertFalse($user->hasDefinedPassword());
        $this->assertFlashMessage('O usuário foi criado e recebeu por e-mail o link para definir a senha.');

        $this->assertSame(['nova@exemplo.test'], $this->mailer->sent[0]['to']);
        $this->assertStringContainsString('Você recebeu acesso ao painel', $this->mailer->sent[0]['html']);
        $this->assertStringContainsString('vale por 48 horas', $this->mailer->sent[0]['html']);
    }

    public function testInviteLinkDefinesThePassword(): void
    {
        $this->post('/admin/users/add', ['name' => 'Nova Consulta', 'email' => 'nova@exemplo.test', 'role' => 'user']);
        $token = $this->mailer->lastToken();

        $this->get("/admin/users/reset-password/$token");
        $this->assertResponseContains('Defina sua senha');

        $this->post("/admin/users/reset-password/$token", [
            'new_password' => 'cavalo bateria grampo',
            'confirm_password' => 'cavalo bateria grampo',
        ]);
        $this->assertRedirect(['action' => 'login']);
        $this->assertFlashMessage('Senha definida. Entre com o seu e-mail e a nova senha.');

        $user = TableRegistry::getTableLocator()->get('Users')->findByEmail('nova@exemplo.test')->firstOrFail();
        $this->assertTrue($user->hasDefinedPassword());
    }

    public function testUserIsCreatedEvenIfTheInviteFails(): void
    {
        $this->mailer->fail = true;

        $this->post('/admin/users/add', ['name' => 'Nova Consulta', 'email' => 'nova@exemplo.test', 'role' => 'user']);

        $this->assertTrue(TableRegistry::getTableLocator()->get('Users')->exists(['email' => 'nova@exemplo.test']));
        $this->assertFlashMessage('O usuário foi criado, mas o e-mail com o link não pôde ser enviado. Use "Enviar link para definir senha" na tela do usuário.');
    }

    public function testEditIgnoresThePassword(): void
    {
        $users = TableRegistry::getTableLocator()->get('Users');
        $before = $users->get(2)->password;

        $this->post('/admin/users/edit/2', [
            'name' => 'Usuário Consulta',
            'email' => 'consulta@exemplo.test',
            'password' => 'trocada pelo admin 123',
            'role' => 'user',
        ]);

        $this->assertRedirect(['action' => 'index']);
        $this->assertSame($before, $users->get(2)->password);
    }

    public function testAdminSendsAResetLinkToAUserWithAPassword(): void
    {
        $users = TableRegistry::getTableLocator()->get('Users');
        $users->updateAll(['password_changed_at' => '2026-01-01 10:00:00', 'email' => 'consulta@exemplo.test'], ['id' => 2]);

        $this->get('/admin/users/view/2');
        $this->assertResponseContains('Enviar link para redefinir senha');

        $this->post('/admin/users/send-password-link/2');

        $this->assertRedirect(['action' => 'view', 2]);
        $this->assertFlashMessage('Link enviado para consulta@exemplo.test.');
        $this->assertStringContainsString('Redefina sua senha', $this->mailer->sent[0]['html']);
    }

    public function testUserRoleCannotSendPasswordLinks(): void
    {
        $this->session(['Auth' => TableRegistry::getTableLocator()->get('Users')->get(2)]);

        $this->post('/admin/users/send-password-link/1');

        $this->assertSame([], $this->mailer->sent);
    }

    public function testFormHasNoPasswordField(): void
    {
        $this->get('/admin/users/add');
        $this->assertResponseNotContains('name="password"');
        $this->assertResponseContains('O usuário receberá por e-mail um link para definir a própria senha');

        $this->get('/admin/users/edit/2');
        $this->assertResponseNotContains('name="password"');
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
