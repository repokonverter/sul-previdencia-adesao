<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Dependents Model
 *
 * @property \App\Model\Table\SubscriptionsTable&\Cake\ORM\Association\BelongsTo $Subscriptions
 *
 * @method \App\Model\Entity\Dependent newEmptyEntity()
 * @method \App\Model\Entity\Dependent newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Dependent> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Dependent get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Dependent findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Dependent patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Dependent> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Dependent|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Dependent saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Dependent>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Dependent>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Dependent>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Dependent> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Dependent>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Dependent>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Dependent>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Dependent> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class DependentsTable extends Table
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

        $this->setTable('dependents');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Subscriptions', [
            'foreignKey' => 'subscription_id',
            'joinType' => 'INNER',
        ]);
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
        $rules->add($rules->existsIn(['subscription_id'], 'Subscriptions'), ['errorField' => 'subscription_id']);

        return $rules;
    }
}
