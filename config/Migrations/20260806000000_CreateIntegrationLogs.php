<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateIntegrationLogs extends BaseMigration
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
        $table = $this->table('integration_logs');
        $table
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('service', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('operation', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('direction', 'string', ['limit' => 20, 'null' => false, 'default' => 'outbound'])
            ->addColumn('http_method', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('url', 'text', ['null' => true])
            ->addColumn('status_code', 'integer', ['null' => true])
            ->addColumn('success', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('duration_ms', 'integer', ['null' => true])
            ->addColumn('request_body', 'text', ['null' => true])
            ->addColumn('response_body', 'text', ['null' => true])
            ->addColumn('error_message', 'text', ['null' => true])
            ->addColumn('context', 'text', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addIndex(['adhesion_initial_data_id', 'created'])
            ->addIndex(['service', 'created'])
            ->addForeignKey('adhesion_initial_data_id', 'adhesion_initial_data', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ]);
        $table->create();
    }
}
