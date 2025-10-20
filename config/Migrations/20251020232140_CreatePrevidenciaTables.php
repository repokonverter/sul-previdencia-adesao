<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreatePrevidenciaTables extends AbstractMigration
{
    public function change(): void
    {
        // subscriptions
        $this->table('subscriptions')
            ->addColumn('plan_type', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('plan_value', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
            ->addColumn('periodicity', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('payment_method', 'string', ['limit' => 30, 'null' => true])
            ->addTimestamps()
            ->create();

        // people
        $this->table('people')
            ->addColumn('subscription_id', 'integer', ['null' => false])
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('cpf', 'string', ['limit' => 14])
            ->addColumn('birth_date', 'date', ['null' => true])
            ->addColumn('marital_status', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('gender', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('is_legal_representative', 'boolean', ['default' => false])
            ->addTimestamps()
            ->addForeignKey('subscription_id', 'subscriptions', 'id', ['delete'=> 'CASCADE'])
            ->create();

        // addresses
        $this->table('addresses')
            ->addColumn('subscription_id', 'integer', ['null' => false])
            ->addColumn('cep', 'string', ['limit' => 9])
            ->addColumn('address', 'string', ['limit' => 120])
            ->addColumn('number', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('complement', 'string', ['limit' => 80, 'null' => true])
            ->addColumn('neighborhood', 'string', ['limit' => 80])
            ->addColumn('city', 'string', ['limit' => 80])
            ->addColumn('state', 'string', ['limit' => 2])
            ->addTimestamps()
            ->addForeignKey('subscription_id', 'subscriptions', 'id', ['delete'=> 'CASCADE'])
            ->create();

        // dependents
        $this->table('dependents')
            ->addColumn('subscription_id', 'integer', ['null' => false])
            ->addColumn('name', 'string', ['limit' => 120])
            ->addColumn('cpf', 'string', ['limit' => 14, 'null' => true])
            ->addColumn('birth_date', 'date', ['null' => true])
            ->addColumn('kinship', 'string', ['limit' => 30, 'null' => true])
            ->addTimestamps()
            ->addForeignKey('subscription_id', 'subscriptions', 'id', ['delete'=> 'CASCADE'])
            ->create();

        // documents
        $this->table('documents')
            ->addColumn('subscription_id', 'integer', ['null' => false])
            ->addColumn('type', 'string', ['limit' => 50])
            ->addColumn('file_path', 'string', ['limit' => 255])
            ->addColumn('issue_date', 'date', ['null' => true])
            ->addColumn('issuer', 'string', ['limit' => 50, 'null' => true])
            ->addTimestamps()
            ->addForeignKey('subscription_id', 'subscriptions', 'id', ['delete'=> 'CASCADE'])
            ->create();
    }
}
