<?php

declare(strict_types=1);

namespace App\Test\TestSuite;

use App\Model\Entity\User;
use App\Services\ResendService;
use Cake\Cache\Cache;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;

/**
 * Base comum dos testes de senha do admin: um usuário com senha conhecida,
 * o envio de e-mail trocado por RecordingMailer e os contadores de
 * tentativa zerados (ficam em disco, entre um teste e outro).
 *
 * Quem usa precisa do fixture app.Users e do IntegrationTestTrait.
 */
trait AdminPasswordTestTrait
{
    protected const PASSWORD = 'senha antiga bem comprida';

    protected RecordingMailer $mailer;

    protected function setUpPasswordTest(): User
    {
        Cache::clear('throttle');

        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();

        $this->mailer = new RecordingMailer();
        $this->mockService(ResendService::class, fn () => $this->mailer);

        $users = TableRegistry::getTableLocator()->get('Users');
        $users->getConnection()
            ->execute("SELECT setval(pg_get_serial_sequence('users', 'id'), (SELECT MAX(id) FROM users))");

        $user = $users->newEmptyEntity();
        $user->name = 'Maria Souza';
        $user->email = 'maria@exemplo.test';
        $user->password = self::PASSWORD;
        $user->role = User::ROLE_ADMIN;
        $user->password_changed_at = new DateTime('2026-01-01 10:00:00');

        return $users->saveOrFail($user);
    }

    protected function userById(int $id): User
    {
        return TableRegistry::getTableLocator()->get('Users')->get($id);
    }

    protected function loginSession(User $user): void
    {
        $this->session(['Auth' => $this->userById($user->id)]);
    }
}
