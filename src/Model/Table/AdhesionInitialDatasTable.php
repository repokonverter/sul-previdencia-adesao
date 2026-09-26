<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class AdhesionInitialDatasTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('adhesion_initial_data');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

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
}
