<?php

declare(strict_types=1);

namespace App\Services;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Log\Log;
use Cake\Mailer\Message;
use Cake\Mailer\Transport\SmtpTransport;
use Exception;
use Throwable;

class ResendService
{
    private Client $httpClient;
    private string $apiKey;
    private string $fromAddress;
    private string $fromName;
    private ?int $adhesionId = null;

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
     * Associates subsequent calls with an adhesion, so their log entries
     * show up in that adhesion's "Integrações" tab in the admin.
     */
    public function forAdhesion(?int $adhesionId): static
    {
        $this->adhesionId = $adhesionId;

        return $this;
    }

    /**
     * @param array $to Lista de e-mails destinatários
     * @param string $subject
     * @param string $html
     * @return bool
     */
    public function send(array $to, string $subject, string $html): bool
    {
        // Sem RESEND_API_KEY (caso local padrão, ver config/.env.example), os
        // e-mails vão por SMTP para o Mailpit em vez de saírem para o Resend.
        if (empty($this->apiKey)) {
            return $this->sendViaMailpit($to, $subject, $html);
        }

        $startedAt = microtime(true);

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

            $success = $response->isOk();
            $errorMessage = null;

            if (!$success) {
                Log::error('Resend API Error: ' . $response->getStringBody());
                $errorMessage = $response->getStringBody();
            }

            IntegrationLogger::logHttp([
                'adhesionId' => $this->adhesionId,
                'service' => 'resend',
                'operation' => 'resend.send',
                'httpMethod' => 'POST',
                'url' => 'https://api.resend.com/emails',
                'statusCode' => $response->getStatusCode(),
                'success' => $success,
                'durationMs' => IntegrationLogger::elapsedMs($startedAt),
                'requestBody' => ['to' => $to, 'subject' => $subject, 'html' => $html],
                'responseBody' => $response->getStringBody(),
                'errorMessage' => $errorMessage,
            ]);

            return $success;
        } catch (Exception $e) {
            Log::error('Resend Service Error: ' . $e->getMessage());

            IntegrationLogger::logHttp([
                'adhesionId' => $this->adhesionId,
                'service' => 'resend',
                'operation' => 'resend.send',
                'httpMethod' => 'POST',
                'url' => 'https://api.resend.com/emails',
                'success' => false,
                'durationMs' => IntegrationLogger::elapsedMs($startedAt),
                'requestBody' => ['to' => $to, 'subject' => $subject],
                'errorMessage' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envia por SMTP para o Mailpit (padrão local: 127.0.0.1:1025, sem
     * autenticação), para inspecionar e-mails em http://localhost:8025 sem
     * gastar envios reais do Resend.
     */
    private function sendViaMailpit(array $to, string $subject, string $html): bool
    {
        $host = Configure::read('Mailpit.host', '127.0.0.1');
        $port = (int)Configure::read('Mailpit.port', 1025);
        $startedAt = microtime(true);

        try {
            $message = new Message();
            $message->setFrom([$this->fromAddress => $this->fromName])
                ->setTo(array_values($to))
                ->setSubject($subject)
                ->setEmailFormat(Message::MESSAGE_HTML)
                ->setBodyHtml($html);

            $transport = new SmtpTransport([
                'host' => $host,
                'port' => $port,
                'timeout' => 5,
                'tls' => false,
            ]);

            $transport->send($message);

            IntegrationLogger::logHttp([
                'adhesionId' => $this->adhesionId,
                'service' => 'resend',
                'operation' => 'resend.send',
                'httpMethod' => 'SMTP',
                'url' => "{$host}:{$port}",
                'success' => true,
                'durationMs' => IntegrationLogger::elapsedMs($startedAt),
                'requestBody' => ['to' => $to, 'subject' => $subject, 'html' => $html],
                'context' => ['transport' => 'mailpit'],
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('Mailpit Service Error: ' . $e->getMessage());

            IntegrationLogger::logHttp([
                'adhesionId' => $this->adhesionId,
                'service' => 'resend',
                'operation' => 'resend.send',
                'httpMethod' => 'SMTP',
                'url' => "{$host}:{$port}",
                'success' => false,
                'durationMs' => IntegrationLogger::elapsedMs($startedAt),
                'requestBody' => ['to' => $to, 'subject' => $subject],
                'errorMessage' => $e->getMessage(),
                'context' => ['transport' => 'mailpit'],
            ]);

            return false;
        }
    }
}
