<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ClicksignWebhook $webhook
 */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">Cadastrar Webhook Clicksign</h2>

    <?= $this->Html->link('<i class="bi bi-arrow-left"></i> Voltar', ['action' => 'index'], ['escape' => false, 'class' => 'btn btn-outline-secondary']) ?>
</div>

<div class="card p-4 shadow-sm" style="max-width: 640px;">
    <p class="text-muted">
        Registra na Clicksign a URL que receberá o aviso de que um envelope ou
        documento foi fechado. Um token de segurança é gerado automaticamente e
        embutido na URL. O aviso é só gatilho: o status exibido aqui vem sempre
        de uma consulta direta à Clicksign, nunca do que o aviso em si alegar.
    </p>

    <?= $this->Form->create($webhook) ?>
    <div class="mt-3">
        <?= $this->Form->button('<i class="bi bi-cloud-check"></i> Cadastrar na Clicksign', [
            'escapeTitle' => false,
            'class' => 'btn btn-primary'
        ]) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
