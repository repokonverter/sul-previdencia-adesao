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
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Log\Log;
use Cake\Http\Client;
use Cake\Core\Configure;

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
        // $this->loadComponent('RequestHandler');
        $this->viewBuilder()->setClassName('Ajax');
        $this->autoRender = false;
    }

    public function save()
    {
        $this->request->allowMethod(['get', 'ajax']);

        $data = $this->request->getData();
        $connection = $this->AdhesionInitialDatas->getConnection();
        $connection->begin();

        $clicksignService = new \App\Services\ClicksignService(
            Configure::read('Clicksign.baseUrl'),
            Configure::read('Clicksign.accessToken')
        );

        dd($clicksignService->getEnvelopes());


        try {
            $initialDataId = isset($data['initialDataId']) ? $data['initialDataId'] : null;

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
                    ]
                );
            }

            if (isset($data['initialData'])) {
                $initialData = $data['initialData'];
                $initial = $initialDataId === null ? $this->AdhesionInitialDatas->newEmptyEntity() : $this->AdhesionInitialDatas->get($initialDataId);
                $initial = $this->AdhesionInitialDatas->patchEntity(
                    $initial,
                    [
                        'storage_uuid' => $data['storageUuid'],
                        'name' => $initialData['name'] ?? '',
                        'email' => $initialData['email'] ?? null,
                        'phone' => $initialData['phone'] ?? null,
                    ],
                );

                $this->AdhesionInitialDatas->save($initial);

                $initialDataId = $initial->id;
            }

            if (isset($data['personalData'])) {
                $personalData = $data['personalData'];
                $personal = !$initialDataAll->adhesion_personal_data ? $this->AdhesionPersonalDatas->newEmptyEntity() : $this->AdhesionPersonalDatas->get($initialDataAll->adhesion_personal_data->id);
                $personal = $this->AdhesionPersonalDatas->patchEntity(
                    $personal,
                    [
                        'adhesion_initial_data_id' => $initialDataId,
                        'plan_for' => $personalData['planFor'] ?? '',
                        'name' => $personalData['name'] ?? '',
                        'cpf' => $personalData['cpf'] ?? '',
                        'birth_date' => $personalData['birthDate'] ?? null,
                        'nacionality' => $personalData['nacionality'] ?? '',
                        'gender' => $personalData['gender'] ?? null,
                        'marital_status' => $personalData['maritalStatus'] ?? null,
                        'number_children' => $personalData['numberChildren'] ?? null,
                        'mother_name' => $personalData['motherName'] ?? null,
                        'father_name' => $personalData['fatherName'] ?? null,
                        'name_legal_representative' => $personalData['nameLegalRepresentative'] ?? '',
                        'cpf_legal_representative' => $personalData['cpfLegalRepresentative'] ?? '',
                        'affiliation_legal_representative' => $personalData['affiliationLegalRepresentative'] ?? '',
                    ],
                );

                if (!$this->AdhesionPersonalDatas->save($personal))
                    throw new \Exception('Falha ao salvar Dados Pessoais: ' . json_encode($personal->getErrors()));
            }

            if (isset($data['documents'])) {
                $documentsData = $data['documents'];
                $documents = !$initialDataAll->adhesion_documents ? $this->AdhesionDocuments->newEmptyEntity() : $this->AdhesionDocuments->get($initialDataAll->adhesion_documents->id);
                $documents = $this->AdhesionDocuments->patchEntity(
                    $documents,
                    [
                        'adhesion_initial_data_id' => $initialDataId,
                        'type' => $documentsData['documentType'],
                        'type_other' => $documentsData['type_other'],
                        'document_number' => $documentsData['documentNumber'],
                        'issue_date' => $documentsData['issueDate'] ?? null,
                        'issuer' => $documentsData['issuer'] ?? null,
                        'place_birth' => $documentsData['placeBirth'] ?? null,
                    ],
                );

                if (!$this->AdhesionDocuments->save($documents))
                    throw new \Exception('Falha ao salvar os documentos: ' . json_encode($documents->getErrors()));
            }

            if (isset($data['plans'])) {
                $planData = $data['plans'];
                $plans = !$initialDataAll->adhesion_plans ? $this->AdhesionPlans->newEmptyEntity() : $this->AdhesionPlans->get($initialDataAll->adhesion_plans->id);
                $plans = $this->AdhesionPlans->patchEntity(
                    $plans,
                    [
                        'adhesion_initial_data_id' => $initialDataId,
                        'benefit_entry_age' => $planData['benefitEntryAge'] ?? null,
                        'monthly_retirement_contribution' => str_replace(',', '.', str_replace('.', '', $planData['monthly_retirement_contribution'])) ?? null,
                        'monthly_survivors_pension_contribution' => str_replace(',', '.', str_replace('.', '', $planData['monthly_survivors_pension_contribution'])) ?? null,
                        'survivors_pension_insured_capital' => str_replace(',', '.', str_replace('.', '', $planData['survivors_pension_insured_capital'])) ?? null,
                        'monthly_disability_retirement_contribution' => str_replace(',', '.', str_replace('.', '', $planData['monthly_disability_retirement_contribution'])) ?? null,
                        'disability_retirement_insured_capital' => str_replace(',', '.', str_replace('.', '', $planData['disability_retirement_insured_capital'])) ?? null,
                    ],
                );

                if (!$this->AdhesionPlans->save($plans))
                    throw new \Exception('Falha ao salvar os planos: ' . json_encode($plans->getErrors()));
            }

            if (isset($data['dependents']) && is_array($data['dependents'])) {
                if ($initialDataAll->adhesion_dependents && count($initialDataAll->adhesion_dependents) > 0)
                    $this->AdhesionDependents->deleteAll(['adhesion_initial_data_id' => $initialDataId]);

                foreach ($data['dependents'] as $dep) {
                    $dependent = $this->AdhesionDependents->newEmptyEntity();
                    $dependent = $this->AdhesionDependents->patchEntity(
                        $dependent,
                        [
                            'adhesion_initial_data_id' => $initialDataId,
                            'name' => $dep['name'] ?? '',
                            'cpf' => $dep['cpf'] ?? null,
                            'birth_date' => $dep['birth_date'] ?? null,
                            'kinship' => $dep['kinship'] ?? null,
                            'participation' => str_replace(',', '.', $dep['participation']) ?? null,
                        ],
                    );

                    if (!$this->AdhesionDependents->save($dependent))
                        throw new \Exception('Falha ao salvar os dependentes: ' . json_encode($dependent->getErrors()));
                }
            }

            if (!empty($data['addresses'])) {
                $addrData = $data['addresses'];
                $address = !$initialDataAll->adhesion_address ? $this->AdhesionAddresses->newEmptyEntity() : $this->AdhesionAddresses->get($initialDataAll->adhesion_address->id);
                $address = $this->AdhesionAddresses->patchEntity(
                    $address,
                    [
                        'adhesion_initial_data_id' => $initialDataId,
                        'cep' => str_replace(['.', '-'], '', $addrData['cep']) ?? '',
                        'address' => $addrData['address'] ?? '',
                        'number' => $addrData['number'] ?? '',
                        'complement' => $addrData['complement'] ?? '',
                        'neighborhood' => $addrData['neighborhood'] ?? '',
                        'city' => $addrData['city'] ?? '',
                        'state' => $addrData['state'] ?? '',
                    ],
                );

                if (!$this->AdhesionAddresses->save($address))
                    throw new \Exception('Falha ao salvar os endereços: ' . json_encode($address->getErrors()));
            }

            if (!empty($data['otherInformations'])) {
                $otherInformationsData = $data['otherInformations'];
                $otherInformations = !$initialDataAll->adhesion_other_information ? $this->AdhesionOtherInformations->newEmptyEntity() : $this->AdhesionOtherInformations->get($initialDataAll->adhesion_other_information->id);
                $otherInformations = $this->AdhesionOtherInformations->patchEntity(
                    $otherInformations,
                    [
                        'adhesion_initial_data_id' => $initialDataId,
                        'main_occupation' => $otherInformationsData['mainOccupation'] ?? '',
                        'category' => $otherInformationsData['category'] ?? '',
                        'brazilian_resident' => $otherInformationsData['brazilianResident'] ?? false,
                        'politically_exposed' => $otherInformationsData['politicallyExposed'] ?? false,
                        'obligation_other_countries' => $otherInformationsData['obligationOtherCountries'] ?? false,
                    ],
                );

                if (!$this->AdhesionOtherInformations->save($otherInformations))
                    throw new \Exception('Falha ao salvar as outras informações: ' . json_encode($otherInformations->getErrors()));
            }

            if (!empty($data['proponentStatement'])) {
                $proponentStatementsData = $data['proponentStatement'];
                $proponentStatements = !$initialDataAll->adhesion_proponent_statement ? $this->AdhesionProponentStatements->newEmptyEntity() : $this->AdhesionProponentStatements->get($initialDataAll->adhesion_proponent_statement->id);
                $proponentStatements = $this->AdhesionProponentStatements->patchEntity(
                    $proponentStatements,
                    [
                        'adhesion_initial_data_id' => $initialDataId,
                        'health_problem' => $proponentStatementsData['healthProblem'] ?? false,
                        'health_problem_obs' => $proponentStatementsData['healthProblemObs'] ?? '',
                        'heart_disease' => $proponentStatementsData['heartDisease'] ?? false,
                        'heart_disease_obs' => $proponentStatementsData['heartDiseaseObs'] ?? '',
                        'suffered_organ_defects' => $proponentStatementsData['sufferedOrganDefects'] ?? false,
                        'suffered_organ_defects_obs' => $proponentStatementsData['sufferedOrganDefectsObs'] ?? '',
                        'surgery' => $proponentStatementsData['surgery'] ?? false,
                        'surgery_obs' => $proponentStatementsData['surgeryObs'] ?? '',
                        'away' => $proponentStatementsData['away'] ?? false,
                        'away_obs' => $proponentStatementsData['awayObs'] ?? '',
                        'practices_parachuting' => $proponentStatementsData['practicesParachuting'] ?? false,
                        'practices_parachuting_obs' => $proponentStatementsData['practicesParachutingObs'] ?? '',
                        'smoker' => $proponentStatementsData['smoker'] ?? false,
                        'smoker_type' => $proponentStatementsData['smokerType'] ?? false,
                        'smoker_type_obs' => $proponentStatementsData['smokerTypeObs'] ?? '',
                        'smoker_qty' => $proponentStatementsData['smokerQty'] ?? '',
                        'weight' => $proponentStatementsData['weight'] ?? null,
                        'height' => $proponentStatementsData['height'] ?? null,
                        'gripe' => $proponentStatementsData['gripe'] ?? false,
                        'gripe_obs' => $proponentStatementsData['gripeObs'] ?? '',
                        'covid' => $proponentStatementsData['covid'] ?? false,
                        'covid_obs' => $proponentStatementsData['covidObs'] ?? '',
                        'covid_sequelae' => $proponentStatementsData['covidSequelae'] ?? false,
                        'covid_sequelae_obs' => $proponentStatementsData['covidSequelaeObs'] ?? '',
                    ],
                );

                if (!$this->AdhesionProponentStatements->save($proponentStatements))
                    throw new \Exception('Falha ao salvar as declarações do proponente: ' . json_encode($proponentStatements->getErrors()));
            }

            if (!empty($data['pensionScheme'])) {
                $pensionSchemesData = $data['pensionScheme'];
                $pensionSchemes = !$initialDataAll->adhesion_pension_scheme ? $this->AdhesionPensionSchemes->newEmptyEntity() : $this->AdhesionPensionSchemes->get($initialDataAll->adhesion_pension_scheme->id);
                $pensionSchemes = $this->AdhesionPensionSchemes->patchEntity(
                    $pensionSchemes,
                    [
                        'adhesion_initial_data_id' => $initialDataId,
                        'due_date' => $pensionSchemesData['pensionSchemeType'] ?? '',
                        'name' => $pensionSchemesData['name'] ?? '',
                        'cpf' => $pensionSchemesData['cpf'] ?? false,
                        'kinship' => $pensionSchemesData['kinship'] ?? false,
                    ],
                );

                if (!$this->AdhesionPensionSchemes->save($pensionSchemes))
                    throw new \Exception('Falha ao salvar o regime de previdência: ' . json_encode($pensionSchemes->getErrors()));
            }

            if (!empty($data['paymentDetail'])) {
                $paymentDetailsData = $data['paymentDetail'];
                $paymentDetails = !$initialDataAll->adhesion_payment_detail ? $this->AdhesionPaymentDetails->newEmptyEntity() : $this->AdhesionPaymentDetails->get($initialDataAll->adhesion_payment_detail->id);
                $paymentDetails = $this->AdhesionPaymentDetails->patchEntity(
                    $paymentDetails,
                    [
                        'adhesion_initial_data_id' => $initialDataId,
                        'due_date' => $paymentDetailsData['due_date'] ?? '',
                        'total_contribution' => $paymentDetailsData['total_contribution'] ?? null,
                        'payment_type' => $paymentDetailsData['payment_type'] ?? '',
                        'account_holder_name' => $paymentDetailsData['account_holder_name'] ?? '',
                        'account_holder_cpf' => $paymentDetailsData['account_holder_cpf'] ?? '',
                        'bank_number' => $paymentDetailsData['bank_number'] ?? '',
                        'bank_name' => $paymentDetailsData['bank_number'] ? $this->Bank->getName($paymentDetailsData['bank_number']) : '',
                        'branch_number' => $paymentDetailsData['branch_number'] ?? '',
                        'account_number' => $paymentDetailsData['account_number'] ?? '',
                    ],
                );

                if (!$this->AdhesionPaymentDetails->save($paymentDetails))
                    throw new \Exception('Falha ao salvar o dado de pagamento: ' . json_encode($paymentDetails->getErrors()));
            }

            $connection->commit();

            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => 'Adesão salva com sucesso!',
                    'initialDataId' => intval($initialDataId),
                ]));
        } catch (\Exception $e) {
            $connection->rollback();
            Log::error('Erro ao salvar adesão: ' . $e->getMessage());

            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
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
