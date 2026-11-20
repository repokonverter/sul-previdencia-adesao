<?php

declare(strict_types=1);

namespace App\Test\TestCase\Services;

use App\Services\BancoDoBrasilService;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * A API Pix do BB respondida por mocks do cliente HTTP: o que importa é o
 * que muda em relação ao Sicoob (token, chave do aplicativo na query,
 * `pixCopiaECola`), não a rede.
 */
class BancoDoBrasilServiceTest extends TestCase
{
    use HttpClientTrait;

    private const BASE = 'https://bb.test/pix/v2';

    private const AUTH = 'https://bb.test/oauth/token';

    private function service(array $override = []): BancoDoBrasilService
    {
        return new BancoDoBrasilService($override + [
            'baseUrl' => self::BASE,
            'authUrl' => self::AUTH,
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
            'appKey' => 'APPKEY',
        ]);
    }

    private function json(int $code, array $body): \Cake\Http\Client\Response
    {
        return $this->newClientResponse($code, ['Content-Type: application/json'], (string)json_encode($body));
    }

    private function mockToken(string $token = 'token-1'): void
    {
        $this->mockClientPost(self::AUTH, $this->json(200, ['access_token' => $token, 'expires_in' => 600]));
    }

    public function testACobComesBackWithTheBrcodeUnderTheSicoobName(): void
    {
        $this->mockToken();
        $this->mockClientPut(self::BASE . '/cob/TXID1?gw-dev-app-key=APPKEY', $this->json(201, [
            'txid' => 'TXID1',
            'status' => 'ATIVA',
            'pixCopiaECola' => '00020101021226...',
        ]));

        $cob = $this->service()->createCobWithTxid('TXID1', ['valor' => ['original' => '150.00']]);

        $this->assertSame('ATIVA', $cob['status']);
        $this->assertSame('00020101021226...', $cob['brcode']);
    }

    public function testAnExpiredTokenIsRenewedOnce(): void
    {
        $this->mockToken();
        $url = self::BASE . '/cob/TXID1?gw-dev-app-key=APPKEY';
        $this->mockClientGet($url, $this->json(401, ['error' => 'invalid_token']));
        $this->mockClientGet($url, $this->json(200, ['status' => 'CONCLUIDA', 'pixCopiaECola' => 'abc']));

        $this->assertSame('CONCLUIDA', $this->service()->getCob('TXID1')['status']);
    }

    public function testTheBacenErrorReachesTheException(): void
    {
        $this->mockToken();
        $this->mockClientPut(self::BASE . '/cob/TXID1?gw-dev-app-key=APPKEY', $this->json(400, [
            'title' => 'Cobrança inválida.',
            'detail' => 'A requisição que busca alterar ou criar uma cobrança não respeita o schema.',
            'violacoes' => [['razao' => 'CPF inválido', 'propriedade' => 'cob.devedor.cpf']],
        ]));

        $this->expectExceptionMessage('não respeita o schema. (cob.devedor.cpf: CPF inválido)');

        $this->service()->createCobWithTxid('TXID1', []);
    }

    public function testAFailedLoginStopsBeforeTheCall(): void
    {
        $this->mockClientPost(self::AUTH, $this->json(401, ['error' => 'invalid_client', 'error_description' => 'Credenciais inválidas']));

        $this->expectExceptionMessage('Falha na autenticação Banco do Brasil (401): Credenciais inválidas');

        $this->service()->getCob('TXID1');
    }

    public function testRemovingTheWebhookAnswersWithoutBody(): void
    {
        $this->mockToken();
        $this->mockClientDelete(self::BASE . '/webhook/chave@exemplo.test?gw-dev-app-key=APPKEY', $this->newClientResponse(204));

        $this->assertSame([], $this->service()->deleteWebhook('chave@exemplo.test'));
    }

    public function testMissingConfigurationIsOnlyAnErrorWhenUsed(): void
    {
        $service = $this->service(['appKey' => null]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('falta "appKey"');

        $service->getCob('TXID1');
    }
}
