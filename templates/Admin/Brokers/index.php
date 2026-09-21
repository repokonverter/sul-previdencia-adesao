<?php

/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Broker> $brokers
 */

$this->assign('title', 'Corretores');

$baseUrl = rtrim($this->Url->build('/', ['fullBase' => true]), '/');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">Corretores</h2>

    <?= $this->Html->link(
        '<i class="bi bi-person-plus me-1"></i> Novo Corretor',
        ['action' => 'add'],
        ['escape' => false, 'class' => 'btn btn-primary']
    ) ?>
</div>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-body">
        <?= $this->Form->create(null, ['type' => 'get']) ?>
        <div class="row g-3">
            <div class="col-md-9">
                <?= $this->Form->control('q', [
                    'label' => 'Busca',
                    'class' => 'form-control',
                    'placeholder' => 'Buscar por nome ou código do corretor',
                    'value' => $this->request->getQuery('q'),
                ]) ?>
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <?= $this->Form->button('<i class="bi bi-search"></i> Filtrar', [
                    'escapeTitle' => false,
                    'class' => 'btn btn-outline-primary w-100'
                ]) ?>
            </div>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><?= $this->Paginator->sort('name', 'Corretor') ?></th>
                    <th>Código</th>
                    <th>Código SUSEP</th>
                    <th class="text-center">Situação</th>
                    <th class="text-center">Adesões</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($brokers as $broker): ?>
                    <tr>
                        <td class="fw-semibold"><?= h($broker->name) ?></td>
                        <td><code><?= h($broker->code) ?></code></td>
                        <td><?= h($broker->susep_code ?: '—') ?></td>
                        <td class="text-center">
                            <?php if ($broker->active): ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= h($broker->adhesions_count) ?></td>
                        <td class="text-end text-nowrap">
                            <button
                                type="button"
                                class="btn btn-sm btn-light me-1 js-copy-link"
                                data-link="<?= h($baseUrl . '/?broker=' . $broker->code) ?>"
                                title="Copiar link de divulgação"
                            >
                                <i class="bi bi-link-45deg"></i>
                            </button>

                            <?= $this->Html->link(
                                '<i class="bi bi-pencil-square"></i>',
                                ['action' => 'edit', $broker->id],
                                ['escape' => false, 'class' => 'btn btn-sm btn-secondary me-1', 'title' => 'Editar']
                            ) ?>

                            <?= $this->Form->postLink(
                                $broker->active ? '<i class="bi bi-toggle-on"></i>' : '<i class="bi bi-toggle-off"></i>',
                                ['action' => 'toggle', $broker->id],
                                [
                                    'escape' => false,
                                    'class' => 'btn btn-sm btn-outline-secondary me-1',
                                    'title' => $broker->active ? 'Desativar' : 'Ativar',
                                ]
                            ) ?>

                            <?= $this->Form->postLink(
                                '<i class="bi bi-trash"></i>',
                                ['action' => 'delete', $broker->id],
                                [
                                    'escape' => false,
                                    'class' => 'btn btn-sm btn-danger',
                                    'confirm' => 'Tem certeza que deseja remover este corretor?',
                                    'title' => 'Remover',
                                ]
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (count($brokers) === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Nenhum corretor cadastrado ainda.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            <?= $this->Paginator->counter('Página {{page}} de {{pages}} ({{count}} registros)') ?>
        </div>
        <div>
            <?= $this->Paginator->links([
                'first' => true,
                'prev' => true,
                'next' => true,
                'last' => true,
                'class' => ['pagination', 'mb-0'],
            ]) ?>
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
