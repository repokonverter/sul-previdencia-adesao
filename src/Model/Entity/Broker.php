<?php

declare(strict_types=1);

namespace App\Model\Entity;

use App\Utility\Cpf;
use Cake\ORM\Entity;

/**
 * Broker Entity: o Corretor de um Parceiro (ver GLOSSARY.md).
 *
 * @property int $id
 * @property int $partner_id
 * @property string $name
 * @property string|null $cpf
 * @property string $token
 * @property bool $active
 * @property \App\Model\Entity\Partner $partner
 */
class Broker extends Entity
{
    /**
     * token fica de fora: nasce no servidor (BrokersTable::beforeSave()) e
     * nunca muda, porque é ele que está nos links já divulgados.
     */
    protected array $_accessible = [
        'partner_id' => true,
        'name' => true,
        'cpf' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'partner' => true,
    ];

    /**
     * Pode ser escolhido no formulário público: ele e o parceiro ativos, e o
     * parceiro comum -- vínculo associativo não tem corretores.
     */
    public function isSelectable(): bool
    {
        return $this->active
            && $this->partner !== null
            && $this->partner->active
            && !$this->partner->is_association;
    }

    /**
     * "{Parceiro} - {Corretor}", como aparece no select da adesão.
     */
    protected function _getLabel(): string
    {
        return ($this->partner?->name ?? '?') . ' - ' . $this->name;
    }

    protected function _getFormattedCpf(): ?string
    {
        return Cpf::format($this->cpf);
    }
}
