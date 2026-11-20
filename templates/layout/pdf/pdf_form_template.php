<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AdhesionInitialData $adhesion
 */

$personal = $adhesion->adhesion_personal_data;
$document = $adhesion->adhesion_document;
$address = $adhesion->adhesion_address;
$other = $adhesion->adhesion_other_information;

$documentLabel = ($document->type ?? '') !== 'Outro' ? ($document->type ?? 'Documento') : $document->type_other;
$cityState = implode('/', array_filter([$address->city ?? '', $address->state ?? '']));

/**
 * Uma célula de ficha: rótulo pequeno em cima e o valor logo abaixo, para o
 * valor ficar colado na descrição em vez de numa coluna separada.
 */
$field = function (string $label, $value, int $colspan = 1): string {
    $value = trim((string)$value);

    return sprintf(
        '<td colspan="%d"><div class="label">%s</div><div class="value">%s</div></td>',
        $colspan,
        h($label),
        $value === '' ? '&nbsp;' : h($value)
    );
};
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 30px 40px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #000;
        }

        h1 {
            text-align: center;
            font-size: 16px;
            margin: 0 0 12px;
        }

        .section-title {
            background: #003f7f;
            color: #fff;
            padding: 3px 6px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            margin-top: 10px;
        }

        table.form-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .form-table td {
            border: 1px solid #555;
            padding: 2px 5px 3px;
            vertical-align: top;
        }

        .form-table .label {
            font-size: 7.5px;
            color: #444;
            text-transform: uppercase;
        }

        .form-table .value {
            font-size: 10px;
            font-weight: bold;
            margin-top: 1px;
        }

        .note-box {
            border: 1px solid #555;
            padding: 6px;
            font-size: 9px;
            line-height: 1.4;
            margin-top: 12px;
        }

        .footer-text {
            font-size: 9px;
            line-height: 1.4;
            text-align: justify;
            margin-top: 12px;
        }

        .signature-line {
            margin-top: 25px;
            font-size: 10px;
        }
    </style>
</head>

<body>

    <h1>FORMULÁRIO DE INSCRIÇÃO</h1>

    <div class="section-title">Dados Pessoais</div>

    <table class="form-table">
        <colgroup>
            <col style="width: 25%">
            <col style="width: 25%">
            <col style="width: 25%">
            <col style="width: 25%">
        </colgroup>
        <tr>
            <?= $field('Nome Completo', $personal->name ?? '', 4) ?>
        </tr>
        <tr>
            <?= $field('Data de Nascimento', $personal?->birth_date?->format('d/m/Y')) ?>
            <?= $field('Nacionalidade', $personal->nacionality ?? '') ?>
            <?= $field('Nº Registro Profissional', $other->professional_register ?? '', 2) ?>
        </tr>
        <tr>
            <?= $field('Profissão', $other->main_occupation_description ?? '', 4) ?>
        </tr>
        <tr>
            <?= $field('Nome da Mãe', $personal->mother_name ?? '', 2) ?>
            <?= $field('Nome do Pai', $personal->father_name ?? '', 2) ?>
        </tr>
    </table>

    <div class="section-title">Documentos</div>

    <table class="form-table">
        <tr>
            <?= $field('CPF', $personal->cpf ?? '') ?>
            <?= $field((string)$documentLabel, $document->document_number ?? '') ?>
        </tr>
    </table>

    <div class="section-title">Endereço Residencial</div>

    <table class="form-table">
        <colgroup>
            <col style="width: 25%">
            <col style="width: 25%">
            <col style="width: 25%">
            <col style="width: 25%">
        </colgroup>
        <tr>
            <?= $field('Logradouro', $address->address ?? '', 3) ?>
            <?= $field('Número', $address->number ?? '') ?>
        </tr>
        <tr>
            <?= $field('Complemento', $address->complement ?? '', 2) ?>
            <?= $field('Bairro', $address->neighborhood ?? '', 2) ?>
        </tr>
        <tr>
            <?= $field('Cidade/UF', $cityState, 3) ?>
            <?= $field('CEP', $address->cep ?? '') ?>
        </tr>
    </table>

    <div class="section-title">Contato</div>

    <table class="form-table">
        <tr style="display: none;">
            <?= $field('Fone Residencial', $personal->phone ?? '') ?>
            <?= $field('Fone Comercial', $personal->commercial_phone ?? '') ?>
        </tr>
        <tr>
            <?= $field('Celular', $adhesion->phone ?? '') ?>
            <?= $field('E-mail', $adhesion->email ?? '') ?>
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
            Vinculado ao Segurado: <strong><?= h($pensionSchemeMain->name) ?></strong><br>
            CPF do Segurado: <strong><?= h($pensionSchemeMain->cpf) ?></strong><br>
            Grau de Parentesco: <strong><?= h($pensionSchemeMain->kinship) ?></strong>
        <?php } ?>
    </div>

    <p class="footer-text">
        (X) AUTORIZO ( ) NÃO AUTORIZO o <?= h($benefitPlan->institution['name']) ?> a realizar o tratamento de meus Dados
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
