<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

/**
 * Um envelope por tentativa, e não um por adesão.
 *
 * A Clicksign só apaga documento de envelope em `draft`, então refinalizar uma
 * proposta — rotina, agora que existe o link de retomada — exige cancelar o
 * envelope que já saiu e criar outro. Com hasOne, o id do anterior seria
 * sobrescrito e o rastro do contrato assinado se perderia.
 */
class ClicksignDatasTableTest extends TestCase
{
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

        return (int)$adhesion->id;
    }

    private function addAttempt(int $adhesionId, int $attempt, string $status = 'sent'): \App\Model\Entity\ClicksignData
    {
        $table = $this->table('ClicksignDatas');
        $entity = $table->newEntity([
            'adhesion_initial_data_id' => $adhesionId,
            'envelope_id' => 'env-' . $adhesionId . '-' . $attempt,
            'attempt' => $attempt,
            'status' => $status,
        ]);
        $table->saveOrFail($entity);

        return $entity;
    }

    public function testAnAdhesionKeepsEveryAttempt(): void
    {
        $adhesionId = $this->createAdhesion();

        $this->addAttempt($adhesionId, 1);
        $this->addAttempt($adhesionId, 2);

        $adhesion = $this->table('AdhesionInitialDatas')->get($adhesionId, contain: ['ClicksignDatas']);

        $this->assertCount(2, $adhesion->clicksign_datas);
    }

    public function testLatestForReturnsTheCurrentAttempt(): void
    {
        $adhesionId = $this->createAdhesion();

        $this->addAttempt($adhesionId, 1);
        $second = $this->addAttempt($adhesionId, 2);

        $latest = $this->table('ClicksignDatas')->latestFor($adhesionId);

        $this->assertSame($second->envelope_id, $latest->envelope_id);
        $this->assertSame(2, $latest->attempt);
    }

    public function testLatestForIsNullWhenNothingWasSentYet(): void
    {
        $this->assertNull($this->table('ClicksignDatas')->latestFor($this->createAdhesion()));
    }

    /**
     * O anterior fica como histórico, com o envelope cancelado, e o novo
     * nasce ao lado — nunca por cima.
     */
    public function testAttemptsDoNotOverwriteEachOther(): void
    {
        $adhesionId = $this->createAdhesion();

        $first = $this->addAttempt($adhesionId, 1);
        $this->addAttempt($adhesionId, 2);

        $reloaded = $this->table('ClicksignDatas')->get($first->id);

        $this->assertSame($first->envelope_id, $reloaded->envelope_id);
    }
}
