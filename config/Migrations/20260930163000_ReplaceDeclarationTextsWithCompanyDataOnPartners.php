<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * A Declaração de comparecimento ao Plano deixou de reaproveitar a ficha de
 * inscrição CEPREV: vínculo associativo agora gera seu próprio PDF
 * (Declaração de Vínculo Associativo), que precisa do nome e do CNPJ da
 * empresa/entidade, não de um título/corpo de texto livre.
 */
class ReplaceDeclarationTextsWithCompanyDataOnPartners extends BaseMigration
{
    public function up(): void
    {
        $this->table('partners')
            ->removeColumn('declaration_title')
            ->removeColumn('declaration_institution_name')
            ->removeColumn('declaration_body')
            ->addColumn('company_name', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('company_cnpj', 'string', ['limit' => 18, 'null' => true])
            ->update();
    }

    public function down(): void
    {
        $this->table('partners')
            ->removeColumn('company_name')
            ->removeColumn('company_cnpj')
            ->addColumn('declaration_title', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('declaration_institution_name', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('declaration_body', 'text', ['null' => true])
            ->update();
    }
}
