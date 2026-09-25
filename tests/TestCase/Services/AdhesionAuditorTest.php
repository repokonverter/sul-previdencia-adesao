<?php

declare(strict_types=1);

namespace App\Test\TestCase\Services;

use App\Model\Entity\AdhesionAudit;
use App\Services\AdhesionAuditor;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

class AdhesionAuditorTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
    ];

    private AdhesionAuditor $auditor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditor = new AdhesionAuditor(TableRegistry::getTableLocator()->get('AdhesionAudits'));
    }

    private function adhesions(): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get('AdhesionInitialDatas');
    }

    private function createAdhesion(): \Cake\Datasource\EntityInterface
    {
        $adhesion = $this->adhesions()->newEntity([
            'storage_uuid' => Text::uuid(),
            'name' => 'Fulano de Tal',
            'email' => 'fulano@exemplo.test',
            'phone' => '48999999999',
        ]);

        $this->adhesions()->saveOrFail($adhesion);

        $plans = TableRegistry::getTableLocator()->get('AdhesionPlans');
        $plans->saveOrFail($plans->newEntity([
            'adhesion_initial_data_id' => $adhesion->id,
            'benefit_entry_age' => 65,
            'monthly_retirement_contribution' => '740.00',
            'monthly_survivors_pension_contribution' => '160.00',
            'survivors_pension_insured_capital' => '1000.00',
            'monthly_disability_retirement_contribution' => '100.00',
            'disability_retirement_insured_capital' => '1000.00',
        ]));

        return $this->adhesions()->get($adhesion->id, contain: AdhesionAuditor::CONTAINS);
    }

    public function testSnapshotLeavesOutIdentifiersAndTimestamps(): void
    {
        $snapshot = AdhesionAuditor::snapshot($this->createAdhesion());

        $this->assertArrayNotHasKey('id', $snapshot);
        $this->assertArrayNotHasKey('created', $snapshot);
        $this->assertArrayNotHasKey('updated', $snapshot);
        $this->assertArrayNotHasKey('adhesion_plan.id', $snapshot);

        $this->assertSame('Fulano de Tal', $snapshot['name']);
        $this->assertArrayHasKey('adhesion_plan.monthly_survivors_pension_contribution', $snapshot);
    }

    public function testDiffReportsOnlyWhatChanged(): void
    {
        $changes = AdhesionAuditor::diff(
            ['name' => 'Fulano', 'email' => 'a@b.test'],
            ['name' => 'Beltrano', 'email' => 'a@b.test']
        );

        $this->assertSame(['name' => ['Fulano', 'Beltrano']], $changes);
    }

    /**
     * O mesmo dinheiro sai do banco e do formulário com grafias diferentes.
     * Registrar isso como alteração encheria a trilha de linhas que não
     * aconteceram, e é justamente o ruído que torna uma trilha inútil.
     */
    public function testDiffIgnoresNumericFormattingDifferences(): void
    {
        $this->assertSame([], AdhesionAuditor::diff(
            ['valor' => '160.00', 'flag' => true, 'vazio' => null],
            ['valor' => 160.0, 'flag' => '1', 'vazio' => '']
        ));
    }

    public function testDiffCatchesRealMoneyChanges(): void
    {
        $changes = AdhesionAuditor::diff(['valor' => '300.00'], ['valor' => '250.00']);

        $this->assertSame(['valor' => ['300.00', '250.00']], $changes);
    }

    public function testDiffShowsListsGrowingAndShrinking(): void
    {
        $before = AdhesionAuditor::snapshot($this->createAdhesion());

        $dependents = TableRegistry::getTableLocator()->get('AdhesionDependents');
        $adhesionId = (int)$this->adhesions()->find()->orderBy(['id' => 'DESC'])->firstOrFail()->id;
        $dependents->saveOrFail($dependents->newEntity([
            'adhesion_initial_data_id' => $adhesionId,
            'name' => 'Maria',
            'participation' => '100.00',
        ]));

        $after = AdhesionAuditor::snapshot($this->adhesions()->get($adhesionId, contain: AdhesionAuditor::CONTAINS));
        $changes = AdhesionAuditor::diff($before, $after);

        $this->assertSame([0, 1], $changes['adhesion_dependents.total']);
        $this->assertArrayHasKey('adhesion_dependents.0.name', $changes);
    }

    public function testRecordKeepsTheAuthorSnapshot(): void
    {
        $adhesion = $this->createAdhesion();
        $user = TableRegistry::getTableLocator()->get('Users')->get(1);

        $audit = $this->auditor->record(
            (int)$adhesion->id,
            $user,
            AdhesionAudit::ACTION_UPDATED,
            ['name' => ['Fulano', 'Beltrano']]
        );

        $this->assertNotNull($audit);
        $this->assertSame(1, $audit->user_id);
        $this->assertSame($user->name, $audit->user_name);
        $this->assertSame(['name' => ['Fulano', 'Beltrano']], $audit->changeList());
    }

    /**
     * Abrir a edição e salvar sem mexer em nada não é um fato: a trilha existe
     * para ser lida, e essa linha só atrapalharia a leitura.
     */
    public function testAnEditThatChangedNothingIsNotRecorded(): void
    {
        $adhesion = $this->createAdhesion();

        $this->assertNull($this->auditor->record((int)$adhesion->id, null, AdhesionAudit::ACTION_UPDATED, []));
    }

    public function testCreationIsRecordedEvenWithoutADiff(): void
    {
        $adhesion = $this->createAdhesion();

        $audit = $this->auditor->record((int)$adhesion->id, null, AdhesionAudit::ACTION_CREATED);

        $this->assertNotNull($audit);
        $this->assertSame('Sistema', $audit->authorLabel());
        $this->assertSame([], $audit->changeList());
    }
}
