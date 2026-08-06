<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddLazyCreationColumnsToPixTransactions extends BaseMigration
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
        $table = $this->table('pix_transactions');
        $table
            ->addColumn('attempt', 'integer', ['default' => 1, 'null' => false])
            ->addColumn('brcode', 'text', ['null' => true])
            ->update();
    }
}
