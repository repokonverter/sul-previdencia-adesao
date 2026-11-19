<?php
/**
 * @var \App\View\AppView $this
 */
$this->assign('title', 'Esqueci minha senha');
?>
<h2>Esqueci minha senha</h2>

<p class="text-muted small">
    Informe o e-mail do seu acesso. Se ele estiver cadastrado, enviaremos um link
    para você criar uma nova senha.
</p>

<?= $this->Form->create() ?>
    <?= $this->Form->control('email', [
        'label' => 'E-mail',
        'type' => 'email',
        'class' => 'form-control',
        'placeholder' => 'Digite seu e-mail',
        'autocomplete' => 'username',
        'required' => true,
    ]) ?>

    <?= $this->Form->button(__('Enviar link'), ['class' => 'btn btn-auth']) ?>
<?= $this->Form->end() ?>

<div class="text-center mt-3">
    <?= $this->Html->link('Voltar para o login', ['action' => 'login']) ?>
</div>
