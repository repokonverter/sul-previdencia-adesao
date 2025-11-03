<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Log\Log;
use Cake\Http\Client;

class RegistrationsController extends AppController
{
    protected \App\Model\Table\AdhesionAddressesTable $AdhesionAddresses;
    protected \App\Model\Table\AdhesionDependentsTable $AdhesionDependents;
    protected \App\Model\Table\AdhesionDocumentsTable $AdhesionDocuments;
    protected \App\Model\Table\AdhesionInitialDatasTable $AdhesionInitialDatas;
    protected \App\Model\Table\AdhesionOtherInformationsTable $AdhesionOtherInformations;
    protected \App\Model\Table\AdhesionPersonalDatasTable $AdhesionPersonalDatas;
    protected \App\Model\Table\AdhesionPlansTable $AdhesionPlans;

    public function initialize(): void
    {
        parent::initialize();

        $this->AdhesionAddresses = $this->fetchTable('AdhesionAddresses');
        $this->AdhesionDependents = $this->fetchTable('AdhesionDependents');
        $this->AdhesionDocuments = $this->fetchTable('AdhesionDocuments');
        $this->AdhesionInitialDatas = $this->fetchTable('AdhesionInitialDatas');
        $this->AdhesionOtherInformations = $this->fetchTable('AdhesionOtherInformations');
        $this->AdhesionPersonalDatas = $this->fetchTable('AdhesionPersonalDatas');
        $this->AdhesionPlans = $this->fetchTable('AdhesionPlans');

        // $this->loadComponent('RequestHandler');
        $this->viewBuilder()->setClassName('Ajax');
        $this->autoRender = false;
    }

    /**
     * Salva a adesão completa (Subscription + People + Address + Dependents + Documents)
     */
    public function save()
    {
        $this->request->allowMethod(['get', 'ajax']);

        $data = $this->request->getData();
        $connection = $this->AdhesionInitialDatas->getConnection();
        $connection->begin();

        try {
            $initialDataId = isset($data['initialDataId']) ? $data['initialDataId'] : null;

            if ($initialDataId !== null) {
                $initialDataAll = $this->AdhesionInitialDatas->get($initialDataId, [
                    'contain' => [
                        'AdhesionPersonalDatas',
                        'AdhesionDocuments',
                        'AdhesionPlans',
                        'AdhesionDependents',
                        'AdhesionAddresses',
                        'AdhesionOtherInformations',
                    ]
                ]);
            }

            if (isset($data['initialData'])) {
                $initialData = $data['initialData'];
                $initial = $initialDataId === null ? $this->AdhesionInitialDatas->newEmptyEntity() : $this->AdhesionInitialDatas->get($initialDataId, []);
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
                $personal = !$initialDataAll->adhesion_personal_data ? $this->AdhesionPersonalDatas->newEmptyEntity() : $this->AdhesionPersonalDatas->get($initialDataAll->adhesion_personal_data->id, []);
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
                $documents = !$initialDataAll->adhesion_documents ? $this->AdhesionDocuments->newEmptyEntity() : $this->AdhesionDocuments->get($initialDataAll->adhesion_documents->id, []);
                $documents = $this->AdhesionDocuments->patchEntity(
                    $documents,
                    [
                        'adhesion_initial_data_id' => $initialDataId,
                        'type' => $documentsData['documentType'],
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
                $plans = !$initialDataAll->adhesion_plans ? $this->AdhesionPlans->newEmptyEntity() : $this->AdhesionPlans->get($initialDataAll->adhesion_plans->id, []);
                $plans = $this->AdhesionPlans->patchEntity(
                    $plans,
                    [
                        'adhesion_initial_data_id' => $initialDataId,
                        'benefit_entry_age' => $planData['benefitEntryAge'] ?? null,
                        'monthly_contribuition_amount' => str_replace(',', '.', str_replace('.', '', $planData['monthlyContribuitionAmount'])) ?? null,
                        'value_founding_contribution' => str_replace(',', '.', str_replace('.', '', $planData['valueFoundingContribution'])) ?? null,
                        'insured_capital' => str_replace(',', '.', str_replace('.', '', $planData['insuredCapital'])) ?? null,
                        'contribution' => str_replace(',', '.', str_replace('.', '', $planData['contribution'])) ?? null,
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
                $address = !$initialDataAll->adhesion_address ? $this->AdhesionAddresses->newEmptyEntity() : $this->AdhesionAddresses->get($initialDataAll->adhesion_address->id, []);
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
                $otherInformations = !$initialDataAll->adhesion_other_information ? $this->AdhesionOtherInformations->newEmptyEntity() : $this->AdhesionOtherInformations->get($initialDataAll->adhesion_other_information->id, []);
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

    /**
     * Lista de planos disponíveis (mock ou tabela)
     */
    public function plans()
    {
        $this->request->allowMethod(['get', 'ajax']);

        // Pode vir de uma tabela "plans" futuramente
        $plans = [
            ['id' => 1, 'name' => 'Plano Essencial', 'value' => 150.00, 'periodicity' => 'mensal'],
            ['id' => 2, 'name' => 'Plano Premium', 'value' => 300.00, 'periodicity' => 'mensal'],
            ['id' => 3, 'name' => 'Plano Master', 'value' => 500.00, 'periodicity' => 'mensal'],
        ];

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'plans' => $plans
            ]));
    }
}
