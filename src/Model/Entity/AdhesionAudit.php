<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AdhesionAudit Entity
 *
 * Uma linha por edição feita no admin. `changes` é o diff em JSON, no formato
 * `campo => [antes, depois]`, com o caminho pontuado da associação
 * (`adhesion_plan.monthly_survivors_pension_contribution`) para que a leitura
 * diga de que parte da adesão se trata sem precisar adivinhar.
 *
 * @property int $id
 * @property int $adhesion_initial_data_id
 * @property int|null $user_id
 * @property string|null $user_name
 * @property string $action
 * @property string|null $changes
 * @property \Cake\I18n\DateTime $created
 */
class AdhesionAudit extends Entity
{
    public const ACTION_CREATED = 'adhesion.created';
    public const ACTION_UPDATED = 'adhesion.updated';

    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'user_id' => true,
        'user_name' => true,
        'action' => true,
        'changes' => true,
        'created' => true,
    ];

    /**
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function changeList(): array
    {
        if (empty($this->changes)) {
            return [];
        }

        return json_decode($this->changes, true) ?: [];
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Adesão cadastrada',
            self::ACTION_UPDATED => 'Adesão editada',
            default => $this->action,
        };
    }

    /**
     * Quem fez. Cai no snapshot quando o usuário não está mais acessível, e em
     * "Sistema" quando a ação não veio de uma sessão de admin.
     */
    public function authorLabel(): string
    {
        return $this->user_name ?: 'Sistema';
    }
}
