<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * O corretor virou dado de atribuição sem uso: nunca controlou risco (isso
 * é propriedade exclusiva do admin desde a entrega anterior) e quem alterou
 * uma adesão já fica registrado em adhesion_audits. Sem consumidor, a tabela
 * e as três colunas em adhesion_initial_data saem.
 */
class RemoveBrokers extends BaseMigration
{
    public function change(): void
    {
        $this->table('adhesion_initial_data')
            ->dropForeignKey('broker_id')
            ->removeColumn('broker_id')
            ->removeColumn('broker_name')
            ->removeColumn('broker_code')
            ->update();

        $this->table('brokers')->drop()->save();
    }
}
