<?php
/**
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Login');
?>
<h2>Acesso ao Sistema</h2>

<?= $this->Form->create() ?>
    <?= $this->Form->control('email', [
        'label' => 'Usuário',
        'type' => 'email',
        'class' => 'form-control',
        'placeholder' => 'Digite seu e-mail',
        'autocomplete' => 'username',
        'required' => true,
    ]) ?>

    <?= $this->Form->control('password', [
        'label' => 'Senha',
        'class' => 'form-control',
        'placeholder' => 'Digite sua senha',
        'autocomplete' => 'current-password',
        'required' => true,
    ]) ?>

    <?= $this->Form->button(__('Entrar'), ['class' => 'btn btn-auth']) ?>
<?= $this->Form->end() ?>

<div class="text-center mt-3">
    <?= $this->Html->link('Esqueci minha senha', ['action' => 'forgotPassword']) ?>
</div>
