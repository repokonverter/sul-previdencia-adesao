<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use App\Services\PlanSimulator;
use Cake\Datasource\ConnectionManager;

class SimulatorController extends AppController
{
    function index()
    {
        $data = $_GET;
        $simulator = $this->planSimulator();
        $minimum = $simulator->parameters()->minimumMonthlyContribution();

        // Só oferece a pergunta "Possuí vínculo associativo?" quando há ao
        // menos um vínculo ativo cadastrado — sem isso, o formulário
        // permanece exatamente como era antes desta funcionalidade existir.
        $associations = $this->fetchTable('Partners')->find()
            ->find('byAssociationScope', isAssociation: true)
            ->where(['Partners.active' => true])
            ->orderBy(['Partners.name' => 'ASC'])
            ->all();

        if ((float)str_replace(',', '.', (string)($data['value'] ?? 0)) < $minimum) {
            $this->Flash->error('O investimento mensal mínimo é de R$ ' . number_format($minimum, 2, ',', '.') . '.');

            return $this->redirect(['controller' => 'Pages', 'action' => 'display', 'home']);
        }

        [$hasSurvivorsPension, $hasDisabilityRetirement] = $this->requestedRisks(
            $data['brokerCode'] ?? null,
            $data['removeSurvivorsPension'] ?? null,
            $data['removeDisabilityRetirement'] ?? null
        );

        [$includeSurvivorsPension, $includeDisabilityRetirement] = $simulator->effectiveRisks(
            $data['date'],
            $hasSurvivorsPension,
            $hasDisabilityRetirement
        );

        $simulations = $simulator->simulate(
            $data['date'],
            (float)$data['value'],
            $hasSurvivorsPension,
            $hasDisabilityRetirement
        );

        $totalMonthlyContributionPlan = $data['value'];
        $age = PlanSimulator::ageOn($data['date']);

        $this->set(compact(
            'simulations',
            'totalMonthlyContributionPlan',
            'age',
            'associations',
            'includeSurvivorsPension',
            'includeDisabilityRetirement'
        ));
    }

    function recalculate()
    {
        $this->request->allowMethod(['get', 'ajax']);
        $this->autoRender = false;

        $simulator = $this->planSimulator();
        $minimum = $simulator->parameters()->minimumMonthlyContribution();
        $date = $this->request->getQuery('date');
        $value = (float)str_replace(',', '.', (string)$this->request->getQuery('value'));

        if (empty($date) || $value < $minimum) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'O investimento mensal mínimo é de R$ ' . number_format($minimum, 2, ',', '.') . '.',
                ]));
        }

        [$hasSurvivorsPension, $hasDisabilityRetirement] = $this->requestedRisks(
            $this->request->getQuery('brokerCode'),
            $this->request->getQuery('removeSurvivorsPension'),
            $this->request->getQuery('removeDisabilityRetirement')
        );

        [$includeSurvivorsPension, $includeDisabilityRetirement] = $simulator->effectiveRisks(
            $date,
            $hasSurvivorsPension,
            $hasDisabilityRetirement
        );

        $simulations = $simulator->simulate($date, $value, $hasSurvivorsPension, $hasDisabilityRetirement);

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'benefitEntryAge' => $simulator->benefitEntryAge($date),
                'monthlyRetirementContribution' => (float)$simulations[1]['contribuicao_aposentadoria'],
                'monthlySurvivorsPensionContribution' => (float)$simulations[1]['contribuicao_morte'],
                'survivorsPensionInsuredCapital' => (float)$simulations[1]['cobertura_morte'],
                'monthlyDisabilityRetirementContribution' => (float)$simulations[1]['contribuicao_invalidez'],
                'disabilityRetirementInsuredCapital' => (float)$simulations[1]['cobertura_invalidez'],
                'totalMonthlyContribution' => $value,
                // Ecoa de volta o que o servidor de fato aplicou: um corretor
                // que ficou inválido/inativo entre a validação em tempo real
                // e este recálculo faz os dois riscos voltarem, e o
                // front-end precisa saber disso para reexibir a etapa da
                // saúde e desmarcar os toggles.
                'hasSurvivorsPension' => $includeSurvivorsPension,
                'hasDisabilityRetirement' => $includeDisabilityRetirement,
            ]));
    }

    private function planSimulator(): PlanSimulator
    {
        return new PlanSimulator(
            ConnectionManager::get('default'),
            $this->fetchTable('PlanParameters')->current()
        );
    }

    /**
     * Quais riscos o pedido está querendo, antes das regras que não dependem
     * de escolha (idade mínima, aplicada por PlanSimulator::effectiveRisks).
     *
     * As flags "removeX" só valem acompanhadas de um código de corretor que
     * de fato existe e está ativo — nunca sozinhas, senão bastaria montar a
     * URL à mão para tirar os riscos sem corretor nenhum.
     *
     * @return array{0: bool, 1: bool} [morte, invalidez]
     */
    private function requestedRisks(?string $brokerCode, $removeSurvivorsPension, $removeDisabilityRetirement): array
    {
        $broker = $this->fetchTable('Brokers')->findByCodeText($brokerCode);

        if ($broker === null || !$broker->isUsable()) {
            return [true, true];
        }

        return [
            empty($removeSurvivorsPension),
            empty($removeDisabilityRetirement),
        ];
    }
}
