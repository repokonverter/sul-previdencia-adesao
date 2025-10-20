<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Dependent Entity
 *
 * @property int $id
 * @property int $subscription_id
 * @property string $name
 * @property string|null $cpf
 * @property \Cake\I18n\Date|null $birth_date
 * @property string|null $kinship
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Subscription $subscription
 */
class Dependent extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'subscription_id' => true,
        'name' => true,
        'cpf' => true,
        'birth_date' => true,
        'kinship' => true,
        'created' => true,
        'modified' => true,
        'subscription' => true,
    ];
}
