<?php

declare(strict_types=1);

namespace App\Test\TestCase\Services;

use App\Services\ClicksignWebhookPayload;
use Cake\TestSuite\TestCase;

/**
 * Achar o id da adesão num payload de webhook, sem rede: pura leitura de
 * array, separada de ClicksignSignatureChecker, que é quem de fato confirma
 * algo com a Clicksign.
 */
class ClicksignWebhookPayloadTest extends TestCase
{
    public function testMetadataAtTheDocumentTopLevel(): void
    {
        $this->assertSame(42, ClicksignWebhookPayload::resolveAdhesionId([
            'document' => ['attributes' => ['metadata' => ['adhesion_initial_data_id' => '42']]],
        ]));
    }

    public function testMetadataNestedUnderDocumentData(): void
    {
        $this->assertSame(42, ClicksignWebhookPayload::resolveAdhesionId([
            'document' => ['data' => ['attributes' => ['metadata' => ['adhesion_initial_data_id' => '42']]]],
        ]));
    }

    public function testMetadataNestedUnderEventData(): void
    {
        $this->assertSame(42, ClicksignWebhookPayload::resolveAdhesionId([
            'event' => ['data' => ['document' => ['attributes' => ['metadata' => ['adhesion_initial_data_id' => '42']]]]],
        ]));
    }

    public function testAnUnrecognizedShapeResolvesToNull(): void
    {
        $this->assertNull(ClicksignWebhookPayload::resolveAdhesionId(['algo' => 'inesperado']));
    }

    public function testAnEmptyBodyResolvesToNull(): void
    {
        $this->assertNull(ClicksignWebhookPayload::resolveAdhesionId([]));
    }

    /**
     * Um valor não numérico não é aceito como id: castar às cegas
     * transformaria "abc" em 0, apontando para uma adesão que existe.
     */
    public function testANonNumericValueIsRejected(): void
    {
        $this->assertNull(ClicksignWebhookPayload::resolveAdhesionId([
            'document' => ['attributes' => ['metadata' => ['adhesion_initial_data_id' => 'abc']]],
        ]));
    }

    public function testEventName(): void
    {
        $this->assertSame('document_closed', ClicksignWebhookPayload::eventName([
            'event' => ['name' => 'document_closed'],
        ]));
        $this->assertNull(ClicksignWebhookPayload::eventName([]));
    }
}
