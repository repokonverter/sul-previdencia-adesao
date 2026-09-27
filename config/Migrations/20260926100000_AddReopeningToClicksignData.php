<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * "Reabrir proposta" para uma adesão travada por assinatura.
 *
 * Não mexe em `status`/`signed_at`: o envelope assinado continua sendo o
 * contrato que a pessoa de fato assinou, prova histórica que nunca é
 * reescrita. `reopened_at` é uma segunda informação, ortogonal à primeira —
 * "o admin decidiu destravar a edição apesar da assinatura existir" — e é só
 * ela que o gating econômico passa a consultar.
 *
 * Não existe equivalente para adesão paga: dinheiro já recebido não é
 * destravado por um clique administrativo sem processo de conciliação, e essa
 * ação nem tenta.
 */
class AddReopeningToClicksignData extends BaseMigration
{
    public function change(): void
    {
        $this->table('clicksign_data')
            ->addColumn('reopened_at', 'datetime', ['null' => true])
            ->addColumn('reopened_by_user_id', 'integer', ['null' => true])
            ->addForeignKey('reopened_by_user_id', 'users', 'id', ['delete' => 'RESTRICT'])
            ->update();
    }
}
