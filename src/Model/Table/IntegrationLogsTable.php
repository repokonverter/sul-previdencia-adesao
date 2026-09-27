<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\Validation\Validator;

class IntegrationLogsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('integration_logs');
        $this->setPrimaryKey('id');

        $this->belongsTo('AdhesionInitialDatas', [
            'foreignKey' => 'adhesion_initial_data_id',
            'joinType' => 'LEFT',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
    }
}
