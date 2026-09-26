<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Um envelope por tentativa, e não um por adesão.
 *
 * A Clicksign só permite apagar documento de envelope em status `draft`. Uma
 * proposta refinalizada — o que o link de retomada torna rotina — não tem como
 * trocar os PDFs do envelope que já saiu para assinatura: a única saída é
 * cancelar aquele e criar outro.
 *
 * Com hasOne, criar o envelope novo sobrescreveria envelope_id e perderia o
 * rastro do anterior, que é justamente o que se quer poder baixar quando ele
 * tiver sido assinado. Mesmo padrão de pix_transactions, que já resolve isso
 * com `attempt`.
 */
class AddAttemptToClicksignData extends BaseMigration
{
    public function up(): void
    {
        $this->table('clicksign_data')
            ->addColumn('attempt', 'integer', ['null' => false, 'default' => 1])
            ->addColumn('canceled_at', 'datetime', ['null' => true])
            ->addIndex(['adhesion_initial_data_id', 'attempt'])
            ->update();
    }

    public function down(): void
    {
        $this->table('clicksign_data')
            ->removeColumn('attempt')
            ->removeColumn('canceled_at')
            ->update();
    }
}
