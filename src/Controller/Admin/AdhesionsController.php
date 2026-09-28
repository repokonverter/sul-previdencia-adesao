<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Model\Table\AdhesionInitialDatasTable;
use App\Model\Table\AdhesionPensionSchemesTable;
use App\Model\Entity\AdhesionAudit;
use Cake\Routing\Router;
use App\Model\Table\PartnersTable;
use App\Services\AdhesionAuditor;
use App\Services\AdhesionSteps;
use App\Services\ClicksignService;
use App\Services\PixPaymentService;
use App\Services\SicoobService;
use App\Utility\Money;

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
        $this->auditor = new AdhesionAuditor(
            $this->fetchTable('AdhesionAudits'),
            $this->fetchTable('AdhesionDeletions')
        );

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
                'ClicksignDatas' => ['sort' => ['ClicksignDatas.attempt' => 'DESC']],
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

        // Uma por adesão da página: o botão "Link de retomada" da listagem
        // abre um modal por linha, e cada um precisa da mesma informação que
        // view() monta para o seu -- sem query extra, forPicker() só lê
        // associações que o find() acima já carregou.
        $resumeInfo = [];
        foreach ($adhesions as $adhesion) {
            $resumeInfo[$adhesion->id] = $this->resumeLinkInfo($adhesion);
        }

        $this->set(compact('adhesions', 'partners', 'resumeInfo'));
    }

    /**
     * @return array{steps: array, suggestedStep: string, url: string|null}
     */
    private function resumeLinkInfo(\Cake\Datasource\EntityInterface $adhesion): array
    {
        return [
            'steps' => AdhesionSteps::forPicker($adhesion),
            'suggestedStep' => AdhesionSteps::firstIncomplete($adhesion),
            'url' => $adhesion->resume_token === null ? null : Router::url(
                ['controller' => 'Simulator', 'action' => 'resume', $adhesion->resume_token, 'prefix' => false],
                true
            ),
        ];
    }

    public function view($id)
    {
        $adhesion = $this->AdhesionInitialDatas->get($id, contain: [
            ...AdhesionAuditor::CONTAINS,
            'PixTransactions' => ['sort' => ['PixTransactions.attempt' => 'DESC']],
            'IntegrationLogs' => ['sort' => ['IntegrationLogs.created' => 'DESC']],
            'AdhesionAudits' => ['sort' => ['AdhesionAudits.created' => 'DESC']],
            'ClicksignDatas' => ['sort' => ['ClicksignDatas.attempt' => 'DESC']],
        ]);

        $resume = $this->resumeLinkInfo($adhesion);

        $this->set([
            'adhesion' => $adhesion,
            'resumeSteps' => $resume['steps'],
            'suggestedStep' => $resume['suggestedStep'],
            'resumeUrl' => $resume['url'],
        ]);
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

    /**
     * Opções do select de corretor, só com os ativos mais o que a adesão já
     * tem: um corretor desativado depois não some da adesão que ele trouxe.
     */
    /**
     * Campos que carregam o conteúdo econômico do contrato. Dados cadastrais
     * (endereço, telefone, nome da mãe) ficam de fora de propósito: corrigir
     * um telefone continua possível em qualquer estado.
     */
    private const ECONOMIC_FIELDS = [
        'adhesion_plan',
        'adhesion_dependents',
        'adhesion_payment_detail',
    ];

    /**
     * Campos que a tela renderiza com a máscara jQuery `money`: pt-BR, ponto
     * de milhar e vírgula decimal (ex.: "1.234,56"). O tipo `decimal` da
     * coluna só aceita ponto decimal -- sem essa conversão, patchEntity()
     * grava a string como veio e o Postgres recusa com "Cannot convert value
     * ... to a decimal".
     */
    private const MONEY_FIELDS = [
        ['adhesion_plan', 'monthly_retirement_contribution'],
        ['adhesion_plan', 'monthly_survivors_pension_contribution'],
        ['adhesion_plan', 'survivors_pension_insured_capital'],
        ['adhesion_plan', 'monthly_disability_retirement_contribution'],
        ['adhesion_plan', 'disability_retirement_insured_capital'],
        ['adhesion_other_information', 'monthly_income'],
        ['adhesion_payment_detail', 'total_contribution'],
    ];

    /**
     * Peso e altura usam a mesma máscara, mas sem separador de milhar -- só
     * trocar a vírgula por ponto basta.
     */
    private const DECIMAL_FIELDS = [
        ['adhesion_proponent_statement', 'weight'],
        ['adhesion_proponent_statement', 'height'],
    ];

    private function normalizeNumericFields(array $data): array
    {
        foreach (self::MONEY_FIELDS as [$section, $field]) {
            if (isset($data[$section][$field])) {
                $data[$section][$field] = Money::parse($data[$section][$field]);
            }
        }

        foreach (self::DECIMAL_FIELDS as [$section, $field]) {
            if (isset($data[$section][$field]) && $data[$section][$field] !== '') {
                $data[$section][$field] = str_replace(',', '.', (string)$data[$section][$field]);
            }
        }

        return $data;
    }

    /**
     * A tela sempre envia os campos de todas as abas no mesmo POST, mesmo as
     * que o admin nunca abriu -- inclusive alguns já vêm com valor por padrão
     * (o rádio "Titular" do plano, o "10" fixo do dia de vencimento), então a
     * seção não chega vazia nem quando ninguém a preencheu.
     *
     * Sem isto, "Salvar Mesmo Incompleto" criaria as sete linhas associadas
     * de uma adesão que só tem os dados iniciais -- e o resto do admin (a
     * etapa mostrada na lista, os botões de PDF) lê "a linha existe" como "a
     * etapa foi concluída", então uma proposta mal começada passaria a
     * aparecer como finalizada.
     *
     * A campo-sinal de cada seção não tem valor padrão nem é enviado à toa:
     * só chega preenchido se alguém de fato respondeu aquele pedaço do
     * formulário. Uma seção cujo sinal está vazio e que a adesão ainda não
     * tinha é descartada do patch; editar uma seção que já existe nunca é
     * afetado, então apagar um campo à mão continua possível.
     */
    private const SIGNAL_FIELDS = [
        'adhesion_personal_data' => 'cpf',
        'adhesion_document' => 'document_number',
        'adhesion_address' => 'cep',
        'adhesion_other_information' => 'category',
        'adhesion_plan' => 'monthly_retirement_contribution',
        'adhesion_proponent_statement' => 'health_problem',
        'adhesion_payment_detail' => 'payment_type',
    ];

    private function stripUntouchedNewSections(\Cake\Datasource\EntityInterface $adhesion, array $data): array
    {
        foreach (self::SIGNAL_FIELDS as $property => $signalField) {
            if ($adhesion->get($property) !== null) {
                continue;
            }

            $signalValue = $data[$property][$signalField] ?? null;

            if ($signalValue === null || $signalValue === '') {
                unset($data[$property]);
            }
        }

        return $data;
    }

    /**
     * Com o Pix pago, mudar valor, risco, beneficiário ou conta bancária
     * deixa de ser edição de cadastro e vira evento contábil: o dinheiro já
     * entrou sobre os números antigos.
     *
     * Adesão assinada trava pelo mesmo motivo: editar valor depois da
     * assinatura faria o registro divergir do documento que a pessoa de fato
     * assinou. "Reabrir proposta" é a única saída, e só existe para o lado da
     * assinatura -- dinheiro já recebido não tem botão equivalente.
     */
    private function economicallyLocked($adhesion): bool
    {
        $reason = $this->lockReason($adhesion);

        return $reason['paid'] || $reason['signed'];
    }

    /**
     * Lê a tentativa mais recente por consulta própria, e não pelo `contain`
     * da adesão: essa entidade também alimenta o retrato de
     * AdhesionAuditor::snapshot(), e clicksign_datas não é conteúdo que
     * edit() edite -- incluí-lo no contain principal faria a mera presença da
     * associação (carregada aqui, ausente na releitura de recordEdit(), que
     * usa AdhesionAuditor::CONTAINS) aparecer como uma "alteração" toda vez.
     *
     * @return array{paid: bool, signed: bool}
     */
    private function lockReason($adhesion): array
    {
        $paid = $this->fetchTable('PixTransactions')->exists([
            'adhesion_initial_data_id' => $adhesion->id,
            'paid' => true,
        ]);

        $latest = $this->fetchTable('ClicksignDatas')->latestFor((int)$adhesion->id);
        $signed = $latest !== null && $latest->isSigned() && !$latest->isReopened();

        return ['paid' => $paid, 'signed' => $signed];
    }

    /**
     * Destrava a edição de uma adesão travada por assinatura.
     *
     * Nunca mexe no envelope nem no que está gravado como assinado: o
     * contrato que a pessoa assinou continua sendo exatamente aquele,
     * preservado como prova histórica. O que muda é só a permissão de editar
     * a partir de agora -- se a edição pedir nova assinatura, é o botão
     * "Regerar documentos" que cria um envelope novo (ClicksignEnvelopeSender
     * já cancela o anterior quando possível; um envelope fechado não pode ser
     * cancelado e permanece como está, o que é o comportamento certo).
     *
     * Recusa se a adesão também estiver paga: dinheiro já recebido não é
     * destravado por um clique administrativo sem processo de conciliação, e
     * esta ação deliberadamente não tenta.
     */
    public function reopenProposal($id)
    {
        $this->request->allowMethod(['post']);

        $paid = $this->fetchTable('PixTransactions')->exists(['adhesion_initial_data_id' => $id, 'paid' => true]);

        if ($paid) {
            $this->Flash->error(
                'Esta adesão tem pagamento confirmado: dinheiro já recebido não é destravado por aqui. '
                . 'Trate a devolução ou ajuste diretamente com o Sicoob, se for o caso.'
            );

            return $this->redirect(['action' => 'view', $id]);
        }

        $clicksignDatas = $this->fetchTable('ClicksignDatas');
        $latest = $clicksignDatas->latestFor((int)$id);

        if ($latest === null || !$latest->isSigned()) {
            $this->Flash->error('Esta adesão não está travada por assinatura.');

            return $this->redirect(['action' => 'view', $id]);
        }

        $clicksignDatas = $this->fetchTable('ClicksignDatas');
        $clicksignDatas->saveOrFail($clicksignDatas->patchEntity($latest, [
            'reopened_at' => \Cake\I18n\DateTime::now(),
            'reopened_by_user_id' => $this->currentUser()?->get('id'),
        ]));

        $this->auditor->record((int)$id, $this->currentUser(), AdhesionAudit::ACTION_UPDATED, [
            'proposta' => ['travada por assinatura', 'reaberta para edição'],
        ]);

        $this->Flash->success(
            'Proposta reaberta. O contrato já assinado continua preservado; uma edição que exigir nova '
            . 'assinatura gera um envelope novo ao regerar os documentos.'
        );

        return $this->redirect(['action' => 'view', $id]);
    }

    private function brokerOptions(?int $current = null): array
    {
        $conditions = $current === null
            ? ['Brokers.active' => true]
            : ['OR' => ['Brokers.active' => true, 'Brokers.id' => $current]];

        return $this->fetchTable('Brokers')->find()
            ->where($conditions)
            ->orderBy(['Brokers.name' => 'ASC'])
            ->all()
            ->combine('id', fn($broker) => $broker->name . ' (' . $broker->code . ')')
            ->toArray();
    }

    public function add()
    {
        $adhesion = $this->AdhesionInitialDatas->newEmptyEntity();
        if ($this->request->is('post')) {
            $adhesion = $this->AdhesionInitialDatas->patchEntity($adhesion, $this->normalizeNumericFields($this->request->getData()), [
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

        $brokers = $this->brokerOptions();
        $economicallyLocked = false;
        $lockReason = ['paid' => false, 'signed' => false];

        $this->set(compact('adhesion', 'brokers', 'economicallyLocked', 'lockReason'));
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
        $lockReason = $this->lockReason($adhesion);
        $economicallyLocked = $lockReason['paid'] || $lockReason['signed'];

        if ($this->request->is(['patch', 'post', 'put'])) {
            // O retrato tem que sair daqui: patchEntity() altera o mesmo
            // objeto, e depois dele não existe mais um "antes" para comparar.
            $before = AdhesionAuditor::snapshot($adhesion);
            $data = $this->stripUntouchedNewSections(
                $adhesion,
                $this->normalizeNumericFields($this->request->getData())
            );

            if ($economicallyLocked) {
                $refused = array_intersect_key($data, array_flip(self::ECONOMIC_FIELDS));
                $data = array_diff_key($data, array_flip(self::ECONOMIC_FIELDS));

                // A tela já mostra esses campos bloqueados, mas o POST é
                // forjável e quem recusa é o servidor.
                if ($refused !== []) {
                    $this->Flash->error(
                        'Esta adesão já foi paga: valores, riscos, beneficiários e conta bancária '
                        . 'não podem mais ser alterados por aqui. Os demais dados foram salvos.'
                    );
                }
            }

            $adhesion = $this->AdhesionInitialDatas->patchEntity(
                $adhesion,
                $data,
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

        $brokers = $this->brokerOptions($adhesion->broker_id);

        $this->set(compact('adhesion', 'brokers', 'economicallyLocked', 'lockReason'));
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
        $item = $this->AdhesionInitialDatas->get($id, contain: ['AdhesionPersonalDatas']);

        if ($this->AdhesionInitialDatas->delete($item)) {
            // Registrado depois de apagar, e não antes: assim não sobra
            // registro de uma exclusão que não chegou a acontecer. A entidade
            // continua em memória, com o nome e o CPF que identificam o que
            // foi apagado.
            $this->auditor->recordDeletion($item, $this->currentUser());

            $this->Flash->success('Registro removido.');
        } else {
            $this->Flash->error('Erro ao excluir o registro.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Lista as adesões excluídas. Precisa de tela própria porque o registro
     * sobrevive à adesão: não há mais onde exibi-lo dentro dela.
     */
    public function deletions()
    {
        $deletions = $this->paginate(
            $this->fetchTable('AdhesionDeletions')->find()->orderBy(['AdhesionDeletions.created' => 'DESC']),
            ['order' => ['AdhesionDeletions.created' => 'DESC']]
        );

        $this->set(compact('deletions'));
    }

    /**
     * Gera o link de retomada, invalidando o anterior.
     *
     * A etapa é conferida contra AdhesionSteps: aceitar qualquer string do
     * POST deixaria o link apontar para uma etapa que não existe, e o
     * formulário cairia calado na primeira.
     */
    /**
     * Pergunta à Clicksign diretamente se a tentativa corrente foi
     * assinada -- mesmo botão manual de checkPixPayment(), mesmo motivo de
     * existir: o webhook é gatilho de melhor esforço, e este é o caminho que
     * não depende dele estar cadastrado nem de adivinhar o payload.
     */
    public function checkClicksignStatus($id)
    {
        $this->request->allowMethod(['post']);

        $tab = $this->request->getData('tab');
        $clicksignDatas = $this->fetchTable('ClicksignDatas');
        $latest = $clicksignDatas->latestFor((int)$id);

        if (!$latest) {
            $this->Flash->error('Nenhum envelope foi enviado para esta adesão ainda.');

            return $this->redirect(['action' => 'view', $id, '?' => array_filter(['tab' => $tab])]);
        }

        try {
            $result = \App\Services\ClicksignSignatureChecker::fromConfigure($clicksignDatas)->refresh($latest);

            if (!$result['found'])
                $this->Flash->error('Envelope não encontrado na Clicksign.');
            elseif ($result['signed'] ?? false)
                $this->Flash->success('Assinatura confirmada na Clicksign.');
            else
                $this->Flash->info('Ainda não há confirmação de assinatura na Clicksign (status: ' . ($result['status'] ?? 'desconhecido') . ').');
        } catch (\Exception $e) {
            $this->Flash->error('Falha ao consultar a Clicksign: ' . $e->getMessage());
        }

        return $this->redirect(['action' => 'view', $id, '?' => array_filter(['tab' => $tab])]);
    }

    /**
     * Baixa um documento assinado, sempre por proxy: o link da Clicksign é
     * uma URL pré-assinada do S3, sem autenticação própria, válida por ~5
     * minutos. Em redirect ela entraria no histórico do navegador e em logs
     * de proxy; assim o único portão continua sendo a sessão do admin.
     */
    public function downloadSignedDocument($id, $documentId)
    {
        $adhesion = $this->AdhesionInitialDatas->get($id, contain: ['ClicksignDatas']);
        $latest = $this->fetchTable('ClicksignDatas')->latestFor((int)$id);

        if ($latest === null || $latest->envelope_id === null) {
            $this->Flash->error('Esta adesão não tem envelope de assinatura.');

            return $this->redirect(['action' => 'view', $id, '?' => ['tab' => 'integrationLogs']]);
        }

        try {
            $clicksign = new \App\Services\ClicksignService(
                \Cake\Core\Configure::read('Clicksign.baseUrl'),
                \Cake\Core\Configure::read('Clicksign.accessToken')
            );
            $clicksign->forAdhesion((int)$id);

            $document = $clicksign->getDocument($latest->envelope_id, $documentId);
            $links = $document['data']['links']['files'] ?? [];
            // "signed" só existe depois do documento fechado; sem ele, cai no
            // original -- útil para conferir o que foi enviado antes de
            // assinado, mas nunca é o que se quer entregar como comprovante.
            $fileUrl = $links['signed'] ?? $links['original'] ?? null;
            $filename = $document['data']['attributes']['filename'] ?? 'documento.pdf';

            if ($fileUrl === null) {
                $this->Flash->error('Não foi possível obter o documento agora. Tente novamente.');

                return $this->redirect(['action' => 'view', $id, '?' => ['tab' => 'integrationLogs']]);
            }

            $bytes = (new \Cake\Http\Client())->get($fileUrl)->getBody();

            $this->autoRender = false;

            return $this->response
                ->withType('application/pdf')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withStringBody((string)$bytes);
        } catch (\Exception $e) {
            $this->Flash->error('Não foi possível obter o documento assinado agora: ' . $e->getMessage());

            return $this->redirect(['action' => 'view', $id, '?' => ['tab' => 'integrationLogs']]);
        }
    }

    public function issueResumeLink($id)
    {
        $this->request->allowMethod(['post']);

        $adhesion = $this->AdhesionInitialDatas->get($id, contain: AdhesionAuditor::CONTAINS);
        $step = $this->request->getData('step');

        if (!AdhesionSteps::exists($step)) {
            $this->Flash->error('Etapa inválida.');

            return $this->redirect(['action' => 'view', $id, '?' => ['openResumeModal' => 1]]);
        }

        if (!AdhesionSteps::forPicker($adhesion)[$step]['selectable']) {
            $this->Flash->error(
                'Esta etapa depende de outra que ainda está em branco: o proponente a pularia sem preencher.'
            );

            return $this->redirect(['action' => 'view', $id, '?' => ['openResumeModal' => 1]]);
        }

        $days = $this->fetchTable('PlanParameters')->current()->resumeLinkDays();
        $this->AdhesionInitialDatas->issueResumeToken($adhesion, $days, $step);

        $this->auditor->record((int)$id, $this->currentUser(), AdhesionAudit::ACTION_UPDATED, [
            'resume_link' => ['', 'gerado para a etapa "' . AdhesionSteps::ORDER[$step] . '", válido por ' . $days . ' dias'],
        ]);

        $this->Flash->success('Link gerado. O anterior, se havia, deixou de valer.');

        return $this->redirect(['action' => 'view', $id, '?' => ['openResumeModal' => 1]]);
    }

    /**
     * Manda o link por e-mail. O endereço vem preenchido com o da adesão mas
     * é editável — e-mail errado é justamente um dos motivos pelos quais a
     * proposta empacou. Digitar outro não reescreve o cadastro: um envio
     * pontual não é uma correção de dados.
     */
    public function sendResumeLink($id)
    {
        $this->request->allowMethod(['post']);

        $adhesion = $this->AdhesionInitialDatas->get($id, contain: ['AdhesionPersonalDatas']);
        $email = trim((string)$this->request->getData('email'));

        if ($adhesion->resume_token === null || $adhesion->resumeTokenHasExpired()) {
            $this->Flash->error('Não há link ativo para enviar. Gere um primeiro.');

            return $this->redirect(['action' => 'view', $id, '?' => ['openResumeModal' => 1]]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->Flash->error('Informe um e-mail válido.');

            return $this->redirect(['action' => 'view', $id, '?' => ['openResumeModal' => 1]]);
        }

        $url = Router::url(
            ['controller' => 'Simulator', 'action' => 'resume', $adhesion->resume_token, 'prefix' => false],
            true
        );

        try {
            \App\Services\ResendService::fromConfigure()->forAdhesion((int)$id)->send(
                [$email],
                'Continue sua proposta de adesão',
                \App\Services\EmailTemplates::resumeProposal(
                    $adhesion->adhesion_personal_data->name ?? $adhesion->name ?? 'Olá',
                    $url,
                    AdhesionSteps::ORDER[$adhesion->resume_step] ?? 'a primeira etapa',
                    $this->fetchTable('PlanParameters')->current()->resumeLinkDays()
                )
            );

            $this->auditor->record((int)$id, $this->currentUser(), AdhesionAudit::ACTION_UPDATED, [
                'resume_link' => ['', 'enviado por e-mail para ' . $email],
            ]);

            $this->Flash->success('Link enviado para ' . $email . '.');
        } catch (\Exception $e) {
            \Cake\Log\Log::error('Falha ao enviar link de retomada da adesão #' . $id . ': ' . $e->getMessage());

            $this->Flash->error('Não foi possível enviar o e-mail agora. O link continua válido para copiar.');
        }

        return $this->redirect(['action' => 'view', $id, '?' => ['openResumeModal' => 1]]);
    }

    public function revokeResumeLink($id)
    {
        $this->request->allowMethod(['post']);

        $adhesion = $this->AdhesionInitialDatas->get($id);

        $this->AdhesionInitialDatas->revokeResumeToken($adhesion);

        $this->auditor->record((int)$id, $this->currentUser(), AdhesionAudit::ACTION_UPDATED, [
            'resume_link' => ['ativo', 'revogado'],
        ]);

        $this->Flash->success('Link revogado.');

        return $this->redirect(['action' => 'view', $id, '?' => ['openResumeModal' => 1]]);
    }

    /**
     * Regera os documentos e manda para assinatura de novo.
     *
     * Nunca automático depois de uma edição: um erro de digitação no
     * formulário do admin dispararia envelope novo e e-mail ao proponente
     * pedindo que assine outra vez. Quem decide incomodar o cliente é o admin.
     *
     * O envelope anterior é cancelado e nasce outro: a Clicksign só apaga
     * documento de envelope em `draft`, então trocar os PDFs do que já saiu
     * para assinatura não é possível.
     */
    public function regenerateDocuments($id)
    {
        $this->request->allowMethod(['post']);

        $adhesion = $this->AdhesionInitialDatas->get($id, contain: AdhesionAuditor::CONTAINS);

        if ($adhesion->adhesion_payment_detail === null) {
            $this->Flash->error('Esta adesão ainda não foi finalizada: não há documentos a regerar.');

            return $this->redirect(['action' => 'view', $id, '?' => ['tab' => 'integrationLogs']]);
        }

        try {
            \App\Services\ClicksignEnvelopeSender::fromConfigure($this->fetchTable('ClicksignDatas'))
                ->send($adhesion, [
                    [
                        'file' => base64_encode($this->PdfGenerator->generatePdfApplicationForm($id, true)),
                        'name' => 'proposta_adesao.pdf',
                    ],
                    [
                        'file' => base64_encode($this->PdfGenerator->generateRegistrationFormPdf($id, true)),
                        'name' => 'formulario_inscricao.pdf',
                    ],
                ]);

            $this->auditor->record((int)$id, $this->currentUser(), AdhesionAudit::ACTION_UPDATED, [
                'documentos' => ['', 'regerados e reenviados para assinatura'],
            ]);

            $this->Flash->success('Documentos regerados e enviados para assinatura. O envelope anterior foi cancelado.');
        } catch (\Exception $e) {
            $this->Flash->error('Falha ao regerar os documentos: ' . $e->getMessage());
        }

        return $this->redirect(['action' => 'view', $id, '?' => ['tab' => 'integrationLogs']]);
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
