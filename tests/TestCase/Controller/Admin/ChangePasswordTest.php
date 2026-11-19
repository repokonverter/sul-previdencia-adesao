<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Entity\User;
use App\Test\TestSuite\AdminPasswordTestTrait;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * "Alterar minha senha", logado: pede a senha atual, mantém esta sessão e
 * derruba as outras.
 */
class ChangePasswordTest extends TestCase
{
    use AdminPasswordTestTrait;
    use IntegrationTestTrait;

    private const NEW_PASSWORD = 'uma frase nova e longa';

    protected array $fixtures = [
        'app.Users',
    ];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->setUpPasswordTest();
        $this->loginSession($this->user);
    }

    private function change(string $current, string $new = self::NEW_PASSWORD): void
    {
        $this->post('/admin/users/change-password', [
            'current_password' => $current,
            'new_password' => $new,
            'confirm_password' => $new,
        ]);
    }

    private function passwordIs(string $password): bool
    {
        return (new DefaultPasswordHasher())->check($password, $this->userById($this->user->id)->password);
    }

    public function testMenuLinksToChangePasswordForBothRoles(): void
    {
        $this->get('/admin/adhesions');
        $this->assertResponseContains('/admin/users/change-password');

        $this->session(['Auth' => $this->userById(2)]);
        $this->get('/admin/users/change-password');
        $this->assertResponseOk();
        $this->assertResponseNotContains('name="_method" value="PUT"');
    }

    public function testChangesThePasswordAndKeepsThisSession(): void
    {
        $this->change(self::PASSWORD);

        $this->assertRedirect('/admin');
        $this->assertTrue($this->passwordIs(self::NEW_PASSWORD));
        $this->assertSame('Sua senha do painel da Sul Previdência foi alterada', $this->mailer->sent[0]['subject']);

        // A sessão desta requisição recebeu a identidade nova e segue valendo.
        $this->session(['Auth' => $this->_requestSession->read('Auth')]);
        $this->get('/admin/adhesions');
        $this->assertResponseOk();
    }

    public function testWrongCurrentPasswordIsRejected(): void
    {
        $this->change('nao e a senha atual');

        $this->assertResponseOk();
        $this->assertResponseContains('Senha atual incorreta.');
        $this->assertTrue($this->passwordIs(self::PASSWORD));
    }

    public function testPolicyAppliesHere(): void
    {
        $this->change(self::PASSWORD, 'curta');

        $this->assertResponseContains('A senha deve ter pelo menos 15 caracteres.');
        $this->assertTrue($this->passwordIs(self::PASSWORD));
    }

    public function testBlocksAfterFiveWrongCurrentPasswords(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->change('errada');
        }

        $this->change(self::PASSWORD);

        $this->assertResponseContains('Muitas tentativas com a senha atual errada.');
        $this->assertTrue($this->passwordIs(self::PASSWORD));
    }
}
