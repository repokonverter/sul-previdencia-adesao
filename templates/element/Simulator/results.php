<?php

/**
 * Os cards de resultado do simulador: patrimônio nos três cenários de
 * rentabilidade e as duas coberturas de risco.
 *
 * Elemento porque é desenhado em dois lugares: na página, e pelo
 * SimulatorController::recalculate(), que devolve este HTML quando a data de
 * nascimento ou o investimento mudam dentro do modal -- sem isso os cards
 * continuavam mostrando a simulação da home enquanto o plano gravado era de
 * outra idade.
 *
 * @var \App\View\AppView $this
 * @var array<int, array<string, mixed>> $simulations
 */

// Closures, e não declarações: este elemento é renderizado pela página do
// simulador e de novo pelo recalculate(), às vezes no mesmo processo.
$createScenario = function ($data, $type) {
    $annualProfitabilityRate = floor($data['taxa_rentabilidade_anual'] * 100);

    switch ($type) {
        case 'death':
            $mainValue = $data['cobertura_morte'];
            $incomeValue = $data['renda_morte_mensal'];
            break;
        case 'disability':
            $mainValue = $data['cobertura_invalidez'];
            $incomeValue = $data['renda_invalidez_mensal'];
            break;
        default:
            $mainValue = $data['saldo_acumulado'];
            $incomeValue = $data['beneficio_mensal'];
            break;
    }

    return '
        <div class="cenario-item rentabilidade-' . $annualProfitabilityRate . '">
            <div class="cenario-titulo">Rentabilidade ' . $annualProfitabilityRate . '%</div>
            <div class="cenario-valor ' . ($type === 'property' ? '' : 'verde') . '">' . $this->Utils->currency($mainValue) . '</div>
            <div class="cenario-renda">Renda Mensal: ' . $this->Utils->currency($incomeValue) . '</div>
        </div>
    ';
};

$createSecureCard = function ($data, $type) {
    switch ($type) {
        case 'death':
            $mainValue = $data['cobertura_morte'];
            $incomeValue = $data['renda_morte_mensal'];
            break;
        case 'disability':
            $mainValue = $data['cobertura_invalidez'];
            $incomeValue = $data['renda_invalidez_mensal'];
            break;
    }

    return '
        <div style="text-align: center; padding: 16px 0;">
            <div style="font-size: 1.8rem; font-weight: bold; color: #3B7A3B; margin-bottom: 12px;">' . $this->Utils->currency($mainValue) . '</div>
            <div style="font-size: 1rem; color: #6c757d;">Renda Mensal: ' . $this->Utils->currency($incomeValue) . '</div>
        </div>
    ';
};
?>
<div class="simulador-resultados-row">
    <div class="card-simulador">
        <div class="card-header azul">
            Patrimônio | Previdência
        </div>
        <div class="card-body">
            <div class="cenarios-container" id="patrimonio-cenarios">
                <?php
                foreach ($simulations as $simulation) {
                    echo $createScenario($simulation, 'property');
                }
                ?>
            </div>
            <div class="descricao-secundaria" id="patrimonio-contribuicao">
                Contribuição Mensal<br>
                <?= $this->Utils->currency($simulations[1]['contribuicao_aposentadoria']); ?>
            </div>
        </div>
    </div>
    <div class="card-simulador">
        <div class="card-header verde">
            Pensão por Morte
        </div>
        <div class="card-body">
            <div class="cenarios-container" id="seguro-morte-cenarios"><?= $createSecureCard($simulations[1], 'death'); ?></div>
            <div class="descricao-secundaria" id="seguro-morte-contribuicao">
                Contribuição Mensal<br>
                <?= $this->Utils->currency($simulations[1]['contribuicao_morte']); ?>
            </div>
        </div>
    </div>
    <div class="card-simulador">
        <div class="card-header verde">
            Aposentadoria por Invalidez
        </div>
        <div class="card-body">
            <div class="cenarios-container" id="seguro-invalidez-cenarios"><?= $createSecureCard($simulations[1], 'disability'); ?></div>
            <div class="descricao-secundaria" id="seguro-invalidez-contribuicao">
                Contribuição Mensal<br>
                <?= $this->Utils->currency($simulations[1]['contribuicao_invalidez']); ?>
            </div>
        </div>
    </div>
</div>
