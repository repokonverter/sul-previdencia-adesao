<?php

/**
 * Os campos do plano: riscos, idade de entrada, contribuições, capitais e o
 * total com "Recalcular". Usado pela aba Plano do editar/criar adesão e pela
 * simulação avulsa (Admin/Simulations). O comportamento está em
 * webroot/js/admin/plan-editor.js, que acha cada campo por data-plan-field
 * e não pelo name -- os nomes mudam com o prefixo.
 *
 * @var \App\View\AppView $this
 * @var string $prefix prefixo dos campos ('adhesion_plan' na adesão, '' na simulação)
 * @var bool $locked valores travados (adesão paga ou assinada)
 * @var bool $canRestoreRisk se um risco desmarcado pode ser marcado de volta
 * @var string|null $riskNotice aviso abaixo dos riscos
 * @var int|null $minimumEntryAge idade atual, piso da idade de entrada
 * @var string|null $recalculateBlockedReason por que o "Recalcular" está desligado
 */

use App\Services\BenefitEntryAge;
use App\Services\PlanSimulator;

$prefix ??= '';
$locked ??= false;
$canRestoreRisk ??= true;
$riskNotice ??= null;
$minimumEntryAge ??= null;
$recalculateBlockedReason ??= null;

$path = fn(string $field): string => $prefix === '' ? $field : "$prefix.$field";
$money = fn(string $field, string $label, string $role, array $options = []): string => $this->Form->control(
    $path($field),
    $options + [
        'label' => $label,
        'class' => 'form-control money',
        'prepend' => 'R$',
        'type' => 'text',
        'data-plan-field' => $role,
    ]
);
?>
<div data-plan-editor>
    <div class="row mb-3">
        <?php foreach ([
            'has_survivors_pension' => ['Pensão por morte', 'survivors'],
            'has_disability_retirement' => ['Aposentadoria por invalidez', 'disability'],
        ] as $field => [$label, $risk]): ?>
            <?php $contracted = (bool)($this->Form->getSourceValue($path($field)) ?? true); ?>
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <?= $this->Form->checkbox($path($field), [
                        'class' => 'form-check-input',
                        'id' => $field,
                        'checked' => $contracted,
                        'data-plan-risk' => $risk,
                        'disabled' => $locked || (!$contracted && !$canRestoreRisk),
                    ]) ?>
                    <label class="form-check-label" for="<?= $field ?>"><?= $label ?></label>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($riskNotice !== null): ?>
        <div class="alert alert-info py-2 small">
            <i class="bi bi-info-circle"></i>
            <?= h($riskNotice) ?>
        </div>
    <?php endif; ?>

    <div class="alert alert-info py-2 small d-none" data-plan-minimum-risk-age>
        <i class="bi bi-info-circle"></i>
        Abaixo de <?= PlanSimulator::MINIMUM_RISK_AGE ?> anos não há pensão por morte nem
        aposentadoria por invalidez: a contribuição inteira vai para a aposentadoria.
    </div>

    <hr class="my-3">

    <div class="row">
        <div class="col-md-4 mb-3">
            <?= $this->Form->control($path('benefit_entry_age'), [
                'label' => 'Idade para entrada em benefício',
                'class' => 'form-control',
                'type' => 'number',
                'min' => $minimumEntryAge,
                'max' => BenefitEntryAge::MAXIMUM,
                'data-plan-field' => 'entryAge',
            ]) ?>
            <div class="form-text">Até <?= BenefitEntryAge::MAXIMUM ?> anos, e não menos que a idade atual.</div>
        </div>
        <div class="col-md-4 mb-3">
            <?= $money('monthly_retirement_contribution', 'Contribuição mensal aposentadoria', 'retirement') ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <?= $money('monthly_survivors_pension_contribution', 'Contribuição mensal pensão por morte', 'survivors') ?>
        </div>
        <div class="col-md-6 mb-3">
            <?= $money('survivors_pension_insured_capital', 'Capital segurado pensão por morte', 'survivorsCapital') ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <?= $money('monthly_disability_retirement_contribution', 'Contribuição mensal aposentadoria por invalidez', 'disability') ?>
        </div>
        <div class="col-md-6 mb-3">
            <?= $money('disability_retirement_insured_capital', 'Capital segurado aposentadoria por invalidez', 'disabilityCapital') ?>
        </div>
    </div>

    <hr class="my-3">

    <div class="row">
        <div class="col-md-4">
            <label for="planTotalContribution" class="form-label fw-bold">Contribuição total mensal</label>
            <div class="input-group">
                <span class="input-group-text">R$</span>
                <input type="text" class="form-control money fw-bold" id="planTotalContribution" data-plan-field="total"
                       <?= $recalculateBlockedReason === null ? '' : 'readonly' ?>>
                <button type="button" class="btn btn-outline-primary" id="btnRecalculatePlan" data-plan-recalculate
                        <?= $recalculateBlockedReason === null ? '' : 'disabled' ?>>
                    <i class="bi bi-arrow-repeat"></i> Recalcular
                </button>
            </div>
            <div class="form-text">
                <?php if ($recalculateBlockedReason === null): ?>
                    É a soma das três contribuições, mas dá para editar.
                    "Recalcular" mantém as contribuições de risco já preenchidas,
                    dá a fatia padrão aos riscos marcados que estão zerados,
                    recalcula o capital segurado e manda para a aposentadoria a
                    diferença entre o total e os riscos.
                <?php else: ?>
                    <?= h($recalculateBlockedReason) ?>
                <?php endif; ?>
            </div>
            <div class="alert alert-danger py-2 mt-2 small" style="display:none;" data-plan-error></div>
        </div>
    </div>
</div>
