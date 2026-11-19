<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Broker;
use App\Utility\Cpf;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;

class BrokersTable extends AppTable
{
    /**
     * Sem 0/o, 1/l/i: o link do corretor é ditado por telefone e copiado à
     * mão de print de WhatsApp.
     */
    private const TOKEN_ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    private const TOKEN_LENGTH = 12;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('brokers');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->belongsTo('Partners', [
            'foreignKey' => 'partner_id',
        ]);

        $this->hasMany('AdhesionInitialDatas', [
            'foreignKey' => 'broker_id',
        ]);
    }

    /**
     * O CPF chega mascarado do formulário; a validação e o índice único
     * trabalham sobre os dígitos.
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (array_key_exists('cpf', (array)$data)) {
            $data['cpf'] = Cpf::normalize($data['cpf']);
        }

        if (isset($data['name'])) {
            $data['name'] = trim((string)$data['name']);
        }
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($entity->isNew() && empty($entity->get('token'))) {
            $entity->set('token', self::newToken());
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('partner_id')
            ->requirePresence('partner_id', 'create')
            ->notEmptyString('partner_id', 'Selecione o parceiro.');

        $validator
            ->scalar('name')
            ->requirePresence('name', 'create')
            ->notEmptyString('name', 'Informe o nome completo do corretor.')
            ->maxLength('name', 150, 'O nome deve ter no máximo 150 caracteres.');

        $validator
            ->scalar('cpf')
            ->allowEmptyString('cpf')
            ->add('cpf', 'valid', [
                'rule' => fn($value) => Cpf::isValid($value),
                'message' => 'Informe um CPF válido.',
            ]);

        $validator
            ->boolean('active')
            ->notEmptyString('active');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('partner_id', 'Partners', 'Selecione um parceiro válido.'));
        $rules->add($rules->isUnique(
            ['partner_id', 'cpf'],
            'Já existe um corretor com este CPF neste parceiro.'
        ));
        $rules->add(
            function (EntityInterface $broker): bool {
                $partner = $this->Partners->find()
                    ->select(['is_association'])
                    ->where(['Partners.id' => $broker->get('partner_id')])
                    ->first();

                return $partner === null || !$partner->is_association;
            },
            'partnerIsNotAssociation',
            ['errorField' => 'partner_id', 'message' => 'Vínculo associativo não tem corretores.']
        );

        return $rules;
    }

    /**
     * Os corretores que o formulário público oferece: ativos, de parceiro
     * comum ativo, na ordem em que o select os mostra.
     */
    public function findSelectable(SelectQuery $query): SelectQuery
    {
        return $query
            ->contain(['Partners'])
            ->where([
                'Brokers.active' => true,
                'Partners.active' => true,
                'Partners.is_association' => false,
            ])
            ->orderBy(['Partners.name' => 'ASC', 'Brokers.name' => 'ASC']);
    }

    /**
     * O corretor de um link de divulgação, se ainda puder ser escolhido.
     */
    public function findSelectableByToken(?string $token): ?Broker
    {
        $token = trim((string)$token);

        if ($token === '') {
            return null;
        }

        /** @var \App\Model\Entity\Broker|null */
        return $this->find('selectable')
            ->where(['Brokers.token' => $token])
            ->first();
    }

    /**
     * Mesmo critério de PromotionalCodesTable::findWithAdhesionCounts().
     */
    public function findWithAdhesionCounts(SelectQuery $query): SelectQuery
    {
        $started = $this->AdhesionInitialDatas->find()
            ->select(['count' => $query->func()->count('*')])
            ->where([
                'AdhesionInitialDatas.broker_id' => $query->identifier('Brokers.id'),
            ]);

        $completed = $this->AdhesionInitialDatas->find()
            ->select(['count' => $query->func()->count('*')])
            ->innerJoinWith('AdhesionOtherInformations')
            ->where([
                'AdhesionInitialDatas.broker_id' => $query->identifier('Brokers.id'),
            ]);

        return $query->selectAlso([
            'adhesions_started' => $started,
            'adhesions_completed' => $completed,
        ]);
    }

    private static function newToken(): string
    {
        $token = '';
        $max = strlen(self::TOKEN_ALPHABET) - 1;

        for ($i = 0; $i < self::TOKEN_LENGTH; $i++) {
            $token .= self::TOKEN_ALPHABET[random_int(0, $max)];
        }

        return $token;
    }
}
