<?php

/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\IntegrationLog> $logs
 * @var array<string> $services
 * @var string|null $service
 * @var string|null $status
 * @var string|null $adhesionId
 */

function formatLogBody(?string $value): string
{
    if ($value === null || $value === '')
        return '';

    $decoded = json_decode($value, true);

    if (json_last_error() === JSON_ERROR_NONE && $decoded !== null)
        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return $value;
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary"><i class="bi bi-activity"></i> Logs de Integração</h2>
</div>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-body">
        <?= $this->Form->create(null, ['type' => 'get']) ?>
        <div class="row g-3">
            <div class="col-md-3">
                <?= $this->Form->control('service', [
                    'label' => 'Serviço',
                    'class' => 'form-select',
                    'type' => 'select',
                    'options' => array_combine($services, array_map('ucfirst', $services)),
                    'empty' => 'Todos os serviços',
                    'value' => $service,
                ]) ?>
            </div>

            <div class="col-md-3">
                <?= $this->Form->control('status', [
                    'label' => 'Status',
                    'class' => 'form-select',
                    'type' => 'select',
                    'options' => ['success' => 'Sucesso', 'failure' => 'Falha'],
                    'empty' => 'Todos os status',
                    'value' => $status,
                ]) ?>
            </div>

            <div class="col-md-3">
                <?= $this->Form->control('adhesion_id', [
                    'label' => 'ID da adesão',
                    'class' => 'form-control',
                    'value' => $adhesionId,
                ]) ?>
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <?= $this->Form->button('<i class="bi bi-search"></i> Filtrar', [
                    'escapeTitle' => false,
                    'class' => 'btn btn-outline-primary w-100 me-2',
                ]) ?>
                <?= $this->Html->link('Limpar', ['action' => 'index'], ['class' => 'btn btn-outline-secondary']) ?>
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
                    <th>Quando</th>
                    <th>Adesão</th>
                    <th>Serviço</th>
                    <th>Operação</th>
                    <th>Status</th>
                    <th>Duração</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= $log->created->format('d/m/Y H:i:s') ?></td>
                        <td>
                            <?php if ($log->adhesion_initial_data_id): ?>
                                <?= $this->Html->link(
                                    '#' . $log->adhesion_initial_data_id,
                                    ['controller' => 'Adhesions', 'action' => 'view', $log->adhesion_initial_data_id, '?' => ['tab' => 'integrationLogs']]
                                ) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-secondary"><?= h($log->service) ?></span></td>
                        <td><?= h($log->operation) ?></td>
                        <td><?= $log->success ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">Falha</span>' ?></td>
                        <td><?= $log->duration_ms !== null ? h($log->duration_ms) . ' ms' : '—' ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#log-<?= $log->id ?>">
                                Detalhes
                            </button>
                        </td>
                    </tr>
                    <tr class="collapse" id="log-<?= $log->id ?>">
                        <td colspan="7">
                            <div class="p-3 bg-light border rounded">
                                <?php if ($log->url): ?>
                                    <p class="mb-1 small text-break"><strong>URL:</strong> <?= h($log->http_method) ?> <?= h($log->url) ?></p>
                                <?php endif; ?>
                                <?php if ($log->error_message): ?>
                                    <p class="mb-2 small text-danger"><strong>Erro:</strong> <?= h($log->error_message) ?></p>
                                <?php endif; ?>

                                <?php foreach (['Request' => $log->request_body, 'Response' => $log->response_body, 'Contexto' => $log->context] as $label => $value): ?>
                                    <?php if ($value): ?>
                                        <details class="mb-2">
                                            <summary class="small fw-semibold text-primary" style="cursor: pointer;"><?= h($label) ?></summary>
                                            <pre class="small bg-white border rounded p-2 mt-1 mb-0" style="max-height: 280px; overflow: auto; white-space: pre-wrap; word-break: break-all;"><?= h(formatLogBody($value)) ?></pre>
                                        </details>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
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
