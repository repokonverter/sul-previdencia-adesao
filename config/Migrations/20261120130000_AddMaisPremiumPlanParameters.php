<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * A linha de plan_parameters do MaisPremium: PlanParametersTable::forPlan()
 * exige uma por plano de config/plans.php.
 *
 * Os valores iniciais do cliente ainda não vieram; parte dos do PlenoPrev,
 * e são editáveis no admin (Parâmetros do plano, aba MaisPremium). A linha
 * existir com o plano desligado (MAISPREMIUM_ENABLED) não tem efeito.
 */
class AddMaisPremiumPlanParameters extends BaseMigration
{
    public function up(): void
    {
        $this->execute(
            "INSERT INTO plan_parameters (
                benefit_plan,
                minimum_monthly_contribution,
                survivors_pension_percent,
                disability_retirement_percent,
                survivors_pension_floor,
                disability_retirement_floor,
                resume_link_days,
                created,
                updated
            )
            SELECT
                'maispremium',
                minimum_monthly_contribution,
                survivors_pension_percent,
                disability_retirement_percent,
                survivors_pension_floor,
                disability_retirement_floor,
                resume_link_days,
                NOW(),
                NOW()
            FROM plan_parameters
            WHERE benefit_plan = 'plenoprev'
                AND NOT EXISTS (SELECT 1 FROM plan_parameters WHERE benefit_plan = 'maispremium')"
        );
    }

    public function down(): void
    {
        $this->execute("DELETE FROM plan_parameters WHERE benefit_plan = 'maispremium'");
    }
}
