<?php

declare(strict_types=1);

use Migrations\AbstractSeed;
use Authentication\PasswordHasher\DefaultPasswordHasher;

class UsersSeed extends AbstractSeed
{
    /**
     * Não trunca mais: `adhesion_plans.admin_overridden_by_user_id`,
     * `adhesion_audits.user_id` e `adhesion_deletions.user_id` passaram a
     * referenciar `users` em entregas posteriores a este seed, e o Postgres
     * recusa truncar uma tabela referenciada por FK
     * ("cannot truncate a table referenced in a foreign key constraint").
     * Em vez de recriar a linha, atualiza se o e-mail já existe -- assim o
     * seed continua idempotente sem depender de truncar nada.
     */
    public function run(): void
    {
        $hasher = new DefaultPasswordHasher();
        $now = date('Y-m-d H:i:s');
        $email = 'admin@sistema.com';
        $password = $hasher->hash('123456');

        $existing = $this->query('SELECT id FROM users WHERE email = ?', [$email])->fetch('assoc');

        if ($existing) {
            $this->execute(
                'UPDATE users SET name = ?, password = ?, password_changed_at = ?, modified = ? WHERE id = ?',
                ['admin', $password, $now, $now, $existing['id']]
            );

            return;
        }

        $this->table('users')->insert([
            [
                'name' => 'admin',
                'email' => $email,
                'password' => $password,
                // Senha definida (não é convite pendente); na atualização,
                // derruba as sessões abertas com a senha anterior.
                'password_changed_at' => $now,
                'created' => $now,
                'modified' => $now,
            ],
        ])->save();
    }
}
