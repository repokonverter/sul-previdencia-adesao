<?php

declare(strict_types=1);

namespace App\Services;

/**
 * A projeção de um plano já decidido: os três cenários de rentabilidade da
 * procedure e a curva ano a ano até os 65 anos.
 *
 * Existe para que a aba Plano do admin e o PDF da simulação mostrem os mesmos
 * números. A curva segue a mesma conta do gráfico do simulador público
 * (templates/Simulator/index.php): cenário do meio, mesmo prazo da procedure,
 * e o ponto dos 65 é o próprio saldo que ela devolveu -- recalculado aqui, ele
 * poderia divergir do card por arredondamento.
 */
class PlanProjection
{
    public const RETIREMENT_AGE = 65;

    /**
     * Posição, nas linhas da procedure (6%, 8%, 10%), do cenário que vira
     * gráfico. É o mesmo que o simulador público desenha.
     */
    public const CHART_SCENARIO = 1;

    /**
     * @param array<int, array{rate: float, balance: float, monthlyBenefit: float}> $scenarios
     */
    public function __construct(
        public readonly array $scenarios,
        public readonly int $monthsUntilRetirement,
        public readonly float $retirementContribution,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $rows linhas de simulacao_previdencia
     */
    public static function fromProcedureRows(array $rows, int $monthsUntilRetirement): self
    {
        $scenarios = array_map(fn(array $row): array => [
            'rate' => (float)$row['taxa_rentabilidade_anual'],
            'balance' => (float)$row['saldo_acumulado'],
            'monthlyBenefit' => (float)$row['beneficio_mensal'],
        ], $rows);

        return new self(
            $scenarios,
            $monthsUntilRetirement,
            (float)($rows[0]['contribuicao_aposentadoria'] ?? 0),
        );
    }

    /**
     * Sem meses até os 65 não há curva: quem já chegou lá não acumula mais.
     */
    public function hasProjection(): bool
    {
        return $this->monthsUntilRetirement > 0 && isset($this->scenarios[self::CHART_SCENARIO]);
    }

    /**
     * Uma idade por ponto, de 12 em 12 meses contados para trás a partir dos
     * 65. Idades sem nenhum mês de contribuição ficam fora.
     *
     * Diferente do simulador público, a curva começa na idade de hoje com
     * saldo zero: para quem está a menos de um ano dos 65 sobrava um ponto
     * só, e um gráfico de um ponto não mostra evolução nenhuma.
     *
     * @return array{ages: array<int>, pure: array<float>, compounded: array<float>}
     */
    public function series(): array
    {
        $series = ['ages' => [], 'pure' => [], 'compounded' => []];

        if (!$this->hasProjection()) {
            return $series;
        }

        $scenario = $this->scenarios[self::CHART_SCENARIO];
        $monthlyRate = (1 + $scenario['rate']) ** (1 / 12) - 1;
        $totalMonths = $this->monthsUntilRetirement;
        $contribution = $this->retirementContribution;
        $firstAge = self::RETIREMENT_AGE - intdiv($totalMonths - 1, 12);

        $series['ages'][] = $firstAge - 1;
        $series['pure'][] = 0.0;
        $series['compounded'][] = 0.0;

        for ($age = $firstAge; $age <= self::RETIREMENT_AGE; $age++) {
            $months = $totalMonths - 12 * (self::RETIREMENT_AGE - $age);
            $balance = $age === self::RETIREMENT_AGE
                ? $scenario['balance']
                : $contribution * (((1 + $monthlyRate) ** $months - 1) / $monthlyRate);

            $series['ages'][] = $age;
            $series['pure'][] = round($contribution * $months, 2);
            $series['compounded'][] = round($balance, 2);
        }

        return $series;
    }

    public function chartScenario(): ?array
    {
        return $this->scenarios[self::CHART_SCENARIO] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'scenarios' => $this->scenarios,
            'monthsUntilRetirement' => $this->monthsUntilRetirement,
            'retirementContribution' => $this->retirementContribution,
            'hasProjection' => $this->hasProjection(),
            'series' => $this->series(),
        ];
    }
}
