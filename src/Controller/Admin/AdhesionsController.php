<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Model\Table\AdhesionInitialDatasTable;
use App\Model\Table\AdhesionPensionSchemesTable;
use App\Model\Entity\AdhesionAudit;
use App\Model\Table\PartnersTable;
use App\Services\AdhesionAuditor;
use App\Services\ClicksignService;
use App\Services\PixPaymentService;
use App\Services\SicoobService;

class AdhesionsController extends AppController
{
    protected AdhesionInitialDatasTable $AdhesionInitialDatas;
    protected AdhesionPensionSchemesTable $AdhesionPensionSchemes;
    protected PartnersTable $Partners;
    protected AdhesionAuditor $auditor;

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('PdfGenerator');

        $this->AdhesionInitialDatas = $this->fetchTable('AdhesionInitialDatas');
        $this->AdhesionPensionSchemes = $this->fetchTable('AdhesionPensionSchemes');
        $this->Partners = $this->fetchTable('Partners');
        $this->auditor = new AdhesionAuditor($this->fetchTable('AdhesionAudits'));

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
                'PromotionalCodes.Partners',
            ]);

        $searchName = $this->request->getQuery('name');
        $searchCpf  = $this->request->getQuery('cpf');
        $searchPromotionalCode = $this->request->getQuery('promotionalCode');
        $searchPartnerId = $this->request->getQuery('partnerId');
        $searchBrokerCode = $this->request->getQuery('brokerCode');

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

        if ($searchPromotionalCode) {
            // Compara com o snapshot gravado na adesão, para que o filtro continue
            // funcionando mesmo que o código tenha sido editado depois.
            $query->where([
                'AdhesionInitialDatas.promotional_code' => \App\Model\Entity\PromotionalCode::normalizeCode($searchPromotionalCode),
            ]);
        }

        if ($searchPartnerId) {
            // O parceiro só é conhecido pelo vínculo atual, então adesões cujo
            // código foi excluído (o parceiro nunca é, se o código foi usado)
            // não entram nesse filtro.
            $query->innerJoinWith('PromotionalCodes')
                ->where(['PromotionalCodes.partner_id' => (int)$searchPartnerId]);
        }

        if ($searchBrokerCode) {
            // Mesmo padrão do filtro de código promocional: compara com o
            // snapshot gravado na adesão, não com o cadastro atual.
            $query->where([
                'AdhesionInitialDatas.broker_code' => \App\Model\Entity\Broker::normalizeCode($searchBrokerCode),
            ]);
        }

        $adhesions = $this->paginate($query);
        $partners = $this->Partners->find()->orderBy(['name' => 'ASC'])->all();

        $this->set(compact('adhesions', 'partners'));
    }

    public function view($id)
    {
        $adhesion = $this->AdhesionInitialDatas->get($id, contain: [
            ...AdhesionAuditor::CONTAINS,
            'PixTransactions' => ['sort' => ['PixTransactions.attempt' => 'DESC']],
            'IntegrationLogs' => ['sort' => ['IntegrationLogs.created' => 'DESC']],
            'AdhesionAudits' => ['sort' => ['AdhesionAudits.created' => 'DESC']],
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

                // Sem diff: numa criação todo campo "mudou", e listar os
                // oitenta não diz nada que a própria adesão não diga.
                $this->auditor->record($adhesion->id, $this->currentUser(), AdhesionAudit::ACTION_CREATED);

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
        $adhesion = $this->AdhesionInitialDatas->get($id, contain: AdhesionAuditor::CONTAINS);

        if ($this->request->is(['patch', 'post', 'put'])) {
            // O retrato tem que sair daqui: patchEntity() altera o mesmo
            // objeto, e depois dele não existe mais um "antes" para comparar.
            $before = AdhesionAuditor::snapshot($adhesion);

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
                $this->recordEdit((int)$id, $before);

                $this->Flash->success('Cliente atualizado com sucesso.');
                return $this->redirect(['action' => 'view', $id]);
            }

            $this->Flash->error('Erro ao salvar, revise os dados.');
        }

        $this->set(compact('adhesion'));
    }

    /**
     * Compara o retrato de antes com o estado recém-gravado, registra o que
     * mudou, e marca o plano como ajustado à mão quando a mudança foi nele.
     */
    private function recordEdit(int $id, array $before): void
    {
        // Relê em vez de reaproveitar a entidade salva: savePensionSchemes()
        // apaga e recria os regimes fora do grafo, então o que está em
        // memória não conhece o estado final.
        $saved = $this->AdhesionInitialDatas->get($id, contain: AdhesionAuditor::CONTAINS);
        $changes = AdhesionAuditor::diff($before, AdhesionAuditor::snapshot($saved));

        $this->auditor->record($id, $this->currentUser(), AdhesionAudit::ACTION_UPDATED, $changes);

        $this->markPlanOverridden($saved, $changes);
    }

    /**
     * Mexer no plano pelo admin é o que caracteriza valor negociado. A flag
     * existe para o formulário público não desfazer isso depois: com ela
     * ligada, o passo do Plano e a data de nascimento ficam somente-leitura
     * para o cliente, já que os valores foram calculados para aquela idade.
     */
    private function markPlanOverridden(\Cake\Datasource\EntityInterface $adhesion, array $changes): void
    {
        $touchedPlan = array_filter(
            array_keys($changes),
            fn(string $field): bool => str_starts_with($field, 'adhesion_plan.')
        );

        if ($touchedPlan === [] || $adhesion->get('adhesion_plan') === null) {
            return;
        }

        $plans = $this->fetchTable('AdhesionPlans');

        $plans->save($plans->patchEntity($adhesion->get('adhesion_plan'), [
            'admin_overridden' => true,
            'admin_overridden_by_user_id' => $this->currentUser()?->get('id'),
            'admin_overridden_at' => \Cake\I18n\DateTime::now(),
        ]));
    }

    /**
     * O usuário da sessão do admin, ou null quando a ação não veio de uma
     * sessão autenticada.
     */
    private function currentUser(): ?\Cake\Datasource\EntityInterface
    {
        $identity = $this->Authentication->getIdentity();
        $user = $identity?->getOriginalData();

        return $user instanceof \Cake\Datasource\EntityInterface ? $user : null;
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
