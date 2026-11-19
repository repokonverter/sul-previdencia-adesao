<?php
/**
 * O link do e-mail: redefinição ("esqueci a senha") ou convite de usuário
 * novo. Link inválido, usado ou expirado cai na mesma mensagem.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\PasswordReset|null $reset
 * @var \App\Model\Entity\User|null $user
 * @var list<string> $personalWords
 */
$title = $reset?->isInvite() ? 'Defina sua senha' : 'Redefinir senha';
$this->assign('title', $title);
?>
<?php if ($reset === null): ?>
    <h2>Link inválido ou expirado</h2>

    <p class="text-muted small">
        Este link já foi usado, foi substituído por um mais recente ou passou do prazo.
        Peça um novo para continuar.
    </p>

    <?= $this->Html->link('Pedir um novo link', ['action' => 'forgotPassword'], ['class' => 'btn btn-auth']) ?>

    <div class="text-center mt-3">
        <?= $this->Html->link('Voltar para o login', ['action' => 'login']) ?>
    </div>
<?php else: ?>
    <h2><?= h($title) ?></h2>

    <p class="text-muted small">
        Olá, <?= h($user->name) ?>. Crie a senha do seu acesso (<?= h($user->email) ?>).
    </p>

    <?= $this->Form->create($user) ?>
        <?php // Para o gerenciador de senhas do navegador saber de qual conta é. ?>
        <input type="email" name="username" value="<?= h($user->email) ?>" autocomplete="username" hidden readonly>

        <?= $this->element('password_fields', ['personalWords' => $personalWords]) ?>

        <?= $this->Form->button(__('Salvar senha'), ['class' => 'btn btn-auth']) ?>
    <?= $this->Form->end() ?>
<?php endif; ?>
