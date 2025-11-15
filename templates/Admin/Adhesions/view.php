<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">
        <i class="bi bi-person-vcard"></i> Detalhes do Cliente
    </h2>

    <?= $this->Html->link(
        '<i class="bi bi-arrow-left"></i> Voltar',
        ['action' => 'index'],
        ['escape' => false, 'class' => 'btn btn-outline-secondary']
    ) ?>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#dados-iniciais">Dados Iniciais</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dados-pessoais">Dados Pessoais</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#endereco">Endereço</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dependentes">Dependentes</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#documentos">Documentos</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#plano">Plano</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#outras-infos">Outras Informações</a></li>
</ul>

<div class="tab-content">

    <!-- DADOS INICIAIS -->
    <div id="dados-iniciais" class="tab-pane fade show active">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-info-circle"></i> Dados Iniciais</h5>
            <p><strong>Nome:</strong> <?= h($adhesion->name ?? '—') ?></p>
            <p><strong>E-mail:</strong> <?= h($adhesion->email ?? '—') ?></p>
            <p><strong>Telefone:</strong> <?= h($adhesion->phone ?? '—') ?></p>
            <p><strong>Data Envio:</strong> <?= h($adhesion->created ?? '—') ?></p>
        </div>
    </div>

    <!-- DADOS PESSOAIS -->
    <div id="dados-pessoais" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">
                <i class="bi bi-person"></i> Dados Pessoais
            </h5>

            <p><strong>Plano para:</strong> <?= h($adhesion->adhesion_personal_data->plan_for ?? '—') ?></p>
            <p><strong>Nome:</strong> <?= h($adhesion->adhesion_personal_data->name ?? '—') ?></p>
            <p><strong>CPF:</strong> <?= h($adhesion->adhesion_personal_data->cpf ?? '—') ?></p>
            <p><strong>Data de Nascimento:</strong> 
                <?= isset($adhesion->adhesion_personal_data->birth_date) 
                    ? $adhesion->adhesion_personal_data->birth_date->format('d/m/Y')
                    : '—' ?>
            </p>
            <p><strong>Nacionalidade:</strong> <?= h($adhesion->adhesion_personal_data->nacionality ?? '—') ?></p>
            <p><strong>Gênero:</strong> <?= h($adhesion->adhesion_personal_data->gender ?? '—') ?></p>
            <p><strong>Estado Civil:</strong> <?= h($adhesion->adhesion_personal_data->marital_status ?? '—') ?></p>
            <p><strong>Número de Filhos:</strong> <?= h($adhesion->adhesion_personal_data->number_children ?? '—') ?></p>
            <p><strong>Nome da Mãe:</strong> <?= h($adhesion->adhesion_personal_data->mother_name ?? '—') ?></p>
            <p><strong>Nome do Pai:</strong> <?= h($adhesion->adhesion_personal_data->father_name ?? '—') ?></p>

            <hr>

            <h6 class="fw-semibold text-secondary mb-2">
                <i class="bi bi-person-badge"></i> Representante Legal (se aplicável)
            </h6>

            <p><strong>Nome:</strong> <?= h($adhesion->adhesion_personal_data->name_legal_representative ?? '—') ?></p>
            <p><strong>CPF:</strong> <?= h($adhesion->adhesion_personal_data->cpf_legal_representative ?? '—') ?></p>
            <p><strong>Parentesco:</strong> <?= h($adhesion->adhesion_personal_data->affiliation_legal_representative ?? '—') ?></p>

        </div>
    </div>


    <!-- ENDERECO -->
    <div id="endereco" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">
                <i class="bi bi-geo-alt"></i> Endereço
            </h5>

            <p><strong>CEP:</strong> <?= h($adhesion->adhesion_address->cep ?? '—') ?></p>
            <p><strong>Endereço:</strong> <?= h($adhesion->adhesion_address->address ?? '—') ?></p>
            <p><strong>Número:</strong> <?= h($adhesion->adhesion_address->number ?? '—') ?></p>
            <p><strong>Bairro:</strong> <?= h($adhesion->adhesion_address->neighborhood ?? '—') ?></p>
            <p><strong>Cidade:</strong> <?= h($adhesion->adhesion_address->city ?? '—') ?></p>
            <p><strong>Estado:</strong> <?= h($adhesion->adhesion_address->state ?? '—') ?></p>
            <p><strong>Complemento:</strong> <?= h($adhesion->adhesion_address->complement ?? '—') ?></p>
        </div>
    </div>


    <!-- DEPENDENTES -->
    <div id="dependentes" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">
                <i class="bi bi-people"></i> Dependentes
            </h5>

            <?php if (!empty($adhesion->adhesion_dependents)): ?>
                <?php foreach ($adhesion->adhesion_dependents as $dep): ?>
                    <div class="border rounded p-3 mb-3 bg-light">
                        <p><strong>Nome:</strong> <?= h($dep->name ?? '—') ?></p>
                        <p><strong>CPF:</strong> <?= h($dep->cpf ?? '—') ?></p>
                        <p><strong>Parentesco:</strong> <?= h($dep->kinship ?? '—') ?></p>
                        <p><strong>Data de Nascimento:</strong> 
                            <?= isset($dep->birth_date) 
                                ? $dep->birth_date->format('d/m/Y')
                                : '—' ?>
                        </p>
                        <p><strong>Participação (%):</strong> <?= h($dep->participation ?? '—') ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted fst-italic">Sem dependentes cadastrados.</p>
            <?php endif; ?>
        </div>
    </div>


    <!-- DOCUMENTOS -->
    <div id="documentos" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">
                <i class="bi bi-folder"></i> Documentos
            </h5>

            <?php if (!empty($adhesion->adhesion_documents)): ?>
                <?php foreach ($adhesion->adhesion_documents as $doc): ?>
                    <div class="border rounded p-3 mb-3 bg-light">

                        <p><strong>Tipo:</strong> <?= h($doc->type ?? '—') ?></p>
                        <p><strong>Número do Documento:</strong> <?= h($doc->document_number ?? '—') ?></p>
                        
                        <p><strong>Data de Emissão:</strong>
                            <?= isset($doc->issue_date) 
                                ? $doc->issue_date->format('d/m/Y')
                                : '—' ?>
                        </p>

                        <p><strong>Órgão Emissor:</strong> <?= h($doc->issuer ?? '—') ?></p>
                        <p><strong>Local de Nascimento:</strong> <?= h($doc->place_birth ?? '—') ?></p>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted fst-italic">Nenhum documento cadastrado.</p>
            <?php endif; ?>
        </div>
    </div>


    <!-- PLANO -->
    <div id="plano" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">
                <i class="bi bi-briefcase"></i> Plano
            </h5>

            <p><strong>Idade de Entrada no Benefício:</strong>
                <?= h($adhesion->adhesion_plan->benefit_entry_age ?? '—') ?>
            </p>

            <p><strong>Contribuição Mensal:</strong>
                R$ <?= isset($adhesion->adhesion_plan->monthly_contribuition_amount)
                    ? number_format($adhesion->adhesion_plan->monthly_contribuition_amount, 2, ',', '.')
                    : '—' ?>
            </p>

            <p><strong>Contribuição Inicial (Aporte):</strong>
                R$ <?= isset($adhesion->adhesion_plan->value_founding_contribution)
                    ? number_format($adhesion->adhesion_plan->value_founding_contribution, 2, ',', '.')
                    : '—' ?>
            </p>

            <p><strong>Capital Segurado:</strong>
                R$ <?= isset($adhesion->adhesion_plan->insured_capital)
                    ? number_format($adhesion->adhesion_plan->insured_capital, 2, ',', '.')
                    : '—' ?>
            </p>

            <p><strong>Modelo de Contribuição:</strong>
                <?= h($adhesion->adhesion_plan->contribution ?? '—') ?>
            </p>
        </div>
    </div>


    <!-- OUTRAS INFORMACOES -->
    <div id="outras-infos" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary">
                <i class="bi bi-chat-dots"></i> Outras Informações
            </h5>

            <p><strong>Ocupação Principal:</strong> 
                <?= h($adhesion->adhesion_other_information->main_occupation ?? '—') ?>
            </p>

            <p><strong>Categoria:</strong> 
                <?= h($adhesion->adhesion_other_information->category ?? '—') ?>
            </p>

            <p><strong>Residente Brasileiro:</strong> 
                <?= isset($adhesion->adhesion_other_information->brazilian_resident)
                    ? ($adhesion->adhesion_other_information->brazilian_resident ? 'Sim' : 'Não')
                    : '—' ?>
            </p>

            <p><strong>Pessoa Politicamente Exposta (PEP):</strong> 
                <?= isset($adhesion->adhesion_other_information->politically_exposed)
                    ? ($adhesion->adhesion_other_information->politically_exposed ? 'Sim' : 'Não')
                    : '—' ?>
            </p>

            <p><strong>Obrigação Fiscal em Outros Países:</strong> 
                <?= isset($adhesion->adhesion_other_information->obligation_other_countries)
                    ? ($adhesion->adhesion_other_information->obligation_other_countries ? 'Sim' : 'Não')
                    : '—' ?>
            </p>
        </div>
    </div>


</div>
