<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AdhesionInitialData $adhesion
 * @var \App\Services\PlanProjection $projection
 * @var array<string, string> $planManager
 */

use App\Model\Entity\Partner;
use App\Services\PlanProjection;
use App\Services\PlanSimulator;
use App\View\Helper\PlanChartHelper;

$brandOrange = '#f37c20';
$brandNavy = '#20446E';
$brandGreen = '#3B7A3B';

$plan = $adhesion->adhesion_plan;
$personalData = $adhesion->adhesion_personal_data;
$partner = $adhesion->association_partner;

// Com vínculo, a cor do parceiro vira o acento do documento (filetes e
// bordas); o fundo da faixa de parceria leva só um tom bem claro dela.
$accent = $partner?->color ?: $brandOrange;
$accentSoft = Partner::tint($accent, 0.07);
$partnerLogo = $partner?->logoDataUri();

$sulLogoPath = WWW_ROOT . 'img' . DS . 'logo_sul_recortada.png';
$sulLogo = is_file($sulLogoPath) ? 'data:image/png;base64,' . base64_encode((string)file_get_contents($sulLogoPath)) : null;

$clientName = $personalData->name ?? $adhesion->name ?? '';
$age = PlanSimulator::ageOn($personalData->birth_date->format('Y-m-d'));

$hasSurvivorsPension = $plan->has_survivors_pension !== false;
$hasDisabilityRetirement = $plan->has_disability_retirement !== false;
$survivorsPensionContribution = $hasSurvivorsPension ? (float)$plan->monthly_survivors_pension_contribution : 0.0;
$disabilityRetirementContribution = $hasDisabilityRetirement ? (float)$plan->monthly_disability_retirement_contribution : 0.0;
$retirementContribution = (float)$plan->monthly_retirement_contribution;
$totalContribution = $retirementContribution + $survivorsPensionContribution + $disabilityRetirementContribution;

$chart = $projection->hasProjection() ? $this->PlanChart->dataUri($projection) : null;
$chartScenario = $projection->chartScenario();
$ratePercent = fn(float $rate): string => (string)floor($rate * 100);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
            padding: 0 0 70px 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .header {
            padding: 26px 44px 18px 44px;
            border-bottom: 4px solid <?= h($accent) ?>;
        }

        .header td {
            vertical-align: middle;
        }

        .header .sul-logo {
            height: 52px;
        }

        .kicker {
            color: <?= h($brandOrange) ?>;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1.6px;
            text-transform: uppercase;
        }

        .doc-title {
            color: <?= h($brandNavy) ?>;
            font-size: 18px;
            font-weight: bold;
            margin-top: 3px;
        }

        .doc-date {
            color: #6b7280;
            font-size: 9px;
            margin-top: 3px;
        }

        .content {
            padding: 0 44px;
        }

        .partnership {
            margin-top: 16px;
            background: <?= h($accentSoft) ?>;
            border-left: 4px solid <?= h($accent) ?>;
            padding: 10px 16px;
        }

        .partnership td {
            vertical-align: middle;
        }

        .partnership .label {
            color: #4b5563;
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .partnership .name {
            color: #111827;
            font-size: 12px;
            font-weight: bold;
            margin-top: 2px;
        }

        .partnership img {
            max-height: 34px;
            max-width: 150px;
        }

        .client {
            margin-top: 18px;
        }

        .client .label {
            color: #6b7280;
            font-size: 8px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .client .name {
            color: #111827;
            font-size: 15px;
            font-weight: bold;
            margin-top: 2px;
        }

        .facts {
            margin-top: 10px;
        }

        .facts td {
            border: 1px solid #e5e7eb;
            padding: 8px 12px;
            width: 33.33%;
        }

        .facts .fact-label {
            color: #6b7280;
            font-size: 8px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .facts .fact-value {
            color: #111827;
            font-size: 12px;
            font-weight: bold;
            margin-top: 2px;
        }

        .section-title {
            color: <?= h($brandNavy) ?>;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin: 22px 0 8px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }

        .section-title span {
            border-bottom: 2px solid <?= h($accent) ?>;
            padding-bottom: 5px;
        }

        .contribution td {
            vertical-align: top;
            padding: 0;
        }

        .total-box {
            background: <?= h($brandNavy) ?>;
            color: #ffffff;
            padding: 14px 16px;
        }

        .total-box .label {
            font-size: 8px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #c7d4e6;
        }

        .total-box .value {
            font-size: 20px;
            font-weight: bold;
            margin-top: 4px;
        }

        .total-box .hint {
            font-size: 8px;
            color: #c7d4e6;
            margin-top: 3px;
        }

        .split td {
            border: 1px solid #e5e7eb;
            padding: 9px 12px;
            vertical-align: top;
        }

        .split .item-label {
            color: #4b5563;
            font-size: 8px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .split .item-value {
            color: #111827;
            font-size: 12px;
            font-weight: bold;
            margin-top: 2px;
        }

        .split .item-sub {
            color: #6b7280;
            font-size: 8.5px;
            margin-top: 2px;
        }

        .split .item-sub strong {
            color: <?= h($brandGreen) ?>;
        }

        .muted {
            color: #9ca3af;
        }

        .scenarios td {
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            width: 33.33%;
            text-align: center;
        }

        .scenarios td.featured {
            border: 1.5px solid <?= h($brandOrange) ?>;
            background: #fff8f2;
        }

        .scenarios .rate {
            color: #4b5563;
            font-size: 8px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .scenarios .balance {
            color: #111827;
            font-size: 14px;
            font-weight: bold;
            margin-top: 4px;
        }

        .scenarios .benefit {
            color: #4b5563;
            font-size: 8.5px;
            margin-top: 3px;
        }

        .scenarios .benefit strong {
            color: <?= h($brandGreen) ?>;
        }

        .legend {
            font-size: 8.5px;
            color: #4b5563;
            margin: 0 0 4px 0;
            text-align: right;
        }

        .legend .swatch {
            display: inline-block;
            width: 14px;
            height: 3px;
            margin: 0 4px 2px 12px;
        }

        .chart img {
            width: 100%;
        }

        .chart-caption {
            color: #6b7280;
            font-size: 8px;
            margin-top: 2px;
        }

        .notice {
            border: 1px solid #e5e7eb;
            border-left: 4px solid <?= h($brandOrange) ?>;
            background: #fafafa;
            padding: 10px 14px;
            color: #374151;
        }

        .notes {
            margin-top: 16px;
            color: #6b7280;
            font-size: 8px;
            line-height: 1.5;
        }

        .notes p {
            margin: 0 0 2px 0;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 44px;
            padding: 10px 44px 0 44px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 7.5px;
            line-height: 1.5;
        }

        .footer strong {
            color: <?= h($brandNavy) ?>;
        }
    </style>
</head>

<body>
    <div class="footer">
        <table>
            <tr>
                <td>
                    <strong><?= h($planManager['planManager']) ?></strong> &middot; CNPJ <?= h($planManager['cnpjPlanManager']) ?><br>
                    <?= h($planManager['address']) ?>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <strong><?= h($planManager['site']) ?></strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="header">
        <table>
            <tr>
                <td>
                    <?php if ($sulLogo !== null): ?>
                        <img class="sul-logo" src="<?= $sulLogo ?>" alt="Sul Previdência">
                    <?php endif; ?>
                </td>
                <td style="text-align: right;">
                    <div class="kicker">Plano PlenoPrev</div>
                    <div class="doc-title">Simulação do Plano</div>
                    <div class="doc-date">Emitida em <?= date('d/m/Y') ?></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <?php if ($partner !== null): ?>
            <div class="partnership">
                <table>
                    <tr>
                        <td>
                            <div class="label">Condição especial em parceria com</div>
                            <div class="name"><?= h($partner->name) ?></div>
                        </td>
                        <?php if ($partnerLogo !== null): ?>
                            <td style="text-align: right;">
                                <img src="<?= $partnerLogo ?>" alt="<?= h($partner->name) ?>">
                            </td>
                        <?php endif; ?>
                    </tr>
                </table>
            </div>
        <?php endif; ?>

        <div class="client">
            <div class="label">Preparado para</div>
            <div class="name"><?= h($clientName) ?></div>
        </div>

        <table class="facts">
            <tr>
                <td>
                    <div class="fact-label">Idade atual</div>
                    <div class="fact-value"><?= $age ?> anos</div>
                </td>
                <td>
                    <div class="fact-label">Entrada em benefício</div>
                    <div class="fact-value"><?= h((string)$plan->benefit_entry_age) ?> anos</div>
                </td>
                <td>
                    <div class="fact-label">Tipo de benefício</div>
                    <div class="fact-value">Prazo definido · 60 meses</div>
                </td>
            </tr>
        </table>

        <div class="section-title"><span>Contribuição mensal</span></div>

        <table class="contribution">
            <tr>
                <td style="width: 30%;">
                    <div class="total-box">
                        <div class="label">Total mensal</div>
                        <div class="value"><?= $this->Utils->currency($totalContribution) ?></div>
                        <div class="hint">Aposentadoria + coberturas de risco</div>
                    </div>
                </td>
                <td style="width: 70%; padding-left: 10px;">
                    <table class="split">
                        <tr>
                            <td style="width: 33.33%;">
                                <div class="item-label">Aposentadoria</div>
                                <div class="item-value"><?= $this->Utils->currency($retirementContribution) ?></div>
                                <div class="item-sub">Formação do saldo</div>
                            </td>
                            <td style="width: 33.33%;">
                                <div class="item-label">Pensão por morte</div>
                                <?php if ($hasSurvivorsPension): ?>
                                    <div class="item-value"><?= $this->Utils->currency($survivorsPensionContribution) ?></div>
                                    <div class="item-sub">Capital segurado <strong><?= $this->Utils->currency($plan->survivors_pension_insured_capital) ?></strong></div>
                                <?php else: ?>
                                    <div class="item-value muted">Não contratado</div>
                                <?php endif; ?>
                            </td>
                            <td style="width: 33.33%;">
                                <div class="item-label">Aposentadoria por invalidez</div>
                                <?php if ($hasDisabilityRetirement): ?>
                                    <div class="item-value"><?= $this->Utils->currency($disabilityRetirementContribution) ?></div>
                                    <div class="item-sub">Capital segurado <strong><?= $this->Utils->currency($plan->disability_retirement_insured_capital) ?></strong></div>
                                <?php else: ?>
                                    <div class="item-value muted">Não contratado</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <?php if ($projection->hasProjection()): ?>
            <div class="section-title"><span>Saldo projetado aos <?= PlanProjection::RETIREMENT_AGE ?> anos</span></div>

            <table class="scenarios">
                <tr>
                    <?php foreach ($projection->scenarios as $index => $scenario): ?>
                        <td class="<?= $index === PlanProjection::CHART_SCENARIO ? 'featured' : '' ?>">
                            <div class="rate">Rentabilidade <?= $ratePercent($scenario['rate']) ?>% a.a.</div>
                            <div class="balance"><?= $this->Utils->currency($scenario['balance']) ?></div>
                            <div class="benefit">Benefício mensal <strong><?= $this->Utils->currency($scenario['monthlyBenefit']) ?></strong></div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </table>

            <div class="section-title"><span>Evolução comparativa das contribuições</span></div>

            <div class="legend">
                <span class="swatch" style="background: <?= PlanChartHelper::COMPOUNDED_COLOR ?>;"></span>Contribuição rentabilizada
                <span class="swatch" style="background: <?= PlanChartHelper::PURE_COLOR ?>;"></span>Contribuição pura
            </div>

            <div class="chart">
                <img src="<?= $chart ?>" alt="Evolução do saldo acumulado até os <?= PlanProjection::RETIREMENT_AGE ?> anos">
            </div>
            <div class="chart-caption">
                Montante acumulado por idade no cenário de <?= $ratePercent($chartScenario['rate']) ?>% a.a.,
                considerando somente a contribuição de aposentadoria (<?= $this->Utils->currency($projection->retirementContribution) ?>/mês)
                ao longo de <?= $projection->monthsUntilRetirement ?> meses.
            </div>
        <?php else: ?>
            <div class="section-title"><span>Projeção de saldo</span></div>
            <div class="notice">
                Com <?= $age ?> anos não há mais meses de contribuição até os <?= PlanProjection::RETIREMENT_AGE ?> anos,
                então não há saldo a projetar. Os valores acima seguem válidos para as coberturas contratadas.
            </div>
        <?php endif; ?>

        <div class="notes">
            <p>* Os valores acima são apenas estimativas de capital acumulado aos <?= PlanProjection::RETIREMENT_AGE ?> anos e não garantem nenhum direito antecipado.</p>
            <p>** O tipo de benefício escolhido para esse plano é o Prazo Definido de 60 meses.</p>        </div>
    </div>
</body>

</html>
