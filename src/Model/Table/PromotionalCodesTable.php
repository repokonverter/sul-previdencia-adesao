<?php

declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use App\Model\Entity\PromotionalCode;
use Cake\Event\EventInterface;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class PromotionalCodesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('promotional_codes');
        $this->setDisplayField('code');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        // LEFT (padrão), não INNER: quando essa associação é encadeada a
        // partir de AdhesionInitialDatas.PromotionalCodes.Partners, um INNER
        // aqui eliminaria da listagem qualquer adesão sem código promocional.
        $this->belongsTo('Partners', [
            'foreignKey' => 'partner_id',
        ]);

        $this->hasMany('AdhesionInitialDatas', [
            'foreignKey' => 'promotional_code_id',
        ]);
    }

    /**
     * Normaliza o código antes da validação.
     *
     * Sem isso, a validação de formato veria o texto cru digitado pelo admin
     * ('sindilojas') e o rejeitaria, ainda que o setter da entidade fosse
     * convertê-lo para a forma canônica logo em seguida.
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['code'])) {
            $data['code'] = PromotionalCode::normalizeCode((string)$data['code']);
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('partner_id')
            ->requirePresence('partner_id', 'create')
            ->notEmptyString('partner_id', 'Selecione o parceiro.');

        $validator
            ->scalar('code')
            ->requirePresence('code', 'create')
            ->notEmptyString('code', 'Informe o código.')
            ->minLength('code', 3, 'O código deve ter ao menos 3 caracteres.')
            ->maxLength('code', 30, 'O código deve ter no máximo 30 caracteres.')
            ->add('code', 'format', [
                'rule' => fn($value) => (bool)preg_match('/^[A-Z0-9-]+$/', (string)$value),
                'message' => 'Use apenas letras, números e hífen.',
            ]);

        $validator
            ->scalar('description')
            ->allowEmptyString('description')
            ->maxLength('description', 255);

        $validator
            ->date('valid_from')
            ->allowEmptyDate('valid_from');

        $validator
            ->date('valid_until')
            ->allowEmptyDate('valid_until')
            ->add('valid_until', 'afterValidFrom', [
                'rule' => function ($value, $context) {
                    $from = $context['data']['valid_from'] ?? null;

                    if (empty($from) || empty($value)) {
                        return true;
                    }

                    return new Date($value) >= new Date($from);
                },
                'message' => 'A data final não pode ser anterior à data inicial.',
            ]);

        $validator
            ->boolean('active')
            ->notEmptyString('active');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['code'], 'Já existe um código promocional com esse valor.'));
        $rules->add($rules->existsIn('partner_id', 'Partners', 'Selecione um parceiro válido.'));

        return $rules;
    }

    /**
     * Busca um código pelo texto digitado, normalizando antes de comparar e
     * já trazendo o parceiro (necessário para isUsable/unusableReason).
     */
    public function findByCodeText(?string $code): ?PromotionalCode
    {
        $normalized = PromotionalCode::normalizeCode($code);

        if ($normalized === null || $normalized === '') {
            return null;
        }

        /** @var \App\Model\Entity\PromotionalCode|null */
        return $this->find()
            ->contain(['Partners'])
            ->where(['PromotionalCodes.code' => $normalized])
            ->first();
    }

    /**
     * Acrescenta as contagens de adesões iniciadas e concluídas.
     *
     * "Concluída" segue o mesmo critério usado na listagem de adesões: a
     * existência de adhesion_other_informations marca o fim do preenchimento.
     */
    public function findWithAdhesionCounts(SelectQuery $query): SelectQuery
    {
        $started = $this->AdhesionInitialDatas->find()
            ->select(['count' => $query->func()->count('*')])
            ->where([
                'AdhesionInitialDatas.promotional_code_id' => $query->identifier('PromotionalCodes.id'),
            ]);

        $completed = $this->AdhesionInitialDatas->find()
            ->select(['count' => $query->func()->count('*')])
            ->innerJoinWith('AdhesionOtherInformations')
            ->where([
                'AdhesionInitialDatas.promotional_code_id' => $query->identifier('PromotionalCodes.id'),
            ]);

        return $query->selectAlso([
            'adhesions_started' => $started,
            'adhesions_completed' => $completed,
        ]);
    }
}
