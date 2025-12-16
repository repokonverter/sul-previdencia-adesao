<?php

namespace App\Services;

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
                'Authorization' => $accessToken,
            ]
        ]);
    }

    private function _request(string $method, string $path, array $payload = [])
    {
        $url = $this->baseUrl . $path;
        $options = ['type' => 'json'];

        $method = strtolower($method);

        if ($method === 'get') {
            if (!empty($payload))
                $url .= '?' . http_build_query($payload);

            $payload = [];
        } else
            $payload = json_encode($payload);

        try {
            $response = $this->httpClient->{$method}(
                $url,
                $payload,
                $options
            );
        } catch (\Exception $e) {
            throw new Exception('Falha na comunicação com Clicksign: ' . $e->getMessage());
        }

        if ($response->isOk())
            return [
                'success' => true,
                ...$response->getJson()
            ];

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

    public function getEnvelopes(array $payload = [])
    {
        return $this->_request('get', '/envelopes', $payload);
    }

    public function createDocument(string $documentId, array $attributes = [])
    {
        $payload = [
            'data' => [
                'type' => 'documents',
                'attributes' => $attributes,
            ],
        ];
        return $this->_request('post', "/envelopes/{$documentId}/documents", $payload);
    }

    public function getDocuments(string $documentId, array $payload = [])
    {
        return $this->_request('get', "/envelopes/{$documentId}/documents", $payload);
    }

    public function updateEnvelope(string $key, array $attributes): array
    {
        $payload = [
            'data' => [
                'type' => 'envelopes',
                'id' => $key,
                'attributes' => $attributes,
            ],
        ];
        return $this->_request('patch', "/envelopes/{$key}", $payload);
    }

    public function getEnvelope(string $key): array
    {
        return $this->_request('get', "/envelopes/{$key}");
    }

    public function deleteEnvelope(string $key): array
    {
        return $this->_request('delete', "/envelopes/{$key}");
    }

    public function cancelEnvelope(string $key): array
    {
        $payload = [
            'data' => [
                'type' => 'envelopes',
                'id' => $key,
                'attributes' => [
                    'status' => 'canceled'
                ],
            ],
        ];
        return $this->_request('patch', "/envelopes/{$key}", $payload);
    }

    public function createDocumentByTemplate(string $templateKey, array $attributes): array
    {
        $payload = [
            'data' => [
                'type' => 'documents',
                'attributes' => [
                    'template' => [
                        'data' => [
                            'type' => 'templates',
                            'id' => $templateKey
                        ]
                    ]
                ]
            ]
        ];

        // Merge extra attributes if needed, though template creation usually relies on the template structure
        if (!empty($attributes)) {
            $payload['data']['attributes'] = array_merge($payload['data']['attributes'], $attributes);
        }

        return $this->_request('post', '/templates/' . $templateKey . '/documents', $payload);
    }

    public function updateDocument(string $envelopeId, string $documentId, array $attributes): array
    {
        $payload = [
            'data' => [
                'type' => 'documents',
                'id' => $documentId,
                'attributes' => $attributes,
            ],
        ];
        return $this->_request('patch', "/envelopes/{$envelopeId}/documents/{$documentId}", $payload);
    }

    public function getDocument(string $envelopeId, string $documentId): array
    {
        return $this->_request('get', "/envelopes/{$envelopeId}/documents/{$documentId}");
    }

    public function deleteDocument(string $envelopeId, string $documentId): array
    {
        return $this->_request('delete', "/envelopes/{$envelopeId}/documents/{$documentId}");
    }

    public function getSigners(string $envelopeKey): array
    {
        return $this->_request('get', "/envelopes/{$envelopeKey}/signers");
    }

    public function createSigner(string $envelopeKey, array $attributes): array
    {
        $payload = [
            'data' => [
                'type' => 'signers',
                'attributes' => $attributes,
            ],
        ];
        return $this->_request('post', "/envelopes/{$envelopeKey}/signers", $payload);
    }

    public function getSigner(string $key): array
    {
        return $this->_request('get', "/signers/{$key}");
    }

    public function updateSigner(string $key, array $attributes): array
    {
        $payload = [
            'data' => [
                'type' => 'signers',
                'id' => $key,
                'attributes' => $attributes,
            ],
        ];
        return $this->_request('patch', "/signers/{$key}", $payload);
    }

    public function deleteSigner(string $key): array
    {
        return $this->_request('delete', "/signers/{$key}");
    }

    public function createRequirement(string $key, array $attributes, array $relationships): array
    {
        $payload = [
            'data' => [
                'type' => 'requirements',
                'attributes' => $attributes,
                'relationships' => $relationships,
            ],
        ];
        return $this->_request('post', "/envelopes/{$key}/requirements", $payload);
    }

    public function getRequirements(string $key): array
    {
        return $this->_request('get', "/envelopes/{$key}/requirements");
    }

    public function deleteRequirement(string $key): array
    {
        return $this->_request('delete', "/envelopes/{$key}/requirements");
    }

    public function getObservers(string $envelopeKey): array
    {
        return $this->_request('get', "/envelopes/{$envelopeKey}/observers");
    }

    public function createObserver(string $envelopeKey, array $attributes): array
    {
        $payload = [
            'data' => [
                'type' => 'observers',
                'attributes' => $attributes,
            ],
        ];
        return $this->_request('post', "/envelopes/{$envelopeKey}/observers", $payload);
    }

    public function deleteObserver(string $key): array
    {
        return $this->_request('delete', "/observers/{$key}");
    }

    public function notifySigner(string $requestKey): array
    {
        // Docs: Notificar um Signatário
        // Path usually /notifications with signer_request_key or similar
        // Checking doc structure: "Notificar um Signatário" -> POST /notifications
        $payload = [
            'data' => [
                'type' => 'notifications',
                'attributes' => [
                    'request_key' => $requestKey
                ],
            ],
        ];
        return $this->_request('post', "/notifications", $payload);
    }

    public function notifyEnvelopeSigners(string $envelopeKey, array $attributes = []): array
    {
        $payload = [
            'data' => [
                'type' => 'notifications',
                'attributes' => array_merge(['envelope_id' => $envelopeKey], $attributes),
            ],
        ];
        return $this->_request('post', "/notifications", $payload);
    }

    public function getTemplates(): array
    {
        return $this->_request('get', "/templates");
    }

    public function createTemplate(array $attributes): array
    {
        $payload = [
            'data' => [
                'type' => 'templates',
                'attributes' => $attributes,
            ],
        ];
        return $this->_request('post', "/templates", $payload);
    }

    public function getTemplate(string $key): array
    {
        return $this->_request('get', "/templates/{$key}");
    }

    public function deleteTemplate(string $key): array
    {
        return $this->_request('delete', "/templates/{$key}");
    }

    public function getFolders(): array
    {
        return $this->_request('get', "/folders");
    }

    public function createFolder(array $attributes): array
    {
        $payload = [
            'data' => [
                'type' => 'folders',
                'attributes' => $attributes,
            ],
        ];
        return $this->_request('post', "/folders", $payload);
    }

    public function getFolder(string $key): array
    {
        return $this->_request('get', "/folders/{$key}");
    }

    public function deleteFolder(string $key): array
    {
        return $this->_request('delete', "/folders/{$key}");
    }

    public function createWhatsAppAcceptance(array $attributes): array
    {
        // Docs: Criar um Aceite via Whatsapp
        $payload = [
            'data' => [
                'type' => 'whatsapp_accepts',
                'attributes' => $attributes,
            ],
        ];
        return $this->_request('post', "/whatsapp_accepts", $payload);
    }
}
