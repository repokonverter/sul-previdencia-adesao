<?php

declare(strict_types=1);

namespace App\Model\Table;

class AdhesionAuditsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('adhesion_audits');
        $this->setPrimaryKey('id');

        $this->belongsTo('AdhesionInitialDatas', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);
    }
}
