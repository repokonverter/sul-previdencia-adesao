<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Entity\ClicksignData;
use App\Model\Table\ClicksignDatasTable;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Exception;

/**
 * Confirma se um envelope foi assinado consultando a Clicksign diretamente.
 *
 * Mesmo padrão de PixPaymentService::confirmIfPaid(): o payload de um webhook
 * nunca é tratado como fonte de verdade, só como gatilho para chamar este
 * serviço, que sempre revalida por GET antes de marcar qualquer coisa como
 * assinada. O botão manual do admin chama exatamente o mesmo caminho.
 */
class ClicksignSignatureChecker
{
    public function __construct(
        private readonly ClicksignDatasTable $clicksignDatas,
        private readonly ClicksignService $clicksign,
    ) {
    }

    public static function fromConfigure(ClicksignDatasTable $clicksignDatas): self
    {
        return new self(
            $clicksignDatas,
            new ClicksignService(
                Configure::read('Clicksign.baseUrl'),
                Configure::read('Clicksign.accessToken')
            )
        );
    }

    /**
     * @return array{found: bool, signed?: bool, status?: string}
     */
    public function refreshLatestFor(int $adhesionId): array
    {
        $attempt = $this->clicksignDatas->latestFor($adhesionId);

        if ($attempt === null) {
            return ['found' => false];
        }

        return $this->refresh($attempt);
    }

    /**
     * @return array{found: bool, signed?: bool, status?: string}
     */
    public function refresh(ClicksignData $attempt): array
    {
        // Cancelada não se torna assinada: o envelope corrente é o que
        // importa, e uma tentativa cancelada não é mais consultável como se
        // ainda estivesse em curso.
        if ($attempt->canceled_at !== null) {
            return ['found' => true, 'signed' => $attempt->isSigned(), 'status' => $attempt->status];
        }

        $this->clicksign->forAdhesion($attempt->adhesion_initial_data_id);

        try {
            $response = $this->clicksign->getEnvelope($attempt->envelope_id);
        } catch (Exception $e) {
            return ['found' => false];
        }

        $status = $response['data']['attributes']['status'] ?? null;

        // "closed" é o vocabulário da Clicksign para o envelope inteiro
        // concluído -- todos os signatários cumpriram suas exigências.
        if ($status === 'closed' && !$attempt->isSigned()) {
            $this->clicksignDatas->save($this->clicksignDatas->patchEntity($attempt, [
                'status' => ClicksignData::STATUS_SIGNED,
                'signed_at' => DateTime::now(),
            ]));
        }

        return ['found' => true, 'signed' => $status === 'closed', 'status' => $status];
    }
}
