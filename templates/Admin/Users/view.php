<?php
$this->assign('title', 'Detalhes do Usuário');
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header">
            <h4>Usuário #<?= h($user->id) ?></h4>
        </div>
        <div class="card-body">
            <p><strong>Nome:</strong> <?= h($user->name) ?></p>
            <p><strong>E-mail:</strong> <?= h($user->email) ?></p>
            <p><strong>Perfil:</strong> <?= h($user->roleLabel()) ?></p>
            <p>
                <strong>Senha:</strong>
                <?php if ($user->hasDefinedPassword()): ?>
                    definida em <?= $user->password_changed_at->format('d/m/Y H:i') ?>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">Aguardando o usuário definir</span>
                <?php endif; ?>
            </p>
            <p><strong>Criado em:</strong> <?= $user->created->format('d/m/Y H:i') ?></p>
            <p><strong>Atualizado em:</strong> <?= $user->modified->format('d/m/Y H:i') ?></p>
        </div>
        <div class="card-footer text-end">
            <?php if ($isAdmin): ?>
                <?= $this->Form->postLink(
                    '<i class="bi bi-envelope me-1"></i>' . ($user->hasDefinedPassword() ? 'Enviar link para redefinir senha' : 'Enviar link para definir senha'),
                    ['action' => 'sendPasswordLink', $user->id],
                    [
                        'escape' => false,
                        'class' => 'btn btn-outline-primary me-2',
                        'confirm' => __('Enviar para {0} um link de senha? Um link enviado antes deixa de valer.', $user->email),
                    ]
                ) ?>
            <?php endif; ?>
            <?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>
</div>
