<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * A exclusão precisa de trilha própria: a chave estrangeira de
 * adhesion_audits é CASCADE, então uma linha lá seria apagada pela própria
 * exclusão que ela registra.
 */
class AdhesionsControllerDeleteTest extends TestCase
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
            'email' => 'fulano@exemplo.test',
            'phone' => '48999999999',
        ]);
        $adhesions->saveOrFail($adhesion);

        $personal = $this->table('AdhesionPersonalDatas');
        $personal->saveOrFail($personal->newEntity([
            'adhesion_initial_data_id' => $adhesion->id,
            'plan_for' => 'Titular',
            'name' => 'Fulano de Tal',
            'cpf' => '123.456.789-09',
            'nacionality' => 'Brasileira',
        ]));

        return (int)$adhesion->id;
    }

    public function testDeletionIsRecordedAndSurvivesTheAdhesion(): void
    {
        $adhesionId = $this->createAdhesion();

        $this->post("/admin/adhesions/delete/$adhesionId");

        $this->assertRedirect(['action' => 'index']);
        $this->assertFalse($this->table('AdhesionInitialDatas')->exists(['id' => $adhesionId]));

        $deletion = $this->table('AdhesionDeletions')->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])
            ->firstOrFail();

        $this->assertSame('Fulano de Tal', $deletion->adhesion_name);
        $this->assertSame('123.456.789-09', $deletion->adhesion_cpf);
        $this->assertSame(1, $deletion->user_id);
        $this->assertNotEmpty($deletion->user_name);
    }

    /**
     * O contraste que justifica a tabela separada: o que estava em
     * adhesion_audits desaparece junto com a adesão.
     */
    public function testTheEditTrailIsLostWithTheAdhesionButTheDeletionIsNot(): void
    {
        $adhesionId = $this->createAdhesion();

        $this->post("/admin/adhesions/edit/$adhesionId", ['name' => 'Beltrano de Tal']);
        $this->assertSame(1, $this->table('AdhesionAudits')->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])->count());

        $this->post("/admin/adhesions/delete/$adhesionId");

        $this->assertSame(0, $this->table('AdhesionAudits')->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])->count());
        $this->assertSame(1, $this->table('AdhesionDeletions')->find()
            ->where(['adhesion_initial_data_id' => $adhesionId])->count());
    }

    public function testDeletionsScreenListsThem(): void
    {
        $adhesionId = $this->createAdhesion();
        $this->post("/admin/adhesions/delete/$adhesionId");

        $this->get('/admin/adhesions/deletions');

        $this->assertResponseOk();
        $this->assertResponseContains('Adesões Excluídas');
        $this->assertResponseContains('123.456.789-09');
    }
}
