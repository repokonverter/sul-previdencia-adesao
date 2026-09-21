<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use App\Model\Table\BrokersTable;
use Cake\Cache\Cache;

/**
 * Endpoint público usado pelo formulário de adesão para validar o código de
 * corretor em tempo real (etapa de idade e valores). Um corretor válido é o
 * que libera a remoção dos riscos — ver RegistrationsController::save() e
 * SimulatorController, que revalidam o mesmo código no servidor: esta
 * consulta é conveniência de UI, nunca autoridade.
 */
class BrokersController extends AppController
{
    protected const RATE_LIMIT_MAX_REQUESTS = 60;
    protected const RATE_LIMIT_WINDOW_SECONDS = 60;

    protected BrokersTable $Brokers;

    public function initialize(): void
    {
        parent::initialize();

        $this->Brokers = $this->fetchTable('Brokers');
    }

    /**
     * Resposta: {valid, code, name, reason, message}
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

        $broker = $this->Brokers->findByCodeText($this->request->getQuery('code'));

        if ($broker === null) {
            return $this->respond([
                'valid' => false,
                'reason' => 'not_found',
                'message' => 'Código de corretor não encontrado.',
            ]);
        }

        if (!$broker->isUsable()) {
            return $this->respond([
                'valid' => false,
                'reason' => 'inactive',
                'message' => 'Este código de corretor não está mais disponível.',
            ]);
        }

        return $this->respond([
            'valid' => true,
            'code' => $broker->code,
            'name' => $broker->name,
        ]);
    }

    protected function respond(array $payload)
    {
        $this->set('result', $payload);
        $this->viewBuilder()->setOption('serialize', 'result');
    }

    /**
     * Mesmo limite simples por IP usado em PromotionalCodesController.
     */
    protected function withinRateLimit(): bool
    {
        $ip = $this->request->clientIp() ?: 'unknown';
        $key = 'broker_lookup_' . md5($ip);
        $hits = (int)Cache::read($key, 'rate_limit');

        if ($hits >= self::RATE_LIMIT_MAX_REQUESTS) {
            return false;
        }

        Cache::write($key, $hits + 1, 'rate_limit');

        return true;
    }
}
