<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AdhesionInitialData $adhesion
 * @var array{companyName: string|null, companyCnpj: string|null} $declaration
 */

$associationPartner = $adhesion->association_partner;
$color = $associationPartner?->color ?? \App\Model\Entity\Partner::DEFAULT_COLOR;

// A cor do parceiro entra só como acento (linhas e bordas), nunca como fundo
// cheio: um vermelho claro atrás da logo deixava o topo todo vermelho. Para o
// fundo da caixa da declaração vai um tom bem claro dela, misturado com
// branco aqui mesmo porque o dompdf não entende color-mix().
$tint = function (string $hex, float $amount): string {
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
};
$softColor = $tint($color, 0.06);

$logoDataUri = null;

if ($associationPartner !== null && $associationPartner->has_logo) {
    $logoData = $associationPartner->logo_data;

    if (is_resource($logoData)) {
        $logoData = stream_get_contents($logoData);
    }

    $logoDataUri = 'data:' . $associationPartner->logo_mime_type . ';base64,' . base64_encode((string)$logoData);
}

$clientName = $adhesion->adhesion_personal_data->name ?? $adhesion->name ?? '';
$clientCpf = $adhesion->adhesion_personal_data->cpf ?? '';
$companyName = $declaration['companyName'] ?? '';
$companyCnpj = $declaration['companyCnpj'] ?? '';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            margin: 0;
        }

        .header-band {
            background: #fff;
            border-bottom: 3px solid <?= h($color) ?>;
            padding: 22px 36px 18px 36px;
            text-align: center;
        }

        .header-band img {
            max-height: 56px;
            max-width: 260px;
        }

        .header-band .partner-name {
            color: #1a1a1a;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .content {
            padding: 36px 48px;
        }

        h1 {
            text-align: center;
            font-size: 18px;
            letter-spacing: 1px;
            color: #1a1a1a;
            margin: 0 0 4px 0;
        }

        .title-rule {
            width: 90px;
            height: 3px;
            background: <?= h($color) ?>;
            margin: 10px auto 30px auto;
        }

        .declaration-box {
            border: 1px solid #d8d8d8;
            border-left: 4px solid <?= h($color) ?>;
            background: <?= h($softColor) ?>;
            padding: 22px 26px;
            font-size: 13px;
            line-height: 1.9;
            text-align: justify;
        }

        .declaration-box strong {
            font-weight: bold;
        }

        .closing {
            margin-top: 26px;
            font-size: 13px;
            line-height: 1.7;
        }

        .place-date {
            margin-top: 40px;
            font-size: 12px;
        }

        .signature-block {
            margin-top: 56px;
            text-align: center;
        }

        .signature-line {
            width: 320px;
            margin: 0 auto;
            border-top: 1px solid #1a1a1a;
            padding-top: 6px;
        }

        .signature-name {
            font-weight: bold;
            font-size: 13px;
        }

        .signature-cpf {
            font-size: 12px;
            color: #444;
        }

        .footer-note {
            position: relative;
            margin-top: 70px;
            padding-top: 8px;
            border-top: 1px solid #e0e0e0;
            font-size: 9px;
            color: #888;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="header-band">
        <?php if ($logoDataUri !== null): ?>
            <img src="<?= $logoDataUri ?>" alt="<?= h($associationPartner->name) ?>">
        <?php else: ?>
            <span class="partner-name"><?= h($associationPartner->name ?? '') ?></span>
        <?php endif; ?>
    </div>

    <div class="content">
        <h1>DECLARAÇÃO DE VÍNCULO ASSOCIATIVO</h1>
        <div class="title-rule"></div>

        <div class="declaration-box">
            Eu, <strong><?= h($clientName) ?></strong>, inscrito(a) no CPF sob o nº
            <strong><?= h($clientCpf) ?></strong>, declaro para os devidos fins de direito que
            possuo vínculo associativo ativo com a instituição
            <strong><?= h($companyName) ?></strong>, inscrita no CNPJ sob o nº
            <strong><?= h($companyCnpj) ?></strong>.
        </div>

        <p class="closing">Por ser a expressão da verdade, firmo a presente declaração.</p>

        <p class="place-date">
            Florianópolis - SC, <?= date('d') ?> de <?= $this->Utils->monthName(date('m')) ?> de <?= date('Y') ?>.
        </p>

        <div class="signature-block">
            <div class="signature-line">
                <div class="signature-name"><?= h($clientName) ?></div>
                <div class="signature-cpf">CPF: <?= h($clientCpf) ?></div>
            </div>
        </div>

        <div class="footer-note">
            Declaração de Vínculo Associativo &mdash; Sul Previdência
        </div>
    </div>

</body>

</html>
