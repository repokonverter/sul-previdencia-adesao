<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\PlanSimulationForm;
use App\Services\BenefitEntryAge;
use App\Services\BenefitPlan;
use App\Services\BenefitPlans;
use App\Services\PlanSimulator;
use App\Utility\Money;

/**
 * Simulação avulsa: o admin monta um plano para um cliente que ainda não tem
 * adesão, vê cenários e gráfico, e baixa o mesmo PDF da aba Plano. Nada é
 * gravado.
 *
 * Também serve a aba Plano do editar adesão: projection() e recalculate()
 * não dependem de adesão, e as duas telas usam o mesmo
 * webroot/js/admin/plan-editor.js.
 */
class SimulationsController extends AppController
{
    protected array $userActions = ['index', 'pdf', 'projection', 'recalculate'];

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('PdfGenerator');
    }

    public function index()
    {
        $simulation = $this->simulationForm();
        // Para quem tem até 65 anos, benefitEntryAge() limitado ao teto do
        // admin dá sempre 65: é o padrão, antes mesmo da data de nascimento.
        $simulation->setData([
            'benefit_plan' => $this->selectedPlan()->slug,
            'benefit_entry_age' => BenefitEntryAge::MAXIMUM,
            'has_survivors_pension' => true,
            'has_disability_retirement' => true,
        ]);

        $this->set('simulation', $simulation);
        $this->setScreenParameters();
    }

    /**
     * O PDF do que está na tela. Recusado pela validação, a página volta com
     * o formulário preenchido e os erros nos campos.
     */
    public function pdf()
    {
        $this->request->allowMethod(['post']);

        $simulation = $this->simulationForm();

        if (!$simulation->execute($this->request->getData())) {
            $this->Flash->error('Revise os campos destacados para gerar o PDF.');
            $this->set('simulation', $simulation);
            $this->setScreenParameters();

            return $this->render('index');
        }

        $quote = $simulation->quote();
        $projection = $this->planSimulator($quote->benefitPlan)->projectQuote($quote);

        return $this->response
            ->withType('pdf')
            ->withDownload($this->PdfGenerator->planSimulationFileName($quote))
            ->withStringBody($this->PdfGenerator->planSimulationPdf($quote, $projection));
    }

    /**
     * Projeção das contribuições que estão na tela, ainda não salvas, para o
     * gráfico acompanhar o que o admin digita.
     */
    public function projection()
    {
        $this->request->allowMethod(['get']);

        $date = $this->birthDateFromQuery();

        if ($date === null) {
            return $this->json(['success' => false, 'message' => 'Data de nascimento é obrigatória.']);
        }

        $projection = $this->planSimulator($this->selectedPlan())->project(
            $date,
            $this->amountFromQuery('retirementContribution'),
            $this->amountFromQuery('survivorsPensionContribution'),
            $this->amountFromQuery('disabilityRetirementContribution'),
        );

        return $this->json(['success' => true] + $projection->toArray());
    }

    /**
     * O "Recalcular": reparte o total pela regra de PlanSimulator::rebalance()
     * e devolve as contribuições e os capitais segurados correspondentes.
     */
    public function recalculate()
    {
        $this->request->allowMethod(['get']);

        $date = $this->birthDateFromQuery();

        if ($date === null) {
            return $this->json(['success' => false, 'message' => 'Preencha a data de nascimento antes de recalcular.']);
        }

        $simulator = $this->planSimulator($this->selectedPlan());
        $total = $this->amountFromQuery('totalContribution');
        $minimumError = $simulator->parameters()->minimumContributionError($total);

        if ($minimumError !== null) {
            return $this->json(['success' => false, 'message' => $minimumError]);
        }

        [$retirement, $survivorsPension, $disabilityRetirement] = $simulator->rebalance(
            $date,
            $total,
            (bool)$this->request->getQuery('hasSurvivorsPension'),
            $this->amountFromQuery('survivorsPensionContribution'),
            (bool)$this->request->getQuery('hasDisabilityRetirement'),
            $this->amountFromQuery('disabilityRetirementContribution'),
        );

        if ($retirement < 0) {
            return $this->json([
                'success' => false,
                'message' => 'A contribuição total é menor que a soma das contribuições de risco.',
            ]);
        }

        [$survivorsPensionCapital, $disabilityRetirementCapital] = $simulator->riskInsuredCapital(
            $date,
            $survivorsPension,
            $disabilityRetirement,
        );

        return $this->json([
            'success' => true,
            'retirementContribution' => $retirement,
            'survivorsPensionContribution' => $survivorsPension,
            'survivorsPensionInsuredCapital' => $survivorsPensionCapital,
            'disabilityRetirementContribution' => $disabilityRetirement,
            'disabilityRetirementInsuredCapital' => $disabilityRetirementCapital,
        ]);
    }

    private function setScreenParameters(): void
    {
        $this->set([
            'benefitPlans' => BenefitPlans::options(),
            'minimumRiskAge' => PlanSimulator::MINIMUM_RISK_AGE,
        ]);
    }

    private function simulationForm(): PlanSimulationForm
    {
        $plan = $this->selectedPlan();

        return new PlanSimulationForm($plan, $this->planSimulator($plan));
    }

    /**
     * O plano de benefícios da simulação: o do formulário no PDF, o da query
     * nas chamadas da tela (plan-editor.js manda `benefitPlan`, que na aba
     * Plano da adesão é o plano dela). Sem plano válido, o principal.
     *
     * Não usa o host: o admin responde nos dois, e o plano muda o PDF e os
     * parâmetros do cálculo.
     */
    private function selectedPlan(): BenefitPlan
    {
        $slug = $this->request->getData('benefit_plan') ?? $this->request->getQuery('benefitPlan');

        return BenefitPlans::exists($slug) ? BenefitPlans::get($slug) : BenefitPlans::primary();
    }

    private function birthDateFromQuery(): ?string
    {
        $date = (string)$this->request->getQuery('date');

        return $date === '' || strtotime($date) === false ? null : $date;
    }

    /**
     * Aceita as duas grafias que a tela produz (Money::parse()); negativo
     * vira zero.
     */
    private function amountFromQuery(string $key): float
    {
        return max(0.0, (float)Money::parse($this->request->getQuery($key)));
    }

    private function json(array $body)
    {
        return $this->response->withType('application/json')->withStringBody(json_encode($body));
    }
}
