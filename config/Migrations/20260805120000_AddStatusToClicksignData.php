<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddStatusToClicksignData extends BaseMigration
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
        $table = $this->table('clicksign_data');
        $table
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending', 'null' => false])
            ->addColumn('attempts', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('last_error', 'text', ['null' => true])
            ->update();
    }
}
