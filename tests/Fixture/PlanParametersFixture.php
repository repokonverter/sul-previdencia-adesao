<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * A linha única de plan_parameters.
 *
 * Existe porque Migrator::run() trunca as tabelas depois de migrar: a linha
 * que CreatePlanParameters insere não sobrevive à preparação do banco de
 * teste. Os valores são os mesmos que a migration semeia, para que um teste
 * que não fala de parâmetros veja o mesmo comportamento da aplicação.
 */
class PlanParametersFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'minimum_monthly_contribution' => '100.00',
                'survivors_pension_percent' => '16.00',
                'disability_retirement_percent' => '10.00',
                'survivors_pension_floor' => '16.00',
                'disability_retirement_floor' => '10.00',
                'resume_link_days' => 7,
                'created' => '2026-01-01 00:00:00',
                'updated' => '2026-01-01 00:00:00',
            ],
        ];
        parent::init();
    }
}
