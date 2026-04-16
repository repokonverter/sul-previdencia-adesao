<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">
        <i class="bi bi-pencil-square"></i> Editar Adesão
    </h2>

    <?= $this->Html->link(
        '<i class="bi bi-arrow-left"></i> Voltar',
        ['action' => 'view', $adhesion->id],
        ['escape' => false, 'class' => 'btn btn-outline-secondary']
    ) ?>
</div>

<div class="adhesion-form-container">
    <?= $this->Form->create($adhesion) ?>
    
    <?= $this->element('../Admin/Adhesions/fields') ?>

    <div class="mt-4 mb-5">
        <?= $this->Form->button('<i class="bi bi-check-lg"></i> Salvar Alterações', [
            'escapeTitle' => false,
            'class' => 'btn btn-success btn-lg px-5'
        ]) ?>
    </div>

    <?= $this->Form->end() ?>
</div>

<?= $this->element('../Admin/Adhesions/js') ?>
