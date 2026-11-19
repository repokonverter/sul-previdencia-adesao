<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\PasswordReset;
use App\Model\Entity\User;
use Cake\I18n\DateTime;

/**
 * Links de redefinição de senha e de convite. O token só existe em claro no
 * e-mail; aqui fica o SHA-256 dele. Cada link vale uma vez, e só o mais
 * recente de cada usuário vale: emitir um novo, ou trocar a senha por
 * qualquer caminho, invalida os pendentes.
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 */
class PasswordResetsTable extends AppTable
{
    /**
     * Em minutos.
     */
    public const LIFETIMES = [
        PasswordReset::PURPOSE_RESET => 60,
        PasswordReset::PURPOSE_INVITE => 48 * 60,
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('password_resets');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * @return string O token em claro, para o link do e-mail.
     */
    public function issue(User $user, string $purpose, ?string $ip = null): string
    {
        $token = bin2hex(random_bytes(32));

        $this->getConnection()->transactional(function () use ($user, $purpose, $ip, $token): void {
            $this->invalidateFor($user->id);

            $reset = $this->newEmptyEntity();
            $reset->user_id = $user->id;
            $reset->token_hash = self::hash($token);
            $reset->purpose = $purpose;
            $reset->expires_at = DateTime::now()->addMinutes(self::LIFETIMES[$purpose]);
            $reset->requested_ip = $ip;

            $this->saveOrFail($reset);
        });

        return $token;
    }

    public function findUsable(string $token): ?PasswordReset
    {
        if ($token === '') {
            return null;
        }

        return $this->find()
            ->contain(['Users'])
            ->where([
                'PasswordResets.token_hash' => self::hash($token),
                'PasswordResets.used_at IS' => null,
                'PasswordResets.expires_at >' => DateTime::now(),
            ])
            ->first();
    }

    public function invalidateFor(int $userId): int
    {
        return $this->updateAll(
            ['used_at' => DateTime::now()],
            ['user_id' => $userId, 'used_at IS' => null]
        );
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
