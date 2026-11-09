<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Perfil do usuário do admin: `admin` pode tudo; `user` usa a Simulação e só
 * visualiza o resto (ver Admin\AppController::$userActions).
 *
 * Quem já existia vira admin para ninguém perder acesso no deploy; cadastros
 * novos nascem `user` e são promovidos explicitamente.
 */
class AddRoleToUsers extends BaseMigration
{
    public function up(): void
    {
        $this->table('users')
            ->addColumn('role', 'string', ['limit' => 20, 'null' => false, 'default' => 'user'])
            ->update();

        $this->execute("UPDATE users SET role = 'admin'");
    }

    public function down(): void
    {
        $this->table('users')
            ->removeColumn('role')
            ->update();
    }
}
