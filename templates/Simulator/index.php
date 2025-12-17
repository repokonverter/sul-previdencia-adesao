<?php

/**
 * @var \App\View\AppView $this
 * @var string|null $csrfToken
 */

use Cake\I18n\Number;

$this->assign('title', 'Sul Previdência - Simulador');

$csrfToken = $this->request->getAttribute('csrfToken');
$logoAssetPath = 'logo_sul_transparente.png';

function createScenario($data, $type)
{
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
            <div class="cenario-valor ' . ($type === 'property' ? '' : 'verde') . '">' . Number::currency($mainValue) . '</div>
            <div class="cenario-renda">Renda Mensal: ' . Number::currency($incomeValue) . '</div>
        </div>
    ';
}

function createSecureCard($data, $type)
{
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
            <div style="font-size: 1.8rem; font-weight: bold; color: #3B7A3B; margin-bottom: 12px;">' . Number::currency($mainValue) . '</div>
            <div style="font-size: 1rem; color: #6c757d;">Renda Mensal: ' . Number::currency($incomeValue) . '</div>
        </div>
    ';
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>SulPrev - Simulador de Previdência Privada</title>

    <?= $this->Html->script('https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js', ['block' => 'script']) ?>

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
            justify-content: space-between;
            margin-bottom: 16px;
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

        .logo-sul-prev {
            height: 100px;
        }

        .simulador-form {
            width: 100%;
            display: flex;
            gap: 24px;
            justify-content: center;
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
            text-align: center;
            font-size: 1.45rem;
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
            width: 90%;
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
            border-radius: 16px 16px 0 0 !important;
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
                    <button onclick="location.href='<?= $this->Url->build(['controller' => 'Pages', 'action' => 'display']); ?>'" style="background:none;border:none;cursor:pointer;padding:0;margin-right:8px;">
                        <span style="font-size:2rem;color:var(--primary-color);">←</span>
                    </button>
                </div>
                <div class="simulador-title">Simulação</div>
                <div><?= $this->Html->image($logoAssetPath, ['alt' => 'Sul Previdencia', 'class' => 'logo-sul-prev']) ?></div>
            </div>
            <div class="simulador-subtitle">
                Informe sua data de nascimento e quanto gostaria de investir mensalmente:
            </div>
            <form class="simulador-form" id="simulador-form">
                <div class="simulador-form-group">
                    <label for="data-nascimento">Data de nascimento</label>
                    <input type="date" max="9999-12-31" class="form-control" name="dateBirth" placeholder="XX/XX/XXXX" value="<?= $_GET['date']; ?>" required>
                </div>
                <div class="simulador-form-group">
                    <label for="valor-investimento">Investimento mensal</label>
                    <input type="text" class="form-control money" name="monthlyInvestment" placeholder="Investimento mensal" value="<?= $_GET['value']; ?>" required>
                </div>
            </form>
            <button class="simulador-btn" style="margin-bottom: 2rem;" onclick="simulate();">
                Simular novamente
            </button>
            <div class="simulador-projeto-label">Resultado</div>
            <div class="simulador-main-row">
                <div class="simulador-resultados-row">
                    <div class="card-simulador">
                        <div class="card-header azul">
                            Patrimônio | Previdência
                        </div>
                        <div class="card-body">
                            <div class="cenarios-container" id="patrimonio-cenarios">
                                <?php
                                foreach ($simulations as $simulation) {
                                    echo createScenario($simulation, 'property');
                                }
                                ?>
                            </div>
                            <div class="descricao-secundaria" id="patrimonio-contribuicao">
                                Contribuição Mensal<br>
                                <?= Number::currency($simulations[1]['contribuicao_aposentadoria']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-simulador">
                        <div class="card-header verde">
                            Pensão por Morte
                        </div>
                        <div class="card-body">
                            <div class="cenarios-container" id="seguro-morte-cenarios"><?= createSecureCard($simulations[1], 'death'); ?></div>
                            <div class="descricao-secundaria" id="seguro-morte-contribuicao">
                                Contribuição Mensal<br>
                                <?= Number::currency($simulations[1]['contribuicao_morte']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-simulador">
                        <div class="card-header verde">
                            Aposentadoria por Invalidez
                        </div>
                        <div class="card-body">
                            <div class="cenarios-container" id="seguro-invalidez-cenarios"><?= createSecureCard($simulations[1], 'disability'); ?></div>
                            <div class="descricao-secundaria" id="seguro-invalidez-contribuicao">
                                Contribuição Mensal<br>
                                <?= Number::currency($simulations[1]['contribuicao_invalidez']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="simulador-projeto-label">Evolução Comparativa das Contribuições</div>
            <div class="simulador-grafico-centralizado">
                <div class="simulador-grafico">
                    <canvas id="simulador-chart" height="300"></canvas>
                </div>
            </div>
            <div class="simulador-obs">
                <p><strong>*Os valores acima são apenas estimativas de capital acumulado aos 65 anos e não garantem nenhum direito antecipado.</strong></p>
            </div>
            <button class="simulador-btn" id="simulador-continuar">
                Gostei, quero continuar <span style="font-size:1.3em;vertical-align:middle;">→</span>
            </button>
        </div>
    </div>
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="registerModalLabel">Adesão</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <h4 class="mb-3"></h4>

                    <form id="registerModalForm" novalidate>
                        <div id="initialData" class="hidden">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nome completo*</label>
                                <input type="text" class="form-control" name="initialData[name]" placeholder="Nome completo" required>
                                <div class="invalid-feedback">
                                    Preenchimento obrigatório.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" name="initialData[email]" placeholder="E-mail" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Celular*</label>
                                <input type="text" class="form-control phone" name="initialData[phone]" placeholder="(XX) XXXXX-XXXX" required>
                                <div class="invalid-feedback">
                                    Preenchimento obrigatório.
                                </div>
                            </div>
                            <p class="form-text">
                                Em conformidade com a Lei Geral de Proteção de Dados (LGPD), informamos que os dados fornecidos serão
                                armazenados em nosso sistema e utilizados exclusivamente para fins de pesquisa de satisfação e suporte ao longo do processo.
                                Ao clicar em “Concordo”, você concorda com o uso dessas informações para que possamos entrar em contato, caso necessário,
                                para esclarecer dúvidas ou auxiliar em eventuais impedimentos.
                            </p>
                        </div>

                        <div id="personalData" class="hidden">
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="planFor" class="form-label">O plano é para*</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" id="planForD" name="personalData[planFor]" value="Dependente" onclick="planForHandle(this)">
                                            <label class="form-check-label" for="planForD">Dependente</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" id="planForT" name="personalData[planFor]" value="Titular" onclick="planForHandle(this)" checked>
                                            <label class="form-check-label" for="planForT">Titular</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nome completo*</label>
                                        <input type="text" class="form-control" name="personalData[name]" placeholder="Nome completo" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="cpf" class="form-label">CPF*</label>
                                        <input type="text" class="form-control cpf" name="personalData[cpf]" placeholder="CPF" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="birthDate" class="form-label">Data de nasc.*</label>
                                        <input type="date" max="9999-12-31" class="form-control" name="personalData[birthDate]" placeholder="Data de nascimento" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="nacionality" class="form-label">Nacionalidade</label>
                                        <input type="text" class="form-control" name="personalData[nacionality]" placeholder="Nacionalidade">
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="gender" class="form-label">Gênero de nasc.*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="genderF" name="personalData[gender]" value="F" required>
                                            <label class="form-check-label" for="genderF">Feminino</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" id="genderM" name="personalData[gender]" value="M" required>
                                            <label class="form-check-label" for="genderM">Masculino</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="maritalStatus" class="form-label">Estado civil*</label>
                                        <select class="form-select" name="personalData[maritalStatus]" required>
                                            <option value="">Selecione...</option>
                                            <option value="Casado">Casado</option>
                                            <option value="Divorciado">Divorciado</option>
                                            <option value="Separado">Separado</option>
                                            <option value="Solteiro">Solteiro</option>
                                            <option value="União estável">União estável</option>
                                            <option value="Viúvo">Viúvo</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="numberChildren" class="form-label">Nº de filhos*</label>
                                        <input type="number" min="0" class="form-control" name="personalData[numberChildren]" placeholder="Nº de filhos" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="motherName" class="form-label">Nome da mãe*</label>
                                        <input type="text" class="form-control" name="personalData[motherName]" placeholder="Nome da mãe" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="fatherName" class="form-label">Nome do pai*</label>
                                        <input type="text" class="form-control" name="personalData[fatherName]" placeholder="Nome do pai" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row hidden" id="divLegalRepresentative" style="display: none;">
                                <blockquote class="blockquote">
                                    <p>Dados do representante legal</p>
                                </blockquote>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="nameLegalRepresentative" class="form-label">Nome*</label>
                                        <input type="text" class="form-control" name="personalData[nameLegalRepresentative]" placeholder="Nome">
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="cpfLegalRepresentative" class="form-label">CPF*</label>
                                        <input type="text" class="form-control cpf" name="personalData[cpfLegalRepresentative]" placeholder="CPF">
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="affiliationLegalRepresentative" class="form-label">Filiação*</label>
                                        <input type="text" class="form-control" name="personalData[affiliationLegalRepresentative]" placeholder="Filiação">
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="documents" class="hidden">
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="documentType" class="form-label">Natureza do documento*</label>
                                        <select class="form-select" name="documents[documentType]" required>
                                            <option value="">Selecione...</option>
                                            <option value="Certificado de reservista">Certificado de reservista</option>
                                            <option value="CNH">CNH</option>
                                            <option value="CTPS">CTPS</option>
                                            <option value="Passaporte">Passaporte</option>
                                            <option value="RG">RG</option>
                                            <option value="Outro">Outro</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="documentNumber" class="form-label">Nº do documento*</label>
                                        <input type="text" class="form-control" name="documents[documentNumber]" placeholder="Nº do documento" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="issueDate" class="form-label">Data de expedição*</label>
                                        <input type="date" max="9999-12-31" class="form-control" name="documents[issueDate]" placeholder="Data de expedição" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="issuer" class="form-label">Órgão expedidor*</label>
                                        <input type="text" class="form-control" name="documents[issuer]" placeholder="Órgão expedidor" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="placeBirth" class="form-label">Naturalidade*</label>
                                        <input type="text" class="form-control" name="documents[placeBirth]" placeholder="Naturalidade" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="plan" class="hidden">
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="benefitEntryAge" class="form-label">Idade para entrada em benefício</label>
                                        <input type="number" class="form-control" name="plans[benefitEntryAge]" placeholder="Idade para entrada em benefício" readonly>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="monthly_retirement_contribution" class="form-label">Contribuição mensal aposentadoria</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control money" name="plans[monthly_retirement_contribution]" value="<?= number_format($simulations[1]['contribuicao_aposentadoria'], 2, '.', ''); ?>" readonly>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="monthly_survivors_pension_contribution" class="form-label">Contribuição mensal pensão por morte</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control money" name="plans[monthly_survivors_pension_contribution]" value="<?= number_format($simulations[1]['contribuicao_morte'], 2, '.', ''); ?>" readonly>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="survivors_pension_insured_capital" class="form-label">Capital segurado pensão por morte</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control money" name="plans[survivors_pension_insured_capital]" value="<?= number_format($simulations[1]['cobertura_morte'], 2, '.', ''); ?>" readonly>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="monthly_disability_retirement_contribution" class="form-label">Contribuição mensal aposentadoria por invalidez</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control money" name="plans[monthly_disability_retirement_contribution]" value="<?= number_format($simulations[1]['contribuicao_invalidez'], 2, '.', ''); ?>" readonly>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="disability_retirement_insured_capital" class="form-label">Capital segurado aposentadoria por invalidez</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control money" name="plans[disability_retirement_insured_capital]" value="<?= number_format($simulations[1]['cobertura_invalidez'], 2, '.', ''); ?>" readonly>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col text-center">
                                    <div>Total de contribuição mensal</div>
                                    <div><?= Number::currency($totalMonthlyContributionPlan, null); ?></div>
                                </div>
                            </div>
                        </div>

                        <div id="dependents" class="hidden">
                            <div class="row">
                                <div class="col d-flex justify-content-end"><button type="button" class="btn btn-success" onclick="addDependent();">Adicionar</button></div>
                            </div>
                            <div id="listDependents"></div>
                        </div>

                        <div id="addressData" class="hidden">
                            <div class="row">
                                <div class="col-8">
                                    <label for="cep" class="form-label">CEP*</label>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control cep" name="addresses[cep]" placeholder="CEP" onchange="getCEP(this.value)" required>
                                        <div class="input-group-text" style="display: none;">
                                            <div class="spinner-border ml-2" role="status">
                                                <span class="visually-hidden">Carregando...</span>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Endereço*</label>
                                        <input type="text" class="form-control" name="addresses[address]" placeholder="Endereço" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-2">
                                    <div class="mb-3">
                                        <label for="addressNumber" class="form-label">Nº</label>
                                        <input type="text" class="form-control" name="addresses[number]" placeholder="Nº">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="complement" class="form-label">Complemento</label>
                                        <input type="text" class="form-control" name="addresses[complement]" placeholder="Complemento">
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="neighborhood" class="form-label">Bairro*</label>
                                        <input type="text" class="form-control" name="addresses[neighborhood]" placeholder="Bairro" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="city" class="form-label">Cidade*</label>
                                        <input type="text" class="form-control" name="addresses[city]" placeholder="Cidade" required>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="mb-3">
                                        <label for="state" class="form-label">UF*</label>
                                        <select class="form-select" name="addresses[state]" required>
                                            <option value="">Selecione...</option>
                                            <option value="AC">Acre</option>
                                            <option value="AL">Alagoas</option>
                                            <option value="AP">Amapá</option>
                                            <option value="AM">Amazonas</option>
                                            <option value="BA">Bahia</option>
                                            <option value="CE">Ceará</option>
                                            <option value="DF">Distrito Federal</option>
                                            <option value="ES">Espírito Santo</option>
                                            <option value="GO">Goiás</option>
                                            <option value="MA">Maranhão</option>
                                            <option value="MT">Mato Grosso</option>
                                            <option value="MS">Mato Grosso do Sul</option>
                                            <option value="MG">Minas Gerais</option>
                                            <option value="PA">Pará</option>
                                            <option value="PB">Paraíba</option>
                                            <option value="PR">Paraná</option>
                                            <option value="PE">Pernambuco</option>
                                            <option value="PI">Piauí</option>
                                            <option value="RJ">Rio de Janeiro</option>
                                            <option value="RN">Rio Grande do Norte</option>
                                            <option value="RS">Rio Grande do Sul</option>
                                            <option value="RO">Rondônia</option>
                                            <option value="RR">Roraima</option>
                                            <option value="SC">Santa Catarina</option>
                                            <option value="SP">São Paulo</option>
                                            <option value="SE">Sergipe</option>
                                            <option value="TO">Tocantins</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="otherInformation" class="hidden">
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="mainOccupation" class="form-label">Ocupação principal*</label>
                                        <select class="form-select" name="otherInformations[mainOccupation]">
                                            <option value="">Digite para buscar...</option>
                                            <option value="252105">Administrador</option>
                                            <option value="252405">Analista de Recursos Humanos</option>
                                            <option value="212405">Analista de Sistemas</option>
                                            <option value="411010">Assistente Administrativo</option>
                                            <option value="514120">Bombeiro Civil</option>
                                            <option value="252210">Contador</option>
                                            <option value="513205">Cozinheiro Geral</option>
                                            <option value="261515">Designer Gráfico</option>
                                            <option value="212415">Desenvolvedor de Software (Programador)</option>
                                            <option value="223505">Enfermeiro</option>
                                            <option value="214205">Engenheiro Civil</option>
                                            <option value="223605">Fisioterapeuta</option>
                                            <option value="142305">Gerente Comercial</option>
                                            <option value="142105">Gerente Administrativo</option>
                                            <option value="225125">Médico Clínico Geral</option>
                                            <option value="782310">Motorista de Furgão ou Veículo Similar</option>
                                            <option value="223710">Nutricionista</option>
                                            <option value="233115">Professor de Educação Física (no ensino fundamental)</option>
                                            <option value="322205">Técnico de Enfermagem</option>
                                            <option value="521110">Vendedor de Comércio Varejista</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Categoria*</label>
                                        <select class="form-select" name="otherInformations[category]">
                                            <option value="">Selecione...</option>
                                            <option value="Autônomo">Autônomo</option>
                                            <option value="Empregado">Empregado</option>
                                            <option value="Empregador">Empregador</option>
                                            <option value="Servidor Público">Servidor Público</option>
                                            <option value="Outros">Outros</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Residente no Brasil?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="otherInformations[brazilianResident]" id="brazilianResidentYes" value="1">
                                            <label class="form-check-label" for="brazilianResidentYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="otherInformations[brazilianResident]" id="brazilianResidentNo" value="0">
                                            <label class="form-check-label" for="brazilianResidentNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">É pessoa politicamente exposta?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="otherInformations[politicallyExposed]" id="politicallyExposedYes" value="1">
                                            <label class="form-check-label" for="politicallyExposedYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="otherInformations[politicallyExposed]" id="politicallyExposedNo" value="0">
                                            <label class="form-check-label" for="politicallyExposedNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Você tem obrigações fiscais com outros países?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="otherInformations[obligationOtherCountries]" id="obligationOtherCountriesYes" value="1">
                                            <label class="form-check-label" for="obligationOtherCountriesYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="otherInformations[obligationOtherCountries]" id="obligationOtherCountriesNo" value="0">
                                            <label class="form-check-label" for="obligationOtherCountriesNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="proponentStatement" class="hidden">
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Encontra-se com algum problema de saúde ou faz uso de algum medicamento?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[healthProblem]" id="healthProblemYes" value="1" onclick="showHide(true, 'healthProblemObs')">
                                            <label class="form-check-label" for="healthProblemYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[healthProblem]" id="healthProblemNo" value="0" onclick="showHide(false, 'healthProblemObs')">
                                            <label class="form-check-label" for="healthProblemNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                        <div id="healthProblemObs" class="mb-3" style="display: none;">
                                            <label for="" class="form-label">Especificar*</label>
                                            <input type="text" class="form-control" name="proponentStatement[healthProblemObs]" placeholder="Especificar">
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Sofre ou já sofreu de doenças do coração, hipertensão, circulatórias, do sangue, diabetes, pulmão, fígado, rins, infarto, acidente vascular cerebral, articulações, qualquer tipo de câncer ou HIV?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[heartDisease]" id="heartDiseaseYes" value="1" onclick="showHide(true, 'heartDiseaseObs')">
                                            <label class="form-check-label" for="heartDiseaseYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[heartDisease]" id="heartDiseaseNo" value="0" onclick="showHide(false, 'heartDiseaseObs')">
                                            <label class="form-check-label" for="heartDiseaseNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                        <div id="heartDiseaseObs" class="mb-3" style="display: none;">
                                            <label for="" class="form-label">Especificar*</label>
                                            <input type="text" class="form-control" name="proponentStatement[heartDiseaseObs]" placeholder="Especificar">
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Sofre ou sofreu de deficiências de órgãos, membros ou sentidos, incluindo doenças ortopédicas relacionadas a esforço repetitivo (LER e DORT)?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[sufferedOrganDefects]" id="sufferedOrganDefectsYes" value="1" onclick="showHide(true, 'sufferedOrganDefectsObs')">
                                            <label class="form-check-label" for="sufferedOrganDefectsYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[sufferedOrganDefects]" id="sufferedOrganDefectsNo" value="0" onclick="showHide(false, 'sufferedOrganDefectsObs')">
                                            <label class="form-check-label" for="sufferedOrganDefectsNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                        <div id="sufferedOrganDefectsObs" class="mb-3" style="display: none;">
                                            <label for="" class="form-label">Especificar*</label>
                                            <input type="text" class="form-control" name="proponentStatement[sufferedOrganDefectsObs]" placeholder="Especificar">
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Fez alguma cirurgia, biópsia ou esteve internado nos últimos cinco anos?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[surgery]" id="surgeryYes" value="1" onclick="showHide(true, 'surgeryObs')">
                                            <label class="form-check-label" for="surgeryYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[surgery]" id="surgeryNo" value="0" onclick="showHide(false, 'surgeryObs')">
                                            <label class="form-check-label" for="surgeryNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                        <div id="surgeryObs" class="mb-3" style="display: none;">
                                            <label for="" class="form-label">Especificar*</label>
                                            <input type="text" class="form-control" name="proponentStatement[surgeryObs]" placeholder="Especificar">
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Está afastado(a) do trabalho ou aposentado por invalidez?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[away]" id="awayYes" value="1" onclick="showHide(true, 'awayObs')">
                                            <label class="form-check-label" for="awayYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[away]" id="awayNo" value="0" onclick="showHide(false, 'awayObs')">
                                            <label class="form-check-label" for="awayNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                        <div id="awayObs" class="mb-3" style="display: none;">
                                            <label for="" class="form-label">Especificar*</label>
                                            <input type="text" class="form-control" name="proponentStatement[awayObs]" placeholder="Especificar">
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Pratica paraquedismo, motociclismo, boxe, asa delta, rodeio, alpinismo, voo livre, automobilismo, mergulho ou exerce atividade, em caráter profissional ou amador, a bordo de aeronaves, que não sejam de linhas regulares?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[practicesParachuting]" id="practicesParachutingYes" value="1" onclick="showHide(true, 'practicesParachutingObs')">
                                            <label class="form-check-label" for="practicesParachutingYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[practicesParachuting]" id="practicesParachutingNo" value="0" onclick="showHide(false, 'practicesParachutingObs')">
                                            <label class="form-check-label" for="practicesParachutingNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                        <div id="practicesParachutingObs" class="mb-3" style="display: none;">
                                            <label for="" class="form-label">Especificar*</label>
                                            <input type="text" class="form-control" name="proponentStatement[practicesParachutingObs]" placeholder="Especificar">
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">É fumante?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[smoker]" id="smokerYes" value="1" onclick="showHide(true, 'smokerObs')">
                                            <label class="form-check-label" for="smokerYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[smoker]" id="smokerNo" value="0" onclick="showHide(false, 'smokerObs')">
                                            <label class="form-check-label" for="smokerNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                        <div id="smokerObs" style="display: none;">
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="proponentStatement[smokerType]" id="smokerTypeYes" value="1" onclick="showHide(false, 'smokerTypeObs')">
                                                    <label class="form-check-label" for="smokerTypeYes">Cigarro</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="proponentStatement[smokerType]" id="smokerTypeNo" value="0" onclick="showHide(true, 'smokerTypeObs')">
                                                    <label class="form-check-label" for="smokerTypeNo">Outros</label>
                                                    <div class="invalid-feedback">
                                                        Preenchimento obrigatório.
                                                    </div>
                                                </div>
                                                <div id="smokerTypeObs" class="mb-3" style="display: none;">
                                                    <label for="" class="form-label">Especificar:*</label>
                                                    <input type="text" class="form-control" name="proponentStatement[smokerTypeObs]" placeholder="Especificar">
                                                    <div class="invalid-feedback">
                                                        Preenchimento obrigatório.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="" class="form-label">Quantidade média/dia:*</label>
                                                <input type="text" class="form-control" name="proponentStatement[smokerQty]" placeholder="Especificar">
                                                <div class="invalid-feedback">
                                                    Preenchimento obrigatório.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Informe peso e altura.*</label>
                                        <div class="row">
                                            <div class="col">
                                                <div class="input-group mb-3">
                                                    <input type="text" class="form-control money" name="proponentStatement[weight]">
                                                    <span class="input-group-text">Kg</span>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="input-group mb-3">
                                                    <input type="text" class="form-control money" name="proponentStatement[height]">
                                                    <span class="input-group-text">m</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Apresenta, no momento, sintomas de gripe, febre, cansaço, tosse, coriza, dores pelo corpo, dor de cabeça, dor de garganta, falta de ar, perda de olfato, perda de paladar ou está aguardando resultado do teste da COVID-19?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[gripe]" id="gripeYes" value="1" onclick="showHide(true, 'gripeObs')">
                                            <label class="form-check-label" for="gripeYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[gripe]" id="gripeNo" value="0" onclick="showHide(false, 'gripeObs')">
                                            <label class="form-check-label" for="gripeNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                        <div id="gripeObs" class="mb-3" style="display: none;">
                                            <label for="" class="form-label">Especificar*</label>
                                            <input type="text" class="form-control" name="proponentStatement[gripeObs]" placeholder="Especificar">
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Foi diagnosticado(a) com infecção pelo novo CORONA VÍRUS ou COVID-19?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[covid]" id="covidYes" value="1" onclick="showHide(true, 'covidObs')">
                                            <label class="form-check-label" for="covidYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[covid]" id="covidNo" value="0" onclick="showHide(false, 'covidObs')">
                                            <label class="form-check-label" for="covidNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                        <div id="covidObs" class="mb-3" style="display: none;">
                                            <label for="" class="form-label">Especificar*</label>
                                            <input type="text" class="form-control" name="proponentStatement[covidObs]" placeholder="Especificar">
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Apresenta, no momento, sequelas do COVID-19 diferente de perda de olfato e/ou paladar?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[covidSequelae]" id="covidSequelaeYes" value="1" onclick="showHide(true, 'covidSequelaeObs')">
                                            <label class="form-check-label" for="covidSequelaeYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="proponentStatement[covidSequelae]" id="covidSequelaeNo" value="0" onclick="showHide(false, 'covidSequelaeObs')">
                                            <label class="form-check-label" for="covidSequelaeNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                        <div id="covidSequelaeObs" class="mb-3" style="display: none;">
                                            <label for="" class="form-label">Especificar*</label>
                                            <input type="text" class="form-control" name="proponentStatement[covidSequelaeObs]" placeholder="Especificar">
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="pensionScheme" class="hidden">
                            <div class="row">
                                <div class="col" id="pensionSchemeAnyPensionSchema">
                                    <div class="mb-3">
                                        <label for="" class="form-label">Você está em algum regime de previdência?*</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="pensionScheme[anyPensionSchema]" id="anyPensionSchemaYes" value="1" onclick="pensionSchema(true)">
                                            <label class="form-check-label" for="anyPensionSchemaYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="pensionScheme[anyPensionSchema]" id="anyPensionSchemaNo" value="0" onclick="pensionSchema(false)">
                                            <label class="form-check-label" for="anyPensionSchemaNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="pensionSchemeType" style="display: none;">
                                    <div class="col">
                                        <div class="mb-3">
                                            <label id="pensionSchemeTypeLabel" class="form-label"></label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="pensionScheme[pensionSchemeType]" id="pensionSchemeTypeGeral" value="Geral (INSS)" required>
                                                <label class="form-check-label" for="pensionSchemeTypeGeral">Geral (INSS)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="pensionScheme[pensionSchemeType]" id="pensionSchemeTypeServidorPublico" value="Próprio (Servidor público)" required>
                                                <label class="form-check-label" for="pensionSchemeTypeServidorPublico">Próprio (Servidor público)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="pensionScheme[pensionSchemeType]" id="pensionSchemeTypeComplementar" value="Complementar (Fundos de pensão)" required>
                                                <label class="form-check-label" for="pensionSchemeTypeComplementar">Complementar (Fundos de pensão)</label>
                                                <div class="invalid-feedback">
                                                    Preenchimento obrigatório.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="pensionSchemeTypeKinship" style="display: none;">
                                        <div class="col">
                                            <div class="mb-3">
                                                <label class="form-label">Vinculado ao segurado*</label>
                                                <input type="text" class="form-control" name="pensionScheme[name]" placeholder="Vinculado ao segurado">
                                                <div class="invalid-feedback">
                                                    Preenchimento obrigatório.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mb-3">
                                                <label class="form-label">CPF*</label>
                                                <input type="text" class="form-control cpf" name="pensionScheme[cpf]" placeholder="CPF">
                                                <div class="invalid-feedback">
                                                    Preenchimento obrigatório.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mb-3">
                                                <label class="form-label">Grau de parentesco*</label>
                                                <input type="text" class="form-control" name="pensionScheme[kinship]" placeholder="Grau de parentesco">
                                                <div class="invalid-feedback">
                                                    Preenchimento obrigatório.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="paymentDetail" class="hidden">
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label class="form-label">Dia do vencimento</label>
                                        <input type="text" class="form-control" name="paymentDetail[due_date]" placeholder="Dia do vencimento" value="10" readonly>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label class="form-label">Total da contribuição</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control money" name="paymentDetail[total_contribution]" placeholder="Total da contribuição - R$ (1+2)" value="<?= $totalMonthlyContributionPlan; ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label class="form-label">Meio de pagamento</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="paymentDetail[payment_type]" id="paymentTypeDebitoConta" value="Débito em conta" onclick="paymentType(this.value);" required>
                                            <label class="form-check-label" for="paymentTypeDebitoConta">Débito em conta (Somente BB)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="paymentDetail[payment_type]" id="paymentTypeBoletoBancario" value="Boleto bancário" onclick="paymentType(this.value);" required>
                                            <label class="form-check-label" for="paymentTypeBoletoBancario">Boleto bancário</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="directDebitType" style="display: none;">
                                <div class="row">
                                    <div class="col">
                                        <div class="mb-3">
                                            <label class="form-label">Nome do correntista</label>
                                            <input type="text" class="form-control" name="paymentDetail[account_holder_name]" placeholder="Nome do correntista">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="mb-3">
                                            <label class="form-label">CPF do correntista</label>
                                            <input type="text" class="form-control cpf" name="paymentDetail[account_holder_cpf]" placeholder="CPF do correntista">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="mb-3">
                                            <label class="form-label">Banco</label>
                                            <select class="form-select" name="paymentDetail[bank_number]" disabled>
                                                <option value="">Selecione...</option>
                                                <?php foreach ($this->Bank->getList() as $bankNumber => $bankName) { ?>
                                                    <option value="<?= $bankNumber; ?>" <?= $bankNumber === '001' ? 'selected' : ''; ?>><?= $bankName; ?></option>
                                                <?php } ?>
                                            </select>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="mb-3">
                                            <label class="form-label">Agência</label>
                                            <input type="text" class="form-control" name="paymentDetail[branch_number]" placeholder="Agência">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label class="form-label">Conta corrente</label>
                                            <input type="text" class="form-control" name="paymentDetail[account_number]" placeholder="Conta corrente">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="conclusion" class="hidden">
                            <div class="row">
                                <div class="col">
                                    <p>Enviamos para o e-mail "<span id="conclusionEmail"></span>" a proposta para assinatura e abaixo o pix para adesão, utilize o QR Code/pix copia e cola para realizar o pagamento.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 text-center">
                                    <img id="pix-qrcode" src="" alt="QR Code PIX" style="max-width: 200px; display: none; margin: 0 auto;" />
                                </div>
                                <div class="col-12 mt-3">
                                    <label for="pix-copy-paste" class="form-label">Pix Copia e Cola</label>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="pix-copy-paste" readonly>
                                        <button class="btn btn-outline-secondary" type="button" id="btn-copy-pix">Copiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" onclick="previousPage()">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="nextPage()">Concordo</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const localStorageKey = 'adesaoSulPrevidencia';
        let draftUUID = localStorage.getItem(localStorageKey);
        let initialDataId = null;
        const registerPages = [{
                title: 'Dados iniciais',
                id: 'initialData',
            },
            {
                title: 'Dados pessoais',
                id: 'personalData',
            },
            {
                title: 'Documentos',
                id: 'documents',
            },
            {
                title: 'Plano',
                id: 'plan',
            },
            {
                title: 'Beneficiário(s)',
                id: 'dependents',
            },
            {
                title: 'Endereço',
                id: 'addressData',
            },
            {
                title: 'Outras informações',
                id: 'otherInformation',
            },
            {
                title: 'Declarações do proponente',
                id: 'proponentStatement',
            },
            {
                title: 'Regime de previdência',
                id: 'pensionScheme',
            },
            {
                title: 'Dados para pagamento',
                id: 'paymentDetail',
            },
            {
                title: 'Conclusão',
                id: 'conclusion',
            },
        ];
        let registerPageIndex = 0
        let registerModal;

        document.addEventListener('DOMContentLoaded', function() {
            const openModalBtn = document.getElementById('simulador-continuar');
            const registerModalEl = document.getElementById('registerModal');
            registerModal = new bootstrap.Modal(registerModalEl);

            if (!draftUUID) {
                draftUUID = self.crypto.randomUUID ? self.crypto.randomUUID() : Math.random().toString(36).substring(2) + Date.now().toString(36);
                localStorage.setItem(localStorageKey, draftUUID);
                console.log('Novo UUID gerado:', draftUUID);
            }

            openModalBtn.addEventListener('click', function() {
                registerPageIndex = 0;

                updatePage(registerPageIndex);

                registerModal.show();
            });

            $('#btn-copy-pix').on('click', function() {
                const copyText = document.getElementById("pix-copy-paste");
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(copyText.value).then(() => {
                    alert("Código PIX copiado!");
                });
            });

            simulationChart();
        });

        const planForHandle = (planFor) => {
            if (planFor.value === 'Dependente') {
                const name = $('#registerModal #personalData input[name="personalData[name]"]').val();
                const cpf = $('#registerModal #personalData input[name="personalData[cpf]"]').val();

                $('#registerModal #personalData input[name="personalData[nameLegalRepresentative]"]').val(name);
                $('#registerModal #personalData input[name="personalData[cpfLegalRepresentative]"]').val(cpf);
                $('#registerModal #divLegalRepresentative input').attr('required', 'required');
                $('#registerModal #divLegalRepresentative').slideDown();

                return;
            }

            $('#registerModal #divLegalRepresentative').slideUp();
            $('#registerModal #divLegalRepresentative input').removeAttr('required');
        }

        const updateButtonPreviousNext = (pageIndex) => {
            switch (pageIndex) {
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7:
                case 8:
                case 9:
                    jQuery('#registerModal .modal-footer .btn-secondary').text('Anterior').show();
                    jQuery('#registerModal .modal-footer .btn-primary').text('Próximo');
                    break;
                case 10:
                    jQuery('#registerModal .modal-footer .btn-secondary').hide();
                    jQuery('#registerModal .modal-footer .btn-primary').text('Fechar');
                    break;
                default:
                    jQuery('#registerModal .modal-footer .btn-secondary').text('Cancelar').show();
                    jQuery('#registerModal .modal-footer .btn-primary').text('Concordo');
                    break;
            }
        }

        const updatePage = (pageIndex) => {
            $('#registerModal #initialData').hide();
            $('#registerModal #personalData').hide();
            $('#registerModal #documents').hide();
            $('#registerModal #plan').hide();
            $('#registerModal #dependents').hide();
            $('#registerModal #addressData').hide();
            $('#registerModal #otherInformation').hide();
            $('#registerModal #proponentStatement').hide();
            $('#registerModal #pensionScheme').hide();
            $('#registerModal #paymentDetail').hide();
            $('#registerModal #conclusion').hide();

            switch (pageIndex) {
                case 0:
                    $('#registerModal #initialData').fadeIn().show();
                    break;
                case 1:
                    const name = $('#registerModal #initialData input[name="initialData[name]"]').val();

                    $('#registerModal #personalData input[name="personalData[name]"]').val(name);

                    $('#registerModal #personalData').fadeIn().show();
                    break;
                case 2:
                    $('#registerModal #documents').fadeIn().show();
                    break;
                case 3:
                    const age = calculateAge($('#registerModal input[name="personalData[birthDate]"]').val());
                    const benefitEntry = age <= 55 ? 65 : age + 10;

                    $('#registerModal input[name="plans[benefitEntryAge]"]').val(benefitEntry);

                    $('#registerModal #plan').fadeIn().show();
                    break;
                case 4:
                    $('#registerModal #dependents').fadeIn().show();
                    break;
                case 5:
                    $('#registerModal #addressData').fadeIn().show();
                    break;
                case 6:
                    $('#registerModal #otherInformation').fadeIn().show();
                    break;
                case 7:
                    $('#registerModal #proponentStatement').fadeIn().show();
                    break;
                case 8:
                    const planFor = $('#registerModal #personalData input[name="personalData[planFor]"]:checked').val();
                    const anyPensionSchema = $('#registerModal #pensionScheme input[name="pensionScheme[anyPensionSchema]"]').is(':checked');

                    if (planFor === 'Dependente') {
                        $('#registerModal #pensionSchemeAnyPensionSchema').hide();

                        pensionSchema(false);
                    } else {
                        $('#registerModal #pensionSchemeType').slideUp();

                        if (!anyPensionSchema) {
                            $('#registerModal #pensionSchemeAnyPensionSchema input[type="checkbox"]').prop('checked', false);
                            $('#registerModal #pensionSchemeAnyPensionSchema').show();
                        } else {
                            $('#registerModal #pensionScheme input[name="pensionScheme[anyPensionSchema]"]:checked').click();
                        }
                    }

                    $('#registerModal #pensionScheme').fadeIn().show();
                    break;
                case 9:
                    $('#registerModal #paymentDetail').fadeIn().show();
                    break;
                case 10:
                    const email = $('#registerModal #initialData input[name="initialData[email]"]').val();

                    $('#registerModal #conclusionEmail').html(email);
                    $('#registerModal #conclusion').fadeIn().show();
                    break;
            }

            $('#registerModal .modal-body h4').html(registerPages[registerPageIndex].title);
            updateButtonPreviousNext(registerPageIndex);
        }

        const nextPage = async () => {
            if (registerPageIndex === registerPages.length - 1)
                window.location.reload();

            let isValid = true;
            const form = document.querySelectorAll(`#${registerPages[registerPageIndex].id} input, #${registerPages[registerPageIndex].id} select`);

            form.forEach((input) => {
                if (!input.checkValidity())
                    isValid = false;

                if (input.classList.contains('cpf') && input.offsetParent !== null) {
                    const feedbackDiv = input.nextElementSibling;
                    const cpfValue = input.value.replace(/\D/g, '');

                    if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
                        const originalText = 'Preenchimento obrigatório.';

                        if (cpfValue.length < 11 || !cpfCheck(cpfValue)) {
                            isValid = false;
                            input.setCustomValidity('CPF inválido.');
                            feedbackDiv.textContent = 'O número de CPF informado é inválido.';
                        } else {
                            input.setCustomValidity('');
                            feedbackDiv.textContent = originalText;
                        }
                    }
                }
            })

            if (!isValid) {
                $(`#registerModalForm #${registerPages[registerPageIndex].id}`)[0].classList.add('was-validated')
                return;
            }

            if (registerPageIndex === 4) {
                if (!checkDependents()) {
                    alert('A porcentagem de participação total é diferente de 100%, favor verificar.');

                    return;
                }
            }

            const response = await saveForm(registerPages[registerPageIndex].id);

            registerPageIndex += 1;

            if (registerPageIndex === 10) {
                $('#pix-qrcode').attr('src', response.qrCodeBase64).show();
                $('#pix-copy-paste').val(response.copyAndPaste);
            }

            updatePage(registerPageIndex)
        }

        const previousPage = () => {
            if (registerPageIndex === 0)
                registerModal.hide();

            if (registerPageIndex !== 0)
                registerPageIndex -= 1;

            updatePage(registerPageIndex)
        }

        const saveForm = async (id) => {
            const formSelector = `#${id}`;
            const formElements = $(`form ${formSelector} input, ${formSelector} select, ${formSelector} textarea`)

            return new Promise((resolve, reject) => {
                $.ajax({
                    type: 'POST',
                    url: `<?= $this->Url->build(['controller' => 'Registrations', 'action' => 'save']) ?>`,
                    headers: {
                        'X-CSRF-Token': '<?= $this->request->getAttribute('csrfToken') ?>'
                    },
                    data: formElements.serialize() + `&storageUuid=${draftUUID}${initialDataId !== null ? `&initialDataId=${initialDataId}` : ''}`,
                    dataType: 'json',
                    beforeSend: () => {},
                    success: (response) => {
                        if (response?.success === true && response?.initialDataId)
                            initialDataId = response.initialDataId;

                        resolve(response);
                    },
                    error: (error) => {
                        alert('Erro ao avançar!');
                        reject(error);
                    }
                })
            })
        }

        const getCEP = (value) => {
            const cep = value.replace(/[^0-9]/g, '');

            if (cep.length < 8)
                return;

            $.ajax({
                type: 'GET',
                url: `https://viacep.com.br/ws/${cep}/json/`,
                beforeSend: () => {
                    $('#addressData .input-group-text').show();
                },
                success: (response) => {
                    $('#addressData .input-group-text').hide();
                    $('#addressData input[name="addresses[address]"]').val(response?.logradouro);
                    $('#addressData input[name="addresses[neighborhood]"]').val(response?.bairro);
                    $('#addressData input[name="addresses[city]"]').val(response?.localidade);
                    $('#addressData select[name="addresses[state]"]').val(response?.uf);
                },
                error: () => {
                    alert('CEP não encontrado!');
                }
            })
        }

        function cpfCheck(cpf) {
            let Soma = 0;
            let Resto;
            const strCPF = String(cpf).replace(/[^\d]/g, '');

            if (strCPF.length !== 11)
                return false;

            if ([
                    '00000000000',
                    '11111111111',
                    '22222222222',
                    '33333333333',
                    '44444444444',
                    '55555555555',
                    '66666666666',
                    '77777777777',
                    '88888888888',
                    '99999999999',
                ].indexOf(strCPF) !== -1)
                return false;

            for (i = 1; i <= 9; i++)
                Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (11 - i);

            Resto = (Soma * 10) % 11;

            if ((Resto == 10) || (Resto == 11))
                Resto = 0;

            if (Resto != parseInt(strCPF.substring(9, 10)))
                return false;

            Soma = 0;

            for (i = 1; i <= 10; i++)
                Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (12 - i);

            Resto = (Soma * 10) % 11;

            if ((Resto == 10) || (Resto == 11))
                Resto = 0;

            if (Resto != parseInt(strCPF.substring(10, 11)))
                return false;

            return true;
        }

        const addDependent = () => {
            const count = $('#listDependents .dependentDiv').length;
            let participation = 100;

            if (count === 3) {
                alert('Limite de beneficiáios excedido, caso queira adicionar mais de 3 beneficiários, será necessário solicitar depois do plano efetuado.');

                return;
            }

            if (count > 0) {
                participation = 100 / (count + 1);
                participation = participation.toFixed(0);

                for (let index = 0; index < count; index++) {
                    $(`#listDependents .dependentDiv input[name="dependents[${index}][participation]"]`).val(participation);
                }
            }

            if (count === 2)
                participation = 34;

            $('#listDependents').append(`
        <div class="dependentDiv">
            <div class="text-center"><strong>Beneficiário ${count + 1}</strong></div>
            <div class="row">
                <div class="col">
                    <div class="mb-3">
                        <label for="nameDependent" class="form-label">Nome*</label>
                        <input type="text" class="form-control" name="dependents[${count}][name]" placeholder="Nome">
                        <div class="invalid-feedback">
                            Preenchimento obrigatório.
                        </div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="mb-3">
                        <label for="kinship" class="form-label">Parentesco*</label>
                        <select class="form-select" name="dependents[${count}][kinship]">
                            <option value="">Selecione...</option>
                            <option value="Avô(ó)">Avô(ó)</option>
                            <option value="Companheiro(a)">Companheiro(a)</option>
                            <option value="Cônjuge">Cônjuge</option>
                            <option value="Filho(a)">Filho(a)</option>
                            <option value="Irmão(ã)">Irmão(ã)</option>
                            <option value="Mãe">Mãe</option>
                            <option value="Nenhum">Nenhum</option>
                            <option value="Neto(a)">Neto(a)</option>
                            <option value="Pai">Pai</option>
                            <option value="Sobrinho(a)">Sobrinho(a)</option>
                            <option value="Tio(a)">Tio(a)</option>
                        </select>
                        <div class="invalid-feedback">
                            Preenchimento obrigatório.
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="mb-3">
                        <label for="cpfDependent" class="form-label">CPF*</label>
                        <input type="text" class="form-control cpf" name="dependents[${count}][cpf]" placeholder="CPF">
                        <div class="invalid-feedback">
                            Preenchimento obrigatório.
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="mb-3">
                        <label for="birthDateDependent" class="form-label">Data de nasc.*</label>
                        <input type="date" max="9999-12-31" class="form-control" name="dependents[${count}][birth_date]" placeholder="Data de nascimento">
                        <div class="invalid-feedback">
                            Preenchimento obrigatório.
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="mb-3">
                        <label for="participationDependent" class="form-label">Participação (%)*</label>
                        <input type="number" min="0" max="100" class="form-control participation" name="dependents[${count}][participation]" placeholder="Participação (%)" value="${participation}">
                        <div class="invalid-feedback">
                            Preenchimento obrigatório.
                        </div>
                    </div>
                </div>
            </div>
        </div>`);

            $('.cpf').mask('000.000.000-00', {
                reverse: true,
            });
        }

        const showHide = (show, id) => {
            if (show)
                $(`#${id}`).show('slow');

            if (!show)
                $(`#${id}`).hide('slow');
        }

        const pensionSchema = (pensionSchema) => {
            let declaration = '<strong>DECLARO</strong> sob pena da lei, que sou segurado do seguinte regime de previdência';

            if (pensionSchema) {
                $('#pensionSchemeType #pensionSchemeTypeLabel').html(declaration);
                $('#pensionSchemeTypeKinship').slideUp();
            }

            if (!pensionSchema) {
                let declaration = '<strong>DECLARO</strong> sob pena da lei, que sou parente até segundo grau do segurado abaixo identificado, o qual é vinculado ao seguinte regime de previdência';
                $('#pensionSchemeType #pensionSchemeTypeLabel').html(declaration);
                $('#pensionSchemeTypeKinship').slideDown();
            }

            $('#pensionSchemeType').slideDown('slow');
        }

        const calculateAge = (dateBirth) => {
            const birthDate = new Date(dateBirth);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();

            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate()))
                age--;

            return age;
        }

        const simulate = () => {
            let isValid = true;
            const date = $('#simulador-form input[name="dateBirth').val();
            const value = $('#simulador-form input[name="monthlyInvestment').val().replace('.', '').replace(',', '.');
            const simulatorUrl = `<?= $this->Url->build(['controller' => 'Simulator', 'action' => 'index']); ?>?date=${date}&value=${value}`;
            const form = document.querySelectorAll(`#simulador-form input`);

            form.forEach((input) => {
                if (!input.checkValidity())
                    isValid = false;
            })

            if (!isValid) {
                $(`#simulador-form`)[0].classList.add('was-validated')
                return;
            }

            window.location.href = simulatorUrl;
        }

        const paymentType = (type) => {
            if (type === 'Débito em conta') {
                $('#directDebitType').slideDown();

                return;
            }

            $('#directDebitType').slideUp();
        }

        const checkDependents = () => {
            let total = 0;
            const dependents = $('#listDependents .participation');

            if (dependents.length === 0)
                return true;

            dependents.each(function() {
                let value = $(this).val();

                total += parseFloat(value) || 0;
            });

            if (total < 100 || total > 100)
                return false;

            return true;
        }

        function calculateMonthsDifference(date1, date2) {
            let months;
            months = (date2.getFullYear() - date1.getFullYear()) * 12;
            months -= date1.getMonth();
            months += date2.getMonth();

            if (date2.getDate() < date1.getDate())
                months--;

            return months >= 0 ? months : 0;
        }

        const simulationChart = () => {
            const ANNUAL_RATE = 0.0803;
            const MONTHLY_RATE = Math.pow(1 + ANNUAL_RATE, 1.0 / 12) - 1;
            const RETIREMENT_AGE = 65;
            const RETIREMENT_ALLOCATION = 0.74;
            const dateBirth = $('.simulador-form-group input[name="dateBirth"]').val();
            const contribution = parseFloat(<?= $_GET['value']; ?>);

            if (!dateBirth || contribution <= 0) {
                alert('Por favor, insira uma data de nascimento e uma contribuição mensal válidas.');
                return;
            }

            const birthDate = new Date(dateBirth);
            const currentAge = calculateAge(dateBirth);
            const today = new Date();
            const retirementDate = new Date(birthDate.getFullYear() + RETIREMENT_AGE, birthDate.getMonth(), birthDate.getDate());

            if (currentAge >= RETIREMENT_AGE) {
                alert('A idade atual é igual ou superior à idade de aposentadoria (65 anos). Não há projeção futura.');
                return;
            }

            const labels = [];
            const pureData = [];
            const compoundedData = [];

            const contribuicaoAposentadoria = contribution * RETIREMENT_ALLOCATION;

            let currentSaldoAcumulado = 0;
            let totalContribuicaoPura = 0;
            let totalMonthsContributed = 0;

            for (let age = currentAge + 1; age <= RETIREMENT_AGE; age++) {
                let monthsInCycle = 0;
                let nextAnniversaryDate = new Date(birthDate.getFullYear() + age + 1, birthDate.getMonth(), birthDate.getDate());

                if (age === currentAge) {
                    monthsInCycle = calculateMonthsDifference(today, nextAnniversaryDate);
                } else if (age < RETIREMENT_AGE) {
                    monthsInCycle = 12;

                } else if (age === RETIREMENT_AGE) {
                    monthsInCycle = calculateMonthsDifference(nextAnniversaryDate, retirementDate);

                    if (monthsInCycle <= 0) break;
                } else {
                    break;
                }

                if (totalMonthsContributed + monthsInCycle > calculateMonthsDifference(today, retirementDate)) {
                    monthsInCycle = calculateMonthsDifference(today, retirementDate) - totalMonthsContributed;
                }

                if (monthsInCycle > 0) {
                    for (let month = 0; month < monthsInCycle; month++) {
                        currentSaldoAcumulado = currentSaldoAcumulado * (1 + MONTHLY_RATE) + contribuicaoAposentadoria;
                    }

                    totalContribuicaoPura += contribuicaoAposentadoria * monthsInCycle;
                    totalMonthsContributed += monthsInCycle;
                }

                labels.push(age);
                pureData.push(Math.round(totalContribuicaoPura));
                compoundedData.push(Math.round(currentSaldoAcumulado * 100) / 100);

                if (age >= RETIREMENT_AGE) break;
            }

            renderChart(labels, pureData, compoundedData);
        }

        const renderChart = (labels, pureData, compoundedData) => {
            let chartInstance = null;
            const ctx = document.getElementById('simulador-chart').getContext('2d');

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Contribuição pura',
                            data: pureData,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: false,
                            tension: 0.3,
                            borderWidth: 3,
                            pointRadius: 3
                        },
                        {
                            label: 'Contribuição rentabilizada',
                            data: compoundedData,
                            borderColor: 'rgba(255, 193, 7, 1)',
                            backgroundColor: 'rgba(255, 193, 7, 0.1)',
                            fill: false,
                            tension: 0.3,
                            borderWidth: 3,
                            pointRadius: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Montante Acumulado (R$)',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) return 'R$' + (value / 1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return 'R$' + (value / 1000).toFixed(0) + 'K';
                                    return 'R$' + value.toFixed(0);
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Idade',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('pt-BR', {
                                            style: 'currency',
                                            currency: 'BRL'
                                        }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>