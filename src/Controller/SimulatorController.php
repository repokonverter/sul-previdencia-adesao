<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use Cake\Datasource\ConnectionManager;

class SimulatorController extends AppController
{
    const MINIMUM_MONTHLY_INVESTMENT = 100.0;

    function index()
    {
        $connection = ConnectionManager::get('default');
        $data = $_GET;

        // Só oferece a pergunta "Possuí vínculo associativo?" quando há ao
        // menos um vínculo ativo cadastrado — sem isso, o formulário
        // permanece exatamente como era antes desta funcionalidade existir.
        $associations = $this->fetchTable('Partners')->find()
            ->find('byAssociationScope', isAssociation: true)
            ->where(['Partners.active' => true])
            ->orderBy(['Partners.name' => 'ASC'])
            ->all();

        if ((float)str_replace(',', '.', (string)($data['value'] ?? 0)) < self::MINIMUM_MONTHLY_INVESTMENT) {
            $this->Flash->error('O investimento mensal mínimo é de R$ ' . number_format(self::MINIMUM_MONTHLY_INVESTMENT, 2, ',', '.') . '.');

            return $this->redirect(['controller' => 'Pages', 'action' => 'display', 'home']);
        }

        $age = $this->calculateAge($data['date']);
        [$includeSurvivorsPension, $includeDisabilityRetirement] = $this->resolveRiskFlags(
            $age,
            $data['brokerCode'] ?? null,
            $data['removeSurvivorsPension'] ?? null,
            $data['removeDisabilityRetirement'] ?? null
        );

        $simulations = $connection
            ->execute(
                'SELECT *
                FROM simulacao_previdencia(:date, :value, :incluirMorte, :incluirInvalidez)',
                [
                    'date' => $data['date'],
                    'value' => $data['value'],
                    'incluirMorte' => $includeSurvivorsPension,
                    'incluirInvalidez' => $includeDisabilityRetirement,
                ],
                [
                    'incluirMorte' => 'boolean',
                    'incluirInvalidez' => 'boolean',
                ]
            )
            ->fetchAll('assoc');
        $totalMonthlyContributionPlan = $data['value'];

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

        $connection = ConnectionManager::get('default');
        $date = $this->request->getQuery('date');
        $value = (float)str_replace(',', '.', (string)$this->request->getQuery('value'));

        if (empty($date) || $value < self::MINIMUM_MONTHLY_INVESTMENT) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'O investimento mensal mínimo é de R$ ' . number_format(self::MINIMUM_MONTHLY_INVESTMENT, 2, ',', '.') . '.',
                ]));
        }

        $age = $this->calculateAge($date);
        [$includeSurvivorsPension, $includeDisabilityRetirement] = $this->resolveRiskFlags(
            $age,
            $this->request->getQuery('brokerCode'),
            $this->request->getQuery('removeSurvivorsPension'),
            $this->request->getQuery('removeDisabilityRetirement')
        );

        $simulations = $connection
            ->execute(
                'SELECT *
                FROM simulacao_previdencia(:date, :value, :incluirMorte, :incluirInvalidez)',
                [
                    'date' => $date,
                    'value' => $value,
                    'incluirMorte' => $includeSurvivorsPension,
                    'incluirInvalidez' => $includeDisabilityRetirement,
                ],
                [
                    'incluirMorte' => 'boolean',
                    'incluirInvalidez' => 'boolean',
                ]
            )
            ->fetchAll('assoc');

        $benefitEntryAge = $age <= 55 ? 65 : $age + 10;

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'benefitEntryAge' => $benefitEntryAge,
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

    /**
     * Determina se cada risco entra no cálculo da simulação.
     *
     * O servidor é a única autoridade aqui: as flags "removeX" só valem
     * quando acompanhadas de um código de corretor que de fato existe e está
     * ativo — nunca são aceitas sozinhas, senão bastaria montar a URL à mão
     * para tirar os riscos sem corretor nenhum. Menor de 16 anos nunca tem
     * risco, corretor ou não (mesma regra que já existia, agora também
     * corrigindo o cálculo de saldo_acumulado/beneficio_mensal — antes desta
     * mudança eles eram computados sobre os 100% da contribuição e só depois
     * zerados em PHP, subestimando a projeção).
     *
     * @return array{0: bool, 1: bool} [incluirMorte, incluirInvalidez]
     */
    private function resolveRiskFlags(int $age, ?string $brokerCode, $removeSurvivorsPension, $removeDisabilityRetirement): array
    {
        if ($age < 16) {
            return [false, false];
        }

        $broker = $this->fetchTable('Brokers')->findByCodeText($brokerCode);

        if ($broker === null || !$broker->isUsable()) {
            return [true, true];
        }

        return [
            empty($removeSurvivorsPension),
            empty($removeDisabilityRetirement),
        ];
    }

    private function calculateAge($birthDate) {
        $birthDateObj = new \DateTime($birthDate);
        $currentDateObj = new \DateTime('today');

        $ageInterval = $currentDateObj->diff($birthDateObj);

        return $ageInterval->y;
    }
}
