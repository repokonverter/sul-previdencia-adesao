<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AdhesionDeletion> $deletions
 */
$this->assign('title', 'Adesões Excluídas');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">
        <i class="bi bi-trash"></i> Adesões Excluídas
    </h2>

    <?= $this->Html->link(
        '<i class="bi bi-arrow-left"></i> Voltar',
        ['action' => 'index'],
        ['escape' => false, 'class' => 'btn btn-outline-secondary']
    ) ?>
</div>

<div class="card p-4 shadow-sm">
    <p class="text-muted small">
        O registro sobrevive à adesão de propósito, e guarda só o necessário para
        identificá-la — nome e CPF do proponente. Os dados da adesão em si foram
        apagados junto com ela.
    </p>

    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 170px;">Quando</th>
                    <th style="width: 90px;">Adesão</th>
                    <th>Proponente</th>
                    <th style="width: 140px;">CPF</th>
                    <th style="width: 200px;">Excluída por</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deletions as $deletion): ?>
                    <tr>
                        <td><?= $deletion->created->format('d/m/Y H:i:s') ?></td>
                        <td><code>#<?= h($deletion->adhesion_initial_data_id) ?></code></td>
                        <td><?= h($deletion->adhesion_name) ?: '<span class="text-muted">—</span>' ?></td>
                        <td><?= h($deletion->adhesion_cpf) ?: '<span class="text-muted">—</span>' ?></td>
                        <td><?= h($deletion->authorLabel()) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (count($deletions->toArray()) === 0): ?>
                    <tr>
                        <td colspan="5" class="text-muted text-center py-4">
                            Nenhuma adesão foi excluída.
                        </td>
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
