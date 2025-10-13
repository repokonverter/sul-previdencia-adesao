<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Sul Previdência</title>
    <?= $this->Html->meta('viewport', 'width=device-width, initial-scale=1'); ?>
    <?= $this->Html->css([
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
    ]) ?>
    <style>
        body {
            background-color: #d7dbe2; /* fundo cinza suave */
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .login-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            width: 360px;
            padding: 40px 35px;
            text-align: center;
        }

        .login-card img {
            width: 120px;
            margin-bottom: 20px;
        }

        .login-card h2 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
        }

        .form-control {
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .btn-login {
            background-color: #f37c20;
            border: none;
            width: 100%;
            padding: 10px;
            color: #fff;
            font-weight: 600;
            border-radius: 6px;
            transition: background-color 0.2s;
        }

        .btn-login:hover {
            background-color: #d96916;
        }

        small {
            color: #888;
            display: block;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <?= $this->Html->image('logo_sul_transparente.png', ['alt' => 'Sul Previdência', 'class' => 'mb-3']); ?>

    <h2>Acesso ao Sistema</h2>

    <?= $this->Form->create() ?>
        <?= $this->Form->control('email', [
            'label' => 'Usuário',
            'class' => 'form-control',
            'placeholder' => 'Digite seu e-mail',
            'required' => true
        ]) ?>

        <?= $this->Form->control('password', [
            'label' => 'Senha',
            'class' => 'form-control',
            'placeholder' => 'Digite sua senha',
            'required' => true
        ]) ?>

        <?= $this->Form->button(__('Entrar'), ['class' => 'btn btn-login']) ?>
    <?= $this->Form->end() ?>

    <small>© <?= date('Y') ?> Sul Previdência</small>
</div>

</body>
</html>
