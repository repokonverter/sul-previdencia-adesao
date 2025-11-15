<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">Clientes / Adesões</h2>

    <?= $this->Html->link(
        '<i class="bi bi-plus-circle me-1"></i> Nova Adesão',
        ['action' => 'add'],
        ['escape' => false, 'class' => 'btn btn-primary']
    ) ?>
</div>

<!-- Filtro de Busca -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body">
        <?= $this->Form->create(null, ['type' => 'get']) ?>
        <div class="row g-3">
            <div class="col-md-4">
                <?= $this->Form->control('name', [
                    'label' => 'Nome',
                    'class' => 'form-control',
                    'placeholder' => 'Buscar por nome'
                ]) ?>
            </div>

            <div class="col-md-4">
                <?= $this->Form->control('cpf', [
                    'label' => 'CPF',
                    'class' => 'form-control',
                    'placeholder' => 'Buscar CPF'
                ]) ?>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <?= $this->Form->button('<i class="bi bi-search"></i> Filtrar', [
                    'escapeTitle' => false,
                    'class' => 'btn btn-outline-primary w-100'
                ]) ?>
            </div>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>

<!-- Lista Moderna -->
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cliente</th>
                    <th>CPF</th>
                    <th>E-mail</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($adhesions as $adhesion): ?>
                <tr>
                    <td class="fw-semibold">
                        <?= h($adhesion->adhesion_personal_data->name ?? '—') ?>
                    </td>
                    <td><?= h($adhesion->adhesion_personal_data->cpf ?? '—') ?></td>
                    <td><?= h($adhesion->email ?? '—') ?></td>
                    
                    <td class="text-end">
                        <?= $this->Html->link(
                            '<i class="bi bi-eye"></i>',
                            ['action' => 'view', $adhesion->id],
                            ['escape' => false, 'class' => 'btn btn-sm btn-light me-1']
                        ) ?>

                        <?= $this->Html->link(
                            '<i class="bi bi-pencil-square"></i>',
                            ['action' => 'edit', $adhesion->id],
                            ['escape' => false, 'class' => 'btn btn-sm btn-secondary me-1']
                        ) ?>

                        <?= $this->Form->postLink(
                            '<i class="bi bi-trash"></i>',
                            ['action' => 'delete', $adhesion->id],
                            [
                                'escape' => false,
                                'class' => 'btn btn-sm btn-danger',
                                'confirm' => 'Tem certeza que deseja remover este cadastro?'
                            ]
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer text-center">
        <?= $this->Paginator->numbers() ?>
    </div>
</div>
