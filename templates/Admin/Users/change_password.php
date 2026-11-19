<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var list<string> $personalWords
 */
$this->assign('title', 'Alterar senha');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">
        <i class="bi bi-key"></i> Alterar senha
    </h2>
</div>

<div class="card shadow-sm border-0" style="max-width: 520px;">
    <div class="card-body">
        <?= $this->Form->create($user) ?>
            <input type="email" name="username" value="<?= h($user->email) ?>" autocomplete="username" hidden readonly>

            <div class="mb-3">
                <?= $this->Form->control('current_password', [
                    'label' => 'Senha atual',
                    'type' => 'password',
                    'class' => 'form-control',
                    'autocomplete' => 'current-password',
                    'required' => true,
                    'value' => '',
                ]) ?>
            </div>

            <?= $this->element('password_fields', ['personalWords' => $personalWords]) ?>

            <p class="text-muted small">
                As sessões abertas em outros navegadores serão encerradas. Este continua conectado.
            </p>

            <div class="text-end">
                <?= $this->Form->button('Alterar senha', ['class' => 'btn btn-success']) ?>
                <?= $this->Html->link('Cancelar', '/admin', ['class' => 'btn btn-secondary ms-2']) ?>
            </div>
        <?= $this->Form->end() ?>
    </div>
</div>
