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

        $this->table('adhesion_plans')
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            ->addColumn('benefit_entry_age', 'tinyinteger', ['limit' => 3, 'null' => true])
            ->addColumn('monthly_retirement_contribution', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('monthly_survivors_pension_contribution', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('survivors_pension_insured_capital', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('monthly_disability_retirement_contribution', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('disability_retirement_insured_capital', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
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

        $this->table('adhesion_proponent_statements')
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            ->addColumn('health_problem', 'boolean', ['default' => true, 'null' => true])
            ->addColumn('health_problem_obs', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('heart_disease', 'boolean', ['default' => true, 'null' => true])
            ->addColumn('heart_disease_obs', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('suffered_organ_defects', 'boolean', ['default' => true, 'null' => true])
            ->addColumn('suffered_organ_defects_obs', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('surgery', 'boolean', ['default' => true, 'null' => true])
            ->addColumn('surgery_obs', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('away', 'boolean', ['default' => true, 'null' => true])
            ->addColumn('away_obs', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('practices_parachuting', 'boolean', ['default' => true, 'null' => true])
            ->addColumn('practices_parachuting_obs', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('smoker', 'boolean', ['default' => true, 'null' => true])
            ->addColumn('smoker_type', 'boolean', ['default' => true, 'null' => true])
            ->addColumn('smoker_type_obs', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('smoker_qty', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('weight', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('height', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('gripe', 'boolean', ['default' => true, 'null' => true])
            ->addColumn('gripe_obs', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('covid', 'boolean', ['default' => true, 'null' => true])
            ->addColumn('covid_obs', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('covid_sequelae', 'boolean', ['default' => true, 'null' => true])
            ->addColumn('covid_sequelae_obs', 'string', ['limit' => 150, 'null' => true])
            ->addTimestamps()
            ->addForeignKey('adhesion_initial_data_id', 'adhesion_initial_data', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('adhesion_pension_schemes')
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            ->addColumn('pension_scheme', 'string', ['limit' => 25])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('cpf', 'string', ['limit' => 15])
            ->addColumn('kinship', 'string', ['limit' => 100])
            ->addTimestamps()
            ->addForeignKey('adhesion_initial_data_id', 'adhesion_initial_data', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('adhesion_payment_details')
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            ->addColumn('due_date', 'integer', ['null' => true])
            ->addColumn('total_contribution', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('payment_type', 'string', ['limit' => 50])
            ->addColumn('account_holder_name', 'string', ['limit' => 150])
            ->addColumn('account_holder_cpf', 'string', ['limit' => 15])
            ->addColumn('bank_number', 'string', ['limit' => 4])
            ->addColumn('bank_name', 'string', ['limit' => 200])
            ->addColumn('branch_number', 'string', ['limit' => 5])
            ->addColumn('account_number', 'string', ['limit' => 8])
            ->addTimestamps()
            ->addForeignKey('adhesion_initial_data_id', 'adhesion_initial_data', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
