<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * Corretores no admin: cadastro dentro do Parceiro, exclusão bloqueada
 * depois de usado, e a adesão encontrada e corrigida pelo corretor.
 */
class PartnerBrokersTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.PlanParameters',
        'app.Partners',
        'app.PromotionalCodes',
        'app.Brokers',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->loginAs(1);

        // A fixture grava ids fixos sem avançar a sequence; o próximo INSERT
        // tentaria o id 1.
        foreach (['partners', 'brokers'] as $table) {
            $this->table('Brokers')->getConnection()
                ->execute("SELECT setval(pg_get_serial_sequence('$table', 'id'), (SELECT MAX(id) FROM $table))");
        }
    }

    private function loginAs(int $id): void
    {
        $this->session([
            'Auth' => $this->table('Users')->get($id),
        ]);
    }

    private function table(string $name): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($name);
    }

    private function createAdhesion(array $data = []): int
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity($data + [
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'email' => 'fulano@exemplo.test',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);

        return (int)$adhesion->id;
    }

    public function testAdminAddsABrokerToAPartner(): void
    {
        $this->post('/admin/partners/add-broker/1', [
            'name' => 'Ana Corretora',
            'cpf' => '390.533.447-05',
            'active' => '1',
            'token' => 'forjado',
        ]);

        $this->assertRedirect(['controller' => 'Partners', 'action' => 'view', 1, 'prefix' => 'Admin']);

        $broker = $this->table('Brokers')->find()->where(['name' => 'Ana Corretora'])->firstOrFail();
        $this->assertSame(1, $broker->partner_id);
        $this->assertSame('39053344705', $broker->cpf);
        $this->assertNotSame('forjado', $broker->token);
    }

    public function testPartnerPageListsBrokersWithTheirLink(): void
    {
        $this->get('/admin/partners/view/1');

        $this->assertResponseOk();
        $this->assertResponseContains('João da Silva');
        $this->assertResponseContains('529.982.247-25');
        $this->assertResponseContains('/?corretor=joaoabc23456');
    }

    public function testAssociationsHaveNoBrokers(): void
    {
        $this->get('/admin/associations/add-broker/2');
        $this->assertResponseCode(404);

        $this->get('/admin/associations/view/2');
        $this->assertResponseOk();
        $this->assertResponseNotContains('Novo corretor');
    }

    public function testUserRoleCannotManageBrokers(): void
    {
        $this->loginAs(2);

        $this->post('/admin/partners/add-broker/1', ['name' => 'Tentativa', 'active' => '1']);

        $this->assertRedirect('/admin/simulations');
        $this->assertSame(0, $this->table('Brokers')->find()->where(['name' => 'Tentativa'])->count());
    }

    public function testUsedBrokerCannotBeDeletedOnlyDeactivated(): void
    {
        $this->createAdhesion(['broker_id' => 1]);

        $this->post('/admin/partners/delete-broker/1');
        $this->assertTrue($this->table('Brokers')->exists(['id' => 1]));
        $this->assertFlashMessageAt(0, 'Este corretor foi usado em 1 adesão(ões) e não pode ser excluído. Desative-o para impedir novos usos.');

        $this->post('/admin/partners/toggle-broker/1');
        $this->assertFalse($this->table('Brokers')->get(1)->active);
    }

    public function testAdminTogglesWhetherBrokersShowThePartnership(): void
    {
        $this->get('/admin/partners/view/1');
        $this->assertResponseContains('Mostrar &quot;em parceria com Corretora Parceira&quot;');
        $this->assertResponseContains('Desligado');

        $this->post('/admin/partners/toggle-broker-partnership/1');
        $this->assertRedirect(['controller' => 'Partners', 'action' => 'view', 1, 'prefix' => 'Admin']);
        $this->assertTrue($this->table('Partners')->get(1)->brokers_show_partnership);

        $this->post('/admin/partners/toggle-broker-partnership/1');
        $this->assertFalse($this->table('Partners')->get(1)->brokers_show_partnership);
    }

    public function testUserRoleSeesButCannotToggleThePartnership(): void
    {
        $this->loginAs(2);

        $this->get('/admin/partners/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Desligado');
        $this->assertResponseNotContains('toggle-broker-partnership');

        $this->post('/admin/partners/toggle-broker-partnership/1');
        $this->assertFalse($this->table('Partners')->get(1)->brokers_show_partnership);
    }

    public function testAssociationsHaveNoPartnershipSwitch(): void
    {
        $this->post('/admin/associations/toggle-broker-partnership/2');

        $this->assertResponseCode(404);
        $this->assertFalse($this->table('Partners')->get(2)->brokers_show_partnership);
    }

    public function testUnusedBrokerIsDeleted(): void
    {
        $this->post('/admin/partners/delete-broker/2');

        $this->assertFalse($this->table('Brokers')->exists(['id' => 2]));
    }

    public function testPartnerWithAUsedBrokerCannotBeDeleted(): void
    {
        // O código do fixture não foi usado: o que segura o parceiro é só o corretor.
        $this->createAdhesion(['broker_id' => 1]);

        $this->post('/admin/partners/delete/1');

        $this->assertTrue($this->table('Partners')->exists(['id' => 1]));
    }

    public function testPartnerSearchFindsByBrokerName(): void
    {
        $this->get('/admin/partners?q=joão');

        $this->assertResponseOk();
        $this->assertSame([1], collection($this->viewVariable('partners'))->extract('id')->toList());
    }

    /**
     * A adesão é do parceiro por código promocional ou por corretor.
     */
    public function testPartnerFilterFindsAdhesionsByCodeOrBroker(): void
    {
        $byBroker = $this->createAdhesion(['broker_id' => 1]);
        $byCode = $this->createAdhesion(['promotional_code_id' => 1, 'promotional_code' => 'PARCEIRO10']);
        $this->createAdhesion();

        $this->get('/admin/adhesions?partnerId=1');
        $ids = collection($this->viewVariable('adhesions'))->extract('id')->toList();
        sort($ids);
        $this->assertSame([$byBroker, $byCode], $ids);

        $this->get('/admin/adhesions?brokerId=1');
        $this->assertSame([$byBroker], collection($this->viewVariable('adhesions'))->extract('id')->toList());
    }

    public function testPartnerCountsIncludeAdhesionsByBroker(): void
    {
        $this->createAdhesion(['broker_id' => 1]);
        $this->createAdhesion(['broker_id' => 1, 'promotional_code_id' => 1, 'promotional_code' => 'PARCEIRO10']);

        $partner = $this->table('Partners')->find('withAdhesionCounts')->where(['Partners.id' => 1])->firstOrFail();

        $this->assertSame(2, (int)$partner->adhesions_started);
    }

    /**
     * O participante não troca o corretor depois de gravado; o admin troca,
     * e a troca fica no histórico com o nome, não com o id.
     */
    public function testAdminCorrectsTheBrokerAndTheHistoryShowsIt(): void
    {
        $id = $this->createAdhesion(['broker_id' => 2]);

        $this->post("/admin/adhesions/edit/$id", [
            'name' => 'Fulano de Tal',
            'email' => 'fulano@exemplo.test',
            'phone' => '48999999999',
            'broker_id' => '1',
        ]);

        $this->assertRedirect(['controller' => 'Adhesions', 'action' => 'view', $id, 'prefix' => 'Admin']);
        $this->assertSame(1, $this->table('AdhesionInitialDatas')->get($id)->broker_id);

        $audit = $this->table('AdhesionAudits')->find()->where(['adhesion_initial_data_id' => $id])->firstOrFail();
        $this->assertSame([2, 1], $audit->changeList()['broker_id']);

        $this->get("/admin/adhesions/view/$id?tab=audits");
        $this->assertResponseContains('Corretora Parceira - Maria Inativa');
        $this->assertResponseContains('Corretora Parceira - João da Silva');
    }

    public function testEditFormKeepsAnInactiveBrokerAlreadySaved(): void
    {
        $id = $this->createAdhesion(['broker_id' => 2]);

        $this->get("/admin/adhesions/edit/$id");

        $this->assertResponseOk();
        $this->assertSame(
            [1 => 'Corretora Parceira - João da Silva', 2 => 'Corretora Parceira - Maria Inativa (inativo)'],
            $this->viewVariable('brokerOptions')
        );
    }
}
