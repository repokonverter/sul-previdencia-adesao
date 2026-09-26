<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ClicksignData Entity
 *
 * Uma linha por tentativa de assinatura -- ver clicksign_data_id_attempt e
 * ClicksignDatasTable::latestFor(). `status` guarda o que este servidor
 * confirmou por GET: nunca o que um payload de webhook alega, que é só
 * gatilho para reconferir (ver WebhooksController::clicksign()).
 *
 * @property int $id
 * @property int $adhesion_initial_data_id
 * @property string $envelope_id
 * @property string $status
 * @property int $attempts
 * @property string|null $last_error
 * @property int $attempt
 * @property \Cake\I18n\DateTime|null $canceled_at
 * @property \Cake\I18n\DateTime|null $signed_at
 * @property string|null $documents
 * @property \Cake\I18n\DateTime|null $reopened_at
 * @property int|null $reopened_by_user_id
 */
class ClicksignData extends Entity
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_FAILED = 'failed';

    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'envelope_id' => true,
        'status' => true,
        'attempts' => true,
        'last_error' => true,
        'attempt' => true,
        'canceled_at' => true,
        'signed_at' => true,
        'documents' => true,
        'reopened_at' => true,
        'reopened_by_user_id' => true,
        'created' => true,
        'updated' => true,
    ];

    public function isSigned(): bool
    {
        return $this->status === self::STATUS_SIGNED;
    }

    /**
     * Assinada, mas o admin decidiu destravar a edição mesmo assim. Não
     * apaga o fato de ter sido assinada -- só tira o cadeado econômico.
     */
    public function isReopened(): bool
    {
        return $this->reopened_at !== null;
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function documentList(): array
    {
        if (empty($this->documents)) {
            return [];
        }

        return json_decode($this->documents, true) ?: [];
    }
}
