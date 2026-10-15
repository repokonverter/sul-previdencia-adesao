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
 * @property bool $is_association
 * @property string|null $company_name
 * @property string|null $company_cnpj
 * @property string|null $institution_number
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
        'is_association' => true,
        'company_name' => true,
        'company_cnpj' => true,
        'institution_number' => true,
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

    /**
     * Nome, CNPJ e Nº do Instituidor da empresa/entidade, para a Declaração
     * de Vínculo Associativo (pdf_association_declaration.php) e para o topo
     * da proposta (pdf_template.php).
     *
     * É a partir daqui que o snapshot gravado na adesão (association_snapshot)
     * é montado, no momento em que o vínculo é validado pela primeira vez --
     * assim a declaração já assinada continua reproduzindo o que foi de fato
     * assinado, mesmo que o cadastro do vínculo mude depois.
     *
     * @return array{companyName: string|null, companyCnpj: string|null, institutionNumber: string|null}
     */
    public function associationDeclarationData(): array
    {
        return [
            'companyName' => $this->company_name,
            'companyCnpj' => $this->company_cnpj,
            'institutionNumber' => $this->institution_number,
        ];
    }
}
