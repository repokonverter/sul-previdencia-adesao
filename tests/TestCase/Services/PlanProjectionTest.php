<?php

declare(strict_types=1);

namespace App\Test\TestCase\Services;

use App\Services\PlanProjection;
use Cake\TestSuite\TestCase;

/**
 * A curva do gráfico do plano, sem banco: o que importa é que ela comece em
 * zero hoje e termine exatamente no saldo que a procedure devolveu.
 */
class PlanProjectionTest extends TestCase
{
    private function projection(int $months, float $contribution = 500.0, float $finalBalance = 123456.78): PlanProjection
    {
        return PlanProjection::fromProcedureRows([
            ['taxa_rentabilidade_anual' => '0.0606', 'saldo_acumulado' => '100000.00', 'beneficio_mensal' => '1900.00', 'contribuicao_aposentadoria' => (string)$contribution],
            ['taxa_rentabilidade_anual' => '0.0803', 'saldo_acumulado' => (string)$finalBalance, 'beneficio_mensal' => '2500.00', 'contribuicao_aposentadoria' => (string)$contribution],
            ['taxa_rentabilidade_anual' => '0.1008', 'saldo_acumulado' => '150000.00', 'beneficio_mensal' => '3100.00', 'contribuicao_aposentadoria' => (string)$contribution],
        ], $months);
    }

    public function testCurveStartsTodayAtZeroAndEndsOnTheProcedureBalance(): void
    {
        // 30 anos hoje: 419 meses, primeira idade com contribuição é 31.
        $series = $this->projection(419)->series();

        $this->assertSame(30, $series['ages'][0]);
        $this->assertSame(0.0, $series['pure'][0]);
        $this->assertSame(0.0, $series['compounded'][0]);

        $this->assertSame(PlanProjection::RETIREMENT_AGE, end($series['ages']));
        $this->assertSame(123456.78, end($series['compounded']));
        $this->assertSame(500.0 * 419, end($series['pure']));
        $this->assertCount(36, $series['ages']);
    }

    public function testIntermediatePointsCompoundAtTheChartScenarioRate(): void
    {
        $series = $this->projection(419)->series();
        $monthlyRate = 1.0803 ** (1 / 12) - 1;

        // Aos 31: 11 meses de contribuição.
        $this->assertSame(500.0 * 11, $series['pure'][1]);
        $this->assertEqualsWithDelta(500 * ((1 + $monthlyRate) ** 11 - 1) / $monthlyRate, $series['compounded'][1], 0.01);
    }

    /**
     * A menos de um ano dos 65 o simulador público desenha um ponto só; aqui
     * a curva ainda tem dois, hoje e os 65.
     */
    public function testLessThanAYearAwayStillHasACurve(): void
    {
        $series = $this->projection(8)->series();

        $this->assertSame([64, 65], $series['ages']);
    }

    public function testNoMonthsLeftHasNoProjection(): void
    {
        $projection = $this->projection(0);

        $this->assertFalse($projection->hasProjection());
        $this->assertSame(['ages' => [], 'pure' => [], 'compounded' => []], $projection->series());
        $this->assertFalse($projection->toArray()['hasProjection']);
    }

    public function testScenariosKeepTheProcedureOrder(): void
    {
        $scenarios = $this->projection(419)->scenarios;

        $this->assertSame([0.0606, 0.0803, 0.1008], array_column($scenarios, 'rate'));
        $this->assertSame(2500.0, $scenarios[PlanProjection::CHART_SCENARIO]['monthlyBenefit']);
    }
}
