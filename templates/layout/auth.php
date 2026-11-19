<?php
/**
 * Telas sem login do admin: entrar, esqueci a senha e definir/redefinir a
 * senha pelo link do e-mail.
 *
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($this->fetch('title') ?: 'Acesso ao Sistema') ?> - Sul Previdência</title>
    <?= $this->Html->css('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css') ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #d7dbe2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .auth-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 400px;
            padding: 40px 35px;
        }

        .auth-card .auth-logo {
            display: block;
            width: 120px;
            margin: 0 auto 20px;
        }

        .auth-card h2 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            text-align: center;
            margin-bottom: 24px;
        }

        .btn-auth {
            background-color: #f37c20;
            border: none;
            width: 100%;
            padding: 10px;
            color: #fff;
            font-weight: 600;
            border-radius: 6px;
        }

        .btn-auth:hover,
        .btn-auth:focus {
            background-color: #d96916;
            color: #fff;
        }

        .auth-card a {
            color: #d96916;
        }

        .auth-footer {
            color: #888;
            font-size: 0.85rem;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <?= $this->Html->image('logo_sul_transparente.png', ['alt' => 'Sul Previdência', 'class' => 'auth-logo']) ?>
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
        <div class="auth-footer">© <?= date('Y') ?> Sul Previdência</div>
    </div>
</body>
</html>
