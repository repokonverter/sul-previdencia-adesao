<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Dompdf\Dompdf;
use DateTime;
use App\Services\ClicksignService;

class AdhesionsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('PdfGenerator');

        $this->viewBuilder()->setLayout('admin');

        $this->AdhesionPersonalDatas = $this->fetchTable('AdhesionPersonalDatas');
        $this->AdhesionInitialDatas = $this->fetchTable('AdhesionInitialDatas');
        $this->AdhesionAddresses = $this->fetchTable('AdhesionAddresses');
        $this->AdhesionDependents = $this->fetchTable('AdhesionDependents');
        $this->AdhesionPlans = $this->fetchTable('AdhesionPlans');
        $this->AdhesionOtherInformations = $this->fetchTable('AdhesionOtherInformations');
        $this->AdhesionDocuments = $this->fetchTable('AdhesionDocuments');


        $this->paginate = [
            'order' => ['AdhesionInitialDatas.created' => 'DESC'],
            'limit' => 10
        ];
    }

    public function assinar($id)
    {

        $clicksign = new ClicksignService(
            'baseUrl',
            'accessToken'
        );

        $resultado = $clicksign->exemplo();

        debug($resultado);
        exit(); // "teste!"
    }

    public function index()
    {
        $query = $this->AdhesionInitialDatas
            ->find()
            ->contain([
                'AdhesionPersonalDatas',
                'AdhesionAddresses',
                'AdhesionPlans',
            ]);

        $searchName = $this->request->getQuery('name');
        $searchCpf  = $this->request->getQuery('cpf');

        if ($searchName) {
            $query->where([
                'AdhesionPersonalDatas.name LIKE' => "%$searchName%"
            ]);
        }

        if ($searchCpf) {
            $query->where([
                'AdhesionPersonalDatas.cpf LIKE' => "%$searchCpf%"
            ]);
        }

        $adhesions = $this->paginate($query);
        // debug($adhesions);exit();
        $this->set(compact('adhesions'));
    }

    public function view($id)
    {
        $adhesion = $this->AdhesionInitialDatas->get($id, [
            'contain' => [
                'AdhesionPersonalDatas',
                'AdhesionAddresses',
                'AdhesionDependents',
                'AdhesionPlans',
                'AdhesionDocuments',
                'AdhesionOtherInformations',
                'AdhesionPaymentDetails',        // NOVO
                'AdhesionPensionSchemes',        // NOVO
                'AdhesionProponentStatements'    // NOVO
            ]
        ]);

        $this->set(compact('adhesion'));
    }

    public function edit($id)
    {
        $adhesion = $this->AdhesionInitialDatas->get($id, [
            'contain' => [
                'AdhesionPersonalDatas',
                'AdhesionAddresses',
                'AdhesionDependents',
                'AdhesionPlans',
                'AdhesionDocuments',
                'AdhesionOtherInformations'
            ]
        ]);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $adhesion = $this->AdhesionInitialDatas->patchEntity(
                $adhesion,
                $this->request->getData(),
                [
                    'associated' => [
                        'AdhesionPersonalDatas',
                        'AdhesionAddresses',
                        'AdhesionDependents',
                        'AdhesionPlans',
                        'AdhesionDocuments',
                        'AdhesionOtherInformations'
                    ]
                ]
            );

            if ($this->AdhesionInitialDatas->save($adhesion)) {
                $this->Flash->success('Cliente atualizado com sucesso.');
                return $this->redirect(['action' => 'view', $id]);
            }

            $this->Flash->error('Erro ao salvar, revise os dados.');
        }

        $this->set(compact('adhesion'));
    }

    public function delete($id)
    {
        $this->request->allowMethod(['post', 'delete']);
        $item = $this->AdhesionInitialDatas->get($id);

        if ($this->AdhesionInitialDatas->delete($item)) {
            $this->Flash->success('Registro removido.');
        } else {
            $this->Flash->error('Erro ao excluir o registro.');
        }

        return $this->redirect(['action' => 'index']);
    }

    public function generatePdf($id)
    {
        return $this->PdfGenerator->generatePdf($id);
    }

    public function generateFormPdf($id)
    {
        return $this->PdfGenerator->generateFormPdf($id);
    }
}
