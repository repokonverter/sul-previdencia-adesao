<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\BrokersController::validate() — o corretor precisa existir
 * e estar ativo; qualquer outro caso não deve nunca reportar sucesso, pois é
 * essa checagem que libera a remoção de riscos no formulário público.
 */
class BrokersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Brokers',
    ];

    public function testActiveBrokerIsValid(): void
    {
        $this->get('/brokers/validate?code=JOAO2026');

        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertTrue($result['valid']);
        $this->assertSame('João da Silva', $result['name']);
    }

    public function testInactiveBrokerIsInvalid(): void
    {
        $this->get('/brokers/validate?code=MARIAINATIVA');

        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertFalse($result['valid']);
        $this->assertSame('inactive', $result['reason']);
    }

    public function testUnknownCodeIsInvalid(): void
    {
        $this->get('/brokers/validate?code=NAOEXISTE');

        $this->assertResponseOk();
        $result = json_decode((string)$this->_response->getBody(), true);

        $this->assertFalse($result['valid']);
        $this->assertSame('not_found', $result['reason']);
    }
}
