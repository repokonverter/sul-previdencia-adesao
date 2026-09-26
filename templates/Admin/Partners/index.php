<?php

/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Partner> $partners
 * @var string $entityLabelPlural
 * @var string $entityLabelSingular
 */

$this->assign('title', $entityLabelPlural);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary"><?= h($entityLabelPlural) ?></h2>

    <?= $this->Html->link(
        '<i class="bi bi-plus-circle me-1"></i> Novo(a) ' . h($entityLabelSingular),
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
                    'placeholder' => 'Buscar por nome do parceiro ou por texto de código',
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
                    <th></th>
                    <th><?= $this->Paginator->sort('name', 'Parceiro') ?></th>
                    <th class="text-center">Situação</th>
                    <th class="text-center">Códigos ativos</th>
                    <th class="text-center">Adesões iniciadas</th>
                    <th class="text-center">Adesões concluídas</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($partners as $partner): ?>
                    <tr>
                        <td>
                            <span class="d-inline-block rounded-circle" style="width:16px;height:16px;background:<?= h($partner->color) ?>"></span>
                        </td>
                        <td class="fw-semibold">
                            <?= $this->Html->link(h($partner->name), ['action' => 'view', $partner->id]) ?>
                        </td>
                        <td class="text-center">
                            <?php if ($partner->active): ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= h($partner->active_codes) ?></td>
                        <td class="text-center"><?= h($partner->adhesions_started) ?></td>
                        <td class="text-center"><?= h($partner->adhesions_completed) ?></td>
                        <td class="text-end">
                            <?= $this->Html->link(
                                '<i class="bi bi-eye"></i>',
                                ['action' => 'view', $partner->id],
                                ['escape' => false, 'class' => 'btn btn-sm btn-light me-1', 'title' => 'Visualizar']
                            ) ?>

                            <?= $this->Html->link(
                                '<i class="bi bi-pencil-square"></i>',
                                ['action' => 'edit', $partner->id],
                                ['escape' => false, 'class' => 'btn btn-sm btn-secondary me-1', 'title' => 'Editar']
                            ) ?>

                            <?= $this->Form->postLink(
                                $partner->active ? '<i class="bi bi-toggle-on"></i>' : '<i class="bi bi-toggle-off"></i>',
                                ['action' => 'toggle', $partner->id],
                                [
                                    'escape' => false,
                                    'class' => 'btn btn-sm btn-outline-secondary me-1',
                                    'title' => $partner->active ? 'Desativar' : 'Ativar',
                                ]
                            ) ?>

                            <?= $this->Form->postLink(
                                '<i class="bi bi-trash"></i>',
                                ['action' => 'delete', $partner->id],
                                [
                                    'escape' => false,
                                    'class' => 'btn btn-sm btn-danger me-1',
                                    'confirm' => 'Tem certeza que deseja remover ' . ($isAssociationScope ? 'este vínculo' : 'este parceiro') . ' e todos os seus códigos?',
                                    'title' => 'Remover',
                                ]
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
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
