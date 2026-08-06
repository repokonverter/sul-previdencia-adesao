<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Model\Table\AdhesionInitialDatasTable;
use App\Model\Table\AdhesionPensionSchemesTable;
use App\Services\ClicksignService;
use App\Services\PixPaymentService;
use App\Services\SicoobService;

class AdhesionsController extends AppController
{
    protected AdhesionInitialDatasTable $AdhesionInitialDatas;
    protected AdhesionPensionSchemesTable $AdhesionPensionSchemes;

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('PdfGenerator');

        $this->AdhesionInitialDatas = $this->fetchTable('AdhesionInitialDatas');
        $this->AdhesionPensionSchemes = $this->fetchTable('AdhesionPensionSchemes');

        $this->paginate = [
            'order' => ['AdhesionInitialDatas.created' => 'DESC'],
            'limit' => 10
        ];
    }

    public function index()
    {
        $query = $this->AdhesionInitialDatas
            ->find()
            ->contain([
                'AdhesionPersonalDatas',
                'AdhesionAddresses',
                'AdhesionDependents',
                'AdhesionPlans',
                'AdhesionDocuments',
                'AdhesionOtherInformations',
                'AdhesionPaymentDetails',
                'AdhesionPensionSchemes',
                'AdhesionProponentStatements',
                'PixTransactions' => ['sort' => ['PixTransactions.attempt' => 'DESC']],
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
                'AdhesionPaymentDetails',
                'AdhesionPensionSchemes',
                'AdhesionProponentStatements',
                'PixTransactions' => ['sort' => ['PixTransactions.attempt' => 'DESC']],
                'IntegrationLogs' => ['sort' => ['IntegrationLogs.created' => 'DESC']],
            ]
        ]);

        $this->set(compact('adhesion'));
    }

    public function checkPixPayment($id)
    {
        $this->request->allowMethod(['post']);

        $tab = $this->request->getData('tab');

        $pixTransactions = $this->fetchTable('PixTransactions');
        $latest = $pixTransactions->find()
            ->where(['adhesion_initial_data_id' => $id])
            ->orderBy(['attempt' => 'DESC'])
            ->first();

        if (!$latest) {
            $this->Flash->error('Nenhuma cobrança Pix foi gerada para esta adesão ainda.');

            return $this->redirect(['action' => 'view', $id, '?' => array_filter(['tab' => $tab])]);
        }

        try {
            $pixPaymentService = new PixPaymentService(SicoobService::fromConfigure(), $pixTransactions);
            $result = $pixPaymentService->confirmIfPaid($latest->txid);

            if (!$result['found'])
                $this->Flash->error('Cobrança não encontrada no Sicoob.');
            elseif ($result['paid'])
                $this->Flash->success('Pagamento confirmado no Sicoob.');
            else
                $this->Flash->info('Ainda não há confirmação de pagamento no Sicoob (status: ' . ($result['status'] ?? 'desconhecido') . ').');
        } catch (\Exception $e) {
            $this->Flash->error('Falha ao consultar o Sicoob: ' . $e->getMessage());
        }

        return $this->redirect(['action' => 'view', $id, '?' => array_filter(['tab' => $tab])]);
    }

    public function add()
    {
        $adhesion = $this->AdhesionInitialDatas->newEmptyEntity();
        if ($this->request->is('post')) {
            $adhesion = $this->AdhesionInitialDatas->patchEntity($adhesion, $this->request->getData(), [
                'associated' => [
                    'AdhesionPersonalDatas',
                    'AdhesionAddresses',
                    'AdhesionDependents',
                    'AdhesionPlans',
                    'AdhesionDocuments',
                    'AdhesionOtherInformations',
                    'AdhesionPaymentDetails',
                    'AdhesionProponentStatements'
                ]
            ]);

            // Gerar UUID se não existir
            if (!$adhesion->storage_uuid) {
                $adhesion->storage_uuid = \Cake\Utility\Text::uuid();
            }

            if ($this->AdhesionInitialDatas->save($adhesion)) {
                $this->savePensionSchemes($adhesion->id, $this->request->getData());
                $this->Flash->success(__('A adesão foi salva com sucesso.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('A adesão não pôde ser salva. Por favor, tente novamente.'));
        }
        $this->set(compact('adhesion'));
    }

    private function savePensionSchemes($adhesionInitialDataId, array $data): void
    {
        $this->AdhesionPensionSchemes->deleteAll(['adhesion_initial_data_id' => $adhesionInitialDataId]);

        foreach ((array)($data['pension_scheme_type'] ?? []) as $pensionSchemeType) {
            $pensionScheme = $this->AdhesionPensionSchemes->newEntity([
                'adhesion_initial_data_id' => $adhesionInitialDataId,
                'pension_scheme' => $pensionSchemeType,
                'name' => $data['pension_scheme_name'] ?? null,
                'cpf' => $data['pension_scheme_cpf'] ?? null,
                'kinship' => $data['pension_scheme_kinship'] ?? null,
            ]);

            $this->AdhesionPensionSchemes->save($pensionScheme);
        }
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
                'AdhesionOtherInformations',
                'AdhesionPaymentDetails',
                'AdhesionPensionSchemes',
                'AdhesionProponentStatements'
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
                        'AdhesionOtherInformations',
                        'AdhesionPaymentDetails',
                        'AdhesionProponentStatements'
                    ]
                ]
            );

            if ($this->AdhesionInitialDatas->save($adhesion)) {
                $this->savePensionSchemes($id, $this->request->getData());
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

    public function generatePdf($id, $returnContent = false)
    {
        $pdf = $this->PdfGenerator->generatePdfApplicationForm($id, $returnContent);

        if (!$returnContent)
            return $pdf;

        $this->response = $this->response->withType('pdf');
        $this->viewBuilder()->setLayout('pdfPreview');
        $this->set(['pdf' => $pdf]);

        return $this->render('pdfPreview');
    }

    public function generateFormPdf($id)
    {
        return $this->PdfGenerator->generateRegistrationFormPdf($id);
    }
}
