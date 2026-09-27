<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Model\Table\PlanParametersTable;

/**
 * Tela única dos parâmetros do plano. Não há index, add nem delete de
 * propósito: a tabela tem uma linha só, criada pela migration, e um segundo
 * registro faria PlanParametersTable::current() escolher em silêncio qual dos
 * dois vale.
 */
class PlanParametersController extends AppController
{
    protected PlanParametersTable $PlanParameters;

    public function initialize(): void
    {
        parent::initialize();

        $this->PlanParameters = $this->fetchTable('PlanParameters');
    }

    public function edit()
    {
        $planParameter = $this->PlanParameters->current();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $planParameter = $this->PlanParameters->patchEntity($planParameter, $this->request->getData());

            if ($this->PlanParameters->save($planParameter)) {
                $this->Flash->success('Parâmetros do plano atualizados.');

                return $this->redirect(['action' => 'edit']);
            }

            $this->Flash->error('Não foi possível salvar os parâmetros. Revise os valores.');
        }

        $this->set(compact('planParameter'));
    }
}
