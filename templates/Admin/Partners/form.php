<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Partner $partner
 */
?>
<div class="container mt-4">
    <?= $this->Form->create($partner, ['type' => 'file', 'class' => 'card p-4 shadow-sm']) ?>

    <div class="row">
        <div class="col-md-8">
            <div class="mb-3">
                <?= $this->Form->control('name', [
                    'label' => 'Nome do parceiro*',
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Sindilojas Porto Alegre',
                    'maxlength' => 120,
                    'required' => true,
                ]) ?>
            </div>
        </div>

        <div class="col-md-2">
            <div class="mb-3">
                <?= $this->Form->control('color', [
                    'label' => 'Cor*',
                    'type' => 'color',
                    'class' => 'form-control form-control-color',
                    'value' => $partner->color ?: \App\Model\Entity\Partner::DEFAULT_COLOR,
                    'required' => true,
                ]) ?>
            </div>
        </div>

        <div class="col-md-2">
            <div class="mb-3">
                <label class="form-label d-block">Situação</label>
                <div class="form-check form-switch mt-2">
                    <?= $this->Form->checkbox('active', [
                        'class' => 'form-check-input',
                        'id' => 'active',
                        'checked' => $partner->isNew() ? true : (bool)$partner->active,
                    ]) ?>
                    <label class="form-check-label" for="active">Ativo</label>
                </div>
                <div class="form-text">Desativar impede novos usos de todos os códigos deste parceiro.</div>
            </div>
        </div>
    </div>

    <hr>

    <div class="row align-items-start">
        <div class="col-md-8">
            <div class="mb-3">
                <?= $this->Form->control('logo_file', [
                    'label' => 'Logo do parceiro (opcional)',
                    'type' => 'file',
                    'class' => 'form-control',
                    'accept' => 'image/png,image/jpeg,image/webp',
                ]) ?>
                <div class="form-text">PNG, JPEG ou WEBP. Máximo de 1 MB. Prefira fundo transparente.</div>
            </div>
        </div>

        <?php if (!$partner->isNew() && $partner->has_logo): ?>
            <div class="col-md-4">
                <label class="form-label d-block">Logo atual</label>
                <div class="border rounded p-2 bg-light text-center mb-2">
                    <?= $this->Html->image(
                        ['controller' => 'Partners', 'action' => 'logo', $partner->id, 'prefix' => false],
                        ['alt' => $partner->name, 'style' => 'max-height:60px;max-width:100%;']
                    ) ?>
                </div>
                <div class="form-check">
                    <?= $this->Form->checkbox('remove_logo', ['class' => 'form-check-input', 'id' => 'remove_logo']) ?>
                    <label class="form-check-label" for="remove_logo">Remover a logo atual</label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="text-end mt-3">
        <?= $this->Form->button('Salvar', ['class' => 'btn btn-success']) ?>
        <?= $this->Html->link('Cancelar', ['action' => 'index'], ['class' => 'btn btn-secondary ms-2']) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
