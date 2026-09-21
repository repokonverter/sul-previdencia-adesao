<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddAssociationsToPartnersAndAdhesions extends BaseMigration
{
    public function change(): void
    {
        // Vínculo associativo é um Partner com is_association = true. Os dois
        // conjuntos (parceiros comuns e vínculos) são disjuntos: o admin
        // apresenta cada um em sua própria tela, mas é a mesma tabela e o
        // mesmo mecanismo de código promocional por trás.
        $this->table('partners')
            ->addColumn('is_association', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('declaration_title', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('declaration_institution_name', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('declaration_body', 'text', ['null' => true])
            ->update();

        // association_partner_id é gravado à parte de promotional_code_id
        // (RESTRICT, não SET NULL) para que o vínculo sobreviva a qualquer
        // mudança no cadastro do código que o originou. association_snapshot
        // guarda os textos da declaração no momento da adesão, para que o
        // admin sempre regenere o PDF que foi de fato assinado, mesmo que o
        // cadastro do vínculo mude depois.
        $this->table('adhesion_initial_data')
            ->addColumn('association_partner_id', 'integer', ['null' => true])
            ->addColumn('association_snapshot', 'text', ['null' => true])
            ->addForeignKey('association_partner_id', 'partners', 'id', ['delete' => 'RESTRICT'])
            ->update();
    }
}
