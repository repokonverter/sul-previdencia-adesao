<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\AdhesionInitialData;
use App\Services\AdhesionAuditor;
use App\Services\BenefitEntryAge;
use App\Services\BenefitPlans;
use Cake\Chronos\ChronosDate;
use Cake\Datasource\EntityInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Association;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use DateTimeInterface;

class AdhesionInitialDatasTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('adhesion_initial_data');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        // propertyName explícito: o nome convencional ('promotional_code') colidiria
        // com a coluna de snapshot do texto do código.
        $this->belongsTo('PromotionalCodes', [
            'foreignKey' => 'promotional_code_id',
            'propertyName' => 'promotional_code_entity',
        ]);

        // Independente de PromotionalCodes: sobrevive a qualquer mudança no
        // cadastro do código que originou o vínculo (ver migration
        // AddAssociationsToPartnersAndAdhesions).
        $this->belongsTo('AssociationPartners', [
            'className' => 'Partners',
            'foreignKey' => 'association_partner_id',
            'propertyName' => 'association_partner',
        ]);

        $this->belongsTo('Brokers', [
            'foreignKey' => 'broker_id',
        ]);

        $this->hasOne('AdhesionPersonalDatas', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
        $this->hasOne('AdhesionPlans', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
        $this->hasOne('AdhesionAddresses', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
        $this->hasOne('AdhesionOtherInformations', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
        $this->hasOne('AdhesionDocuments', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
        $this->hasOne('AdhesionProponentStatements', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
        $this->hasMany('AdhesionPensionSchemes', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
        $this->hasMany('ClicksignDatas', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
        $this->hasOne('AdhesionPaymentDetails', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);

        $this->hasMany('AdhesionDependents', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
        $this->hasMany('PixTransactions', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
        $this->hasMany('IntegrationLogs', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
        $this->hasMany('AdhesionAudits', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('storage_uuid')
            ->maxLength('storage_uuid', 50)
            ->requirePresence('storage_uuid', 'create')
            ->notEmptyString('storage_uuid');

        $validator
            ->scalar('name')
            ->maxLength('name', 120)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        return $validator;
    }

    /**
     * Opção de save() com que o admin pede a faixa de BenefitEntryAge. O
     * fluxo público não a passa: lá a idade de entrada vem da regra de
     * PlanSimulator::benefitEntryAge(), que passa dos 65 para quem tem mais
     * de 55 anos.
     */
    public const CHECK_BENEFIT_ENTRY_AGE = 'checkBenefitEntryAge';

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('broker_id', 'Brokers', 'Selecione um corretor válido.'));

        $rules->addCreate(
            fn(EntityInterface $adhesion): bool => BenefitPlans::exists($adhesion->get('benefit_plan')),
            'knownBenefitPlan',
            ['errorField' => 'benefit_plan', 'message' => 'Selecione um plano válido.']
        );

        // Pix, envelope e parceiro já ficam ligados ao plano; adesão no plano
        // errado se exclui e refaz.
        $rules->addUpdate(
            fn(EntityInterface $adhesion): bool => !$adhesion->isDirty('benefit_plan'),
            'benefitPlanUnchanged',
            ['errorField' => 'benefit_plan', 'message' => 'O plano de uma adesão não muda depois de criada.']
        );

        $rules->add(
            fn(EntityInterface $adhesion): bool => $this->attributionMatchesBenefitPlan($adhesion),
            'attributionMatchesBenefitPlan'
        );

        // Regra na raiz, e não no AdhesionPlansTable: o piso depende da data
        // de nascimento, que mora em outra tabela.
        $rules->add(
            fn(EntityInterface $adhesion, array $options): bool => empty($options[self::CHECK_BENEFIT_ENTRY_AGE])
                || $this->benefitEntryAgeWithinRange($adhesion),
            'benefitEntryAgeWithinRange'
        );

        return $rules;
    }

    /**
     * Código promocional, corretor e vínculo associativo pertencem a um
     * parceiro, e o parceiro a um plano de benefícios: atribuir a uma adesão
     * de outro plano mandaria o link e a proposta de um para o outro. O
     * formulário público já só oferece os do plano; a regra cobre o admin e
     * o POST forjado.
     */
    private function attributionMatchesBenefitPlan(EntityInterface $adhesion): bool
    {
        $partnerPlans = [
            'promotional_code_id' => [$this->PromotionalCodes, 'Partners', 'O código promocional é de um parceiro de outro plano.'],
            'broker_id' => [$this->Brokers, 'Partners', 'O corretor é de um parceiro de outro plano.'],
            'association_partner_id' => [$this->AssociationPartners, null, 'O vínculo associativo é de outro plano.'],
        ];
        $matches = true;

        foreach ($partnerPlans as $field => [$association, $partners, $message]) {
            $id = $adhesion->get($field);

            if ($id === null || !$adhesion->isDirty($field)) {
                continue;
            }

            $planField = ($partners ?? $association->getAlias()) . '.benefit_plan';
            $query = $association->find()
                ->select(['benefit_plan' => $planField])
                ->where([$association->aliasField('id') => $id]);

            if ($partners !== null) {
                $query->innerJoinWith($partners);
            }

            $row = $query->disableHydration()->first();

            if ($row !== null && $row['benefit_plan'] !== $adhesion->get('benefit_plan')) {
                $adhesion->setError($field, ['benefitPlan' => $message]);
                $matches = false;
            }
        }

        return $matches;
    }

    /**
     * O erro vai para o campo do plano, onde o FormHelper o mostra. Uma
     * adesão já gravada fora da faixa também é recusada, até o admin ajustar
     * o valor: corrigir sozinho mudaria o plano sem ninguém ver.
     */
    private function benefitEntryAgeWithinRange(EntityInterface $adhesion): bool
    {
        $plan = $adhesion->get('adhesion_plan');

        if ($plan === null) {
            return true;
        }

        $birthDate = $adhesion->get('adhesion_personal_data')?->get('birth_date');
        $error = BenefitEntryAge::error(
            $plan->get('benefit_entry_age'),
            $birthDate instanceof ChronosDate || $birthDate instanceof DateTimeInterface ? $birthDate->format('Y-m-d') : null
        );

        if ($error === null) {
            return true;
        }

        $plan->setError('benefit_entry_age', ['withinRange' => $error]);

        return false;
    }

    /**
     * As contagens do card de resumo da listagem de adesões e do dashboard,
     * sobre todas as adesões da consulta (não só a página atual). As regras
     * são as mesmas das colunas da listagem e dos cards de parceiro: concluída tem "Outras informações"; assinada e
     * paga olham qualquer tentativa, porque as duas coisas são definitivas;
     * aguardando pagamento chegou à etapa de pagamento e não tem Pix pago.
     *
     * @return array{total: int, completed: int, signed: int, paid: int, awaitingPayment: int}
     */
    public function summarize(SelectQuery $query): array
    {
        $exists = fn (Association $related, array $conditions = []) => $query->newExpr()->exists(
            $related->find()
                ->select(['id'])
                ->where([$related->aliasField('adhesion_initial_data_id') => $query->identifier('AdhesionInitialDatas.id')] + $conditions)
        );
        // COUNT(CASE WHEN ... THEN 1 END): o 1 vai literal, não como
        // parâmetro, senão o Postgres o recebe como texto.
        $countWhen = fn ($condition) => $query->func()->count(
            $query->newExpr()->case()->when($condition)->then($query->newExpr('1'))
        );

        $paid = $exists($this->PixTransactions, ['PixTransactions.paid' => true]);

        $row = $query
            ->select([
                'total' => $query->func()->count('*'),
                'completed' => $countWhen($exists($this->AdhesionOtherInformations)),
                'signed' => $countWhen($exists($this->ClicksignDatas, ['ClicksignDatas.status' => 'signed'])),
                'paid' => $countWhen($paid),
                'awaitingPayment' => $countWhen($query->newExpr()->and([
                    $exists($this->AdhesionPaymentDetails),
                    $query->newExpr()->not($paid),
                ])),
            ])
            ->disableHydration()
            ->first();

        return array_map('intval', $row);
    }

    /**
     * Emite um link de retomada novo, invalidando o anterior.
     *
     * A rotação é na geração, e não no envio: o admin gera uma vez e pode
     * distribuir o mesmo link por e-mail e por WhatsApp sem que o primeiro
     * canal mate o segundo.
     *
     * 32 bytes de random_bytes, e não um hash do id: derivar do id torna a
     * base enumerável se o algoritmo vazar, e ids sequenciais tornam trivial
     * gerar candidatos.
     */
    public function issueResumeToken(AdhesionInitialData $adhesion, int $days, ?string $step = null): string
    {
        $adhesion = $this->patchEntity($adhesion, [
            'resume_token' => bin2hex(random_bytes(32)),
            'resume_token_expires_at' => DateTime::now()->addDays($days),
            'resume_step' => $step,
        ]);

        $this->saveOrFail($adhesion);

        return $adhesion->resume_token;
    }

    public function revokeResumeToken(AdhesionInitialData $adhesion): void
    {
        $this->saveOrFail($this->patchEntity($adhesion, [
            'resume_token' => null,
            'resume_token_expires_at' => null,
        ]));
    }

    /**
     * A adesão de um link de retomada, com tudo que o formulário precisa para
     * voltar preenchido. Devolve null para token inexistente; a expiração é
     * pergunta da entidade, porque a página diz coisas diferentes nos dois
     * casos.
     */
    public function findByResumeToken(?string $token): ?AdhesionInitialData
    {
        if ($token === null || $token === '') {
            return null;
        }

        /** @var \App\Model\Entity\AdhesionInitialData|null */
        return $this->find()
            ->where(['AdhesionInitialDatas.resume_token' => $token])
            ->contain(AdhesionAuditor::CONTAINS)
            ->first();
    }
}
