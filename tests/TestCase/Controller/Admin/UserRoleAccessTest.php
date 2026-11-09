<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Entity\User;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use DateTimeImmutable;

/**
 * Perfil `user` do admin: usa a Simulação por completo e só visualiza
 * Adesões, Parceiros/Vínculos e Parâmetros do Plano. O resto é do admin.
 */
class UserRoleAccessTest extends TestCase
{
    use IntegrationTestTrait;

    private const DENIED = 'Seu perfil não tem permissão para esta ação.';

    protected array $fixtures = [
        'app.Users',
        'app.PlanParameters',
        'app.Partners',
        'app.PromotionalCodes',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->loginAs(2);
    }

    private function loginAs(int $id): void
    {
        $this->session([
            'Auth' => TableRegistry::getTableLocator()->get('Users')->get($id),
        ]);
    }

    private function table(string $name): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($name);
    }

    private function createAdhesion(): int
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'email' => 'fulano@exemplo.test',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);

        return (int)$adhesion->id;
    }

    private function assertDenied(): void
    {
        $this->assertRedirect('/admin/simulations');
        $this->assertFlashMessage(self::DENIED);
    }

    public function testUserUsesTheSimulation(): void
    {
        $this->get('/admin/simulations');
        $this->assertResponseOk();

        $this->post('/admin/simulations/pdf', [
            'name' => 'Ciclana de Souza',
            'birth_date' => (new DateTimeImmutable('-35 years'))->format('Y-m-d'),
            'benefit_entry_age' => '65',
            'has_survivors_pension' => '0',
            'has_disability_retirement' => '0',
            'monthly_retirement_contribution' => '740,00',
        ]);

        $this->assertResponseOk();
        $this->assertContentType('application/pdf');
    }

    public function testMenuHidesTheAdminOnlyScreens(): void
    {
        $this->get('/admin');
        $this->assertResponseOk();
        $this->assertResponseContains('href="/admin/simulations"');
        $this->assertResponseNotContains('href="/admin/users"');
        $this->assertResponseNotContains('href="/admin/clicksign-webhooks"');

        $this->loginAs(1);
        $this->get('/admin');
        $this->assertResponseContains('href="/admin/users"');
        $this->assertResponseContains('href="/admin/clicksign-webhooks"');
    }

    public function testUserSeesAdhesionsWithoutTheEditingActions(): void
    {
        $id = $this->createAdhesion();

        $this->get('/admin/adhesions');
        $this->assertResponseOk();
        $this->assertResponseContains("/admin/adhesions/view/$id");
        $this->assertResponseNotContains("/admin/adhesions/edit/$id");
        $this->assertResponseNotContains("/admin/adhesions/delete/$id");
        $this->assertResponseNotContains('Nova Adesão');
        $this->assertResponseNotContains("resumeLinkModal-$id");

        $this->get("/admin/adhesions/view/$id");
        $this->assertResponseOk();
        $this->assertResponseNotContains("/admin/adhesions/edit/$id");
        $this->assertResponseNotContains("resumeLinkModal-$id");

        $this->get('/admin/adhesions/deletions');
        $this->assertResponseOk();
    }

    public function testAdminStillSeesTheEditingActions(): void
    {
        $id = $this->createAdhesion();
        $this->loginAs(1);

        $this->get("/admin/adhesions/view/$id");

        $this->assertResponseOk();
        $this->assertResponseContains("/admin/adhesions/edit/$id");
        $this->assertResponseContains("resumeLinkModal-$id");
    }

    public function testUserCannotChangeAdhesions(): void
    {
        $id = $this->createAdhesion();

        $this->get('/admin/adhesions/add');
        $this->assertDenied();

        $this->get("/admin/adhesions/edit/$id");
        $this->assertDenied();

        $this->post("/admin/adhesions/edit/$id", ['name' => 'Beltrano de Tal']);
        $this->assertDenied();
        $this->assertSame('Fulano de Tal', $this->table('AdhesionInitialDatas')->get($id)->name);

        $this->post("/admin/adhesions/delete/$id");
        $this->assertDenied();
        $this->assertTrue($this->table('AdhesionInitialDatas')->exists(['id' => $id]));

        foreach (['regenerate-documents', 'reopen-proposal', 'issue-resume-link', 'send-resume-link', 'revoke-resume-link'] as $action) {
            $this->post("/admin/adhesions/$action/$id");
            $this->assertDenied();
        }
        $this->assertNull($this->table('AdhesionInitialDatas')->get($id)->resume_token);
    }

    /**
     * As consultas só perguntam à integração o que ela já decidiu; sem
     * cobrança/envelope gravados, elas nem chegam a sair daqui.
     */
    public function testUserCanCheckPaymentAndSignatureStatus(): void
    {
        $id = $this->createAdhesion();

        $this->post("/admin/adhesions/check-pix-payment/$id");
        $this->assertRedirectContains("/admin/adhesions/view/$id");
        $this->assertFlashMessage('Nenhuma cobrança Pix foi gerada para esta adesão ainda.');

        $this->post("/admin/adhesions/check-clicksign-status/$id");
        $this->assertRedirectContains("/admin/adhesions/view/$id");
        $this->assertFlashMessage('Nenhum envelope foi enviado para esta adesão ainda.');
    }

    public function testUserSeesPartnersWithoutTheEditingActions(): void
    {
        $this->get('/admin/partners');
        $this->assertResponseOk();
        $this->assertResponseContains('/admin/partners/view/1');
        $this->assertResponseNotContains('/admin/partners/edit/1');
        $this->assertResponseNotContains('/admin/partners/add');

        $this->get('/admin/partners/view/1');
        $this->assertResponseOk();
        $this->assertResponseNotContains('/admin/partners/edit/1');
        $this->assertResponseNotContains('/admin/partners/add-code/1');

        // Vínculos herdam o controller e os templates de Parceiros. Só a
        // listagem aqui: Partners/view.php declara uma função global e não
        // pode ser renderizado duas vezes no mesmo processo.
        $this->get('/admin/associations');
        $this->assertResponseOk();
        $this->assertResponseContains('/admin/associations/view/2');
        $this->assertResponseNotContains('/admin/associations/edit/2');
    }

    public function testUserCannotChangePartners(): void
    {
        $this->get('/admin/partners/edit/1');
        $this->assertDenied();

        $this->post('/admin/partners/toggle/1');
        $this->assertDenied();
        $this->assertTrue($this->table('Partners')->get(1)->active);

        $this->post('/admin/associations/delete/2');
        $this->assertDenied();
        $this->assertTrue($this->table('Partners')->exists(['id' => 2]));
    }

    public function testUserSeesPlanParametersReadOnly(): void
    {
        $this->get('/admin/plan-parameters/edit');

        $this->assertResponseOk();
        $this->assertResponseContains('Somente leitura');
        $this->assertResponseContains('<fieldset disabled>');
        $this->assertResponseNotContains('>Salvar</button>');
    }

    public function testUserCannotSavePlanParameters(): void
    {
        $planParameters = $this->table('PlanParameters');
        $before = $planParameters->find()->orderBy(['id' => 'ASC'])->firstOrFail();

        $this->post('/admin/plan-parameters/edit', ['minimum_monthly_contribution' => '999,00']);

        $this->assertDenied();
        $after = $planParameters->get($before->id);
        $this->assertEquals($before->minimum_monthly_contribution, $after->minimum_monthly_contribution);
    }

    public function testAdminOnlyScreensAreDenied(): void
    {
        foreach (['/admin/users', '/admin/users/view/1', '/admin/users/add', '/admin/clicksign-webhooks', '/admin/pix-webhooks', '/admin/integration-logs'] as $url) {
            $this->get($url);
            $this->assertDenied();
        }
    }

    public function testAjaxRequestsGetAForbidden(): void
    {
        $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);

        $this->get('/admin/users');

        $this->assertResponseCode(403);
    }

    /**
     * O perfil vem do banco, não da sessão: quem foi rebaixado perde o acesso
     * na hora, e a sessão de antes da coluna `role` existir continua valendo.
     */
    public function testRoleComesFromTheDatabaseNotTheSession(): void
    {
        $demoted = new User(['id' => 2, 'name' => 'Usuário Consulta', 'role' => User::ROLE_ADMIN]);
        $this->session(['Auth' => $demoted]);

        $this->get('/admin/users');
        $this->assertDenied();

        $beforeTheColumn = new User(['id' => 1, 'name' => 'Lorem ipsum']);
        $this->session(['Auth' => $beforeTheColumn]);

        $this->get('/admin/users');
        $this->assertResponseOk();
    }

    public function testAUserRemovedFromTheDatabaseIsLoggedOut(): void
    {
        $this->session(['Auth' => new User(['id' => 999, 'name' => 'Ninguém'])]);

        $this->get('/admin/simulations');

        $this->assertRedirectContains('/admin/users/login');
    }

    public function testUserCanLogOut(): void
    {
        $this->get('/admin/users/logout');

        $this->assertRedirectContains('/admin/users/login');
    }
}
