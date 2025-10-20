<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Subscription Entity
 *
 * @property int $id
 * @property string|null $plan_type
 * @property string|null $plan_value
 * @property string|null $periodicity
 * @property string|null $payment_method
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Address[] $addresses
 * @property \App\Model\Entity\Dependent[] $dependents
 * @property \App\Model\Entity\Document[] $documents
 * @property \App\Model\Entity\Person[] $people
 */
class Subscription extends Entity
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
        'plan_type' => true,
        'plan_value' => true,
        'periodicity' => true,
        'payment_method' => true,
        'created' => true,
        'modified' => true,
        'addresses' => true,
        'dependents' => true,
        'documents' => true,
        'people' => true,
    ];
}
