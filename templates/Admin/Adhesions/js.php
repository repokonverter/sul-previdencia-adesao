<script>
    const planForHandle = (planFor) => {
        if (planFor.value === 'Dependente') {
            const name = $('input[name="adhesion_personal_data[name]"]').val();
            const cpf = $('input[name="adhesion_personal_data[cpf]"]').val();

            $('input[name="adhesion_personal_data[name_legal_representative]"]').val(name);
            $('input[name="adhesion_personal_data[cpf_legal_representative]"]').val(cpf);
            $('input[name="adhesion_personal_data[name_legal_representative]"]').attr('required', 'required');
            $('input[name="adhesion_personal_data[cpf_legal_representative]"]').attr('required', 'required');
            $('input[name="adhesion_personal_data[affiliation_legal_representative]"]').attr('required', 'required');
            $('#divLegalRepresentative').slideDown();

            return;
        }

        $('#divLegalRepresentative').slideUp();
        $('input[name="adhesion_personal_data[name_legal_representative]"]').removeAttr('required');
        $('input[name="adhesion_personal_data[cpf_legal_representative]"]').removeAttr('required');
        $('input[name="adhesion_personal_data[affiliation_legal_representative]"]').removeAttr('required');
    }

    const showHide = (show, id) => {
        if (show)
            $(`#${id}`).show('slow');

        if (!show)
            $(`#${id}`).hide('slow');
    }

    const pensionSchema = (isParticipant) => {
        let declaration = '<strong>DECLARO</strong> sob pena da lei, que sou segurado do seguinte regime de previdência';

        if (isParticipant) {
            $('#pensionSchemeType #pensionSchemeTypeLabel').html(declaration);
            $('#pensionSchemeTypeKinship').slideUp();
        } else {
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

    const getCEP = (value) => {
        const cep = value.replace(/[^0-9]/g, '');

        if (cep.length < 8)
            return;

        $.ajax({
            type: 'GET',
            url: `https://viacep.com.br/ws/${cep}/json/`,
            beforeSend: () => {
                $('.cep-loading').show();
            },
            success: (response) => {
                $('.cep-loading').hide();
                $('input[name="adhesion_address[address]"]').val(response?.logradouro);
                $('input[name="adhesion_address[neighborhood]"]').val(response?.bairro);
                $('input[name="adhesion_address[city]"]').val(response?.localidade);
                $('select[name="adhesion_address[state]"]').val(response?.uf);
            },
            error: () => {
                $('.cep-loading').hide();
                alert('CEP não encontrado!');
            }
        })
    }

    const paymentType = (type) => {
        if (type === 'Débito em conta') {
            $('#directDebitType').slideDown();
            return;
        }
        $('#directDebitType').slideUp();
    }

    /**
     * Campos obrigatórios (`required`) ficam dentro de abas do Bootstrap que
     * começam ocultas (`display: none`). O navegador bloqueia o submit
     * silenciosamente nesse caso: não consegue rolar até um campo invisível
     * para mostrar o balão de erro, e a tela simplesmente não reage --
     * exatamente o "não me apresenta falha nenhuma" que motivou isto.
     *
     * A solução: marcar cada aba com um selo mostrando quantos campos
     * obrigatórios ainda faltam (atualizado a cada digitação) e, ao tentar
     * salvar, trocar para a primeira aba com pendência antes de deixar a
     * validação nativa rodar -- só então o campo está visível e o balão
     * aparece no lugar certo.
     */
    const updateIncompleteBadges = () => {
        let totalPending = 0;
        const pendingTabs = [];

        document.querySelectorAll('.tab-pane').forEach((pane) => {
            const fields = pane.querySelectorAll('[required]');
            const seenRadioGroups = new Set();
            let missing = 0;

            fields.forEach((field) => {
                if (field.type === 'radio') {
                    if (seenRadioGroups.has(field.name)) return;
                    seenRadioGroups.add(field.name);
                }

                if (!field.checkValidity()) missing++;
            });

            const badge = document.querySelector(`a[href="#${pane.id}"] .tab-missing-badge`);
            if (!badge) return;

            if (missing > 0) {
                badge.textContent = missing;
                badge.classList.remove('d-none');
                totalPending++;
                pendingTabs.push(pane.id);
            } else {
                badge.classList.add('d-none');
            }
        });

        return { totalPending, pendingTabs };
    };

    const tabLabel = (paneId) => {
        const link = document.querySelector(`a[href="#${paneId}"]`);
        if (!link) return paneId;

        // O texto do link inclui o badge (ex.: "Documentos 2"); o próprio nó
        // de texto do link, sem os filhos, é só o rótulo.
        return Array.from(link.childNodes)
            .filter((node) => node.nodeType === Node.TEXT_NODE)
            .map((node) => node.textContent.trim())
            .join(' ')
            .trim();
    };

    const showIncompleteAlert = (pendingTabs) => {
        const $alert = $('#incompleteFieldsAlert');
        if (pendingTabs.length === 0) {
            $alert.addClass('d-none');
            return;
        }

        const labels = pendingTabs.map(tabLabel).join(', ');
        $('#incompleteFieldsAlertText').text(
            `Ainda faltam campos obrigatórios em: ${labels}. Corrija-os ou use ` +
            `"Salvar Mesmo Incompleto" para gravar assim mesmo.`
        );
        $alert.removeClass('d-none');
    };

    /**
     * Só exibição -- soma as três contribuições do plano (aposentadoria,
     * pensão por morte, invalidez) para o admin ter noção do total sem
     * somar de cabeça. Não é gravada: o campo não tem `name`.
     */
    /**
     * Mesma regra de App\Utility\Money::parse() do lado do servidor: o campo
     * carrega ponto decimal cru do banco até a máscara jQuery rodar (no
     * carregamento da página) e vírgula pt-BR depois que o admin mexe nele.
     * Tratar todo ponto como separador de milhar sem checar a vírgula inflava
     * "2000.00" em 100x, virando "200000".
     */
    const parseMoneyValue = (value) => {
        if (!value) return 0;

        const str = String(value).trim();
        const normalized = str.includes(',') ? str.replace(/\./g, '').replace(',', '.') : str;
        const parsed = parseFloat(normalized);

        return isNaN(parsed) ? 0 : parsed;
    };

    const updatePlanTotalContribution = () => {
        const total = Array.from(document.querySelectorAll('.plan-contribution-field'))
            .reduce((sum, field) => sum + parseMoneyValue(field.value), 0);

        const $total = $('#planTotalContribution');
        if ($total.length) $total.val(total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    };

    const formatMoneyValue = (value) => value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    /**
     * Desmarcar um risco zera a contribuição dele e soma o que tinha na de
     * aposentadoria -- "sem risco, o valor inteiro vai para aposentadoria".
     * Marcar de volta devolve exatamente o que foi tirado, guardado no
     * próprio checkbox enquanto ele fica desmarcado.
     */
    const RISK_FIELDS = {
        has_survivors_pension: {
            contribution: 'adhesion_plan[monthly_survivors_pension_contribution]',
            capital: 'adhesion_plan[survivors_pension_insured_capital]',
        },
        has_disability_retirement: {
            contribution: 'adhesion_plan[monthly_disability_retirement_contribution]',
            capital: 'adhesion_plan[disability_retirement_insured_capital]',
        },
    };

    const setRiskFieldsDisabled = (checkbox, disabled) => {
        const fields = RISK_FIELDS[checkbox.id];
        if (!fields) return;

        // `readonly`, não `disabled`: um campo `disabled` não é enviado no
        // submit, e o zero precisa chegar ao servidor tanto quanto o valor
        // restaurado ao marcar de volta. `bg-body-secondary`/`text-muted` só
        // para o campo não parecer editável como os outros -- sem isso a
        // diferença visual entre "risco fora" e "risco dentro" era quase nula.
        [`input[name="${fields.contribution}"]`, `input[name="${fields.capital}"]`].forEach((selector) => {
            $(selector)
                .prop('readOnly', disabled)
                .toggleClass('bg-body-secondary text-muted', disabled);
        });
    };

    const handleRiskToggle = function() {
        const fields = RISK_FIELDS[this.id];
        if (!fields) return;

        const $contribution = $(`input[name="${fields.contribution}"]`);
        const $capital = $(`input[name="${fields.capital}"]`);
        const $retirement = $('input[name="adhesion_plan[monthly_retirement_contribution]"]');

        if (!this.checked) {
            $(this).data('lastContribution', parseMoneyValue($contribution.val()));
            $(this).data('lastCapital', parseMoneyValue($capital.val()));

            $retirement.val(formatMoneyValue(parseMoneyValue($retirement.val()) + parseMoneyValue($contribution.val())));
            $contribution.val(formatMoneyValue(0));
            $capital.val(formatMoneyValue(0));
        } else {
            const contributionValue = $(this).data('lastContribution') || 0;
            const capitalValue = $(this).data('lastCapital') || 0;

            $retirement.val(formatMoneyValue(Math.max(0, parseMoneyValue($retirement.val()) - contributionValue)));
            $contribution.val(formatMoneyValue(contributionValue));
            $capital.val(formatMoneyValue(capitalValue));

            $(this).removeData('lastContribution').removeData('lastCapital');
        }

        setRiskFieldsDisabled(this, !this.checked);
        updatePlanTotalContribution();
    };

    $(document).ready(function() {
        const form = document.querySelector('.adhesion-form-container form');

        updateIncompleteBadges();
        updatePlanTotalContribution();

        // Sem isso, clicar no meio do valor só posiciona o cursor ali --
        // digitar por cima do texto existente produz um número errado em
        // vez de substituir o total inteiro. setTimeout(0) porque a máscara
        // jQuery (reverse:true) também mexe na posição do cursor no foco, e
        // roda depois -- sem adiar, ela desfazia a seleção.
        const planTotalField = document.getElementById('planTotalContribution');
        ['focus', 'click'].forEach((eventName) => {
            planTotalField?.addEventListener(eventName, function() {
                setTimeout(() => this.select(), 0);
            });
        });

        Object.keys(RISK_FIELDS).forEach((id) => {
            const checkbox = document.getElementById(id);
            if (!checkbox) return;

            // Estado inicial só reflete o que já está marcado, sem mover valor.
            setRiskFieldsDisabled(checkbox, !checkbox.checked);
            checkbox.addEventListener('change', handleRiskToggle);
        });

        // Delegado: cobre também os beneficiários adicionados dinamicamente.
        $(form).on('input change', updateIncompleteBadges);
        $(form).on('input change', '.plan-contribution-field', updatePlanTotalContribution);

        /**
         * Capital segurado a partir da contribuição de risco como está no
         * campo, sem redistribuir nada -- diferente do "Recalcular" abaixo,
         * que parte do total e reescreve as três contribuições pelas taxas
         * padrão. Isto é o que mantém "Capital segurado pensão por morte" e
         * "...invalidez" em dia quando o admin ajusta só a contribuição de
         * um risco à mão (SimulatorController::recalculateRiskCapital).
         */
        let riskCapitalTimeout;

        const recalculateRiskCapital = () => {
            const birthDate = $('input[name="adhesion_personal_data[birth_date]"]').val();

            if (!birthDate) return;

            const $survivorsContribution = $('input[name="adhesion_plan[monthly_survivors_pension_contribution]"]');
            const $disabilityContribution = $('input[name="adhesion_plan[monthly_disability_retirement_contribution]"]');

            $.ajax({
                type: 'GET',
                url: <?= json_encode($this->Url->build([
                    'controller' => 'Simulator',
                    'action' => 'recalculateRiskCapital',
                    'prefix' => false,
                ])) ?>,
                data: {
                    date: birthDate,
                    survivorsPensionContribution: parseMoneyValue($survivorsContribution.val()),
                    disabilityRetirementContribution: parseMoneyValue($disabilityContribution.val()),
                },
                dataType: 'json',
                success: (response) => {
                    if (!response.success) return;

                    // Readonly (risco desmarcado) fica de fora: o capital já
                    // está travado em zero por handleRiskToggle, e não é
                    // este campo que decidiu a contribuição zerada.
                    if (!$survivorsContribution.prop('readOnly')) {
                        $('input[name="adhesion_plan[survivors_pension_insured_capital]"]').val(formatMoneyValue(response.survivorsPensionInsuredCapital));
                    }

                    if (!$disabilityContribution.prop('readOnly')) {
                        $('input[name="adhesion_plan[disability_retirement_insured_capital]"]').val(formatMoneyValue(response.disabilityRetirementInsuredCapital));
                    }
                },
            });
        };

        $(form).on('input', '.plan-risk-contribution-field', function() {
            clearTimeout(riskCapitalTimeout);
            riskCapitalTimeout = setTimeout(recalculateRiskCapital, 500);
        });

        /**
         * "Recalcular": mesmo endpoint que o formulário público usa no passo
         * Plano (SimulatorController::recalculate), reaproveitado aqui para
         * não duplicar a fórmula atuarial. Ele lê os riscos como estão
         * salvos no banco -- o aviso ao lado do botão existe por causa disso.
         */
        const recalcButton = document.getElementById('btnRecalculatePlan');

        if (recalcButton) {
            recalcButton.addEventListener('click', function() {
                const errorDiv = document.getElementById('planRecalculateError');
                const birthDate = $('input[name="adhesion_personal_data[birth_date]"]').val();
                const value = parseMoneyValue(document.getElementById('planTotalContribution').value);

                errorDiv.style.display = 'none';

                if (!birthDate) {
                    errorDiv.textContent = 'Preencha a data de nascimento em "Dados Pessoais" antes de recalcular.';
                    errorDiv.style.display = 'block';
                    return;
                }

                recalcButton.disabled = true;

                $.ajax({
                    type: 'GET',
                    url: <?= json_encode($this->Url->build([
                        'controller' => 'Simulator',
                        'action' => 'recalculate',
                        'prefix' => false,
                    ])) ?>,
                    data: {
                        date: birthDate,
                        value: value,
                        initialDataId: recalcButton.dataset.adhesionId,
                        storageUuid: recalcButton.dataset.storageUuid,
                    },
                    dataType: 'json',
                    success: (response) => {
                        if (!response.success) {
                            errorDiv.textContent = response.message || 'Não foi possível recalcular o plano.';
                            errorDiv.style.display = 'block';
                            return;
                        }

                        $('input[name="adhesion_plan[benefit_entry_age]"]').val(response.benefitEntryAge);
                        $('input[name="adhesion_plan[monthly_retirement_contribution]"]').val(formatMoneyValue(response.monthlyRetirementContribution));
                        $('input[name="adhesion_plan[monthly_survivors_pension_contribution]"]').val(formatMoneyValue(response.monthlySurvivorsPensionContribution));
                        $('input[name="adhesion_plan[survivors_pension_insured_capital]"]').val(formatMoneyValue(response.survivorsPensionInsuredCapital));
                        $('input[name="adhesion_plan[monthly_disability_retirement_contribution]"]').val(formatMoneyValue(response.monthlyDisabilityRetirementContribution));
                        $('input[name="adhesion_plan[disability_retirement_insured_capital]"]').val(formatMoneyValue(response.disabilityRetirementInsuredCapital));

                        updatePlanTotalContribution();
                    },
                    error: () => {
                        errorDiv.textContent = 'Não foi possível recalcular o plano. Tente novamente.';
                        errorDiv.style.display = 'block';
                    },
                    complete: () => {
                        recalcButton.disabled = false;
                    },
                });
            });
        }

        $(form).on('submit', function(e) {
            const submitter = e.originalEvent?.submitter;

            // Botão "Salvar Mesmo Incompleto": pula toda a checagem, é
            // exatamente para permitir gravar pela metade.
            if (submitter && submitter.hasAttribute('formnovalidate')) {
                $('#incompleteFieldsAlert').addClass('d-none');
                return;
            }

            const { pendingTabs } = updateIncompleteBadges();

            if (pendingTabs.length === 0) return;

            e.preventDefault();
            showIncompleteAlert(pendingTabs);

            const firstPaneId = pendingTabs[0];
            const tabLink = document.querySelector(`a[href="#${firstPaneId}"]`);
            if (tabLink) {
                bootstrap.Tab.getOrCreateInstance(tabLink).show();
            }

            // Espera a aba ficar visível antes de focar o campo -- reportar
            // validade de um campo ainda oculto falha do mesmo jeito que o
            // submit nativo.
            setTimeout(() => {
                const pane = document.getElementById(firstPaneId);
                const invalidField = Array.from(pane.querySelectorAll('[required]'))
                    .find((field) => !field.checkValidity());
                invalidField?.reportValidity();
                invalidField?.focus();
            }, 200);
        });
    });

    $(document).ready(function() {
        if ($('input[name="adhesion_personal_data[plan_for]"]:checked').val() === 'Dependente') {
            $('#divLegalRepresentative').show();
        }

        if ($('input[name="adhesion_other_information[politically_exposed]"]:checked').val() == '1') {
            $('#politicallyExposedObs').show();
        }

        if ($('input[name="adhesion_proponent_statement[health_problem]"]:checked').val() == '1') {
            $('#healthProblemObs').show();
        }

        if ($('input[name="adhesion_proponent_statement[heart_disease]"]:checked').val() == '1') {
            $('#heartDiseaseObs').show();
        }

        if ($('input[name="adhesion_proponent_statement[suffered_organ_defects]"]:checked').val() == '1') {
            $('#sufferedOrganDefectsObs').show();
        }

        if ($('input[name="adhesion_proponent_statement[surgery]"]:checked').val() == '1') {
            $('#surgeryObs').show();
        }

        if ($('input[name="adhesion_proponent_statement[away]"]:checked').val() == '1') {
            $('#awayObs').show();
        }

        if ($('input[name="adhesion_proponent_statement[practices_parachuting]"]:checked').val() == '1') {
            $('#practicesParachutingObs').show();
        }

        if ($('input[name="adhesion_proponent_statement[smoker]"]:checked').val() == '1') {
            $('#smokerObs').show();
            if ($('input[name="adhesion_proponent_statement[smoker_type]"]:checked').val() == '0') {
                $('#smokerTypeObs').show();
            }
        }

        if ($('input[name="adhesion_proponent_statement[gripe]"]:checked').val() == '1') {
            $('#gripeObs').show();
        }

        if ($('input[name="adhesion_proponent_statement[covid]"]:checked').val() == '1') {
            $('#covidObs').show();
        }

        if ($('input[name="adhesion_proponent_statement[covid_sequelae]"]:checked').val() == '1') {
            $('#covidSequelaeObs').show();
        }

        if ($('input[name="adhesion_pension_scheme[any_pension_schema]"]:checked').val() == '1') {
            pensionSchema(true);
        } else if ($('input[name="adhesion_pension_scheme[any_pension_schema]"]:checked').val() == '0') {
            pensionSchema(false);
        }

        if ($('input[name="adhesion_payment_detail[payment_type]"]:checked').val() === 'Débito em conta') {
            $('#directDebitType').show();
        }

        // Lógica de busca de ocupação (CBO)
        let searchTimeout;
        const $searchInput = $('#mainOccupationSearch');
        const $hiddenCodeInput = $('#mainOccupationCode');
        const $hiddenDescInput = $('#mainOccupationDescription');
        const $resultsDiv = $('#occupationResults');

        $searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            const term = $(this).val();

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
            $hiddenCodeInput.val(id);
            $hiddenDescInput.val(description);
            $searchInput.val(description);
            $resultsDiv.hide();
            $searchInput.removeClass('is-invalid');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.occupation-search-container').length) {
                $resultsDiv.hide();
            }
        });
    });
</script>