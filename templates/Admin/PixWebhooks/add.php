<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\PixWebhook $webhook
 * @var array<string, string> $benefitPlans slug => nome
 * @var array<string, string|null> $defaultKeys slug => chave Pix da conta do plano
 */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">Cadastrar Webhook Pix</h2>

    <?= $this->Html->link('<i class="bi bi-arrow-left"></i> Voltar', ['action' => 'index'], ['escape' => false, 'class' => 'btn btn-outline-secondary']) ?>
</div>

<div class="card p-4 shadow-sm" style="max-width: 640px;">
    <p class="text-muted">
        Registra no banco do plano a URL que receberá notificações de pagamento Pix para a
        chave informada. Um token de segurança é gerado automaticamente e embutido na URL.
    </p>

    <?= $this->Form->create($webhook) ?>
    <?= $this->Form->control('benefit_plan', [
        'label' => 'Plano',
        'type' => 'select',
        'options' => $benefitPlans,
        'empty' => count($benefitPlans) > 1 ? 'Selecione o plano' : false,
        'class' => 'form-select mb-3',
    ]) ?>
    <?php
    $keyHelp = [];
    foreach ($benefitPlans as $slug => $planName) {
        $keyHelp[] = $planName . ': ' . ($defaultKeys[$slug] ?? '(não configurada)');
    }
    ?>
    <?= $this->Form->control('chave', [
        'label' => 'Chave Pix',
        'class' => 'form-control',
        'required' => false,
        'help' => h('Em branco, usa a chave configurada para o plano — ' . implode(' · ', $keyHelp) . '.'),
    ]) ?>
    <div class="mt-3">
        <?= $this->Form->button('<i class="bi bi-cloud-check"></i> Cadastrar no banco', [
            'escapeTitle' => false,
            'class' => 'btn btn-primary'
        ]) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
