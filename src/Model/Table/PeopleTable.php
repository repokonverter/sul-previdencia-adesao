<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * People Model
 *
 * @property \App\Model\Table\SubscriptionsTable&\Cake\ORM\Association\BelongsTo $Subscriptions
 *
 * @method \App\Model\Entity\Person newEmptyEntity()
 * @method \App\Model\Entity\Person newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Person> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Person get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Person findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Person patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Person> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Person|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Person saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Person>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Person>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Person>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Person> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Person>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Person>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Person>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Person> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PeopleTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('people');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Subscriptions', [
            'foreignKey' => 'subscription_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('name', 'create')
            ->notEmptyString('name', 'Informe o nome')
            ->maxLength('name', 120)
            ->maxLength('cpf', 14)
            ->email('email')
            ->allowEmptyString('email')
            ->boolean('is_legal_representative');

        return $validator;
    }
}