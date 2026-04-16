<?php
$this->assign('title', 'Editar Usuário');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">
        <i class="bi bi-person-gear"></i> Editar Usuário
    </h2>

    <?= $this->Html->link(
        '<i class="bi bi-arrow-left"></i> Voltar',
        ['action' => 'index'],
        ['escape' => false, 'class' => 'btn btn-outline-secondary']
    ) ?>
</div>
<?= $this->element('../Admin/Users/form') ?>
