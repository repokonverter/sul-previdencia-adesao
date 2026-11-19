<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\User;
use App\Utility\PasswordPolicy;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * @method \App\Model\Entity\User newEmptyEntity()
 * @method \App\Model\Entity\User newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\User> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\User findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\User> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\User>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @property \App\Model\Table\PasswordResetsTable&\Cake\ORM\Association\HasMany $PasswordResets
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UsersTable extends AppTable
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('email');
        $this->setPrimaryKey('id');

        $this->hasMany('PasswordResets', [
            'foreignKey' => 'user_id',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->inList('role', array_keys(User::ROLES), 'Perfil inválido.')
            ->notEmptyString('role');

        return $validator;
    }

    /**
     * Usuário novo, sem senha conhecida: recebe uma aleatória e um convite
     * por e-mail para definir a própria (ver UsersController::add()).
     */
    public function newInvitedUser(array $data): User
    {
        $user = $this->newEntity($data);
        $user->password = bin2hex(random_bytes(32));
        $user->password_changed_at = null;

        return $user;
    }

    /**
     * O único caminho que troca uma senha: aplica PasswordPolicy, carimba
     * password_changed_at (o que derruba as outras sessões, ver
     * Admin\AppController) e invalida os links pendentes. Os erros ficam na
     * entidade, nos campos `new_password` e `confirm_password` do formulário.
     */
    public function changePassword(User $user, string $newPassword, string $confirmation): bool
    {
        $errors = [];
        $violations = PasswordPolicy::violations($newPassword, $user->name, $user->email);

        if ($violations !== []) {
            $errors['new_password'] = $violations;
        }

        if ($newPassword !== $confirmation) {
            $errors['confirm_password'] = [__('A confirmação não confere com a nova senha.')];
        }

        if ($errors !== []) {
            $user->setErrors($errors);

            return false;
        }

        $user->password = $newPassword;
        // Sem os microssegundos, que o banco não guarda: a sessão de quem
        // trocou recebe esta mesma entidade e precisa bater com o banco.
        $user->password_changed_at = DateTime::now()->microsecond(0);

        return (bool)$this->getConnection()->transactional(function () use ($user): bool {
            if (!$this->save($user)) {
                return false;
            }

            $this->PasswordResets->invalidateFor($user->id);

            return true;
        });
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['email']), ['errorField' => 'email']);

        return $rules;
    }
}
