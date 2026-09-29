<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\PlanParameter $planParameter
 */
$this->assign('title', 'Parâmetros do Plano');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">
        <i class="bi bi-sliders"></i> Parâmetros do Plano
    </h2>
</div>

<div class="container mt-4">
    <?= $this->Form->create($planParameter, ['class' => 'card p-4 shadow-sm']) ?>

    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <?= $this->Form->control('minimum_monthly_contribution', [
                    'label' => 'Contribuição mensal mínima (R$)*',
                    'class' => 'form-control money2',
                    'prepend' => 'R$',
                    'type' => 'text',
                    'required' => true,
                ]) ?>
                <div class="form-text">
                    Vale com ou sem risco contratado. Sem risco, o valor inteiro vai para a aposentadoria.
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <h5 class="fw-bold mb-3">Divisão da contribuição</h5>
    <p class="text-muted small">
        Percentual da contribuição mensal destinado a cada risco. A aposentadoria
        recebe o que sobra, então os dois somados precisam ficar abaixo de 100%.
    </p>

    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <?= $this->Form->control('survivors_pension_percent', [
                    'label' => 'Pensão por morte (%)*',
                    'class' => 'form-control percent',
                    'type' => 'text',
                    'required' => true,
                ]) ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="mb-3">
                <?= $this->Form->control('disability_retirement_percent', [
                    'label' => 'Aposentadoria por invalidez (%)*',
                    'class' => 'form-control percent',
                    'type' => 'text',
                    'required' => true,
                ]) ?>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <h5 class="fw-bold mb-3">Mínimos para ajuste manual</h5>
    <p class="text-muted small">
        O menor valor que pode ser digitado ao alterar a contribuição de um risco
        à mão, na edição de uma adesão. Não se aplica a risco removido, e não
        pode passar do que o percentual acima gera na contribuição mínima.
    </p>

    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <?= $this->Form->control('survivors_pension_floor', [
                    'label' => 'Mínimo da pensão por morte (R$)*',
                    'class' => 'form-control money2',
                    'prepend' => 'R$',
                    'type' => 'text',
                    'required' => true,
                ]) ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="mb-3">
                <?= $this->Form->control('disability_retirement_floor', [
                    'label' => 'Mínimo da invalidez (R$)*',
                    'class' => 'form-control money2',
                    'prepend' => 'R$',
                    'type' => 'text',
                    'required' => true,
                ]) ?>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-3 mb-0">
        <i class="bi bi-info-circle"></i>
        Alterar estes valores <strong>não modifica nenhuma adesão já gravada</strong>:
        cada adesão guarda os valores calculados no momento em que foi feita, e é
        de lá que o PDF é gerado.
    </div>

    <div class="text-end mt-3">
        <?= $this->Form->button('Salvar', ['class' => 'btn btn-success']) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
