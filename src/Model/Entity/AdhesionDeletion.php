<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AdhesionDeletion Entity
 *
 * Uma linha por adesão excluída no admin. Sobrevive à exclusão porque não tem
 * chave estrangeira para a adesão — ver a migration CreateAdhesionDeletions.
 *
 * @property int $id
 * @property int $adhesion_initial_data_id
 * @property string|null $adhesion_name
 * @property string|null $adhesion_cpf
 * @property int|null $user_id
 * @property string|null $user_name
 * @property \Cake\I18n\DateTime $created
 */
class AdhesionDeletion extends Entity
{
    protected array $_accessible = [
        'adhesion_initial_data_id' => true,
        'adhesion_name' => true,
        'adhesion_cpf' => true,
        'user_id' => true,
        'user_name' => true,
        'created' => true,
    ];

    public function authorLabel(): string
    {
        return $this->user_name ?: 'Sistema';
    }
}
