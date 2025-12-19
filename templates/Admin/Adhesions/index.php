<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AdhesionInitialData> $adhesions
 */

function getAdhesionStage($adhesion) {
    if (!empty($adhesion->adhesion_other_information))
        return 'Outras Informações (Finalizado)';

    if (!empty($adhesion->adhesion_proponent_statement))
        return 'Declarações do Proponente';

    if (!empty($adhesion->adhesion_pension_schemes))
        return 'Beneficiários / Pensão';

    if (!empty($adhesion->adhesion_payment_detail))
        return 'Dados de Pagamento';

    if (!empty($adhesion->adhesion_plan))
        return 'Plano';

    if (!empty($adhesion->adhesion_documents))
        return 'Documentos';

    if (!empty($adhesion->adhesion_dependents))
        return 'Dependentes';

    if (!empty($adhesion->adhesion_address))
        return 'Endereço';

    if (!empty($adhesion->adhesion_personal_data))
        return 'Dados Pessoais';

    return 'Dados Iniciais';
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">Adesões</h2>

    <?= $this->Html->link(
        '<i class="bi bi-plus-circle me-1"></i> Nova Adesão',
        ['action' => 'add'],
        ['escape' => false, 'class' => 'btn btn-primary']
    ) ?>
</div>

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

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cliente</th>
                    <th>CPF</th>
                    <th>Celular</th>
                    <th>E-mail</th>
                    <th>Etapa</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($adhesions as $adhesion): ?>
                <tr>
                    <td class="fw-semibold"><?= h($adhesion->name ?? $adhesion->adhesion_personal_data->name) ?></td>
                    <td><?= h($adhesion->adhesion_personal_data->cpf ?? '—') ?></td>
                    <td><?= h($adhesion->phone ?? '—') ?></td>
                    <td><?= h($adhesion->email ?? '—') ?></td>
                    <td><?= h(getAdhesionStage($adhesion)) ?></td>
                    <td class="text-end">
                        <?php
                        if ($adhesion->adhesion_payment_detail) {
                            echo $this->Html->link(
                                '<i class="bi bi-file-pdf"></i>',
                                ['action' => 'generatePdf', $adhesion->id],
                                ['escape' => false, 'class' => 'btn btn-sm btn-light me-1', 'title' => 'Gerar PDF da proposta']
                            );
                        }
                        ?>

                        <?= $this->Html->link(
                            '<i class="bi bi-eye"></i>',
                            ['action' => 'view', $adhesion->id],
                            ['escape' => false, 'class' => 'btn btn-sm btn-light me-1', 'title' => 'Visualizar']
                        ) ?>

                        <?= $this->Html->link(
                            '<i class="bi bi-pencil-square"></i>',
                            ['action' => 'edit', $adhesion->id],
                            ['escape' => false, 'class' => 'btn btn-sm btn-secondary me-1', 'title' => 'Editar']
                        ) ?>

                        <?= $this->Form->postLink(
                            '<i class="bi bi-trash"></i>',
                            ['action' => 'delete', $adhesion->id],
                            [
                                'escape' => false,
                                'class' => 'btn btn-sm btn-danger me-1',
                                'confirm' => 'Tem certeza que deseja remover este cadastro?',
                                'title' => 'Remover'
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
