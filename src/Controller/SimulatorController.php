<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use App\Services\AdhesionFormMap;
use App\Services\PlanSimulator;
use Cake\Datasource\ConnectionManager;

class SimulatorController extends AppController
{
    function index()
    {
        $data = $_GET;
        $simulator = $this->planSimulator();
        $minimum = $simulator->parameters()->minimumMonthlyContribution();

        $associations = $this->activeAssociations();

        if ((float)str_replace(',', '.', (string)($data['value'] ?? 0)) < $minimum) {
            $this->Flash->error('O investimento mensal mínimo é de R$ ' . number_format($minimum, 2, ',', '.') . '.');

            return $this->redirect(['controller' => 'Pages', 'action' => 'display', 'home']);
        }

        // Uma simulação nova nasce com os dois riscos: remover risco deixou de
        // existir no fluxo público e passou a ser ato do admin sobre uma
        // adesão que já existe.
        [$includeSurvivorsPension, $includeDisabilityRetirement] = $simulator->effectiveRisks($data['date']);

        $simulations = $simulator->simulate($data['date'], (float)$data['value']);

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

        // A tela lia $_GET direto em quatro pontos, o que a amarrava à home:
        // a retomada não passa por lá e quebrava o script inteiro.
        $this->set([
            'simulationDate' => $data['date'],
            'simulationValue' => $data['value'],
        ]);
    }

    /**
     * Devolve a proposta ao proponente, preenchida, na etapa que o admin
     * escolheu.
     *
     * Renderiza a mesma tela do simulador: é o mesmo formulário, e duplicá-lo
     * significaria manter duas cópias de um modal de mil linhas em dia. O que
     * muda é que `date` e `value` vêm da adesão, e não da query -- quem abre o
     * link não passou pela home.
     */
    function resume(string $resumeToken)
    {
        $adhesions = $this->fetchTable('AdhesionInitialDatas');
        $adhesion = $adhesions->findByResumeToken($resumeToken);

        // Link inexistente e link vencido dizem coisas diferentes a quem
        // abre: um nunca existiu, o outro existiu e o prazo acabou. Nenhum dos
        // dois é um 404 seco, que faria a pessoa achar que o sistema perdeu
        // os dados dela.
        if ($adhesion === null || $adhesion->resumeTokenHasExpired()) {
            $this->set('expired', $adhesion !== null);

            return $this->render('resume_unavailable');
        }

        $simulator = $this->planSimulator();
        $birthDate = $adhesion->adhesion_personal_data?->birth_date?->format('Y-m-d');
        $value = (float)(
            $adhesion->adhesion_payment_detail?->total_contribution
                ?? $adhesion->adhesion_plan?->monthly_retirement_contribution
                ?? $simulator->parameters()->minimumMonthlyContribution()
        );

        // Sem data de nascimento não há o que simular: a proposta parou antes
        // dos dados pessoais, e retomar equivale a começar.
        if ($birthDate === null) {
            return $this->redirect(['controller' => 'Pages', 'action' => 'display', 'home']);
        }

        [$includeSurvivorsPension, $includeDisabilityRetirement] = $simulator->effectiveRisks(
            $birthDate,
            $adhesion->adhesion_plan->has_survivors_pension ?? true,
            $adhesion->adhesion_plan->has_disability_retirement ?? true
        );

        $this->set([
            'simulations' => $simulator->simulate(
                $birthDate,
                $value,
                $includeSurvivorsPension,
                $includeDisabilityRetirement
            ),
            'totalMonthlyContributionPlan' => $value,
            'age' => PlanSimulator::ageOn($birthDate),
            'associations' => $this->activeAssociations(),
            'includeSurvivorsPension' => $includeSurvivorsPension,
            'includeDisabilityRetirement' => $includeDisabilityRetirement,
            'simulationDate' => $birthDate,
            'simulationValue' => $value,
            'resumed' => [
                'initialDataId' => $adhesion->id,
                'storageUuid' => $adhesion->storage_uuid,
                'step' => $adhesion->resume_step,
                'planLocked' => (bool)($adhesion->adhesion_plan->admin_overridden ?? false),
                'form' => AdhesionFormMap::toFormPayload($adhesion),
            ],
        ]);

        return $this->render('index');
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

        [$hasSurvivorsPension, $hasDisabilityRetirement, $planLocked] = $this->storedPlanState(
            $this->request->getQuery('initialDataId'),
            $this->request->getQuery('storageUuid')
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
                // Ecoa de volta o que o servidor aplicou, para a tela se
                // realinhar: é daqui que o formulário fica sabendo que um
                // risco foi removido pelo admin, e com isso se a etapa da
                // saúde deve aparecer.
                'hasSurvivorsPension' => $includeSurvivorsPension,
                'hasDisabilityRetirement' => $includeDisabilityRetirement,
                // Valor ajustado à mão é valor negociado: a tela trava o passo
                // para o cliente não desfazer, com um clique em "Recalcular",
                // o que foi combinado por telefone.
                'planLocked' => $planLocked,
            ]));
    }

    /**
     * O capital segurado de uma contribuição de risco editada à mão.
     *
     * Diferente de recalculate(), que redistribui o investimento total pelas
     * taxas padrão e reescreve as contribuições, este endpoint parte da
     * contribuição exata que o admin já digitou em cada risco -- sem mexer
     * nela nem no total -- e devolve só o capital correspondente. É o que a
     * tela de editar adesão chama a cada mudança nos campos de contribuição
     * de morte/invalidez, para o capital não ficar desatualizado até alguém
     * calcular na mão.
     */
    function recalculateRiskCapital()
    {
        $this->request->allowMethod(['get', 'ajax']);
        $this->autoRender = false;

        $date = $this->request->getQuery('date');

        if (empty($date)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Data de nascimento é obrigatória.',
                ]));
        }

        $survivorsPensionContribution = (float)str_replace(
            ',',
            '.',
            (string)$this->request->getQuery('survivorsPensionContribution')
        );
        $disabilityRetirementContribution = (float)str_replace(
            ',',
            '.',
            (string)$this->request->getQuery('disabilityRetirementContribution')
        );

        [$survivorsPensionInsuredCapital, $disabilityRetirementInsuredCapital] = $this->planSimulator()
            ->riskInsuredCapital($date, $survivorsPensionContribution, $disabilityRetirementContribution);

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'survivorsPensionInsuredCapital' => $survivorsPensionInsuredCapital,
                'disabilityRetirementInsuredCapital' => $disabilityRetirementInsuredCapital,
            ]));
    }

    /**
     * Só oferece a pergunta "Possuí vínculo associativo?" quando há ao menos
     * um vínculo ativo cadastrado — sem isso, o formulário permanece
     * exatamente como era antes desta funcionalidade existir.
     */
    private function activeAssociations(): iterable
    {
        return $this->fetchTable('Partners')->find()
            ->find('byAssociationScope', isAssociation: true)
            ->where(['Partners.active' => true])
            ->orderBy(['Partners.name' => 'ASC'])
            ->all();
    }

    /**
     * O plano como está gravado, sem recalcular nada.
     *
     * É a diferença entre os dois botões do passo: "Recalcular" roda a
     * fórmula a partir do investimento digitado, e "Atualizar" traz o que o
     * admin gravou. Se o segundo recalculasse, um clique apagaria o valor
     * negociado por telefone -- que é exatamente o que ele existe para
     * preservar.
     */
    function planState()
    {
        $this->request->allowMethod(['get', 'ajax']);
        $this->autoRender = false;

        $adhesion = $this->identifiedAdhesion(
            $this->request->getQuery('initialDataId'),
            $this->request->getQuery('storageUuid')
        );

        if ($adhesion === null || $adhesion->adhesion_plan === null) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => false]));
        }

        $plan = $adhesion->adhesion_plan;

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'benefitEntryAge' => $plan->benefit_entry_age,
                'monthlyRetirementContribution' => (float)$plan->monthly_retirement_contribution,
                'monthlySurvivorsPensionContribution' => (float)$plan->monthly_survivors_pension_contribution,
                'survivorsPensionInsuredCapital' => (float)$plan->survivors_pension_insured_capital,
                'monthlyDisabilityRetirementContribution' => (float)$plan->monthly_disability_retirement_contribution,
                'disabilityRetirementInsuredCapital' => (float)$plan->disability_retirement_insured_capital,
                'totalMonthlyContribution' => (float)$plan->monthly_retirement_contribution
                    + (float)$plan->monthly_survivors_pension_contribution
                    + (float)$plan->monthly_disability_retirement_contribution,
                'hasSurvivorsPension' => (bool)$plan->has_survivors_pension,
                'hasDisabilityRetirement' => (bool)$plan->has_disability_retirement,
                'planLocked' => (bool)$plan->admin_overridden,
            ]));
    }

    /**
     * A adesão que o navegador diz ser, só quando ele prova. O id é
     * sequencial e viaja no POST; quem autoriza falar sobre a adesão é o
     * storage_uuid, conferido como em save().
     */
    private function identifiedAdhesion($initialDataId, $storageUuid): ?\App\Model\Entity\AdhesionInitialData
    {
        if (empty($initialDataId) || empty($storageUuid)) {
            return null;
        }

        /** @var \App\Model\Entity\AdhesionInitialData|null $adhesion */
        $adhesion = $this->fetchTable('AdhesionInitialDatas')->find()
            ->where(['AdhesionInitialDatas.id' => (int)$initialDataId])
            ->contain(['AdhesionPlans'])
            ->first();

        if ($adhesion === null || !hash_equals((string)$adhesion->storage_uuid, (string)$storageUuid)) {
            return null;
        }

        return $adhesion;
    }

    private function planSimulator(): PlanSimulator
    {
        return new PlanSimulator(
            ConnectionManager::get('default'),
            $this->fetchTable('PlanParameters')->current()
        );
    }

    /**
     * O estado do plano gravado: quais riscos tem, e se os valores foram
     * ajustados à mão pelo admin.
     *
     * Lê do banco em vez de aceitar da URL, porque desde que a remoção de
     * risco virou ato do admin não existe mais motivo para o navegador opinar:
     * montar a URL à mão tiraria riscos de graça. Sem adesão identificada é
     * uma simulação nova, e toda simulação nova tem os dois.
     *
     * @return array{0: bool, 1: bool, 2: bool} [morte, invalidez, ajustado]
     */
    private function storedPlanState($initialDataId, $storageUuid): array
    {
        $adhesion = $this->identifiedAdhesion($initialDataId, $storageUuid);

        if ($adhesion === null) {
            return [true, true, false];
        }

        return [
            $adhesion->adhesion_plan->has_survivors_pension ?? true,
            $adhesion->adhesion_plan->has_disability_retirement ?? true,
            (bool)($adhesion->adhesion_plan->admin_overridden ?? false),
        ];
    }
}
