<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Corretor: pessoa de um Parceiro que atendeu o participante (ver
 * GLOSSARY.md). Não é o corretor removido em RemoveBrokers, que era um
 * cadastro solto, sem parceiro, capturado em silêncio por ?broker=.
 */
class CreatePartnerBrokers extends BaseMigration
{
    public function change(): void
    {
        // CPF único só dentro do parceiro: a mesma pessoa pode atender por
        // dois parceiros. O Postgres não compara NULLs no índice único, então
        // corretores sem CPF não colidem entre si.
        $this->table('brokers')
            ->addColumn('partner_id', 'integer', ['null' => false])
            ->addColumn('name', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('cpf', 'string', ['limit' => 11, 'null' => true])
            ->addColumn('token', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('active', 'boolean', ['null' => false, 'default' => true])
            ->addTimestamps()
            ->addIndex(['token'], ['unique' => true])
            ->addIndex(['partner_id', 'cpf'], ['unique' => true])
            ->addForeignKey('partner_id', 'partners', 'id', ['delete' => 'CASCADE'])
            ->create();

        // RESTRICT, e não SET NULL como o código promocional: o admin já
        // recusa excluir corretor usado, e se algum caminho escapar disso, o
        // banco recusa também em vez de apagar a atribuição sem ninguém ver.
        $this->table('adhesion_initial_data')
            ->addColumn('broker_id', 'integer', ['null' => true])
            ->addForeignKey('broker_id', 'brokers', 'id', ['delete' => 'RESTRICT'])
            ->update();
    }
}
