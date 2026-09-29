<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Marca quando os passos "Regime de previdência" e "Beneficiário(s)" foram de
 * fato enviados.
 *
 * Os dois só gravam linha quando há algo a gravar -- zero linhas é resposta
 * legítima ("não está em regime nenhum", "sem beneficiário") e por isso
 * AdhesionSteps::EVIDENCE não os usa como prova de conclusão, pulando direto
 * para a etapa seguinte no rótulo de status. Sem essa coluna, quem parou
 * exatamente nesses passos aparece na listagem do admin como se já tivesse
 * chegado ao próximo -- errado especificamente por "Regime de previdência"
 * anteceder "Plano", cuja etapa nasceu com esse rótulo emprestado.
 */
class AddStepAnsweredMarkersToAdhesionInitialData extends BaseMigration
{
    public function up(): void
    {
        $this->table('adhesion_initial_data')
            ->addColumn('pension_scheme_answered_at', 'datetime', ['null' => true])
            ->addColumn('dependents_answered_at', 'datetime', ['null' => true])
            ->update();
    }

    public function down(): void
    {
        $this->table('adhesion_initial_data')
            ->removeColumn('pension_scheme_answered_at')
            ->removeColumn('dependents_answered_at')
            ->update();
    }
}
