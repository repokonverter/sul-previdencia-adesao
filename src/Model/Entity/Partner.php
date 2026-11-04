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

    /**
     * A logo embutida como data URI, para os PDFs: o dompdf não busca a
     * rota de logo, que depende de uma requisição HTTP.
     */
    public function logoDataUri(): ?string
    {
        if (!$this->has_logo) {
            return null;
        }

        $data = $this->logo_data;

        if (is_resource($data)) {
            // O stream do Postgres é lido uma vez só; sem voltar ao início,
            // um segundo PDF na mesma requisição sairia sem logo.
            rewind($data);
            $data = stream_get_contents($data);
        }

        return 'data:' . $this->logo_mime_type . ';base64,' . base64_encode((string)$data);
    }

    /**
     * Mistura a cor com branco: $amount = 0 é branco, 1 é a cor pura.
     *
     * A cor do parceiro entra nos PDFs só como acento (linhas e bordas),
     * nunca como fundo cheio: um vermelho claro atrás da logo deixava o topo
     * todo vermelho. Para fundos vai um tom bem claro dela, calculado aqui
     * porque o dompdf não entende color-mix().
     */
    public static function tint(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (!ctype_xdigit($hex) || strlen($hex) !== 6) {
            return '#fafafa';
        }

        $channels = array_map(
            fn(string $channel): int => (int)round(255 - (255 - hexdec($channel)) * $amount),
            str_split($hex, 2)
        );

        return vsprintf('#%02x%02x%02x', $channels);
    }
}
