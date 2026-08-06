<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\PixWebhook $webhook
 * @var string|null $defaultChave
 */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">Cadastrar Webhook Pix</h2>

    <?= $this->Html->link('<i class="bi bi-arrow-left"></i> Voltar', ['action' => 'index'], ['escape' => false, 'class' => 'btn btn-outline-secondary']) ?>
</div>

<div class="card p-4 shadow-sm" style="max-width: 640px;">
    <p class="text-muted">
        Registra no Sicoob a URL que receberá notificações de pagamento Pix para a chave
        informada. Um token de segurança é gerado automaticamente e embutido na URL.
    </p>

    <?= $this->Form->create($webhook) ?>
    <?= $this->Form->control('chave', [
        'label' => 'Chave Pix',
        'class' => 'form-control',
        'value' => $defaultChave,
        'help' => 'Chave cadastrada no Sicoob para recebimento das cobranças.'
    ]) ?>
    <div class="mt-3">
        <?= $this->Form->button('<i class="bi bi-cloud-check"></i> Cadastrar no Sicoob', [
            'escapeTitle' => false,
            'class' => 'btn btn-primary'
        ]) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
