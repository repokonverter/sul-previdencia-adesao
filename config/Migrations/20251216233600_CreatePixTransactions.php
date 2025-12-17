<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePixTransactions extends BaseMigration
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
        $table = $this->table('pix_transactions')
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            ->addColumn('txid', 'string', ['limit' => 255])
            ->addColumn('paid', 'boolean', ['default' => false])
            ->addColumn('payment_date', 'datetime', ['null' => true])
            ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('expired', 'boolean', ['default' => false])
            ->addForeignKey('adhesion_initial_data_id', 'adhesion_initial_data', 'id', ['delete' => 'CASCADE'])
            ->addTimestamps();
        $table->create();
    }
}
