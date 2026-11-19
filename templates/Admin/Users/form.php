<div class="container mt-4">
    <?= $this->Form->create($user, ['class' => 'card p-4 shadow-sm']) ?>

    <div class="mb-3">
        <?= $this->Form->control('name', ['label' => 'Nome', 'class' => 'form-control']) ?>
    </div>

    <div class="mb-3">
        <?= $this->Form->control('email', ['label' => 'E-mail', 'class' => 'form-control']) ?>
    </div>


    <div class="mb-3">
        <?= $this->Form->control('role', [
            'label' => 'Perfil',
            'class' => 'form-select',
            'options' => \App\Model\Entity\User::ROLES
        ]) ?>
    </div>

    <?php if ($user->isNew()): ?>
        <div class="alert alert-info small mb-3">
            <i class="bi bi-envelope me-1"></i>
            O usuário receberá por e-mail um link para definir a própria senha
            (válido por 48 horas).
        </div>
    <?php else: ?>
        <p class="text-muted small mb-3">
            A senha não é alterada aqui: use "Enviar link para redefinir senha" na tela do usuário.
        </p>
    <?php endif; ?>

    <div class="text-end">
        <?= $this->Form->button('Salvar', ['class' => 'btn btn-success']) ?>
        <?= $this->Html->link('Cancelar', ['action' => 'index'], ['class' => 'btn btn-secondary ms-2']) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
