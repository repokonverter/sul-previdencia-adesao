<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateBrokersAndRiskRemoval extends BaseMigration
{
    public function change(): void
    {
        $this->table('brokers')
            ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('code', 'string', ['limit' => 30, 'null' => false])
            ->addColumn('susep_code', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('active', 'boolean', ['null' => false, 'default' => true])
            ->addTimestamps()
            ->addIndex(['code'], ['unique' => true])
            ->create();

        // broker_id é RESTRICT (não SET NULL): o servidor recalcula quais
        // riscos existem a partir do corretor gravado na adesão a cada
        // requisição (ver RegistrationsController::save()), então perder essa
        // referência silenciosamente reabriria riscos que a pessoa removeu.
        // broker_name/broker_code são o snapshot para rastreabilidade mesmo
        // que o corretor seja renomeado depois.
        $this->table('adhesion_initial_data')
            ->addColumn('broker_id', 'integer', ['null' => true])
            ->addColumn('broker_name', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('broker_code', 'string', ['limit' => 30, 'null' => true])
            ->addForeignKey('broker_id', 'brokers', 'id', ['delete' => 'RESTRICT'])
            ->update();

        // Default true: toda adesão existente e toda adesão sem corretor
        // contratou os dois riscos, exatamente como sempre foi.
        $this->table('adhesion_plans')
            ->addColumn('has_survivors_pension', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('has_disability_retirement', 'boolean', ['null' => false, 'default' => true])
            ->update();
    }
}
