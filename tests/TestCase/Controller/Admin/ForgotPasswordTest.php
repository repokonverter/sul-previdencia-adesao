<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Entity\User;
use App\Test\TestSuite\AdminPasswordTestTrait;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Esqueci a senha: resposta neutra, link de uso único e sessões antigas
 * derrubadas na troca.
 */
class ForgotPasswordTest extends TestCase
{
    use AdminPasswordTestTrait;
    use IntegrationTestTrait;

    private const NEUTRAL = 'Se este e-mail estiver cadastrado, você receberá um link em instantes.';
    private const NEW_PASSWORD = 'uma frase nova e longa';

    protected array $fixtures = [
        'app.Users',
    ];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->setUpPasswordTest();
    }

    private function requestLink(string $email, string $ip = '10.0.0.1'): void
    {
        // Direto, e não por configRequest(): ele mescla e acumularia os IPs.
        $this->_request['environment']['REMOTE_ADDR'] = $ip;
        $this->post('/admin/users/forgot-password', ['email' => $email]);
    }

    public function testLoginPageLinksToForgotPassword(): void
    {
        $this->get('/admin/users/login');

        $this->assertResponseOk();
        $this->assertResponseContains('/admin/users/forgot-password');
    }

    public function testAnswerIsTheSameWhetherTheEmailExistsOrNot(): void
    {
        $this->requestLink('MARIA@exemplo.test ');
        $this->assertRedirect(['action' => 'login']);
        $this->assertFlashMessage(self::NEUTRAL);
        $this->assertCount(1, $this->mailer->sent);
        $this->assertSame(['maria@exemplo.test'], $this->mailer->sent[0]['to']);
        $this->assertStringContainsString('vale por 60 minutos', $this->mailer->sent[0]['html']);

        $this->requestLink('ninguem@exemplo.test');
        $this->assertRedirect(['action' => 'login']);
        $this->assertFlashMessage(self::NEUTRAL);
        $this->assertCount(1, $this->mailer->sent, 'Nenhum e-mail para quem não existe.');
    }

    public function testEmailLimitStopsSendingWithoutChangingTheAnswer(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->requestLink('maria@exemplo.test', "10.0.0.$i");
            $this->assertFlashMessage(self::NEUTRAL);
        }

        $this->assertCount(3, $this->mailer->sent, '3 por hora por e-mail.');
    }

    public function testIpLimitStopsSendingWithoutChangingTheAnswer(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->requestLink("outro$i@exemplo.test");
        }

        $this->requestLink('maria@exemplo.test');
        $this->assertFlashMessage(self::NEUTRAL);
        $this->assertSame([], $this->mailer->sent, '5 por 15 minutos por IP.');
    }

    public function testResetLinkChangesThePasswordOnceAndSendsTheNotice(): void
    {
        $this->requestLink('maria@exemplo.test');
        $token = $this->mailer->lastToken();

        $this->get("/admin/users/reset-password/$token");
        $this->assertResponseOk();
        $this->assertResponseContains('Redefinir senha');
        $this->assertHeader('Referrer-Policy', 'no-referrer');

        $this->post("/admin/users/reset-password/$token", [
            'new_password' => self::NEW_PASSWORD,
            'confirm_password' => self::NEW_PASSWORD,
        ]);
        $this->assertRedirect(['action' => 'login']);
        $this->assertFlashMessage('Senha alterada. Entre com a nova senha.');
        $this->assertTrue((new DefaultPasswordHasher())->check(self::NEW_PASSWORD, $this->userById($this->user->id)->password));
        $this->assertSame('Sua senha do painel da Sul Previdência foi alterada', end($this->mailer->sent)['subject']);

        $this->get("/admin/users/reset-password/$token");
        $this->assertResponseContains('Link inválido ou expirado');
    }

    public function testWeakPasswordIsRejectedAndTheLinkStillWorks(): void
    {
        $this->requestLink('maria@exemplo.test');
        $token = $this->mailer->lastToken();

        $this->post("/admin/users/reset-password/$token", [
            'new_password' => 'maria souza 2026 abc',
            'confirm_password' => 'outra coisa',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('A senha não pode conter seu nome ou e-mail.');
        $this->assertResponseContains('A confirmação não confere com a nova senha.');

        $this->get("/admin/users/reset-password/$token");
        $this->assertResponseNotContains('Link inválido ou expirado');
    }

    public function testInvalidTokenShowsTheSameMessage(): void
    {
        $this->get('/admin/users/reset-password/' . str_repeat('a', 64));

        $this->assertResponseOk();
        $this->assertResponseContains('Link inválido ou expirado');
        $this->assertResponseContains('/admin/users/forgot-password');
    }

    /**
     * Quem estava logado antes da troca (em outro navegador, por exemplo)
     * perde o acesso.
     */
    public function testSessionOpenedBeforeTheChangeIsEnded(): void
    {
        $this->loginSession($this->user);
        $this->get('/admin/users/change-password');
        $this->assertResponseOk();

        $users = $this->getTableLocator()->get('Users');
        $users->changePassword($this->userById($this->user->id), self::NEW_PASSWORD, self::NEW_PASSWORD);

        $this->get('/admin/adhesions');
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);
        $this->assertFlashMessage('Sua senha foi alterada. Entre novamente.');
    }
}
