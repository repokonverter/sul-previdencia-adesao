<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class AdhesionAuditsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('adhesion_audits');
        $this->setPrimaryKey('id');

        // Só created: uma linha de auditoria não é editada, então não existe
        // "quando foi alterada".
        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                ],
            ],
        ]);

        $this->belongsTo('AdhesionInitialDatas', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);
    }
}
