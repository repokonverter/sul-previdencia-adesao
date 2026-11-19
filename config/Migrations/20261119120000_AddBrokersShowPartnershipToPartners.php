<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * O Parceiro decide se os seus Corretores levam a marca dele ("em parceria
 * com...") para a adesão. Desligado por padrão, inclusive nos parceiros que
 * já existem: a marca só aparece quando o admin escolhe.
 */
class AddBrokersShowPartnershipToPartners extends BaseMigration
{
    public function change(): void
    {
        $this->table('partners')
            ->addColumn('brokers_show_partnership', 'boolean', ['null' => false, 'default' => false])
            ->update();
    }
}
