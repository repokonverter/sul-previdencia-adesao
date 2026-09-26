<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AdhesionInitialData $adhesion
 * @var array{title: string, institutionName: string, body: string|null} $declaration
 */

$associationPartner = $adhesion->association_partner;

$logoDataUri = null;

if ($associationPartner !== null && $associationPartner->has_logo) {
    $logoData = $associationPartner->logo_data;

    if (is_resource($logoData)) {
        $logoData = stream_get_contents($logoData);
    }

    $logoDataUri = 'data:' . $associationPartner->logo_mime_type . ';base64,' . base64_encode((string)$logoData);
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }

        h1 {
            text-align: center;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .section-title {
            background: #003f7f;
            color: #fff;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 13px;
            margin-top: 12px;
        }

        table.form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .form-table th,
        .form-table td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 12px;
        }

        .full {
            width: 100%;
        }

        .note-box {
            width: 90%;
            border: 1px solid #000;
            padding: 6px;
            font-size: 10px;
            margin-top: 6px;
            margin-bottom: 12px;
        }

        .small-text {
            font-size: 10px;
        }

        .footer-text {
            font-size: 11px;
            margin-top: 30px;
        }

        .checkbox-box {
            border: 1px solid #000;
            width: 10px;
            height: 10px;
            display: inline-block;
            vertical-align: middle;
            margin-right: 3px;
        }

        .checkbox-box.checked {
            background: #000;
        }
    </style>
</head>

<body>

    <?php if ($logoDataUri !== null): ?>
        <div style="text-align: center; margin-bottom: 10px;">
            <img src="<?= $logoDataUri ?>" alt="<?= h($associationPartner->name) ?>" style="max-height: 60px;">
        </div>
    <?php endif; ?>

    <h1><?= h($declaration['title']) ?></h1>

    <div class="section-title">DADOS PESSOAIS</div>

    <table class="form-table">
        <tr>
            <td><strong>Nome Completo:</strong></td>
            <td colspan="3"><?= h($adhesion->adhesion_personal_data->name ?? '') ?></td>
        </tr>
        <tr>
            <td><strong>Data de Nascimento:</strong></td>
            <td><?= h($adhesion->adhesion_personal_data->birth_date->format('d/m/Y') ?? '') ?></td>

            <td><strong>Nacionalidade:</strong></td>
            <td><?= h($adhesion->adhesion_personal_data->nacionality ?? '') ?></td>
        </tr>

        <tr>
            <td><strong>Nome da Mãe:</strong></td>
            <td colspan="3"><?= h($adhesion->adhesion_personal_data->mother_name ?? '') ?></td>
        </tr>

        <tr>
            <td><strong>Nome do Pai:</strong></td>
            <td colspan="3"><?= h($adhesion->adhesion_personal_data->father_name ?? '') ?></td>
        </tr>

        <tr>
            <td><strong>Profissão:</strong></td>
            <td><?= h($adhesion->adhesion_other_information->main_occupation_description ?? '') ?></td>

            <td><strong>Nº Registro Profissional:</strong></td>
            <td><?= h($adhesion->adhesion_other_information->professional_register ?? '') ?></td>
        </tr>
    </table>

    <div class="section-title">Documentos</div>

    <table class="form-table">
        <tr>
            <td><strong>CPF:</strong></td>
            <td><?= h($adhesion->adhesion_personal_data->cpf ?? '') ?></td>
        </tr>
        <tr>
            <td><strong><?= $adhesion->adhesion_document->type !== 'Outro' ? $adhesion->adhesion_document->type : $adhesion->adhesion_document->type_other; ?>:</strong></td>
            <td><?= h($adhesion->adhesion_document->document_number ?? '') ?></td>
        </tr>
    </table>

    <div class="section-title">Endereço Residencial</div>

    <table class="form-table">
        <tr>
            <td><strong>Logradouro:</strong></td>
            <td colspan="3"><?= h($adhesion->adhesion_address->address ?? '') ?></td>
        </tr>

        <tr>
            <td><strong>Número:</strong></td>
            <td><?= h($adhesion->adhesion_address->number ?? '') ?></td>

            <td><strong>Complemento:</strong></td>
            <td><?= h($adhesion->adhesion_address->complement ?? '') ?></td>
        </tr>

        <tr>
            <td><strong>Bairro:</strong></td>
            <td><?= h($adhesion->adhesion_address->neighborhood ?? '') ?></td>

            <td><strong>Cidade/UF:</strong></td>
            <td><?= h($adhesion->adhesion_address->city ?? '') ?>/<?= h($adhesion->adhesion_address->state ?? '') ?></td>
        </tr>

        <tr>
            <td><strong>CEP:</strong></td>
            <td colspan="3"><?= h($adhesion->adhesion_address->cep ?? '') ?></td>
        </tr>
    </table>

    <div class="section-title">Contato</div>

    <table class="form-table">
        <tr style="display: none;">
            <td><strong>Fone Residencial:</strong></td>
            <td><?= h($adhesion->adhesion_personal_data->phone ?? '') ?></td>

            <td><strong>Fone Comercial:</strong></td>
            <td><?= h($adhesion->adhesion_personal_data->commercial_phone ?? '') ?></td>
        </tr>

        <tr>
            <td><strong>Celular:</strong></td>
            <td><?= h($adhesion->phone ?? '') ?></td>

            <td><strong>E-mail:</strong></td>
            <td><?= h($adhesion->email ?? '') ?></td>
        </tr>
    </table>

    <?php
        $pensionSchemes = $adhesion->adhesion_pension_schemes ?? [];
        $pensionSchemeValues = array_map(fn($ps) => $ps->pension_scheme, $pensionSchemes);
        $pensionSchemeMain = $pensionSchemes[0] ?? null;
    ?>
    <div class="note-box">
        <?= !$pensionSchemeMain || !$pensionSchemeMain->cpf ? 'DECLARO sob as penas da Lei, que sou segurado do' : 'DECLARO sob as penas da Lei, que sou parente até segundo grau do segurado abaixo identificado, o qual é vinculado ao' ?> seguinte regime de previdência:
        <br>
        (<?= in_array('Geral (INSS)', $pensionSchemeValues) ? 'X' : ' ' ?>) GERAL (INSS);
        (<?= in_array('Próprio (Servidor público)', $pensionSchemeValues) ? 'X' : ' ' ?>) PRÓPRIO (Servidor Público);
        (<?= in_array('Complementar (Fundos de pensão)', $pensionSchemeValues) ? 'X' : ' ' ?>) COMPLEMENTAR (Fundos de Pensão)
        <?php if ($pensionSchemeMain && $pensionSchemeMain->cpf) { ?>
            <br><br>
            Vinculado ao Segurado: <?= h($pensionSchemeMain->name) ?><br>
            CPF do Segurado: <?= h($pensionSchemeMain->cpf) ?><br>
            Grau de Parentesco: <?= h($pensionSchemeMain->kinship) ?>
        <?php } ?>
    </div>

    <?php if (!empty($declaration['body'])): ?>
        <div class="note-box"><?= nl2br(h($declaration['body'])) ?></div>
    <?php endif; ?>

    <p class="footer-text">
        (X) AUTORIZO ( ) NÃO AUTORIZO o <?= h($declaration['institutionName']) ?> a realizar o tratamento de meus Dados
        Pessoais para oferecer produtos e serviços, por meio de e-mail, ligações, SMS,
        mensagens, bem como autorizo compartilhar meus dados com seus parceiros e demais
        prestadores de serviços.
    </p>

    <div class="signature-line">
        <p>Local: Florianópolis, <?= date('d') ?> de <?= $this->Utils->monthName(date('m')) ?> de <?= date('Y') ?>.</p>
        <p>Assinatura: _____________________________________________</p>
    </div>

</body>

</html>