<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Um link de redefinição de senha ou de convite (ver PasswordResetsTable).
 *
 * @property int $id
 * @property int $user_id
 * @property string $token_hash
 * @property string $purpose
 * @property \Cake\I18n\DateTime $expires_at
 * @property \Cake\I18n\DateTime|null $used_at
 * @property string|null $requested_ip
 * @property \Cake\I18n\DateTime|null $created
 * @property \App\Model\Entity\User $user
 */
class PasswordReset extends Entity
{
    public const PURPOSE_RESET = 'reset';
    public const PURPOSE_INVITE = 'invite';

    /**
     * Nada vem de formulário: tudo é montado por PasswordResetsTable::issue().
     */
    protected array $_accessible = [];

    public function isInvite(): bool
    {
        return $this->purpose === self::PURPOSE_INVITE;
    }
}
