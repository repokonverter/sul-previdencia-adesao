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

        if ((float)str_replace(',', '.', (string)($data['value'] ?? 0)) < self::MINIMUM_MONTHLY_INVESTMENT) {
            $this->Flash->error('O investimento mensal mínimo é de R$ ' . number_format(self::MINIMUM_MONTHLY_INVESTMENT, 2, ',', '.') . '.');

            return $this->redirect(['controller' => 'Pages', 'action' => 'display', 'home']);
        }

        $simulations = $connection
            ->execute(
                'SELECT *
                FROM simulacao_previdencia(:date, :value)',
                [
                    'date' => $data['date'],
                    'value' => $data['value'],
                ]
            )
            ->fetchAll('assoc');
        $totalMonthlyContributionPlan = $data['value'];
        $age = $this->calculateAge($data['date']);

        if ($age < 16) {
            $simulations[1]['contribuicao_aposentadoria'] = $data['value'];
            $simulations[1]['contribuicao_morte'] = 0;
            $simulations[1]['contribuicao_invalidez'] = 0;
        }

        $this->set(compact('simulations', 'totalMonthlyContributionPlan', 'age'));
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

        $simulations = $connection
            ->execute(
                'SELECT *
                FROM simulacao_previdencia(:date, :value)',
                [
                    'date' => $date,
                    'value' => $value,
                ]
            )
            ->fetchAll('assoc');
        $age = $this->calculateAge($date);

        if ($age < 16) {
            $simulations[1]['contribuicao_aposentadoria'] = $value;
            $simulations[1]['contribuicao_morte'] = 0;
            $simulations[1]['contribuicao_invalidez'] = 0;
        }

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
            ]));
    }

    private function calculateAge($birthDate) {
        $birthDateObj = new \DateTime($birthDate);
        $currentDateObj = new \DateTime('today');

        $ageInterval = $currentDateObj->diff($birthDateObj);

        return $ageInterval->y;
    }
}
