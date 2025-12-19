<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\ORM\TableRegistry;

/**
 * Occupations Controller
 */
class OccupationsController extends AppController
{
    /**
     * Search method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function search()
    {
        $this->request->allowMethod(['ajax', 'get']);
        $term = $this->request->getQuery('term');

        $this->viewBuilder()->setClassName('Json');

        if (!$term) {
            $this->set('results', []);
            $this->viewBuilder()->setOption('serialize', 'results');

            return;
        }

        $cbosTable = TableRegistry::getTableLocator()->get('Cbos');

        $results = $cbosTable->find()
            ->where([
                'OR' => [
                    'code LIKE' => '%' . $term . '%',
                    'description LIKE' => '%' . $term . '%'
                ]
            ])
            ->limit(20)
            ->all()
            ->map(function ($row) {
                return [
                    'id' => $row->code,
                    'text' => $row->code . ' - ' . $row->description,
                    'description' => $row->description
                ];
            });

        $this->set('results', $results);
        $this->viewBuilder()->setOption('serialize', 'results');
    }
}
