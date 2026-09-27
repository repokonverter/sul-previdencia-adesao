<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Entity\ClicksignData;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * O gating por assinatura, "Reabrir proposta", e os caminhos de recusa de
 * checkClicksignStatus()/downloadSignedDocument() que não tocam a Clicksign.
 *
 * O que de fato chama a Clicksign nesses dois últimos não está coberto aqui,
 * mesmo padrão já aceito para ClicksignEnvelopeSender: exigiria mocking de
 * Cake\Http\Client, fora do escopo combinado.
 */
class AdhesionsControllerSignatureTest extends TestCase
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

    private function createAdhesion(): int
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);

        $plans = $this->table('AdhesionPlans');
        $plans->saveOrFail($plans->newEntity([
            'adhesion_initial_data_id' => $adhesion->id,
            'monthly_survivors_pension_contribution' => '160.00',
        ]));

        return (int)$adhesion->id;
    }

    private function addSignedAttempt(int $adhesionId): ClicksignData
    {
        $table = $this->table('ClicksignDatas');
        $entity = $table->newEntity([
            'adhesion_initial_data_id' => $adhesionId,
            'envelope_id' => 'env-' . $adhesionId,
            'attempt' => 1,
            'status' => ClicksignData::STATUS_SIGNED,
            'signed_at' => new \Cake\I18n\DateTime(),
        ]);
        $table->saveOrFail($entity);

        return $entity;
    }

    public function testASignedAdhesionLocksItsEconomicFields(): void
    {
        $adhesionId = $this->createAdhesion();
        $this->addSignedAttempt($adhesionId);

        $this->get("/admin/adhesions/edit/$adhesionId");

        $this->assertResponseOk();
        $this->assertResponseContains('Adesão assinada');
        $this->assertResponseContains('Reabrir proposta');
    }

    public function testEditingAfterSignatureKeepsTheEconomicFields(): void
    {
        $adhesionId = $this->createAdhesion();
        $this->addSignedAttempt($adhesionId);

        $plan = $this->table('AdhesionPlans')->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])->firstOrFail();

        $this->post("/admin/adhesions/edit/$adhesionId", [
            'adhesion_plan' => ['id' => $plan->id, 'monthly_survivors_pension_contribution' => '999.00'],
        ]);

        $reloaded = $this->table('AdhesionPlans')->get($plan->id);
        $this->assertEqualsWithDelta(160.0, (float)$reloaded->monthly_survivors_pension_contribution, 0.01);
    }

    public function testReopeningLetsEditingHappenAgainWithoutTouchingTheSignedRecord(): void
    {
        $adhesionId = $this->createAdhesion();
        $attempt = $this->addSignedAttempt($adhesionId);

        $this->post("/admin/adhesions/reopen-proposal/$adhesionId");

        $this->assertRedirect();

        $reloaded = $this->table('ClicksignDatas')->get($attempt->id);

        // O que foi de fato assinado não muda: status e signed_at permanecem.
        $this->assertSame(ClicksignData::STATUS_SIGNED, $reloaded->status);
        $this->assertNotNull($reloaded->signed_at);
        // Só o destravamento é novo.
        $this->assertNotNull($reloaded->reopened_at);
        $this->assertSame(1, $reloaded->reopened_by_user_id);

        // E agora a edição passa.
        $plan = $this->table('AdhesionPlans')->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])->firstOrFail();
        $this->post("/admin/adhesions/edit/$adhesionId", [
            'adhesion_plan' => ['id' => $plan->id, 'monthly_survivors_pension_contribution' => '999.00'],
        ]);
        $this->assertEqualsWithDelta(999.0, (float)$this->table('AdhesionPlans')->get($plan->id)->monthly_survivors_pension_contribution, 0.01);
    }

    public function testReopeningIsAuditedAndDoesNotTriggerAPlanOverride(): void
    {
        $adhesionId = $this->createAdhesion();
        $this->addSignedAttempt($adhesionId);

        $this->post("/admin/adhesions/reopen-proposal/$adhesionId");

        $audit = $this->table('AdhesionAudits')->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])
            ->orderBy(['id' => 'DESC'])
            ->firstOrFail();

        $this->assertArrayHasKey('proposta', $audit->changeList());

        // Reabrir não é o admin ajustando valor: não deve marcar o plano como
        // negociado à mão.
        $plan = $this->table('AdhesionPlans')->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])->firstOrFail();
        $this->assertFalse($plan->admin_overridden);
    }

    public function testReopeningWithoutASignedAttemptIsRefused(): void
    {
        $adhesionId = $this->createAdhesion();

        $this->post("/admin/adhesions/reopen-proposal/$adhesionId");

        $this->assertSame(
            0,
            $this->table('AdhesionAudits')->find()->where(['adhesion_initial_data_id' => $adhesionId])->count()
        );
    }

    /**
     * Dinheiro já recebido não tem "Reabrir proposta": a ação recusa mesmo
     * havendo assinatura, e não toca o registro do envelope.
     */
    public function testReopeningIsRefusedWhenTheAdhesionIsPaid(): void
    {
        $adhesionId = $this->createAdhesion();
        $attempt = $this->addSignedAttempt($adhesionId);

        $pix = $this->table('PixTransactions');
        $pix->saveOrFail($pix->newEntity([
            'adhesion_initial_data_id' => $adhesionId,
            'txid' => 'tx' . $adhesionId,
            'attempt' => 1,
            'paid' => true,
        ]));

        $this->post("/admin/adhesions/reopen-proposal/$adhesionId");

        $this->assertNull($this->table('ClicksignDatas')->get($attempt->id)->reopened_at);
    }

    public function testCheckingStatusWithoutAnyEnvelopeIsRefusedWithoutCallingClicksign(): void
    {
        $adhesionId = $this->createAdhesion();

        $this->post("/admin/adhesions/check-clicksign-status/$adhesionId");

        $this->assertRedirect();
    }

    public function testDownloadingWithoutAnyEnvelopeIsRefusedWithoutCallingClicksign(): void
    {
        $adhesionId = $this->createAdhesion();

        $this->get("/admin/adhesions/download-signed-document/$adhesionId/qualquer-id");

        $this->assertRedirect();
    }

    /**
     * Filtra pelo nome em vez de confiar na paginação: index() ordena só por
     * created DESC sem desempate, e uma suíte inteira roda rápido o
     * suficiente para criar várias adesões no mesmo segundo -- entre elas, a
     * posição na primeira página fica indeterminada.
     */
    public function testTheSignatureBadgeAppearsOnTheListing(): void
    {
        // O filtro de index() casa AdhesionPersonalDatas.name, não o nome da
        // raíz -- createAdhesion() não grava dados pessoais, então precisa
        // criar essa linha para o filtro achar a adesão certa.
        $uniqueName = 'Assinatura Teste ' . bin2hex(random_bytes(6));
        $adhesionId = $this->createAdhesion();
        $this->addSignedAttempt($adhesionId);

        $personalDatas = $this->table('AdhesionPersonalDatas');
        $personalDatas->saveOrFail($personalDatas->newEntity([
            'adhesion_initial_data_id' => $adhesionId,
            'plan_for' => 'Titular',
            'name' => $uniqueName,
            'cpf' => '123.456.789-09',
            'nacionality' => 'Brasileira',
        ]));

        $this->get('/admin/adhesions?' . http_build_query(['name' => $uniqueName]));

        $this->assertResponseOk();
        $this->assertResponseContains('Assinado');
    }
}
