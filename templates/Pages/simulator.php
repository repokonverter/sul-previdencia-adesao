<?php

/**
 * @var \App\View\AppView $this
 * @var string|null $csrfToken
 */

use Cake\Utility\Text;

// Define a variável para o token CSRF, se estiver disponível na view
// No CakePHP 5, você pode acessar o token CSRF do Request.
$csrfToken = $this->request->getAttribute('csrfToken');

// Define um nome para o asset de logo (assumindo que você o moveu para webroot/img/)
$logoAssetPath = 'logo_sul_transparente.png';
?>
<!DOCTYPE html>
<html>

<head>
    <title>SulPrev - Simulador de Previdência Privada</title>
    <?= $this->Html->script('https://cdn.jsdelivr.net/npm/chart.js', ['block' => 'scriptLibraries']) ?>

    <style>
        :root {
            --primary-color: rgb(252, 122, 41);
            --white: #FFFFFF;
            --dark-bg: #2C3E50;
            --text-color: #333333;
            --card-shadow: 0px 4px 24px rgba(0, 0, 0, 0.10);
        }

        body {
            background: #fff !important;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-x: hidden;
        }

        .simulador-popup {
            max-width: 950px;
            margin: 48px auto;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 4px 32px rgba(0, 0, 0, 0.10);
            padding: 0 0 32px 0;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1;
        }

        .simulador-header-bar {
            width: 100%;
            height: 10px;
            background: var(--primary-color);
            border-radius: 24px 24px 0 0;
            margin-bottom: 0;
        }

        .simulador-content {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 32px 0 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-sizing: border-box;
        }

        .simulador-header {
            width: 100%;
            display: flex;
            align-items: center;
            margin-bottom: 32px;
            background: none;
            height: auto;
            border-radius: 0;
        }

        .simulador-title {
            text-align: center;
            font-size: 2.2rem;
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 8px;
        }

        .simulador-subtitle {
            text-align: center;
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 24px;
        }

        .simulador-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .simulador-logo img {
            height: 40px;
        }

        .simulador-form {
            width: 100%;
            display: flex;
            gap: 24px;
            justify-content: center;
            margin-bottom: 32px;
            max-width: 600px;
        }

        .simulador-form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 180px;
            flex: 1;
        }

        .simulador-form label {
            font-size: 1rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .simulador-form input {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            background: #f8f8f8;
            color: var(--text-color);
        }

        .simulador-projeto-label {
            width: 100%;
            text-align: left;
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 12px;
            margin-top: 8px;
        }

        .simulador-main-row {
            width: 100%;
            display: flex;
            gap: 24px;
            justify-content: flex-start;
            align-items: flex-start;
            margin-bottom: 24px;
            overflow-x: auto;
            padding-bottom: 16px;
            -webkit-overflow-scrolling: touch;
        }

        .simulador-resultados-row {
            display: flex;
            gap: 24px;
            flex: 0 0 auto;
            min-width: min-content;
        }

        .simulador-grafico {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            padding: 16px;
            width: 320px;
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .simulador-obs {
            font-size: 0.95rem;
            color: #888;
            margin-top: 8px;
            text-align: left;
            width: 100%;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .simulador-btn {
            width: 100%;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 12px;
            padding: 18px 0;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 32px;
            margin-right: 0;
            align-self: unset;
            display: block;
        }

        .simulador-btn:hover {
            background: #e55d00;
        }

        @media (max-width: 1100px) {
            .simulador-main-row {
                justify-content: flex-start;
                padding-bottom: 24px;
            }

            .simulador-resultados-row {
                flex: 0 0 auto;
            }

            .simulador-grafico {
                flex: 0 0 auto;
                margin: 0;
            }

            .simulador-btn {
                margin-right: 0;
                align-self: center;
            }
        }

        @media (max-width: 600px) {
            .simulador-container {
                padding: 16px 4px;
            }

            .simulador-header {
                flex-direction: column;
                gap: 12px;
            }

            .simulador-title {
                font-size: 1.0rem;
            }

            .simulador-form {
                flex-direction: column;
                gap: 12px;
            }

            .simulador-main-row {
                flex-direction: column;
                align-items: center;
                gap: 0;
                overflow-x: unset;
                padding-bottom: 0;
            }

            .simulador-resultados-row {
                flex-direction: column;
                align-items: center;
                gap: 16px;
                width: 100%;
            }

            .card-simulador {
                width: 100%;
                min-width: unset;
                max-width: unset;
                margin-bottom: 12px;
            }

            .simulador-grafico-centralizado {
                width: 100%;
                margin-bottom: 16px;
            }

            .simulador-grafico {
                width: 100% !important;
                min-width: unset;
                max-width: unset;
                padding: 0;
            }

            .simulador-grafico canvas {
                width: 100% !important;
                height: auto !important;
                max-width: 100vw;
            }
        }

        @media (min-width: 601px) {
            .simulador-main-row {
                justify-content: center;
            }
        }

        .cards-container {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
        }

        .card-simulador {
            background: #fff;
            border-radius: 0 0 16px 16px;
            box-shadow: 0 2px 8px rgba(32, 68, 110, 0.08);
            width: 260px;
            flex: 0 0 auto;
            min-width: 260px;
            max-width: 260px;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            margin-bottom: 0;
            min-height: 400px;
        }

        .card-header {
            font-size: 1.05rem;
            font-weight: bold;
            color: #fff;
            padding: 12px;
            border-radius: 16px 16px 0 0;
            text-align: center;
        }

        .card-header.azul {
            background: #20446E;
        }

        .card-header.verde {
            background: #3B7A3B;
        }

        .card-body {
            padding: 16px 12px 16px 12px;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .valor-principal {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .valor-principal.azul {
            color: #20446E;
        }

        .valor-principal.verde {
            color: #3B7A3B;
        }

        .descricao-secundaria {
            font-size: 0.98rem;
            color: #6c757d;
            margin-top: 8px;
        }

        .cenarios-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
            flex: 1;
        }

        .cenario-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
            padding: 6px 8px;
            border-radius: 6px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }

        .cenario-item.rentabilidade-6 {
            border-left-color: #28a745;
        }

        .cenario-item.rentabilidade-8 {
            border-left-color: #ffc107;
        }

        .cenario-item.rentabilidade-10 {
            border-left-color: #dc3545;
        }

        .cenario-titulo {
            font-size: 0.8rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 1px;
        }

        .cenario-valor {
            font-size: 1.05rem;
            font-weight: bold;
            color: #20446E;
            line-height: 1.2;
        }

        .cenario-valor.verde {
            color: #3B7A3B;
        }

        .cenario-renda {
            font-size: 0.8rem;
            color: #6c757d;
            line-height: 1.1;
        }

        .simulador-main-row::-webkit-scrollbar {
            height: 8px;
        }

        .simulador-main-row::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 4px;
        }

        .simulador-main-row::-webkit-scrollbar-thumb {
            background-color: var(--primary-color);
            border-radius: 4px;
        }

        .simulador-grafico-centralizado {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 16px;
        }
    </style>
</head>

<body style="background: #fff;">
    <div class="simulador-popup">
        <div class="simulador-header-bar"></div>
        <div class="simulador-content">
            <div class="simulador-header">
                <div class="simulador-logo">
                    <button onclick="window.history.back()" style="background:none;border:none;cursor:pointer;padding:0;margin-right:8px;">
                        <span style="font-size:2rem;color:var(--primary-color);">←</span>
                    </button>
                    <?= $this->Html->image($logoAssetPath, ['alt' => 'Sul Previdencia']) ?>
                </div>
                <div class="simulador-title">Bem-vindo à Adesão Digital da Sul Previdencia</div>
            </div>
            <div class="simulador-subtitle">
                Informe sua data de nascimento e quanto gostaria de investir mensalmente:
            </div>
            <form class="simulador-form" id="simulador-form">
                <div class="simulador-form-group">
                    <label for="data-nascimento">Data de Nascimento</label>
                    <input type="date" id="data-nascimento" required />
                </div>
                <div class="simulador-form-group">
                    <label for="valor-investimento">Investimento Total Mensal</label>
                    <input type="number" id="valor-investimento" min="1" step="0.01" placeholder="R$ 100,00" required />
                </div>
            </form>
            <div class="simulador-projeto-label"></div>
            <div class="simulador-main-row">
                <div class="simulador-resultados-row">
                    <div class="card-simulador">
                        <div class="card-header azul">
                            Patrimônio | Previdência
                        </div>
                        <div class="card-body">
                            <div class="cenarios-container" id="patrimonio-cenarios"></div>
                            <div class="descricao-secundaria" id="patrimonio-contribuicao"></div>
                        </div>
                    </div>
                    <div class="card-simulador">
                        <div class="card-header verde">
                            Pensão por Morte
                        </div>
                        <div class="card-body">
                            <div class="cenarios-container" id="seguro-morte-cenarios"></div>
                            <div class="descricao-secundaria" id="seguro-morte-contribuicao"></div>
                        </div>
                    </div>
                    <div class="card-simulador">
                        <div class="card-header verde">
                            Aposentadoria por Invalidez
                        </div>
                        <div class="card-body">
                            <div class="cenarios-container" id="seguro-invalidez-cenarios"></div>
                            <div class="descricao-secundaria" id="seguro-invalidez-contribuicao"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="simulador-grafico-centralizado">
                <div class="simulador-grafico">
                    <canvas id="simulador-chart" width="320" height="220"></canvas>
                </div>
            </div>
            <div class="simulador-obs">
                * Os valores acima são apenas estimativas e não garantem nenhum direito antecipado. Saiba mais...
            </div>
            <button class="simulador-btn" id="simulador-continuar" onclick="window.location.href='<?= $this->Url->build(['controller' => 'SeuController', 'action' => 'alguma_acao', '?' => ['cadastro' => 1]]) ?>'">
                Gostei, quero continuar <span style="font-size:1.3em;vertical-align:middle;">→</span>
            </button>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Verifica se há dados da simulação mais recente (sessionStorage tem prioridade absoluta)
            const simulationInputs = JSON.parse(sessionStorage.getItem('simulationInputs'));
            const simulationResults = JSON.parse(sessionStorage.getItem('simulationResults'));

            let dataNascimentoFinal = '';
            let valorInvestimentoFinal = '';

            // Se tem resultados de simulação, significa que veio de uma simulação recente
            if (simulationInputs && simulationResults) {
                // Usa os dados da simulação mais recente
                dataNascimentoFinal = simulationInputs.data_nascimento || '';
                valorInvestimentoFinal = simulationInputs.valor_investimento || '';

                // Atualiza também o localStorage para manter sincronia
                if (dataNascimentoFinal) localStorage.setItem('simulador_data_nascimento', dataNascimentoFinal);
                if (valorInvestimentoFinal) localStorage.setItem('simulador_valor_mensal', valorInvestimentoFinal);
            } else {
                // Fallback para localStorage apenas se não há dados recentes
                dataNascimentoFinal = localStorage.getItem('simulador_data_nascimento') || '';
                valorInvestimentoFinal = localStorage.getItem('simulador_valor_mensal') || '';
            }

            // Preenche os campos com os valores finais
            if (dataNascimentoFinal) {
                document.getElementById('data-nascimento').value = dataNascimentoFinal;
            }
            if (valorInvestimentoFinal) {
                document.getElementById('valor-investimento').value = valorInvestimentoFinal;
            }
            // simulationResults já foi declarado acima
            if (simulationResults && simulationResults.length > 0) {
                // Função para formatar valores monetários
                function formatarMoeda(valor) {
                    return parseFloat(valor || 0).toLocaleString('pt-BR', {
                        minimumFractionDigits: 2
                    });
                }

                // Função para formatar taxa de rentabilidade
                function formatarTaxa(taxa) {
                    return (parseFloat(taxa || 0) * 100).toFixed(0) + '%';
                }

                // Função para criar cenário HTML
                function criarCenarioHTML(resultado, tipo) {
                    const taxa = formatarTaxa(resultado.taxa_rentabilidade_anual);
                    const classeRentabilidade = `rentabilidade-${(parseFloat(resultado.taxa_rentabilidade_anual) * 100).toFixed(0)}`;

                    let valorPrincipal, valorRenda;
                    if (tipo === 'patrimonio') {
                        valorPrincipal = formatarMoeda(resultado.saldo_acumulado);
                        valorRenda = formatarMoeda(resultado.beneficio_mensal);
                    } else if (tipo === 'morte') {
                        valorPrincipal = formatarMoeda(resultado.cobertura_morte);
                        valorRenda = formatarMoeda(resultado.renda_morte_mensal);
                    } else if (tipo === 'invalidez') {
                        valorPrincipal = formatarMoeda(resultado.cobertura_invalidez);
                        valorRenda = formatarMoeda(resultado.renda_invalidez_mensal);
                    }

                    return `
            <div class="cenario-item ${classeRentabilidade}">
              <div class="cenario-titulo">Rentabilidade ${taxa}</div>
              <div class="cenario-valor ${tipo === 'patrimonio' ? '' : 'verde'}">R$ ${valorPrincipal}</div>
              <div class="cenario-renda">Renda Mensal: R$ ${valorRenda}</div>
            </div>
          `;
                }

                // Função para criar card de seguro (sem rentabilidade)
                function criarCardSeguroHTML(resultado, tipo) {
                    let valorPrincipal, valorRenda;
                    if (tipo === 'morte') {
                        valorPrincipal = formatarMoeda(resultado.cobertura_morte);
                        valorRenda = formatarMoeda(resultado.renda_morte_mensal);
                    } else if (tipo === 'invalidez') {
                        valorPrincipal = formatarMoeda(resultado.cobertura_invalidez);
                        valorRenda = formatarMoeda(resultado.renda_invalidez_mensal);
                    }

                    return `
            <div style="text-align: center; padding: 16px 0;">
              <div style="font-size: 1.8rem; font-weight: bold; color: #3B7A3B; margin-bottom: 12px;">R$ ${valorPrincipal}</div>
              <div style="font-size: 1rem; color: #6c757d;">Renda Mensal: R$ ${valorRenda}</div>
            </div>
          `;
                }

                // Processa todos os cenários
                let patrimonioCenarios = '';
                let morteCenarios = '';
                let invalidezCenarios = '';

                // Para Patrimônio, exibe todos os cenários de rentabilidade
                simulationResults.forEach(resultado => {
                    patrimonioCenarios += criarCenarioHTML(resultado, 'patrimonio');
                });

                // Para Morte e Invalidez, exibe apenas um valor (primeiro resultado)
                // pois os valores são iguais independente da rentabilidade
                if (simulationResults.length > 0) {
                    morteCenarios = criarCardSeguroHTML(simulationResults[0], 'morte');
                    invalidezCenarios = criarCardSeguroHTML(simulationResults[0], 'invalidez');
                }

                // Exibe os cenários nos cards
                document.getElementById('patrimonio-cenarios').innerHTML = patrimonioCenarios;
                document.getElementById('seguro-morte-cenarios').innerHTML = morteCenarios;
                document.getElementById('seguro-invalidez-cenarios').innerHTML = invalidezCenarios;

                // Usa o primeiro resultado para exibir as contribuições (são iguais em todos os cenários)
                const primeiroResultado = simulationResults[0];
                document.getElementById('patrimonio-contribuicao').innerText = `Contribuição Mensal: R$ ${formatarMoeda(primeiroResultado.contribuicao_aposentadoria)}`;
                document.getElementById('seguro-morte-contribuicao').innerText = `Contribuição Mensal: R$ ${formatarMoeda(primeiroResultado.contribuicao_morte)}`;
                document.getElementById('seguro-invalidez-contribuicao').innerText = `Contribuição Mensal: R$ ${formatarMoeda(primeiroResultado.contribuicao_invalidez)}`;

                // Gráfico (mantido inalterado)
                const ctx = document.getElementById('simulador-chart').getContext('2d');
                if (window.simuladorChart) window.simuladorChart.destroy();
                window.simuladorChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Adesão', '', '', '', '', '65 anos'],
                        datasets: [{
                                label: 'Patrimônio',
                                data: [0, 50, 90, 130, 180, 226.4],
                                borderColor: '#FF6200',
                                backgroundColor: 'rgba(255,98,0,0.10)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Seguro Morte',
                                data: [111.8, 111.8, 111.8, 111.8, 111.8, 111.8],
                                borderColor: '#1abc9c',
                                backgroundColor: 'rgba(26,188,156,0.10)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Seguro Invalidez',
                                data: [125.5, 125.5, 125.5, 125.5, 125.5, 125.5],
                                borderColor: '#1abc9c',
                                backgroundColor: 'rgba(26,188,156,0.10)',
                                fill: true,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                title: {
                                    display: false
                                }
                            },
                            y: {
                                title: {
                                    display: false
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        });

        document.getElementById('simulador-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const dataNascimento = document.getElementById('data-nascimento').value;
            const valorInvestimento = document.getElementById('valor-investimento').value;

            // Salva no sessionStorage
            sessionStorage.setItem('simulationInputs', JSON.stringify({
                data_nascimento: dataNascimento,
                valor_investimento: valorInvestimento
            }));

            // Atualiza o localStorage com os novos valores antes de processar
            localStorage.setItem('simulador_data_nascimento', dataNascimento);
            localStorage.setItem('simulador_valor_mensal', valorInvestimento);

            // Chama o backend para recalcular
            // URL de destino para o CakePHP (ajuste o Controller/Action conforme sua rota)
            const submitUrl = '<?= $this->Url->build(['controller' => 'Simulador', 'action' => 'simular', '_ext' => 'json']) ?>';

            fetch(submitUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        // Inclui o token CSRF do CakePHP
                        'X-CSRF-Token': '<?= $csrfToken ?>'
                    },
                    body: JSON.stringify({
                        data_nascimento: dataNascimento,
                        valor_investimento: valorInvestimento
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // data já é um array, salve ele diretamente
                    sessionStorage.setItem('simulationResults', JSON.stringify(data));
                    // Atualiza os valores nos inputs também
                    sessionStorage.setItem('simulationInputs', JSON.stringify({
                        data_nascimento: dataNascimento,
                        valor_investimento: valorInvestimento
                    }));
                    window.location.reload();
                })
                .catch(error => {
                    alert('Erro ao calcular simulação!');
                    console.error(error);
                });
        });
    </script>
</body>

</html>