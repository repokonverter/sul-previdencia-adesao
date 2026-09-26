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
 * @property string|null $declaration_title
 * @property string|null $declaration_institution_name
 * @property string|null $declaration_body
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
        'declaration_title' => true,
        'declaration_institution_name' => true,
        'declaration_body' => true,
        'created' => true,
        'modified' => true,
        'promotional_codes' => true,
    ];

    protected array $_hidden = [
        'logo_data',
    ];

    /**
     * Textos padrão do formulário de inscrição (pdf_form_template.php) quando
     * a adesão não tem vínculo associativo, ou quando o vínculo não preencheu
     * algum dos campos da declaração.
     */
    public const DEFAULT_DECLARATION_TITLE = 'FORMULÁRIO DE INSCRIÇÃO';
    public const DEFAULT_DECLARATION_INSTITUTION_NAME = 'CEPREV';
    public const DEFAULT_DECLARATION_BODY = null;

    protected function _getHasLogo(): bool
    {
        return $this->logo_mime_type !== null;
    }

    /**
     * Título, nome de instituição e texto livre efetivos para a declaração
     * deste vínculo, com fallback para os valores padrão (CEPREV) em qualquer
     * campo deixado em branco no cadastro.
     *
     * É a partir daqui que o snapshot gravado na adesão (association_snapshot)
     * é montado, no momento em que o vínculo é validado pela primeira vez.
     *
     * @return array{title: string, institutionName: string, body: string|null}
     */
    public function declarationTexts(): array
    {
        return [
            'title' => $this->declaration_title ?: self::DEFAULT_DECLARATION_TITLE,
            'institutionName' => $this->declaration_institution_name ?: self::DEFAULT_DECLARATION_INSTITUTION_NAME,
            'body' => $this->declaration_body ?: self::DEFAULT_DECLARATION_BODY,
        ];
    }
}
