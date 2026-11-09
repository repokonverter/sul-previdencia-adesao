/**
 * Os campos do plano (templates/element/Plan/editor.php), na aba Plano do
 * editar/criar adesão e na simulação avulsa do admin.
 *
 * Cada campo é achado por data-plan-field, e não pelo name: na adesão os
 * nomes levam o prefixo adhesion_plan[...], na simulação não. As contas
 * ficam no servidor (Admin/Simulations::recalculate, ::projection e o
 * Simulator::recalculateRiskCapital público); aqui só se lê a tela e se
 * escreve a resposta.
 */
window.PlanEditor = (() => {
    /**
     * Mesma regra de App\Utility\Money::parse() do lado do servidor: o campo
     * carrega ponto decimal cru do banco até a máscara jQuery rodar (no
     * carregamento da página) e vírgula pt-BR depois que o admin mexe nele.
     * Tratar todo ponto como separador de milhar sem checar a vírgula inflava
     * "2000.00" em 100x, virando "200000".
     */
    const parseMoney = (value) => {
        if (!value) return 0;

        const str = String(value).trim();
        const normalized = str.includes(',') ? str.replace(/\./g, '').replace(',', '.') : str;
        const parsed = parseFloat(normalized);

        return isNaN(parsed) ? 0 : parsed;
    };

    const formatMoney = (value) => value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const ageOn = (birthDate) => {
        const birth = new Date(`${birthDate}T00:00:00`);

        if (isNaN(birth.getTime())) return null;

        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const months = today.getMonth() - birth.getMonth();

        if (months < 0 || (months === 0 && today.getDate() < birth.getDate())) age--;

        return age;
    };

    const debounce = (callback, wait) => {
        let timeout;

        return () => {
            clearTimeout(timeout);
            timeout = setTimeout(callback, wait);
        };
    };

    const getJson = (url, params) => fetch(`${url}?${new URLSearchParams(params)}`, {
        headers: { Accept: 'application/json' },
    }).then((response) => {
        if (!response.ok) throw new Error(response.statusText);

        return response.json();
    });

    /**
     * @param {Object} options
     * @param {HTMLElement} options.root o [data-plan-editor] do elemento
     * @param {HTMLInputElement} options.birthDateInput
     * @param {{riskCapital: string, recalculate: string, projection: string}} options.urls
     * @param {number|null} options.minimumRiskAge só na simulação avulsa:
     *   abaixo dessa idade os riscos ficam desmarcados e travados. Na adesão
     *   a regra fica com o servidor, que não deixa o plano chegar a esse
     *   estado.
     */
    const init = ({ root, birthDateInput, urls, minimumRiskAge = null }) => {
        if (!root) return;

        const field = (role) => root.querySelector(`[data-plan-field="${role}"]`);
        const fields = {
            entryAge: field('entryAge'),
            retirement: field('retirement'),
            total: field('total'),
        };
        const risks = {
            survivors: {
                checkbox: root.querySelector('[data-plan-risk="survivors"]'),
                contribution: field('survivors'),
                capital: field('survivorsCapital'),
            },
            disability: {
                checkbox: root.querySelector('[data-plan-risk="disability"]'),
                contribution: field('disability'),
                capital: field('disabilityCapital'),
            },
        };
        const errorBox = root.querySelector('[data-plan-error]');
        const recalculateButton = root.querySelector('[data-plan-recalculate]');
        const minimumRiskAgeNotice = root.querySelector('[data-plan-minimum-risk-age]');

        const birthDate = () => birthDateInput?.value || '';
        const contracted = (risk) => risks[risk].checkbox.checked;
        // Risco desmarcado vai como zero: é o switch que diz se o risco
        // existe, não o banco -- o admin pode ter acabado de desmarcar e
        // ainda não salvou.
        const contractedContribution = (risk) => (contracted(risk) ? parseMoney(risks[risk].contribution.value) : 0);

        /**
         * Só exibição -- a soma das três contribuições. Não é gravada: o
         * campo não tem `name`.
         */
        const updateTotal = () => {
            const total = [fields.retirement, risks.survivors.contribution, risks.disability.contribution]
                .reduce((sum, input) => sum + parseMoney(input.value), 0);

            fields.total.value = formatMoney(total);
        };

        // `readonly`, não `disabled`: um campo `disabled` não é enviado no
        // submit, e o zero precisa chegar ao servidor tanto quanto o valor
        // restaurado ao marcar de volta. `bg-body-secondary`/`text-muted` só
        // para o campo não parecer editável como os outros.
        const setRiskReadOnly = (risk, readOnly) => {
            [risks[risk].contribution, risks[risk].capital].forEach((input) => {
                input.readOnly = readOnly;
                input.classList.toggle('bg-body-secondary', readOnly);
                input.classList.toggle('text-muted', readOnly);
            });
        };

        /**
         * Desmarcar um risco zera a contribuição dele e soma o que tinha na
         * de aposentadoria -- "sem risco, o valor inteiro vai para
         * aposentadoria". Marcar de volta devolve exatamente o que foi tirado.
         */
        const toggleRisk = (risk) => {
            const { checkbox, contribution, capital } = risks[risk];

            if (!checkbox.checked) {
                risks[risk].last = {
                    contribution: parseMoney(contribution.value),
                    capital: parseMoney(capital.value),
                };

                fields.retirement.value = formatMoney(parseMoney(fields.retirement.value) + parseMoney(contribution.value));
                contribution.value = formatMoney(0);
                capital.value = formatMoney(0);
            } else {
                const last = risks[risk].last || { contribution: 0, capital: 0 };

                fields.retirement.value = formatMoney(Math.max(0, parseMoney(fields.retirement.value) - last.contribution));
                contribution.value = formatMoney(last.contribution);
                capital.value = formatMoney(last.capital);

                delete risks[risk].last;
            }

            setRiskReadOnly(risk, !checkbox.checked);
            updateTotal();
        };

        const showError = (message) => {
            errorBox.textContent = message;
            errorBox.style.display = 'block';
        };

        /**
         * Gráfico e cenários com as contribuições que estão na tela, ainda
         * não salvas.
         */
        const updateProjection = () => {
            if (!birthDate() || !window.PlanProjectionView) return;

            getJson(urls.projection, {
                date: birthDate(),
                retirementContribution: parseMoney(fields.retirement.value),
                survivorsPensionContribution: contractedContribution('survivors'),
                disabilityRetirementContribution: contractedContribution('disability'),
            }).then((response) => {
                if (response.success) window.PlanProjectionView.render(response);
            }).catch(() => {});
        };

        /**
         * Mantém os capitais segurados em dia enquanto o admin digita a
         * contribuição de um risco, sem esperar o "Recalcular".
         */
        const updateRiskCapital = () => {
            if (!birthDate()) return;

            getJson(urls.riskCapital, {
                date: birthDate(),
                survivorsPensionContribution: parseMoney(risks.survivors.contribution.value),
                disabilityRetirementContribution: parseMoney(risks.disability.contribution.value),
            }).then((response) => {
                if (!response.success) return;

                // Readonly (risco desmarcado) fica de fora: o capital já está
                // travado em zero por toggleRisk.
                if (!risks.survivors.capital.readOnly) {
                    risks.survivors.capital.value = formatMoney(response.survivorsPensionInsuredCapital);
                }

                if (!risks.disability.capital.readOnly) {
                    risks.disability.capital.value = formatMoney(response.disabilityRetirementInsuredCapital);
                }
            }).catch(() => {});
        };

        const scheduleProjection = debounce(updateProjection, 600);
        const scheduleRiskCapital = debounce(updateRiskCapital, 500);

        /**
         * "Recalcular": a regra é de PlanSimulator::rebalance() -- risco
         * preenchido fica como o admin digitou, risco marcado em zero ganha a
         * fatia padrão, a aposentadoria leva o resto.
         */
        const recalculate = () => {
            errorBox.style.display = 'none';

            if (!birthDate()) {
                showError('Preencha a data de nascimento antes de recalcular.');
                return;
            }

            recalculateButton.disabled = true;

            getJson(urls.recalculate, {
                date: birthDate(),
                totalContribution: parseMoney(fields.total.value),
                hasSurvivorsPension: contracted('survivors') ? 1 : 0,
                survivorsPensionContribution: parseMoney(risks.survivors.contribution.value),
                hasDisabilityRetirement: contracted('disability') ? 1 : 0,
                disabilityRetirementContribution: parseMoney(risks.disability.contribution.value),
            }).then((response) => {
                if (!response.success) {
                    showError(response.message || 'Não foi possível recalcular o plano.');
                    return;
                }

                fields.retirement.value = formatMoney(response.retirementContribution);
                risks.survivors.contribution.value = formatMoney(response.survivorsPensionContribution);
                risks.survivors.capital.value = formatMoney(response.survivorsPensionInsuredCapital);
                risks.disability.contribution.value = formatMoney(response.disabilityRetirementContribution);
                risks.disability.capital.value = formatMoney(response.disabilityRetirementInsuredCapital);

                updateTotal();
                updateProjection();
            }).catch(() => showError('Não foi possível recalcular o plano. Tente novamente.'))
                .finally(() => {
                    recalculateButton.disabled = false;
                });
        };

        /**
         * A idade de hoje é o piso da idade de entrada, e, na simulação
         * avulsa, decide se há risco. Os riscos desmarcados por idade voltam
         * marcados quando a data muda para alguém mais velho.
         */
        const applyAge = () => {
            const age = birthDate() ? ageOn(birthDate()) : null;

            if (fields.entryAge) fields.entryAge.min = age ?? '';

            if (minimumRiskAge === null) return;

            const underAge = age !== null && age < minimumRiskAge;

            minimumRiskAgeNotice?.classList.toggle('d-none', !underAge);

            Object.keys(risks).forEach((risk) => {
                const { checkbox } = risks[risk];

                if (underAge && !checkbox.disabled) {
                    risks[risk].uncheckedByAge = checkbox.checked;
                    checkbox.disabled = true;

                    if (checkbox.checked) {
                        checkbox.checked = false;
                        toggleRisk(risk);
                    }
                } else if (!underAge && checkbox.disabled) {
                    checkbox.disabled = false;

                    if (risks[risk].uncheckedByAge) {
                        checkbox.checked = true;
                        toggleRisk(risk);
                    }

                    delete risks[risk].uncheckedByAge;
                }
            });
        };

        Object.keys(risks).forEach((risk) => {
            // Estado inicial só reflete o que já está marcado, sem mover valor.
            setRiskReadOnly(risk, !contracted(risk));

            risks[risk].checkbox.addEventListener('change', () => {
                toggleRisk(risk);
                scheduleProjection();
            });

            ['input', 'change'].forEach((eventName) => {
                risks[risk].contribution.addEventListener(eventName, scheduleRiskCapital);
            });
        });

        [fields.retirement, risks.survivors.contribution, risks.disability.contribution].forEach((input) => {
            ['input', 'change'].forEach((eventName) => input.addEventListener(eventName, () => {
                updateTotal();
                scheduleProjection();
            }));
        });

        // Sem isso, clicar no meio do valor só posiciona o cursor ali --
        // digitar por cima do texto existente produz um número errado em vez
        // de substituir o total inteiro. setTimeout(0) porque a máscara jQuery
        // (reverse:true) também mexe na posição do cursor no foco, e roda
        // depois -- sem adiar, ela desfazia a seleção.
        ['focus', 'click'].forEach((eventName) => {
            fields.total.addEventListener(eventName, () => setTimeout(() => fields.total.select(), 0));
        });

        recalculateButton?.addEventListener('click', recalculate);

        birthDateInput?.addEventListener('change', () => {
            applyAge();
            scheduleRiskCapital();
            scheduleProjection();
        });

        applyAge();
        updateTotal();
        updateProjection();
    };

    return { init, parseMoney, formatMoney };
})();
