<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\Validation\Validator;

class PixWebhooksTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('pix_webhooks');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('chave')
            ->requirePresence('chave', 'create')
            ->notEmptyString('chave');

        $validator
            ->scalar('token')
            ->maxLength('token', 64)
            ->requirePresence('token', 'create')
            ->notEmptyString('token');

        $validator
            ->scalar('url')
            ->requirePresence('url', 'create')
            ->notEmptyString('url');

        return $validator;
    }
}
