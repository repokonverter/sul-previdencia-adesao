<?php

/**
 * Simulação avulsa: um plano para mandar ao cliente em PDF, sem adesão.
 * Nada é gravado; o formulário só existe para gerar o PDF.
 *
 * @var \App\View\AppView $this
 * @var \App\Form\PlanSimulationForm $simulation
 * @var int $minimumRiskAge
 */
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="fw-bold text-primary">
        <i class="bi bi-calculator"></i> Simulação
    </h2>
</div>
<p class="text-muted mb-4">
    Monte um plano para um cliente que ainda não tem adesão, confira os
    cenários e gere o PDF para enviar. Nada aqui é gravado.
</p>

<?= $this->Form->create($simulation, ['url' => ['action' => 'pdf'], 'id' => 'planSimulationForm']) ?>

<div class="card p-4 shadow-sm mb-4">
    <h5 class="fw-bold mb-3 text-primary">Cliente</h5>
    <div class="row">
        <div class="col-md-8 mb-3">
            <?= $this->Form->control('name', [
                'label' => 'Nome*',
                'class' => 'form-control',
                'required' => true,
            ]) ?>
        </div>
        <div class="col-md-4 mb-3">
            <?= $this->Form->control('birth_date', [
                'label' => 'Data de nasc.*',
                'class' => 'form-control',
                'type' => 'date',
                'required' => true,
                'max' => date('Y-m-d'),
            ]) ?>
        </div>
    </div>
</div>

<div class="card p-4 shadow-sm">
    <h5 class="fw-bold mb-3 text-primary">Plano</h5>

    <h6 class="fw-bold mb-2">Riscos contratados</h6>
    <p class="text-muted small">
        Desmarcar transfere a fatia do risco para a contribuição de aposentadoria.
    </p>

    <?= $this->element('Plan/editor') ?>

    <hr class="my-4">

    <?= $this->element('Plan/projection', ['pdfSubmit' => true]) ?>
</div>

<?= $this->Form->end() ?>

<?= $this->Html->script('admin/plan-editor') ?>
<script>
    $(document).ready(function() {
        const form = document.getElementById('planSimulationForm');

        PlanEditor.init({
            root: form.querySelector('[data-plan-editor]'),
            birthDateInput: form.querySelector('input[name="birth_date"]'),
            urls: {
                riskCapital: <?= json_encode($this->Url->build(['controller' => 'Simulator', 'action' => 'recalculateRiskCapital', 'prefix' => false])) ?>,
                recalculate: <?= json_encode($this->Url->build(['action' => 'recalculate'])) ?>,
                projection: <?= json_encode($this->Url->build(['action' => 'projection'])) ?>,
            },
            minimumRiskAge: <?= (int)$minimumRiskAge ?>,
        });
    });
</script>
