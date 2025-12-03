<?php

namespace App\Service;

use Cake\Http\Client;
use Exception;

class ClicksignService
{
    private $httpClient;
    private $baseUrl;
    private $accessToken;

    public function __construct(string $baseUrl, string $accessToken)
    {
        $this->baseUrl = $baseUrl;
        $this->accessToken = $accessToken;
        $this->httpClient = new Client([
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/vnd.api+json',
                'Authorization' => 'Bearer ' . $accessToken,
            ]
        ]);
    }

    private function _request(string $method, string $path, array $payload = [])
    {
        $url = $this->baseUrl . $path;
        $options = ['type' => 'json'];

        $method = strtolower($method);

        if ($method === 'get') {
            if (!empty($params))
                $url .= '?' . http_build_query($params);

            $payload = null;
        } else
            $payload = json_encode(['data' => ['attributes' => $params]]);

        try {
            $response = $this->httpClient->{$method}(
                $url,
                $payload,
                $options
            );
        } catch (\Exception $e) {
            throw new Exception('Falha na comunicação com Clicksign: ' . $e->getMessage());
        }

        if ($response->isOk() || $response->isCreated()) {
            return $response->getJson();
        }

        $errorMessage = 'Erro Clicksign. Status: ' . $response->getStatusCode();

        $errorBody = $response->getJson();

        if (isset($errorBody['errors'])) {
            $details = array_map(function ($error) {
                return $error['detail'] ?? 'Erro desconhecido.';
            }, $errorBody['errors']);
            $errorMessage .= '. Detalhes: ' . implode('; ', $details);
        }

        throw new Exception($errorMessage);
    }

    public function createEnvelope(
        string $name,
        ?string $default_subject = null,
        ?string $default_message = null,
        array $extraAttributes = []
    ): array {
        $attributes = array_merge([
            'name' => $name,
            'default_subject' => $default_subject,
            'default_message' => $default_message,
            'locale' => 'pt-BR',
            'auto_close' => true,
            'remind_interval' => '3',
            'block_after_refusal' => false,
        ], $extraAttributes);

        $attributes = array_filter($attributes, fn($value) => $value !== null);
        $payload = [
            'data' => [
                'type' => 'envelopes',
                'attributes' => $attributes,
            ],
        ];

        return $this->_request('post', '/envelopes', $payload);
    }

    public function getEnvelopes(array $payload = []) {
        return $this->_request('get', '/envelopes', $payload);
    }

    public function createDocument(string $documentId, array $payload = [])
    {
        return $this->_request('post', "/envelopes/{$documentId}/documents", $data);
    }

    public function getDocuments(string $documentId, array $payload = [])
    {
        return $this->_request('get', "/envelopes/{$documentId}/documents", $data);
    }
}
