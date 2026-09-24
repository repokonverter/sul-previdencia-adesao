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

    /**
     * O storage_uuid nasce no servidor e volta na resposta: o navegador não
     * escolhe o seu, e um valor enviado na criação é ignorado.
     *
     * @return array{0: int, 1: string} [initialDataId, storageUuid]
     */
    private function createInitialData(): array
    {
        $this->postAjax('/registrations/save', [
            'initialData' => ['name' => 'Fulano de Tal', 'phone' => '48999999999'],
        ]);

        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['storageUuid']);

        return [(int)$result['initialDataId'], (string)$result['storageUuid']];
    }

    public function testValidBrokerLocksInAndRemovesSelectedRisk(): void
    {
        [$initialDataId, $storageUuid] = $this->createInitialData();

        $this->postAjax('/registrations/save', [
            'storageUuid' => $storageUuid,
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
        [$initialDataId, $storageUuid] = $this->createInitialData();

        $this->postAjax('/registrations/save', [
            'storageUuid' => $storageUuid,
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
        [$initialDataId, $storageUuid] = $this->createInitialData();

        $this->postAjax('/registrations/save', [
            'storageUuid' => $storageUuid,
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

    /**
     * O initialDataId é sequencial e viaja no POST. Quem autoriza a escrita
     * é o storage_uuid, conferido contra o gravado -- antes ele era apenas
     * regravado por cima, e bastava trocar o número para reescrever a adesão
     * de outra pessoa.
     */
    public function testWritingToAnotherAdhesionIsRejected(): void
    {
        [$victimId, $victimUuid] = $this->createInitialData();
        [, $attackerUuid] = $this->createInitialData();

        $this->assertNotSame($victimUuid, $attackerUuid, 'cada adesão recebe o seu próprio uuid');

        foreach ([['storageUuid' => $attackerUuid], []] as $credentials) {
            $this->postAjax('/registrations/save', $credentials + [
                'initialDataId' => $victimId,
                'initialData' => ['name' => 'Invadido', 'phone' => '48900000000'],
            ]);

            $result = json_decode((string)$this->_response->getBody(), true);

            $this->assertFalse($result['success']);
        }

        $victim = TableRegistry::getTableLocator()->get('AdhesionInitialDatas')->get($victimId);

        $this->assertSame('Fulano de Tal', $victim->name);
        $this->assertSame($victimUuid, $victim->storage_uuid);
    }

    public function testStorageUuidSentOnCreationIsIgnored(): void
    {
        $this->postAjax('/registrations/save', [
            'storageUuid' => 'escolhido-pelo-navegador',
            'initialData' => ['name' => 'Fulano de Tal', 'phone' => '48999999999'],
        ]);

        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertNotSame('escolhido-pelo-navegador', $result['storageUuid']);

        $adhesion = TableRegistry::getTableLocator()->get('AdhesionInitialDatas')->get($result['initialDataId']);

        $this->assertSame($result['storageUuid'], $adhesion->storage_uuid);
    }
}
