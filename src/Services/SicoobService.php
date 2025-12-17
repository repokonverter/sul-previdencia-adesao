<?php

namespace App\Services;

use Cake\Http\Client;
use Exception;
use Cake\Log\Log;

class SicoobService
{
    private $httpClient;
    private $baseUrl;
    private $authUrl;
    private $clientId;
    private $certificate;
    private $privateKey;
    private $accessToken;

    public function __construct(array $config)
    {
        $this->baseUrl = $config['baseUrl'] ?? 'https://sandbox.sicoob.com.br/sicoob/sandbox/pix/api/v2';
        $this->authUrl = $config['authUrl'] ?? 'https://sandbox.sicoob.com.br/token';
        $this->clientId = $config['clientId'] ?? null;
        $this->certificate = $config['certificate'] ?? null;
        $this->privateKey = $config['privateKey'] ?? null;
        $this->accessToken = $config['fixedToken'] ?? null;

        $sslConfig = [];
        if ($this->certificate && $this->privateKey) {
            $sslConfig = [
                'ssl_cert' => $this->certificate,
                'ssl_key' => $this->privateKey,
            ];
        }

        $this->httpClient = new Client(array_merge($sslConfig, [
            'timeout' => 30,
        ]));
    }

    /**
     * Authenticate and retrieve Access Token
     * Request: POST /token
     * Content-Type: application/x-www-form-urlencoded
     * Body: grant_type=client_credentials&client_id={client_id}&scope=cob.write cob.read cobv.write cobv.read pix.write pix.read webhook.read webhook.write payload_location.write payload_location.read
     */
    private function authenticate()
    {
        // If we have a fixed token (and it was already set in constructor),
        // we might still land here if it expired (401).
        // However, if we utilize fixedToken, we typically don't auto-refresh via API
        // unless we also have the credentials to do so.
        // For now, if accessToken is present and we are treating it as "fixed" (passed in config),
        // strict adherence to "don't request" depends on whether user provided client_id too.
        // But user request said: "se eu estiver usando em homologação ele não solicitar o access token e usar o que eu cadastrar no .env".

        if (!empty($this->clientId)) {
            $this->_performAuthRequest();
        } else {
            // If we don't have clientId, we can't authenticate anyway.
            // Assume the current token is all we have.
            Log::warning("SicoobService: Attempted to authenticate but no Client ID provided. relying on fixed token if available.");
        }
    }

    private function _performAuthRequest()
    {
        try {
            $response = $this->httpClient->post($this->authUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'scope' => 'cob.write cob.read cobv.write cobv.read pix.write pix.read webhook.read webhook.write payload_location.write payload_location.read' // Scopes for Pix V2
            ], [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded']
            ]);

            if (!$response->isOk()) {
                Log::error('Sicoob Auth Error: ' . $response->getStringBody());
                throw new Exception('Falha na autenticação Sicoob: ' . $response->getStatusCode());
            }

            $body = $response->getJson();
            $this->accessToken = $body['access_token'] ?? null;

            if (!$this->accessToken) {
                throw new Exception('Token de acesso não retornado pelo Sicoob.');
            }
        } catch (Exception $e) {
            Log::error('Sicoob Service Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * General request method with auto-authentication
     */
    private function request(string $method, string $path, array $data = [], array $headers = [])
    {
        if (!$this->accessToken) {
            $this->authenticate();
        }

        $url = $this->baseUrl . $path;
        $defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'client_id' => $this->clientId, // Requesting client_id in headers
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $headers = array_merge($defaultHeaders, $headers);
        $options = ['headers' => $headers, 'type' => 'json'];

        try {
            $response = $this->httpClient->{$method}($url, $data, $options);

            // Handle 401 Unauthorized - Retry once ONLY if we have credentials to refresh
            if ($response->getStatusCode() === 401 && !empty($this->clientId)) {
                $this->authenticate();
                // Update header with new token
                $headers['Authorization'] = 'Bearer ' . $this->accessToken;
                $options['headers'] = $headers;
                $response = $this->httpClient->{$method}($url, $data, $options);
            } elseif ($response->getStatusCode() === 401) {
                throw new Exception('Sicoob: Não autorizado (401). Verifique o Token fixo.');
            }

            if (!$response->isOk()) {
                Log::warning("Sicoob API Error [{$method} {$url}]: " . $response->getStringBody());
                // Extract detailed error if available
                $errorBody = $response->getJson();
                $errorMsg = $errorBody['mensagem'] ?? 'Erro na requisição Sicoob (' . $response->getStatusCode() . ')';
                throw new Exception($errorMsg);
            }

            return $response->getJson();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function createCob(array $data): array
    {
        return $this->request('post', '/cob', $data);
    }

    /**
     * @param string $txid
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function createCobWithTxid(string $txid, array $data): array
    {
        return $this->request('put', "/cob/{$txid}", $data);
    }

    /**
     * @param string $txid
     * @return array
     * @throws Exception
     */
    public function getCob(string $txid): array
    {
        return $this->request('get', "/cob/{$txid}");
    }

    /**
     * @param array $params (inicio, fim, cpf, cnpj, locationPresent, status, paginacao)
     * @return array
     * @throws Exception
     */
    public function listCob(array $params): array
    {
        $queryString = http_build_query($params);
        return $this->request('get', "/cob?{$queryString}");
    }

    /**
     * @param string $txid
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function updateCob(string $txid, array $data): array
    {
        return $this->request('patch', "/cob/{$txid}", $data);
    }

    /**
     * @param string $txid
     * @return string|null
     * @throws Exception
     */
    public function cobImage(string $txid): string|null
    {
        return $this->request('get', "/cob/{$txid}/imagem");
    }

    /**
     * @param string $txid
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function createCobv(string $txid, array $data): array
    {
        return $this->request('put', "/cobv/{$txid}", $data);
    }

    /**
     * @param string $txid
     * @return array
     * @throws Exception
     */
    public function getCobv(string $txid): array
    {
        return $this->request('get', "/cobv/{$txid}");
    }

    /**
     * @param array $params (inicio, fim, cpf, cnpj, locationPresent, status, paginacao)
     * @return array
     * @throws Exception
     */
    public function listCobv(array $params): array
    {
        $queryString = http_build_query($params);
        return $this->request('get', "/cobv?{$queryString}");
    }

    /**
     * @param array $params (inicio, fim, txid, cpf, cnpj, status, pagina, itensPorPagina)
     * @return array
     * @throws Exception
     */
    public function listPix(array $params): array
    {
        $queryString = http_build_query($params);
        return $this->request('get', "/pix?{$queryString}");
    }

    /**
     * @param string $e2eid
     * @return array
     * @throws Exception
     */
    public function getPix(string $e2eid): array
    {
        return $this->request('get', "/pix/{$e2eid}");
    }

    /**
     * @param string $e2eid
     * @param string $id
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function requestDevolucao(string $e2eid, string $id, array $data): array
    {
        return $this->request('put', "/pix/{$e2eid}/devolucao/{$id}", $data);
    }

    /**
     * @param string $e2eid
     * @param string $id
     * @return array
     * @throws Exception
     */
    public function getDevolucao(string $e2eid, string $id): array
    {
        return $this->request('get', "/pix/{$e2eid}/devolucao/{$id}");
    }

    /**
     * @param string $chave
     * @param string $webhookUrl
     * @return array
     * @throws Exception
     */
    public function configureWebhook(string $chave, string $webhookUrl): array
    {
        return $this->request('put', "/webhook/{$chave}", ['webhookUrl' => $webhookUrl]);
    }

    /**
     * @param string $chave
     * @return array
     * @throws Exception
     */
    public function getWebhook(string $chave): array
    {
        return $this->request('get', "/webhook/{$chave}");
    }

    /**
     * @param string $chave
     * @return array
     * @throws Exception
     */
    public function deleteWebhook(string $chave): array
    {
        return $this->request('delete', "/webhook/{$chave}");
    }

    /**
     * @param array $data (tipoCob)
     * @return array
     * @throws Exception
     */
    public function createPayloadLocation(array $data): array
    {
        return $this->request('post', '/loc', $data);
    }

    /**
     * @param string $id
     * @return array
     * @throws Exception
     */
    public function getPayloadLocation(string $id): array
    {
        return $this->request('get', "/loc/{$id}");
    }

    /**
     * @param array $params (inicio, fim, txIdPresente, tipoCob, paginacao)
     * @return array
     * @throws Exception
     */
    public function listPayloadLocations(array $params): array
    {
        $queryString = http_build_query($params);
        return $this->request('get', "/loc?{$queryString}");
    }

    /**
     * @param string $id
     * @return array
     * @throws Exception
     */
    public function detachLocation(string $id): array
    {
        return $this->request('delete', "/loc/{$id}/txid");
    }

    /**
     * @param string $id
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function createBatchCobv(string $id, array $data): array
    {
        return $this->request('put', "/lotecobv/{$id}", $data);
    }

    /**
     * @param string $id
     * @return array
     * @throws Exception
     */
    public function getBatchCobv(string $id): array
    {
        return $this->request('get', "/lotecobv/{$id}");
    }

    /**
     * @param array $params (inicio, fim, paginacao)
     * @return array
     * @throws Exception
     */
    public function listBatchCobv(array $params): array
    {
        $queryString = http_build_query($params);
        return $this->request('get', "/lotecobv?{$queryString}");
    }
}
