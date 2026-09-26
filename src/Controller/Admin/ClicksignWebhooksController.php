<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Services\ClicksignService;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Utility\Security;

/**
 * Cadastro do webhook da Clicksign.
 *
 * Mesmo padrão de Admin\PixWebhooksController: registra na API de terceiro,
 * grava localmente só depois de o cadastro remoto ter sucesso, e remove dos
 * dois lados. A conta da Clicksign é única, então normalmente há uma linha só
 * aqui -- mas nada impede reemitir se o token precisar trocar.
 */
class ClicksignWebhooksController extends AppController
{
    public function index()
    {
        $webhooks = $this->fetchTable('ClicksignWebhooks')->find()->orderBy(['created' => 'DESC'])->all();

        $this->set(compact('webhooks'));
    }

    public function add()
    {
        $clicksignWebhooks = $this->fetchTable('ClicksignWebhooks');
        $webhook = $clicksignWebhooks->newEmptyEntity();

        if ($this->request->is('post')) {
            $token = bin2hex(Security::randomBytes(20));
            $url = Router::url('/clicksign/webhook/' . $token, true);

            try {
                $clicksign = new ClicksignService(
                    Configure::read('Clicksign.baseUrl'),
                    Configure::read('Clicksign.accessToken')
                );

                $response = $clicksign->createWebhook([
                    'endpoint' => $url,
                    // "close" cobre o envelope inteiro (todo mundo assinou),
                    // "document_closed" cobre cada documento -- a doc da
                    // Clicksign descreve como "pronto para download". Os dois
                    // disparam o mesmo tratamento aqui: o payload nunca é
                    // fonte de verdade, é só gatilho para reconferir por GET.
                    'events' => ['close', 'document_closed'],
                    'status' => 'active',
                ]);

                $webhook = $clicksignWebhooks->patchEntity($webhook, [
                    'clicksign_webhook_id' => $response['data']['id'],
                    'token' => $token,
                    'url' => $url,
                ]);

                if ($clicksignWebhooks->save($webhook)) {
                    $this->Flash->success('Webhook cadastrado na Clicksign com sucesso.');

                    return $this->redirect(['action' => 'index']);
                }

                $this->Flash->error('Webhook criado na Clicksign, mas houve falha ao salvar localmente. Verifique os logs.');
            } catch (\Exception $e) {
                $this->Flash->error('Falha ao cadastrar webhook na Clicksign: ' . $e->getMessage());
            }
        }

        $this->set(compact('webhook'));
    }

    public function delete($id)
    {
        $this->request->allowMethod(['post', 'delete']);

        $clicksignWebhooks = $this->fetchTable('ClicksignWebhooks');
        $webhook = $clicksignWebhooks->get($id);

        try {
            $clicksign = new ClicksignService(
                Configure::read('Clicksign.baseUrl'),
                Configure::read('Clicksign.accessToken')
            );
            $clicksign->deleteWebhook($webhook->clicksign_webhook_id);
        } catch (\Exception $e) {
            $this->Flash->error('Falha ao remover webhook na Clicksign (removendo apenas localmente): ' . $e->getMessage());
        }

        if ($clicksignWebhooks->delete($webhook))
            $this->Flash->success('Webhook removido.');
        else
            $this->Flash->error('Erro ao excluir o registro local.');

        return $this->redirect(['action' => 'index']);
    }
}
