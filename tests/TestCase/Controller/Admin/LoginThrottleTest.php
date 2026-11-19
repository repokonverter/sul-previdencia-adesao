<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestSuite\AdminPasswordTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Limite de tentativas no login: 5 erros por e-mail (ou 20 por IP) em 15
 * minutos bloqueiam, inclusive a senha certa, com a mesma mensagem para
 * e-mail cadastrado ou não.
 */
class LoginThrottleTest extends TestCase
{
    use AdminPasswordTestTrait;
    use IntegrationTestTrait;

    private const BLOCKED = 'Muitas tentativas. Tente novamente em alguns minutos ou use "Esqueci minha senha".';

    protected array $fixtures = [
        'app.Users',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPasswordTest();
    }

    private function login(string $email, string $password, string $ip = '10.0.0.1'): void
    {
        // Direto, e não por configRequest(): ele mescla e acumularia os IPs.
        $this->_request['environment']['REMOTE_ADDR'] = $ip;
        $this->post('/admin/users/login', ['email' => $email, 'password' => $password]);
    }

    public function testCorrectPasswordLogsIn(): void
    {
        $this->login('maria@exemplo.test', self::PASSWORD);

        $this->assertRedirect('/admin');
        $this->assertSession('maria@exemplo.test', 'Auth.email');
    }

    public function testFiveFailuresBlockEvenTheCorrectPassword(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->login('maria@exemplo.test', 'errada');
            $this->assertFlashMessage('Usuário ou senha incorretos.');
        }

        $this->login('maria@exemplo.test', self::PASSWORD);

        $this->assertResponseOk();
        $this->assertFlashMessage(self::BLOCKED);
        $this->assertSessionNotHasKey('Auth');
    }

    public function testBlockLooksTheSameForAnUnknownEmail(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->login('ninguem@exemplo.test', 'errada');
        }

        $this->assertFlashMessage(self::BLOCKED);
    }

    public function testSuccessResetsTheCounter(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->login('maria@exemplo.test', 'errada');
        }

        $this->login('maria@exemplo.test', self::PASSWORD);
        $this->assertRedirect('/admin');

        for ($i = 0; $i < 4; $i++) {
            $this->login('maria@exemplo.test', 'errada');
        }

        $this->login('maria@exemplo.test', self::PASSWORD);
        $this->assertRedirect('/admin');
    }

    public function testTwentyFailuresFromOneIpBlockOtherEmails(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->login("tentativa$i@exemplo.test", 'errada');
        }

        $this->login('maria@exemplo.test', self::PASSWORD);
        $this->assertFlashMessage(self::BLOCKED);

        $this->login('maria@exemplo.test', self::PASSWORD, '10.0.0.2');
        $this->assertRedirect('/admin');
    }
}
