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

<div class="tab-content">
    <!-- DADOS INICIAIS -->
    <div id="initialData" class="tab-pane fade show active">
        <div class="card p-4 shadow-sm">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('name', ['label' => 'Nome completo*', 'class' => 'form-control', 'required' => true]) ?>
                </div>
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('email', ['label' => 'E-mail*', 'class' => 'form-control', 'required' => true]) ?>
                </div>
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('phone', ['label' => 'Celular*', 'class' => 'form-control phone', 'required' => true]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- DADOS PESSOAIS -->
    <div id="personalData" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <div class="row mb-3">
                <div class="col">
                    <label class="form-label">O plano é para*</label><br>
                    <?= $this->Form->radio(
                        'adhesion_personal_data.plan_for',
                        ['Dependente' => 'Dependente', 'Titular' => 'Titular'],
                        ['default' => 'Titular', 'onclick' => 'planForHandle(this)']
                    ) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('adhesion_personal_data.name', ['label' => 'Nome completo*', 'class' => 'form-control', 'required' => true]) ?>
                </div>
                <div class="col-md-3 mb-3">
                    <?= $this->Form->control('adhesion_personal_data.cpf', ['label' => 'CPF*', 'class' => 'form-control cpf', 'required' => true]) ?>
                </div>
                <div class="col-md-3 mb-3">
                    <?php
                    $birthDate = $adhesion->adhesion_personal_data->birth_date
                        ? $adhesion->adhesion_personal_data->birth_date->format('Y-m-d')
                        : null;
                    echo $this->Form->label('adhesion_personal_data.birth_date', 'Data de nasc.*', ['class' => 'form-label']);
                    echo $this->Form->input('adhesion_personal_data.birth_date', ['class' => 'form-control', 'type' => 'date', 'required' => true, 'value' => $birthDate, 'max' => '9999-12-31']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_personal_data.nacionality', ['label' => 'Nacionalidade', 'class' => 'form-control']) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Gênero de nasc.*</label><br>
                    <?= $this->Form->radio(
                        'adhesion_personal_data.gender',
                        ['F' => 'Feminino', 'M' => 'Masculino'],
                        ['required' => true]
                    ) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_personal_data.marital_status', [
                        'label' => 'Estado civil*',
                        'type' => 'select',
                        'options' => [
                            'Casado' => 'Casado',
                            'Divorciado' => 'Divorciado',
                            'Separado' => 'Separado',
                            'Solteiro' => 'Solteiro',
                            'União estável' => 'União estável',
                            'Viúvo' => 'Viúvo'
                        ],
                        'empty' => 'Selecione...',
                        'class' => 'form-select',
                        'required' => true
                    ]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_personal_data.number_children', ['label' => 'Nº de filhos*', 'class' => 'form-control', 'type' => 'number', 'min' => 0, 'required' => true]) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_personal_data.mother_name', ['label' => 'Nome da mãe*', 'class' => 'form-control', 'required' => true]) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_personal_data.father_name', ['label' => 'Nome do pai*', 'class' => 'form-control', 'required' => true]) ?>
                </div>
            </div>

            <div id="divLegalRepresentative" style="display: none;" class="mt-3 p-3 border rounded bg-light">
                <h6>Dados do representante legal</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <?= $this->Form->control('adhesion_personal_data.name_legal_representative', ['label' => 'Nome*', 'class' => 'form-control']) ?>
                    </div>
                    <div class="col-md-4 mb-3">
                        <?= $this->Form->control('adhesion_personal_data.cpf_legal_representative', ['label' => 'CPF*', 'class' => 'form-control cpf']) ?>
                    </div>
                    <div class="col-md-4 mb-3">
                        <?= $this->Form->control('adhesion_personal_data.affiliation_legal_representative', ['label' => 'Filiação*', 'class' => 'form-control']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DOCUMENTOS -->
    <div id="documents" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('adhesion_document.type', [
                        'label' => 'Natureza do documento*',
                        'type' => 'select',
                        'options' => [
                            'Certificado de reservista' => 'Certificado de reservista',
                            'CNH' => 'CNH',
                            'CTPS' => 'CTPS',
                            'Passaporte' => 'Passaporte',
                            'RG' => 'RG',
                            'Outro' => 'Outro'
                        ],
                        'empty' => 'Selecione...',
                        'class' => 'form-select',
                        'required' => true
                    ]) ?>
                </div>
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('adhesion_document.document_number', ['label' => 'Nº do documento*', 'class' => 'form-control', 'required' => true]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <?php
                    $issueDate = $adhesion->adhesion_document->issue_date
                        ? $adhesion->adhesion_document->issue_date->format('Y-m-d')
                        : null;
                    echo $this->Form->label('adhesion_document.issue_date', 'Data de expedição*', ['class' => 'form-label']);
                    echo $this->Form->input('adhesion_document.issue_date', [
                        'class' => 'form-control',
                        'type' => 'date',
                        'required' => true,
                        'value' => $issueDate,
                        'max' => '9999-12-31'
                    ]) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_document.issuer', ['label' => 'Órgão expedidor*', 'class' => 'form-control', 'required' => true]) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_document.place_birth', ['label' => 'Naturalidade*', 'class' => 'form-control', 'required' => true]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- PLANO -->
    <div id="plan" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_plan.benefit_entry_age', ['label' => 'Idade para entrada em benefício', 'class' => 'form-control', 'type' => 'number']) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_plan.monthly_retirement_contribution', ['label' => 'Contribuição mensal aposentadoria', 'class' => 'form-control money', 'prepend' => 'R$', 'type' => 'text']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('adhesion_plan.monthly_survivors_pension_contribution', ['label' => 'Contribuição mensal pensão por morte', 'class' => 'form-control money', 'prepend' => 'R$', 'type' => 'text']) ?>
                </div>
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('adhesion_plan.survivors_pension_insured_capital', ['label' => 'Capital segurado pensão por morte', 'class' => 'form-control money', 'prepend' => 'R$', 'type' => 'text']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('adhesion_plan.monthly_disability_retirement_contribution', ['label' => 'Contribuição mensal aposentadoria por invalidez', 'class' => 'form-control money', 'prepend' => 'R$', 'type' => 'text']) ?>
                </div>
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('adhesion_plan.disability_retirement_insured_capital', ['label' => 'Capital segurado aposentadoria por invalidez', 'class' => 'form-control money', 'prepend' => 'R$', 'type' => 'text']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- BENEFICIÁRIOS (DEPENDENTS) -->
    <div id="dependents" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-success" onclick="addDependent();">Adicionar Beneficiário</button>
            </div>
            <div id="listDependents">
                <?php if (!empty($adhesion->adhesion_dependents)): ?>
                    <?php foreach ($adhesion->adhesion_dependents as $idx => $dep): ?>
                        <div class="dependentDiv border rounded p-3 mb-3 shadow-sm bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="h6 mb-0"><strong>Beneficiário <?= $idx + 1 ?></strong></div>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeDependent(this);">Remover</button>
                            </div>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <?= $this->Form->control("adhesion_dependents.$idx.name", ['label' => 'Nome*', 'class' => 'form-control']) ?>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <?= $this->Form->control("adhesion_dependents.$idx.kinship", [
                                        'label' => 'Parentesco*',
                                        'type' => 'select',
                                        'options' => [
                                            'Avô(ó)' => 'Avô(ó)',
                                            'Companheiro(a)' => 'Companheiro(a)',
                                            'Cônjuge' => 'Cônjuge',
                                            'Filho(a)' => 'Filho(a)',
                                            'Irmão(ã)' => 'Irmão(ã)',
                                            'Mãe' => 'Mãe',
                                            'Nenhum' => 'Nenhum',
                                            'Neto(a)' => 'Neto(a)',
                                            'Pai' => 'Pai',
                                            'Sobrinho(a)' => 'Sobrinho(a)',
                                            'Tio(a)' => 'Tio(a)'
                                        ],
                                        'empty' => 'Selecione...',
                                        'class' => 'form-select'
                                    ]) ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <?= $this->Form->control("adhesion_dependents.$idx.cpf", ['label' => 'CPF*', 'class' => 'form-control cpf']) ?>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <?php
                                    $birthDate = $adhesion->adhesion_dependents[$idx]->birth_date
                                        ? $adhesion->adhesion_dependents[$idx]->birth_date->format('Y-m-d')
                                        : null;
                                    echo $this->Form->label("adhesion_dependents.$idx.birth_date", 'Data de nasc.*', ['class' => 'form-label']);
                                    echo $this->Form->input("adhesion_dependents.$idx.birth_date", ['class' => 'form-control', 'type' => 'date', 'value' => $birthDate, 'max' => '9999-12-31']);
                                    ?>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <?= $this->Form->control("adhesion_dependents.$idx.participation", ['label' => 'Participação (%)*', 'class' => 'form-control participation', 'type' => 'number']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ENDEREÇO -->
    <div id="addressData" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">CEP*</label>
                    <div class="input-group">
                        <?= $this->Form->text('adhesion_address.cep', ['class' => 'form-control cep', 'onchange' => 'getCEP(this.value)', 'required' => true]) ?>
                        <span class="input-group-text cep-loading" style="display: none;">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                        </span>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-10 mb-3">
                    <?= $this->Form->control('adhesion_address.address', ['label' => 'Endereço*', 'class' => 'form-control', 'required' => true]) ?>
                </div>
                <div class="col-md-2 mb-3">
                    <?= $this->Form->control('adhesion_address.number', ['label' => 'Nº', 'class' => 'form-control']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('adhesion_address.complement', ['label' => 'Complemento', 'class' => 'form-control']) ?>
                </div>
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('adhesion_address.neighborhood', ['label' => 'Bairro*', 'class' => 'form-control', 'required' => true]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('adhesion_address.city', ['label' => 'Cidade*', 'class' => 'form-control', 'required' => true]) ?>
                </div>
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('adhesion_address.state', [
                        'label' => 'UF*',
                        'type' => 'select',
                        'options' => [
                            'AC' => 'Acre',
                            'AL' => 'Alagoas',
                            'AP' => 'Amapá',
                            'AM' => 'Amazonas',
                            'BA' => 'Bahia',
                            'CE' => 'Ceará',
                            'DF' => 'Distrito Federal',
                            'ES' => 'Espírito Santo',
                            'GO' => 'Goiás',
                            'MA' => 'Maranhão',
                            'MT' => 'Mato Grosso',
                            'MS' => 'Mato Grosso do Sul',
                            'MG' => 'Minas Gerais',
                            'PA' => 'Pará',
                            'PB' => 'Paraíba',
                            'PR' => 'Paraná',
                            'PE' => 'Pernambuco',
                            'PI' => 'Piauí',
                            'RJ' => 'Rio de Janeiro',
                            'RN' => 'Rio Grande do Norte',
                            'RS' => 'Rio Grande do Sul',
                            'RO' => 'Rondônia',
                            'RR' => 'Roraima',
                            'SC' => 'Santa Catarina',
                            'SP' => 'São Paulo',
                            'SE' => 'Sergipe',
                            'TO' => 'Tocantins'
                        ],
                        'empty' => 'Selecione...',
                        'class' => 'form-select',
                        'required' => true
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- OUTRAS INFORMAÇÕES -->
    <div id="otherInformation" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <div class="row">
                <div class="col-md-12 mb-3 occupation-search-container">
                    <label class="form-label">Ocupação principal*</label>
                    <?= $this->Form->hidden('adhesion_other_information.main_occupation_description', ['id' => 'mainOccupationDescription']) ?>
                    <?= $this->Form->hidden('adhesion_other_information.main_occupation_code', ['id' => 'mainOccupationCode']) ?>
                    <div class="input-group">
                        <input type="text" class="form-control" id="mainOccupationSearch" placeholder="Digite para buscar..." autocomplete="off" value="<?= h($adhesion->adhesion_other_information->main_occupation_description ?? '') ?>">
                        <span class="input-group-text" id="occupationLoading" style="display:none;">
                            <div class="spinner-border spinner-border-sm"></div>
                        </span>
                    </div>
                    <div id="occupationResults" class="list-group position-absolute w-100 shadow bg-white" style="max-height: 200px; overflow-y: auto; z-index: 1000; display:none;"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_other_information.category', [
                        'label' => 'Categoria*',
                        'type' => 'select',
                        'options' => [
                            'Aposentado' => 'Aposentado',
                            'Autônomo' => 'Autônomo',
                            'Empregado' => 'Empregado',
                            'Empregador' => 'Empregador',
                            'Servidor Público' => 'Servidor Público',
                            'Outros' => 'Outros'
                        ],
                        'empty' => 'Selecione...',
                        'class' => 'form-select'
                    ]) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_other_information.monthly_income', ['label' => 'Renda mensal bruta*', 'class' => 'form-control money', 'prepend' => 'R$', 'type' => 'text']) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_other_information.company', ['label' => 'Empresa*', 'class' => 'form-control']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Residente no Brasil?*</label><br>
                    <?= $this->Form->radio('adhesion_other_information.brazilian_resident', ['1' => 'Sim', '0' => 'Não']) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">PEP (Pessoa Politicamente Exposta)?*</label><br>
                    <?= $this->Form->radio('adhesion_other_information.politically_exposed', ['1' => 'Sim', '0' => 'Não'], ['onclick' => "showHide(this.value == '1', 'politicallyExposedObs')"]) ?>
                    <div id="politicallyExposedObs" style="display:none;" class="mt-2">
                        <?= $this->Form->control('adhesion_other_information.politically_exposed_obs', ['label' => 'Especificar PEP', 'class' => 'form-control']) ?>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Obrigações fiscais em outros países?*</label><br>
                    <?= $this->Form->radio('adhesion_other_information.obligation_other_countries', ['1' => 'Sim', '0' => 'Não']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- DECLARAÇÕES DO PROPONENTE -->
    <div id="proponentStatement" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <?php
            $statements = [
                'health_problem' => 'Encontra-se com algum problema de saúde ou faz uso de algum medicamento?',
                'heart_disease' => 'Sofre ou já sofreu de doenças do coração, hipertensão, circulatórias, etc?',
                'suffered_organ_defects' => 'Sofre ou sofreu de deficiências de órgãos, membros ou sentidos?',
                'surgery' => 'Fez alguma cirurgia, biópsia ou esteve internado nos últimos cinco anos?',
                'away' => 'Está afastado(a) do trabalho ou aposentado por invalidez?',
                'practices_parachuting' => 'Pratica paraquedismo, motociclismo, boxe, asa delta, etc?',
                'smoker' => 'É fumante?',
                'gripe' => 'Apresenta sintomas de gripe, febre, tosse, etc?',
                'covid' => 'Foi diagnosticado(a) com CORONA VÍRUS ou COVID-19?',
                'covid_sequelae' => 'Apresenta sequelas do COVID-19?'
            ];

            foreach ($statements as $field => $label): ?>
                <div class="mb-3 border-bottom pb-2">
                    <label class="form-label d-block text-wrap"><?= $label ?>*</label>
                    <?= $this->Form->radio("adhesion_proponent_statement.$field", ['1' => 'Sim', '0' => 'Não'], [
                        'onclick' => "showHide(this.value == '1', '{$field}Obs')"
                    ]) ?>
                    <div id="<?= $field ?>Obs" style="display:none;" class="mt-2">
                        <?php if ($field === 'smoker'): ?>
                            <div class="ms-3 mb-2">
                                <label class="form-label">Tipo de fumante</label><br>
                                <?= $this->Form->radio('adhesion_proponent_statement.smoker_type', ['1' => 'Cigarro', '0' => 'Outros'], ['onclick' => "showHide(this.value == '0', 'smokerTypeObs')"]) ?>
                                <div id="smokerTypeObs" style="display:none;" class="mt-2">
                                    <?= $this->Form->control('adhesion_proponent_statement.smoker_type_obs', ['label' => 'Especificar tipo', 'class' => 'form-control']) ?>
                                </div>
                            </div>
                            <div class="ms-3">
                                <?= $this->Form->control('adhesion_proponent_statement.smoker_qty', ['label' => 'Quantidade média/dia', 'class' => 'form-control']) ?>
                            </div>
                        <?php else: ?>
                            <?= $this->Form->control("adhesion_proponent_statement.{$field}_obs", ['label' => 'Especificar', 'class' => 'form-control']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="row mt-3">
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('adhesion_proponent_statement.weight', ['label' => 'Peso (Kg)', 'class' => 'form-control money', 'type' => 'text']) ?>
                </div>
                <div class="col-md-6 mb-3">
                    <?= $this->Form->control('adhesion_proponent_statement.height', ['label' => 'Altura (m)', 'class' => 'form-control money', 'type' => 'text']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- REGIME DE PREVIDÊNCIA -->
    <div id="pensionScheme" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <div class="mb-3">
                <label class="form-label">Você está em algum regime de previdência?*</label><br>
                <?= $this->Form->radio('adhesion_pension_scheme.any_pension_schema', ['1' => 'Sim', '0' => 'Não'], ['onclick' => 'pensionSchema(this.value == "1")']) ?>
            </div>
            <div id="pensionSchemeType" style="display: none;" class="mt-3 p-3 border rounded bg-light">
                <label id="pensionSchemeTypeLabel" class="form-label mb-3 fw-bold"></label>
                <div class="mb-3">
                    <?= $this->Form->radio('adhesion_pension_scheme.pension_scheme', [
                        'Geral (INSS)' => 'Geral (INSS)',
                        'Próprio (Servidor público)' => 'Próprio (Servidor público)',
                        'Complementar (Fundos de pensão)' => 'Complementar (Fundos de pensão)'
                    ]) ?>
                </div>
                <div id="pensionSchemeTypeKinship" style="display: none;">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <?= $this->Form->control('adhesion_pension_scheme.name', ['label' => 'Vinculado ao segurado*', 'class' => 'form-control']) ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <?= $this->Form->control('adhesion_pension_scheme.cpf', ['label' => 'CPF*', 'class' => 'form-control cpf']) ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <?= $this->Form->control('adhesion_pension_scheme.kinship', ['label' => 'Grau de parentesco*', 'class' => 'form-control']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DADOS PARA PAGAMENTO -->
    <div id="paymentDetail" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_payment_detail.due_date', ['label' => 'Dia do vencimento', 'class' => 'form-control', 'readonly' => true, 'value' => '10']) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <?= $this->Form->control('adhesion_payment_detail.total_contribution', ['label' => 'Total da contribuição', 'class' => 'form-control money', 'prepend' => 'R$', 'type' => 'text']) ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Meio de pagamento*</label><br>
                    <?= $this->Form->radio('adhesion_payment_detail.payment_type', [
                        'Débito em conta' => 'Débito em conta (BB)',
                        'Boleto bancário' => 'Boleto bancário'
                    ], ['onclick' => 'paymentType(this.value)']) ?>
                </div>
            </div>
            <div id="directDebitType" style="display: none;" class="mt-3 p-3 border rounded bg-light">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <?= $this->Form->control('adhesion_payment_detail.account_holder_name', ['label' => 'Nome do correntista', 'class' => 'form-control']) ?>
                    </div>
                    <div class="col-md-4 mb-3">
                        <?= $this->Form->control('adhesion_payment_detail.account_holder_cpf', ['label' => 'CPF do correntista', 'class' => 'form-control cpf']) ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <?= $this->Form->control('adhesion_payment_detail.bank_number', [
                            'label' => 'Banco',
                            'type' => 'select',
                            'options' => ['001' => 'Banco do Brasil'],
                            'default' => '001',
                            'class' => 'form-select'
                        ]) ?>
                    </div>
                    <div class="col-md-3 mb-3">
                        <?= $this->Form->control('adhesion_payment_detail.branch_number', ['label' => 'Agência', 'class' => 'form-control']) ?>
                    </div>
                    <div class="col-md-3 mb-3">
                        <?= $this->Form->control('adhesion_payment_detail.account_number', ['label' => 'Conta corrente', 'class' => 'form-control']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // JS específico para dinâmica de beneficiários na view
    const addDependent = () => {
        const count = $('#listDependents .dependentDiv').length;
        if (count >= 3) {
            alert('Limite de 3 beneficiários.');
            return;
        }

        const html = `
            <div class="dependentDiv border rounded p-3 mb-3 shadow-sm bg-light">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="h6 mb-0"><strong>Beneficiário ${count + 1}</strong></div>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeDependent(this);">Remover</button>
                </div>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Nome*</label>
                        <input type="text" name="adhesion_dependents[${count}][name]" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Parentesco*</label>
                        <select name="adhesion_dependents[${count}][kinship]" class="form-select">
                            <option value="">Selecione...</option>
                            <option value="Avô(ó)">Avô(ó)</option><option value="Companheiro(a)">Companheiro(a)</option>
                            <option value="Cônjuge">Cônjuge</option><option value="Filho(a)">Filho(a)</option>
                            <option value="Irmão(ã)">Irmão(ã)</option><option value="Mãe">Mãe</option>
                            <option value="Nenhum">Nenhum</option><option value="Neto(a)">Neto(a)</option>
                            <option value="Pai">Pai</option><option value="Sobrinho(a)">Sobrinho(a)</option>
                            <option value="Tio(a)">Tio(a)</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">CPF*</label>
                        <input type="text" name="adhesion_dependents[${count}][cpf]" class="form-control cpf">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Data de nasc.*</label>
                        <input type="date" name="adhesion_dependents[${count}][birth_date]" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Participação (%)*</label>
                        <input type="number" name="adhesion_dependents[${count}][participation]" class="form-control participation">
                    </div>
                </div>
            </div>
        `;
        $('#listDependents').append(html);
        $('.cpf').mask('000.000.000-00', {
            reverse: true
        });
    }

    const removeDependent = (element) => {
        $(element).closest('.dependentDiv').remove();
    }
</script>