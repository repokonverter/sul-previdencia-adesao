<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * O webhook registrado na Clicksign, mesmo padrão de pix_webhooks: o segredo
 * vive no path da URL cadastrada (`/clicksign/webhook/{token}`), não em
 * header ou query, e é conferido a cada chamada recebida.
 *
 * Uma linha por registro — normalmente uma só, já que a conta da Clicksign é
 * única — para que trocar o token não exija apagar e recriar do zero.
 */
class CreateClicksignWebhooks extends BaseMigration
{
    public function change(): void
    {
        $this->table('clicksign_webhooks')
            ->addColumn('clicksign_webhook_id', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('token', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('url', 'text', ['null' => false])
            ->addIndex(['token'], ['unique' => true])
            ->addTimestamps()
            ->create();
    }
}
