<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * O receptor do webhook: um token errado é rejeitado, um payload sem forma
 * reconhecida não derruba a resposta 200.
 *
 * Não cobre aqui a resolução do id da adesão a partir de metadata -- isso é
 * ClicksignWebhookPayloadTest, sem rede. Um payload que resolvesse um id de
 * verdade faria este teste chamar a Clicksign de verdade (via
 * ClicksignSignatureChecker::fromConfigure()), o que a suíte não faz em
 * nenhum outro lugar; a separação existe justamente para não precisar disso.
 */
class WebhooksClicksignTest extends TestCase
{
    use IntegrationTestTrait;

    private function table(string $name): \Cake\ORM\Table
    {
        return TableRegistry::getTableLocator()->get($name);
    }

    /**
     * Um token novo por chamada: nada aqui roda em fixture truncada entre
     * métodos de teste, e um token fixo repetido colide na constraint única.
     */
    private function registerWebhook(): string
    {
        $token = bin2hex(random_bytes(20));

        $webhooks = $this->table('ClicksignWebhooks');
        $webhooks->saveOrFail($webhooks->newEntity([
            'clicksign_webhook_id' => 'remote-id-' . $token,
            'token' => $token,
            'url' => 'https://example.test/clicksign/webhook/' . $token,
        ]));

        return $token;
    }

    public function testAnUnknownTokenIsRejected(): void
    {
        $this->post('/clicksign/webhook/token-que-nao-existe', ['event' => ['name' => 'close']]);

        $this->assertResponseCode(404);
    }

    /**
     * Um payload sem o formato esperado não pode derrubar a resposta: a
     * Clicksign trata qualquer coisa fora de 2XX como falha de entrega e
     * reenvia, e a rota nunca teve como garantir o formato do que chega.
     */
    public function testAnUnrecognizedPayloadStillAnswers200(): void
    {
        $token = $this->registerWebhook();

        $this->post("/clicksign/webhook/$token", ['algo' => 'inesperado']);

        $this->assertResponseCode(200);
    }

    public function testAnEmptyBodyStillAnswers200(): void
    {
        $token = $this->registerWebhook();

        $this->configRequest(['headers' => ['Content-Type' => 'application/json']]);
        $this->post("/clicksign/webhook/$token", '');

        $this->assertResponseCode(200);
    }

    public function testAGetIsNotAllowed(): void
    {
        $token = $this->registerWebhook();

        $this->get("/clicksign/webhook/$token");

        $this->assertResponseCode(405);
    }
}
