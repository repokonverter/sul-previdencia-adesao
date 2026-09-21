<?php

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Partner $partner
 * @var iterable<\App\Model\Entity\PromotionalCode> $promotionalCodes
 */

use Cake\I18n\Date;

$this->assign('title', 'Parceiro: ' . $partner->name);

$baseUrl = rtrim($this->Url->build('/', ['fullBase' => true]), '/');

function getCodeValidityCell(\App\Model\Entity\PromotionalCode $code): string
{
    if (!$code->valid_from && !$code->valid_until) {
        return '<span class="text-muted">Indeterminada</span>';
    }

    $from = $code->valid_from ? $code->valid_from->i18nFormat('dd/MM/yyyy') : '—';
    $until = $code->valid_until ? $code->valid_until->i18nFormat('dd/MM/yyyy') : '—';

    return h($from) . ' até ' . h($until);
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary d-flex align-items-center gap-2">
        <span class="d-inline-block rounded-circle" style="width:18px;height:18px;background:<?= h($partner->color) ?>"></span>
        <?= h($partner->name) ?>
        <?php if (!$partner->active): ?>
            <span class="badge bg-secondary">Inativo</span>
        <?php endif; ?>
    </h2>

    <div>
        <?= $this->Html->link(
            '<i class="bi bi-pencil-square"></i> Editar parceiro',
            ['action' => 'edit', $partner->id],
            ['escape' => false, 'class' => 'btn btn-secondary me-1']
        ) ?>
        <?= $this->Html->link(
            '<i class="bi bi-arrow-left"></i> Voltar',
            ['action' => 'index'],
            ['escape' => false, 'class' => 'btn btn-outline-secondary']
        ) ?>
    </div>
</div>

<?php if ($partner->has_logo): ?>
    <div class="mb-4">
        <?= $this->Html->image(
            ['controller' => 'Partners', 'action' => 'logo', $partner->id, 'prefix' => false],
            ['alt' => $partner->name, 'style' => 'max-height:60px;']
        ) ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h5 class="card-title d-flex justify-content-between align-items-center">
            Códigos promocionais
            <?= $this->Html->link(
                '<i class="bi bi-plus-circle me-1"></i> Novo código',
                ['action' => 'addCode', $partner->id],
                ['escape' => false, 'class' => 'btn btn-sm btn-primary']
            ) ?>
        </h5>

        <div class="table-responsive mt-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Descrição</th>
                        <th>Validade</th>
                        <th class="text-center">Situação</th>
                        <th class="text-center">Iniciadas</th>
                        <th class="text-center">Concluídas</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promotionalCodes as $code): ?>
                        <tr>
                            <td><code><?= h($code->code) ?></code></td>
                            <td><?= h($code->description ?: '—') ?></td>
                            <td><?= getCodeValidityCell($code) ?></td>
                            <td class="text-center">
                                <?php if ($code->active): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= h($code->adhesions_started) ?></td>
                            <td class="text-center"><?= h($code->adhesions_completed) ?></td>
                            <td class="text-end text-nowrap">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-light me-1 js-copy-link"
                                    data-link="<?= h($baseUrl . '/?promo=' . $code->code) ?>"
                                    title="Copiar link de divulgação"
                                >
                                    <i class="bi bi-link-45deg"></i>
                                </button>

                                <?= $this->Html->link(
                                    '<i class="bi bi-pencil-square"></i>',
                                    ['action' => 'editCode', $code->id],
                                    ['escape' => false, 'class' => 'btn btn-sm btn-secondary me-1', 'title' => 'Editar']
                                ) ?>

                                <?= $this->Form->postLink(
                                    $code->active ? '<i class="bi bi-toggle-on"></i>' : '<i class="bi bi-toggle-off"></i>',
                                    ['action' => 'toggleCode', $code->id],
                                    [
                                        'escape' => false,
                                        'class' => 'btn btn-sm btn-outline-secondary me-1',
                                        'title' => $code->active ? 'Desativar' : 'Ativar',
                                    ]
                                ) ?>

                                <?= $this->Form->postLink(
                                    '<i class="bi bi-trash"></i>',
                                    ['action' => 'deleteCode', $code->id],
                                    [
                                        'escape' => false,
                                        'class' => 'btn btn-sm btn-danger',
                                        'confirm' => 'Tem certeza que deseja remover este código?',
                                        'title' => 'Remover',
                                    ]
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (count($promotionalCodes) === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nenhum código cadastrado ainda.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.js-copy-link').forEach(function (button) {
        button.addEventListener('click', function () {
            const link = button.getAttribute('data-link');
            const icon = button.querySelector('i');

            navigator.clipboard.writeText(link).then(function () {
                icon.className = 'bi bi-check-lg';
                setTimeout(function () {
                    icon.className = 'bi bi-link-45deg';
                }, 1500);
            });
        });
    });
</script>
