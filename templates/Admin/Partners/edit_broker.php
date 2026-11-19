<?php
$this->assign('title', 'Editar Corretor');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">
        <i class="bi bi-person-badge"></i> Editar corretor de <?= h($partner->name) ?>
    </h2>

    <?= $this->Html->link(
        '<i class="bi bi-arrow-left"></i> Voltar',
        ['action' => 'view', $partner->id],
        ['escape' => false, 'class' => 'btn btn-outline-secondary']
    ) ?>
</div>
<?= $this->element('../Admin/Partners/broker_form') ?>
