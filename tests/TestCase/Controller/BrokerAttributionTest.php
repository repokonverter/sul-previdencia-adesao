<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * O Corretor no fluxo público: a pergunta dos Dados Iniciais, o link de
 * divulgação (?corretor=) e a regra de que, uma vez gravado, ele só muda
 * pelo admin.
 */
class BrokerAttributionTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.PlanParameters',
        'app.Partners',
        'app.PromotionalCodes',
        'app.Brokers',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableCsrfToken();
    }

    private function saveInitialData(array $initialData, array $extra = []): array
    {
        $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);
        $this->post('/registrations/save', $extra + [
            'initialData' => $initialData + ['name' => 'Fulano de Tal', 'phone' => '48999999999'],
        ]);

        $this->assertResponseOk();

        return json_decode((string)$this->_response->getBody(), true);
    }

    private function brokerOf(int $initialDataId): ?int
    {
        return TableRegistry::getTableLocator()->get('AdhesionInitialDatas')->get($initialDataId)->broker_id;
    }

    private function openSimulator(array $query = []): void
    {
        // index() lê $_GET direto, que o IntegrationTestTrait não preenche.
        $_GET = ['date' => '1990-03-15', 'value' => '500'] + $query;
        try {
            $this->get('/simulator?' . http_build_query($_GET));
        } finally {
            $_GET = [];
        }

        $this->assertResponseOk();
    }

    public function testSelectedBrokerIsSaved(): void
    {
        $result = $this->saveInitialData(['brokerId' => '1']);

        $this->assertSame(1, $this->brokerOf((int)$result['initialDataId']));
    }

    public function testInactiveBrokerIsIgnored(): void
    {
        $result = $this->saveInitialData(['brokerId' => '2']);

        $this->assertNull($this->brokerOf((int)$result['initialDataId']));
    }

    /**
     * Voltar aos Dados Iniciais e responder "Não", ou escolher outro, não
     * desfaz a atribuição: quem troca é o admin.
     */
    public function testOnceSavedTheBrokerDoesNotChangeFromTheForm(): void
    {
        $first = $this->saveInitialData(['brokerId' => '1']);
        $id = (int)$first['initialDataId'];

        $brokers = TableRegistry::getTableLocator()->get('Brokers');
        $brokers->getConnection()
            ->execute("SELECT setval(pg_get_serial_sequence('brokers', 'id'), (SELECT MAX(id) FROM brokers))");
        $other = $brokers->saveOrFail($brokers->newEntity(['partner_id' => 1, 'name' => 'Outro', 'active' => true]));

        $this->saveInitialData(['brokerId' => (string)$other->id], [
            'initialDataId' => $id,
            'storageUuid' => $first['storageUuid'],
        ]);
        $this->assertSame(1, $this->brokerOf($id));

        $this->saveInitialData([], [
            'initialDataId' => $id,
            'storageUuid' => $first['storageUuid'],
        ]);
        $this->assertSame(1, $this->brokerOf($id));
    }

    public function testQuestionListsOnlySelectableBrokersAsPartnerDashName(): void
    {
        $this->openSimulator();

        $this->assertResponseContains('Foi atendido por algum corretor?');
        $this->assertResponseContains('Corretora Parceira - João da Silva');
        $this->assertResponseNotContains('Maria Inativa');
        $this->assertNull($this->viewVariable('preselectedBrokerId'));
    }

    public function testQuestionIsHiddenWithoutSelectableBrokers(): void
    {
        TableRegistry::getTableLocator()->get('Brokers')->updateAll(['active' => false], []);

        $this->openSimulator();

        $this->assertResponseNotContains('Foi atendido por algum corretor?');
    }

    public function testBrokerLinkPreselectsTheBroker(): void
    {
        $this->openSimulator(['corretor' => 'joaoabc23456']);

        $this->assertSame(1, $this->viewVariable('preselectedBrokerId'));
        $this->assertResponseContains('id="brokerAnswerYes" value="yes" checked');
        $this->assertResponseRegExp('/value="1"[^>]*selected>Corretora Parceira - João da Silva/');
    }

    public function testUnusableBrokerLinkIsSilentlyIgnored(): void
    {
        foreach (['mariabc23456', 'naoexiste'] as $token) {
            $this->openSimulator(['corretor' => $token]);

            $this->assertNull($this->viewVariable('preselectedBrokerId'));
            $this->assertResponseContains('id="brokerAnswerNo" value="no" checked');
        }
    }

    /**
     * Sem código e sem vínculo, a marca da adesão é a do parceiro do
     * corretor -- só se o parceiro ligou essa opção; o código promocional e
     * o vínculo, quando há, continuam na frente e independem dela.
     */
    public function testBrandPartnerFallsBackToTheBrokersPartnerOnlyWhenItShowsPartnership(): void
    {
        $locator = TableRegistry::getTableLocator();
        $adhesions = $locator->get('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity(['name' => 'Fulano', 'storage_uuid' => 'x', 'benefit_plan' => 'plenoprev', 'broker_id' => 1]);
        $adhesions->saveOrFail($adhesion);
        $contain = ['PromotionalCodes.Partners', 'AssociationPartners', 'Brokers.Partners'];

        $loaded = $adhesions->get($adhesion->id, contain: $contain);
        $this->assertNull($loaded->brandPartner(), 'Desligado por padrão: o corretor não leva a marca.');

        $loaded->promotional_code_entity = $locator->get('PromotionalCodes')->get(1, contain: ['Partners']);
        $this->assertSame(1, $loaded->brandPartner()?->id, 'O código promocional não depende da opção.');

        $locator->get('Partners')->updateAll(['brokers_show_partnership' => true], ['id' => 1]);
        $loaded = $adhesions->get($adhesion->id, contain: $contain);
        $this->assertSame(1, $loaded->brandPartner()?->id);

        $loaded->association_partner = $locator->get('Partners')->get(2);
        $this->assertSame(2, $loaded->brandPartner()?->id);
    }

    public function testBrokerOptionCarriesThePartnersPartnershipChoice(): void
    {
        $this->openSimulator();
        $this->assertResponseContains('data-show-partnership="0"');

        TableRegistry::getTableLocator()->get('Partners')->updateAll(['brokers_show_partnership' => true], ['id' => 1]);

        $this->openSimulator();
        $this->assertResponseContains('data-show-partnership="1"');
    }
}
