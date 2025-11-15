<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">
        <i class="bi bi-pencil-square"></i> Editar Cliente
    </h2>

    <?= $this->Html->link(
        '<i class="bi bi-arrow-left"></i> Voltar',
        ['action' => 'view', $adhesion->id],
        ['escape' => false, 'class' => 'btn btn-outline-secondary']
    ) ?>
</div>

<?= $this->Form->create($adhesion) ?>

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#dados-iniciais">Dados Iniciais</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dados-pessoais">Dados Pessoais</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#endereco">Endereço</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dependentes">Dependentes</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#plano">Plano</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#outras-infos">Outras informações</a></li>
</ul>

<div class="tab-content">

    <!-- DADOS INICIAIS -->
    <div id="dados-iniciais" class="tab-pane fade show active">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-info-circle"></i> Dados Iniciais</h5>

            <?= $this->Form->control('initial_data.email', [
                'label' => 'E-mail', 'class' => 'form-control'
            ]) ?>
            <?= $this->Form->control('initial_data.phone', [
                'label' => 'Telefone', 'class' => 'form-control mt-2'
            ]) ?>
        </div>
    </div>

    <!-- DADOS PESSOAIS -->
    <div id="dados-pessoais" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-person"></i> Dados Pessoais</h5>

            <?= $this->Form->control('personal_data.full_name', ['label'=>'Nome completo','class'=>'form-control']) ?>
            <?= $this->Form->control('personal_data.cpf', ['label'=>'CPF','class'=>'form-control mt-2']) ?>
            <?= $this->Form->control('personal_data.rg', ['label'=>'RG','class'=>'form-control mt-2']) ?>
            <?= $this->Form->control('personal_data.birth_date', ['label'=>'Data de nascimento','type'=>'date','class'=>'form-control mt-2']) ?>
            <?= $this->Form->control('personal_data.marital_status', ['label'=>'Estado civil','class'=>'form-control mt-2']) ?>
        </div>
    </div>

    <!-- ENDERECO -->
    <div id="endereco" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-geo-alt"></i> Endereço</h5>

            <?= $this->Form->control('address.zip_code', ['label'=>'CEP','class'=>'form-control']) ?>
            <?= $this->Form->control('address.street', ['label'=>'Rua','class'=>'form-control mt-2']) ?>
            <?= $this->Form->control('address.number', ['label'=>'Número','class'=>'form-control mt-2']) ?>
            <?= $this->Form->control('address.district', ['label'=>'Bairro','class'=>'form-control mt-2']) ?>
            <?= $this->Form->control('address.city', ['label'=>'Cidade','class'=>'form-control mt-2']) ?>
            <?= $this->Form->control('address.state', ['label'=>'Estado','class'=>'form-control mt-2']) ?>
            <?= $this->Form->control('address.complement', ['label'=>'Complemento','class'=>'form-control mt-2']) ?>
        </div>
    </div>

    <!-- DEPENDENTES -->
    <div id="dependentes" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-people"></i> Dependentes</h5>

            <?php if (!empty($adhesion->dependents)): ?>
                <?php foreach ($adhesion->dependents as $idx => $dep): ?>
                    <div class="border p-3 mb-3 rounded">
                        <strong>Dependente <?= $idx+1 ?></strong>
                        <?= $this->Form->control("dependents.$idx.name", ['label'=>'Nome','class'=>'form-control mt-2']) ?>
                        <?= $this->Form->control("dependents.$idx.relationship", ['label'=>'Parentesco','class'=>'form-control mt-2']) ?>
                        <?= $this->Form->control("dependents.$idx.birth_date", ['label'=>'Nascimento','type'=>'date','class'=>'form-control mt-2']) ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nenhum dependente cadastrado.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- PLANO -->
    <div id="plano" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-briefcase"></i> Plano</h5>

            <?= $this->Form->control('plan.plan_name', ['label'=>'Nome do plano','class'=>'form-control']) ?>
            <?= $this->Form->control('plan.ans_code', ['label'=>'Código ANS','class'=>'form-control mt-2']) ?>
        </div>
    </div>

    <!-- OUTRAS INFOS -->
    <div id="outras-infos" class="tab-pane fade">
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-chat-dots"></i> Outras Informações</h5>

            <?= $this->Form->control('other_information.notes', [
                'label' => 'Observações', 'type' => 'textarea', 'class' => 'form-control'
            ]) ?>
        </div>
    </div>

</div>

<div class="mt-4">
    <?= $this->Form->button('<i class="bi bi-check-lg"></i> Salvar Alterações', [
        'escapeTitle' => false,
        'class' => 'btn btn-success btn-lg px-4'
    ]) ?>
</div>

<?= $this->Form->end() ?>
