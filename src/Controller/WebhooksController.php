<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use App\Services\IntegrationLogger;
use App\Services\PixPaymentService;
use App\Services\SicoobService;
use Cake\Http\Exception\NotFoundException;
use Cake\Log\Log;

class WebhooksController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->autoRender = false;
    }

    /**
     * Recebido em POST /sicoob/webhook/{token}/pix — o Sicoob acrescenta "/pix"
     * ao final da URL cadastrada, por isso o token vive no path, não na query.
     *
     * O payload nunca é tratado como fonte de verdade: cada txid encontrado é
     * reconfirmado via GET /cob/{txid} antes de qualquer coisa ser marcada como paga.
     */
    public function pix(string $token)
    {
        $this->request->allowMethod(['post']);

        $rawBody = (string)$this->request->getBody();
        // O token é o segredo deste endpoint (vive no path, não em header/query),
        // então nunca deve ser persistido em claro no log.
        $loggedUrl = str_replace($token, '[REDACTED]', (string)$this->request->getRequestTarget());

        $webhook = $this->fetchTable('PixWebhooks')->find()->where(['token' => $token])->first();

        if (!$webhook) {
            IntegrationLogger::logHttp([
                'service' => 'sicoob',
                'operation' => 'sicoob.webhook_received',
                'direction' => 'inbound',
                'httpMethod' => 'POST',
                'url' => $loggedUrl,
                'success' => false,
                'requestBody' => $rawBody,
                'errorMessage' => 'Token de webhook inválido ou desconhecido.',
            ]);

            throw new NotFoundException();
        }

        $body = $this->request->getParsedBody();
        $pixEvents = is_array($body) ? ($body['pix'] ?? []) : [];

        if (empty($pixEvents) && is_array($body) && isset($body['txid']))
            $pixEvents = [$body];

        if (empty($pixEvents))
            Log::warning('Webhook Sicoob: payload sem formato reconhecido: ' . $rawBody);

        $pixPaymentService = new PixPaymentService(SicoobService::fromConfigure(), $this->fetchTable('PixTransactions'));
        $processedTxids = [];

        foreach ($pixEvents as $event) {
            $txid = $event['txid'] ?? null;

            if (!$txid)
                continue;

            $processedTxids[] = $txid;

            try {
                $pixPaymentService->confirmIfPaid($txid);
            } catch (\Exception $e) {
                Log::error('Webhook Sicoob: falha ao confirmar txid ' . $txid . ': ' . $e->getMessage());
            }
        }

        IntegrationLogger::logHttp([
            'service' => 'sicoob',
            'operation' => 'sicoob.webhook_received',
            'direction' => 'inbound',
            'httpMethod' => 'POST',
            'url' => $loggedUrl,
            'success' => true,
            'requestBody' => $rawBody,
            'context' => ['txids' => $processedTxids, 'events_count' => count($pixEvents)],
        ]);

        return $this->response->withStatus(200);
    }
}
