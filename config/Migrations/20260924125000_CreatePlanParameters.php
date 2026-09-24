<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Parâmetros do plano, editáveis pelo admin em vez de hardcoded.
 *
 * Linha única: não há tela de criar nem de excluir, só de editar (ver
 * Admin\PlanParametersController). PlanParametersTable::current() é o único
 * caminho de leitura.
 *
 * As taxas estavam cravadas na procedure (16% e 10%) e o mínimo numa constante
 * de SimulatorController. Os dois pisos não existiam em lugar nenhum: eles só
 * passam a valer quando o admin digita o valor de um risco à mão (entrega 4) —
 * enquanto os riscos são percentuais, qualquer contribuição a partir do mínimo
 * já os satisfaz, e é por isso que o pedido original só faz sentido lendo-os
 * como limite do ajuste manual, não como resultado da fórmula.
 *
 * Alterar estes valores não mexe em nenhuma adesão já gravada: adhesion_plans
 * guarda os valores calculados, e o PDF lê de lá.
 */
class CreatePlanParameters extends BaseMigration
{
    public function up(): void
    {
        $this->table('plan_parameters')
            ->addColumn('minimum_monthly_contribution', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => false,
                'comment' => 'Contribuição mensal mínima, com ou sem risco contratado',
            ])
            ->addColumn('survivors_pension_percent', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false,
                'comment' => 'Fatia da contribuição destinada à pensão por morte, em %',
            ])
            ->addColumn('disability_retirement_percent', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false,
                'comment' => 'Fatia da contribuição destinada à aposentadoria por invalidez, em %',
            ])
            ->addColumn('survivors_pension_floor', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => false,
                'comment' => 'Mínimo em R$ para a pensão por morte quando o valor é ajustado à mão',
            ])
            ->addColumn('disability_retirement_floor', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => false,
                'comment' => 'Mínimo em R$ para a invalidez quando o valor é ajustado à mão',
            ])
            ->addTimestamps()
            ->create();

        // Os valores que estavam no código, para que nada mude de
        // comportamento ao migrar.
        $this->execute(
            "INSERT INTO plan_parameters (
                minimum_monthly_contribution,
                survivors_pension_percent,
                disability_retirement_percent,
                survivors_pension_floor,
                disability_retirement_floor,
                created,
                updated
            ) VALUES (100.00, 16.00, 10.00, 16.00, 10.00, NOW(), NOW())"
        );
    }

    public function down(): void
    {
        $this->table('plan_parameters')->drop()->update();
    }
}
