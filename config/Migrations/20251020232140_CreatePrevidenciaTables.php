<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePrevidenciaTables extends BaseMigration
{
    public function change(): void
    {
        $this->table('adhesion_initial_data')
            ->addColumn('storage_uuid', 'string', ['limit' => 50])
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('email', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
            ->addTimestamps()
            ->create();

        // people
        $this->table('adhesion_personal_data')
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            ->addColumn('plan_for', 'string', ['limit' => 50])
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('cpf', 'string', ['limit' => 14])
            ->addColumn('birth_date', 'date', ['null' => true])
            ->addColumn('nacionality', 'string', ['limit' => 200])
            ->addColumn('gender', 'string', ['limit' => 1, 'null' => true])
            ->addColumn('marital_status', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('number_children', 'tinyinteger', ['limit' => 2, 'null' => true, 'default' => '0'])
            ->addColumn('mother_name', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('father_name', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('name_legal_representative', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('cpf_legal_representative', 'string', ['limit' => 14, 'null' => true])
            ->addColumn('affiliation_legal_representative', 'string', ['limit' => 50, 'null' => true])
            ->addForeignKey('adhesion_initial_data_id', 'adhesion_initial_data', 'id', ['delete' => 'CASCADE'])
            ->addTimestamps()
            ->create();

        $this->table('adhesion_documents')
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            ->addColumn('type', 'string', ['limit' => 50])
            ->addColumn('document_number', 'string', ['limit' => 255])
            ->addColumn('issue_date', 'date', ['null' => true])
            ->addColumn('issuer', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('place_birth', 'string', ['limit' => 100, 'null' => true])
            ->addTimestamps()
            ->addForeignKey('adhesion_initial_data_id', 'adhesion_initial_data', 'id', ['delete' => 'CASCADE'])
            ->create();

        // subscriptions
        $this->table('adhesion_plans')
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            ->addColumn('benefit_entry_age', 'tinyinteger', ['limit' => 3, 'null' => true])
            ->addColumn('monthly_contribuition_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('value_founding_contribution', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('insured_capital', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('contribution', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addTimestamps()
            ->addForeignKey('adhesion_initial_data_id', 'adhesion_initial_data', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('adhesion_dependents')
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('cpf', 'string', ['limit' => 14, 'null' => true])
            ->addColumn('birth_date', 'date', ['null' => true])
            ->addColumn('kinship', 'string', ['limit' => 40, 'null' => true])
            ->addColumn('participation', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addTimestamps()
            ->addForeignKey('adhesion_initial_data_id', 'adhesion_initial_data', 'id', ['delete' => 'CASCADE'])
            ->create();

        // addresses
        $this->table('adhesion_addresses')
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            ->addColumn('cep', 'string', ['limit' => 12])
            ->addColumn('address', 'string', ['limit' => 190])
            ->addColumn('number', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('complement', 'string', ['limit' => 80, 'null' => true])
            ->addColumn('neighborhood', 'string', ['limit' => 80])
            ->addColumn('city', 'string', ['limit' => 80])
            ->addColumn('state', 'string', ['limit' => 2])
            ->addTimestamps()
            ->addForeignKey('adhesion_initial_data_id', 'adhesion_initial_data', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('adhesion_other_informations')
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            ->addColumn('main_occupation', 'integer', ['null' => true])
            ->addColumn('category', 'string', ['limit' => 25])
            ->addColumn('brazilian_resident', 'boolean', ['default' => true, 'null' => true])
            ->addColumn('politically_exposed', 'boolean', ['default' => false, 'null' => true])
            ->addColumn('obligation_other_countries', 'boolean', ['default' => false, 'null' => true])
            ->addTimestamps()
            ->addForeignKey('adhesion_initial_data_id', 'adhesion_initial_data', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
