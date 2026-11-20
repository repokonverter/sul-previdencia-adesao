<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Model\Table\PlanParametersTable;
use App\Services\BenefitPlans;
use App\Utility\Money;
use Cake\Http\Exception\NotFoundException;

/**
 * Os parâmetros de cada plano de benefícios, um formulário por plano. Não há
 * index, add nem delete de propósito: cada plano tem uma linha só, criada
 * por migration, e o índice único em benefit_plan não deixa haver duas.
 */
class PlanParametersController extends AppController
{
    protected PlanParametersTable $PlanParameters;

    /**
     * `edit` também é a tela de consulta: o perfil `user` abre, mas o POST
     * é recusado em edit().
     */
    protected array $userActions = ['edit'];

    public function initialize(): void
    {
        parent::initialize();

        $this->PlanParameters = $this->fetchTable('PlanParameters');
    }

    public function edit(?string $benefitPlan = null)
    {
        if ($benefitPlan !== null && !BenefitPlans::exists($benefitPlan)) {
            throw new NotFoundException();
        }

        $plan = $benefitPlan === null ? BenefitPlans::primary() : BenefitPlans::get($benefitPlan);
        $planParameter = $this->PlanParameters->forPlan($plan->slug);

        if ($this->request->is(['patch', 'post', 'put'])) {
            if (!$this->isAdmin()) {
                return $this->denyAccess();
            }

            $planParameter = $this->PlanParameters->patchEntity(
                $planParameter,
                $this->normalizeNumericFields($this->request->getData())
            );

            if ($this->PlanParameters->save($planParameter)) {
                $this->Flash->success('Parâmetros do plano ' . $plan->name . ' atualizados.');

                return $this->redirect(['action' => 'edit', $plan->slug]);
            }

            $this->Flash->error('Não foi possível salvar os parâmetros. Revise os valores.');
        }

        $benefitPlans = BenefitPlans::options();

        $this->set(compact('planParameter', 'plan', 'benefitPlans'));
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
