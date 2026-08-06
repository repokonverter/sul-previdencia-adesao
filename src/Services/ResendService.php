<?php

declare(strict_types=1);

namespace App\Services;

use Cake\Http\Client;
use Cake\Log\Log;
use Exception;

class ResendService
{
    private Client $httpClient;
    private string $apiKey;
    private string $fromAddress;
    private string $fromName;

    public static function fromConfigure(): self
    {
        return new self([
            'apiKey' => \Cake\Core\Configure::read('Resend.apiKey'),
            'fromAddress' => \Cake\Core\Configure::read('Resend.fromAddress'),
            'fromName' => \Cake\Core\Configure::read('Resend.fromName'),
        ]);
    }

    public function __construct(array $config)
    {
        $this->apiKey = $config['apiKey'] ?? '';
        $this->fromAddress = $config['fromAddress'] ?? '';
        $this->fromName = $config['fromName'] ?? '';

        $this->httpClient = new Client(['timeout' => 15]);
    }

    /**
     * @param array $to Lista de e-mails destinatários
     * @param string $subject
     * @param string $html
     * @return bool
     */
    public function send(array $to, string $subject, string $html): bool
    {
        if (empty($this->apiKey)) {
            Log::warning('ResendService: RESEND_API_KEY não configurado. E-mail não enviado. Assunto: ' . $subject);

            return false;
        }

        try {
            $response = $this->httpClient->post('https://api.resend.com/emails', json_encode([
                'from' => "{$this->fromName} <{$this->fromAddress}>",
                'to' => array_values($to),
                'subject' => $subject,
                'html' => $html,
            ]), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'type' => 'json',
            ]);

            if (!$response->isOk()) {
                Log::error('Resend API Error: ' . $response->getStringBody());

                return false;
            }

            return true;
        } catch (Exception $e) {
            Log::error('Resend Service Error: ' . $e->getMessage());

            return false;
        }
    }
}
