<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
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

        $webhook = $this->fetchTable('PixWebhooks')->find()->where(['token' => $token])->first();

        if (!$webhook)
            throw new NotFoundException();

        $body = $this->request->getParsedBody();
        $pixEvents = is_array($body) ? ($body['pix'] ?? []) : [];

        if (empty($pixEvents) && is_array($body) && isset($body['txid']))
            $pixEvents = [$body];

        if (empty($pixEvents))
            Log::warning('Webhook Sicoob: payload sem formato reconhecido: ' . $this->request->getBody());

        $pixPaymentService = new PixPaymentService(SicoobService::fromConfigure(), $this->fetchTable('PixTransactions'));

        foreach ($pixEvents as $event) {
            $txid = $event['txid'] ?? null;

            if (!$txid)
                continue;

            try {
                $pixPaymentService->confirmIfPaid($txid);
            } catch (\Exception $e) {
                Log::error('Webhook Sicoob: falha ao confirmar txid ' . $txid . ': ' . $e->getMessage());
            }
        }

        return $this->response->withStatus(200);
    }
}
