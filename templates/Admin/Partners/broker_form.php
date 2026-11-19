<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Partner $partner
 * @var \App\Model\Entity\Broker $broker
 */
?>
<div class="container mt-4">
    <?= $this->Form->create($broker, ['class' => 'card p-4 shadow-sm']) ?>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <?= $this->Form->control('name', [
                    'label' => 'Nome completo*',
                    'class' => 'form-control',
                    'maxlength' => 150,
                    'required' => true,
                ]) ?>
            </div>
        </div>

        <div class="col-md-3">
            <div class="mb-3">
                <?= $this->Form->control('cpf', [
                    'label' => 'CPF (opcional)',
                    'class' => 'form-control cpf',
                    'placeholder' => '000.000.000-00',
                    'value' => $broker->formatted_cpf ?? $broker->cpf,
                    'maxlength' => 14,
                ]) ?>
            </div>
        </div>

        <div class="col-md-3">
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
                <div class="form-text">Desativado, some do formulário de adesão e o link dele deixa de valer.</div>
            </div>
        </div>
    </div>

    <div class="text-end mt-3">
        <?= $this->Form->button('Salvar', ['class' => 'btn btn-success']) ?>
        <?= $this->Html->link('Cancelar', ['action' => 'view', $partner->id], ['class' => 'btn btn-secondary ms-2']) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
