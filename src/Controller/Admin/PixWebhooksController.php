<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Services\BenefitPlans;
use App\Services\PixPaymentService;
use Cake\Utility\Security;

/**
 * Um webhook por conta Pix: cada plano de benefícios recebe na sua (ver
 * config/plans.php), e o webhook gravado sabe de qual plano é, para o
 * WebhooksController reconfirmar o pagamento no banco certo.
 */
class PixWebhooksController extends AppController
{
    public function index()
    {
        $webhooks = $this->fetchTable('PixWebhooks')->find()->orderBy(['created' => 'DESC'])->all();
        $benefitPlans = BenefitPlans::options();

        $this->set(compact('webhooks', 'benefitPlans'));
    }

    public function add()
    {
        $pixWebhooks = $this->fetchTable('PixWebhooks');
        $webhook = $pixWebhooks->newEmptyEntity();

        if ($this->request->is('post')) {
            $slug = (string)$this->request->getData('benefit_plan');

            if (!BenefitPlans::exists($slug)) {
                $this->Flash->error('Selecione o plano.');

                return $this->redirect(['action' => 'add']);
            }

            $plan = BenefitPlans::get($slug);
            $chave = $this->request->getData('chave') ?: PixPaymentService::pixKeyFor($plan);
            $token = bin2hex(Security::randomBytes(20));
            // No host do plano, e não no desta requisição: o admin responde
            // nos dois, e a URL cadastrada no banco tem que sobreviver a quem
            // a cadastrou.
            $url = $plan->url('/' . $plan->pix['provider'] . '/webhook/' . $token);

            try {
                PixPaymentService::gatewayFor($plan)->configureWebhook($chave, $url);

                $webhook = $pixWebhooks->patchEntity($webhook, [
                    'chave' => $chave,
                    'token' => $token,
                    'url' => $url,
                    'benefit_plan' => $plan->slug,
                ]);

                if ($pixWebhooks->save($webhook)) {
                    $this->Flash->success('Webhook cadastrado no banco com sucesso.');

                    return $this->redirect(['action' => 'index']);
                }

                $this->Flash->error('Webhook criado no banco, mas houve falha ao salvar localmente. Verifique os logs.');
            } catch (\Exception $e) {
                $this->Flash->error('Falha ao cadastrar webhook no banco: ' . $e->getMessage());
            }
        }

        $benefitPlans = BenefitPlans::options();
        $defaultKeys = array_map(
            fn ($plan) => PixPaymentService::pixKeyFor($plan),
            BenefitPlans::all()
        );

        $this->set(compact('webhook', 'benefitPlans', 'defaultKeys'));
    }

    public function delete($id)
    {
        $this->request->allowMethod(['post', 'delete']);

        $pixWebhooks = $this->fetchTable('PixWebhooks');
        $webhook = $pixWebhooks->get($id);

        try {
            PixPaymentService::gatewayFor(BenefitPlans::get($webhook->benefit_plan))->deleteWebhook($webhook->chave);
        } catch (\Exception $e) {
            $this->Flash->error('Falha ao remover webhook no banco (removendo apenas localmente): ' . $e->getMessage());
        }

        if ($pixWebhooks->delete($webhook))
            $this->Flash->success('Webhook removido.');
        else
            $this->Flash->error('Erro ao excluir o registro local.');

        return $this->redirect(['action' => 'index']);
    }
}
