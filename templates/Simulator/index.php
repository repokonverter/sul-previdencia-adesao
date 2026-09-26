<?php

/**
 * @var \App\View\AppView $this
 * @var string|null $csrfToken
 */

use Cake\Core\Configure;
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

        /* Border própria, não a utilitária ".border" do Bootstrap: ela vem
           com "!important", e sempre vence a cor do parceiro que o JS seta
           via style.borderColor. */
        .partner-badge {
            border: 1px solid #dee2e6;
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
                    <label for="valor-investimento">Investimento mensal <small class="text-muted">(mínimo R$ 100,00)</small></label>
                    <input type="text" class="form-control money" name="monthlyInvestment" placeholder="Investimento mensal" value="<?= $_GET['value']; ?>" required>
                </div>
            </form>
            <div id="simulador-form-error" class="text-danger mb-2" style="display: none;"></div>
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
                    <div id="registerModalPartner" class="d-none align-items-center ms-auto me-3 partner-badge rounded-pill px-3 py-1">
                        <span class="text-muted small me-2 d-none d-sm-inline">em parceria com</span>
                        <img id="registerModalPartnerLogo" src="" alt="" class="d-none" style="max-height:34px;max-width:130px;object-fit:contain;">
                        <span id="registerModalPartnerLabel" class="fw-semibold small"></span>
                    </div>
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
                            <?php if (!empty($associations)): ?>
                                <div class="mb-3" id="associationQuestionGroup">
                                    <label class="form-label d-block">Possuí vínculo associativo?</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="associationAnswer" id="associationAnswerNo" value="no" checked>
                                        <label class="form-check-label" for="associationAnswerNo">Não</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="associationAnswer" id="associationAnswerYes" value="yes">
                                        <label class="form-check-label" for="associationAnswerYes">Sim</label>
                                    </div>

                                    <div class="mt-2 d-none" id="associationSelectGroup">
                                        <select class="form-select" id="associationPartnerId" name="initialData[associationPartnerId]">
                                            <option value="">Selecione seu vínculo</option>
                                            <?php foreach ($associations as $association): ?>
                                                <option value="<?= h($association->id) ?>"><?= h($association->name) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Selecione seu vínculo.</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3" id="promotionalCodeGroup">
                                <label for="promotionalCode" class="form-label" id="promotionalCodeLabel">
                                    Código promocional <span class="text-muted fw-normal" id="promotionalCodeOptionalHint">(opcional)</span>
                                </label>
                                <div class="input-group">
                                    <input type="text"
                                        class="form-control text-uppercase"
                                        id="promotionalCode"
                                        name="initialData[promotionalCode]"
                                        placeholder="Digite o código, se você tiver um"
                                        maxlength="30"
                                        autocomplete="off"
                                        spellcheck="false">
                                    <span class="input-group-text d-none" id="promotionalCodeSpinner">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    </span>
                                    <button type="button" class="btn btn-outline-secondary d-none" id="promotionalCodeRemove">
                                        Remover
                                    </button>
                                </div>
                                <div id="promotionalCodeFeedback" class="small mt-1"></div>
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
                                        <input type="date" max="9999-12-31" class="form-control" name="personalData[birthDate]" placeholder="Data de nascimento" value="<?= $_GET['date']; ?>" required>
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
                            <div class="alert alert-info d-none" id="planLockedNotice">
                                <strong>Valores ajustados pela Sul Previdência.</strong>
                                Para alterá-los, fale com seu atendente.
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="benefitEntryAge" class="form-label">Idade para entrada em benefício</label>
                                        <input type="number" class="form-control" name="plans[benefitEntryAge]" placeholder="Idade para entrada em benefício">
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="planMonthlyInvestment" class="form-label">Investimento mensal <small class="text-muted">(mínimo R$ 100,00)</small></label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control money" id="planMonthlyInvestment" value="<?= number_format($totalMonthlyContributionPlan, 2, '.', ''); ?>">
                                            <button type="button" class="btn btn-outline-primary" id="btnRecalculatePlan" onclick="recalculatePlan();">Recalcular</button>
                                        </div>
                                        <div id="planRecalculateError" class="text-danger mt-1" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>
                            <!--
                                O código do corretor deixou de ser campo da
                                etapa: remover risco é ato do admin sobre uma
                                adesão, não escolha de quem preenche. O campo
                                oculto mantém o link de divulgação
                                (?broker=CODIGO) atribuindo a adesão ao
                                corretor, que é o que ele de fato faz.
                            -->
                            <input type="hidden" id="brokerCode" name="plans[brokerCode]" value="">

                            <div class="row">
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
                            <div class="row" id="survivorsPensionPlanRow">
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
                            <div class="row" id="disabilityRetirementPlanRow">
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
                                    <div id="planTotalMonthlyContribution"><?= Number::currency($totalMonthlyContributionPlan, null); ?></div>
                                </div>
                            </div>
                        </div>

                        <div id="dependents" class="hidden">
                            <div class="row mb-2">
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
                                        <div class="position-relative">
                                            <input type="hidden" name="otherInformations[mainOccupationDescription]" id="mainOccupationDescription">
                                            <input type="hidden" name="otherInformations[mainOccupationCode]" id="mainOccupationCode">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="mainOccupationSearch" placeholder="Digite para buscar..." autocomplete="off" required>
                                                <div class="invalid-feedback">
                                                    Preenchimento obrigatório.
                                                </div>
                                                <span class="input-group-text" id="occupationLoading" style="display:none;">
                                                    <div class="spinner-border spinner-border-sm" role="status">
                                                        <span class="visually-hidden">Carregando...</span>
                                                    </div>
                                                </span>
                                            </div>
                                            <div id="occupationResults" class="list-group position-absolute w-100 shadow bg-white" style="max-height: 200px; overflow-y: auto; z-index: 1000;"></div>

                                        </div>
                                        <script>
                                            $(document).ready(function() {
                                                let searchTimeout;
                                                const $searchInput = $('#mainOccupationSearch');
                                                const $hiddenInput = $('#mainOccupationCode');
                                                const $resultsDiv = $('#occupationResults');

                                                $searchInput.on('input', function() {
                                                    clearTimeout(searchTimeout);
                                                    const term = $(this).val();

                                                    $hiddenInput.val('');
                                                    $('#mainOccupationDescription').val('');

                                                    if (term.length < 3) {
                                                        $resultsDiv.hide().empty();
                                                        return;
                                                    }

                                                    searchTimeout = setTimeout(function() {
                                                        $.ajax({
                                                            url: '/occupations/search',
                                                            dataType: 'json',
                                                            data: {
                                                                term: term
                                                            },
                                                            beforeSend: function() {
                                                                $('#occupationLoading').show();
                                                            },
                                                            success: function(data) {
                                                                $('#occupationLoading').hide();
                                                                $resultsDiv.empty();

                                                                if (data && data.length > 0) {
                                                                    data.forEach(function(item) {
                                                                        const $item = $('<a href="#" class="list-group-item list-group-item-action"></a>')
                                                                            .text(item.description)
                                                                            .data('id', item.id)
                                                                            .data('description', item.description);

                                                                        $resultsDiv.append($item);
                                                                    });
                                                                    console.log($resultsDiv);
                                                                    $resultsDiv.show();
                                                                } else {
                                                                    $resultsDiv.hide();
                                                                }
                                                            },
                                                            error: function() {
                                                                $('#occupationLoading').hide();
                                                                $resultsDiv.hide();
                                                            }
                                                        });
                                                    }, 1200);
                                                });

                                                $resultsDiv.on('click', 'a.list-group-item', function(e) {
                                                    e.preventDefault();
                                                    const id = $(this).data('id');
                                                    const description = $(this).data('description');

                                                    $('#mainOccupationCode').val(id);
                                                    $('#mainOccupationDescription').val(description);
                                                    $searchInput.val(description);
                                                    $resultsDiv.hide();

                                                    $searchInput.removeClass('is-invalid');
                                                });

                                                $(document).on('click', function(e) {
                                                    if (!$(e.target).closest('.position-relative').length) {
                                                        $resultsDiv.hide();
                                                        if (!$hiddenInput.val()) {
                                                            $searchInput.val('');
                                                        }
                                                    }
                                                });

                                                $('form').on('submit', function() {
                                                    if (!$hiddenInput.val()) {
                                                        $searchInput.addClass('is-invalid');
                                                        return false;
                                                    }
                                                });
                                            });
                                        </script>
                                        <div class="invalid-feedback">
                                            Preenchimento obrigatório.
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Categoria*</label>
                                        <select class="form-select" name="otherInformations[category]" required>
                                            <option value="">Selecione...</option>
                                            <option value="Aposentado">Aposentado</option>
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
                                <div class="col">
                                    <div class="mb-3">
                                        <label class="form-label">Renda mensal bruta aproximada*</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="text" class="form-control money" name="otherInformations[monthlyIncome]" placeholder="Renda mensal bruta aproximada" required>
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
                                        <label for="company" class="form-label">Empresa que trabalha/última empresa*</label>
                                        <input type="text" class="form-control" name="otherInformations[company]" placeholder="Empresa que trabalha/última empresa" required>
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
                                            <input class="form-check-input" type="radio" name="otherInformations[brazilianResident]" id="brazilianResidentYes" value="1" onclick="showHide(false, 'brazilianResidentObs')">
                                            <label class="form-check-label" for="brazilianResidentYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="otherInformations[brazilianResident]" id="brazilianResidentNo" value="0" onclick="showHide(true, 'brazilianResidentObs')">
                                            <label class="form-check-label" for="brazilianResidentNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                        <div id="brazilianResidentObs" class="mb-3" style="display: none;">
                                            <label for="" class="form-label">Especificar:*</label>
                                            <input type="text" class="form-control" name="otherInformations[brazilianResidentObs]" placeholder="País de residência">
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
                                            <input class="form-check-input" type="radio" name="otherInformations[politicallyExposed]" id="politicallyExposedYes" value="1" onclick="showHide(true, 'politicallyExposedObs')">
                                            <label class="form-check-label" for="politicallyExposedYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="otherInformations[politicallyExposed]" id="politicallyExposedNo" value="0" onclick="showHide(false, 'politicallyExposedObs')">
                                            <label class="form-check-label" for="politicallyExposedNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                        <div id="politicallyExposedObs" class="mb-3" style="display: none;">
                                            <label for="" class="form-label">Especificar:*</label>
                                            <input type="text" class="form-control" name="otherInformations[politicallyExposedObs]" placeholder="Especificar">
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
                                            <input class="form-check-input" type="radio" name="otherInformations[obligationOtherCountries]" id="obligationOtherCountriesYes" value="1" onclick="showHide(true, 'obligationOtherCountriesObs')">
                                            <label class="form-check-label" for="obligationOtherCountriesYes">Sim</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="otherInformations[obligationOtherCountries]" id="obligationOtherCountriesNo" value="0" onclick="showHide(false, 'obligationOtherCountriesObs')">
                                            <label class="form-check-label" for="obligationOtherCountriesNo">Não</label>
                                            <div class="invalid-feedback">
                                                Preenchimento obrigatório.
                                            </div>
                                        </div>
                                        <div id="obligationOtherCountriesObs" class="mb-3" style="display: none;">
                                            <label for="" class="form-label">Especificar:*</label>
                                            <input type="text" class="form-control" name="otherInformations[obligationOtherCountriesObs]" placeholder="Especificar">
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
                                                <input class="form-check-input" type="checkbox" name="pensionScheme[pensionSchemeType][]" id="pensionSchemeTypeGeral" value="Geral (INSS)">
                                                <label class="form-check-label" for="pensionSchemeTypeGeral">Geral (INSS)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="pensionScheme[pensionSchemeType][]" id="pensionSchemeTypeServidorPublico" value="Próprio (Servidor público)">
                                                <label class="form-check-label" for="pensionSchemeTypeServidorPublico">Próprio (Servidor público)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="pensionScheme[pensionSchemeType][]" id="pensionSchemeTypeComplementar" value="Complementar (Fundos de pensão)">
                                                <label class="form-check-label" for="pensionSchemeTypeComplementar">Complementar (Fundos de pensão)</label>
                                                <div class="invalid-feedback" id="pensionSchemeTypeInvalidFeedback">
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
                                            <input type="text" class="form-control money" id="paymentTotalContribution" name="paymentDetail[total_contribution]" placeholder="Total da contribuição - R$ (1+2)" value="<?= $totalMonthlyContributionPlan; ?>" readonly>
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
                                    <p>Adesão registrada com sucesso. Redirecionando para o pagamento...</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" id="fakerFillBtn" class="btn btn-outline-secondary me-auto" style="display: none;" onclick="fillStepWithFakeData(currentStepId)">🎲 Preencher (dev)</button>
                    <button type="button" class="btn btn-secondary" onclick="previousPage()">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="nextPage()">Concordo</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const isDebug = <?= Configure::read('debug') ? 'true' : 'false' ?>;
        let initialDataId = null;
        // Devolvido pelo servidor quando a adesão nasce, e reenviado a cada
        // gravação seguinte: é ele que autoriza escrever *nesta* adesão. Era
        // gerado aqui e guardado no localStorage, mas initialDataId volta a
        // null a cada recarregamento, então o mesmo valor acabava servindo a
        // várias adesões e o link de pagamento ficava ambíguo.
        let storageUuid = null;
        /* ---------------------------------------------------------------
         * Passos do formulário
         *
         * Esta lista é a ordem canônica. Cada passo se identifica pelo seu
         * `id` — o mesmo do <div> correspondente e o mesmo que
         * RegistrationsController::save() espera — e nunca por posição: a
         * navegação opera sobre os passos *visíveis*, calculados na hora, e
         * um passo condicional faz os índices deixarem de ser contíguos.
         *
         * Ganchos, todos opcionais:
         *   visible()        o passo entra na navegação? (padrão: sim)
         *   onEnter()        roda ao exibir o passo
         *   beforeValidate() roda antes da validação do HTML; false aborta
         *                    sem marcar o formulário
         *   validate()       soma-se à validação do HTML; false marca o
         *                    formulário como was-validated
         *   afterValidate()  roda depois do portão de validação; false
         *                    aborta (para quem já mostra o próprio aviso)
         * ------------------------------------------------------------- */
        const registerPages = [{
                title: 'Dados iniciais',
                id: 'initialData',
                beforeValidate: (btnPrimary) => validateInitialDataStep(btnPrimary),
            },
            {
                title: 'Dados pessoais',
                id: 'personalData',
                onEnter: () => {
                    const name = $('#registerModal #initialData input[name="initialData[name]"]').val();

                    $('#registerModal #personalData input[name="personalData[name]"]').val(name);
                },
            },
            {
                title: 'Documentos',
                id: 'documents',
            },
            {
                title: 'Beneficiário(s)',
                id: 'dependents',
                afterValidate: () => {
                    if (checkDependents())
                        return true;

                    alert('A porcentagem de participação total é diferente de 100%, favor verificar.');

                    return false;
                },
            },
            {
                title: 'Endereço',
                id: 'addressData',
            },
            {
                title: 'Outras informações',
                id: 'otherInformation',
                validate: () => {
                    if ($('#mainOccupationCode').val())
                        return true;

                    $('#mainOccupationSearch').addClass('is-invalid');

                    return false;
                },
            },
            {
                title: 'Regime de previdência',
                id: 'pensionScheme',
                onEnter: () => {
                    const planFor = $('#registerModal #personalData input[name="personalData[planFor]"]:checked').val();
                    const anyPensionSchema = $('#registerModal #pensionScheme input[name="pensionScheme[anyPensionSchema]"]').is(':checked');

                    if (planFor === 'Dependente') {
                        $('#registerModal #pensionSchemeAnyPensionSchema').hide();

                        pensionSchema(false);

                        return;
                    }

                    $('#registerModal #pensionSchemeType').slideUp();

                    if (!anyPensionSchema) {
                        $('#registerModal #pensionSchemeAnyPensionSchema input[type="checkbox"]').prop('checked', false);
                        $('#registerModal #pensionSchemeAnyPensionSchema').show();

                        return;
                    }

                    $('#registerModal #pensionScheme input[name="pensionScheme[anyPensionSchema]"]:checked').click();
                },
                validate: () => {
                    if (!$('#pensionSchemeType').is(':visible'))
                        return true;

                    const checked = $('#pensionSchemeType input[name="pensionScheme[pensionSchemeType][]"]:checked').length > 0;

                    $('#pensionSchemeTypeComplementar').toggleClass('is-invalid', !checked);

                    return checked;
                },
            },
            {
                title: 'Plano',
                id: 'plan',
                onEnter: () => {
                    const age = calculateAge($('#registerModal input[name="personalData[birthDate]"]').val());
                    const benefitEntry = age <= 55 ? 65 : age + 10;

                    $('#registerModal input[name="plans[benefitEntryAge]"]').val(benefitEntry);

                    // Uma proposta retomada pode chegar com risco já removido
                    // pelo admin: as linhas correspondentes não podem aparecer.
                    updateRiskVisibility();
                    applyPlanLock();
                },
            },
            {
                title: 'Declarações do proponente',
                id: 'proponentStatement',
                // Sem nenhum risco contratado, a Declaração Pessoal de Saúde
                // não tem o que subscrever e o passo sai da navegação.
                visible: () => !shouldSkipHealthStep(),
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

        let currentStepId = registerPages[0].id;

        const visibleSteps = () => registerPages.filter((step) => !step.visible || step.visible());

        const currentStep = () => registerPages.find((step) => step.id === currentStepId);

        const currentPosition = () => visibleSteps().findIndex((step) => step.id === currentStepId);

        let registerModal;

        document.addEventListener('DOMContentLoaded', function() {
            const openModalBtn = document.getElementById('simulador-continuar');
            const registerModalEl = document.getElementById('registerModal');
            registerModal = new bootstrap.Modal(registerModalEl);

            openModalBtn.addEventListener('click', function() {
                currentStepId = registerPages[0].id;

                updatePage();

                registerModal.show();
            });

            initPromotionalCode();
            initBrokerCode();

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

        const updateButtonPreviousNext = () => {
            const position = currentPosition();
            const isFirst = position === 0;
            const isLast = position === visibleSteps().length - 1;

            jQuery('#fakerFillBtn').toggle(isDebug && !isLast);

            if (isLast) {
                jQuery('#registerModal .modal-footer .btn-secondary').hide();
                jQuery('#registerModal .modal-footer .btn-primary').text('Fechar');

                return;
            }

            if (isFirst) {
                jQuery('#registerModal .modal-footer .btn-secondary').text('Cancelar').show();
                jQuery('#registerModal .modal-footer .btn-primary').text('Concordo');

                return;
            }

            jQuery('#registerModal .modal-footer .btn-secondary').text('Anterior').show();
            jQuery('#registerModal .modal-footer .btn-primary').text('Próximo');
        }

        const updatePage = () => {
            registerPages.forEach((step) => $(`#registerModal #${step.id}`).hide());

            const step = currentStep();

            if (step.onEnter)
                step.onEnter();

            $(`#registerModal #${step.id}`).fadeIn().show();

            $('#registerModal .modal-body h4').html(step.title);
            updateButtonPreviousNext();
        }

        /* ---------------------------------------------------------------
         * Código promocional
         *
         * Estados: empty | checking | valid | invalid | error
         * Apenas 'empty' e 'valid' permitem avançar da etapa 1.
         * ------------------------------------------------------------- */
        const promoValidateUrl = '<?= $this->Url->build(['controller' => 'PromotionalCodes', 'action' => 'validate', 'prefix' => false]) ?>';
        const promoDebounceMs = 500;
        const promoMinLength = 3;

        let promoState = { status: 'empty', code: null, partnerName: null, logoUrl: null, color: null };
        let promoDebounceTimer = null;
        let promoAbortController = null;

        const promoNormalize = (value) => (value || '')
            .normalize('NFD')
            .replace(new RegExp('[\\u0300-\\u036f]', 'g'), '')
            .toUpperCase()
            .replace(/[^A-Z0-9-]/g, '');

        const promoEls = () => ({
            input: document.getElementById('promotionalCode'),
            spinner: document.getElementById('promotionalCodeSpinner'),
            remove: document.getElementById('promotionalCodeRemove'),
            feedback: document.getElementById('promotionalCodeFeedback'),
        });

        /* ---------------------------------------------------------------
         * Vínculo associativo
         *
         * Só existe no DOM quando há ao menos um vínculo ativo cadastrado
         * (ver SimulatorController::index()). O código promocional vira
         * obrigatório quando a resposta é "sim", e passa a restringir a
         * validação ao parceiro do vínculo selecionado.
         * ------------------------------------------------------------- */
        const hasAssociationQuestion = document.getElementById('associationQuestionGroup') !== null;

        const associationEls = () => ({
            yesRadio: document.getElementById('associationAnswerYes'),
            noRadio: document.getElementById('associationAnswerNo'),
            selectGroup: document.getElementById('associationSelectGroup'),
            select: document.getElementById('associationPartnerId'),
        });

        const isAssociationYes = () => hasAssociationQuestion && associationEls().yesRadio.checked;

        const currentAssociationId = () => isAssociationYes() ? (associationEls().select.value || '') : '';

        const updatePromotionalCodeRequirement = () => {
            const hint = document.getElementById('promotionalCodeOptionalHint');

            if (hint) hint.textContent = isAssociationYes() ? '(obrigatório)' : '(opcional)';
        };

        const setAssociationAnswer = (isYes, partnerId = '') => {
            if (!hasAssociationQuestion) return;

            const { yesRadio, noRadio, selectGroup, select } = associationEls();

            yesRadio.checked = isYes;
            noRadio.checked = !isYes;
            selectGroup.classList.toggle('d-none', !isYes);
            select.required = isYes;

            if (isYes && partnerId) select.value = String(partnerId);
            if (!isYes) select.value = '';

            updatePromotionalCodeRequirement();
        };

        /**
         * Mostrado junto do erro do código quando a pessoa afirmou ter
         * vínculo, mas o código não resolve: o vínculo nunca impede uma
         * adesão, então sempre há uma saída para seguir sem ele.
         */
        const appendAssociationEscapeHatch = (feedback) => {
            if (!isAssociationYes()) return;

            const { select } = associationEls();
            const option = select.options[select.selectedIndex];
            const partnerName = option && option.value ? option.textContent : 'seu vínculo';

            const hatch = document.createElement('div');
            hatch.className = 'mt-1';
            hatch.innerHTML = 'Não tem um código válido? Entre em contato com ' + partnerName +
                ', ou <button type="button" class="btn btn-link btn-sm p-0 align-baseline" id="associationSkip">continue sem vínculo</button>.';
            feedback.appendChild(hatch);

            document.getElementById('associationSkip').addEventListener('click', () => {
                setAssociationAnswer(false);
                clearPromoCode();
            });
        };

        const initAssociationQuestion = () => {
            if (!hasAssociationQuestion) return;

            const { yesRadio, noRadio, select } = associationEls();

            // Trocar a resposta ou o vínculo selecionado revalida o código já
            // digitado: ele pode não pertencer ao vínculo escolhido agora.
            [yesRadio, noRadio].forEach((radio) => radio.addEventListener('change', () => {
                setAssociationAnswer(yesRadio.checked);

                const { input } = promoEls();

                if (input.value) lookupPromoCode(input.value);
            }));

            select.addEventListener('change', () => {
                const { input } = promoEls();

                if (input.value) lookupPromoCode(input.value);
            });
        };

        const updatePartnerHeader = () => {
            const wrapper = document.getElementById('registerModalPartner');
            const logo = document.getElementById('registerModalPartnerLogo');
            const label = document.getElementById('registerModalPartnerLabel');

            if (promoState.status !== 'valid') {
                wrapper.classList.add('d-none');
                wrapper.classList.remove('d-flex');
                wrapper.style.borderColor = '';
                logo.classList.add('d-none');
                logo.removeAttribute('src');
                label.textContent = '';

                return;
            }

            wrapper.classList.remove('d-none');
            wrapper.classList.add('d-flex');
            wrapper.style.borderColor = promoState.color || '';

            if (promoState.logoUrl) {
                logo.src = promoState.logoUrl;
                logo.alt = promoState.partnerName;
                logo.classList.remove('d-none');
                // Se a imagem falhar, cai para o nome do parceiro em texto.
                logo.onerror = () => {
                    logo.classList.add('d-none');
                    label.textContent = promoState.partnerName;
                };
                label.textContent = '';
            } else {
                logo.classList.add('d-none');
                logo.removeAttribute('src');
                label.textContent = promoState.partnerName;
            }
        };

        const setPromoState = (status, data = {}) => {
            promoState = {
                status,
                code: data.code ?? null,
                partnerName: data.partnerName ?? null,
                logoUrl: data.logoUrl ?? null,
                color: data.color ?? null,
            };

            const { input, spinner, remove, feedback } = promoEls();

            input.classList.remove('is-valid', 'is-invalid', 'border-warning');
            spinner.classList.add('d-none');
            remove.classList.add('d-none');
            feedback.className = 'small mt-1';
            feedback.textContent = '';

            if (status === 'checking') {
                spinner.classList.remove('d-none');
            } else if (status === 'valid') {
                input.classList.add('is-valid');
                remove.classList.remove('d-none');
                feedback.classList.add('text-success');
                feedback.textContent = '✓ ' + data.partnerName;
            } else if (status === 'invalid') {
                input.classList.add('is-invalid');
                feedback.classList.add('text-danger');
                feedback.textContent = '✗ ' + (data.message || 'Código promocional não encontrado.');

                appendAssociationEscapeHatch(feedback);
            } else if (status === 'error') {
                input.classList.add('border-warning');
                feedback.classList.add('text-warning-emphasis');
                feedback.innerHTML =
                    'Não foi possível validar o código agora. ' +
                    '<button type="button" class="btn btn-link btn-sm p-0 align-baseline" id="promotionalCodeRetry">Tentar novamente</button>' +
                    ' ou <button type="button" class="btn btn-link btn-sm p-0 align-baseline" id="promotionalCodeSkip">continuar sem o código</button>.';

                document.getElementById('promotionalCodeRetry')
                    .addEventListener('click', () => lookupPromoCode(promoNormalize(input.value)));
                document.getElementById('promotionalCodeSkip')
                    .addEventListener('click', clearPromoCode);

                appendAssociationEscapeHatch(feedback);
            }

            updatePartnerHeader();
        };

        const clearPromoCode = () => {
            const { input } = promoEls();

            if (promoAbortController) promoAbortController.abort();
            clearTimeout(promoDebounceTimer);

            input.value = '';
            setPromoState('empty');
            input.focus();
        };

        const lookupPromoCode = async (code) => {
            if (!code || code.length < promoMinLength) {
                setPromoState(code ? 'invalid' : 'empty', {
                    message: 'O código deve ter ao menos ' + promoMinLength + ' caracteres.'
                });

                return;
            }

            // Cancela a consulta anterior para que uma resposta atrasada não
            // sobrescreva o resultado de uma consulta mais recente.
            if (promoAbortController) promoAbortController.abort();
            promoAbortController = new AbortController();

            setPromoState('checking');

            try {
                const associationId = currentAssociationId();
                const url = promoValidateUrl + '?code=' + encodeURIComponent(code) +
                    (associationId ? '&associationId=' + encodeURIComponent(associationId) : '');

                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: promoAbortController.signal,
                });

                const result = await response.json();

                if (result.valid) {
                    // Código de um vínculo associativo, mas a pergunta ainda
                    // estava em "não": corrige a resposta em vez de bloquear.
                    if (result.autoAssociation) {
                        setAssociationAnswer(true, result.autoAssociation.id);
                    }

                    setPromoState('valid', result);
                } else {
                    setPromoState('invalid', result);
                }
            } catch (error) {
                if (error.name === 'AbortError') return;

                setPromoState('error');
            }
        };

        const initPromotionalCode = () => {
            const { input, remove } = promoEls();

            input.addEventListener('input', () => {
                const normalized = promoNormalize(input.value);

                if (input.value !== normalized) {
                    const position = input.selectionStart;
                    input.value = normalized;
                    input.setSelectionRange(position, position);
                }

                clearTimeout(promoDebounceTimer);

                if (normalized === '') {
                    if (promoAbortController) promoAbortController.abort();
                    setPromoState('empty');

                    return;
                }

                promoDebounceTimer = setTimeout(() => lookupPromoCode(normalized), promoDebounceMs);
            });

            input.addEventListener('blur', () => {
                const normalized = promoNormalize(input.value);

                if (normalized === '' || promoState.status === 'valid' || promoState.status === 'checking') return;

                clearTimeout(promoDebounceTimer);
                lookupPromoCode(normalized);
            });

            remove.addEventListener('click', clearPromoCode);

            // Link atribuído do parceiro: ?promo=CODIGO chega preenchido e validado.
            const fromUrl = promoNormalize(new URLSearchParams(window.location.search).get('promo'));

            if (fromUrl) {
                input.value = fromUrl;
                lookupPromoCode(fromUrl);
            }

            initAssociationQuestion();
        };

        /* ---------------------------------------------------------------
         * Riscos contratados
         *
         * Quem decide é o servidor, e só o admin muda: o formulário público
         * não tem mais como remover risco. Estas variáveis nascem do que a
         * página recebeu e se realinham a cada recálculo, que devolve o que o
         * servidor de fato aplicou.
         * ------------------------------------------------------------- */
        let hasSurvivorsPension = <?= $includeSurvivorsPension ? 'true' : 'false' ?>;
        let hasDisabilityRetirement = <?= $includeDisabilityRetirement ? 'true' : 'false' ?>;

        /**
         * Plano com valor ajustado à mão pelo admin.
         *
         * Nasce falso: uma proposta nova é sempre fórmula pura, e só uma
         * proposta retomada chega ajustada. Enquanto vale, o investimento
         * mensal e o "Recalcular" ficam bloqueados -- um clique rodaria a
         * fórmula de novo e apagaria, em silêncio, o que foi negociado por
         * telefone. A data de nascimento também trava: os valores foram
         * calculados para aquela idade, e a procedure escolhe custo unitário e
         * teto por ela, então mudá-la tornaria o capital atuarialmente
         * impossível. O servidor recusa de todo jeito (ver
         * RegistrationsController::save); isto é a tela contando o porquê.
         */
        let planLocked = false;

        const applyPlanLock = () => {
            document.getElementById('planLockedNotice').classList.toggle('d-none', !planLocked);
            document.getElementById('planMonthlyInvestment').disabled = planLocked;
            document.getElementById('btnRecalculatePlan').disabled = planLocked;

            const birthDate = document.querySelector('#registerModal input[name="personalData[birthDate]"]');

            if (birthDate)
                birthDate.readOnly = planLocked;
        };

        const updateRiskVisibility = () => {
            document.getElementById('survivorsPensionPlanRow').classList.toggle('d-none', !hasSurvivorsPension);
            document.getElementById('disabilityRetirementPlanRow').classList.toggle('d-none', !hasDisabilityRetirement);
        };

        /**
         * Sem nenhum risco contratado, a Declaração Pessoal de Saúde não tem o
         * que subscrever. Só com os dois removidos: as perguntas subscrevem
         * morte e invalidez, e esconder a declaração com um risco vivo
         * deixaria exposição não subscrita num contrato assinado.
         */
        const shouldSkipHealthStep = () => !hasSurvivorsPension && !hasDisabilityRetirement;

        /**
         * Link de divulgação do corretor: ?broker=CODIGO segue atribuindo a
         * adesão, agora sem campo visível e sem validação em tempo real -- o
         * servidor revalida o código ao gravar, que sempre foi a única
         * garantia de verdade.
         */
        const initBrokerCode = () => {
            const fromUrl = promoNormalize(new URLSearchParams(window.location.search).get('broker'));

            if (fromUrl)
                document.getElementById('brokerCode').value = fromUrl;
        };

        /**
         * Gancho beforeValidate do passo "Dados iniciais": um código
         * promocional preenchido precisa estar resolvido antes de avançar.
         * Campo vazio segue normalmente (é opcional), a menos que a pessoa
         * tenha afirmado ter vínculo associativo.
         */
        const validateInitialDataStep = async (btnPrimary) => {
            if (promoState.status === 'checking') {
                // Aguarda a consulta em andamento terminar e reavalia.
                // Um teto evita travar o botão para sempre caso a
                // requisição fique pendurada sem nunca resolver.
                btnPrimary.disabled = true;

                await new Promise((resolve) => {
                    let elapsed = 0;
                    const poll = setInterval(() => {
                        elapsed += 100;

                        if (promoState.status !== 'checking') {
                            clearInterval(poll);
                            resolve();
                        } else if (elapsed >= 15000) {
                            clearInterval(poll);
                            setPromoState('error');
                            resolve();
                        }
                    }, 100);
                });

                btnPrimary.disabled = false;
            }

            if (promoState.status === 'invalid' || promoState.status === 'error') {
                document.getElementById('promotionalCode').focus();

                return false;
            }

            // Vínculo associativo torna o código obrigatório e exige que
            // o vínculo esteja selecionado.
            if (isAssociationYes()) {
                if (promoState.status !== 'valid') {
                    document.getElementById('promotionalCode').focus();

                    return false;
                }

                const { select } = associationEls();

                if (!select.value) {
                    select.focus();

                    return false;
                }
            }

            return true;
        };

        const nextPage = async () => {
            const btnPrimary = document.querySelector('#registerModal .modal-footer .btn-primary');
            const step = currentStep();

            if (currentPosition() === visibleSteps().length - 1) {
                btnPrimary.disabled = true;
                btnPrimary.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Aguarde...';
                window.location.reload();
                return;
            }

            if (step.beforeValidate && !(await step.beforeValidate(btnPrimary)))
                return;

            let isValid = true;
            const form = document.querySelectorAll(`#${step.id} input, #${step.id} select`);

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

            if (step.validate && !step.validate())
                isValid = false;

            if (!isValid) {
                $(`#registerModalForm #${step.id}`)[0].classList.add('was-validated')

                return;
            }

            if (step.afterValidate && !step.afterValidate())
                return;

            btnPrimary.disabled = true;
            btnPrimary.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Aguarde...';

            try {
                const response = await saveForm(step.id);

                if (response.redirectUrl) {
                    window.location.href = response.redirectUrl;

                    return;
                }

                // Os passos visíveis são recalculados *depois* de gravar: a
                // resposta do servidor pode mudar quais riscos a adesão tem,
                // e com isso fazer a etapa de saúde aparecer ou sair.
                goToAdjacentStep(1);

                updatePage()
            } catch (error) {
                alert(error?.message || 'Não foi possível avançar. Tente novamente em instantes.');
            } finally {
                btnPrimary.disabled = false;
                updateButtonPreviousNext();
            }
        }

        /**
         * Move o passo corrente `offset` posições na lista de passos
         * visíveis. Um passo invisível simplesmente não está na lista, então
         * não existe "pular": a aritmética já o ignora.
         */
        const goToAdjacentStep = (offset) => {
            const steps = visibleSteps();
            const target = steps[steps.findIndex((step) => step.id === currentStepId) + offset];

            if (target)
                currentStepId = target.id;
        };

        const previousPage = () => {
            if (currentPosition() === 0) {
                registerModal.hide();

                return;
            }

            goToAdjacentStep(-1);

            updatePage()
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
                    data: formElements.serialize()
                        + (storageUuid !== null ? `&storageUuid=${encodeURIComponent(storageUuid)}` : '')
                        + (initialDataId !== null ? `&initialDataId=${initialDataId}` : ''),
                    dataType: 'json',
                    beforeSend: () => {},
                    success: (response) => {
                        if (response?.success === true && response?.initialDataId) {
                            initialDataId = response.initialDataId;
                            storageUuid = response.storageUuid;
                        }

                        if (response?.success === false) {
                            reject(response);
                            return;
                        }

                        resolve(response);
                    },
                    error: () => {
                        reject({ message: 'Não foi possível se comunicar com o servidor. Verifique sua conexão e tente novamente.' });
                    }
                })
            })
        }

        const fakeFirstNames = ['Ana', 'Bruno', 'Carla', 'Daniel', 'Eduarda', 'Fábio', 'Gabriela', 'Hugo', 'Isabela', 'João', 'Larissa', 'Marcos', 'Natália', 'Otávio', 'Patrícia', 'Rafael', 'Sofia', 'Thiago'];
        const fakeLastNames = ['Silva', 'Souza', 'Oliveira', 'Santos', 'Pereira', 'Costa', 'Almeida', 'Ribeiro', 'Carvalho', 'Gomes', 'Martins', 'Rocha'];

        const fakePick = (arr) => arr[Math.floor(Math.random() * arr.length)];
        const fakeInt = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;
        const fakeDigits = (n) => Array.from({
            length: n
        }, () => fakeInt(0, 9)).join('');

        const fakeCPF = () => {
            const calcDigit = (digits) => {
                let sum = 0;
                let weight = digits.length + 1;

                digits.forEach((digit) => {
                    sum += digit * weight;
                    weight -= 1;
                });

                const rest = (sum * 10) % 11;

                return rest === 10 ? 0 : rest;
            };

            const base = Array.from({
                length: 9
            }, () => fakeInt(0, 9));
            const d1 = calcDigit(base);
            const d2 = calcDigit([...base, d1]);
            const all = [...base, d1, d2];

            return `${all.slice(0, 3).join('')}.${all.slice(3, 6).join('')}.${all.slice(6, 9).join('')}-${all.slice(9).join('')}`;
        };

        const fakeName = () => `${fakePick(fakeFirstNames)} ${fakePick(fakeLastNames)}`;

        const fakeEmail = () => {
            const base = fakeName()
                .toLowerCase()
                .normalize('NFD')
                .replace(new RegExp('[\\u0300-\\u036f]', 'g'), '')
                .replace(/[^a-z ]/g, '')
                .trim()
                .replace(/\s+/g, '.');

            return `${base}.${fakeInt(100, 999)}@teste.dev`;
        };

        const fakePhone = () => `(${fakeInt(11, 99)}) 9${fakeDigits(4)}-${fakeDigits(4)}`;

        const fakeCEP = () => '01310-930';

        const fakeDateISO = (minAge, maxAge) => {
            const today = new Date();
            const age = fakeInt(minAge, maxAge);
            const month = fakeInt(1, 12);
            const day = fakeInt(1, 28);

            return `${today.getFullYear() - age}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        };

        const fakeMoney = (min, max) => `${fakeInt(min, max)},00`;

        const fillStepWithFakeData = (stepId) => {
            const $container = $(`#registerModal #${stepId}`);
            const radioGroups = {};

            $container.find('input[type=radio]').each(function() {
                const name = $(this).attr('name');

                if (!name)
                    return;

                radioGroups[name] = radioGroups[name] || [];
                radioGroups[name].push(this);
            });

            const preferredRadioValues = {
                'paymentDetail[payment_type]': 'Boleto bancário',
            };

            Object.entries(radioGroups).forEach(([name, options]) => {
                const preferredValue = preferredRadioValues[name];
                const preferredOption = preferredValue && options.find((option) => option.value === preferredValue);
                const noOption = options.find((option) => option.value === '0');
                const chosen = preferredOption || noOption || fakePick(options);

                $(chosen).prop('checked', true).trigger('click').trigger('change');
            });

            $container.find('select').each(function() {
                const $select = $(this);

                if ($select.prop('disabled'))
                    return;

                const options = $select.find('option').filter((_, option) => option.value !== '').toArray();

                if (options.length === 0)
                    return;

                $select.val(fakePick(options).value).trigger('change');
            });

            $container.find('input[type=text], input[type=email], input[type=number], input[type=date]').each(function() {
                const $input = $(this);

                if ($input.prop('readonly') || $input.prop('disabled'))
                    return;

                const name = $input.attr('name') || '';
                const id = $input.attr('id') || '';
                const type = $input.attr('type');
                let value;

                if (id === 'mainOccupationSearch') {
                    $('#mainOccupationCode').val('999999');
                    $('#mainOccupationDescription').val('Profissional liberal (dev)');
                    value = 'Profissional liberal (dev)';
                } else if ($input.hasClass('cpf')) {
                    value = fakeCPF();
                } else if ($input.hasClass('cep')) {
                    value = fakeCEP();
                } else if (type === 'email') {
                    value = fakeEmail();
                } else if (type === 'date') {
                    value = name === 'personalData[birthDate]' ? fakeDateISO(25, 70) : fakeDateISO(1, 60);
                } else if ($input.hasClass('phone')) {
                    value = fakePhone();
                } else if (name === 'proponentStatement[weight]') {
                    value = fakeMoney(50, 120);
                } else if (name === 'proponentStatement[height]') {
                    value = fakeMoney(1, 2);
                } else if ($input.hasClass('money')) {
                    value = fakeMoney(1000, 20000);
                } else if (name.includes('numberChildren')) {
                    value = String(fakeInt(0, 3));
                } else if (name.includes('benefitEntryAge')) {
                    value = String(fakeInt(60, 70));
                } else if (name === 'addresses[number]') {
                    value = String(fakeInt(1, 9999));
                } else if ($input.hasClass('participation')) {
                    return;
                } else if (type === 'number') {
                    value = String(fakeInt(1, 100));
                } else if (/name/i.test(name)) {
                    value = fakeName();
                } else {
                    value = `Teste dev ${fakeInt(1, 999)}`;
                }

                const maxLength = parseInt($input.attr('maxlength'), 10);

                if (!Number.isNaN(maxLength))
                    value = value.slice(0, maxLength);

                $input.val(value);

                if (!$input.hasClass('cep') && id !== 'mainOccupationSearch')
                    $input.trigger('input').trigger('change').trigger('blur');
            });
        };

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

        const recalculatePlan = () => {
            const birthDate = $('#registerModal input[name="personalData[birthDate]"]').val();
            const investmentInput = document.getElementById('planMonthlyInvestment');
            const value = investmentInput.value.replace(/\./g, '').replace(',', '.');
            const errorDiv = document.getElementById('planRecalculateError');
            const btn = document.getElementById('btnRecalculatePlan');

            errorDiv.style.display = 'none';
            btn.disabled = true;

            $.ajax({
                type: 'GET',
                url: `<?= $this->Url->build(['controller' => 'Simulator', 'action' => 'recalculate']) ?>`,
                data: {
                    date: birthDate,
                    value: value,
                    // Identifica a adesão para o servidor saber quais riscos
                    // ela tem; sem isso é simulação nova, com os dois.
                    initialDataId: initialDataId ?? '',
                    storageUuid: storageUuid ?? '',
                },
                dataType: 'json',
                success: (response) => {
                    if (!response.success) {
                        errorDiv.textContent = response.message || 'Não foi possível recalcular o plano.';
                        errorDiv.style.display = 'block';
                        return;
                    }

                    const formatMoney = (num) => num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                    $('#registerModal input[name="plans[benefitEntryAge]"]').val(response.benefitEntryAge);
                    $('#registerModal input[name="plans[monthly_retirement_contribution]"]').val(formatMoney(response.monthlyRetirementContribution));
                    $('#registerModal input[name="plans[monthly_survivors_pension_contribution]"]').val(formatMoney(response.monthlySurvivorsPensionContribution));
                    $('#registerModal input[name="plans[survivors_pension_insured_capital]"]').val(formatMoney(response.survivorsPensionInsuredCapital));
                    $('#registerModal input[name="plans[monthly_disability_retirement_contribution]"]').val(formatMoney(response.monthlyDisabilityRetirementContribution));
                    $('#registerModal input[name="plans[disability_retirement_insured_capital]"]').val(formatMoney(response.disabilityRetirementInsuredCapital));
                    $('#paymentTotalContribution').val(formatMoney(response.totalMonthlyContribution));
                    document.getElementById('planTotalMonthlyContribution').textContent = response.totalMonthlyContribution.toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    });

                    // O servidor é quem decide quais riscos a adesão tem: a
                    // tela se realinha ao que veio na resposta, que é como
                    // ela fica sabendo de uma remoção feita no admin.
                    if (response.hasSurvivorsPension !== undefined)
                        hasSurvivorsPension = response.hasSurvivorsPension;

                    if (response.hasDisabilityRetirement !== undefined)
                        hasDisabilityRetirement = response.hasDisabilityRetirement;

                    if (response.planLocked !== undefined) {
                        planLocked = response.planLocked;
                        applyPlanLock();
                    }

                    updateRiskVisibility();
                },
                error: () => {
                    errorDiv.textContent = 'Não foi possível recalcular o plano. Tente novamente.';
                    errorDiv.style.display = 'block';
                },
                complete: () => {
                    btn.disabled = false;
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

            if (count === 4) {
                alert('Limite de beneficiáios excedido, caso queira adicionar mais de 4 beneficiários, será necessário solicitar depois do plano efetuado.');

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
        <div class="dependentDiv border rounded p-3 mb-3 shadow-sm bg-light">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="h6 mb-0"><strong>Beneficiário ${count + 1}</strong></div>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeDependent(this);">
                    <i class="bi bi-trash"></i> Remover
                </button>
            </div>
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

        const removeDependent = (element) => {
            $(element).closest('.dependentDiv').remove();

            // Re-index remaining dependents
            $('#listDependents .dependentDiv').each(function(index) {
                $(this).find('strong').text(`Beneficiário ${index + 1}`);
                $(this).find('input, select').each(function() {
                    let name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/dependents\[\d+\]/, `dependents[${index}]`);
                        $(this).attr('name', name);
                    }
                });
            });

            // Recalculate participation
            const count = $('#listDependents .dependentDiv').length;
            if (count > 0) {
                let baseParticipation = Math.floor(100 / count);
                let remainder = 100 % count;

                $('#listDependents .dependentDiv').each(function(index) {
                    let p = baseParticipation + (index < remainder ? 1 : 0);
                    $(this).find('.participation').val(p);
                });
            }
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
            const date = $('#simulador-form input[name="dateBirth"]').val();
            const monthlyInvestmentInput = document.querySelector('#simulador-form input[name="monthlyInvestment"]');
            const value = monthlyInvestmentInput.value.replace(/\./g, '').replace(',', '.');
            let simulatorUrl = `<?= $this->Url->build(['controller' => 'Simulator', 'action' => 'index']); ?>?date=${date}&value=${value}`;

            // "Simular novamente" recarrega esta mesma página: sem isto, um
            // ?promo=/?broker= já em uso na URL atual se perderia no reload.
            const incomingParams = new URLSearchParams(window.location.search);

            ['promo', 'broker'].forEach((param) => {
                const paramValue = incomingParams.get(param);

                if (paramValue) simulatorUrl += `&${param}=${encodeURIComponent(paramValue)}`;
            });

            const form = document.querySelectorAll(`#simulador-form input`);
            const errorDiv = document.getElementById('simulador-form-error');

            errorDiv.style.display = 'none';

            form.forEach((input) => {
                if (!input.checkValidity())
                    isValid = false;
            })

            if (monthlyInvestmentInput.value && parseFloat(value) < 100) {
                isValid = false;
                errorDiv.textContent = 'Investimento mensal mínimo é R$ 100,00.';
                errorDiv.style.display = 'block';
            }

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