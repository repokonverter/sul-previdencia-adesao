<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Nº do Instituidor do vínculo associativo: aparece no topo da proposta
 * (pdf_template.php) no lugar do número fixo do CEPREV quando a adesão tem
 * vínculo. Texto, não inteiro, para preservar zeros à esquerda ("009").
 */
class AddInstitutionNumberToPartners extends BaseMigration
{
    public function change(): void
    {
        $this->table('partners')
            ->addColumn('institution_number', 'string', ['limit' => 20, 'null' => true])
            ->update();
    }
}
