<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\Date;
use Cake\ORM\Entity;

/**
 * PromotionalCode Entity
 *
 * @property int $id
 * @property int $partner_id
 * @property string $code
 * @property string|null $description
 * @property \Cake\I18n\Date|null $valid_from
 * @property \Cake\I18n\Date|null $valid_until
 * @property bool $active
 * @property \App\Model\Entity\Partner $partner
 */
class PromotionalCode extends Entity
{
    /**
     * A janela de validade é sempre avaliada no horário de Brasília,
     * independentemente do timezone do servidor.
     *
     * Não usar o timezone padrão da aplicação é intencional: ele vem de
     * APP_DEFAULT_TIMEZONE e hoje difere entre ambientes (UTC em
     * desenvolvimento, America/Sao_Paulo em produção). Depender dele faria um
     * código "válido até 31/12" expirar às 21h do dia 31 no Brasil.
     */
    public const VALIDITY_TIMEZONE = 'America/Sao_Paulo';

    protected array $_accessible = [
        'partner_id' => true,
        'code' => true,
        'description' => true,
        'valid_from' => true,
        'valid_until' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'partner' => true,
    ];

    /**
     * Normaliza o código sempre que ele é atribuído, para que a comparação e o
     * índice único trabalhem sobre um valor canônico.
     */
    protected function _setCode(?string $code): ?string
    {
        return static::normalizeCode($code);
    }

    /**
     * Remove acentos, espaços e símbolos, e converte para maiúsculas.
     */
    public static function normalizeCode(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT', $code);

        if ($transliterated !== false) {
            $code = $transliterated;
        }

        $code = strtoupper($code);

        return preg_replace('/[^A-Z0-9-]/', '', $code);
    }

    /**
     * A data de hoje no horário de Brasília.
     */
    public static function today(): Date
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone(self::VALIDITY_TIMEZONE));

        return new Date($now->format('Y-m-d'));
    }

    /**
     * Um código só é utilizável se ele e o parceiro estiverem ativos e a data
     * atual estiver dentro da janela de validade do código. A comparação usa
     * a data local (America/Sao_Paulo), não UTC, para que "válido até 31/12"
     * continue valendo às 21h do dia 31 no Brasil.
     */
    public function isUsable(?Date $today = null): bool
    {
        return $this->unusableReason($today) === null;
    }

    /**
     * Motivo pelo qual o código não pode ser usado, ou null se ele estiver válido.
     */
    public function unusableReason(?Date $today = null): ?string
    {
        if (!$this->active) {
            return 'inactive';
        }

        if ($this->partner !== null && !$this->partner->active) {
            return 'inactive';
        }

        $today = $today ?? static::today();

        if ($this->valid_from !== null && $today->lessThan($this->valid_from)) {
            return 'not_started';
        }

        if ($this->valid_until !== null && $today->greaterThan($this->valid_until)) {
            return 'expired';
        }

        return null;
    }
}
