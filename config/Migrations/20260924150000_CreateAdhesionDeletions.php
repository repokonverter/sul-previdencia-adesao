<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Quem excluiu qual adesão, e quando.
 *
 * Tabela separada de adhesion_audits, e sem chave estrangeira para a adesão,
 * porque é justamente o caso em que a adesão deixa de existir: a FK de
 * adhesion_audits é CASCADE, então uma linha registrando a exclusão seria
 * apagada pela própria exclusão que registra.
 *
 * Guarda o mínimo para identificar o que foi apagado — nome e CPF do
 * proponente — e deliberadamente NÃO guarda a adesão inteira. Reter aqui a
 * filiação, os dados bancários e a Declaração Pessoal de Saúde de alguém
 * depois de a adesão ter sido apagada esvaziaria o sentido de apagar. O que
 * fica é o necessário para responder "quem apagou a adesão de quem", que é a
 * pergunta de responsabilidade.
 */
class CreateAdhesionDeletions extends BaseMigration
{
    public function change(): void
    {
        $this->table('adhesion_deletions')
            // Sem FK: a linha referenciada já não existe. O id fica como
            // registro histórico, para cruzar com logs antigos.
            ->addColumn('adhesion_initial_data_id', 'integer', ['null' => false])
            ->addColumn('adhesion_name', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('adhesion_cpf', 'string', ['limit' => 14, 'null' => true])
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('user_name', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addIndex(['created'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'RESTRICT'])
            ->create();
    }
}
