<?php

/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\PixWebhook> $webhooks
 * @var array<string, string> $benefitPlans slug => nome
 */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">Webhooks Pix</h2>

    <?= $this->Html->link(
        '<i class="bi bi-plus-circle me-1"></i> Cadastrar Webhook',
        ['action' => 'add'],
        ['escape' => false, 'class' => 'btn btn-primary']
    ) ?>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Plano</th>
                    <th>Chave Pix</th>
                    <th>URL cadastrada</th>
                    <th>Cadastrado em</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($webhooks as $webhook): ?>
                    <tr>
                        <td><?= h($benefitPlans[$webhook->benefit_plan] ?? $webhook->benefit_plan) ?></td>
                        <td><?= h($webhook->chave) ?></td>
                        <td class="text-break"><?= h($webhook->url) ?></td>
                        <td><?= h($webhook->created) ?></td>
                        <td class="text-end">
                            <?= $this->Form->postLink(
                                '<i class="bi bi-trash"></i> Remover',
                                ['action' => 'delete', $webhook->id],
                                [
                                    'escape' => false,
                                    'class' => 'btn btn-sm btn-outline-danger',
                                    'confirm' => 'Remover este webhook no banco e localmente?'
                                ]
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!count($webhooks)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Nenhum webhook cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
