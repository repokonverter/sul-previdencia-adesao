<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 20px 30px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.1;
            color: #000;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 5px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
            vertical-align: middle;
            font-size: 9pt;
        }

        .header-table {
            width: 100%;
            border: none !important;
            margin-bottom: 5px;
        }

        .header-table td {
            border: none !important;
            vertical-align: bottom;
            padding: 0;
        }

        .main-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .logo-text {
            font-size: 22pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: right;
        }

        .slogan {
            font-size: 9pt;
            font-style: italic;
            text-align: right;
            margin-top: -4px;
        }

        .section-title {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 10pt;
            padding: 4px;
            border: 1px solid #000;
            margin-top: 8px;
            text-transform: uppercase;
        }

        .checkbox {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            margin-right: 3px;
            text-align: center;
            line-height: 10px;
            font-size: 10px;
        }

        .checkbox.checked::after {
            content: "X";
            font-size: 8px;
            font-weight: bold;
        }

        /* Utilitários de Texto */
        .small-note {
            font-size: 8pt;
            text-align: justify;
            margin-top: 3px;
            line-height: 1.2;
        }

        .bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* Assinaturas e Administrativo */
        .invisible-table,
        .invisible-table tr,
        .invisible-table td {
            border: none !important;
            background: transparent !important;
            padding: 0;
        }

        .signature-table {
            width: 100%;
            margin-top: 20px;
        }

        .signature-cell {
            width: 50%;
            padding: 0 10px;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            width: 90%;
            height: 20px;
            margin: 0 auto;
        }

        .signature-label {
            text-align: center;
            font-size: 9pt;
            padding-top: 2px;
        }

        /* Caixas Admin */
        .seg-table {
            table-layout: fixed;
            font-size: 8pt;
            margin-top: 5px;
        }

        .light-box {
            background: #f2f4ff;
            border: 1px solid #d3d7f6;
            padding: 2px 5px;
            font-size: 9pt;
            display: inline-block;
            width: 200px;
        }

        /* Rodapé */
        .footer-text {
            font-size: 8pt;
            text-align: center;
            margin-top: 30px;
            line-height: 1.3;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div>
        <div style="font-size: 8pt; text-align: right; margin-bottom: 5px;">
            BFX - Proposta Plenoprev <?= date('M/Y') ?>
        </div>

        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    <div class="main-title">PROPOSTA DE INSCRIÇÃO</div>
                    <div style="font-size: 9pt;">
                        <span class="checkbox"></span> Contratação avulsa de cobertura de risco
                    </div>
                </td>
                <td style="width: 40%;">
                    <div class="logo-text">PLENOPREV</div>
                    <div class="slogan">A realização do seu futuro</div>
                </td>
            </tr>
        </table>

        <div style="border-bottom: 2px solid #000; margin-bottom: 5px;"></div>
        <div style="font-size: 7pt; text-align: right; margin-bottom: 5px;">
            1ª Via: MAG Seguros - 2ª Via: Sul Previdência - 3ª Via: Participante
        </div>

        <table>
            <tr>
                <td>
                    CNPJ do Plano<br>
                    48.307.525/0001-09
                </td>
                <td>
                    Nº do Instituidor<br>
                    123123
                </td>
                <td>
                    Nome do instituidor<br>
                    TEste
                </td>
                <td>
                    Nº da Proposta<br>
                    <?= $proposalNumber ?>
                </td>
            </tr>
        </table>

        <table style="margin-top: -1px;">
            <tr>
                <td colspan="4">Nome Completo<br><?= $adhesion->adhesion_personal_data->name ?? '' ?></td>
                <td colspan="2">Data de Nascimento<br><?= $adhesion->adhesion_personal_data->birth_date ? $adhesion->adhesion_personal_data->birth_date->format('d/m/Y') : '' ?></td>
            </tr>
            <tr>
                <td>Idade<br><?= $age ?? '' ?></td>
                <td>Sexo<br><?= $adhesion->adhesion_personal_data->gender ?? '' ?></td>
                <td>Estado Civil<br><?= $adhesion->adhesion_personal_data->marital_status ?? '' ?></td>
                <td>Nº de Filhos<br><?= $adhesion->adhesion_personal_data->number_childrens ?? 0 ?></td>
                <td colspan="2">CPF / Tipo<br><?= $adhesion->adhesion_personal_data->cpf ?? '' ?>, <span class="bold"><?= $adhesion->adhesion_personal_data->plan_for ?? '' ?></span></td>
            </tr>
            <tr>
                <td colspan="2">Nacionalidade<br><?= $adhesion->adhesion_personal_data->nacionality ?? '' ?></td>
                <td>Nat. do Documento<br><?= $adhesion->adhesion_document->type ?? '' ?> <?= $adhesion->adhesion_document->type_other ?? '' ?></td>
                <td>Nº do Documento<br><?= $adhesion->adhesion_document->document_number ?? '' ?></td>
                <td>Órgão Expedidor<br><?= $adhesion->adhesion_document->issuer ?? '' ?>/<?= $adhesion->adhesion_document->state ?? '' ?></td>
                <td>Data de Expedição<br><?= $adhesion->adhesion_document->issue_date ? $adhesion->adhesion_document->issue_date->format('d/m/Y') : '' ?></td>
            </tr>
            <tr>
                <td colspan="2">Ocupação princ.<br><?= $adhesion->adhesion_other_information->main_occupation ?? '' ?></td>
                <td colspan="2">Cód.<br><?= $adhesion->adhesion_other_information->main_occupation_code ?? '' ?>, ativo/inativo</td>
                <td colspan="2">
                    Empresa que trabalha<br><?= $adhesion->adhesion_other_information->company ?? '' ?>
                </td>
            </tr>
            <tr>
                <td colspan="2">Categoria<br><?= $adhesion->adhesion_other_information->category ?? '' ?></td>
                <td>Renda mensal bruta<br><?= $adhesion->adhesion_other_information->monthly_income ?? '<br>' ?></td>
                <td colspan="3">Pessoa Politicamente Exposta¹?<br><?= $adhesion->adhesion_other_information->politically_exposed ? 'Sim, ' . ($adhesion->adhesion_other_information->politically_exposed_obs ?? '') : 'Não' ?></td>
            </tr>
            <tr>
                <td colspan="2">Residente no Brasil?<br><?= $adhesion->adhesion_other_information->brazilian_resident ? 'Sim' : 'Não' ?></td>
                <td colspan="4">Obrigações fiscais exterior²?<br><?= $adhesion->adhesion_other_information->obligation_other_countries ? 'Sim' : 'Não' ?></td>
            </tr>
            <tr>
                <td colspan="4">Endereço Residencial<br><?= $adhesion->adhesion_address->address ?? '' ?></td>
                <td colspan="2">Nº<br><?= $adhesion->adhesion_address->number ?? '' ?></td>
            </tr>
            <tr>
                <td>Complemento<br><?= $adhesion->adhesion_address->complement ?? '' ?></td>
                <td>Bairro<br><?= $adhesion->adhesion_address->neighborhood ?? '' ?></td>
                <td colspan="2">Cidade<br><?= $adhesion->adhesion_address->city ?? '' ?></td>
                <td>UF<br><?= $adhesion->adhesion_address->state ?? '' ?></td>
                <td>CEP<br><?= $adhesion->adhesion_address->cep ?? '' ?></td>
            </tr>
            <tr>
                <td>Telefone<br><?= $adhesion->phone ?? '' ?></td>
                <td colspan="4">E-mail<br><?= $adhesion->email ?? '' ?></td>
                <td>É participante?<br><?= $adhesion->is_participant ? 'Sim' : 'Não' ?></td>
            </tr>
            <?php if ($adhesion->adhesion_personal_data->cpf_legal_representative) { ?>
                <tr>
                    <td colspan="4">Nome do representante legal<br><?= $adhesion->adhesion_personal_data->name_legal_representative ?? '' ?></td>
                    <td colspan="2">CPF do representante legal<br><?= $adhesion->adhesion_personal_data->cpf_legal_representative ?? '' ?></td>
                </tr>
                <tr>
                    <td colspan="6">Filiação<br><?= $adhesion->adhesion_personal_data->affiliation_legal_representative ?? '' ?></td>
                </tr>
            <?php } ?>
        </table>

        <div class="small-note">
            <b>¹Pessoas politicamente expostas:</b> Consideram-se pessoas politicamente expostas os agentes públicos que desempenham ou tenham desempenhado, nos 5 (cinco) anos anteriores, empregos ou funções públicas relevantes, assim como funções relevantes em organizações internacionais. cargos, empregos ou funções públicas, assim como seus representantes, familiares e outras pessoas de seu relacionamento próximo. São considerados familiares os parentes, na linha direta, até o primeiro grau, o cônjuge, o companheiro, a companheira, o enteado e a enteada, conforme definido na Circular SUSEP Nº 612/2020.
            <br>
            <b>²A Lei de Conformidade Tributária de Contas Estrangeiras [Foreign Account Tax Compliance Act (FATCA)]</b> é uma lei federal norte-americana que prevê a obrigatoriedade de instituições bancárias estrangeiras fornecerem dados de seus correntistas às autoridades americanas, desde que esses correntistas sejam também cidadãos norte-americanos.
        </div>

        <div class="section-title">PLANO DE BENEFÍCIOS</div>
        <table>
            <tr>
                <td>Benefício<br>APOSENTADORIA PROGRAMADA</td>
                <td>Idade para Entrada em Benefício<br><?= $adhesion->adhesion_plan->benefit_entry_age ?? '' ?> anos</td>
                <td>Valor da Contribuição (1)<br>R$ <?= number_format($adhesion->adhesion_plan->monthly_retirement_contribution ?? 0, 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td colspan="3">Taxa de Carregamento do plano: 0%. O valor de contribuição será atualizado, anualmente, no mês de junho, pela variação do INPC.</td>
            </tr>
        </table>
        <div class="small-note">Inscrição do plano no Cadastro Nacional de Planos de Benefícios (CNPB) nº 2011/0017-65.</div>

        <div class="section-title">BENEFICIÁRIOS DO PLANO</div>
        <table>
            <tr>
                <th>Nome Completo</th>
                <th>Data Nasc.</th>
                <th>Parentesco*</th>
                <th>Participação</th>
            </tr>
            <?php if (!empty($adhesion->adhesion_dependents)): ?>
                <?php foreach ($adhesion->adhesion_dependents as $dependent): ?>
                    <tr>
                        <td><?= $dependent->name ?? '' ?></td>
                        <td><?= $dependent->birth_date ? $dependent->birth_date->format('d/m/Y') : '' ?></td>
                        <td><?= $dependent->kinship ?? '' ?></td>
                        <td><?= $dependent->participation ?? '' ?>%</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">Não há beneficiários indicados. Será observado o art. 792 do Código Civil.</td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="section-title">PARCELA ADICIONAL DE RISCO</div>
        <table>
            <tr>
                <td>Benefício</td>
                <td>Nº Processo SUSEP</td>
                <td>Valor do Pecúlio</td>
                <td>Valor da Contribuição</td>
            </tr>
            <tr>
                <td>
                    1102 - PECÚLIO POR MORTE
                </td>
                <td>
                    15.414.000077/2005-16
                </td>
                <td>
                    R$ <?= number_format($adhesion->adhesion_plan->survivors_pension_insured_capital ?? 0, 2, ',', '.') ?>
                </td>
                <td>
                    R$ <?= number_format($adhesion->adhesion_plan->monthly_survivors_pension_contribution ?? 0, 2, ',', '.') ?>
                </td>
            </tr>
            <tr>
                <td>
                    1102 - PECÚLIO POR INVALIDEZ
                </td>
                <td>
                    15.414.000078/2005-52
                </td>
                <td>
                    R$ <?= number_format($adhesion->adhesion_plan->disability_retirement_insured_capital ?? 0, 2, ',', '.') ?>
                </td>
                <td>
                    R$ <?= number_format($adhesion->adhesion_plan->monthly_disability_retirement_contribution ?? 0, 2, ',', '.') ?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    Pecúlios garantidos pela Mongeral Aegon Seguros e Previdência<br>
                    CNPJ: 33.608.308/0001-73 Carregamento: 30%
                </td>
                <td class="text-right bold">
                    Total da Parcela Adicional de Risco (2):
                </td>
                <td>
                    R$ <?= number_format(($adhesion->adhesion_plan->monthly_survivors_pension_contribution ?? 0) + ($adhesion->adhesion_plan->monthly_disability_retirement_contribution ?? 0), 2, ',', '.') ?>
                </td>
            </tr>
        </table>
        <div class="small-note">O valor da contribuição para o(s) pecúlio(s) será atualizado, anualmente, no mês de junho, pela variação do INPC e em função da nova idade atingida
            pelo participante. O registro destes planos na SUSEP não implica, por parte da autarquia, incentivo ou recomendação para sua comercialização.</div>

        <div class="section-title">DADOS PARA PAGAMENTO</div>
        <table>
            <tr>
                <td>Dia do vencimento<br><?= $adhesion->adhesion_payment_detail->due_date ?? '' ?></td>
                <td colspan="2">Total da contribuição (1+2)<br>R$ <?= number_format($adhesion->adhesion_payment_detail->total_contribution ?? 0, 2, ',', '.') ?></td>
                <td colspan="2">Forma Pagamento<br><?= $adhesion->adhesion_payment_detail->payment_type ?? '' ?></td>
            </tr>
            <?php if (($adhesion->adhesion_payment_detail->payment_type ?? '') == 'Débito em Conta'): ?>
                <tr>
                    <td colspan="3">Nome do correntista<br><?= $adhesion->adhesion_payment_detail->account_holder ?? '' ?></td>
                    <td colspan="2">CPF do correntista<br><?= $adhesion->adhesion_payment_detail->account_holder_cpf ?? '' ?></td>
                </tr>
                <tr>
                    <td>Nº do banco<br><?= $adhesion->adhesion_payment_detail->bank_number ?? '' ?></td>
                    <td>Nome do banco<br><?= $adhesion->adhesion_payment_detail->bank_name ?? '' ?></td>
                    <td>Nº da agência<br><?= $adhesion->adhesion_payment_detail->branch_number ?? '' ?></td>
                    <td>Nº da conta corrente<br><?= $adhesion->adhesion_payment_detail->account_number ?? '' ?></td>
                    <td>Autorização de débito em conta no verso da 2ª via.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="page-break"></div>

    <div>
        <div class="small-note">
            Espaço para relógio protocolo<br>
            A aceitação estará sujeita à análise do risco e a MONGERAL AEGON tem o prazo de até 15 dias, contados da data que vier a ser registrada pelo relógio protocolo, para manifestar-se em relação à aceitação ou recusa desta proposta. Este prazo será suspenso quando necessária a requisição de outros documentos ou dados para análise do risco. Essa eventual suspensão terminará quando forem protocolados os documentos ou dados para análise do risco. Caso não haja manifestação de recusa desta proposta pela MONGERAL AEGON no prazo antes referido, a aceitação da proposta se dará automaticamente. No caso de não aceitação da proposta, o valor aportado será devolvido, atualizado até a data da efetiva restituição, de acordo com a regulamentação em vigor.
        </div>

        <div class="section-title">PARA USO DA SEGURADORA</div>
        <table>
            <tr>
                <td colspan="2">Nome Proponente<br><br></td>
                <td colspan="2">CPF<br><br></td>
                <td>Cód. Órgão<br><br></td>
                <td>A partir de<br><br></td>
            </tr>
            <tr>
                <td>Convênio Adesão<br><br></td>
                <td>Ação Marketing<br><br></td>
                <td>Alternativa<br><br></td>
                <td>Sucursal<br><br></td>
                <td>Diretor Regional<br><br></td>
                <td>Gerente de Sucursal<br><br></td>
            </tr>
            <tr>
                <td colspan="2">Gerente Comercial<br><br></td>
                <td colspan="2">Agente<br><br></td>
                <td>Corretor 1<br><br></td>
                <td>Corretor 2<br><br></td>
            </tr>
        </table>

        <div class="section-title">PARA USO DO CORRETOR</div>
        <table>
            <tr>
                <td colspan="2">Nome do Corretor<br><br></td>
                <td>Código SUSEP<br><br></td>
                <td>Assinatura<br><br></td>
            </tr>
        </table>

        <div class="section-title">DECLARAÇÕES DO PROPONENTE <span style="font-size: 8pt; font-weight: normal;">Declaração Pessoal de Saúde (nunca deve ser assinada em branco.)</span></div>
        <table style="font-size: 8pt;">
            <tr>
                <th width="75%">Perguntas</th>
                <th width="25%">Respostas</th>
            </tr>
            <tr>
                <td>1. Encontra-se com algum problema de saúde ou faz uso de algum medicamento?</td>
                <td><?= $adhesion->adhesion_proponent_statement->health_problem ? 'Sim, ' . $adhesion->adhesion_proponent_statement->health_problem_obs : 'Não' ?></td>
            </tr>
            <tr>
                <td>2. Sofre ou já sofreu de doenças do coração, hipertensão,circulatórias, do sangue, diabetes, pulmão, fígado, rins, infarto, acidente vascular cerebral, articulações, qualquer tipo de câncer ou HIV?</td>
                <td><?= $adhesion->adhesion_proponent_statement->heart_disease ? 'Sim, ' . $adhesion->adhesion_proponent_statement->heart_disease_obs : 'Não' ?></td>
            </tr>
            <tr>
                <td>3. Sofre ou sofreu de deficiências de órgãos, membros ou sentidos, incluindo doenças ortopédicas relacionadas a esforço repetitivo (LER e DORT)?</td>
                <td><?= $adhesion->adhesion_proponent_statement->suffered_organ_defects ? 'Sim, ' . $adhesion->adhesion_proponent_statement->suffered_organ_defects_obs : 'Não' ?></td>
            </tr>
            <tr>
                <td>4. Fez alguma cirurgia, biópsia ou esteve internado nos últimos 5 anos?</td>
                <td><?= $adhesion->adhesion_proponent_statement->surgery ? 'Sim, ' . $adhesion->adhesion_proponent_statement->surgery_obs : 'Não' ?></td>
            </tr>
            <tr>
                <td>5. Está afastado(a) do trabalho ou aposentado por invalidez?</td>
                <td><?= $adhesion->adhesion_proponent_statement->away ? 'Sim, ' . $adhesion->adhesion_proponent_statement->away_obs : 'Não' ?></td>
            </tr>
            <tr>
                <td>6. Pratica paraquedismo, motociclismo, boxe, asa delta, rodeio, alpinismo, voo livre, automobilismo, mergulho ou exerce atividade, em caráter profissional ou amador, a bordo de aeronaves, que não sejam de linhas regulares?</td>
                <td><?= $adhesion->adhesion_proponent_statement->practices_parachuting ? 'Sim, ' . $adhesion->adhesion_proponent_statement->practices_parachuting_obs : 'Não' ?></td>
            </tr>
            <tr>
                <td>7. Fumante?</td>
                <td><?= $adhesion->adhesion_proponent_statement->smoker ? 'Sim, ' . $adhesion->adhesion_proponent_statement->smoker_type . ', ' . $adhesion->adhesion_proponent_statement->smoker_type_obs : 'Não' ?></td>
            </tr>
            <tr>
                <td>8. Peso e Altura</td>
                <td><?= $adhesion->adhesion_proponent_statement->weight ?? '' ?> Kg e <?= number_format($adhesion->adhesion_proponent_statement->height ?? 0, 2, ',', '.') ?> m</td>
            </tr>
            <tr>
                <td>9. Apresenta, no momento, sintomas de gripe, febre, cansaço, tosse, coriza, dores pelo corpo, dor de cabeça, dor de garganta, falta de ar, perda de olfato, perda de paladar ou está aguardando resultado do teste da COVID-19?</td>
                <td><?= $adhesion->adhesion_proponent_statement->gripe ? 'Sim, ' . $adhesion->adhesion_proponent_statement->gripe_obs : 'Não' ?></td>
            </tr>
            <tr>
                <td>10. Foi diagnosticado(a) com infecção pelo novo CORONA VÍRUS ou COVID-19?</td>
                <td><?= $adhesion->adhesion_proponent_statement->covid ? 'Sim, ' . $adhesion->adhesion_proponent_statement->covid_obs : 'Não' ?></td>
            </tr>
            <tr>
                <td>11. Apresenta, no momento, sequelas do COVID-19 diferente de perda de olfato e/ou paladar?</td>
                <td><?= $adhesion->adhesion_proponent_statement->covid_sequelae ? 'Sim, ' . $adhesion->adhesion_proponent_statement->covid_sequelae_obs : 'Não' ?></td>
            </tr>
        </table>

        <div class="small-note">
            Declaro ter recebido o exemplar do estatuto da Sul Previdência e do regulamento do plano PlenoPrev, bem como o material explicativo sobre o referido plano. Declaro também que tive prévio e expresso conhecimento e estou de acordo com os termos dos regulamentos dos planos de pecúlio, contratados pela Sul Previdência na MAG Seguros, e por mim custeados, que determinam como único beneficiário a Sul Previdência, o que não poderá ser alterado. Entendo que a responsabilidade pelo pagamento das rendas mensais de aposentadoria programada, aposentadoria por invalidez e pensão será da Sul Previdência. Desta maneira, reconheço que a minha assinatura na presente proposta implica na minha automática adesão aos referidos regulamentos, sabendo, desde já, que a aceitação dos planos de risco está sujeita à análise do risco. Declaro, ainda, que as informações por mim fornecidas são verdadeiras e ciente estou de que quaisquer omissões ou falsidades tornarão nula esta proposta, nos termos do Art. 766 do Código Civil, podendo vir a responder civil e criminalmente pelas inveracidades eventualmente verificadas. Autorizo, desde já, médicos, hospitais, clínicas ou quaisquer entidades públicas ou privadas a prestar à MAG Seguros informações relacionadas ao meu estado de saúde ou moléstias que eu possa sofrer ou ter sofrido, bem como resultados de exames e tratamentos instituídos, isentando-os, desde já, de qualquer responsabilidade que implique em ofensa ou sigilo profissional. Comprometo-me a informar à Sul Previdência a minha condição de pessoa politicamente exposta, mesmo que ocorrida após a assinatura da proposta, durante a vigência do plano, conforme os termos definidos na IN MPS nº 34/2020 e na Circular SUSEP nº 612/2020.
        </div>

        <table class="signature-table invisible-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                </td>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                </td>
            </tr>
            <tr>
                <td class="signature-label">Local e Data</td>
                <td class="signature-label">Assinatura do Proponente ou Representante Legal</td>
            </tr>
        </table>

        <div style="margin-top: 15px;">
            <div style="font-weight:bold; font-size: 9pt;">PARA USO DA SUL PREVIDÊNCIA</div>
            <table class="invisible-table" style="width: auto;">
                <tr>
                    <td>
                        <div class="light-box">Conferido em</div>
                    </td>
                    <td>
                        <div class="light-box">Visto</div>
                    </td>
                </tr>
            </table>
            <div style="font-size:8pt;">De acordo com a solicitação do proponente.</div>
        </div>

        <table class="signature-table invisible-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                </td>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                </td>
            </tr>
            <tr>
                <td class="signature-label">Local e Data</td>
                <td class="signature-label">Assinatura do Representante da Sul Previdência</td>
            </tr>
        </table>

        <div style="border-top: 1px solid #000; border-bottom: 1px solid #000; text-align: center;">
            Gestor do plano: Sociedade de Previdência Complementar Sul Previdência - CNPJ: 12.148.125/0001-42
        </div>
    </div>

    <div class="page-break"></div>

    <div>
        <div class="section-title">PARA USO DA SEGURADORA</div>
        <table>
            <tr>
                <td colspan="2">Nome Proponente<br><br></td>
                <td colspan="2">CPF<br><br></td>
                <td>Cód. Órgão<br><br></td>
                <td>A partir de<br><br></td>
            </tr>
            <tr>
                <td>Convênio Adesão<br><br></td>
                <td>Ação Marketing<br><br></td>
                <td>Alternativa<br><br></td>
                <td>Sucursal<br><br></td>
                <td>Diretor Regional<br><br></td>
                <td>Gerente de Sucursal<br><br></td>
            </tr>
            <tr>
                <td colspan="2">Gerente Comercial<br><br></td>
                <td colspan="2">Agente<br><br></td>
                <td>Corretor 1<br><br></td>
                <td>Corretor 2<br><br></td>
            </tr>
        </table>

        <div class="section-title">PARA USO DO CORRETOR</div>
        <table>
            <tr>
                <td colspan="2">Nome do Corretor<br><br></td>
                <td>Código SUSEP<br><br></td>
                <td>Assinatura<br><br></td>
            </tr>
        </table>

        <div class="section-title">DECLARAÇÕES DO PROPONENTE</div>
        <div class="small-note">
            Declaro ter recebido o exemplar do estatuto da Sul Previdência e do regulamento do plano PlenoPrev, bem como material explicativo sobre o referido plano. Declaro também que tive prévio e expresso conhecimento e estou de acordo com os termos dos regulamentos dos planos de Pecúlio, contratados pela Sul Previdência junto à MAG Seguros, e por mim custeados, que determinam como único beneficiário a Sul Previdência, o que não poderá ser alterado. Entendo que a responsabilidade pelo pagamento das rendas mensais de aposentadoria programada, aposentadoria por invalidez e pensão será da Sul Previdência. Desta maneira, reconheço que a minha assinatura na presente proposta implica na minha automática adesão aos referidos regulamentos, sabendo, desde já, que a aceitação dos planos de risco está sujeita à análise do risco.
            <br>
            Declaro, ainda, que as informações por mim fornecidas são verdadeiras e ciente estou de que quaisquer omissões ou falsidades tornarão nula esta proposta, nos termos do Artigo 766 do Código Civil, podendo vir a responder civil e criminalmente pelas inveracidades eventualmente verificadas. Autorizo, desde já, médicos, hospitais, clínicas ou quaisquer entidades públicas ou privadas a prestar à MAG Seguros informações relacionadas ao meu estado de saúde ou moléstias que eu possa sofrer ou ter sofrido, bem como resultados de exames e tratamentos instituídos, isentando-os, desde já, de qualquer responsabilidade que implique em ofensa ou sigilo profissional.
            <br>
            Comprometo-me a informar à Sul Previdência a minha condição de pessoa politicamente exposta, mesmo que ocorrida após a assinatura da proposta, durante a vigência do plano, conforme os termos definidos na IN MPS nº 34/2020 e na Circular SUSEP nº 612/2020.
            <br>
            Concordo em receber eletronicamente o Relatório Anual com informações do Plano, bem como autorizo a utilização do endereço eletrônico para envio de demais informações e documentos relacionados com o Plano.
            <strong>ACEITE: SIM</strong>
        </div>

        <table class="signature-table invisible-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                </td>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                </td>
            </tr>
            <tr>
                <td class="signature-label">Local e Data</td>
                <td class="signature-label">Assinatura do Proponente ou Representante Legal</td>
            </tr>
        </table>

        <div class="section-title">PARA USO DA SUL PREVIDÊNCIA</div>
        <div class="small-note">
            Autorizo o banco designado no anverso a debitar na conta corrente, por mim indicada, o valor correspondente às contribuições do plano contratado nesta proposta. Estou ciente de que os débitos em conta corrente serão comandados tendo por base as informações enviadas diretamente pela Sul Previdência ao banco. Comprometo-me, desde já, a manter saldo suficiente para a finalidade, isentando o banco de qualquer responsabilidade caso a conta não comporte o valor do documento a liquidar. Declaro-me ciente de que o banco poderá, mediante aviso, com antecedência mínima de 15 (quinze) dias do vencimento do encargo ou da próxima parcela, tornar sem efeito a presente autorização, reservando-me adotar o mesmo procedimento, quando do meu interesse. Declaro que as informações prestadas são verdadeiras, não havendo responsabilidade da Sul Previdência ou do banco informado nesta proposta pela não efetivação dos débitos em função de informações incorretas.
        </div>

        <table class="signature-table invisible-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                </td>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                </td>
            </tr>
            <tr>
                <td class="signature-label">Local e Data</td>
                <td class="signature-label">Assinatura do Correntista</td>
            </tr>
        </table>

        <div style="margin-top: 15px;">
            <div style="font-weight:bold; font-size: 9pt;">PARA USO DA SUL PREVIDÊNCIA</div>
            <table class="invisible-table" style="width: auto;">
                <tr>
                    <td>
                        <div class="light-box">Conferido em</div>
                    </td>
                    <td>
                        <div class="light-box">Visto</div>
                    </td>
                </tr>
            </table>
            <div style="font-size:8pt;">De acordo com a solicitação do proponente.</div>
        </div>

        <table class="signature-table invisible-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                </td>
                <td class="signature-cell">
                    <div class="signature-line"></div>
                </td>
            </tr>
            <tr>
                <td class="signature-label">Local e Data</td>
                <td class="signature-label">Assinatura do Representante da Sul Previdência</td>
            </tr>
        </table>

        <div class="footer-text">
            Gestor do plano: Sociedade de Previdência Complementar Sul Previdência - CNPJ: 12.148.125/0001-42<br>
            Rua Vidal Ramos nº 31 - Sala 602 - Centro - Florianópolis - SC<br>
            www.sulprevidencia.org.br
        </div>
    </div>
</body>

</html>