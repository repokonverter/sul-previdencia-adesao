<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Partner $partner
 * @var \App\Model\Entity\PromotionalCode $promotionalCode
 */
?>
<div class="container mt-4">
    <?= $this->Form->create($promotionalCode, ['class' => 'card p-4 shadow-sm']) ?>

    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <?= $this->Form->control('code', [
                    'label' => 'Código*',
                    'class' => 'form-control text-uppercase',
                    'placeholder' => 'EX: SINDILOJAS2026',
                    'maxlength' => 30,
                    'required' => true,
                ]) ?>
                <div class="form-text">Sempre gravado em maiúsculas. Apenas letras, números e hífen. De 3 a 30 caracteres.</div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="mb-3">
                <?= $this->Form->control('description', [
                    'label' => 'Descrição (opcional)',
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Campanha de março na rádio',
                    'maxlength' => 255,
                ]) ?>
                <div class="form-text">Nota interna, visível apenas aqui no admin.</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <?= $this->Form->control('valid_from', [
                    'label' => 'Válido a partir de',
                    'type' => 'date',
                    'class' => 'form-control',
                    'empty' => true,
                ]) ?>
                <div class="form-text">Em branco = válido desde já.</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="mb-3">
                <?= $this->Form->control('valid_until', [
                    'label' => 'Válido até',
                    'type' => 'date',
                    'class' => 'form-control',
                    'empty' => true,
                ]) ?>
                <div class="form-text">Em branco = validade indeterminada. A data é inclusiva.</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="mb-3">
                <label class="form-label d-block">Situação</label>
                <div class="form-check form-switch mt-2">
                    <?= $this->Form->checkbox('active', [
                        'class' => 'form-check-input',
                        'id' => 'active',
                        'checked' => $promotionalCode->isNew() ? true : (bool)$promotionalCode->active,
                    ]) ?>
                    <label class="form-check-label" for="active">Ativo</label>
                </div>
                <div class="form-text">Desativar impede novos usos imediatamente, sem mexer nas datas.</div>
            </div>
        </div>
    </div>

    <div class="text-end mt-3">
        <?= $this->Form->button('Salvar', ['class' => 'btn btn-success']) ?>
        <?= $this->Html->link('Cancelar', ['action' => 'view', $partner->id], ['class' => 'btn btn-secondary ms-2']) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
