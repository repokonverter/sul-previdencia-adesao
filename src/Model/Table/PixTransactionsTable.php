<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\Validation\Validator;

class PixTransactionsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('pix_transactions');
        $this->setDisplayField('txid');
        $this->setPrimaryKey('id');

        $this->belongsTo('AdhesionInitialDatas', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('txid')
            ->maxLength('txid', 255)
            ->allowEmptyString('txid');

        return $validator;
    }
}
