<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use Cake\Datasource\ConnectionManager;

class SimulatorController extends AppController
{
    function index()
    {
        $connection = ConnectionManager::get('default');
        $data = $_GET;
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

    private function calculateAge($birthDate) {
        $birthDateObj = new \DateTime($birthDate);
        $currentDateObj = new \DateTime('today');

        $ageInterval = $currentDateObj->diff($birthDateObj);

        return $ageInterval->y;
    }
}
