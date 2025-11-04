<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Log\Log;
use Cake\Http\Client;
use Cake\Datasource\ConnectionManager;

class SimulatorController extends AppController
{
    function index()
    {
        $connection = ConnectionManager::get('default');
        $teste = $connection->execute(
            'SELECT *
            FROM simulacao_plenoprev(:date, :value)',
            [
                'date' => '2025-01-01',
                'value' => 2000
            ]
        )->fetchAll();

        // dd($teste);
    }
}
