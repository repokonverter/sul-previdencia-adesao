<?php

declare(strict_types=1);

namespace App\Model\Table;

class AdhesionDeletionsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('adhesion_deletions');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);
    }
}
