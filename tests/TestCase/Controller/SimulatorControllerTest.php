<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Cobre a autoridade do servidor sobre a remoção de riscos
 * (SimulatorController::recalculate()): as flags removeSurvivorsPension /
 * removeDisabilityRetirement só têm efeito quando acompanhadas de um código
 * de corretor que de fato existe e está ativo — nunca aceitas isoladamente a
 * partir da URL, que é o único jeito de garantir que a etapa de saúde e a
 * declaração de risco no PDF não sumam para quem não tem corretor nenhum.
 */
class SimulatorControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Brokers',
        'app.PlanParameters',
    ];

    private function recalculate(array $query): array
    {
        $this->get('/simulator/recalculate?' . http_build_query($query));

        $this->assertResponseOk();

        return json_decode((string)$this->_response->getBody(), true);
    }

    public function testRemovalFlagsWithoutAnyBrokerCodeAreIgnored(): void
    {
        $result = $this->recalculate([
            'date' => '1990-01-01',
            'value' => '1000',
            'removeSurvivorsPension' => '1',
            'removeDisabilityRetirement' => '1',
        ]);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['hasSurvivorsPension']);
        $this->assertTrue($result['hasDisabilityRetirement']);
        $this->assertGreaterThan(0.0, $result['monthlySurvivorsPensionContribution']);
    }

    public function testRemovalFlagsWithForgedBrokerCodeAreIgnored(): void
    {
        $result = $this->recalculate([
            'date' => '1990-01-01',
            'value' => '1000',
            'brokerCode' => 'CODIGO-QUE-NAO-EXISTE',
            'removeSurvivorsPension' => '1',
        ]);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['hasSurvivorsPension']);
    }

    public function testRemovalFlagsWithValidActiveBrokerAreApplied(): void
    {
        $result = $this->recalculate([
            'date' => '1990-01-01',
            'value' => '1000',
            'brokerCode' => 'JOAO2026',
            'removeSurvivorsPension' => '1',
        ]);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['hasSurvivorsPension']);
        $this->assertTrue($result['hasDisabilityRetirement']);
        $this->assertEqualsWithDelta(0.0, (float)$result['monthlySurvivorsPensionContribution'], 0.01);
    }

    public function testRemovalFlagsWithInactiveBrokerAreIgnored(): void
    {
        $result = $this->recalculate([
            'date' => '1990-01-01',
            'value' => '1000',
            'brokerCode' => 'MARIAINATIVA',
            'removeSurvivorsPension' => '1',
            'removeDisabilityRetirement' => '1',
        ]);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['hasSurvivorsPension']);
        $this->assertTrue($result['hasDisabilityRetirement']);
    }

    public function testMinorUnderSixteenNeverHasRisksEvenWithValidBroker(): void
    {
        $tenYearsAgo = (new \DateTime('-10 years'))->format('Y-m-d');

        $result = $this->recalculate([
            'date' => $tenYearsAgo,
            'value' => '1000',
            'brokerCode' => 'JOAO2026',
        ]);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['hasSurvivorsPension']);
        $this->assertFalse($result['hasDisabilityRetirement']);
        $this->assertEqualsWithDelta(1000.0, (float)$result['monthlyRetirementContribution'], 0.01);
    }
}
