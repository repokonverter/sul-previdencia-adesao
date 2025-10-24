<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class AdhesionPlansTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('adhesion_plans');
        $this->addBehavior('Timestamp');
        $this->belongsTo('AdhesionInitialDatas', [
            'foreignKey' => 'adhesion_initial_data_id',
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
        $rules->add($rules->existsIn(['adhesion_initial_data_id'], 'AdhesionInitialData'), ['errorField' => 'adhesion_initial_data_id']);

        return $rules;
    }
}
