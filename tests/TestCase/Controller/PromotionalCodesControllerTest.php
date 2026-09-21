<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Cobre a validação cruzada entre código promocional e vínculo associativo
 * (App\Controller\PromotionalCodesController::validate()): um código só é
 * aceito para o vínculo a que pertence, e um código de vínculo digitado sem
 * a pergunta respondida corrige a resposta em vez de bloquear.
 */
class PromotionalCodesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Partners',
        'app.PromotionalCodes',
    ];

    public function testPlainPartnerCodeIsValidWithoutAssociation(): void
    {
        $this->get('/promotional-codes/validate?code=PARCEIRO10');

        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertTrue($result['valid']);
        $this->assertSame('Corretora Parceira', $result['partnerName']);
        $this->assertNull($result['autoAssociation']);
    }

    public function testAssociationCodeAutoCorrectsWhenAskedWithoutAssociation(): void
    {
        $this->get('/promotional-codes/validate?code=SINDILOJAS2026');

        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertTrue($result['valid']);
        $this->assertSame(2, $result['autoAssociation']['id']);
        $this->assertSame('Sindilojas Exemplo', $result['autoAssociation']['name']);
    }

    public function testAssociationCodeMatchingSelectedAssociationDoesNotAutoCorrect(): void
    {
        $this->get('/promotional-codes/validate?code=SINDILOJAS2026&associationId=2');

        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertTrue($result['valid']);
        $this->assertNull($result['autoAssociation']);
    }

    public function testCodeFromAnotherPartnerIsRejectedForSelectedAssociation(): void
    {
        $this->get('/promotional-codes/validate?code=PARCEIRO10&associationId=2');

        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertFalse($result['valid']);
        $this->assertSame('wrong_partner', $result['reason']);
    }
}
