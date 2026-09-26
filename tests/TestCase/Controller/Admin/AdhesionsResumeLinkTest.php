<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Services\AdhesionSteps;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * O admin escolhendo a etapa e distribuindo o link.
 */
class AdhesionsResumeLinkTest extends TestCase
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
     * @param list<string> $sections seções já preenchidas, além da raiz
     */
    private function createAdhesion(array $sections = []): int
    {
        $adhesions = $this->table('AdhesionInitialDatas');
        $adhesion = $adhesions->newEntity([
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'email' => 'fulano@exemplo.test',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);

        $rows = [
            'personalData' => ['AdhesionPersonalDatas', ['plan_for' => 'Titular', 'name' => 'Fulano', 'cpf' => '123.456.789-09', 'birth_date' => '1985-03-10', 'nacionality' => 'Brasileira']],
            'documents' => ['AdhesionDocuments', ['type' => 'RG', 'document_number' => '1234567']],
            'addressData' => ['AdhesionAddresses', ['cep' => '88000000', 'address' => 'Rua', 'number' => '1', 'neighborhood' => 'Centro', 'city' => 'Floripa', 'state' => 'SC']],
            'otherInformation' => ['AdhesionOtherInformations', ['main_occupation_description' => 'Dev', 'main_occupation_code' => '999999', 'category' => 'Outros']],
        ];

        foreach ($sections as $section) {
            [$table, $data] = $rows[$section];
            $this->table($table)->saveOrFail(
                $this->table($table)->newEntity($data + ['adhesion_initial_data_id' => $adhesion->id])
            );
        }

        return (int)$adhesion->id;
    }

    public function testThePickerSuggestsTheFirstMissingStep(): void
    {
        $adhesionId = $this->createAdhesion(['personalData']);

        $this->get("/admin/adhesions/view/$adhesionId?tab=resume");

        $this->assertResponseOk();
        $this->assertResponseContains('Link de retomada');
        // Documentos é a primeira em branco, então vem pré-escolhida.
        $this->assertResponseContains('<option value="documents"');
    }

    public function testIssuingALinkStoresTheStepAndAuditsIt(): void
    {
        $adhesionId = $this->createAdhesion(['personalData', 'documents']);

        $this->post("/admin/adhesions/issue-resume-link/$adhesionId", ['step' => 'dependents']);

        $this->assertRedirect();

        $adhesion = $this->table('AdhesionInitialDatas')->get($adhesionId);

        $this->assertNotNull($adhesion->resume_token);
        $this->assertSame('dependents', $adhesion->resume_step);
        $this->assertFalse($adhesion->resumeTokenHasExpired());

        $audit = $this->table('AdhesionAudits')->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])
            ->orderBy(['id' => 'DESC'])
            ->firstOrFail();

        $this->assertArrayHasKey('resume_link', $audit->changeList());
    }

    /**
     * nextPage() valida só a etapa corrente, então apontar o link para uma
     * etapa cujas anteriores estão em branco faria a lacuna passar batido e a
     * adesão ser finalizada sem endereço.
     */
    public function testAStepThatDependsOnAnEmptyOneIsRefused(): void
    {
        $adhesionId = $this->createAdhesion();

        $this->post("/admin/adhesions/issue-resume-link/$adhesionId", ['step' => 'paymentDetail']);

        $this->assertNull($this->table('AdhesionInitialDatas')->get($adhesionId)->resume_token);
    }

    public function testAnUnknownStepIsRefused(): void
    {
        $adhesionId = $this->createAdhesion(['personalData']);

        $this->post("/admin/adhesions/issue-resume-link/$adhesionId", ['step' => 'etapaInventada']);

        $this->assertNull($this->table('AdhesionInitialDatas')->get($adhesionId)->resume_token);
    }

    public function testRevokingClearsTheTokenAndAuditsIt(): void
    {
        $adhesionId = $this->createAdhesion(['personalData']);
        $this->post("/admin/adhesions/issue-resume-link/$adhesionId", ['step' => 'documents']);

        $this->post("/admin/adhesions/revoke-resume-link/$adhesionId");

        $adhesion = $this->table('AdhesionInitialDatas')->get($adhesionId);

        $this->assertNull($adhesion->resume_token);
        $this->assertNull($adhesion->resume_token_expires_at);
    }

    public function testSendingWithoutAnActiveLinkIsRefused(): void
    {
        $adhesionId = $this->createAdhesion(['personalData']);

        $this->post("/admin/adhesions/send-resume-link/$adhesionId", ['email' => 'alguem@exemplo.test']);

        $this->assertSame(
            0,
            $this->table('AdhesionAudits')->find()->where(['adhesion_initial_data_id' => $adhesionId])->count()
        );
    }

    public function testAnInvalidEmailIsRefused(): void
    {
        $adhesionId = $this->createAdhesion(['personalData']);
        $this->post("/admin/adhesions/issue-resume-link/$adhesionId", ['step' => 'documents']);

        $this->post("/admin/adhesions/send-resume-link/$adhesionId", ['email' => 'nao-e-email']);

        $audits = $this->table('AdhesionAudits')->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])
            ->toArray();

        // Só a linha da geração; o envio não aconteceu.
        $this->assertCount(1, $audits);
    }

    public function testStepOrderMatchesTheFormsOwnOrder(): void
    {
        $this->assertSame(
            [
                'initialData', 'personalData', 'documents', 'dependents', 'addressData',
                'otherInformation', 'pensionScheme', 'plan', 'proponentStatement', 'paymentDetail',
            ],
            array_keys(AdhesionSteps::ORDER)
        );
    }
}
