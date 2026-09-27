<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Guarda os ids e nomes dos documentos enviados nesta tentativa.
 *
 * Sem isso, listar os documentos para download exigiria uma chamada à
 * Clicksign a cada exibição da tela da adesão -- e uma que pode falhar,
 * deixando a tela quebrada por causa de terceiro. O id e o nome não mudam
 * depois de criados, então gravá-los aqui é seguro e evita a dependência.
 */
class AddDocumentsToClicksignData extends BaseMigration
{
    public function change(): void
    {
        $this->table('clicksign_data')
            ->addColumn('documents', 'text', [
                'null' => true,
                'comment' => 'JSON: [{"id": "...", "name": "..."}]',
            ])
            ->update();
    }
}
