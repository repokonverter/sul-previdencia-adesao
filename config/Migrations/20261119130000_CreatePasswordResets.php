<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Esqueci a senha, convite de usuário novo e sessões derrubadas na troca de
 * senha.
 */
class CreatePasswordResets extends BaseMigration
{
    public function change(): void
    {
        // Só o hash do token: quem lê o banco (ou um backup) não tem como
        // usar o link. `purpose` separa redefinição (1 hora) de convite (48
        // horas); `used_at` marca tanto o uso quanto a invalidação por um
        // pedido mais novo.
        $this->table('password_resets')
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('token_hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('purpose', 'string', ['limit' => 10, 'null' => false])
            ->addColumn('expires_at', 'datetime', ['null' => false])
            ->addColumn('used_at', 'datetime', ['null' => true])
            ->addColumn('requested_ip', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addIndex(['token_hash'], ['unique' => true])
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();

        // Sessão aberta antes desta data é derrubada (Admin\AppController).
        // Nulo = o usuário nunca definiu a própria senha (convite pendente).
        $this->table('users')
            ->addColumn('password_changed_at', 'datetime', ['null' => true])
            ->update();

        // Quem já existe já tem senha: preenche para que o link enviado a
        // essas contas seja de redefinição, não de convite. Custa um novo
        // login a quem estiver logado no deploy.
        if ($this->isMigratingUp()) {
            $this->execute('UPDATE users SET password_changed_at = COALESCE(modified, created, NOW())');
        }
    }
}
