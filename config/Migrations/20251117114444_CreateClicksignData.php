<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateClicksignData extends BaseMigration
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
        $table = $this->table('clicksign_data')
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            ->addColumn('envelope_id', 'string', ['limit' => 50])
            ->addForeignKey('adhesion_initial_data_id', 'adhesion_initial_data', 'id', ['delete' => 'CASCADE'])
            ->addTimestamps();
        $table->create();
    }
}
