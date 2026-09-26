<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Broker Entity
 *
 * Um corretor validado é o que permite, na etapa de idade e valores do
 * formulário público, remover um ou ambos os riscos (pensão por morte,
 * aposentadoria por invalidez) — ver [[RegistrationsController::save]] e
 * [[SimulatorController]]. O código é o que o corretor divulga (por link ou
 * digitando); susep_code é só informativo, não usado em cálculo algum.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $susep_code
 * @property bool $active
 */
class Broker extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'code' => true,
        'susep_code' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Mesma normalização de PromotionalCode::normalizeCode: maiúsculas, sem
     * acento, só letras/números/hífen. Consistência deliberada — os dois são
     * códigos que uma pessoa digita ou recebe por link.
     */
    protected function _setCode(?string $code): ?string
    {
        return static::normalizeCode($code);
    }

    public static function normalizeCode(?string $code): ?string
    {
        return PromotionalCode::normalizeCode($code);
    }

    public function isUsable(): bool
    {
        return $this->active;
    }
}
