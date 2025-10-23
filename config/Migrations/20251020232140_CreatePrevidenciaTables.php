<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePrevidenciaTables extends BaseMigration
{
    public function change(): void
    {
        // people
        $this->table('people')
            ->addColumn('storage_uuid', 'string', ['limit' => 50])
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('cpf', 'string', ['limit' => 14])
            ->addColumn('birth_date', 'date', ['null' => true])
            ->addColumn('marital_status', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('gender', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('is_legal_representative', 'boolean', ['default' => false])
            ->addTimestamps()
            ->create();

        // subscriptions
        $this->table('subscriptions')
            ->addColumn('person_id', 'integer', ['null' => false])
            ->addColumn('plan_type', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('plan_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('periodicity', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('payment_method', 'string', ['limit' => 30, 'null' => true])
            ->addTimestamps()
            ->addForeignKey('person_id', 'people', 'id', ['delete' => 'CASCADE'])
            ->create();

        // addresses
        $this->table('addresses')
            ->addColumn('person_id', 'integer', ['null' => false])
            ->addColumn('cep', 'string', ['limit' => 9])
            ->addColumn('address', 'string', ['limit' => 120])
            ->addColumn('number', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('complement', 'string', ['limit' => 80, 'null' => true])
            ->addColumn('neighborhood', 'string', ['limit' => 80])
            ->addColumn('city', 'string', ['limit' => 80])
            ->addColumn('state', 'string', ['limit' => 2])
            ->addTimestamps()
            ->addForeignKey('person_id', 'people', 'id', ['delete' => 'CASCADE'])
            ->create();

        // dependents
        $this->table('dependents')
            ->addColumn('person_id', 'integer', ['null' => false])
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('cpf', 'string', ['limit' => 14, 'null' => true])
            ->addColumn('birth_date', 'date', ['null' => true])
            ->addColumn('kinship', 'string', ['limit' => 30, 'null' => true])
            ->addTimestamps()
            ->addForeignKey('person_id', 'people', 'id', ['delete' => 'CASCADE'])
            ->create();

        // documents
        $this->table('documents')
            ->addColumn('person_id', 'integer', ['null' => false])
            ->addColumn('type', 'string', ['limit' => 50])
            ->addColumn('file_path', 'string', ['limit' => 255])
            ->addColumn('issue_date', 'date', ['null' => true])
            ->addColumn('issuer', 'string', ['limit' => 50, 'null' => true])
            ->addTimestamps()
            ->addForeignKey('person_id', 'people', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
