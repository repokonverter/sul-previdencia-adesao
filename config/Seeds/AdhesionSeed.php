<?php
declare(strict_types=1);

use Migrations\AbstractSeed;
use Cake\Utility\Text;

class AdhesionSeed extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $uuid = Text::uuid(); // storage_uuid

        // 1) Insert into adhesion_initial_data and return ID
        $initial = $this->fetchRow("
            INSERT INTO adhesion_initial_data (storage_uuid, name, email, phone, created, updated)
            VALUES ('$uuid', 'João da Silva', 'joao.silva@example.com', '(47) 98888-7777', '$now', '$now')
            RETURNING id;
        ");

        $initialId = $initial['id'];

        // 2) adhesion_personal_data
        $this->execute("
            INSERT INTO adhesion_personal_data
            (adhesion_initial_data_id, plan_for, name, cpf, birth_date, nacionality, gender, marital_status, number_children, mother_name, father_name, name_legal_representative, cpf_legal_representative, affiliation_legal_representative, created, updated)
            VALUES
            ($initialId, 'Titular', 'João da Silva', '12345678901', '1988-01-15', 'Brasileiro', 'M', 'Casado', 2, 'Maria Silva', 'Pedro Silva', NULL, NULL, NULL, '$now', '$now')
        ");

        // 3) adhesion_plans
        // $this->execute("
        //     INSERT INTO adhesion_plans
        //     (adhesion_initial_data_id, benefit_entry_age, monthly_contribuition_amount, value_founding_contribution, insured_capital, contribution, created, updated)
        //     VALUES
        //     ($initialId, 35, 500.00, 1000.00, 200000.00, '100.00', '$now', '$now')
        // ");

        // 4) adhesion_addresses
        $this->execute("
            INSERT INTO adhesion_addresses
            (adhesion_initial_data_id, cep, address, number, complement, neighborhood, city, state, created, updated)
            VALUES
            ($initialId, '88330000', 'Rua das Palmeiras', '100', 'Apto 101', 'Centro', 'Balneário Camboriú', 'SC', '$now', '$now')
        ");

        // 5) adhesion_other_informations
        $this->execute("
            INSERT INTO adhesion_other_informations
            (adhesion_initial_data_id, main_occupation, category, brazilian_resident, politically_exposed, obligation_other_countries, created, updated)
            VALUES
            ($initialId, '3', 'Profissional Liberal', true, false, false, '$now', '$now')
        ");

        // 6) adhesion_documents (RG/Certidão example)
        $this->execute("
            INSERT INTO adhesion_documents
            (adhesion_initial_data_id, type, type_other, document_number, issue_date, issuer, place_birth, created, updated)
            VALUES
            ($initialId, 'RG', 'RG', '1234567', '2005-08-10', 'SSP-SC', 'Florianópolis', '$now', '$now')
        ");

        // 7) adhesion_dependents
        $this->execute("
            INSERT INTO adhesion_dependents
            (adhesion_initial_data_id, name, cpf, birth_date, kinship, participation, created, updated)
            VALUES
            ($initialId, 'Ana Silva', '12312312312', '2015-09-10', 'Filha', 50, '$now', '$now'),
            ($initialId, 'Lucas Silva', '32132132132', '2018-04-22', 'Filho', 50, '$now', '$now')
        ");

        // ------------------------------------------------------------
        // 2) adhesion_payment_details  (NOVA TABELA)
        // ------------------------------------------------------------
        $paymentDetails = [
            [
                'adhesion_initial_data_id' => $initialId,
                'due_date' => 10,
                'total_contribution' => 250.50,
                'payment_type' => 'Débito em conta',

                'account_holder_name' => 'João da Silva',
                'account_holder_cpf' => '123.456.789-00',

                'bank_number' => '237',
                'bank_name' => 'Bradesco',
                'branch_number' => '1234',
                'account_number' => '87654321',

                'created' => date('Y-m-d H:i:s'),
            ]
        ];

        $this->table('adhesion_payment_details')
            ->insert($paymentDetails)
            ->saveData();


        // ------------------------------------------------------------
        // 3) adhesion_pension_schemes  (NOVA TABELA)
        // ------------------------------------------------------------
        $pensionSchemes = [
            [
                'adhesion_initial_data_id' => $initialId,
                'pension_scheme' => 'PGBL',
                'name' => 'Maria Silva',
                'cpf' => '987.654.321-00',
                'kinship' => 'Cônjuge',

                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'adhesion_initial_data_id' => $initialId,
                'pension_scheme' => 'PGBL',
                'name' => 'Pedro Henrique Silva',
                'cpf' => '111.222.333-44',
                'kinship' => 'Filho',

                'created' => date('Y-m-d H:i:s'),
            ]
        ];

        $this->table('adhesion_pension_schemes')
            ->insert($pensionSchemes)
            ->saveData();


        // ------------------------------------------------------------
        // 4) adhesion_proponent_statements  (NOVA TABELA)
        // ------------------------------------------------------------
        $proponentStatements = [
            [
                'adhesion_initial_data_id' => $initialId,

                'health_problem' => false,
                'health_problem_obs' => null,

                'heart_disease' => false,
                'heart_disease_obs' => null,

                'suffered_organ_defects' => false,
                'suffered_organ_defects_obs' => null,

                'surgery' => true,
                'surgery_obs' => 'Apendicectomia em 2018',

                'away' => false,
                'away_obs' => null,

                'practices_parachuting' => false,
                'practices_parachuting_obs' => null,

                'smoker' => true,
                'smoker_type' => false,
                'smoker_type_obs' => 'Ocasional',
                'smoker_qty' => '2 cigarros por semana',

                'weight' => 82.50,
                'height' => 1.78,

                'gripe' => false,
                'gripe_obs' => null,

                'covid' => true,
                'covid_obs' => 'Infectado em 2021',
                'covid_sequelae' => false,
                'covid_sequelae_obs' => null,

                'created' => date('Y-m-d H:i:s'),
            ]
        ];

        $this->table('adhesion_proponent_statements')
            ->insert($proponentStatements)
            ->saveData();
    }
}
