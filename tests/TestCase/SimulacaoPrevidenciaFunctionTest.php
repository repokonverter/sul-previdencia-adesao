<?php

declare(strict_types=1);

namespace App\Test\TestCase;

use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Cobre a stored procedure simulacao_previdencia diretamente, focando na
 * parametrização de risco introduzida pela migration
 * ParameterizeSimulatorFunctionRisks: quando um risco é desligado, sua fatia
 * da contribuição precisa ir inteira para a previdência ANTES do cálculo de
 * saldo_acumulado/beneficio_mensal — não apenas ser zerada depois, o que
 * subestimava a projeção (bug que existia quando o próprio
 * SimulatorController fazia esse ajuste em PHP, depois da procedure já ter
 * rodado sobre 100% da contribuição).
 */
class SimulacaoPrevidenciaFunctionTest extends TestCase
{
    private function simulate(string $birthDate, float $monthly, bool $incluirMorte = true, bool $incluirInvalidez = true): array
    {
        $connection = ConnectionManager::get('test');

        return $connection->execute(
            'SELECT * FROM simulacao_previdencia(:date, :value, :incluirMorte, :incluirInvalidez)',
            [
                'date' => $birthDate,
                'value' => $monthly,
                'incluirMorte' => $incluirMorte,
                'incluirInvalidez' => $incluirInvalidez,
            ],
            [
                'incluirMorte' => 'boolean',
                'incluirInvalidez' => 'boolean',
            ]
        )->fetchAll('assoc');
    }

    public function testDefaultsKeepBothRisksAsBefore(): void
    {
        $rows = $this->simulate('1990-01-01', 1000.0);

        $this->assertEqualsWithDelta(160.0, (float)$rows[0]['contribuicao_morte'], 0.01);
        $this->assertEqualsWithDelta(100.0, (float)$rows[0]['contribuicao_invalidez'], 0.01);
        $this->assertEqualsWithDelta(740.0, (float)$rows[0]['contribuicao_aposentadoria'], 0.01);
    }

    public function testRemovingSurvivorsPensionShiftsItsShareToRetirement(): void
    {
        $rows = $this->simulate('1990-01-01', 1000.0, incluirMorte: false);

        $this->assertEqualsWithDelta(0.0, (float)$rows[0]['contribuicao_morte'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float)$rows[0]['cobertura_morte'], 0.01);
        // 16% de morte + 10% de invalidez continua ativa = 90% vai para a
        // previdência (contra 74% com os dois riscos).
        $this->assertEqualsWithDelta(900.0, (float)$rows[0]['contribuicao_aposentadoria'], 0.01);
    }

    public function testRemovingBothRisksSendsFullContributionToRetirement(): void
    {
        $withRisks = $this->simulate('1990-01-01', 1000.0);
        $withoutRisks = $this->simulate('1990-01-01', 1000.0, incluirMorte: false, incluirInvalidez: false);

        $this->assertEqualsWithDelta(0.0, (float)$withoutRisks[0]['contribuicao_morte'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float)$withoutRisks[0]['contribuicao_invalidez'], 0.01);
        $this->assertEqualsWithDelta(1000.0, (float)$withoutRisks[0]['contribuicao_aposentadoria'], 0.01);

        // A regressão que esta migration corrige: saldo_acumulado tem que
        // crescer por ter mais dinheiro entrando na previdência, não ficar
        // igual (o que aconteceria se o risco fosse zerado só depois de
        // saldo_acumulado já calculado sobre os 74% originais).
        $this->assertGreaterThan(
            (float)$withRisks[0]['saldo_acumulado'],
            (float)$withoutRisks[0]['saldo_acumulado']
        );
        $this->assertGreaterThan(
            (float)$withRisks[0]['beneficio_mensal'],
            (float)$withoutRisks[0]['beneficio_mensal']
        );
    }
}
