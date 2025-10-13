<?php
$this->assign('title', 'Usuários');
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Usuários</h2>
        <?= $this->Html->link('Novo Usuário', ['action' => 'add'], ['class' => 'btn btn-primary']) ?>
    </div>

    <!-- Formulário de Busca -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <?= $this->Form->create(null, ['type' => 'get', 'class' => 'row g-2']) ?>
                <div class="col-md-4">
                    <?= $this->Form->control('q', [
                        'label' => false,
                        'placeholder' => 'Buscar por nome ou e-mail...',
                        'value' => $this->request->getQuery('q'),
                        'class' => 'form-control'
                    ]) ?>
                </div>
                <div class="col-md-2">
                    <?= $this->Form->button('Buscar', ['class' => 'btn btn-secondary w-100']) ?>
                </div>
                <div class="col-md-2">
                    <?= $this->Html->link('Limpar', ['action' => 'index'], ['class' => 'btn btn-light w-100']) ?>
                </div>
            <?= $this->Form->end() ?>
        </div>
    </div>

    <!-- Tabela -->
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th><?= $this->Paginator->sort('id', 'ID') ?></th>
                    <th><?= $this->Paginator->sort('name', 'Nome') ?></th>
                    <th><?= $this->Paginator->sort('email', 'E-mail') ?></th>
                    <th><?= $this->Paginator->sort('role', 'Perfil') ?></th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= h($user->id) ?></td>
                        <td><?= h($user->name) ?></td>
                        <td><?= h($user->email) ?></td>
                        <td><?= ucfirst(h($user->role)) ?></td>
                        <td class="text-end">
                            <?= $this->Html->link('<i class="bi bi-eye"></i>', ['action' => 'view', $user->id], [
                                'escape' => false, 'class' => 'btn btn-sm btn-outline-primary me-1', 'title' => 'Ver'
                            ]) ?>
                            <?= $this->Html->link('<i class="bi bi-pencil"></i>', ['action' => 'edit', $user->id], [
                                'escape' => false, 'class' => 'btn btn-sm btn-outline-warning me-1', 'title' => 'Editar'
                            ]) ?>
                            <?= $this->Form->postLink('<i class="bi bi-trash"></i>', ['action' => 'delete', $user->id], [
                                'escape' => false,
                                'confirm' => __('Tem certeza que deseja excluir {0}?', $user->name),
                                'class' => 'btn btn-sm btn-outline-danger',
                                'title' => 'Excluir'
                            ]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            <?= $this->Paginator->counter(__('Página {{page}} de {{pages}}, exibindo {{current}} registro(s) de {{count}} no total')) ?>
        </div>
        <ul class="pagination mb-0">
            <?= $this->Paginator->first('« Primeira') ?>
            <?= $this->Paginator->prev('‹ Anterior') ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next('Próxima ›') ?>
            <?= $this->Paginator->last('Última »') ?>
        </ul>
    </div>
</div>
