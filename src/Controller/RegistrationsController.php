<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use App\Model\Table\AdhesionAddressesTable;
use App\Model\Table\AdhesionDependentsTable;
use App\Model\Table\AdhesionDocumentsTable;
use App\Model\Table\AdhesionInitialDatasTable;
use App\Model\Table\AdhesionOtherInformationsTable;
use App\Model\Table\AdhesionPaymentDetailsTable;
use App\Model\Table\AdhesionPensionSchemesTable;
use App\Model\Table\AdhesionPersonalDatasTable;
use App\Model\Table\AdhesionPlansTable;
use App\Model\Table\AdhesionProponentStatementsTable;
use App\Model\Table\ClicksignDatasTable;
use App\Model\Table\PromotionalCodesTable;
use App\Services\IntegrationLogger;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\DateTime;
use Cake\Http\Exception\NotFoundException;
use Cake\Log\Log;
use Cake\Http\Client;
use Cake\Core\Configure;
use App\View\Helper\BankHelper;
use Cake\Routing\Router;
use App\Services\AdhesionFormMap;
use App\Utility\Money;
use Cake\Utility\Text;
use Cake\View\View;

class RegistrationsController extends AppController
{
    protected AdhesionAddressesTable $AdhesionAddresses;
    protected AdhesionDependentsTable $AdhesionDependents;
    protected AdhesionDocumentsTable $AdhesionDocuments;
    protected AdhesionInitialDatasTable $AdhesionInitialDatas;
    protected AdhesionOtherInformationsTable $AdhesionOtherInformations;
    protected AdhesionPersonalDatasTable $AdhesionPersonalDatas;
    protected AdhesionPlansTable $AdhesionPlans;
    protected AdhesionProponentStatementsTable $AdhesionProponentStatements;
    protected AdhesionPensionSchemesTable $AdhesionPensionSchemes;
    protected AdhesionPaymentDetailsTable $AdhesionPaymentDetails;
    protected ClicksignDatasTable $ClicksignDatas;
    protected PromotionalCodesTable $PromotionalCodes;
    protected BankHelper $Bank;

    public function initialize(): void
    {
        parent::initialize();

        $this->AdhesionAddresses = $this->fetchTable('AdhesionAddresses');
        $this->AdhesionDependents = $this->fetchTable('AdhesionDependents');
        $this->AdhesionDocuments = $this->fetchTable('AdhesionDocuments');
        $this->AdhesionInitialDatas = $this->fetchTable('AdhesionInitialDatas');
        $this->AdhesionPersonalDatas = $this->fetchTable('AdhesionPersonalDatas');
        $this->AdhesionPlans = $this->fetchTable('AdhesionPlans');
        $this->AdhesionOtherInformations = $this->fetchTable('AdhesionOtherInformations');
        $this->AdhesionProponentStatements = $this->fetchTable('AdhesionProponentStatements');
        $this->AdhesionPensionSchemes = $this->fetchTable('AdhesionPensionSchemes');
        $this->AdhesionPaymentDetails = $this->fetchTable('AdhesionPaymentDetails');
        $this->ClicksignDatas = $this->fetchTable('ClicksignDatas');
        $this->PromotionalCodes = $this->fetchTable('PromotionalCodes');

        $this->loadComponent('PdfGenerator');

        $this->Bank = new BankHelper(new View());

        $this->viewBuilder()->setClassName('Ajax');

        $this->autoRender = false;
    }

    public function save()
    {
        $this->request->allowMethod(['get', 'ajax']);

        $data = $this->request->getData();
        $connection = $this->AdhesionInitialDatas->getConnection();
        $connection->begin();
        $adhesionCommitted = false;

        try {
            $initialDataId = isset($data['initialDataId']) ? $data['initialDataId'] : null;
            $storageUuid = null;

            if ($initialDataId !== null) {
                $initialDataAll = $this->AdhesionInitialDatas->get(
                    $initialDataId,
                    contain: [
                        'AdhesionPersonalDatas',
                        'AdhesionDocuments',
                        'AdhesionPlans',
                        'AdhesionDependents',
                        'AdhesionAddresses',
                        'AdhesionOtherInformations',
                        'AdhesionProponentStatements',
                        'AdhesionPensionSchemes',
                        'AdhesionPaymentDetails',
                        'ClicksignDatas',
                        'PixTransactions',
                    ]
                );

                // O id da adesão é sequencial e chega pelo POST; sozinho, ele
                // deixaria qualquer visitante reescrever a adesão de outra
                // pessoa trocando o número. Quem autoriza a escrita é o
                // storage_uuid, e ele é conferido contra o que está gravado —
                // antes era apenas regravado por cima, o que não conferia nada.
                if (!hash_equals((string)$initialDataAll->storage_uuid, (string)($data['storageUuid'] ?? ''))) {
                    throw new NotFoundException();
                }

                $storageUuid = $initialDataAll->storage_uuid;
            }

            if (isset($data['initialData'])) {
                $initialData = $data['initialData'];
                $initial = $initialDataId === null ? $this->AdhesionInitialDatas->newEmptyEntity() : $this->AdhesionInitialDatas->get($initialDataId);

                $patchData = [
                    'name' => $initialData['name'] ?? '',
                    'email' => $initialData['email'] ?? null,
                    'phone' => $initialData['phone'] ?? null,
                ];

                // O storage_uuid autoriza toda escrita seguinte nesta adesão e
                // é a chave pública do link de pagamento, então nasce aqui e
                // nunca é aceito do navegador — que antes o gerava, caindo em
                // Math.random() + Date.now() quando crypto.randomUUID não
                // existia, e reaproveitava o mesmo valor entre adesões.
                if ($initial->isNew()) {
                    $patchData['storage_uuid'] = Text::uuid();
                }

                // Uma vez atribuído, o código promocional permanece mesmo que
                // ele seja desativado depois (parceiro incluído) ou que um
                // passo seguinte reenvie initialData sem o campo.
                if (empty($initial->promotional_code_id)) {
                    // O código enviado pelo formulário é revalidado aqui: a
                    // checagem no navegador é conveniência, não garantia.
                    $promotionalCode = $this->PromotionalCodes->findByCodeText($initialData['promotionalCode'] ?? null);

                    if ($promotionalCode !== null && $promotionalCode->isUsable()) {
                        $patchData['promotional_code_id'] = $promotionalCode->id;
                        $patchData['promotional_code'] = $promotionalCode->code;

                        // Um código de vínculo associativo (parceiro com
                        // is_association = true) grava o vínculo junto, na
                        // mesma chamada. Os textos da Declaração são
                        // congelados agora, para que o PDF gerado depois
                        // reproduza sempre o que foi de fato assinado, mesmo
                        // que o cadastro do vínculo mude no futuro.
                        $codePartner = $promotionalCode->partner;

                        if ($codePartner !== null && $codePartner->is_association) {
                            $patchData['association_partner_id'] = $codePartner->id;
                            $patchData['association_snapshot'] = json_encode($codePartner->declarationTexts());
                        }
                    }
                }

                // Código promocional continua opcional para quem respondeu
                // ter vínculo (ver templates/Simulator/index.php): sem ele, o
                // vínculo escolhido no <select> precisa poder ser gravado
                // sozinho. Nunca é aceito isoladamente a partir do que o
                // front-end alega, só quando o id aponta para um vínculo
                // ativo de verdade.
                if (
                    empty($initial->association_partner_id)
                    && empty($patchData['association_partner_id'])
                    && !empty($initialData['associationPartnerId'])
                ) {
                    $association = $this->fetchTable('Partners')->find()
                        ->find('byAssociationScope', isAssociation: true)
                        ->where([
                            'Partners.id' => (int)$initialData['associationPartnerId'],
                            'Partners.active' => true,
                        ])
                        ->first();

                    if ($association !== null) {
                        $patchData['association_partner_id'] = $association->id;
                        $patchData['association_snapshot'] = json_encode($association->declarationTexts());
                    }
                }

                $initial = $this->AdhesionInitialDatas->patchEntity($initial, $patchData);

                $this->AdhesionInitialDatas->save($initial);

                $initialDataId = $initial->id;
                $storageUuid = $initial->storage_uuid;
            }

            if (isset($data['personalData'])) {
                $personal = !$initialDataAll->adhesion_personal_data ? $this->AdhesionPersonalDatas->newEmptyEntity() : $this->AdhesionPersonalDatas->get($initialDataAll->adhesion_personal_data->id);
                $personal = $this->AdhesionPersonalDatas->patchEntity(
                    $personal,
                    ['adhesion_initial_data_id' => $initialDataId]
                        + AdhesionFormMap::toColumns('personalData', $data['personalData']),
                );

                if (!$this->AdhesionPersonalDatas->save($personal))
                    throw new \Exception('Falha ao salvar Dados Pessoais: ' . json_encode($personal->getErrors()));
            }

            if (isset($data['documents'])) {
                $documents = !$initialDataAll->adhesion_document ? $this->AdhesionDocuments->newEmptyEntity() : $this->AdhesionDocuments->get($initialDataAll->adhesion_document->id);
                $documents = $this->AdhesionDocuments->patchEntity(
                    $documents,
                    ['adhesion_initial_data_id' => $initialDataId]
                        + AdhesionFormMap::toColumns('documents', $data['documents']),
                );

                if (!$this->AdhesionDocuments->save($documents))
                    throw new \Exception('Falha ao salvar os documentos: ' . json_encode($documents->getErrors()));
            }

            if (isset($data['plans'])) {
                $planData = $data['plans'];

                $plans = !$initialDataAll->adhesion_plan ? $this->AdhesionPlans->newEmptyEntity() : $this->AdhesionPlans->get($initialDataAll->adhesion_plan->id);

                // has_survivors_pension e has_disability_retirement NÃO entram
                // aqui. Quais riscos a adesão tem é propriedade exclusiva do
                // admin: o formulário público não tem mais como remover risco,
                // e reescrever as flags a cada passo do Plano ressuscitaria,
                // em silêncio, o risco que o admin acabou de tirar -- o
                // payload simplesmente não traz mais as flags, e tudo voltaria
                // a true. Sem as chaves, o valor gravado permanece; numa
                // adesão nova, o default da coluna dá os dois riscos.
                $planColumns = AdhesionFormMap::toColumns('plans', $planData);
                $planPatch = [
                    'adhesion_initial_data_id' => $initialDataId,
                    'benefit_entry_age' => $planColumns['benefit_entry_age'],
                ];

                // Valor ajustado à mão pelo admin é valor negociado, e o
                // formulário não o desfaz. A tela já mostra o passo travado,
                // mas a tela é conveniência e o POST é forjável.
                if (!$plans->admin_overridden) {
                    $planPatch += $planColumns;
                }

                $plans = $this->AdhesionPlans->patchEntity($plans, $planPatch);

                if (!$this->AdhesionPlans->save($plans))
                    throw new \Exception('Falha ao salvar os planos: ' . json_encode($plans->getErrors()));
            }

            if (isset($data['dependents']) && is_array($data['dependents'])) {
                if ($initialDataAll->adhesion_dependents && count($initialDataAll->adhesion_dependents) > 0)
                    $this->AdhesionDependents->deleteAll(['adhesion_initial_data_id' => $initialDataId]);

                foreach ($data['dependents'] as $dep) {
                    $dependent = $this->AdhesionDependents->patchEntity(
                        $this->AdhesionDependents->newEmptyEntity(),
                        ['adhesion_initial_data_id' => $initialDataId]
                            + AdhesionFormMap::toColumns('dependents', $dep),
                    );

                    if (!$this->AdhesionDependents->save($dependent))
                        throw new \Exception('Falha ao salvar os dependentes: ' . json_encode($dependent->getErrors()));
                }
            }

            if (!empty($data['addresses'])) {
                $address = !$initialDataAll->adhesion_address ? $this->AdhesionAddresses->newEmptyEntity() : $this->AdhesionAddresses->get($initialDataAll->adhesion_address->id);
                $address = $this->AdhesionAddresses->patchEntity(
                    $address,
                    ['adhesion_initial_data_id' => $initialDataId]
                        + AdhesionFormMap::toColumns('addresses', $data['addresses']),
                );

                if (!$this->AdhesionAddresses->save($address))
                    throw new \Exception('Falha ao salvar os endereços: ' . json_encode($address->getErrors()));
            }

            if (!empty($data['otherInformations'])) {
                $otherInformations = !$initialDataAll->adhesion_other_information ? $this->AdhesionOtherInformations->newEmptyEntity() : $this->AdhesionOtherInformations->get($initialDataAll->adhesion_other_information->id);
                $otherInformations = $this->AdhesionOtherInformations->patchEntity(
                    $otherInformations,
                    ['adhesion_initial_data_id' => $initialDataId]
                        + AdhesionFormMap::toColumns('otherInformations', $data['otherInformations']),
                );

                if (!$this->AdhesionOtherInformations->save($otherInformations))
                    throw new \Exception('Falha ao salvar as outras informações: ' . json_encode($otherInformations->getErrors()));
            }

            if (!empty($data['proponentStatement'])) {
                $proponentStatements = !$initialDataAll->adhesion_proponent_statement ? $this->AdhesionProponentStatements->newEmptyEntity() : $this->AdhesionProponentStatements->get($initialDataAll->adhesion_proponent_statement->id);
                $proponentStatements = $this->AdhesionProponentStatements->patchEntity(
                    $proponentStatements,
                    ['adhesion_initial_data_id' => $initialDataId]
                        + AdhesionFormMap::toColumns('proponentStatement', $data['proponentStatement']),
                );

                if (!$this->AdhesionProponentStatements->save($proponentStatements))
                    throw new \Exception('Falha ao salvar as declarações do proponente: ' . json_encode($proponentStatements->getErrors()));
            }

            if (!empty($data['pensionScheme'])) {
                $pensionSchemesData = $data['pensionScheme'];
                $pensionSchemeTypes = (array)($pensionSchemesData['pensionSchemeType'] ?? []);

                $this->AdhesionPensionSchemes->deleteAll(['adhesion_initial_data_id' => $initialDataId]);

                foreach ($pensionSchemeTypes as $pensionSchemeType) {
                    $pensionScheme = $this->AdhesionPensionSchemes->newEmptyEntity();
                    $pensionScheme = $this->AdhesionPensionSchemes->patchEntity(
                        $pensionScheme,
                        [
                            'adhesion_initial_data_id' => $initialDataId,
                            'pension_scheme' => $pensionSchemeType,
                            'name' => $pensionSchemesData['name'] ?? null,
                            'cpf' => $pensionSchemesData['cpf'] ?? null,
                            'kinship' => $pensionSchemesData['kinship'] ?? null,
                        ],
                    );

                    if (!$this->AdhesionPensionSchemes->save($pensionScheme))
                        throw new \Exception('Falha ao salvar o regime de previdência: ' . json_encode($pensionScheme->getErrors()));
                }
            }

            // Zero linhas em dependents/pensionScheme é resposta legítima, e
            // por isso os dois passos precisam de um marcador próprio para
            // provar que foram de fato enviados (ver AdhesionSteps::EVIDENCE)
            // -- diferente dos blocos acima, que gravam por causa do que o
            // payload contém, isto grava por causa de qual passo o navegador
            // diz estar enviando.
            if ($initialDataId !== null && ($data['currentStepId'] ?? null) === 'dependents') {
                $this->markStepAnswered(intval($initialDataId), 'dependents_answered_at');
            }

            if ($initialDataId !== null && ($data['currentStepId'] ?? null) === 'pensionScheme') {
                $this->markStepAnswered(intval($initialDataId), 'pension_scheme_answered_at');
            }

            if (!empty($data['paymentDetail'])) {
                $paymentDetailsData = $data['paymentDetail'];

                if ($paymentDetailsData['payment_type'] === 'Débito em conta' && !isset($paymentDetailsData['bank_number']))
                    $paymentDetailsData['bank_number'] = '001';

                $totalContribution = Money::parse($paymentDetailsData['total_contribution'] ?? null);
                $paymentDetails = !$initialDataAll->adhesion_payment_detail ? $this->AdhesionPaymentDetails->newEmptyEntity() : $this->AdhesionPaymentDetails->get($initialDataAll->adhesion_payment_detail->id);
                $paymentDetails = $this->AdhesionPaymentDetails->patchEntity(
                    $paymentDetails,
                    [
                        'adhesion_initial_data_id' => $initialDataId,
                        'due_date' => $paymentDetailsData['due_date'] ?? '',
                        'total_contribution' => $totalContribution ?? null,
                        'payment_type' => $paymentDetailsData['payment_type'] ?? '',
                        'account_holder_name' => $paymentDetailsData['account_holder_name'] ?? null,
                        'account_holder_cpf' => $paymentDetailsData['account_holder_cpf'] ?? null,
                        'bank_number' => $paymentDetailsData['bank_number'] ?? null,
                        'bank_name' => isset($paymentDetailsData['bank_number']) ? $this->Bank->getName($paymentDetailsData['bank_number']) : null,
                        'branch_number' => $paymentDetailsData['branch_number'] ?? null,
                        'account_number' => $paymentDetailsData['account_number'] ?? null,
                    ],
                );

                if (!$this->AdhesionPaymentDetails->save($paymentDetails))
                    throw new \Exception('Falha ao salvar o dado de pagamento: ' . json_encode($paymentDetails->getErrors()));

                // Dados da adesão são insubstituíveis; commit agora para que nada abaixo
                // (Clicksign, Sicoob) possa apagá-los em caso de falha de terceiro.
                $connection->commit();
                $adhesionCommitted = true;

                IntegrationLogger::logEvent([
                    'adhesionId' => intval($initialDataId),
                    'operation' => 'adhesion.finalized',
                ]);

                $base64PdfForms = [
                    [
                        'file' => base64_encode($this->PdfGenerator->generatePdfApplicationForm($initialDataId, true)),
                        'name' => 'proposta_adesao.pdf',
                    ],
                    [
                        'file' => base64_encode($this->PdfGenerator->generateRegistrationFormPdf($initialDataId, true)),
                        'name' => 'formulario_inscricao.pdf',
                    ]
                ];

                IntegrationLogger::logEvent([
                    'adhesionId' => intval($initialDataId),
                    'operation' => 'application.pdfs_generated',
                    'context' => ['files' => array_column($base64PdfForms, 'name')],
                ]);

                $customerName = $initialDataAll->adhesion_personal_data->name ?? 'Cliente';

                try {
                    \App\Services\ClicksignEnvelopeSender::fromConfigure($this->ClicksignDatas)
                        ->send($initialDataAll, $base64PdfForms);
                } catch (\Exception $e) {
                    // A adesão já foi commitada acima: falha de terceiro não
                    // pode custar ao proponente os dez passos que ele preencheu.
                    // O admin é avisado e regera os documentos pela tela.
                    $this->notifyAdminsOfClicksignFailure($initialDataId, $customerName, $e->getMessage());
                }

                // A cobrança Pix não é mais criada aqui: nasce sob demanda quando o
                // cliente abre a página de pagamento (ver PaymentsController), o que
                // tira o Sicoob do caminho crítico desta requisição.
                $paymentUrl = Router::url([
                    'controller' => 'Payments',
                    'action' => 'view',
                    $initialDataAll->storage_uuid,
                ], true);

                if (!empty($initialDataAll->email))
                    $this->sendPaymentLinkEmail(intval($initialDataId), $initialDataAll->email, $customerName, $paymentUrl);

                return $this->response->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => true,
                        'message' => 'Adesão salva com sucesso!',
                        'initialDataId' => intval($initialDataId),
                        'storageUuid' => $storageUuid,
                        'redirectUrl' => $paymentUrl,
                    ]));
            }

            $connection->commit();

            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => 'Adesão salva com sucesso!',
                    'initialDataId' => intval($initialDataId),
                    'storageUuid' => $storageUuid,
                ]));
        } catch (\Exception $e) {
            if (!$adhesionCommitted)
                $connection->rollback();

            Log::error('Erro ao salvar adesão: ' . $e->getMessage());

            IntegrationLogger::logEvent([
                'adhesionId' => $adhesionCommitted ? intval($initialDataId) : null,
                'operation' => 'adhesion.save_failed',
                'success' => false,
                'errorMessage' => $e->getMessage(),
            ]);

            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => $adhesionCommitted
                        ? 'Sua adesão foi salva, mas houve um erro ao continuar o processamento: ' . $e->getMessage()
                        : $e->getMessage(),
                    'initialDataId' => $adhesionCommitted ? intval($initialDataId) : null,
                ]));
        }
    }

    /**
     * Grava o instante em que um passo sem evidência própria (dependents,
     * pensionScheme) foi enviado. Ver AdhesionSteps::EVIDENCE.
     */
    private function markStepAnswered(int $initialDataId, string $column): void
    {
        $adhesion = $this->AdhesionInitialDatas->get($initialDataId);
        $adhesion = $this->AdhesionInitialDatas->patchEntity($adhesion, [$column => DateTime::now()]);

        if (!$this->AdhesionInitialDatas->save($adhesion))
            throw new \Exception('Falha ao marcar a etapa como respondida: ' . json_encode($adhesion->getErrors()));
    }

    private function notifyAdminsOfClicksignFailure(int $initialDataId, string $customerName, string $errorMessage): void
    {
        try {
            $adminEmails = $this->fetchTable('Users')->find()->select(['email'])->extract('email')->toArray();

            if (empty($adminEmails))
                return;

            \App\Services\ResendService::fromConfigure()->forAdhesion($initialDataId)->send(
                $adminEmails,
                'Falha na assinatura eletrônica - Adesão #' . $initialDataId,
                \App\Services\EmailTemplates::clicksignFailureAlert($initialDataId, $customerName, $errorMessage)
            );
        } catch (\Exception $e) {
            Log::error('Falha ao notificar admins sobre erro no Clicksign: ' . $e->getMessage());
        }
    }

    private function sendPaymentLinkEmail(int $initialDataId, string $email, string $customerName, string $paymentUrl): void
    {
        try {
            \App\Services\ResendService::fromConfigure()->forAdhesion($initialDataId)->send(
                [$email],
                'Sua adesão está quase concluída - falta o pagamento',
                \App\Services\EmailTemplates::paymentLink($customerName, $paymentUrl)
            );
        } catch (\Exception $e) {
            Log::error('Falha ao enviar e-mail de pagamento para ' . $email . ': ' . $e->getMessage());
        }
    }

    /**
     * Consulta de CEP via ViaCEP
     */
    public function getCep()
    {
        $this->request->allowMethod(['get', 'ajax']);
        $cep = preg_replace('/\D/', '', $this->request->getQuery('cep') ?? '');

        if (strlen($cep) !== 8) {
            throw new BadRequestException('CEP inválido.');
        }

        $http = new Client();
        $response = $http->get("https://viacep.com.br/ws/{$cep}/json/");
        $data = $response->getJson();

        if (isset($data['erro'])) {
            throw new NotFoundException('CEP não encontrado.');
        }

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'data' => [
                    'cep' => $data['cep'] ?? '',
                    'address' => $data['logradouro'] ?? '',
                    'neighborhood' => $data['bairro'] ?? '',
                    'city' => $data['localidade'] ?? '',
                    'state' => $data['uf'] ?? '',
                ]
            ]));
    }
}
