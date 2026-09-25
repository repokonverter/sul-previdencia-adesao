<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\ClicksignData;
use App\Services\ClicksignService;
use Cake\I18n\DateTime;
use Cake\Log\Log;

class ClicksignDatasTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('clicksign_data');
        $this->setPrimaryKey('id');

        $this->belongsTo('AdhesionInitialDatas', [
            'foreignKey' => 'adhesion_initial_data_id',
        ]);
    }

    /**
     * A tentativa corrente de uma adesão — a mais recente. Mesmo padrão de
     * PixTransactions: as anteriores ficam como histórico, com os envelopes
     * que foram cancelados ou assinados.
     */
    public function latestFor(int $adhesionId): ?ClicksignData
    {
        /** @var \App\Model\Entity\ClicksignData|null */
        return $this->find()
            ->where(['ClicksignDatas.adhesion_initial_data_id' => $adhesionId])
            ->orderBy(['ClicksignDatas.attempt' => 'DESC'])
            ->first();
    }

    /**
     * Cancela o envelope de uma tentativa anterior, para que a nova nasça
     * limpa.
     *
     * Um envelope já fechado não pode ser cancelado, e não deve: ele é o
     * contrato que a pessoa de fato assinou, e fica como prova histórica,
     * superado pela tentativa nova em vez de apagado.
     *
     * Falhar aqui nunca impede a tentativa nova. O pior caso é um envelope
     * órfão na conta da Clicksign, que alguém resolve à mão; recusar a nova
     * finalização por causa disso deixaria o proponente sem documento algum.
     */
    public function cancelEnvelope(ClicksignService $clicksign, ClicksignData $attempt): void
    {
        if ($attempt->envelope_id === null || $attempt->canceled_at !== null) {
            return;
        }

        try {
            $clicksign->cancelEnvelope($attempt->envelope_id);

            $this->save($this->patchEntity($attempt, [
                'canceled_at' => DateTime::now(),
                'status' => 'canceled',
            ]));
        } catch (\Exception $e) {
            Log::warning(
                'Não foi possível cancelar o envelope ' . $attempt->envelope_id
                . ' da adesão #' . $attempt->adhesion_initial_data_id . ': ' . $e->getMessage()
            );
        }
    }
}
