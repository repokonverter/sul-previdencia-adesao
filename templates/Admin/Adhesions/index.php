<?php

/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AdhesionInitialData> $adhesions
 * @var iterable<\App\Model\Entity\Partner> $partners
 */

$getAdhesionStage = function ($adhesion)
{
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
};

$getPromotionalCodeCell = function ($adhesion)
{
    if (empty($adhesion->promotional_code))
        return '<span class="text-muted">&mdash;</span>';

    // O snapshot é a fonte da verdade; o parceiro vem do vínculo, quando ainda existe.
    $partner = $adhesion->promotional_code_entity->partner->name ?? null;

    $cell = '<code>' . h($adhesion->promotional_code) . '</code>';

    if ($partner)
        $cell .= '<br><span class="text-muted small">' . h($partner) . '</span>';

    return $cell;
};

$getBrokerCell = function ($adhesion)
{
    if (empty($adhesion->broker_code))
        return '<span class="text-muted">&mdash;</span>';

    return '<code>' . h($adhesion->broker_code) . '</code><br><span class="text-muted small">' . h($adhesion->broker_name) . '</span>';
};

$getPixStatusBadge = function ($adhesion)
{
    if (empty($adhesion->adhesion_payment_detail))
        return '—';

    $latestPix = $adhesion->pix_transactions[0] ?? null;

    if (!$latestPix)
        return '<span class="badge bg-secondary">Sem cobrança</span>';

    if ($latestPix->paid)
        return '<span class="badge bg-success">Pago</span>';

    return '<span class="badge bg-warning text-dark">Aguardando</span>';
};

$getSignatureStatusBadge = function ($adhesion)
{
    $latest = $adhesion->clicksign_datas[0] ?? null;

    if (!$latest)
        return '—';

    return match ($latest->status) {
        'signed' => '<span class="badge bg-success">Assinado</span>',
        'sent' => '<span class="badge bg-warning text-dark">Aguardando</span>',
        'canceled' => '<span class="badge bg-secondary">Cancelado</span>',
        'failed' => '<span class="badge bg-danger">Falhou</span>',
        default => '<span class="badge bg-secondary">' . h($latest->status) . '</span>',
    };
};
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">Adesões</h2>

    <div>
        <?= $this->Html->link(
            '<i class="bi bi-trash me-1"></i> Excluídas',
            ['action' => 'deletions'],
            ['escape' => false, 'class' => 'btn btn-outline-secondary me-2']
        ) ?>
        <?= $this->Html->link(
            '<i class="bi bi-plus-circle me-1"></i> Nova Adesão',
            ['action' => 'add'],
            ['escape' => false, 'class' => 'btn btn-primary']
        ) ?>
    </div>
</div>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-body">
        <?= $this->Form->create(null, ['type' => 'get']) ?>
        <div class="row g-3">
            <div class="col-md-2">
                <?= $this->Form->control('name', [
                    'label' => 'Nome',
                    'class' => 'form-control',
                    'placeholder' => 'Buscar por nome'
                ]) ?>
            </div>

            <div class="col-md-2">
                <?= $this->Form->control('cpf', [
                    'label' => 'CPF',
                    'class' => 'form-control',
                    'placeholder' => 'Buscar CPF'
                ]) ?>
            </div>

            <div class="col-md-2">
                <?= $this->Form->control('promotionalCode', [
                    'label' => 'Código promocional',
                    'class' => 'form-control text-uppercase',
                    'placeholder' => 'Buscar por código'
                ]) ?>
            </div>

            <div class="col-md-2">
                <?php
                $partnerOptions = [];
                foreach ($partners as $partner) {
                    $partnerOptions[$partner->id] = $partner->name;
                }
                ?>
                <?= $this->Form->control('partnerId', [
                    'label' => 'Parceiro',
                    'type' => 'select',
                    'options' => $partnerOptions,
                    'empty' => 'Todos os parceiros',
                    'class' => 'form-select',
                ]) ?>
            </div>

            <div class="col-md-2">
                <?= $this->Form->control('brokerCode', [
                    'label' => 'Corretor',
                    'class' => 'form-control text-uppercase',
                    'placeholder' => 'Buscar por código'
                ]) ?>
            </div>

            <div class="col-md-2 d-flex align-items-end">
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
                    <th>Celular</th>
                    <th>E-mail</th>
                    <th>Etapa</th>
                    <th>Código</th>
                    <th>Corretor</th>
                    <th>Pix</th>
                    <th>Assinatura</th>
                    <th>Data/hora</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php $resumeModals = ''; ?>
                <?php foreach ($adhesions as $adhesion): ?>
                    <tr>
                        <td class="fw-semibold"><?= h($adhesion->name ?? $adhesion->adhesion_personal_data->name) ?></td>
                        <td><?= h($adhesion->phone ?? '—') ?></td>
                        <td><?= h($adhesion->email ?? '—') ?></td>
                        <td><?= h($getAdhesionStage($adhesion)) ?></td>
                        <td><?= $getPromotionalCodeCell($adhesion) ?></td>
                        <td><?= $getBrokerCell($adhesion) ?></td>
                        <td><?= $getPixStatusBadge($adhesion) ?></td>
                        <td><?= $getSignatureStatusBadge($adhesion) ?></td>
                        <td><?= h($adhesion->created->format('d/m/Y H:i:s') ?? '—') ?></td>
                        <td class="text-end">
                            <?php
                            if ($adhesion->adhesion_payment_detail) {
                                echo $this->Html->link(
                                    '<i class="bi bi-file-pdf"></i>',
                                    ['action' => 'generatePdf', $adhesion->id],
                                    ['escape' => false, 'class' => 'btn btn-sm btn-light me-1', 'title' => 'Gerar PDF da proposta']
                                );
                                echo $this->Html->link(
                                    '<i class="bi bi-file-pdf"></i>',
                                    ['action' => 'generateFormPdf', $adhesion->id],
                                    ['escape' => false, 'class' => 'btn btn-sm btn-light me-1', 'title' => 'Gerar PDF da inscrição']
                                );
                            }
                            ?>

                            <?= $this->Html->link(
                                '<i class="bi bi-eye"></i>',
                                ['action' => 'view', $adhesion->id],
                                ['escape' => false, 'class' => 'btn btn-sm btn-light me-1', 'title' => 'Visualizar']
                            ) ?>

                            <button type="button" class="btn btn-sm btn-light me-1" title="Link de retomada"
                                    data-bs-toggle="modal" data-bs-target="#resumeLinkModal-<?= $adhesion->id ?>">
                                <i class="bi bi-link-45deg"></i>
                            </button>

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
                    <?php
                    // Acumulado à parte: um <div class="modal"> não pode ser
                    // filho direto de <tbody> -- o navegador o realocaria para
                    // fora da tabela de qualquer jeito, então ele já nasce
                    // depois dela, sem depender desse comportamento.
                    $resumeModals .= $this->element('../Admin/Adhesions/resume_modal', [
                        'adhesion' => $adhesion,
                        'resumeSteps' => $resumeInfo[$adhesion->id]['steps'],
                        'suggestedStep' => $resumeInfo[$adhesion->id]['suggestedStep'],
                        'resumeUrl' => $resumeInfo[$adhesion->id]['url'],
                    ]);
                    ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= $resumeModals ?>

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