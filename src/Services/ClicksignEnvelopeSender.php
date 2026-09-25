<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Entity\ClicksignData;
use App\Model\Table\ClicksignDatasTable;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Log\Log;
use Exception;

/**
 * Cria o envelope de assinatura de uma adesão e o manda para o proponente.
 *
 * Vive fora do controller porque tem dois chamadores: a finalização do
 * formulário e o botão do admin que regera os documentos depois de uma
 * alteração. Enquanto estava dentro de save(), o segundo não tinha como
 * existir sem duplicar cento e poucas linhas de integração.
 */
class ClicksignEnvelopeSender
{
    public function __construct(
        private readonly ClicksignDatasTable $clicksignDatas,
        private readonly ClicksignService $clicksign,
    ) {
    }

    public static function fromConfigure(ClicksignDatasTable $clicksignDatas): self
    {
        return new self(
            $clicksignDatas,
            new ClicksignService(
                Configure::read('Clicksign.baseUrl'),
                Configure::read('Clicksign.accessToken')
            )
        );
    }

    /**
     * @param array<int, array{file: string, name: string}> $documents PDFs em base64
     * @throws \Exception quando a Clicksign recusa qualquer passo
     */
    public function send(EntityInterface $adhesion, array $documents): ClicksignData
    {
        $adhesionId = (int)$adhesion->id;
        $customerName = $adhesion->adhesion_personal_data->name ?? 'Cliente';

        $this->clicksign->forAdhesion($adhesionId);

        $clicksignData = null;

        try {
            // Cada finalização é uma tentativa, com o seu próprio
            // envelope. A Clicksign só apaga documento de envelope em
            // `draft`, então trocar os PDFs de um envelope que já saiu
            // para assinatura é impossível -- e o código anterior
            // tentava, apagando apenas o primeiro dos dois e subindo
            // mais dois por cima, deixando três documentos no pacote
            // que o proponente assinaria.
            $previous = $this->clicksignDatas->latestFor((int)$adhesionId);

            if ($previous !== null) {
                $this->clicksignDatas->cancelEnvelope($this->clicksign, $previous);
            }

            $envelopeResponse = $this->clicksign->createEnvelope(
                'Envelope de Adesão - ' . $customerName
            );
            $envelopeId = $envelopeResponse['data']['id'];

            $clicksignData = $this->clicksignDatas->newEntity([
                'adhesion_initial_data_id' => $adhesionId,
                'envelope_id' => $envelopeId,
                'attempt' => ($previous->attempt ?? 0) + 1,
            ]);

            if (!$this->clicksignDatas->save($clicksignData))
                throw new \Exception('Falha ao salvar no clicksign: ' . json_encode($clicksignData->getErrors()));

            if ($envelopeId) {
                $documentIds = [];

                foreach ($documents as $document) {
                    $documentResponse = $this->clicksign->createDocument($envelopeId, [
                        'filename' => $document['name'],
                        'content_base64' => "data:application/pdf;base64," . $document['file'],
                    ]);

                    if (!$documentResponse['success'])
                        throw new \Exception('Falha ao criar o documento no clicksign: ' . json_encode($documentResponse['data']));

                    $documentIds[] = $documentResponse['data']['id'];
                }

                $clicksignSignerResponse = $this->clicksign->createSigner($envelopeId, [
                    'name' => $customerName,
                    'email' => $adhesion->email,
                    'documentation' => $adhesion->adhesion_personal_data->cpf,
                    'birthday' => $adhesion->adhesion_personal_data->birth_date,
                    'group' => 1,
                    'communicate_events' => [
                        'signature_request' => 'email',
                        'signature_reminder' => 'email',
                        'document_signed' => 'email'
                    ]
                ]);

                if (!$clicksignSignerResponse['success'])
                    throw new \Exception('Falha ao criar o assinante no clicksign: ' . json_encode($clicksignSignerResponse['data']));

                foreach ($documentIds as $documentId) {
                    $clicksignRequirementResponse = $this->clicksign->createRequirement($envelopeId, [
                        'action' => 'agree',
                        'role' => 'contractor'
                    ], [
                        "document" => [
                            "data" => [
                                "type" => "documents",
                                'id' => $documentId
                            ]
                        ],
                        "signer" => [
                            "data" => [
                                "type" => "signers",
                                'id' => $clicksignSignerResponse['data']['id']
                            ]
                        ]
                    ]);

                    if (!$clicksignRequirementResponse['success'])
                        throw new \Exception('Falha ao criar a exigência no clicksign: ' . json_encode($clicksignRequirementResponse['data']));

                    $clicksignRequirementResponse = $this->clicksign->createRequirement($envelopeId, [
                        'action' => 'provide_evidence',
                        'auth' => 'email'
                    ], [
                        "document" => [
                            "data" => [
                                "type" => "documents",
                                'id' => $documentId
                            ]
                        ],
                        "signer" => [
                            "data" => [
                                "type" => "signers",
                                'id' => $clicksignSignerResponse['data']['id']
                            ]
                        ]
                    ]);

                    if (!$clicksignRequirementResponse['success'])
                        throw new \Exception('Falha ao criar a exigência no clicksign: ' . json_encode($clicksignRequirementResponse['data']));
                }

                $clicksignEnvelopeResponse = $this->clicksign->updateEnvelope($envelopeId, [
                    'status' => 'running'
                ]);

                if (!$clicksignEnvelopeResponse['success'])
                    throw new \Exception('Falha ao atualizar o envelope no clicksign: ' . json_encode($clicksignEnvelopeResponse['data']));

                $clicksignNotificationResponse = $this->clicksign->notifyEnvelopeSigners($envelopeId, ['message' => null]);

                if (!$clicksignNotificationResponse['success'])
                    throw new \Exception('Falha ao notificar o envelope no clicksign: ' . json_encode($clicksignNotificationResponse['data']));
            }

            $clicksignData = $this->clicksignDatas->patchEntity($clicksignData, [
                'status' => 'sent',
                'attempts' => ($clicksignData->attempts ?? 0) + 1,
                'last_error' => null,
            ]);
            $this->clicksignDatas->save($clicksignData);

            return $clicksignData;
        } catch (Exception $e) {
            Log::error('Erro integração Clicksign: ' . $e->getMessage());

            // A tentativa fica gravada como falha mesmo assim: sem isso, o
            // admin não teria como saber que houve uma, nem por quê.
            if ($clicksignData !== null) {
                $clicksignData = $this->clicksignDatas->patchEntity($clicksignData, [
                    'status' => 'failed',
                    'attempts' => ($clicksignData->attempts ?? 0) + 1,
                    'last_error' => $e->getMessage(),
                ]);
                $this->clicksignDatas->save($clicksignData);
            }

            throw $e;
        }
    }
}
