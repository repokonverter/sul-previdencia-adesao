<?php

declare(strict_types=1);

namespace App\Services;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Exception;

/**
 * API Pix v2 do Banco do Brasil (padrão Bacen), conta do MaisPremium.
 *
 * Diferenças para o Sicoob que importam aqui:
 * - o token sai de OAuth client credentials com client_id/client_secret em
 *   Basic auth;
 * - toda chamada leva a chave do aplicativo (`gw-dev-app-key`) na query;
 * - a cobrança devolve o copia-e-cola em `pixCopiaECola` (campo do Bacen),
 *   não em `brcode` (extensão do Sicoob) -- getCob/createCobWithTxid
 *   devolvem os dois, para o PixPaymentService não precisar saber de banco;
 * - em produção a API exige mTLS com o certificado da empresa.
 *
 * As URLs vêm todas do .env (BB_*): homologação e produção ficam no portal
 * BB Developers, junto com as credenciais do aplicativo.
 */
class BancoDoBrasilService implements PixGateway
{
    private const SCOPES = 'cob.read cob.write pix.read pix.write webhook.read webhook.write';

    private Client $httpClient;

    private ?string $accessToken = null;

    private ?int $adhesionId = null;

    /**
     * @param string $configKey Chave do Configure com as credenciais; ver
     *   `pix.config` em config/plans.php.
     */
    public static function fromConfigure(string $configKey = 'BancoDoBrasil'): self
    {
        return new self((array)Configure::read($configKey));
    }

    /**
     * @param array{baseUrl?: ?string, authUrl?: ?string, clientId?: ?string, clientSecret?: ?string, appKey?: ?string, certificateBase64?: ?string, privateKeyBase64?: ?string} $config
     */
    public function __construct(private array $config)
    {
        $ssl = [];
        $certificate = self::writeTmp('bb_cert', $config['certificateBase64'] ?? null);
        $privateKey = self::writeTmp('bb_key', $config['privateKeyBase64'] ?? null);

        if ($certificate && $privateKey) {
            $ssl = [
                'ssl_local_cert' => $certificate,
                'ssl_local_pk' => $privateKey,
            ];
        }

        $this->httpClient = new Client($ssl + ['timeout' => 30]);
    }

    /**
     * O certificado e a chave chegam em Base64 no .env (como no Sicoob) e o
     * cliente HTTP precisa de arquivo. O nome leva o hash do conteúdo, para um
     * certificado renovado não reaproveitar o arquivo do anterior.
     */
    private static function writeTmp(string $prefix, ?string $base64): ?string
    {
        if (empty($base64)) {
            return null;
        }

        $path = TMP . $prefix . '_' . md5($base64) . '.pem';

        if (!file_exists($path)) {
            file_put_contents($path, base64_decode($base64));
            chmod($path, 0600);
        }

        return $path;
    }

    public function forAdhesion(?int $adhesionId): static
    {
        $this->adhesionId = $adhesionId;

        return $this;
    }

    public function createCobWithTxid(string $txid, array $data): array
    {
        return self::withBrcode($this->request('put', "/cob/{$txid}", $data));
    }

    public function getCob(string $txid): array
    {
        return self::withBrcode($this->request('get', "/cob/{$txid}"));
    }

    /**
     * @param array $params (inicio, fim, cpf, cnpj, status, paginacao.paginaAtual, paginacao.itensPorPagina)
     */
    public function listCob(array $params): array
    {
        return $this->request('get', '/cob', [], $params);
    }

    public function configureWebhook(string $chave, string $webhookUrl): array
    {
        return $this->request('put', "/webhook/{$chave}", ['webhookUrl' => $webhookUrl]);
    }

    public function getWebhook(string $chave): array
    {
        return $this->request('get', "/webhook/{$chave}");
    }

    public function deleteWebhook(string $chave): array
    {
        return $this->request('delete', "/webhook/{$chave}");
    }

    private static function withBrcode(array $cob): array
    {
        $cob['brcode'] ??= $cob['pixCopiaECola'] ?? null;

        return $cob;
    }

    private function authenticate(): void
    {
        $url = $this->setting('authUrl');
        $startedAt = microtime(true);
        $statusCode = null;
        $errorMessage = null;

        try {
            $response = $this->httpClient->post($url, [
                'grant_type' => 'client_credentials',
                'scope' => self::SCOPES,
            ], [
                'auth' => ['username' => $this->setting('clientId'), 'password' => $this->setting('clientSecret')],
            ]);
            $statusCode = $response->getStatusCode();
            $this->accessToken = $response->isOk() ? ($response->getJson()['access_token'] ?? null) : null;

            if (!$this->accessToken) {
                $errorMessage = 'Falha na autenticação Banco do Brasil (' . $statusCode . '): ' . self::errorMessage($response);
            }
        } catch (Exception $e) {
            $errorMessage = 'Falha na autenticação Banco do Brasil: ' . $e->getMessage();
        }

        IntegrationLogger::logHttp([
            'adhesionId' => $this->adhesionId,
            'service' => 'bancodobrasil',
            'operation' => 'bancodobrasil.authenticate',
            'httpMethod' => 'POST',
            'url' => $url,
            'statusCode' => $statusCode,
            'success' => $errorMessage === null,
            'durationMs' => IntegrationLogger::elapsedMs($startedAt),
            'errorMessage' => $errorMessage,
        ]);

        if ($errorMessage !== null) {
            throw new Exception($errorMessage);
        }
    }

    /**
     * @param array $query Parâmetros além da chave do aplicativo, que vai em todas.
     */
    private function request(string $method, string $path, array $data = [], array $query = []): array
    {
        $operation = 'bancodobrasil.' . IntegrationLogger::operationFromCaller();
        $url = $this->setting('baseUrl') . $path . '?' . http_build_query(['gw-dev-app-key' => $this->setting('appKey')] + $query);
        $startedAt = microtime(true);
        $statusCode = null;

        try {
            if (!$this->accessToken) {
                $this->authenticate();
            }

            $response = $this->send($method, $url, $data);

            // Token vencido (dura minutos): renova uma vez e repete.
            if ($response->getStatusCode() === 401) {
                $this->authenticate();
                $response = $this->send($method, $url, $data);
            }

            $statusCode = $response->getStatusCode();
            $json = $response->getJson();
            $errorMessage = $response->isOk() ? null : self::errorMessage($response);

            IntegrationLogger::logHttp([
                'adhesionId' => $this->adhesionId,
                'service' => 'bancodobrasil',
                'operation' => $operation,
                'httpMethod' => strtoupper($method),
                'url' => $this->loggedUrl($url),
                'statusCode' => $statusCode,
                'success' => $errorMessage === null,
                'durationMs' => IntegrationLogger::elapsedMs($startedAt),
                'requestBody' => $data,
                'responseBody' => $json,
                'errorMessage' => $errorMessage,
            ]);

            if ($errorMessage !== null) {
                throw new Exception($errorMessage);
            }

            // DELETE /webhook responde 204, sem corpo.
            return is_array($json) ? $json : [];
        } catch (Exception $e) {
            // Falha de rede (ou de autenticação, já logada) não chega ao log acima.
            if ($statusCode === null) {
                IntegrationLogger::logHttp([
                    'adhesionId' => $this->adhesionId,
                    'service' => 'bancodobrasil',
                    'operation' => $operation,
                    'httpMethod' => strtoupper($method),
                    'url' => $this->loggedUrl($url),
                    'success' => false,
                    'durationMs' => IntegrationLogger::elapsedMs($startedAt),
                    'requestBody' => $data,
                    'errorMessage' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    private function send(string $method, string $url, array $data): Response
    {
        $options = ['headers' => ['Authorization' => 'Bearer ' . $this->accessToken, 'Accept' => 'application/json']];

        if ($method === 'get' || $method === 'delete') {
            return $this->httpClient->{$method}($url, [], $options);
        }

        return $this->httpClient->{$method}($url, $data, $options + ['type' => 'json']);
    }

    /**
     * A chave do aplicativo é credencial: não vai para o log.
     */
    private function loggedUrl(string $url): string
    {
        return str_replace((string)$this->setting('appKey'), '[REDACTED]', $url);
    }

    /**
     * Erros do Bacen vêm como `{title, detail, violacoes}`; os do gateway do
     * BB, como `{erros: [{mensagem}]}` ou `{error_description}` (OAuth).
     */
    private static function errorMessage(Response $response): string
    {
        $json = $response->getJson();

        $message = $json['detail']
            ?? $json['title']
            ?? $json['erros'][0]['mensagem']
            ?? $json['error_description']
            ?? $json['message']
            ?? null;

        if (!empty($json['violacoes'][0]['razao'])) {
            $message .= ' (' . $json['violacoes'][0]['propriedade'] . ': ' . $json['violacoes'][0]['razao'] . ')';
        }

        return $message ?? 'Erro na requisição Banco do Brasil (' . $response->getStatusCode() . ')';
    }

    /**
     * Configuração que falta só é erro quando é usada: o admin precisa montar
     * o serviço para remover um webhook mesmo com o .env incompleto.
     */
    private function setting(string $key): string
    {
        $value = $this->config[$key] ?? null;

        if (empty($value)) {
            throw new Exception("Banco do Brasil não configurado: falta \"$key\" (variáveis BB_* no .env).");
        }

        return (string)$value;
    }
}
