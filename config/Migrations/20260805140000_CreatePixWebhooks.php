<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePixWebhooks extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('pix_webhooks')
            ->addColumn('chave', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('token', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('url', 'text', ['null' => false])
            ->addTimestamps();
        $table->create();
    }
}
