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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1 0 auto;
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
            flex-shrink: 0;
            margin-top: 50px;
        }
    </style>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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
                    <a class="nav-link" href="<?= $this->Url->build('/admin/partners') ?>">Parceiros</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $this->Url->build('/admin/associations') ?>">Vínculos associativos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $this->Url->build('/admin/plan-parameters/edit') ?>">Parâmetros do Plano</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $this->Url->build('/admin/clicksign-webhooks') ?>">Webhook Clicksign</a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" href="<?= $this->Url->build('/admin/integration-logs') ?>">Logs de Integração</a>
                </li> -->
                <!-- <li class="nav-item">
                    <a class="nav-link" href="<?= $this->Url->build('/admin/pix-webhooks') ?>">Webhooks Pix</a>
                </li> -->
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

    <footer class="footer border-top">

        Painel Administrativo &copy; <?= date('Y') ?> - Sul Previdência
    </footer>

    <?= $this->Html->script('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js') ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js" integrity="sha512-pHVGpX7F/27yZ0ISY+VVjyULApbDlD0/X0rgGbTqCE7WFW5MezNTWG/dnhtbBuICzsd0WQPgpE4REBLv+UqChw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        var SPMaskBehavior = function(val) {
                return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
            },
            spOptions = {
                onKeyPress: function(val, e, field, options) {
                    field.mask(SPMaskBehavior.apply({}, arguments), options);
                }
            };

        $(document).ready(function() {
            // Restrito a elementos <input>: o FormHelper do CakePHP envolve
            // todo controle 'type' => 'date' num <div class="... date">
            // (a classe do wrapper é o próprio tipo do campo). Um seletor
            // '.date' genérico pega esse <div> também, e o jquery.mask, ao
            // tentar aplicar a máscara num elemento que não é input/textarea,
            // apaga o conteúdo interno — o label e o <input type="date"> que
            // deveriam estar ali somem do DOM. Some com nenhum erro no
            // servidor (o HTML gerado está correto) nem no console.
            $('input.date').mask('00/00/0000');
            $('input.time').mask('00:00:00');
            $('input.date_time').mask('00/00/0000 00:00:00');
            $('input.cep').mask('00000-000');
            $('input.cpf').mask('000.000.000-00', {
                reverse: true
            });
            $('input.cnpj').mask('00.000.000/0000-00', {
                reverse: true
            });
            $('input.money').mask('000.000.000.000.000,00', {
                reverse: true
            });
            $('input.money2').mask("#.##0,00", {
                reverse: true
            });
            $('input.percent').mask('##0,00%', {
                reverse: true
            });
            $('input.phone').mask(SPMaskBehavior, spOptions);
        });
    </script>
</body>

</html>