<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\AdhesionInitialData;
use App\Services\AdhesionAuditor;
use Cake\I18n\DateTime;
use Cake\Validation\Validator;

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
        $this->hasOne('ClicksignDatas', [
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
