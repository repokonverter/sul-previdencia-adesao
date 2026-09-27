<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * O status "assinada" passa a existir.
 *
 * Até aqui clicksign_data.status só guardava pending/sent/failed — a
 * gravação sabia que mandou para assinatura, nunca se alguém assinou. Esta
 * migration acrescenta `signed_at`, que é o que diferencia "enviado" de
 * "assinado" e o que a tela e o gating econômico passam a consultar.
 */
class AddSignatureTrackingToClicksignData extends BaseMigration
{
    public function change(): void
    {
        $this->table('clicksign_data')
            ->addColumn('signed_at', 'datetime', ['null' => true])
            ->update();
    }
}
