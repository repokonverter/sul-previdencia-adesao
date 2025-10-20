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
    public function initialize(): void
    {
        parent::initialize();

        $this->loadModel('Subscriptions');
        $this->loadModel('People');
        $this->loadModel('Addresses');
        $this->loadModel('Dependents');
        $this->loadModel('Documents');

        $this->loadComponent('RequestHandler');
        $this->viewBuilder()->setClassName('Ajax');
        $this->autoRender = false;
    }

    /**
     * Salva a adesão completa (Subscription + People + Address + Dependents + Documents)
     */
    public function save()
    {
        $this->request->allowMethod(['post', 'ajax']);

        $data = $this->request->getData();

        $connection = $this->Subscriptions->getConnection();
        $connection->begin();

        try {
            // --- Subscription ---
            $subscription = $this->Subscriptions->newEntity([
                'plan_type'       => $data['plan_type'] ?? null,
                'plan_value'      => $data['plan_value'] ?? null,
                'periodicity'     => $data['periodicity'] ?? null,
                'payment_method'  => $data['payment_method'] ?? null,
            ]);

            if (!$this->Subscriptions->save($subscription)) {
                throw new BadRequestException('Erro ao salvar inscrição.');
            }

            $subscriptionId = $subscription->id;

            // --- Pessoa principal ---
            if (empty($data['person'])) {
                throw new BadRequestException('Dados da pessoa não informados.');
            }

            $personData = $data['person'];
            $person = $this->People->newEntity([
                'subscription_id'        => $subscriptionId,
                'name'                   => $personData['name'] ?? '',
                'cpf'                    => $personData['cpf'] ?? '',
                'birth_date'             => $personData['birth_date'] ?? null,
                'marital_status'         => $personData['marital_status'] ?? null,
                'gender'                 => $personData['gender'] ?? null,
                'email'                  => $personData['email'] ?? null,
                'phone'                  => $personData['phone'] ?? null,
                'is_legal_representative'=> $personData['is_legal_representative'] ?? false,
            ]);

            if (!$this->People->save($person)) {
                throw new BadRequestException('Erro ao salvar os dados da pessoa.');
            }

            // --- Endereço ---
            if (!empty($data['address'])) {
                $addrData = $data['address'];
                $address = $this->Addresses->newEntity([
                    'subscription_id' => $subscriptionId,
                    'cep'             => $addrData['cep'] ?? '',
                    'address'         => $addrData['address'] ?? '',
                    'number'          => $addrData['number'] ?? '',
                    'complement'      => $addrData['complement'] ?? '',
                    'neighborhood'    => $addrData['neighborhood'] ?? '',
                    'city'            => $addrData['city'] ?? '',
                    'state'           => $addrData['state'] ?? '',
                ]);
                $this->Addresses->save($address);
            }

            // --- Dependentes ---
            if (!empty($data['dependents']) && is_array($data['dependents'])) {
                foreach ($data['dependents'] as $dep) {
                    if (empty($dep['name'])) continue;
                    $dependent = $this->Dependents->newEntity([
                        'subscription_id' => $subscriptionId,
                        'name'            => $dep['name'] ?? '',
                        'cpf'             => $dep['cpf'] ?? null,
                        'birth_date'      => $dep['birth_date'] ?? null,
                        'kinship'         => $dep['kinship'] ?? null,
                    ]);
                    $this->Dependents->save($dependent);
                }
            }

            // --- Documentos (uploads) ---
            if (!empty($data['documents']) && is_array($data['documents'])) {
                foreach ($data['documents'] as $doc) {
                    if (empty($doc['type']) || empty($doc['file_path'])) continue;

                    $document = $this->Documents->newEntity([
                        'subscription_id' => $subscriptionId,
                        'type'            => $doc['type'],
                        'file_path'       => $doc['file_path'],
                        'issue_date'      => $doc['issue_date'] ?? null,
                        'issuer'          => $doc['issuer'] ?? null,
                    ]);

                    $this->Documents->save($document);
                }
            }

            $connection->commit();

            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => 'Adesão salva com sucesso!',
                    'subscription_id' => $subscriptionId,
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
