<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Broker $broker
 */
?>
<div class="container mt-4">
    <?= $this->Form->create($broker, ['class' => 'card p-4 shadow-sm']) ?>

    <div class="row">
        <div class="col-md-5">
            <div class="mb-3">
                <?= $this->Form->control('name', [
                    'label' => 'Nome do corretor*',
                    'class' => 'form-control',
                    'placeholder' => 'Ex: João da Silva',
                    'maxlength' => 120,
                    'required' => true,
                ]) ?>
            </div>
        </div>

        <div class="col-md-3">
            <div class="mb-3">
                <?= $this->Form->control('code', [
                    'label' => 'Código*',
                    'class' => 'form-control text-uppercase',
                    'placeholder' => 'Ex: JOAO2026',
                    'maxlength' => 30,
                    'required' => true,
                ]) ?>
                <div class="form-text">Sempre gravado em maiúsculas. Letras, números e hífen.</div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="mb-3">
                <?= $this->Form->control('susep_code', [
                    'label' => 'Código SUSEP',
                    'class' => 'form-control',
                    'maxlength' => 30,
                ]) ?>
                <div class="form-text">Opcional, informativo.</div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="mb-3">
                <label class="form-label d-block">Situação</label>
                <div class="form-check form-switch mt-2">
                    <?= $this->Form->checkbox('active', [
                        'class' => 'form-check-input',
                        'id' => 'active',
                        'checked' => $broker->isNew() ? true : (bool)$broker->active,
                    ]) ?>
                    <label class="form-check-label" for="active">Ativo</label>
                </div>
                <div class="form-text">Desativar não afeta adesões já em andamento com este corretor.</div>
            </div>
        </div>
    </div>

    <div class="text-end mt-3">
        <?= $this->Form->button('Salvar', ['class' => 'btn btn-success']) ?>
        <?= $this->Html->link('Cancelar', ['action' => 'index'], ['class' => 'btn btn-secondary ms-2']) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
