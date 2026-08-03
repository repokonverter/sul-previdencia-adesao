<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddObsColumnsToAdhesionOtherInformations extends BaseMigration
{
    public function change(): void
    {
        $this->table('adhesion_other_informations')
            ->addColumn('brazilian_resident_obs', 'string', ['limit' => 250, 'null' => true, 'after' => 'brazilian_resident'])
            ->addColumn('obligation_other_countries_obs', 'string', ['limit' => 250, 'null' => true, 'after' => 'obligation_other_countries'])
            ->update();
    }
}
