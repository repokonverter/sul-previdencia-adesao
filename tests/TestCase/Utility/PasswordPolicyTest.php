<?php

declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\PasswordPolicy;
use Cake\TestSuite\TestCase;

class PasswordPolicyTest extends TestCase
{
    public function testLengthIsWhatCountsNotComposition(): void
    {
        $this->assertSame([], PasswordPolicy::violations('cavalo bateria grampo'));
        $this->assertSame([], PasswordPolicy::violations('aaaaaaaaaaaaaaa'), 'Sem regra de composição (NIST).');

        $this->assertSame(['A senha deve ter pelo menos 15 caracteres.'], PasswordPolicy::violations('Curta@123'));
        $this->assertSame(['A senha deve ter no máximo 64 caracteres.'], PasswordPolicy::violations(str_repeat('a', 65)));
    }

    public function testLengthCountsCharactersNotBytes(): void
    {
        // 15 caracteres, 21 bytes.
        $this->assertSame([], PasswordPolicy::violations('ação ação ação '));
    }

    public function testRejectsTheServiceNameInAnySpelling(): void
    {
        foreach (['minha senha SulPrev 2026', 'minha senha sul-prev x', 'PREVIDÊNCIA privada 1'] as $password) {
            $this->assertSame(
                ['A senha não pode conter o nome da Sul Previdência.'],
                PasswordPolicy::violations($password),
                $password
            );
        }
    }

    public function testRejectsTheUsersOwnNameOrEmail(): void
    {
        $name = 'João da Silva';
        $email = 'joao.silva@exemplo.test';
        $expected = ['A senha não pode conter seu nome ou e-mail.'];

        $this->assertSame($expected, PasswordPolicy::violations('joao gosta de cafe', $name, $email), 'Nome sem acento');
        $this->assertSame($expected, PasswordPolicy::violations('SILVA e um bom nome', $name, $email), 'Sobrenome');
        $this->assertSame([], PasswordPolicy::violations('da mata ate o rio x', $name, $email), 'Partes curtas como "da" não contam.');
    }

    public function testPersonalWordsAreNormalizedNameAndEmailParts(): void
    {
        $this->assertSame(
            ['joao', 'silva', 'jsilva'],
            PasswordPolicy::personalWords('João da Silva', 'jsilva@exemplo.test')
        );
    }
}
