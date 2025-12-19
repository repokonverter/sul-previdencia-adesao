<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?= $this->Html->charset() ?>
    <title>Sul Previdência | Painel Administrativo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= $this->Html->css([
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    ]) ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
            color: #333;
        }

        .navbar {
            background-color: #004b85;
        }

        .navbar-brand {
            color: #fff !important;
            font-weight: 600;
        }

        .navbar-nav .nav-link {
            color: #fff !important;
        }

        .btn-primary {
            background-color: #f37c20;
            border-color: #f37c20;
        }

        .btn-primary:hover {
            background-color: #d86a1b;
            border-color: #d86a1b;
        }

        footer {
            padding: 15px 0;
            background: #004b85;
            color: #fff;
            text-align: center;
            margin-top: 50px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
        <a href="<?= $this->Url->build('/admin') ?>" class="d-inline-flex bg-white rounded-4 p-1 me-2" style="min-width: 77px; min-height: 77px;">
            <?= $this->Html->image('logo_sul_transparente.png', ['alt' => 'Sul Previdência', 'height' => '69', 'width' => '69']); ?>
        </a>
    <ul class="navbar-nav ms-auto">
        <li class="nav-item">
            <a class="nav-link" href="<?= $this->Url->build('/admin/users') ?>">Usuários</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= $this->Url->build('/admin/adhesions') ?>">Adesões</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= $this->Url->build('/admin/users/logout') ?>">Sair</a>
        </li>
    </ul>
  </div>
</nav>

<main class="container mt-4">
    <?= $this->Flash->render() ?>
    <?= $this->fetch('content') ?>
</main>

<footer class="footer fixed-bottom border-top">

    Painel Administrativo &copy; <?= date('Y') ?> - Sul Previdência
</footer>

<?= $this->Html->script('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js') ?>
</body>
</html>
