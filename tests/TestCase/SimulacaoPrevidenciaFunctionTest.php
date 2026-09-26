<?php

declare(strict_types=1);

namespace App\Test\TestCase;

use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Cobre a stored procedure simulacao_previdencia diretamente.
 *
 * Depois de CollapseSimulatorFunctionToRiskAmounts a procedure não decide mais
 * quanto vai para cada risco — recebe os valores prontos, em reais. O que
 * sobra para ela, e é o que este arquivo testa, é a parte atuarial: a
 * aposentadoria absorve o que não foi para risco, e absorve ANTES do cálculo
 * de saldo_acumulado/beneficio_mensal; o capital segurado sai da contribuição
 * de cada risco pela tabela de custo da idade.
 *
 * As taxas de 16% e 10% que antes viviam aqui são agora regra de PHP, testadas
 * em PlanSimulatorTest.
 */
class SimulacaoPrevidenciaFunctionTest extends TestCase
{
    private function simulate(string $birthDate, float $monthly, float $survivorsPension, float $disabilityRetirement): array
    {
        return ConnectionManager::get('test')->execute(
            'SELECT * FROM simulacao_previdencia(:date, :value, :survivorsPension, :disabilityRetirement)',
            [
                'date' => $birthDate,
                'value' => $monthly,
                'survivorsPension' => $survivorsPension,
                'disabilityRetirement' => $disabilityRetirement,
            ],
            [
                'value' => 'decimal',
                'survivorsPension' => 'decimal',
                'disabilityRetirement' => 'decimal',
            ]
        )->fetchAll('assoc');
    }

    public function testRetirementReceivesWhateverIsNotSpentOnRisk(): void
    {
        $rows = $this->simulate('1990-01-01', 1000.0, 160.0, 100.0);

        $this->assertEqualsWithDelta(160.0, (float)$rows[0]['contribuicao_morte'], 0.01);
        $this->assertEqualsWithDelta(100.0, (float)$rows[0]['contribuicao_invalidez'], 0.01);
        $this->assertEqualsWithDelta(740.0, (float)$rows[0]['contribuicao_aposentadoria'], 0.01);
    }

    public function testRiskWithoutContributionHasNoCoverage(): void
    {
        $rows = $this->simulate('1990-01-01', 1000.0, 0.0, 100.0);

        $this->assertEqualsWithDelta(0.0, (float)$rows[0]['contribuicao_morte'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float)$rows[0]['cobertura_morte'], 0.01);
        $this->assertGreaterThan(0.0, (float)$rows[0]['cobertura_invalidez']);
        $this->assertEqualsWithDelta(900.0, (float)$rows[0]['contribuicao_aposentadoria'], 0.01);
    }

    public function testMoreIntoRetirementGrowsTheProjection(): void
    {
        $withRisks = $this->simulate('1990-01-01', 1000.0, 160.0, 100.0);
        $withoutRisks = $this->simulate('1990-01-01', 1000.0, 0.0, 0.0);

        $this->assertEqualsWithDelta(1000.0, (float)$withoutRisks[0]['contribuicao_aposentadoria'], 0.01);

        // O ponto da parametrização: sem risco, o dinheiro entra na
        // previdência antes de saldo_acumulado ser calculado. Se fosse zerado
        // depois, os dois cenários dariam o mesmo saldo.
        $this->assertGreaterThan(
            (float)$withRisks[0]['saldo_acumulado'],
            (float)$withoutRisks[0]['saldo_acumulado']
        );
        $this->assertGreaterThan(
            (float)$withRisks[0]['beneficio_mensal'],
            (float)$withoutRisks[0]['beneficio_mensal']
        );
    }

    public function testCoverageScalesWithTheRiskContribution(): void
    {
        $single = $this->simulate('1990-01-01', 1000.0, 80.0, 0.0);
        $double = $this->simulate('1990-01-01', 1000.0, 160.0, 0.0);

        // Capital = contribuição / custo unitário da idade, limitado ao teto:
        // dobrar a contribuição dobra a cobertura enquanto o teto não morde.
        $this->assertEqualsWithDelta(
            2 * (float)$single[0]['cobertura_morte'],
            (float)$double[0]['cobertura_morte'],
            0.02
        );
    }

    /**
     * As migrations anteriores deixavam o DROP comentado, e no Postgres
     * CREATE OR REPLACE com assinatura diferente cria sobrecarga. Com as
     * versões antigas vivas e DEFAULT nos parâmetros 3 e 4, uma chamada com
     * dois argumentos respondia "function ... is not unique".
     */
    public function testOnlyTheFourNumericSignatureExists(): void
    {
        $signatures = ConnectionManager::get('test')->execute(
            "SELECT oid::regprocedure::text AS signature
               FROM pg_proc
              WHERE proname = 'simulacao_previdencia'"
        )->fetchAll('assoc');

        $this->assertSame(
            ['simulacao_previdencia(date,numeric,numeric,numeric)'],
            array_column($signatures, 'signature')
        );
    }
}
