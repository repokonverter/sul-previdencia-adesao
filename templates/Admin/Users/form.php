<div class="container mt-4">
    <?= $this->Form->create($user, ['class' => 'card p-4 shadow-sm']) ?>

    <div class="mb-3">
        <?= $this->Form->control('name', ['label' => 'Nome', 'class' => 'form-control']) ?>
    </div>

    <div class="mb-3">
        <?= $this->Form->control('email', ['label' => 'E-mail', 'class' => 'form-control']) ?>
    </div>

    <div class="mb-3">
        <?= $this->Form->control('password', ['label' => 'Senha', 'class' => 'form-control', 'type' => 'password']) ?>
    </div>

    <div class="mb-3">
        <?= $this->Form->control('role', [
            'label' => 'Perfil',
            'class' => 'form-select',
            'options' => ['admin' => 'Administrador', 'user' => 'Usuário']
        ]) ?>
    </div>

    <div class="text-end">
        <?= $this->Form->button('Salvar', ['class' => 'btn btn-success']) ?>
        <?= $this->Html->link('Cancelar', ['action' => 'index'], ['class' => 'btn btn-secondary ms-2']) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
