<?php

declare(strict_types=1);

namespace App\Test\TestCase\Services;

use App\Services\PlanSimulator;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;

/**
 * Cobre a regra que saiu do PL/pgSQL: quanto vai para cada risco.
 *
 * As taxas vêm de plan_parameters (16% e 10% na linha que a migration semeia),
 * não mais de constantes cravadas na procedure.
 */
class PlanSimulatorTest extends TestCase
{
    protected array $fixtures = [
        'app.PlanParameters',
    ];

    private PlanSimulator $simulator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->simulator = new PlanSimulator(
            ConnectionManager::get('test'),
            TableRegistry::getTableLocator()->get('PlanParameters')->current()
        );
    }

    private function yearsAgo(int $years): string
    {
        return (new DateTimeImmutable("-$years years"))->format('Y-m-d');
    }

    public function testUsesTheConfiguredPercentages(): void
    {
        [$survivorsPension, $disabilityRetirement] = $this->simulator->riskContributions($this->yearsAgo(35), 1000.0);

        $this->assertSame(160.0, $survivorsPension);
        $this->assertSame(100.0, $disabilityRetirement);
    }

    public function testRemovedRiskGetsNothing(): void
    {
        [$survivorsPension, $disabilityRetirement] = $this->simulator->riskContributions(
            $this->yearsAgo(35),
            1000.0,
            hasSurvivorsPension: false
        );

        $this->assertSame(0.0, $survivorsPension);
        $this->assertSame(100.0, $disabilityRetirement);
    }

    /**
     * Abaixo da idade mínima não há risco, mesmo que o chamador peça os dois —
     * a regra não é uma escolha, e vive num lugar por onde todos passam.
     */
    public function testUnderMinimumAgeHasNoRiskAtAll(): void
    {
        $birthDate = $this->yearsAgo(PlanSimulator::MINIMUM_RISK_AGE - 1);

        $this->assertSame([0.0, 0.0], $this->simulator->riskContributions($birthDate, 1000.0));
        $this->assertSame([false, false], $this->simulator->effectiveRisks($birthDate));
    }

    public function testAtMinimumAgeRisksExist(): void
    {
        $birthDate = $this->yearsAgo(PlanSimulator::MINIMUM_RISK_AGE);

        $this->assertSame([true, true], $this->simulator->effectiveRisks($birthDate));
    }

    /**
     * Contribuição é dinheiro: sem arredondar para centavos, os três valores
     * exibidos não somavam o total. Com R$ 333,33 a fórmula dá 53,3328 e
     * 33,333, que a tela mostrava como 53,33 + 33,33 + 246,66 = R$ 333,32.
     */
    public function testRiskContributionsAreRoundedAndStillSumToTheTotal(): void
    {
        $total = 333.33;
        [$survivorsPension, $disabilityRetirement] = $this->simulator->riskContributions($this->yearsAgo(35), $total);

        $this->assertSame(53.33, $survivorsPension);
        $this->assertSame(33.33, $disabilityRetirement);

        $rows = $this->simulator->simulate($this->yearsAgo(35), $total);
        $retirement = (float)$rows[0]['contribuicao_aposentadoria'];

        $this->assertEqualsWithDelta($total, $survivorsPension + $disabilityRetirement + $retirement, 0.001);
    }

    public function testSimulateFeedsTheProcedureTheAmountsItDecided(): void
    {
        $rows = $this->simulator->simulate($this->yearsAgo(35), 1000.0, hasDisabilityRetirement: false);

        $this->assertEqualsWithDelta(160.0, (float)$rows[0]['contribuicao_morte'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float)$rows[0]['contribuicao_invalidez'], 0.01);
        $this->assertEqualsWithDelta(840.0, (float)$rows[0]['contribuicao_aposentadoria'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float)$rows[0]['cobertura_invalidez'], 0.01);
    }

    public function testBenefitEntryAge(): void
    {
        $this->assertSame(65, $this->simulator->benefitEntryAge($this->yearsAgo(30)));
        $this->assertSame(65, $this->simulator->benefitEntryAge($this->yearsAgo(55)));
        $this->assertSame(70, $this->simulator->benefitEntryAge($this->yearsAgo(60)));
    }
}
