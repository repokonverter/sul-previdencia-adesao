<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role
 * @property \Cake\I18n\DateTime|null $password_changed_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class User extends Entity
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    /**
     * @var array<string, string>
     */
    public const ROLES = [
        self::ROLE_ADMIN => 'Administrador',
        self::ROLE_USER => 'Usuário',
    ];

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? (string)$this->role;
    }

    /**
     * Convidado que ainda não usou o link: a senha gravada é aleatória e
     * ninguém a conhece.
     */
    public function hasDefinedPassword(): bool
    {
        return $this->password_changed_at !== null;
    }

    protected function _setPassword(string $password): string
    {
        return (new DefaultPasswordHasher())->hash($password);
    }

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'email' => true,
        // Sem `password`: ninguém define senha por formulário de cadastro.
        // Ela só muda por UsersTable::changePassword() (link por e-mail ou
        // "Alterar minha senha"), com a política de PasswordPolicy.
        'role' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected array $_hidden = [
        'password',
    ];
}
