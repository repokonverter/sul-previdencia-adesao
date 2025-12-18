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

        /* Tabelas Gerais */
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

        /* Cabeçalho Limpo (Sem bordas) */
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

        /* Títulos de Seção */
        .section-title {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 10pt;
            padding: 4px;
            border: 1px solid #000;
            margin-top: 8px;
            text-transform: uppercase;
        }

        /* Checkbox Simulado (Visual apenas) */
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
            margin-top: 25px;
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
            width: 100px;
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
                        <span class="checkbox checked"></span> Contratação avulsa de cobertura de risco
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
                <td colspan="3">Nome Completo<br><?= $adhesion->adhesion_personal_data->name ?? '' ?></td>
                <td>Data de Nascimento<br><?= $adhesion->adhesion_personal_data->birth_date ? $adhesion->adhesion_personal_data->birth_date->format('d/m/Y') : '' ?></td>
            </tr>
            <tr>
                <td>Idade<br><?= $age ?? '' ?></td>
                <td>Sexo<br><?= $adhesion->adhesion_personal_data->gender ?? '' ?></td>
                <td>Estado Civil<br><?= $adhesion->adhesion_personal_data->marital_status ?? '' ?></td>
                <td>Nº de Filhos<br><?= $adhesion->adhesion_personal_data->number_childrens ?? 0 ?></td>
                <td>CPF / Tipo<br><?= $adhesion->adhesion_personal_data->cpf ?? '' ?>, <span class="bold"><?= $adhesion->adhesion_personal_data->plan_for ?? '' ?></span></td>
            </tr>
            <tr>
                <td>Nacionalidade<br><?= $adhesion->adhesion_personal_data->nacionality ?? '' ?></td>
                <td>Nat. do Documento<br><?= $adhesion->adhesion_document->type ?? '' ?> <?= $adhesion->adhesion_document->type_other ?? '' ?></td>
                <td>Nº do Documento<br><?= $adhesion->adhesion_document->document_number ?? '' ?></td>
                <td>Órgão Expedidor<br><?= $adhesion->adhesion_document->issuer ?? '' ?> / <?= $adhesion->adhesion_document->state ?? '' ?></td>
                <td>Data de Expedição<br><?= $adhesion->adhesion_document->issue_date ? $adhesion->adhesion_document->issue_date->format('d/m/Y') : '' ?></td>
            </tr>
            <tr>
                <td>Ocupação princ.<br><?= $adhesion->adhesion_other_information->main_occupation ?? '' ?></td>
                <td colspan="3">
                    Cód: <?= $adhesion->adhesion_other_information->main_occupation ?? '' ?>
                    (<?= $adhesion->adhesion_other_information->status ?? '' ?>)
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    Categoria: <?= $adhesion->adhesion_other_information->category ?? '' ?>
                </td>
            </tr>
            <tr>
                <td>Pessoa Politicamente Exposta?</td>
                <td><?= $adhesion->adhesion_other_information->politically_exposed ? 'Sim. Especificar: ' . ($adhesion->adhesion_other_information->politically_exposed_obs ?? '') : 'Não' ?></td>
                <td>Residente no Brasil?</td>
                <td><?= $adhesion->adhesion_other_information->brazilian_resident ? 'Sim' : 'Não' ?></td>
            </tr>
            <tr>
                <td>Obrigações fiscais exterior?</td>
                <td colspan="3"><?= $adhesion->adhesion_other_information->obligation_other_countries ? 'Sim' : 'Não' ?></td>
            </tr>
            <tr>
                <td>Endereço Residencial</td>
                <td colspan="3">
                    <?= $adhesion->adhesion_address->address ?? '' ?>, Nº <?= $adhesion->adhesion_address->number ?? '' ?>
                    <?= $adhesion->adhesion_address->complement ? ' - ' . $adhesion->adhesion_address->complement : '' ?>
                </td>
            </tr>
            <tr>
                <td>Bairro / Cidade / UF</td>
                <td colspan="3">
                    <?= $adhesion->adhesion_address->neighborhood ?? '' ?> -
                    <?= $adhesion->adhesion_address->city ?? '' ?> / <?= $adhesion->adhesion_address->state ?? '' ?>
                </td>
            </tr>
            <tr>
                <td>CEP</td>
                <td><?= $adhesion->adhesion_address->cep ?? '' ?></td>
                <td>Telefone / Celular</td>
                <td><?= $adhesion->phone ?? '' ?></td>
            </tr>
            <tr>
                <td>E-mail</td>
                <td colspan="3"><?= $adhesion->email ?? '' ?></td>
            </tr>
            <tr>
                <td>Representante Legal</td>
                <td colspan="3">
                    Nome: <?= $adhesion->adhesion_personal_data->name_legal_representative ?? '' ?> |
                    CPF: <?= $adhesion->adhesion_personal_data->cpf_legal_representative ?? '' ?>
                </td>
            </tr>
            <tr>
                <td>Filiação</td>
                <td colspan="3">
                    Mãe: <?= $adhesion->adhesion_personal_data->mother_name ?? '' ?> |
                    Pai: <?= $adhesion->adhesion_personal_data->father_name ?? '' ?>
                </td>
            </tr>
        </table>

        <div class="small-note">
            <b>¹ Pessoas politicamente expostas:</b> Consideram-se pessoas politicamente expostas os agentes públicos que desempenham ou tenham desempenhado, nos 5 (cinco) anos anteriores, empregos ou funções públicas relevantes...
            <br>
            <b>² FATCA:</b> Lei federal norte-americana que prevê a obrigatoriedade de instituições bancárias estrangeiras fornecerem dados...
        </div>

        <div class="section-title">PLANO DE BENEFÍCIOS</div>
        <table>
            <tr>
                <td width="50%">Benefício</td>
                <td>APOSENTADORIA PROGRAMADA</td>
            </tr>
            <tr>
                <td>Idade para Entrada em Benefício</td>
                <td><?= $adhesion->adhesion_plan->benefit_entry_age ?? '' ?> anos</td>
            </tr>
            <tr>
                <td>Valor da Contribuição (1)</td>
                <td>R$ <?= number_format($adhesion->adhesion_plan->monthly_retirement_contribution ?? 0, 2, ',', '.') ?></td>
            </tr>
        </table>
        <div class="small-note">Taxa de Carregamento: 0%. Atualização anual (Junho/INPC). CNPB nº 2011/0017-65.</div>

        <div class="section-title">BENEFICIÁRIOS DO PLANO</div>
        <table>
            <tr>
                <th>Nome Completo</th>
                <th>Data Nasc.</th>
                <th>Parentesco*</th>
                <th>%</th>
            </tr>
            <?php if (!empty($adhesion->adhesion_dependents)): ?>
                <?php foreach ($adhesion->adhesion_dependents as $dependent): ?>
                    <tr>
                        <td><?= $dependent->name ?? '' ?></td>
                        <td><?= $dependent->birth_date ? $dependent->birth_date->format('d/m/Y') : '' ?></td>
                        <td><?= $dependent->kinship ?? '' ?></td>
                        <td><?= $dependent->participation ?? '' ?> %</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">Não há beneficiários indicados. Observar art. 792 do Código Civil.</td>
                </tr>
            <?php endif; ?>
        </table>
        <div class="small-note">*Legenda: C-Cônjuge; F-Filho(a); I-Irmão(ã); M-Mãe; P-Pai; Outros...</div>

        <div class="section-title">PARCELA ADICIONAL DE RISCO</div>
        <table>
            <tr>
                <td>PECÚLIO POR MORTE (Proc. 15.414.000077/2005-16)</td>
                <td>
                    Capital: R$ <?= number_format($adhesion->adhesion_plan->survivors_pension_insured_capital ?? 0, 2, ',', '.') ?> |
                    Contrib.: R$ <?= number_format($adhesion->adhesion_plan->monthly_survivors_pension_contribution ?? 0, 2, ',', '.') ?>
                </td>
            </tr>
            <tr>
                <td>PECÚLIO POR INVALIDEZ (Proc. 15.414.000078/2005-52)</td>
                <td>
                    Capital: R$ <?= number_format($adhesion->adhesion_plan->disability_retirement_insured_capital ?? 0, 2, ',', '.') ?> |
                    Contrib.: R$ <?= number_format($adhesion->adhesion_plan->monthly_disability_retirement_contribution ?? 0, 2, ',', '.') ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="text-right bold">
                    Total Risco (2): R$ <?= number_format(($adhesion->adhesion_plan->monthly_survivors_pension_contribution ?? 0) + ($adhesion->adhesion_plan->monthly_disability_retirement_contribution ?? 0), 2, ',', '.') ?>
                </td>
            </tr>
        </table>

        <div class="section-title">DADOS PARA PAGAMENTO</div>
        <table>
            <tr>
                <td>Vencimento: Dia <?= $adhesion->adhesion_payment_detail->due_date ?? '' ?></td>
                <td>Total (1+2): R$ <?= number_format($adhesion->adhesion_payment_detail->total_contribution ?? 0, 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Forma Pagamento</td>
                <td class="bold"><?= $adhesion->adhesion_payment_detail->payment_type ?? '' ?></td>
            </tr>
            <?php if (($adhesion->adhesion_payment_detail->payment_type ?? '') == 'Débito em Conta'): ?>
                <tr>
                    <td colspan="2">
                        Banco: <?= $adhesion->adhesion_payment_detail->bank_number ?? '' ?> - <?= $adhesion->adhesion_payment_detail->bank_name ?? '' ?> |
                        Ag: <?= $adhesion->adhesion_payment_detail->branch_number ?? '' ?> |
                        CC: <?= $adhesion->adhesion_payment_detail->account_number ?? '' ?>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="page-break"></div>

    <div>
        <div style="font-size: 8pt; text-align: justify; border: 1px dashed #ccc; padding: 5px; margin-bottom: 10px;">
            Espaço para relógio protocolo. A aceitação estará sujeita à análise do risco e a MONGERAL AEGON tem o prazo de até 15 dias...
        </div>

        <div class="section-title">PARA USO DA SEGURADORA</div>
        <table class="seg-table">
            <tr>
                <th width="30%">Nome Proponente</th>
                <th width="20%">CPF</th>
                <th width="25%">Cód. Órgão</th>
                <th width="25%">A partir de</th>
            </tr>
            <tr>
                <td><br></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <th>Convênio Adesão</th>
                <th>Ação Marketing</th>
                <th>Alternativa</th>
                <th>Sucursal</th>
            </tr>
            <tr>
                <td><br></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <th>Gerente Comercial</th>
                <th>Agente</th>
                <th>Corretor 1</th>
                <th>Corretor 2</th>
            </tr>
            <tr>
                <td><br></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>

        <div class="section-title">DECLARAÇÃO PESSOAL DE SAÚDE</div>
        <table style="font-size: 8pt;">
            <tr>
                <th width="70%">Perguntas</th>
                <th width="10%">Resp.</th>
                <th width="20%">Obs.</th>
            </tr>
            <tr>
                <td>1. Problema de saúde ou medicamentos?</td>
                <td class="text-center"><?= $adhesion->adhesion_proponent_statement->health_problem ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->health_problem_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>2. Doenças coração, hipertensão, diabetes, câncer?</td>
                <td class="text-center"><?= $adhesion->adhesion_proponent_statement->heart_disease ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->heart_disease_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>3. Deficiências, LER/DORT?</td>
                <td class="text-center"><?= $adhesion->adhesion_proponent_statement->suffered_organ_defects ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->suffered_organ_defects_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>4. Cirurgia/Internação (5 anos)?</td>
                <td class="text-center"><?= $adhesion->adhesion_proponent_statement->surgery ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->surgery_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>5. Afastado/Aposentado invalidez?</td>
                <td class="text-center"><?= $adhesion->adhesion_proponent_statement->away ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->away_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>6. Esportes de risco/Voo?</td>
                <td class="text-center"><?= $adhesion->adhesion_proponent_statement->practices_parachuting ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->practices_parachuting_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>7. Fumante?</td>
                <td class="text-center"><?= $adhesion->adhesion_proponent_statement->smoker ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->smoker_type_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>8. Peso / Altura</td>
                <td colspan="2"><?= $adhesion->adhesion_proponent_statement->weight ?? '' ?> Kg / <?= $adhesion->adhesion_proponent_statement->height ?? '' ?> m</td>
            </tr>
            <tr>
                <td>9. Sintomas gripe/COVID?</td>
                <td class="text-center"><?= $adhesion->adhesion_proponent_statement->gripe ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->gripe_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>10. Já teve COVID-19?</td>
                <td class="text-center"><?= $adhesion->adhesion_proponent_statement->covid ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->covid_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>11. Sequelas COVID-19?</td>
                <td class="text-center"><?= $adhesion->adhesion_proponent_statement->covid_sequelae ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->covid_sequelae_obs ?? '' ?></td>
            </tr>
        </table>

        <div class="small-note">
            Declaro ter recebido o estatuto da Sul Previdência e o regulamento do PlenoPrev. As informações são verdadeiras sob pena do Art. 766 do Código Civil. Autorizo médicos/hospitais a prestar informações à MAG Seguros.
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
                <td class="signature-label">Assinatura do Proponente / Rep. Legal</td>
            </tr>
        </table>

        <div style="margin-top:20px; border-top: 1px solid #000; padding-top: 5px;">
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
    </div>

    <div class="page-break"></div>

    <div>
        <div class="section-title">PARA USO DA SEGURADORA</div>
        <table class="seg-table">
            <tr>
                <th width="30%">Nome Proponente</th>
                <th width="20%">CPF</th>
                <th width="25%">Cód. Órgão</th>
                <th width="25%">A partir de</th>
            </tr>
            <tr>
                <td><br></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <th>Convênio Adesão</th>
                <th>Ação Marketing</th>
                <th>Alternativa</th>
                <th>Sucursal</th>
            </tr>
            <tr>
                <td><br></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <th>Gerente Comercial</th>
                <th>Agente</th>
                <th>Corretor 1</th>
                <th>Corretor 2</th>
            </tr>
            <tr>
                <td><br></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>

        <div class="section-title">DECLARAÇÕES E AUTORIZAÇÕES</div>
        <div class="small-note" style="margin-bottom: 10px;">
            Declaro ter recebido os regulamentos e estatutos. Reconheço que a assinatura implica adesão automática.
            <br>
            Concordo em receber eletronicamente o Relatório Anual e demais informações do Plano.
            <br>
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
                <td class="signature-label">Assinatura do Proponente / Rep. Legal</td>
            </tr>
        </table>

        <div class="section-title" style="margin-top:20px;">AUTORIZAÇÃO DE DÉBITO (USO SUL PREVIDÊNCIA)</div>
        <div class="small-note">
            Autorizo o débito em conta corrente das contribuições. Comprometo-me a manter saldo suficiente.
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

        <div style="margin-top:20px;">
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
                    <td class="signature-label">Assinatura Rep. Sul Previdência</td>
                </tr>
            </table>
        </div>

        <div class="footer-text">
            Gestor do plano: Sociedade de Previdência Complementar Sul Previdência - CNPJ: 12.148.125/0001-42<br>
            Rua Vidal Ramos nº 31 - Sala 602 - Centro - Florianópolis - SC<br>
            www.sulprevidencia.org.br
        </div>
    </div>
</body>

</html>