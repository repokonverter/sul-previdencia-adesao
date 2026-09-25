<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * Cobre a autoridade do servidor sobre quais riscos a adesão tem.
 *
 * A regra mudou: remover risco deixou de ser escolha de quem preenche o
 * formulário — com ou sem código de corretor — e passou a ser ato do admin
 * sobre uma adesão que já existe. Por isso recalculate() lê os riscos do banco
 * e ignora por completo o que vier na URL.
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

    /**
     * @return array{0: int, 1: string} [initialDataId, storageUuid]
     */
    private function createAdhesion(
        bool $hasSurvivorsPension,
        bool $hasDisabilityRetirement,
        bool $adminOverridden = false,
    ): array {
        $adhesions = TableRegistry::getTableLocator()->get('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);

        $plans = TableRegistry::getTableLocator()->get('AdhesionPlans');
        $plans->saveOrFail($plans->newEntity([
            'adhesion_initial_data_id' => $adhesion->id,
            'has_survivors_pension' => $hasSurvivorsPension,
            'has_disability_retirement' => $hasDisabilityRetirement,
            'admin_overridden' => $adminOverridden,
        ]));

        return [(int)$adhesion->id, (string)$adhesion->storage_uuid];
    }

    /**
     * Nem mesmo acompanhadas de um corretor válido e ativo: o campo saiu do
     * formulário, e aceitar a flag deixaria qualquer um tirar risco montando
     * a URL à mão.
     */
    public function testRemovalFlagsInTheQueryAreIgnored(): void
    {
        $result = $this->recalculate([
            'date' => '1990-01-01',
            'value' => '1000',
            'brokerCode' => 'JOAO2026',
            'removeSurvivorsPension' => '1',
            'removeDisabilityRetirement' => '1',
        ]);

        $this->assertTrue($result['hasSurvivorsPension']);
        $this->assertTrue($result['hasDisabilityRetirement']);
        $this->assertGreaterThan(0.0, $result['monthlySurvivorsPensionContribution']);
    }

    public function testWithoutAnIdentifiedAdhesionBothRisksExist(): void
    {
        $result = $this->recalculate(['date' => '1990-01-01', 'value' => '1000']);

        $this->assertTrue($result['hasSurvivorsPension']);
        $this->assertTrue($result['hasDisabilityRetirement']);
    }

    public function testRisksComeFromTheStoredAdhesion(): void
    {
        [$id, $uuid] = $this->createAdhesion(hasSurvivorsPension: false, hasDisabilityRetirement: true);

        $result = $this->recalculate([
            'date' => '1990-01-01',
            'value' => '1000',
            'initialDataId' => $id,
            'storageUuid' => $uuid,
        ]);

        $this->assertFalse($result['hasSurvivorsPension']);
        $this->assertTrue($result['hasDisabilityRetirement']);
        $this->assertEqualsWithDelta(0.0, (float)$result['monthlySurvivorsPensionContribution'], 0.01);
        // A fatia do risco removido vai para a aposentadoria.
        $this->assertEqualsWithDelta(900.0, (float)$result['monthlyRetirementContribution'], 0.01);
    }

    /**
     * O id é sequencial e vem do navegador; quem autoriza falar sobre a
     * adesão é o storage_uuid, do mesmo jeito que em save().
     */
    public function testAnotherAdhesionsIdWithoutItsUuidRevealsNothing(): void
    {
        [$id] = $this->createAdhesion(hasSurvivorsPension: false, hasDisabilityRetirement: false);

        foreach (['uuid-que-nao-e-o-dela', ''] as $uuid) {
            $result = $this->recalculate([
                'date' => '1990-01-01',
                'value' => '1000',
                'initialDataId' => $id,
                'storageUuid' => $uuid,
            ]);

            $this->assertTrue($result['hasSurvivorsPension']);
            $this->assertTrue($result['hasDisabilityRetirement']);
        }
    }

    /**
     * A tela precisa saber que os valores foram negociados para travar o
     * passo: sem isso, um clique em "Recalcular" rodaria a fórmula e apagaria
     * o ajuste combinado por telefone.
     */
    public function testAnAdjustedPlanComesBackLocked(): void
    {
        [$id, $uuid] = $this->createAdhesion(true, true, adminOverridden: true);

        $result = $this->recalculate([
            'date' => '1990-01-01',
            'value' => '1000',
            'initialDataId' => $id,
            'storageUuid' => $uuid,
        ]);

        $this->assertTrue($result['planLocked']);
    }

    public function testAFreshSimulationIsNeverLocked(): void
    {
        $result = $this->recalculate(['date' => '1990-01-01', 'value' => '1000']);

        $this->assertFalse($result['planLocked']);
    }

    public function testMinorUnderSixteenNeverHasRisks(): void
    {
        $tenYearsAgo = (new \DateTime('-10 years'))->format('Y-m-d');

        $result = $this->recalculate(['date' => $tenYearsAgo, 'value' => '1000']);

        $this->assertFalse($result['hasSurvivorsPension']);
        $this->assertFalse($result['hasDisabilityRetirement']);
        $this->assertEqualsWithDelta(1000.0, (float)$result['monthlyRetirementContribution'], 0.01);
    }
}
