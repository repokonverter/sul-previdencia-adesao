<?= $this->Html->script('admin/plan-editor') ?>
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
    // Além dos obrigatórios, os de faixa: uma idade de entrada acima de 65
    // numa aba oculta travaria o submit do mesmo jeito silencioso.
    const VALIDATED_FIELDS = '[required], [min], [max]';

    const updateIncompleteBadges = () => {
        let totalPending = 0;
        const pendingTabs = [];

        document.querySelectorAll('.tab-pane').forEach((pane) => {
            const fields = pane.querySelectorAll(VALIDATED_FIELDS);
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
            `Ainda há campos obrigatórios ou fora da faixa em: ${labels}. Corrija-os ou use ` +
            `"Salvar Mesmo Incompleto" para gravar assim mesmo.`
        );
        $alert.removeClass('d-none');
    };

    $(document).ready(function() {
        const form = document.querySelector('.adhesion-form-container form');

        updateIncompleteBadges();

        // Delegado: cobre também os beneficiários adicionados dinamicamente.
        $(form).on('input change', updateIncompleteBadges);

        PlanEditor.init({
            root: form.querySelector('[data-plan-editor]'),
            birthDateInput: form.querySelector('input[name="adhesion_personal_data[birth_date]"]'),
            urls: {
                riskCapital: <?= json_encode($this->Url->build(['controller' => 'Simulator', 'action' => 'recalculateRiskCapital', 'prefix' => false])) ?>,
                recalculate: <?= json_encode($this->Url->build(['controller' => 'Simulations', 'action' => 'recalculate'])) ?>,
                projection: <?= json_encode($this->Url->build(['controller' => 'Simulations', 'action' => 'projection'])) ?>,
            },
        });

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
                const invalidField = Array.from(pane.querySelectorAll(VALIDATED_FIELDS))
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