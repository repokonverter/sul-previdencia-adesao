<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePartnersAndPromotionalCodes extends BaseMigration
{
    public function change(): void
    {
        $this->table('partners')
            ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('color', 'string', ['limit' => 7, 'null' => false])
            ->addColumn('logo_data', 'binary', ['null' => true])
            ->addColumn('logo_mime_type', 'string', ['limit' => 60, 'null' => true])
            ->addColumn('logo_filename', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('active', 'boolean', ['null' => false, 'default' => true])
            ->addTimestamps()
            ->create();

        $this->table('promotional_codes')
            ->addColumn('partner_id', 'integer', ['null' => false])
            ->addColumn('code', 'string', ['limit' => 30, 'null' => false])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('valid_from', 'date', ['null' => true])
            ->addColumn('valid_until', 'date', ['null' => true])
            ->addColumn('active', 'boolean', ['null' => false, 'default' => true])
            ->addTimestamps()
            ->addIndex(['code'], ['unique' => true])
            ->addIndex(['partner_id'])
            ->addForeignKey('partner_id', 'partners', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('adhesion_initial_data')
            ->addColumn('promotional_code_id', 'integer', ['null' => true])
            ->addColumn('promotional_code', 'string', ['limit' => 30, 'null' => true])
            ->addForeignKey('promotional_code_id', 'promotional_codes', 'id', ['delete' => 'SET_NULL'])
            ->update();
    }
}
