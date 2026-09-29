<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Model\Table\PlanParametersTable;
use App\Utility\Money;

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
            $planParameter = $this->PlanParameters->patchEntity(
                $planParameter,
                $this->normalizeNumericFields($this->request->getData())
            );

            if ($this->PlanParameters->save($planParameter)) {
                $this->Flash->success('Parâmetros do plano atualizados.');

                return $this->redirect(['action' => 'edit']);
            }

            $this->Flash->error('Não foi possível salvar os parâmetros. Revise os valores.');
        }

        $this->set(compact('planParameter'));
    }

    /**
     * A tela renderiza os campos de dinheiro com a máscara jQuery `money2`
     * (pt-BR, vírgula decimal) e os de percentual com `percent` (que embute
     * o próprio "%" no valor digitado) -- sem converter para o formato que a
     * coluna `decimal` aceita antes do patchEntity(), o Postgres recusa com
     * "Cannot convert value ... to a decimal", do mesmo jeito que já
     * acontecia na edição de adesões antes dessa normalização existir lá.
     */
    private function normalizeNumericFields(array $data): array
    {
        foreach (['minimum_monthly_contribution', 'survivors_pension_floor', 'disability_retirement_floor'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = Money::parse($data[$field]);
            }
        }

        foreach (['survivors_pension_percent', 'disability_retirement_percent'] as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $data[$field] = str_replace(',', '.', rtrim((string)$data[$field], '%'));
            }
        }

        return $data;
    }
}
