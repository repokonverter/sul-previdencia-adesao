<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Model\Table\BrokersTable;

class BrokersController extends AppController
{
    protected BrokersTable $Brokers;

    public function initialize(): void
    {
        parent::initialize();

        $this->Brokers = $this->fetchTable('Brokers');

        $this->paginate = [
            'order' => ['Brokers.name' => 'ASC'],
            'limit' => 20,
        ];
    }

    public function index()
    {
        $query = $this->Brokers->find('withAdhesionCounts');

        $q = $this->request->getQuery('q');

        if ($q) {
            $query->where([
                'OR' => [
                    'Brokers.name LIKE' => "%$q%",
                    'Brokers.code LIKE' => "%$q%",
                ],
            ]);
        }

        $brokers = $this->paginate($query);

        $this->set(compact('brokers'));
    }

    public function add()
    {
        $broker = $this->Brokers->newEmptyEntity();

        if ($this->request->is('post')) {
            $broker = $this->Brokers->patchEntity($broker, $this->formData());

            if ($this->Brokers->save($broker)) {
                $this->Flash->success(__('O corretor foi salvo com sucesso.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('Não foi possível salvar o corretor. Por favor, tente novamente.'));
        }

        $this->set(compact('broker'));
    }

    public function edit($id = null)
    {
        $broker = $this->Brokers->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $broker = $this->Brokers->patchEntity($broker, $this->formData());

            if ($this->Brokers->save($broker)) {
                $this->Flash->success(__('O corretor foi salvo com sucesso.'));

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('Não foi possível salvar o corretor. Por favor, tente novamente.'));
        }

        $this->set(compact('broker'));
    }

    /**
     * Um corretor só pode ser excluído se nunca tiver sido usado em uma
     * adesão (broker_id é RESTRICT). Havendo qualquer uso, o caminho é
     * desativar.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $broker = $this->Brokers->get($id);

        $adhesions = $this->Brokers->AdhesionInitialDatas->find()
            ->where(['broker_id' => $broker->id])
            ->count();

        if ($adhesions > 0) {
            $this->Flash->error(__(
                'Este corretor foi usado em {0} adesão(ões) e não pode ser excluído. Desative-o para impedir novos usos.',
                $adhesions
            ));

            return $this->redirect(['action' => 'index']);
        }

        if ($this->Brokers->delete($broker)) {
            $this->Flash->success(__('O corretor foi removido com sucesso.'));
        } else {
            $this->Flash->error(__('Não foi possível remover o corretor. Por favor, tente novamente.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function toggle($id = null)
    {
        $this->request->allowMethod(['post']);

        $broker = $this->Brokers->get($id);
        $broker->active = !$broker->active;

        if ($this->Brokers->save($broker)) {
            $this->Flash->success(__(
                'O corretor {0} foi {1}.',
                $broker->name,
                $broker->active ? 'ativado' : 'desativado'
            ));
        } else {
            $this->Flash->error(__('Não foi possível alterar o corretor.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    protected function formData(): array
    {
        $data = $this->request->getData();

        $data['active'] = (bool)($data['active'] ?? false);

        return $data;
    }
}
