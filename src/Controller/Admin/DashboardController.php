<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;

class DashboardController extends AppController
{
    protected array $userActions = ['index'];

    public function index()
    {
        $this->viewBuilder()->setLayout('admin');
    }
}
