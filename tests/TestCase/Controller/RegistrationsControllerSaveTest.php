<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Cobre RegistrationsController::save() na etapa de idade e valores: o
 * corretor trava na primeira validação bem-sucedida, e quais riscos a
 * adesão tem é recalculado a partir dele — nunca aceito das flags que o
 * front-end mandou. Não cobre a etapa final (dados de pagamento), que
 * dispara PDF e Clicksign.
 */
class RegistrationsControllerSaveTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Brokers',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableCsrfToken();
    }

    /**
     * configRequest() consome os headers a cada chamada de post()/get(), por
     * isso precisa ser refeito antes de toda requisição desta suíte.
     */
    private function postAjax(string $url, array $data): void
    {
        $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);
        $this->post($url, $data);
    }

    private function createInitialData(string $storageUuid): int
    {
        $this->postAjax('/registrations/save', [
            'storageUuid' => $storageUuid,
            'initialData' => ['name' => 'Fulano de Tal', 'phone' => '48999999999'],
        ]);

        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertTrue($result['success']);

        return (int)$result['initialDataId'];
    }

    public function testValidBrokerLocksInAndRemovesSelectedRisk(): void
    {
        $initialDataId = $this->createInitialData('uuid-broker-valid');

        $this->postAjax('/registrations/save', [
            'storageUuid' => 'uuid-broker-valid',
            'initialDataId' => $initialDataId,
            'plans' => [
                'benefitEntryAge' => 65,
                'monthly_retirement_contribution' => '740,00',
                'monthly_survivors_pension_contribution' => '0,00',
                'survivors_pension_insured_capital' => '0,00',
                'monthly_disability_retirement_contribution' => '100,00',
                'disability_retirement_insured_capital' => '1000,00',
                'brokerCode' => 'joao2026',
                'removeSurvivorsPension' => '1',
            ],
        ]);

        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($result['success']);

        $adhesion = TableRegistry::getTableLocator()->get('AdhesionInitialDatas')
            ->get($initialDataId, contain: ['AdhesionPlans']);

        $this->assertSame('JOAO2026', $adhesion->broker_code);
        $this->assertSame('João da Silva', $adhesion->broker_name);
        $this->assertFalse($adhesion->adhesion_plan->has_survivors_pension);
        $this->assertTrue($adhesion->adhesion_plan->has_disability_retirement);
    }

    public function testForgedRiskFlagsWithoutBrokerAreIgnored(): void
    {
        $initialDataId = $this->createInitialData('uuid-no-broker');

        $this->postAjax('/registrations/save', [
            'storageUuid' => 'uuid-no-broker',
            'initialDataId' => $initialDataId,
            'plans' => [
                'benefitEntryAge' => 65,
                'monthly_retirement_contribution' => '740,00',
                'monthly_survivors_pension_contribution' => '160,00',
                'survivors_pension_insured_capital' => '1000,00',
                'monthly_disability_retirement_contribution' => '100,00',
                'disability_retirement_insured_capital' => '1000,00',
                // Nenhum brokerCode enviado: as duas flags têm que ser
                // completamente ignoradas pelo servidor.
                'removeSurvivorsPension' => '1',
                'removeDisabilityRetirement' => '1',
            ],
        ]);

        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($result['success']);

        $adhesion = TableRegistry::getTableLocator()->get('AdhesionInitialDatas')
            ->get($initialDataId, contain: ['AdhesionPlans']);

        $this->assertNull($adhesion->broker_id);
        $this->assertTrue($adhesion->adhesion_plan->has_survivors_pension);
        $this->assertTrue($adhesion->adhesion_plan->has_disability_retirement);
    }

    public function testInactiveBrokerCodeIsNeverLockedIn(): void
    {
        $initialDataId = $this->createInitialData('uuid-inactive-broker');

        $this->postAjax('/registrations/save', [
            'storageUuid' => 'uuid-inactive-broker',
            'initialDataId' => $initialDataId,
            'plans' => [
                'benefitEntryAge' => 65,
                'monthly_retirement_contribution' => '740,00',
                'monthly_survivors_pension_contribution' => '160,00',
                'survivors_pension_insured_capital' => '1000,00',
                'monthly_disability_retirement_contribution' => '100,00',
                'disability_retirement_insured_capital' => '1000,00',
                'brokerCode' => 'MARIAINATIVA',
                'removeSurvivorsPension' => '1',
            ],
        ]);

        $this->assertResponseOk();

        $adhesion = TableRegistry::getTableLocator()->get('AdhesionInitialDatas')
            ->get($initialDataId, contain: ['AdhesionPlans']);

        $this->assertNull($adhesion->broker_id);
        $this->assertTrue($adhesion->adhesion_plan->has_survivors_pension);
    }
}
