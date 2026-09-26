<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Só a listagem, que não toca a Clicksign. add() e delete() chamam a API de
 * verdade (createWebhook/deleteWebhook, já validados manualmente contra o
 * sandbox) e ficam fora do escopo de HTTP mocking combinado.
 */
class ClicksignWebhooksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->session([
            'Auth' => TableRegistry::getTableLocator()->get('Users')->get(1),
        ]);

        // Sem fixture declarada para esta tabela, uma linha gravada num teste
        // sobrevive para o próximo dentro do mesmo processo de suite.
        TableRegistry::getTableLocator()->get('ClicksignWebhooks')->deleteAll([]);
    }

    public function testIndexListsRegisteredWebhooks(): void
    {
        $webhooks = TableRegistry::getTableLocator()->get('ClicksignWebhooks');
        $webhooks->saveOrFail($webhooks->newEntity([
            'clicksign_webhook_id' => 'remote-1',
            'token' => bin2hex(random_bytes(20)),
            'url' => 'https://example.test/clicksign/webhook/x',
        ]));

        $this->get('/admin/clicksign-webhooks');

        $this->assertResponseOk();
        $this->assertResponseContains('example.test/clicksign/webhook');
    }

    public function testIndexWithNoneRegistered(): void
    {
        $this->get('/admin/clicksign-webhooks');

        $this->assertResponseOk();
        $this->assertResponseContains('Nenhum webhook cadastrado');
    }
}
