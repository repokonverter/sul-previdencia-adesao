<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <title><?= $this->fetch('title') ?: 'Sul Previdência' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $this->Html->meta('csrfToken', $this->request->getAttribute('csrfToken')) ?>
    <meta name="theme-color" content="#F47C20">

    <!-- CSS principal -->
    <?= $this->Html->css(['application']) ?>

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <!-- Áreas para CSS e scripts adicionais -->
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>

<body>
    <?= $this->fetch('content1') ?>
</body>

</html>
