<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Multiplano: cada adesão, parceiro (e vínculo associativo), linha de
 * parâmetros e webhook Pix passa a pertencer a um plano de benefícios
 * (slug de config/plans.php), e cada cobrança Pix grava o banco onde nasceu.
 *
 * Tudo que existe é do PlenoPrev, recebido pelo Sicoob. As colunas nascem
 * nulas, recebem esse valor e só então viram NOT NULL, sem default: daqui em
 * diante, gravar sem dizer o plano tem que falhar, e não cair em silêncio
 * no PlenoPrev.
 *
 * Ver docs/plano-multiplano-maispremium.md.
 */
class AddBenefitPlanColumns extends BaseMigration
{
    private const BENEFIT_PLAN_TABLES = [
        'adhesion_initial_data',
        'partners',
        'plan_parameters',
        'pix_webhooks',
    ];

    public function up(): void
    {
        foreach (self::BENEFIT_PLAN_TABLES as $table) {
            $this->table($table)
                ->addColumn('benefit_plan', 'string', ['limit' => 30, 'null' => true])
                ->update();

            $this->execute("UPDATE $table SET benefit_plan = 'plenoprev'");

            $this->table($table)
                ->changeColumn('benefit_plan', 'string', ['limit' => 30, 'null' => false])
                ->update();
        }

        $this->table('adhesion_initial_data')
            ->addIndex(['benefit_plan'])
            ->update();

        $this->table('partners')
            ->addIndex(['benefit_plan'])
            ->update();

        // Uma linha por plano: PlanParametersTable::forPlan() não escolhe
        // entre duas.
        $this->table('plan_parameters')
            ->addIndex(['benefit_plan'], ['unique' => true])
            ->update();

        $this->table('pix_transactions')
            ->addColumn('provider', 'string', ['limit' => 30, 'null' => true])
            ->update();

        $this->execute("UPDATE pix_transactions SET provider = 'sicoob'");

        $this->table('pix_transactions')
            ->changeColumn('provider', 'string', ['limit' => 30, 'null' => false])
            ->update();
    }

    public function down(): void
    {
        $this->table('pix_transactions')
            ->removeColumn('provider')
            ->update();

        foreach (self::BENEFIT_PLAN_TABLES as $table) {
            $this->table($table)
                ->removeColumn('benefit_plan')
                ->update();
        }
    }
}
