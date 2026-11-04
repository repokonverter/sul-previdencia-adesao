<?php

/**
 * Cenários de rentabilidade, gráfico até os 65 e o botão do PDF da
 * simulação. Usado pela aba Plano do "Visualizar" e do "Editar": no editar,
 * js.php chama window.PlanProjectionView.render() com a projeção dos valores
 * da tela depois do "Recalcular".
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AdhesionInitialData $adhesion
 * @var \App\Services\PlanProjection|null $planProjection
 */

use App\Services\PlanProjection;
use App\View\Helper\PlanChartHelper;

$planProjection ??= null;
$partner = $adhesion->association_partner ?? null;
$canDownloadPdf = !$adhesion->isNew() && $planProjection !== null;
?>
<style>
    .plan-kpi {
        border: 1px solid #e5e7eb;
        border-radius: .5rem;
        padding: .9rem 1rem;
        height: 100%;
        background: #fff;
    }

    .plan-kpi-label {
        color: #6b7280;
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .plan-kpi-value {
        color: #111827;
        font-size: 1.25rem;
        font-weight: 700;
        margin-top: .15rem;
    }

    .plan-kpi-sub {
        color: #6b7280;
        font-size: .8rem;
        margin-top: .15rem;
    }

    .plan-kpi-sub strong {
        color: #3B7A3B;
    }

    .plan-kpi-total {
        background: #20446E;
        border-color: #20446E;
    }

    .plan-kpi-total .plan-kpi-label,
    .plan-kpi-total .plan-kpi-sub {
        color: #c7d4e6;
    }

    .plan-kpi-total .plan-kpi-value {
        color: #fff;
        font-size: 1.6rem;
    }

    .plan-section-title {
        color: #20446E;
        font-size: .8rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        margin: 0;
    }

    .plan-scenario {
        border: 1px solid #e5e7eb;
        border-radius: .5rem;
        padding: .9rem 1rem;
        text-align: center;
        height: 100%;
        background: #fff;
    }

    .plan-scenario.featured {
        border: 2px solid #f37c20;
        background: #fff8f2;
    }

    .plan-scenario-rate {
        color: #4b5563;
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .plan-scenario-balance {
        color: #111827;
        font-size: 1.3rem;
        font-weight: 700;
        margin: .2rem 0;
    }

    .plan-scenario-benefit {
        color: #4b5563;
        font-size: .82rem;
    }

    .plan-scenario-benefit strong {
        color: #3B7A3B;
    }

    .plan-chart-card {
        border: 1px solid #e5e7eb;
        border-radius: .5rem;
        padding: 1rem 1rem .5rem 1rem;
        background: #fff;
    }

    .plan-chart-wrap {
        position: relative;
        height: 320px;
    }

    .plan-partner-badge {
        border: 1px solid #e5e7eb;
        border-left: 4px solid var(--partner-color, #f37c20);
        border-radius: .375rem;
        padding: .3rem .7rem;
        background: #fff;
        font-size: .82rem;
    }

    .plan-partner-badge img {
        max-height: 26px;
        max-width: 110px;
    }
</style>

<div id="planProjection" class="mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h6 class="plan-section-title">Projeção até os <?= PlanProjection::RETIREMENT_AGE ?> anos</h6>
            <div class="text-muted small">Cenários de rentabilidade sobre a contribuição de aposentadoria</div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php if ($partner !== null): ?>
                <span class="plan-partner-badge d-inline-flex align-items-center gap-2" style="--partner-color: <?= h($partner->color ?: '#f37c20') ?>;">
                    <span class="text-muted">em parceria com</span>
                    <?php if ($partner->has_logo): ?>
                        <img src="<?= h($this->Url->build(['controller' => 'Partners', 'action' => 'logo', $partner->id, 'prefix' => false])) ?>" alt="<?= h($partner->name) ?>">
                    <?php endif; ?>
                    <span class="fw-semibold"><?= h($partner->name) ?></span>
                </span>
            <?php endif; ?>

            <?php if ($canDownloadPdf): ?>
                <?= $this->Html->link(
                    '<i class="bi bi-file-earmark-pdf"></i> Gerar PDF do plano',
                    ['action' => 'planPdf', $adhesion->id],
                    ['escape' => false, 'class' => 'btn btn-primary', 'id' => 'btnPlanPdf']
                ) ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="planProjectionBody" class="d-none">
        <div class="row g-3 mb-3" id="planScenarios"></div>

        <div class="plan-chart-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-semibold small text-secondary">Evolução comparativa das contribuições</span>
                <span class="text-muted small" id="planChartCaption"></span>
            </div>
            <div class="plan-chart-wrap">
                <canvas id="planProjectionChart" role="img" aria-label="Evolução do saldo acumulado até os <?= PlanProjection::RETIREMENT_AGE ?> anos"></canvas>
            </div>
        </div>

        <div class="small text-muted mt-2">
            <div>*Os valores acima são apenas estimativas de capital acumulado aos <?= PlanProjection::RETIREMENT_AGE ?> anos e não garantem nenhum direito antecipado.</div>
            <div>**O tipo de benefício escolhido para esse plano é o Prazo Definido de 60 meses.</div>
        </div>
    </div>

    <div id="planProjectionEmpty" class="alert alert-light border d-none mb-0">
        <i class="bi bi-info-circle"></i>
        <span></span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
<script>
    window.PlanProjectionView = (() => {
        const RETIREMENT_AGE = <?= PlanProjection::RETIREMENT_AGE ?>;
        const CHART_SCENARIO = <?= PlanProjection::CHART_SCENARIO ?>;
        const PURE_COLOR = <?= json_encode(PlanChartHelper::PURE_COLOR) ?>;
        const COMPOUNDED_COLOR = <?= json_encode(PlanChartHelper::COMPOUNDED_COLOR) ?>;

        const currency = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });

        const shortCurrency = (value) => {
            if (value >= 1000000) return 'R$ ' + (value / 1000000).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' mi';
            if (value >= 1000) return 'R$ ' + (value / 1000).toLocaleString('pt-BR', { maximumFractionDigits: 0 }) + ' mil';
            return 'R$ ' + value.toFixed(0);
        };

        let chart = null;

        const showEmpty = (message) => {
            document.getElementById('planProjectionBody').classList.add('d-none');

            const empty = document.getElementById('planProjectionEmpty');
            empty.querySelector('span').textContent = message;
            empty.classList.remove('d-none');
        };

        const renderScenarios = (scenarios) => {
            const container = document.getElementById('planScenarios');

            container.replaceChildren(...scenarios.map((scenario, index) => {
                const column = document.createElement('div');
                column.className = 'col-md-4';
                column.innerHTML = `
                    <div class="plan-scenario ${index === CHART_SCENARIO ? 'featured' : ''}">
                        <div class="plan-scenario-rate">Rentabilidade ${Math.floor(scenario.rate * 100)}% a.a.</div>
                        <div class="plan-scenario-balance">${currency.format(scenario.balance)}</div>
                        <div class="plan-scenario-benefit">Benefício mensal <strong>${currency.format(scenario.monthlyBenefit)}</strong></div>
                    </div>`;

                return column;
            }));
        };

        const renderChart = (projection) => {
            const { ages, pure, compounded } = projection.series;
            const rate = Math.floor(projection.scenarios[CHART_SCENARIO].rate * 100);

            document.getElementById('planChartCaption').textContent =
                `Cenário de ${rate}% a.a. · ${currency.format(projection.retirementContribution)}/mês por ${projection.monthsUntilRetirement} meses`;

            if (chart) {
                chart.data.labels = ages;
                chart.data.datasets[0].data = compounded;
                chart.data.datasets[1].data = pure;
                chart.update();
                return;
            }

            chart = new Chart(document.getElementById('planProjectionChart'), {
                type: 'line',
                data: {
                    labels: ages,
                    datasets: [{
                            label: 'Contribuição rentabilizada',
                            data: compounded,
                            borderColor: COMPOUNDED_COLOR,
                            backgroundColor: 'rgba(232, 113, 26, 0.08)',
                            fill: 'origin',
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            pointHoverBorderWidth: 2,
                            pointHoverBorderColor: '#fff',
                            pointHoverBackgroundColor: COMPOUNDED_COLOR,
                        },
                        {
                            label: 'Contribuição pura',
                            data: pure,
                            borderColor: PURE_COLOR,
                            backgroundColor: PURE_COLOR,
                            fill: false,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            pointHoverBorderWidth: 2,
                            pointHoverBorderColor: '#fff',
                            pointHoverBackgroundColor: PURE_COLOR,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: '#eef0f4' },
                            title: {
                                display: true,
                                text: 'Montante acumulado (R$)',
                                color: '#6b7280',
                                font: { size: 12, weight: '600' }
                            },
                            ticks: {
                                color: '#6b7280',
                                callback: (value) => shortCurrency(value)
                            }
                        },
                        x: {
                            grid: { display: false },
                            title: {
                                display: true,
                                text: 'Idade',
                                color: '#6b7280',
                                font: { size: 12, weight: '600' }
                            },
                            ticks: {
                                color: '#6b7280',
                                autoSkipPadding: 12,
                                maxRotation: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'line',
                                color: '#374151',
                                boxWidth: 18
                            }
                        },
                        tooltip: {
                            backgroundColor: '#111827',
                            padding: 10,
                            callbacks: {
                                title: (items) => `${items[0].label} anos`,
                                label: (context) => `${context.dataset.label}: ${currency.format(context.parsed.y)}`
                            }
                        }
                    }
                }
            });
        };

        /**
         * @param projection PlanProjection::toArray(), ou null quando falta
         *   plano ou data de nascimento.
         */
        const render = (projection) => {
            if (!projection) {
                showEmpty('Preencha a data de nascimento e o plano para ver a projeção.');
                return;
            }

            if (!projection.hasProjection) {
                showEmpty(`Sem meses de contribuição até os ${RETIREMENT_AGE} anos: não há saldo a projetar.`);
                return;
            }

            document.getElementById('planProjectionEmpty').classList.add('d-none');
            document.getElementById('planProjectionBody').classList.remove('d-none');

            renderScenarios(projection.scenarios);
            renderChart(projection);
        };

        return { render };
    })();

    window.PlanProjectionView.render(<?= json_encode($planProjection?->toArray()) ?>);

    // No editar, o PDF sai do que está gravado: com alteração ainda não
    // salva, o documento não bateria com o que está na tela.
    (() => {
        const form = document.querySelector('.adhesion-form-container form');
        const pdfButton = document.getElementById('btnPlanPdf');

        if (!form || !pdfButton) return;

        let dirty = false;

        ['input', 'change'].forEach((eventName) => form.addEventListener(eventName, () => {
            dirty = true;
        }));

        pdfButton.addEventListener('click', (event) => {
            if (dirty && !confirm('Há alterações não salvas. O PDF usa os valores já gravados da adesão. Gerar mesmo assim?')) {
                event.preventDefault();
            }
        });
    })();
</script>
