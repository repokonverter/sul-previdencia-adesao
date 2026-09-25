<?php
$validTabs = [
    'initialData', 'personalData', 'documents', 'plan', 'dependents', 'addressData',
    'otherInformation', 'proponentStatement', 'pensionScheme', 'paymentDetail', 'integrationLogs',
    'audits', 'resume',
];
$activeTab = $this->request->getQuery('tab');
if (!in_array($activeTab, $validTabs, true))
    $activeTab = 'initialData';

$navLinkClass = function (string $tabId, string $activeTab): string
{
    return 'nav-link' . ($tabId === $activeTab ? ' active' : '');
};

$tabPaneClass = function (string $tabId, string $activeTab): string
{
    return 'tab-pane fade' . ($tabId === $activeTab ? ' show active' : '');
};

/**
 * Transforma o caminho pontuado do diff em algo legível:
 * "adhesion_personal_data.cpf" vira "Dados pessoais › Cpf".
 */
$auditFieldLabel = function (string $path): string
{
    $sections = [
        'adhesion_personal_data' => 'Dados pessoais',
        'adhesion_document' => 'Documentos',
        'adhesion_plan' => 'Plano',
        'adhesion_dependents' => 'Beneficiários',
        'adhesion_address' => 'Endereço',
        'adhesion_other_information' => 'Outras informações',
        'adhesion_proponent_statement' => 'Declarações do proponente',
        'adhesion_pension_schemes' => 'Regime de previdência',
        'adhesion_payment_detail' => 'Dados para pagamento',
    ];

    $parts = explode('.', $path);
    $section = $sections[$parts[0]] ?? null;

    if ($section === null)
        return ucfirst(str_replace('_', ' ', $path));

    array_shift($parts);

    return $section . ' › ' . ucfirst(str_replace('_', ' ', implode(' ', $parts)));
};

$formatAuditValue = function ($value): string
{
    if ($value === null || $value === '')
        return '—';

    if (is_bool($value))
        return $value ? 'Sim' : 'Não';

    return (string)$value;
};

$formatLogBody = function (?string $value): string
{
    if ($value === null || $value === '')
        return '';

    $decoded = json_decode($value, true);

    if (json_last_error() === JSON_ERROR_NONE && $decoded !== null)
        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return $value;
};
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">
        <i class="bi bi-person-vcard"></i> Detalhes da Adesão
    </h2>

    <div>
        <?= $this->Html->link(' Proposta PDF', ['action' => 'generatePdf', $adhesion->id], ['class' => 'btn btn-primary']) ?>
        <?= $this->Html->link(' Inscrição PDF', ['action' => 'generateFormPdf', $adhesion->id], ['class' => 'btn btn-primary']) ?>
        <?= $this->Html->link('<i class="bi bi-pencil"></i> Editar', ['action' => 'edit', $adhesion->id], ['escape' => false, 'class' => 'btn btn-warning']) ?>
        <?= $this->Html->link('<i class="bi bi-arrow-left"></i> Voltar', ['action' => 'index'], ['escape' => false, 'class' => 'btn btn-outline-secondary']) ?>
    </div>
</div>

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><a class="<?= $navLinkClass('initialData', $activeTab) ?>" data-bs-toggle="tab" href="#initialData">Dados Iniciais</a></li>
    <li class="nav-item"><a class="<?= $navLinkClass('personalData', $activeTab) ?>" data-bs-toggle="tab" href="#personalData">Dados Pessoais</a></li>
    <li class="nav-item"><a class="<?= $navLinkClass('documents', $activeTab) ?>" data-bs-toggle="tab" href="#documents">Documentos</a></li>
    <li class="nav-item"><a class="<?= $navLinkClass('plan', $activeTab) ?>" data-bs-toggle="tab" href="#plan">Plano</a></li>
    <li class="nav-item"><a class="<?= $navLinkClass('dependents', $activeTab) ?>" data-bs-toggle="tab" href="#dependents">Beneficiários</a></li>
    <li class="nav-item"><a class="<?= $navLinkClass('addressData', $activeTab) ?>" data-bs-toggle="tab" href="#addressData">Endereço</a></li>
    <li class="nav-item"><a class="<?= $navLinkClass('otherInformation', $activeTab) ?>" data-bs-toggle="tab" href="#otherInformation">Outras Informações</a></li>
    <li class="nav-item"><a class="<?= $navLinkClass('proponentStatement', $activeTab) ?>" data-bs-toggle="tab" href="#proponentStatement">Declarações do Proponente</a></li>
    <li class="nav-item"><a class="<?= $navLinkClass('pensionScheme', $activeTab) ?>" data-bs-toggle="tab" href="#pensionScheme">Regime de Previdência</a></li>
    <li class="nav-item"><a class="<?= $navLinkClass('paymentDetail', $activeTab) ?>" data-bs-toggle="tab" href="#paymentDetail">Dados para Pagamento</a></li>
    <li class="nav-item"><a class="<?= $navLinkClass('integrationLogs', $activeTab) ?>" data-bs-toggle="tab" href="#integrationLogs">Integrações</a></li>
    <li class="nav-item"><a class="<?= $navLinkClass('audits', $activeTab) ?>" data-bs-toggle="tab" href="#audits">Histórico</a></li>
    <li class="nav-item"><a class="<?= $navLinkClass('resume', $activeTab) ?>" data-bs-toggle="tab" href="#resume">Retomada</a></li>
</ul>

<div class="tab-content" style="margin-bottom: 80px;">
    <!-- DADOS INICIAIS -->
    <div id="initialData" class="<?= $tabPaneClass('initialData', $activeTab) ?>">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Dados Iniciais</h5>
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Nome:</strong> <?= h($adhesion->name) ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>E-mail:</strong> <?= h($adhesion->email) ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Celular:</strong> <?= h($adhesion->phone) ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Criado em:</strong> <?= $adhesion->created->format('d/m/Y H:i') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- DADOS PESSOAIS -->
    <div id="personalData" class="<?= $tabPaneClass('personalData', $activeTab) ?>">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Dados Pessoais</h5>
            <?php $p = $adhesion->adhesion_personal_data; ?>
            <div class="row">
                <div class="col-md-12">
                    <p><strong>O plano é para:</strong> <?= h($p->plan_for ?? '—') ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Nome completo:</strong> <?= h($p->name ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>CPF:</strong> <?= h($p->cpf ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Data de nasc.:</strong> <?= $p && $p->birth_date ? $p->birth_date->format('d/m/Y') : '—' ?></p>
                </div>

                <div class="col-md-4">
                    <p><strong>Nacionalidade:</strong> <?= h($p->nacionality ?? '—') ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Gênero:</strong> <?= h($p->gender ?? '—') ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Estado civil:</strong> <?= h($p->marital_status ?? '—') ?></p>
                </div>

                <div class="col-md-4">
                    <p><strong>Nº de filhos:</strong> <?= h($p->number_children ?? '—') ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Nome da mãe:</strong> <?= h($p->mother_name ?? '—') ?></p>
                </div>
                <div class="col-md-4">
                    <p><strong>Nome do pai:</strong> <?= h($p->father_name ?? '—') ?></p>
                </div>
            </div>
            <?php if (!empty($p->name_legal_representative)): ?>
                <div class="mt-3 p-3 border rounded bg-light">
                    <h6>Representante Legal</h6>
                    <p><strong>Nome:</strong> <?= h($p->name_legal_representative) ?></p>
                    <p><strong>CPF:</strong> <?= h($p->cpf_legal_representative) ?></p>
                    <p><strong>Filiação:</strong> <?= h($p->affiliation_legal_representative) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- DOCUMENTOS -->
    <div id="documents" class="<?= $tabPaneClass('documents', $activeTab) ?>">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Documentos</h5>
            <?php if (!empty($adhesion->adhesion_document)): ?>
                <div class="mb-3">
                    <p><strong>Natureza:</strong> <?= h($adhesion->adhesion_document->type) ?></p>
                    <p><strong>Nº do documento:</strong> <?= h($adhesion->adhesion_document->document_number) ?></p>
                    <p><strong>Data de expedição:</strong> <?= $adhesion->adhesion_document->issue_date ? $adhesion->adhesion_document->issue_date->format('d/m/Y') : '—' ?></p>
                    <p><strong>Órgão expedidor:</strong> <?= h($adhesion->adhesion_document->issuer) ?></p>
                    <p><strong>Naturalidade:</strong> <?= h($adhesion->adhesion_document->place_birth) ?></p>
                </div>
            <?php else: ?>
                <p>Nenhum documento registrado.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- PLANO -->
    <div id="plan" class="<?= $tabPaneClass('plan', $activeTab) ?>">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Plano</h5>
            <?php $pl = $adhesion->adhesion_plan; // Pode vir como collection ou single dependendo do contain
            ?>
            <?php if ($pl): ?>
                <p><strong>Idade entrada em benefício:</strong> <?= h($pl->benefit_entry_age) ?></p>
                <p><strong>Contribuição mensal aposentadoria:</strong> R$ <?= number_format($pl->monthly_retirement_contribution, 2, ',', '.') ?></p>
                <p><strong>Contribuição mensal pensão por morte:</strong> R$ <?= number_format($pl->monthly_survivors_pension_contribution, 2, ',', '.') ?></p>
                <p><strong>Capital segurado pensão por morte:</strong> R$ <?= number_format($pl->survivors_pension_insured_capital, 2, ',', '.') ?></p>
                <p><strong>Contribuição mensal invalidez:</strong> R$ <?= number_format($pl->monthly_disability_retirement_contribution, 2, ',', '.') ?></p>
                <p><strong>Capital segurado invalidez:</strong> R$ <?= number_format($pl->disability_retirement_insured_capital, 2, ',', '.') ?></p>
            <?php else: ?>
                <p>Nenhum plano registrado.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- BENEFICIÁRIOS -->
    <div id="dependents" class="<?= $tabPaneClass('dependents', $activeTab) ?>">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Beneficiários</h5>
            <?php if (!empty($adhesion->adhesion_dependents)): ?>
                <?php foreach ($adhesion->adhesion_dependents as $idx => $dep): ?>
                    <div class="border rounded p-3 mb-3 bg-light">
                        <h6>Beneficiário <?= $idx + 1 ?></h6>
                        <p><strong>Nome:</strong> <?= h($dep->name) ?></p>
                        <p><strong>Parentesco:</strong> <?= h($dep->kinship) ?></p>
                        <p><strong>CPF:</strong> <?= h($dep->cpf) ?></p>
                        <p><strong>Nascimento:</strong> <?= $dep->birth_date ? $dep->birth_date->format('d/m/Y') : '—' ?></p>
                        <p><strong>Participação:</strong> <?= h($dep->participation) ?>%</p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nenhum beneficiário registrado.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ENDEREÇO -->
    <div id="addressData" class="<?= $tabPaneClass('addressData', $activeTab) ?>">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Endereço</h5>
            <?php $a = $adhesion->adhesion_address; ?>
            <?php if ($a): ?>
                <p><strong>CEP:</strong> <?= h($a->cep) ?></p>
                <p><strong>Endereço:</strong> <?= h($a->address) ?>, <?= h($a->number) ?></p>
                <p><strong>Bairro:</strong> <?= h($a->neighborhood) ?></p>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Cidade:</strong> <?= h($a->city) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>UF:</strong> <?= h($a->state) ?></p>
                    </div>
                </div>
                <p><strong>Complemento:</strong> <?= h($a->complement ?? '—') ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- OUTRAS INFORMAÇÕES -->
    <div id="otherInformation" class="<?= $tabPaneClass('otherInformation', $activeTab) ?>">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Outras Informações</h5>
            <?php $o = $adhesion->adhesion_other_information; ?>
            <p><strong>Ocupação principal:</strong> <?= h($o->main_occupation_description ?? '—') ?> (CBO: <?= h($o->main_occupation_code ?? '—') ?>)</p>
            <p><strong>Categoria:</strong> <?= h($o->category ?? '—') ?></p>
            <p><strong>Renda mensal bruta:</strong> R$ <?= number_format($o->monthly_income ?? 0, 2, ',', '.') ?></p>
            <p><strong>Empresa:</strong> <?= h($o->company ?? '—') ?></p>
            <p><strong>Residente no Brasil?</strong> <?= isset($o->brazilian_resident) ? ($o->brazilian_resident ? 'Sim' : 'Não') : '—' ?></p>
            <p><strong>É PEP?</strong> <?= isset($o->politically_exposed) ? ($o->politically_exposed ? 'Sim' : 'Não') : '—' ?></p>
            <?php if ($o && $o->politically_exposed): ?>
                <p><strong>Obs PEP:</strong> <?= h($o->politically_exposed_obs) ?></p>
            <?php endif; ?>
            <p><strong>Obrigações fiscais em outros países?</strong> <?= isset($o->obligation_other_countries) ? ($o->obligation_other_countries ? 'Sim' : 'Não') : '—' ?></p>
        </div>
    </div>

    <!-- DECLARAÇÕES -->
    <div id="proponentStatement" class="<?= $tabPaneClass('proponentStatement', $activeTab) ?>">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Declarações do Proponente</h5>
            <?php $s = $adhesion->adhesion_proponent_statement; ?>
            <?php
            $fields = [
                'health_problem' => 'Problema de saúde?',
                'heart_disease' => 'Doença do coração?',
                'suffered_organ_defects' => 'Deficiência de órgãos/membros?',
                'surgery' => 'Fez cirurgia/biópsia?',
                'away' => 'Afastado ou aposentado por invalidez?',
                'practices_parachuting' => 'Pratica esportes de risco?',
                'smoker' => 'É fumante?',
                'gripe' => 'Sintomas de gripe?',
                'covid' => 'Teve COVID?',
                'covid_sequelae' => 'Sequelas de COVID?'
            ];
            foreach ($fields as $field => $label): ?>
                <p><strong><?= $label ?></strong> <?= isset($s->$field) ? ($s->$field ? 'Sim' : 'Não') : '—' ?></p>
                <?php $obs = $field . '_obs';
                if (!empty($s->$obs)): ?>
                    <p class="ms-3 text-muted">Obs: <?= h($s->$obs) ?></p>
                <?php endif; ?>
                <?php if ($field === 'smoker' && $s && $s->smoker): ?>
                    <p class="ms-3 text-muted">Tipo: <?= $s->smoker_type ? 'Cigarro' : 'Outros (' . h($s->smoker_type_obs) . ')' ?> | Qtd: <?= h($s->smoker_qty) ?></p>
                <?php endif; ?>
                <hr>
            <?php endforeach; ?>
            <p><strong>Peso:</strong> <?= h($s->weight ?? '—') ?> Kg | <strong>Altura:</strong> <?= h($s->height ?? '—') ?> m</p>
        </div>
    </div>

    <!-- REGIME PREVIDÊNCIA -->
    <div id="pensionScheme" class="<?= $tabPaneClass('pensionScheme', $activeTab) ?>">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Regime de Previdência</h5>
            <?php $pensionSchemes = $adhesion->adhesion_pension_schemes ?? []; ?>
            <?php if (!empty($pensionSchemes)): ?>
                <p><strong>Regime(s):</strong></p>
                <ul>
                    <?php foreach ($pensionSchemes as $ps): ?>
                        <li><?= h($ps->pension_scheme) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php $ps = $pensionSchemes[0]; ?>
                <?php if ($ps->name): ?>
                    <div class="mt-2 p-2 border rounded">
                        <p><strong>Vinculado ao segurado:</strong> <?= h($ps->name) ?></p>
                        <p><strong>CPF:</strong> <?= h($ps->cpf) ?></p>
                        <p><strong>Grau de parentesco:</strong> <?= h($ps->kinship) ?></p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p>Nenhuma informação registrada.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- PAGAMENTO -->
    <div id="paymentDetail" class="<?= $tabPaneClass('paymentDetail', $activeTab) ?>">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Dados para Pagamento</h5>
            <?php $pd = $adhesion->adhesion_payment_detail; ?>
            <?php if ($pd): ?>
                <p><strong>Vencimento:</strong> Dia <?= h($pd->due_date) ?></p>
                <p><strong>Total contribuição:</strong> R$ <?= number_format($pd->total_contribution, 2, ',', '.') ?></p>
                <p><strong>Meio de pagamento:</strong> <?= h($pd->payment_type) ?></p>
                <?php if ($pd->payment_type === 'Débito em conta'): ?>
                    <div class="mt-2 p-2 border rounded">
                        <p><strong>Correntista:</strong> <?= h($pd->account_holder_name) ?> (CPF: <?= h($pd->account_holder_cpf) ?>)</p>
                        <p><strong>Banco:</strong> <?= h($pd->bank_number) ?> - <?= h($pd->bank_name) ?></p>
                        <p><strong>Agência:</strong> <?= h($pd->branch_number) ?> | <strong>Conta:</strong> <?= h($pd->account_number) ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="card p-4 shadow-sm mt-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-primary">Cobranças Pix</h5>
                <?= $this->Form->create(null, ['url' => ['action' => 'checkPixPayment', $adhesion->id], 'style' => 'display:inline']) ?>
                <?= $this->Form->hidden('tab', ['id' => 'checkPixPaymentTab', 'value' => $activeTab]) ?>
                <?= $this->Form->button('<i class="bi bi-arrow-repeat"></i> Verificar pagamento', [
                    'type' => 'submit',
                    'escapeTitle' => false,
                    'class' => 'btn btn-sm btn-outline-primary',
                ]) ?>
                <?= $this->Form->end() ?>
            </div>
            <?php if (!empty($adhesion->pix_transactions)): ?>
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Tentativa</th>
                            <th>Txid</th>
                            <th>Valor</th>
                            <th>Pago</th>
                            <th>Data pagamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adhesion->pix_transactions as $pix): ?>
                            <tr>
                                <td><?= h($pix->attempt) ?></td>
                                <td class="text-break"><?= h($pix->txid) ?></td>
                                <td>R$ <?= number_format($pix->amount, 2, ',', '.') ?></td>
                                <td><?= $pix->paid ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></td>
                                <td><?= h($pix->payment_date) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted mb-0">Nenhuma cobrança Pix gerada ainda. O cliente ainda não abriu a página de pagamento.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- INTEGRAÇÕES -->
    <div id="integrationLogs" class="<?= $tabPaneClass('integrationLogs', $activeTab) ?>">
        <?php
        $envelope = $adhesion->clicksign_datas[0] ?? null;
        $changedAt = $adhesion->adhesion_plan?->admin_overridden_at;
        // Documento gerado antes da última alteração está desatualizado. A
        // comparação é de datas em vez de uma flag porque a pergunta é
        // exatamente essa: o que o proponente tem em mãos é anterior ao que
        // está gravado?
        $documentsAreStale = $envelope !== null
            && $changedAt !== null
            && $envelope->created !== null
            && $changedAt->greaterThan($envelope->created);
        ?>

        <?php if ($documentsAreStale): ?>
            <div class="alert alert-warning">
                <h6 class="fw-bold"><i class="bi bi-exclamation-triangle"></i> Documentos desatualizados</h6>
                <p class="mb-2">
                    O plano foi alterado em <strong><?= $changedAt->format('d/m/Y H:i') ?></strong>,
                    depois de os documentos terem sido gerados em
                    <strong><?= $envelope->created->format('d/m/Y H:i') ?></strong>.
                    O que o proponente tem para assinar não reflete o que está gravado.
                </p>
                <?= $this->Form->postLink(
                    '<i class="bi bi-arrow-repeat"></i> Regerar documentos e reenviar para assinatura',
                    ['action' => 'regenerateDocuments', $adhesion->id],
                    [
                        'escape' => false,
                        'class' => 'btn btn-warning btn-sm',
                        'confirm' => 'O envelope atual será cancelado e o proponente receberá um e-mail '
                            . 'pedindo que assine os documentos novos. Continuar?',
                    ]
                ) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($adhesion->clicksign_datas)): ?>
            <div class="card p-4 shadow-sm mb-3">
                <h5 class="fw-bold mb-3 text-primary">Envelopes de assinatura</h5>
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 90px;">Tentativa</th>
                            <th>Envelope</th>
                            <th style="width: 120px;">Situação</th>
                            <th style="width: 160px;">Criado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adhesion->clicksign_datas as $attempt): ?>
                            <tr>
                                <td>#<?= h($attempt->attempt) ?></td>
                                <td><code class="small"><?= h($attempt->envelope_id) ?></code></td>
                                <td>
                                    <span class="badge bg-<?= match ($attempt->status) {
                                        'sent' => 'success',
                                        'failed' => 'danger',
                                        'canceled' => 'secondary',
                                        default => 'warning',
                                    } ?>"><?= h($attempt->status) ?></span>
                                </td>
                                <td><?= $attempt->created?->format('d/m/Y H:i') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Linha do tempo de integrações</h5>
            <?php if (!empty($adhesion->integration_logs)): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Quando</th>
                                <th>Serviço</th>
                                <th>Operação</th>
                                <th>Status</th>
                                <th>Duração</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adhesion->integration_logs as $log): ?>
                                <tr>
                                    <td><?= $log->created->format('d/m/Y H:i:s') ?></td>
                                    <td><span class="badge bg-secondary"><?= h($log->service) ?></span></td>
                                    <td><?= h($log->operation) ?></td>
                                    <td><?= $log->success ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">Falha</span>' ?></td>
                                    <td><?= $log->duration_ms !== null ? h($log->duration_ms) . ' ms' : '—' ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#integrationLog-<?= $log->id ?>">
                                            Detalhes
                                        </button>
                                    </td>
                                </tr>
                                <tr class="collapse" id="integrationLog-<?= $log->id ?>">
                                    <td colspan="6">
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
                                                        <pre class="small bg-white border rounded p-2 mt-1 mb-0" style="max-height: 280px; overflow: auto; white-space: pre-wrap; word-break: break-all;"><?= h($formatLogBody($value)) ?></pre>
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
            <?php else: ?>
                <p class="text-muted mb-0">Nenhuma chamada a integrações registrada ainda para esta adesão.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- RETOMADA -->
    <div id="resume" class="<?= $tabPaneClass('resume', $activeTab) ?>">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Link de retomada</h5>

            <?php if ($resumeUrl !== null): ?>
                <div class="alert <?= $adhesion->resumeTokenHasExpired() ? 'alert-warning' : 'alert-success' ?>">
                    <?php if ($adhesion->resumeTokenHasExpired()): ?>
                        <i class="bi bi-clock-history"></i> Este link <strong>expirou</strong>
                        em <?= $adhesion->resume_token_expires_at->format('d/m/Y H:i') ?>.
                        Gere outro abaixo.
                    <?php else: ?>
                        <i class="bi bi-check-circle"></i> Link ativo, válido até
                        <strong><?= $adhesion->resume_token_expires_at->format('d/m/Y H:i') ?></strong>,
                        apontando para
                        <strong><?= h(\App\Services\AdhesionSteps::ORDER[$adhesion->resume_step] ?? 'a primeira etapa') ?></strong>.
                    <?php endif; ?>
                </div>

                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="resumeUrl" value="<?= h($resumeUrl) ?>" readonly>
                    <button class="btn btn-outline-secondary" type="button" id="copyResumeUrl">
                        <i class="bi bi-clipboard"></i> Copiar
                    </button>
                    <?php
                    $phone = preg_replace('/\D/', '', (string)$adhesion->phone);
                    $message = 'Olá! Continue sua proposta de adesão por aqui: ' . $resumeUrl;
                    ?>
                    <?php if ($phone !== ''): ?>
                        <a class="btn btn-outline-success"
                           target="_blank"
                           rel="noopener"
                           href="https://wa.me/55<?= h($phone) ?>?text=<?= rawurlencode($message) ?>">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                    <?php endif; ?>
                </div>

                <?= $this->Form->create(null, [
                    'url' => ['action' => 'sendResumeLink', $adhesion->id],
                    'class' => 'row g-2 align-items-end mb-3',
                ]) ?>
                <div class="col-md-6">
                    <label for="resumeEmail" class="form-label">Enviar por e-mail para</label>
                    <input type="email" name="email" id="resumeEmail" class="form-control"
                           value="<?= h($adhesion->email) ?>" required>
                    <div class="form-text">
                        Vem preenchido com o e-mail da adesão. Digitar outro envia para ele
                        sem alterar o cadastro.
                    </div>
                </div>
                <div class="col-md-3">
                    <?= $this->Form->button('<i class="bi bi-envelope"></i> Enviar', [
                        'escape' => false,
                        'class' => 'btn btn-outline-primary w-100',
                    ]) ?>
                </div>
                <?= $this->Form->end() ?>

                <?= $this->Form->postLink(
                    '<i class="bi bi-x-circle"></i> Revogar link',
                    ['action' => 'revokeResumeLink', $adhesion->id],
                    [
                        'escape' => false,
                        'class' => 'btn btn-outline-danger btn-sm',
                        'confirm' => 'Revogar o link? Quem o tiver deixa de conseguir abrir a proposta.',
                    ]
                ) ?>
            <?php else: ?>
                <p class="text-muted">
                    Nenhum link ativo. Gere um para o proponente continuar de onde parou.
                </p>
            <?php endif; ?>

            <hr class="my-4">

            <h6 class="fw-bold mb-2"><?= $resumeUrl === null ? 'Gerar link' : 'Gerar um link novo' ?></h6>
            <p class="text-muted small">
                Gerar invalida o link anterior. O mesmo link pode ser distribuído por
                quantos canais quiser.
            </p>

            <?= $this->Form->create(null, ['url' => ['action' => 'issueResumeLink', $adhesion->id]]) ?>
            <div class="row align-items-end">
                <div class="col-md-6">
                    <label for="resumeStep" class="form-label">O proponente recomeça em</label>
                    <select name="step" id="resumeStep" class="form-select">
                        <?php foreach ($resumeSteps as $id => $step): ?>
                            <option value="<?= h($id) ?>"
                                <?= $step['selectable'] ? '' : 'disabled' ?>
                                <?= $id === $suggestedStep ? 'selected' : '' ?>>
                                <?= h($step['label']) ?><?= $step['complete'] ? '' : ' — em branco' ?><?= $step['reason'] ? ' (' . h($step['reason']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        Etapas que dependem de outra ainda em branco ficam indisponíveis: o
                        proponente as pularia sem preencher, e a adesão seria finalizada
                        incompleta.
                    </div>
                </div>
                <div class="col-md-3">
                    <?= $this->Form->button('<i class="bi bi-link-45deg"></i> Gerar link', [
                        'escape' => false,
                        'class' => 'btn btn-primary w-100',
                    ]) ?>
                </div>
            </div>
            <?= $this->Form->end() ?>
        </div>
    </div>

    <!-- HISTÓRICO -->
    <div id="audits" class="<?= $tabPaneClass('audits', $activeTab) ?>">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Histórico de alterações</h5>

            <?php if (!empty($adhesion->adhesion_audits)): ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 160px;">Quando</th>
                                <th style="width: 200px;">Quem</th>
                                <th>O que</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adhesion->adhesion_audits as $audit): ?>
                                <tr>
                                    <td><?= $audit->created->format('d/m/Y H:i:s') ?></td>
                                    <td><?= h($audit->authorLabel()) ?></td>
                                    <td>
                                        <div class="fw-semibold mb-1"><?= h($audit->actionLabel()) ?></div>
                                        <?php $changes = $audit->changeList(); ?>
                                        <?php if ($changes !== []): ?>
                                            <ul class="list-unstyled small mb-0">
                                                <?php foreach ($changes as $field => [$was, $now]): ?>
                                                    <li class="mb-1">
                                                        <span class="text-muted"><?= h($auditFieldLabel($field)) ?>:</span>
                                                        <span class="text-decoration-line-through text-danger-emphasis"><?= h($formatAuditValue($was)) ?></span>
                                                        <i class="bi bi-arrow-right"></i>
                                                        <span class="fw-semibold text-success-emphasis"><?= h($formatAuditValue($now)) ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">
                    Nenhuma alteração feita pelo admin nesta adesão. O que o próprio
                    proponente preencheu no formulário não entra aqui.
                </p>
            <?php endif; ?>

            <?php if (!empty($adhesion->adhesion_plan?->admin_overridden)): ?>
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    Os valores deste plano foram ajustados manualmente
                    <?php if ($adhesion->adhesion_plan->admin_overridden_at): ?>
                        em <?= $adhesion->adhesion_plan->admin_overridden_at->format('d/m/Y H:i') ?>
                    <?php endif; ?>
                    e não são mais o resultado da fórmula.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        const copyButton = document.getElementById('copyResumeUrl');

        if (copyButton) {
            copyButton.addEventListener('click', function() {
                const field = document.getElementById('resumeUrl');

                field.select();
                navigator.clipboard.writeText(field.value);

                copyButton.innerHTML = '<i class="bi bi-check2"></i> Copiado';
                setTimeout(function() {
                    copyButton.innerHTML = '<i class="bi bi-clipboard"></i> Copiar';
                }, 2000);
            });
        }

        document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function(trigger) {
            trigger.addEventListener('shown.bs.tab', function(e) {
                const tabId = e.target.getAttribute('href').substring(1);
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabId);
                history.replaceState(null, '', url);

                const tabInput = document.getElementById('checkPixPaymentTab');
                if (tabInput) tabInput.value = tabId;
            });
        });
    });
</script>