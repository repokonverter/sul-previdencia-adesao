<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use App\Model\Table\PromotionalCodesTable;
use Cake\Cache\Cache;
use Cake\Routing\Router;

/**
 * Endpoint público usado pelo formulário de adesão para consultar um código
 * promocional.
 */
class PromotionalCodesController extends AppController
{
    protected const RATE_LIMIT_MAX_REQUESTS = 60;
    protected const RATE_LIMIT_WINDOW_SECONDS = 60;

    protected PromotionalCodesTable $PromotionalCodes;

    public function initialize(): void
    {
        parent::initialize();

        $this->PromotionalCodes = $this->fetchTable('PromotionalCodes');
    }

    /**
     * Consulta um código promocional e devolve o resultado da validação.
     *
     * Resposta: {valid, code, partnerName, logoUrl, color, reason, message}
     */
    public function validate()
    {
        $this->request->allowMethod(['get', 'ajax']);
        $this->viewBuilder()->setClassName('Json');

        if (!$this->withinRateLimit()) {
            $this->response = $this->response->withStatus(429);

            return $this->respond([
                'valid' => false,
                'reason' => 'rate_limited',
                'message' => 'Muitas consultas. Aguarde um instante e tente novamente.',
            ]);
        }

        $promotionalCode = $this->PromotionalCodes->findByCodeText($this->request->getQuery('code'));

        if ($promotionalCode === null) {
            return $this->respond([
                'valid' => false,
                'reason' => 'not_found',
                'message' => 'Código promocional não encontrado.',
            ]);
        }

        $reason = $promotionalCode->unusableReason();

        if ($reason !== null) {
            return $this->respond([
                'valid' => false,
                'reason' => $reason,
                'message' => $this->messageForReason($reason, $promotionalCode),
            ]);
        }

        $partner = $promotionalCode->partner;

        // Vínculo selecionado no formulário restringe o código: um código de
        // outro parceiro (ou de nenhum vínculo) não pode ser usado para
        // assinar a Declaração de um vínculo diferente do seu.
        $associationId = $this->request->getQuery('associationId');

        if ($associationId !== null && $associationId !== '' && (int)$associationId !== $partner->id) {
            // A mensagem cita o vínculo que a pessoa selecionou, não o
            // parceiro a quem o código pertence de fato — é contra aquele
            // que a comparação está sendo feita.
            $selectedAssociation = $this->PromotionalCodes->Partners->find()
                ->select(['name'])
                ->where(['id' => (int)$associationId])
                ->first();

            return $this->respond([
                'valid' => false,
                'reason' => 'wrong_partner',
                'message' => sprintf(
                    'Este código não pertence a %s.',
                    $selectedAssociation->name ?? 'este vínculo'
                ),
            ]);
        }

        return $this->respond([
            'valid' => true,
            'code' => $promotionalCode->code,
            'partnerName' => $partner->name,
            'color' => $partner->color,
            'logoUrl' => $partner->has_logo
                ? Router::url(['controller' => 'Partners', 'action' => 'logo', $partner->id])
                : null,
            // Código pertence a um vínculo associativo, mas a pergunta "Possuí
            // vínculo associativo?" ainda estava em "não": o formulário
            // corrige a resposta sozinho em vez de bloquear (cobre o caso do
            // link ?promo=CODIGO, que não passa pela pergunta).
            'autoAssociation' => $associationId === null && $partner->is_association ? [
                'id' => $partner->id,
                'name' => $partner->name,
            ] : null,
        ]);
    }

    protected function messageForReason(string $reason, $promotionalCode): string
    {
        if ($reason === 'expired') {
            return sprintf('Este código expirou em %s.', $promotionalCode->valid_until->i18nFormat('dd/MM/yyyy'));
        }

        if ($reason === 'not_started') {
            return sprintf(
                'Este código só é válido a partir de %s.',
                $promotionalCode->valid_from->i18nFormat('dd/MM/yyyy')
            );
        }

        return 'Este código promocional não está mais disponível.';
    }

    protected function respond(array $payload)
    {
        $this->set('result', $payload);
        $this->viewBuilder()->setOption('serialize', 'result');
    }

    /**
     * Limite simples por IP. O endpoint é enumerável por natureza, mas como o
     * código não concede benefício algum, basta conter automação grosseira.
     */
    protected function withinRateLimit(): bool
    {
        $ip = $this->request->clientIp() ?: 'unknown';
        $key = 'promo_lookup_' . md5($ip);
        $hits = (int)Cache::read($key, 'rate_limit');

        if ($hits >= self::RATE_LIMIT_MAX_REQUESTS) {
            return false;
        }

        Cache::write($key, $hits + 1, 'rate_limit');

        return true;
    }
}
