<?php
$this->assign('title', 'Novo Usuário');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">
        <i class="bi bi-person-plus-fill"></i> Novo Usuário
    </h2>

    <?= $this->Html->link(
        '<i class="bi bi-arrow-left"></i> Voltar',
        ['action' => 'index'],
        ['escape' => false, 'class' => 'btn btn-outline-secondary']
    ) ?>
</div>
<?= $this->element('../Admin/Users/form') ?>
