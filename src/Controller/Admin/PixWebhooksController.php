<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Services\SicoobService;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Utility\Security;

class PixWebhooksController extends AppController
{
    public function index()
    {
        $webhooks = $this->fetchTable('PixWebhooks')->find()->orderBy(['created' => 'DESC'])->all();

        $this->set(compact('webhooks'));
    }

    public function add()
    {
        $pixWebhooks = $this->fetchTable('PixWebhooks');
        $webhook = $pixWebhooks->newEmptyEntity();

        if ($this->request->is('post')) {
            $chave = $this->request->getData('chave') ?: Configure::read('Sicoob.pixKey');
            $token = bin2hex(Security::randomBytes(20));
            $url = Router::url('/sicoob/webhook/' . $token, true);

            try {
                SicoobService::fromConfigure()->configureWebhook($chave, $url);

                $webhook = $pixWebhooks->patchEntity($webhook, [
                    'chave' => $chave,
                    'token' => $token,
                    'url' => $url,
                ]);

                if ($pixWebhooks->save($webhook)) {
                    $this->Flash->success('Webhook cadastrado no Sicoob com sucesso.');

                    return $this->redirect(['action' => 'index']);
                }

                $this->Flash->error('Webhook criado no Sicoob, mas houve falha ao salvar localmente. Verifique os logs.');
            } catch (\Exception $e) {
                $this->Flash->error('Falha ao cadastrar webhook no Sicoob: ' . $e->getMessage());
            }
        }

        $defaultChave = Configure::read('Sicoob.pixKey');
        $this->set(compact('webhook', 'defaultChave'));
    }

    public function delete($id)
    {
        $this->request->allowMethod(['post', 'delete']);

        $pixWebhooks = $this->fetchTable('PixWebhooks');
        $webhook = $pixWebhooks->get($id);

        try {
            SicoobService::fromConfigure()->deleteWebhook($webhook->chave);
        } catch (\Exception $e) {
            $this->Flash->error('Falha ao remover webhook no Sicoob (removendo apenas localmente): ' . $e->getMessage());
        }

        if ($pixWebhooks->delete($webhook))
            $this->Flash->success('Webhook removido.');
        else
            $this->Flash->error('Erro ao excluir o registro local.');

        return $this->redirect(['action' => 'index']);
    }
}
