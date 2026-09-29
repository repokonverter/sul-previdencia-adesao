<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * O carimbo de tempo que não existia.
 *
 * As migrations criam `created`/`updated`, o behavior Timestamp grava
 * `created`/`modified` por padrão, e a diferença não produz erro: a
 * atribuição vai para uma propriedade que o save descarta. O resultado era
 * `updated` NULL em toda tabela do projeto.
 */
class AppTableTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'app.PlanParameters',
        'app.ClicksignWebhooks',
    ];

    public function testStampsUpdatedOnTablesThatHaveIt(): void
    {
        $table = TableRegistry::getTableLocator()->get('PlanParameters');
        $parameters = $table->find()->firstOrFail();

        $table->saveOrFail($table->patchEntity($parameters, ['minimum_monthly_contribution' => '120.00']));

        $saved = $table->find()->firstOrFail();

        $this->assertNotNull($saved->updated);
        $this->assertGreaterThan(
            '2026-01-01 00:00:00',
            $saved->updated->format('Y-m-d H:i:s'),
            'a fixture nasce com 2026-01-01; se o carimbo não for reescrito, continua lá'
        );
    }

    public function testStampsModifiedOnTablesThatUseThatNameInstead(): void
    {
        $table = TableRegistry::getTableLocator()->get('Users');
        $user = $table->get(1);

        $table->saveOrFail($table->patchEntity($user, ['name' => 'Nome novo']));

        $this->assertNotNull($table->get(1)->modified);
        $this->assertGreaterThan(
            '2025-10-13 12:10:01',
            $table->get(1)->modified->format('Y-m-d H:i:s')
        );
    }

    public function testStampsCreatedOnInsert(): void
    {
        $table = TableRegistry::getTableLocator()->get('ClicksignWebhooks');

        $webhook = $table->newEntity([
            'clicksign_webhook_id' => 'wh_carimbo',
            'token' => 'token-carimbo',
            'url' => 'https://example.test/clicksign/webhook/token-carimbo',
        ]);
        $table->saveOrFail($webhook);

        $this->assertNotNull($webhook->created);
        $this->assertNotNull($webhook->updated);
    }
}
