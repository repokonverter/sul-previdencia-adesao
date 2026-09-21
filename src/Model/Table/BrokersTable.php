<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Broker;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class BrokersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('brokers');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('AdhesionInitialDatas', [
            'foreignKey' => 'broker_id',
        ]);
    }

    /**
     * Normaliza o código antes da validação, pelo mesmo motivo de
     * PromotionalCodesTable::beforeMarshal.
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['code'])) {
            $data['code'] = Broker::normalizeCode((string)$data['code']);
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->requirePresence('name', 'create')
            ->notEmptyString('name', 'Informe o nome do corretor.')
            ->maxLength('name', 120);

        $validator
            ->scalar('code')
            ->requirePresence('code', 'create')
            ->notEmptyString('code', 'Informe o código do corretor.')
            ->minLength('code', 3, 'O código deve ter ao menos 3 caracteres.')
            ->maxLength('code', 30, 'O código deve ter no máximo 30 caracteres.')
            ->add('code', 'format', [
                'rule' => fn($value) => (bool)preg_match('/^[A-Z0-9-]+$/', (string)$value),
                'message' => 'Use apenas letras, números e hífen.',
            ]);

        $validator
            ->scalar('susep_code')
            ->allowEmptyString('susep_code')
            ->maxLength('susep_code', 30);

        $validator
            ->boolean('active')
            ->notEmptyString('active');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['code'], 'Já existe um corretor com esse código.'));

        return $rules;
    }

    /**
     * Busca um corretor pelo código digitado, normalizando antes de comparar.
     */
    public function findByCodeText(?string $code): ?Broker
    {
        $normalized = Broker::normalizeCode($code);

        if ($normalized === null || $normalized === '') {
            return null;
        }

        /** @var \App\Model\Entity\Broker|null */
        return $this->find()
            ->where(['Brokers.code' => $normalized])
            ->first();
    }

    /**
     * Acrescenta a contagem de adesões que usaram este corretor, mesmo
     * padrão de PartnersTable::findWithAdhesionCounts.
     */
    public function findWithAdhesionCounts(SelectQuery $query): SelectQuery
    {
        $adhesions = $this->AdhesionInitialDatas->find()
            ->select(['count' => $query->func()->count('*')])
            ->where(['AdhesionInitialDatas.broker_id' => $query->identifier('Brokers.id')]);

        return $query->selectAlso(['adhesions_count' => $adhesions]);
    }
}
