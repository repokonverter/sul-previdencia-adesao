<?php

/**
 * @var \Cake\View\View $this
 */

use Cake\Core\Configure;

/**
 * Default `html` block.
 */
if (!$this->fetch('html')) {
    $this->start('html');
    if (Configure::check('App.language')) {
        printf('<html lang="%s">', Configure::read('App.language'));
    } else {
        echo '<html>';
    }
    $this->end();
}

/**
 * Default `title` block.
 */
if (!$this->fetch('title')) {
    $this->start('title');
    echo Configure::read('App.title');
    $this->end();
}

/**
 * Default `footer` block.
 */
if (!$this->fetch('tb_footer')) {
    $this->start('tb_footer');
    if (Configure::check('App.title')) {
        // printf('&copy;%s %s', date('Y'), Configure::read('App.title'));
    } else {
        // printf('&copy;%s', date('Y'));
    }
    $this->end();
}

/**
 * Default `body` block.
 */
$this->prepend(
    'tb_body_attrs',
    ' class="' . implode(' ', [h($this->request->getParam('controller')), h($this->request->getParam('action'))]) . '" '
);
if (!$this->fetch('tb_body_start')) {
    $this->start('tb_body_start');
    echo '<body' . $this->fetch('tb_body_attrs') . '>';
    $this->end();
}
/**
 * Default `flash` block.
 */
if (!$this->fetch('tb_flash')) {
    $this->start('tb_flash');
    echo $this->Flash->render();
    $this->end();
}
if (!$this->fetch('tb_body_end')) {
    $this->start('tb_body_end');
    echo '</body>';
    $this->end();
}

/**
 * Prepend `meta` block with `author` and `favicon`.
 */
if (Configure::check('App.author')) {
    $this->prepend(
        'meta',
        $this->Html->meta('author', null, ['name' => 'author', 'content' => Configure::read('App.author')])
    );
}

$this->prepend('meta', $this->Html->meta('favicon.ico', '/favicon.ico', ['type' => 'icon']));

/**
 * Prepend `css` block with Bootstrap stylesheets
 * Change to bootstrap.min to use the compressed version
 */
if (Configure::read('debug')) {
    $this->prepend('css', $this->Html->css(['BootstrapUI.bootstrap']));
} else {
    $this->prepend('css', $this->Html->css(['BootstrapUI.bootstrap.min']));
}
$this->prepend(
    'css',
    $this->Html->css(['BootstrapUI./font/bootstrap-icons', 'BootstrapUI./font/bootstrap-icon-sizes'])
);

/**
 * Prepend `script` block with Popper and Bootstrap scripts
 * Change bootstrap.min to use the compressed version
 */
if (Configure::read('debug')) {
    $this->prepend('script', $this->Html->script(['BootstrapUI.bootstrap.bundle']));
} else {
    $this->prepend('script', $this->Html->script(['BootstrapUI.bootstrap.bundle.min']));
}

?>
<!doctype html>
<?= $this->fetch('html') ?>

<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= h($this->fetch('title')) ?></title>
    <?= $this->fetch('meta') ?>

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <?= $this->fetch('css') ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js" integrity="sha512-pHVGpX7F/27yZ0ISY+VVjyULApbDlD0/X0rgGbTqCE7WFW5MezNTWG/dnhtbBuICzsd0WQPgpE4REBLv+UqChw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>

<?php
echo $this->fetch('tb_body_start');
echo $this->fetch('tb_flash');
echo $this->fetch('content');
echo $this->fetch('tb_footer');
echo $this->fetch('script');
echo $this->fetch('tb_body_end');
?>

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
        $('.date').mask('00/00/0000');
        $('.time').mask('00:00:00');
        $('.date_time').mask('00/00/0000 00:00:00');
        $('.cep').mask('00000-000');
        $('.cpf').mask('000.000.000-00', {
            reverse: true
        });
        $('.cnpj').mask('00.000.000/0000-00', {
            reverse: true
        });
        $('.money').mask('000.000.000.000.000,00', {
            reverse: true
        });
        $('.money2').mask("#.##0,00", {
            reverse: true
        });
        $('.percent').mask('##0,00%', {
            reverse: true
        });
        $('.phone').mask(SPMaskBehavior, spOptions);
    });
</script>

</html>
