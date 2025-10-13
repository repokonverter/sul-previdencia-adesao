<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->fetch('title') ?> - Sul Previdência</title>

    <?= $this->Html->css(['bootstrap.min', 'login']) ?> 
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body style="background-color: #d9dde3;">
    <main class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>
    
</body>
</html>
