<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Partner Entity
 *
 * @property int $id
 * @property string $name
 * @property string $color
 * @property resource|string|null $logo_data
 * @property string|null $logo_mime_type
 * @property string|null $logo_filename
 * @property bool $active
 * @property \App\Model\Entity\PromotionalCode[] $promotional_codes
 */
class Partner extends Entity
{
    public const DEFAULT_COLOR = '#0d6efd';

    protected array $_accessible = [
        'name' => true,
        'color' => true,
        'logo_data' => true,
        'logo_mime_type' => true,
        'logo_filename' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'promotional_codes' => true,
    ];

    protected array $_hidden = [
        'logo_data',
    ];

    protected function _getHasLogo(): bool
    {
        return $this->logo_mime_type !== null;
    }
}
