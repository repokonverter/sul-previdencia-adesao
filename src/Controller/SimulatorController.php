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

        $this->set(compact('simulations'));
    }
}
