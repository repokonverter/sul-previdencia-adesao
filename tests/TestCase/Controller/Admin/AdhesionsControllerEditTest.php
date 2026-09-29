<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Services\AdhesionAuditor;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * Cobre a trilha de auditoria da edição de adesão no admin: quem mexeu, o que
 * mudou, e a marca de que o plano passou a ter valor ajustado à mão.
 */
class AdhesionsControllerEditTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.PlanParameters',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableCsrfToken();
        $this->session([
            'Auth' => TableRegistry::getTableLocator()->get('Users')->get(1),
        ]);
    }

    private function table(string $name): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($name);
    }

    /**
     * @return array{0: int, 1: int} [adhesionId, planId]
     */
    private function createAdhesion(): array
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'email' => 'fulano@exemplo.test',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);

        // O formulário do admin desreferencia as associações sem guarda (ex.:
        // adhesion_personal_data->birth_date), então a adesão de teste precisa
        // tê-las para a tela renderizar sem aviso.
        foreach ([
            'AdhesionPersonalDatas' => ['plan_for' => 'Titular', 'name' => 'Fulano de Tal', 'cpf' => '123.456.789-09', 'birth_date' => '1985-03-10', 'nacionality' => 'Brasileira'],
            'AdhesionDocuments' => ['type' => 'RG', 'document_number' => '1234567', 'issue_date' => '2010-01-01', 'issuer' => 'SSP'],
        ] as $table => $data) {
            $this->table($table)->saveOrFail(
                $this->table($table)->newEntity($data + ['adhesion_initial_data_id' => $adhesion->id])
            );
        }

        $plans = $this->table('AdhesionPlans');
        $plan = $plans->newEntity([
            'adhesion_initial_data_id' => $adhesion->id,
            'benefit_entry_age' => 65,
            'monthly_retirement_contribution' => '740.00',
            'monthly_survivors_pension_contribution' => '160.00',
            'survivors_pension_insured_capital' => '1000.00',
            'monthly_disability_retirement_contribution' => '100.00',
            'disability_retirement_insured_capital' => '1000.00',
        ]);
        $plans->saveOrFail($plan);

        return [(int)$adhesion->id, (int)$plan->id];
    }

    private function auditsFor(int $adhesionId): array
    {
        return $this->table('AdhesionAudits')->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])
            ->orderBy(['created' => 'DESC', 'id' => 'DESC'])
            ->toArray();
    }

    public function testEditingThePlanRecordsWhoChangedWhatAndMarksTheOverride(): void
    {
        [$adhesionId, $planId] = $this->createAdhesion();

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'name' => 'Fulano de Tal',
            'adhesion_plan' => [
                'id' => $planId,
                'benefit_entry_age' => 65,
                'monthly_retirement_contribution' => '790.00',
                'monthly_survivors_pension_contribution' => '110.00',
                'survivors_pension_insured_capital' => '1000.00',
                'monthly_disability_retirement_contribution' => '100.00',
                'disability_retirement_insured_capital' => '1000.00',
            ],
        ]);

        $this->assertRedirect(['action' => 'view', $adhesionId]);

        $audits = $this->auditsFor($adhesionId);
        $this->assertCount(1, $audits);

        $changes = $audits[0]->changeList();
        $this->assertSame(
            ['160.00', '110.00'],
            $changes['adhesion_plan.monthly_survivors_pension_contribution']
        );
        $this->assertSame(1, $audits[0]->user_id);
        $this->assertNotEmpty($audits[0]->user_name);

        $plan = $this->table('AdhesionPlans')->get($planId);
        $this->assertTrue($plan->admin_overridden);
        $this->assertSame(1, $plan->admin_overridden_by_user_id);
        $this->assertNotNull($plan->admin_overridden_at);
    }

    /**
     * Editar um dado cadastral não é ajuste de valor: a adesão fica auditada,
     * mas o plano não passa a contar como negociado à mão — senão o passo do
     * Plano travaria para o cliente por causa de uma correção de telefone.
     */
    public function testEditingOutsideThePlanDoesNotMarkTheOverride(): void
    {
        [$adhesionId, $planId] = $this->createAdhesion();

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'name' => 'Beltrano de Tal',
            'phone' => '48988888888',
        ]);

        $this->assertRedirect(['action' => 'view', $adhesionId]);

        $changes = $this->auditsFor($adhesionId)[0]->changeList();
        $this->assertSame(['Fulano de Tal', 'Beltrano de Tal'], $changes['name']);
        $this->assertArrayHasKey('phone', $changes);

        $this->assertFalse($this->table('AdhesionPlans')->get($planId)->admin_overridden);
    }

    public function testSavingWithoutChangingAnythingRecordsNothing(): void
    {
        [$adhesionId] = $this->createAdhesion();

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'name' => 'Fulano de Tal',
            'email' => 'fulano@exemplo.test',
            'phone' => '48999999999',
        ]);

        $this->assertRedirect(['action' => 'view', $adhesionId]);
        $this->assertSame([], $this->auditsFor($adhesionId));
    }

    public function testHistoryTabShowsTheChange(): void
    {
        [$adhesionId, $planId] = $this->createAdhesion();

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'adhesion_plan' => [
                'id' => $planId,
                'monthly_survivors_pension_contribution' => '110.00',
            ],
        ]);

        $this->get("/admin/adhesions/view/$adhesionId?tab=audits");

        $this->assertResponseOk();
        $this->assertResponseContains('Histórico de alterações');
        $this->assertResponseContains('Plano › Monthly survivors pension contribution');
        $this->assertResponseContains('ajustados manualmente');
    }

    public function testTheFormOffersRiskSwitches(): void
    {
        [$adhesionId] = $this->createAdhesion();

        $this->get("/admin/adhesions/edit/$adhesionId");

        $this->assertResponseOk();
        $this->assertResponseContains('Riscos contratados');

        // O checkbox precisa vir acompanhado do campo oculto: sem ele,
        // desmarcar não posta nada, a chave some do payload e o patch deixa o
        // risco exatamente como estava -- desmarcar não faria nada.
        $this->assertResponseContains('name="adhesion_plan[has_survivors_pension]" value="0"');
    }

    public function testUncheckingARiskSwitchRemovesIt(): void
    {
        [$adhesionId, $planId] = $this->createAdhesion();

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'adhesion_plan' => [
                'id' => $planId,
                // O que o navegador posta quando a chave está desmarcada.
                'has_survivors_pension' => '0',
                'has_disability_retirement' => '1',
                'monthly_survivors_pension_contribution' => '0.00',
            ],
        ]);

        $this->assertRedirect(['action' => 'view', $adhesionId]);

        $plan = $this->table('AdhesionPlans')->get($planId);

        $this->assertFalse($plan->has_survivors_pension);
        $this->assertTrue($plan->has_disability_retirement);

        $changes = $this->auditsFor($adhesionId)[0]->changeList();
        $this->assertArrayHasKey('adhesion_plan.has_survivors_pension', $changes);
    }

    public function testLoweringAContributionBelowTheFloorIsRefusedWithoutSaving(): void
    {
        [$adhesionId, $planId] = $this->createAdhesion();

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'adhesion_plan' => [
                'id' => $planId,
                'has_survivors_pension' => '1',
                'has_disability_retirement' => '1',
                'monthly_survivors_pension_contribution' => '5.00',
            ],
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('R$ 16,00');

        $plan = $this->table('AdhesionPlans')->get($planId);

        $this->assertEqualsWithDelta(160.0, (float)$plan->monthly_survivors_pension_contribution, 0.01);
        $this->assertFalse($plan->admin_overridden);
    }

    private function markPaid(int $adhesionId): void
    {
        $pix = $this->table('PixTransactions');
        $pix->saveOrFail($pix->newEntity([
            'adhesion_initial_data_id' => $adhesionId,
            'txid' => 'tx' . $adhesionId,
            'attempt' => 1,
            'paid' => true,
        ]));
    }

    /**
     * Com o dinheiro já recebido sobre os números antigos, mudar valor ou
     * risco deixa de ser edição de cadastro e vira evento contábil.
     */
    public function testAPaidAdhesionKeepsItsEconomicFields(): void
    {
        [$adhesionId, $planId] = $this->createAdhesion();
        $this->markPaid($adhesionId);

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'name' => 'Beltrano de Tal',
            'adhesion_plan' => [
                'id' => $planId,
                'monthly_survivors_pension_contribution' => '110.00',
                'has_survivors_pension' => '0',
            ],
        ]);

        $plan = $this->table('AdhesionPlans')->get($planId);

        $this->assertEqualsWithDelta(160.0, (float)$plan->monthly_survivors_pension_contribution, 0.01);
        $this->assertTrue($plan->has_survivors_pension);

        // O que é cadastral continua passando: travar tudo transformaria uma
        // correção de telefone num impedimento.
        $this->assertSame('Beltrano de Tal', $this->table('AdhesionInitialDatas')->get($adhesionId)->name);
    }

    public function testThePaidLockIsAnnouncedOnTheForm(): void
    {
        [$adhesionId] = $this->createAdhesion();
        $this->markPaid($adhesionId);

        $this->get("/admin/adhesions/edit/$adhesionId");

        $this->assertResponseOk();
        $this->assertResponseContains('Adesão paga');
    }

    /**
     * O que o proponente tem para assinar é anterior ao que está gravado: a
     * tela precisa dizer isso, porque nada reenvia sozinho — quem decide
     * incomodar o cliente é o admin.
     */
    public function testTheScreenWarnsWhenTheDocumentsPredateTheChange(): void
    {
        [$adhesionId, $planId] = $this->createAdhesion();

        $clicksign = $this->table('ClicksignDatas');
        $clicksign->saveOrFail($clicksign->newEntity([
            'adhesion_initial_data_id' => $adhesionId,
            'envelope_id' => 'env-antigo',
            'attempt' => 1,
            'status' => 'sent',
            'created' => new \Cake\I18n\DateTime('-1 day'),
        ]));

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'adhesion_plan' => ['id' => $planId, 'monthly_survivors_pension_contribution' => '110.00'],
        ]);

        $this->get("/admin/adhesions/view/$adhesionId?tab=integrationLogs");

        $this->assertResponseOk();
        $this->assertResponseContains('Documentos desatualizados');
        $this->assertResponseContains('Regerar documentos');
        $this->assertResponseContains('env-antigo');
    }

    public function testNoWarningWhenTheDocumentsCameAfterTheChange(): void
    {
        [$adhesionId, $planId] = $this->createAdhesion();

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'adhesion_plan' => ['id' => $planId, 'monthly_survivors_pension_contribution' => '110.00'],
        ]);

        $clicksign = $this->table('ClicksignDatas');
        $clicksign->saveOrFail($clicksign->newEntity([
            'adhesion_initial_data_id' => $adhesionId,
            'envelope_id' => 'env-novo',
            'attempt' => 1,
            'status' => 'sent',
            'created' => new \Cake\I18n\DateTime('+1 minute'),
        ]));

        $this->get("/admin/adhesions/view/$adhesionId?tab=integrationLogs");

        $this->assertResponseOk();
        $this->assertResponseNotContains('Documentos desatualizados');
    }

    public function testRegeneratingIsRefusedBeforeTheAdhesionIsFinalised(): void
    {
        [$adhesionId] = $this->createAdhesion();

        $this->post("/admin/adhesions/regenerate-documents/$adhesionId");

        $this->assertRedirect();
        $this->assertSame(0, $this->table('ClicksignDatas')->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])->count());
    }

    public function testSnapshotContainsEverythingTheEditFormCanTouch(): void
    {
        [$adhesionId] = $this->createAdhesion();

        $snapshot = AdhesionAuditor::snapshot(
            $this->table('AdhesionInitialDatas')->get($adhesionId, contain: AdhesionAuditor::CONTAINS)
        );

        // Os campos economicamente sensíveis precisam estar no retrato, senão
        // uma alteração neles passaria despercebida pela trilha.
        $this->assertArrayHasKey('adhesion_plan.monthly_survivors_pension_contribution', $snapshot);
        $this->assertArrayHasKey('adhesion_plan.has_survivors_pension', $snapshot);
        $this->assertArrayHasKey('adhesion_dependents.total', $snapshot);
    }
}
