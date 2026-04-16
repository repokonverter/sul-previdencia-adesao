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
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#initialData">Dados Iniciais</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#personalData">Dados Pessoais</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#documents">Documentos</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#plan">Plano</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dependents">Beneficiários</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#addressData">Endereço</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#otherInformation">Outras Informações</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#proponentStatement">Declarações do Proponente</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pensionScheme">Regime de Previdência</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#paymentDetail">Dados para Pagamento</a></li>
</ul>

<div class="tab-content" style="margin-bottom: 80px;">
    <!-- DADOS INICIAIS -->
    <div id="initialData" class="tab-pane fade show active">
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
    <div id="personalData" class="tab-pane fade">
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
    <div id="documents" class="tab-pane fade">
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
    <div id="plan" class="tab-pane fade">
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
    <div id="dependents" class="tab-pane fade">
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
    <div id="addressData" class="tab-pane fade">
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
    <div id="otherInformation" class="tab-pane fade">
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
    <div id="proponentStatement" class="tab-pane fade">
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
    <div id="pensionScheme" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">Regime de Previdência</h5>
            <?php $ps = $adhesion->adhesion_pension_scheme; ?>
            <?php if ($ps): ?>
                <p><strong>Regime:</strong> <?= h($ps->pension_scheme) ?></p>
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
    <div id="paymentDetail" class="tab-pane fade">
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
    </div>
</div>