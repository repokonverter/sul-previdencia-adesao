<!-- pdf_template.ctp -->
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 5px; text-align: left; }
        .checkbox { font-size: 12pt; }
        .header { text-align: center; font-weight: bold; }
        .section-title { background-color: #f0f0f0; font-weight: bold; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <!-- Page 1 -->
    <div>
        <h1 class="header">PROPOSTA DE INSCRIÇÃO</h1>
        <p>Contratação avulsa de cobertura de risco</p>
        <p>1ª Via: MAG Seguros - 2ª Via: Sul Previdência - 3ª Via: Participante</p>
        <p>BFX - Proposta Plenoprev <?= date('M/Y') ?></p>

        <!-- Header table -->
        <table>
            <tr><td>CNPJ do Plano</td><td>48.307.525/0001-09</td></tr>
            <tr><td>Nº do Instituidor</td><td></td></tr> <!-- Assumir vazio ou fixo -->
            <tr><td>Nome do Instituidor</td><td></td></tr> <!-- Assumir vazio ou fixo -->
            <tr><td>Nº da Proposta</td><td><?= $proposalNumber ?></td></tr>
        </table>

        <!-- Personal data -->
        <table>
            <tr>
                <td>Nome Completo</td>
                <td colspan="3"><?= $adhesion->adhesion_personal_data->name ?? '' ?></td>
            </tr>
            <tr>
                <td>Data de Nascimento</td>
                <td><?= $adhesion->adhesion_personal_data->birth_date ? $adhesion->adhesion_personal_data->birth_date->format('d/m/Y') : '' ?></td>
                <td>Idade</td>
                <td><?= $age ?? '' ?></td>
            </tr>
            <tr>
                <td>Sexo</td>
                <td class="checkbox">
                    <?= ($adhesion->adhesion_personal_data->gender ?? '') == 'F' ? '&#x2611; F &#x2610; M' : '&#x2610; F &#x2611; M' ?>
                </td>
                <td>Estado Civil</td>
                <td>
                    <?php
                    $marital = $adhesion->adhesion_personal_data->marital_status ?? '';
                    $options = ['Solteiro' => 'Solteiro', 'Casado' => 'Casado', 'Divorciado' => 'Divorciado', 'Viúvo' => 'Viúvo', 'Separado' => 'Separado', 'União Estável' => 'União Estável'];
                    foreach ($options as $key => $label) {
                        echo ($marital == $key ? '&#x2611;' : '&#x2610;') . ' ' . $label . ' ';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>Nº de Filhos</td>
                <td><?= $adhesion->adhesion_personal_data->number_childrens ?? 0 ?></td>
                <td>CPF</td>
                <td><?= $adhesion->adhesion_personal_data->cpf ?? '' ?> (<?= ($adhesion->adhesion_personal_data->plan_for ?? '') == 'Titular' ? '&#x2611; Titular &#x2610; Dependente' : '&#x2610; Titular &#x2611; Dependente' ?>)</td>
            </tr>
            <tr>
                <td>Nacionalidade</td>
                <td><?= $adhesion->adhesion_personal_data->nacionality ?? '' ?></td>
                <td>Natureza do Documento</td>
                <td><?= $adhesion->adhesion_document->type ?? '' ?> <?= $adhesion->adhesion_document->type_other ?? '' ?></td>
            </tr>
            <tr>
                <td>Nº do Documento</td>
                <td><?= $adhesion->adhesion_document->document_number ?? '' ?></td>
                <td>Órgão Expedidor</td>
                <td><?= $adhesion->adhesion_document->issuer ?? '' ?></td>
            </tr>
            <tr>
                <td>Data de Expedição</td>
                <td><?= $adhesion->adhesion_document->issue_date ? $adhesion->adhesion_document->issue_date->format('d/m/Y') : '' ?></td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td>Ocupação Principal</td>
                <td>Código: <?= $adhesion->adhesion_other_information->main_occupation ?? '' ?> (<?= isset($adhesion->adhesion_other_information) ? '&#x2611; Ativo &#x2610; Inativo' : '' ?>)</td> <!-- Ajustar baseado em dados -->
                <td>Empresa em que Trabalha</td>
                <td></td> <!-- Não mapeado; assumir vazio -->
            </tr>
            <tr>
                <td>Categoria</td>
                <td>
                    <?php
                    $category = $adhesion->adhesion_other_information->category ?? '';
                    $catOptions = ['Empregado' => 'Empregado', 'Servidor Público' => 'Servidor Público', 'Outros' => 'Outros', 'Empregador' => 'Empregador', 'Autônomo' => 'Autônomo'];
                    foreach ($catOptions as $key => $label) {
                        echo ($category == $key ? '&#x2611;' : '&#x2610;') . ' ' . $label . ' ';
                    }
                    ?>
                </td>
                <td>Renda Mensal Bruta</td>
                <td></td> <!-- Não mapeado -->
            </tr>
            <tr>
                <td>É Pessoa Exposta Politicamente?</td>
                <td><?= $adhesion->adhesion_other_information->politically_exposed ? 'Sim. Especificar: ' : 'Não' ?></td>
                <td>Residente no Brasil?</td>
                <td><?= $adhesion->adhesion_other_information->brazilian_resident ? 'Sim' : 'Não' ?></td>
            </tr>
            <tr>
                <td>Você tem obrigações fiscais com outros países?</td>
                <td colspan="3"><?= $adhesion->adhesion_other_information->obligation_other_countries ? 'Sim. Favor preencher o Formulário...' : 'Não' ?></td>
            </tr>
            <tr>
                <td>Endereço Residencial</td>
                <td colspan="3"><?= $adhesion->adhesion_address->address ?? '' ?></td>
            </tr>
            <tr>
                <td>Nº</td>
                <td><?= $adhesion->adhesion_address->number ?? '' ?></td>
                <td>Complemento</td>
                <td><?= $adhesion->adhesion_address->complement ?? '' ?></td>
            </tr>
            <tr>
                <td>Bairro</td>
                <td><?= $adhesion->adhesion_address->neighborhood ?? '' ?></td>
                <td>Cidade</td>
                <td><?= $adhesion->adhesion_address->city ?? '' ?></td>
            </tr>
            <tr>
                <td>UF</td>
                <td><?= $adhesion->adhesion_address->state ?? '' ?></td>
                <td>CEP</td>
                <td><?= $adhesion->adhesion_address->cep ?? '' ?></td>
            </tr>
            <tr>
                <td>DDD</td>
                <td>Telefone Fixo</td>
                <td>DDD</td>
                <td>Telefone Celular: <?= $adhesion->phone ?? '' ?></td>
            </tr>
            <tr>
                <td>E-mail</td>
                <td colspan="3"><?= $adhesion->email ?? '' ?></td>
            </tr>
            <tr>
                <td>É participante?</td>
                <td>Sim &#x2610; Não &#x2610;</td> <!-- Ajustar se mapeado -->
                <td colspan="2"></td>
            </tr>
            <tr>
                <td>Nome Completo do Representante Legal</td>
                <td colspan="3"><?= $adhesion->adhesion_personal_data->name_legal_representative ?? '' ?></td>
            </tr>
            <tr>
                <td>CPF do Representante Legal</td>
                <td colspan="3"><?= $adhesion->adhesion_personal_data->cpf_legal_representative ?? '' ?></td>
            </tr>
            <tr>
                <td>Filiação</td>
                <td colspan="3">Mãe: <?= $adhesion->adhesion_personal_data->mother_name ?? '' ?> Pai: <?= $adhesion->adhesion_personal_data->father_name ?? '' ?></td>
            </tr>
        </table>

        <!-- Plano de Benefícios -->
        <h2 class="section-title">PLANO DE BENEFÍCIOS</h2>
        <table>
            <tr><td>Benefício</td><td>APOSENTADORIA PROGRAMADA</td></tr>
            <tr><td>Idade para Entrada em Benefício</td><td><?= $adhesion->adhesion_plan->benefit_entry_age ?? '' ?> anos</td></tr>
            <tr><td>Valor da Contribuição (1)</td><td>R$ <?= number_format($adhesion->adhesion_plan->monthly_retirement_contribution ?? 0, 2, ',', '.') ?></td></tr>
        </table>
        <p>Taxa de Carregamento do plano: 0%. O valor de contribuição será atualizado, anualmente, no mês de junho, pela variação do INPC.</p>
        <p>Inscrição do plano no Cadastro Nacional de Planos de Benefícios (CNPB) nº 2011/0017-65.</p>

        <!-- Beneficiários -->
        <h2 class="section-title">BENEFICIÁRIOS DO PLANO</h2>
        <table>
            <tr><th>Nome Completo</th><th>Data de Nascimento</th><th>Parentesco*</th><th>Participação</th></tr>
            <?php foreach ($adhesion->adhesion_dependents as $dependent): ?>
            <tr>
                <td><?= $dependent->name ?? '' ?></td>
                <td><?= $dependent->birth_date ? $dependent->birth_date->format('d/m/Y') : '' ?></td>
                <td><?= $dependent->kinship ?? '' ?></td> <!-- Mapear para A, C, F etc. se necessário -->
                <td><?= $dependent->participation ?? '' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <p>*A - Avô(ó); C- Cônjuge; F - Filho(a); H - Companheiro(a); I - Irmão(ã); M - Mãe; N - Neto(a); P - Pai; S - Sobrinho(a); T - Tio(a); U - Nenhum</p>

        <!-- Parcela Adicional de Risco -->
        <h2 class="section-title">PARCELA ADICIONAL DE RISCO</h2>
        <table>
            <tr><td>Benefício</td><td>1102 - PECÚLIO POR MORTE</td></tr>
            <tr><td>Nº Processo SUSEP</td><td>15.414.000077/2005-16</td></tr>
            <tr><td>Valor do Pecúlio</td><td>R$ <?= number_format($adhesion->adhesion_plan->survivors_pension_insured_capital ?? 0, 2, ',', '.') ?></td></tr>
            <tr><td>Valor da Contribuição</td><td>R$ <?= number_format($adhesion->adhesion_plan->monthly_survivors_pension_contribution ?? 0, 2, ',', '.') ?></td></tr>
            <tr><td>1103 - PECÚLIO POR INVALIDEZ</td><td>15.414.000078/2005-52</td></tr>
            <tr><td>Valor do Pecúlio</td><td>R$ <?= number_format($adhesion->adhesion_plan->disability_retirement_insured_capital ?? 0, 2, ',', '.') ?></td></tr>
            <tr><td>Valor da Contribuição</td><td>R$ <?= number_format($adhesion->adhesion_plan->monthly_disability_retirement_contribution ?? 0, 2, ',', '.') ?></td></tr>
        </table>
        <p>Pecúlios garantidos pela Mongeral Aegon Seguros e Previdência CNPJ: 33.608.308/0001-73 Carregamento: 30%</p>
        <p>Total da Parcela Adicional de Risco (2) R$ <?= number_format(($adhesion->adhesion_plan->monthly_survivors_pension_contribution ?? 0) + ($adhesion->adhesion_plan->monthly_disability_retirement_contribution ?? 0), 2, ',', '.') ?></p>
        <p>O valor da contribuição para o(s) pecúlio(s) será atualizado, anualmente, no mês de junho, pela variação do INPC e em função da nova idade atingida pelo participante. O registro destes planos na SUSEP não implica, por parte da autarquia, incentivo ou recomendação para sua comercialização.</p>

        <!-- Dados para Pagamento -->
        <h2 class="section-title">DADOS PARA PAGAMENTO</h2>
        <table>
            <tr><td>Dia do Vencimento</td><td><?= $adhesion->adhesion_payment_detail->due_date ?? '' ?></td></tr>
            <tr><td>Total da Contribuição - R$ (1+2)</td><td><?= number_format($adhesion->adhesion_payment_detail->total_contribution ?? 0, 2, ',', '.') ?></td></tr>
            <tr>
                <td colspan="2">
                    <?= ($adhesion->adhesion_payment_detail->payment_type ?? '') == 'Consignação em folha' ? '&#x2611;' : '&#x2610;' ?> Consignação em folha
                    <?= ($adhesion->adhesion_payment_detail->payment_type ?? '') == 'Débito em Conta' ? '&#x2611;' : '&#x2610;' ?> Débito em Conta
                    <?= ($adhesion->adhesion_payment_detail->payment_type ?? '') == 'Boleto Bancário' ? '&#x2611;' : '&#x2610;' ?> Boleto Bancário
                </td>
            </tr>
            <tr><td>Nome do Correntista</td><td><?= $adhesion->adhesion_payment_detail->account_holder_name ?? '' ?></td></tr>
            <tr><td>CPF do Correntista</td><td><?= $adhesion->adhesion_payment_detail->account_holder_cpf ?? '' ?></td></tr>
            <tr><td>Nº do Banco</td><td><?= $adhesion->adhesion_payment_detail->bank_number ?? '' ?></td></tr>
            <tr><td>Nome do Banco</td><td><?= $adhesion->adhesion_payment_detail->bank_name ?? '' ?></td></tr>
            <tr><td>Nº da Agência</td><td><?= $adhesion->adhesion_payment_detail->branch_number ?? '' ?></td></tr>
            <tr><td>Nº da Conta Corrente</td><td><?= $adhesion->adhesion_payment_detail->account_number ?? '' ?></td></tr>
        </table>
        <p>Autorização de débito em conta no verso da 2ª via.</p>
    </div>

    <div class="page-break"></div>

    <!-- Page 2 -->
    <div>
        <p>Espaço para relógio protocolo</p>
        <p>A aceitação estará sujeita à análise do risco e a MONGERAL AEGON tem o prazo de até 15 dias, contados da data que vier a ser registrada pelo relógio protocolo, para manifestar-se em relação à aceitação ou recusa desta proposta. Este prazo será suspenso quando necessária a requisição de outros documentos ou dados para análise do risco. Essa eventual suspensão terminará quando forem protocolados os documentos ou dados para análise do risco. Caso não haja manifestação de recusa desta proposta pela MONGERAL AEGON no prazo antes referido, a aceitação da proposta se dará automaticamente. No caso de não aceitação da proposta, o valor aportado será devolvido, atualizado até a data da efetiva restituição, de acordo com a regulamentação em vigor.</p>

        <h2 class="section-title">PARA USO DA SEGURADORA</h2>
        <table>
            <tr><td>Nome do Proponente</td><td><?= $adhesion->adhesion_personal_data->name ?? '' ?></td></tr>
            <tr><td>CPF</td><td><?= $adhesion->adhesion_personal_data->cpf ?? '' ?></td></tr>
            <!-- Outros campos administrativos vazios -->
        </table>

        <h2 class="section-title">PARA USO DO CORRETOR</h2>
        <table>
            <!-- Campos vazios -->
        </table>

        <h2 class="section-title">DECLARAÇÕES DO PROPONENTE Declaração Pessoal de Saúde</h2>
        <table>
            <tr><th>Perguntas</th><th>Respostas</th><th>Em caso afirmativo, especificar</th></tr>
            <tr>
                <td>1. Encontra-se com algum problema de saúde ou faz uso de algum medicamento?</td>
                <td><?= $adhesion->adhesion_proponent_statement->health_problem ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->health_problem_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>2. Sofre ou já sofreu de doenças do coração, hipertensão,circulatórias, do sangue, diabetes, pulmão, fígado, rins, infarto, acidente vascular cerebral, articulações, qualquer tipo de câncer ou HIV?</td>
                <td><?= $adhesion->adhesion_proponent_statement->heart_disease ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->heart_disease_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>3. Sofre ou sofreu de deficiências de órgãos, membros ou sentidos, incluindo doenças ortopédicas relacionadas a esforço repetitivo (LER e DORT)?</td>
                <td><?= $adhesion->adhesion_proponent_statement->suffered_organ_defects ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->suffered_organ_defects_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>4. Fez alguma cirurgia, biópsia ou esteve internado nos últimos cinco anos?</td>
                <td><?= $adhesion->adhesion_proponent_statement->surgery ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->surgery_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>5. Está afastado(a) do trabalho ou aposentado por invalidez?</td>
                <td><?= $adhesion->adhesion_proponent_statement->away ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->away_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>6. Pratica paraquedismo, motociclismo, boxe, asa delta, rodeio, alpinismo, voo livre, automobilismo, mergulho ou exerce atividade, em caráter profissional ou amador, a bordo de aeronaves, que não sejam de linhas regulares?</td>
                <td><?= $adhesion->adhesion_proponent_statement->practices_parachuting ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->practices_parachuting_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>7. É fumante?</td>
                <td><?= $adhesion->adhesion_proponent_statement->smoker ? 'Sim' : 'Não' ?></td>
                <td>Cigarro <?= $adhesion->adhesion_proponent_statement->smoker_type ? '' : '' ?> Outros: <?= $adhesion->adhesion_proponent_statement->smoker_type_obs ?? '' ?> Quantidade média/dia: <?= $adhesion->adhesion_proponent_statement->smoker_qty ?? '' ?></td>
            </tr>
            <tr>
                <td>8. Informe peso e altura:</td>
                <td colspan="2"><?= $adhesion->adhesion_proponent_statement->weight ?? '' ?> Kg e <?= $adhesion->adhesion_proponent_statement->height ?? '' ?> m</td>
            </tr>
            <tr>
                <td>9. Apresenta, no momento, sintomas de gripe, febre, cansaço, tosse, coriza, dores pelo corpo, dor de cabeça, dor de garganta, falta de ar, perda de olfato, perda de paladar ou está aguardando resultado do teste da COVID-19?</td>
                <td><?= $adhesion->adhesion_proponent_statement->gripe ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->gripe_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>10. Foi diagnosticado(a) com infecção pelo novo CORONA VÍRUS ou COVID-19?</td>
                <td><?= $adhesion->adhesion_proponent_statement->covid ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->covid_obs ?? '' ?></td>
            </tr>
            <tr>
                <td>11. Apresenta, no momento, sequelas do COVID-19 diferente de perda de olfato e/ou paladar?</td>
                <td><?= $adhesion->adhesion_proponent_statement->covid_sequelae ? 'Sim' : 'Não' ?></td>
                <td><?= $adhesion->adhesion_proponent_statement->covid_sequelae_obs ?? '' ?></td>
            </tr>
        </table>

        <p>Declaro ter recebido o exemplar do estatuto da Sul Previdência e do regulamento do plano PlenoPrev, bem como o material explicativo sobre o referido plano. [...] (copiar texto legal completo do PDF original)</p>

        <!-- Assinaturas -->
        <table>
            <tr><td>Local e Data</td><td>Assinatura do Proponente ou Representante Legal</td></tr>
            <tr><td>Local e Data</td><td>Assinatura do Representante da Sul Previdência</td></tr>
        </table>
        <p>Gestor do plano: Sociedade de Previdência Complementar Sul Previdência - CNPJ: 12.148.125/0001-42</p>
    </div>

    <div class="page-break"></div>

    <!-- Page 3 -->
    <div>
        <!-- Repetir seções administrativas e declarações semelhantes à página 2, ajustando conforme screenshots -->
        <h2 class="section-title">PARA USO DA SEGURADORA</h2>
        <!-- Campos semelhantes -->

        <h2 class="section-title">PARA USO DO CORRETOR</h2>
        <!-- Campos vazios -->

        <h2 class="section-title">DECLARAÇÕES DO PROPONENTE</h2>
        <p>Declaro ter recebido o exemplar do estatuto da Sul Previdência e do regulamento do plano PlenoPrev, bem como material explicativo sobre o 
referido plano. Declaro também que tive prévio e expresso conhecimento e estou de acordo com os termos dos regulamentos dos planos de Pecúlio, 
contratados pela Sul Previdência junto à MAG Seguros, e por mim custeados, que determinam como único beneficiário a Sul Previdência, o que não 
poderá ser alterado. Entendo que a responsabilidade pelo pagamento das rendas mensais de aposentadoria programada, aposentadoria por invalidez 
e pensão será da Sul Previdência. Desta maneira, reconheço que a minha assinatura na presente proposta implica na minha automática adesão aos 
referidos regulamentos, sabendo, desde já, que a aceitação dos planos de risco está sujeita à análise do risco.</p>

        <p> Declaro, ainda, que as informações por mim fornecidas são verdadeiras e ciente estou de que quaisquer omissões ou falsidades tornarão nula esta 
proposta, nos termos do Artigo 766 do Código Civil, podendo vir a responder civil e criminalmente pelas inveracidades eventualmente verificadas. 
Autorizo, desde já, médicos, hospitais, clínicas ou quaisquer entidades públicas ou privadas a prestar à MAG Seguros informações relacionadas ao meu 
estado de saúde ou moléstias que eu possa sofrer ou ter sofrido, bem como resultados de exames e tratamentos instituídos, isentando-os, desde já, 
de qualquer responsabilidade que implique em ofensa ou sigilo profissional.</p>

        <p> Comprometo-me a informar à Sul Previdência a minha condição de pessoa politicamente exposta, mesmo que ocorrida após a assinatura da proposta, 
durante a vigência do plano, conforme os termos definidos na IN MPS nº 34/2020 e na Circular SUSEP nº 612/2020.</p>

        <p> Concordo em receber eletronicamente o Relatório Anual com informações do Plano, bem como autorizo a utilização do endereço eletrônico para envio 
de demais informações e documentos relacionados com o Plano.</p>

        <p>
            <span class="checkbox checked"></span> Sim  
            <span class="checkbox"></span> Não
        </p>

        <!-- Autorização de débito -->
        

        <!-- Assinaturas -->
        <h2 style="font-size:14px; margin-top:35px; font-weight:bold;">PARA USO DA SUL PREVIDÊNCIA</h2>

<table style="width:100%; margin-bottom:20px;">
    <tr>
        <td style="width:120px;"> 
            <div class="light-box">Conferido em</div>
        </td>
        <td style="width:150px;">
            <div class="light-box">Visto</div>
        </td>
    </tr>
</table>

<p style="font-size:11px;">De acordo com a solicitação do proponente.</p>

<!-- LINHAS DE ASSINATURA EM DUAS COLUNAS -->
<table class="signature-table">
    <tr>
        <td class="signature-cell">
            <!-- linha de assinatura 1 -->
        </td>
        <td class="signature-cell">
            <!-- linha de assinatura 2 -->
        </td>
    </tr>
    <tr>
        <td class="signature-label">Local e Data</td>
        <td class="signature-label">Assinatura do Correntista</td>
    </tr>
</table>

<table class="signature-table" style="margin-top:25px;">
    <tr>
        <td class="signature-cell">
            <!-- linha de assinatura 3 -->
        </td>
    </tr>
    <tr>
        <td class="signature-label">Assinatura do Representante da Sul Previdência</td>
    </tr>
</table>

<!-- RODAPÉ OFICIAL -->
<div class="footer-text">
    Gestor do plano: Sociedade de Previdência Complementar Sul Previdência - CNPJ: 12.148.125/0001-42<br>
    Rua Vidal Ramos nº 31 - Sala 602 - Centro - Florianópolis - SC - CEP.: 88.010-320<br>
    www.sulprevidencia.org.br
</div>
    </div>
</body>
</html>

<style>
    .signature-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 30px;
        font-size: 12px;
    }

    .signature-cell {
        width: 50%;
        vertical-align: bottom;
        padding-bottom: 40px; /* espaço para assinatura */
        text-align: center;
    }

    .signature-label {
        font-weight: bold;
        border-top: 1px solid #000;
        padding-top: 5px;
    }

    .footer-text {
        font-size: 11px;
        text-align: center;
        margin-top: 40px;
        line-height: 1.4;
    }

    .light-box {
        background: #f2f4ff;
        border: 1px solid #d3d7f6;
        width: 160px;
        padding: 4px 6px;
        font-size: 11px;
    }

    .checkbox {
        display: inline-block;
        width: 12px;
        height: 12px;
        border: 1px solid #000;
        margin-right: 4px;
        vertical-align: middle;
        text-align: center;
        line-height: 12px;
        font-size: 12px;
        font-weight: bold;
        font-family: DejaVu Sans, sans-serif; /* garante o ✓ */
    }

    .checkbox.checked::after {
        content: "✓"; /* check seguro */
    }
</style>