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
        dd($data);
        $simulation = $connection->execute(
            'SELECT *
            FROM simulacao_previdencia(:date, :value)',
            [
                'date' => '2025-01-01',
                'value' => 1000
            ]
        )->fetchAll('assoc');

        dd($teste);
    }
}
