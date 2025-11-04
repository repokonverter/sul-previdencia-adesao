<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class AdhesionPensionSchemesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('adhesion_pension_schemes');
        $this->addBehavior('Timestamp');
        $this->belongsTo('AdhesionInitialDatas', [
            'foreignKey' => 'adhesion_initial_data_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
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
        $rules->add($rules->existsIn(['adhesion_initial_data_id'], 'AdhesionInitialDatas'), ['errorField' => 'adhesion_initial_data_id']);

        return $rules;
    }
}
