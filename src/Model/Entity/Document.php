<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Document Entity
 *
 * @property int $id
 * @property int $subscription_id
 * @property string $type
 * @property string $file_path
 * @property \Cake\I18n\Date|null $issue_date
 * @property string|null $issuer
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Subscription $subscription
 */
class Document extends Entity
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
        'type' => true,
        'file_path' => true,
        'issue_date' => true,
        'issuer' => true,
        'created' => true,
        'modified' => true,
        'subscription' => true,
    ];
}
