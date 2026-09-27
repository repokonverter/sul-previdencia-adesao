<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\Validation\Validator;

class ClicksignWebhooksTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('clicksign_webhooks');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('clicksign_webhook_id')
            ->requirePresence('clicksign_webhook_id', 'create')
            ->notEmptyString('clicksign_webhook_id');

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
