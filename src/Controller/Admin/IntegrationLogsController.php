<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;

class IntegrationLogsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->paginate = [
            'order' => ['IntegrationLogs.created' => 'DESC'],
            'limit' => 25,
        ];
    }

    public function index()
    {
        $query = $this->fetchTable('IntegrationLogs')->find()
            ->contain(['AdhesionInitialDatas']);

        $service = $this->request->getQuery('service');
        $status = $this->request->getQuery('status');
        $adhesionId = $this->request->getQuery('adhesion_id');

        if ($service)
            $query->where(['IntegrationLogs.service' => $service]);

        if ($status === 'success')
            $query->where(['IntegrationLogs.success' => true]);
        elseif ($status === 'failure')
            $query->where(['IntegrationLogs.success' => false]);

        if ($adhesionId)
            $query->where(['IntegrationLogs.adhesion_initial_data_id' => $adhesionId]);

        $logs = $this->paginate($query);
        $services = ['sicoob', 'clicksign', 'resend', 'internal'];

        $this->set(compact('logs', 'services', 'service', 'status', 'adhesionId'));
    }
}
