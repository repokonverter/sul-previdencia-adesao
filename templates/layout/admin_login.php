<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login Admin</title>
    <?= $this->Html->css('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css') ?>
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-dark">
    <div class="card p-4 shadow" style="width: 350px;">
        <h4 class="text-center mb-3">Login Admin</h4>
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </div>
</body>
</html>
