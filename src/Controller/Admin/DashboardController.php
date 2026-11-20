<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Services\BenefitPlans;

class DashboardController extends AppController
{
    protected array $userActions = ['index'];

    public function index()
    {
        $this->viewBuilder()->setLayout('admin');

        // Os mesmos números do resumo da listagem de adesões, um card por
        // plano: o admin é um só para todos.
        $adhesions = $this->fetchTable('AdhesionInitialDatas');
        $summaries = [];

        foreach (BenefitPlans::all() as $slug => $plan) {
            $summaries[$slug] = [
                'name' => $plan->name,
                'counts' => $adhesions->summarize(
                    $adhesions->find()->where(['AdhesionInitialDatas.benefit_plan' => $slug])
                ),
            ];
        }

        $this->set(compact('summaries'));
    }
}
