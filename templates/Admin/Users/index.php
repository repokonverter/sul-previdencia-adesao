<?php
$this->assign('title', 'Usuários');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">Usuários</h2>

    <?= $this->Html->link(
        '<i class="bi bi-plus-circle me-1"></i> Novo Usuário',
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
                    'placeholder' => 'Buscar por nome ou e-mail',
                    'value' => $this->request->getQuery('q')
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
                    <th><?= $this->Paginator->sort('id', 'ID') ?></th>
                    <th><?= $this->Paginator->sort('name', 'Nome') ?></th>
                    <th><?= $this->Paginator->sort('email', 'E-mail') ?></th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= h($user->id) ?></td>
                        <td class="fw-semibold"><?= h($user->name) ?></td>
                        <td><?= h($user->email) ?></td>
                        <td class="text-end">
                            <?= $this->Html->link(
                                '<i class="bi bi-eye"></i>',
                                ['action' => 'view', $user->id],
                                ['escape' => false, 'class' => 'btn btn-sm btn-light me-1', 'title' => 'Visualizar']
                            ) ?>

                            <?= $this->Html->link(
                                '<i class="bi bi-pencil-square"></i>',
                                ['action' => 'edit', $user->id],
                                ['escape' => false, 'class' => 'btn btn-sm btn-secondary me-1', 'title' => 'Editar']
                            ) ?>

                            <?= $this->Form->postLink(
                                '<i class="bi bi-trash"></i>',
                                ['action' => 'delete', $user->id],
                                [
                                    'escape' => false,
                                    'class' => 'btn btn-sm btn-danger me-1',
                                    'confirm' => __('Tem certeza que deseja remover este usuário?'),
                                    'title' => 'Remover'
                                ]
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer text-center bg-white border-0">
        <?= $this->Paginator->numbers([
            'prev' => '‹ Anterior',
            'next' => 'Próxima ›',
            'first' => '« Primeira',
            'last' => 'Última »'
        ]) ?>
    </div>
</div>