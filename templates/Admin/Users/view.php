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
            <p><strong>Perfil:</strong> <?= h($user->role) ?></p>
            <p><strong>Criado em:</strong> <?= $user->created->format('d/m/Y H:i') ?></p>
            <p><strong>Atualizado em:</strong> <?= $user->modified->format('d/m/Y H:i') ?></p>
        </div>
        <div class="card-footer text-end">
            <?= $this->Html->link('Voltar', ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>
</div>
