<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Subscriptions Model
 *
 * @property \App\Model\Table\AddressesTable&\Cake\ORM\Association\HasMany $Addresses
 * @property \App\Model\Table\DependentsTable&\Cake\ORM\Association\HasMany $Dependents
 * @property \App\Model\Table\DocumentsTable&\Cake\ORM\Association\HasMany $Documents
 * @property \App\Model\Table\PeopleTable&\Cake\ORM\Association\HasMany $People
 *
 * @method \App\Model\Entity\Subscription newEmptyEntity()
 * @method \App\Model\Entity\Subscription newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Subscription> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Subscription get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Subscription findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Subscription patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Subscription> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Subscription|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Subscription saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Subscription>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Subscription>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Subscription>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Subscription> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Subscription>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Subscription>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Subscription>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Subscription> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SubscriptionsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('subscriptions');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->hasOne('People', [
            'foreignKey' => 'subscription_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);

        $this->hasOne('Addresses', [
            'foreignKey' => 'subscription_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);

        $this->hasMany('Dependents', [
            'foreignKey' => 'subscription_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);

        $this->hasMany('Documents', [
            'foreignKey' => 'subscription_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->numeric('plan_value')
            ->allowEmptyString('plan_value')
            ->maxLength('plan_type', 50)
            ->maxLength('periodicity', 20)
            ->maxLength('payment_method', 30);

        return $validator;
    }
}