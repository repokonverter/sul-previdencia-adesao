# Plano — Etapas do formulário, edição no admin e retomada de proposta

Decidido em sessão de grilling (2026-09-24). Este documento é a fonte da verdade
do que foi acordado; implementação deve seguir a ordem abaixo.

## Pedido do cliente

1. Mover "Plano" para depois de "Regime de previdência", e "Declarações do
   proponente" para depois de "Plano" — esta última não aparecendo quando o
   risco for removido.
2. Editar proposta no admin, escolher a etapa em que o cliente continua, e
   enviar o link para ele retomar. O id do link tem que ser um hash.
3. Mostrar no admin se o cliente assinou na Clicksign, com opção de baixar os
   contratos assinados (verificar possibilidade na API deles).
4. Remover o código do corretor da etapa; os ajustes de risco e de valores
   passam a ser feitos no admin, gravando quem alterou. Mínimo de R$ 100,00 de
   contribuição, sendo R$ 16,00 de morte e R$ 10,00 de invalidez, configurável
   em vez de hardcoded.

## Interpretações que divergem do pedido

Cinco pontos onde o acordado reinterpreta o enunciado. **Levar ao cliente antes
de considerar o escopo fechado.**

1. **Os R$ 16,00 e R$ 10,00 não são valores fixos: são 16% e 10%** da
   contribuição, hardcoded na procedure
   (`20260921010000_ParameterizeSimulatorFunctionRisks.php:76-78`). No mínimo de
   R$ 100 dão exatamente R$ 16 e R$ 10. Enquanto os riscos forem percentuais,
   pisos absolutos são inalcançáveis — qualquer total ≥ R$ 100 já os satisfaz.
   O pedido só é coerente se os pisos limitarem **o quanto o admin pode baixar
   os valores à mão**, e é essa a leitura adotada. Se o cliente quis outra
   coisa, é o ponto que muda mais código.
2. **A DPS só desaparece quando os dois riscos são removidos** (`&&`), não
   quando um deles sai. O cliente escreveu no singular. As oito perguntas
   subscrevem morte *e* invalidez: esconder a declaração com um risco vivo
   deixaria exposição não subscrita num contrato assinado.
3. **O item 1 só produz efeito visível junto com o item 2.** Com o corretor
   fora do modal, o fluxo público não tem mais como remover risco; uma adesão
   nova nasce com os dois. A DPS só consegue ser pulada numa proposta
   **retomada**, depois de o admin ter removido ambos. Os dois itens precisam
   entrar juntos.
4. **Adesão assinada ou paga não terá valores editáveis.** O cliente não pediu
   restrição, mas editar valor depois da assinatura faz o registro divergir do
   documento assinado, e depois do pagamento é evento contábil, não cadastro.
5. **Reassinatura gera envelope novo.** A Clicksign só permite `DELETE` de
   documento em status `draft`, então refinalizar exige cancelar o envelope e
   criar outro. O cliente verá múltiplos envelopes por adesão na conta dele.

## Decisões fechadas

### Item 1 — ordem e condicionalidade das etapas

Nova ordem (`registerPages`, `templates/Simulator/index.php:1841`):

```
initialData → personalData → documents → dependents → addressData
→ otherInformation → pensionScheme → plan → proponentStatement*
→ paymentDetail → conclusion
```

`* proponentStatement` só aparece se houver ao menos um risco contratado.

- "Beneficiário(s)" sobe para a 4ª posição, efeito colateral aceito de o Plano
  descer.
- A condicionalidade lê **estado do servidor** (`adhesion_plans.has_survivors_pension`
  / `has_disability_retirement`), nunca o DOM.
- **Navegação refatorada de índices para ids.** Hoje os passos são números
  mágicos em ~20 lugares (`case 0..10` em `updatePage()` e
  `updateButtonPreviousNext()`, `=== 4` / `=== 6` / `=== 8` nas validações de
  `nextPage()`, `=== 7` no pulo da DPS em `nextPage()` e `previousPage()`,
  `!== 10` no botão do faker). Cada entrada de `registerPages` passa a ter
  `id`, `title`, `visible()`, `validate()` e `onEnter()`; a navegação opera
  sobre a lista de passos **visíveis**, calculada na hora. Pré-requisito: com
  passo condicional os índices deixam de ser contíguos, e o link de retomada
  precisa guardar `'plan'`, não `7`.

### Item 2 — edição no admin e link de retomada

- **`resume_token`** em `adhesion_initial_data`: `bin2hex(random_bytes(32))`,
  índice único, gerado **no servidor**. Token aleatório, **não** hash do id —
  hash derivado do id torna a base enumerável se o algoritmo vazar, e ids
  sequenciais tornam trivial gerar candidatos.
- `resume_token_expires_at`, prazo de **7 dias** vindo de `plan_parameters`.
  Rotaciona **a cada geração** (não a cada envio, para que o mesmo link possa
  ir por vários canais). Revogável pelo admin. Link expirado mostra página
  "este link expirou, fale com seu atendente", nunca 404.
- Rota nova `/proposta/{resumeToken}`, no padrão do `/pagamento/{storageUuid}`
  que já existe (`config/routes.php:67`).
- A rota de retomada **não tem `date`/`value` na query**;
  `SimulatorController::index()` redireciona para a home se `value < 100`
  (linha 27). Na retomada, `date` vem de `birth_date` e `value` da contribuição
  gravada.
- **Repopulação:** blob JSON renderizado no servidor + preenchedor genérico em
  JS que dispara `change`/`click` em cada campo — obrigatório, porque muitos
  campos têm handler que mostra/esconde seção dependente (`showHide()`,
  `planForHandle`, `pensionSchema`). Molde:
  `fillStepWithFakeData()` (`templates/Simulator/index.php:2780`), que já faz
  isso certo. Dependentes num laço chamando `addDependent()` antes de
  preencher.
- **Mapeamento de campos extraído para um lugar só** (ex. `AdhesionFormMap`),
  usado pelo `save()` e pelo apresentador do blob. Hoje os ~130 mapeamentos
  (`personalData[birthDate]` → `birth_date`) existem só dentro dos
  `patchEntity()` do `save()`, numa direção. Duas listas paralelas fariam a
  retomada perder campos em silêncio quando alguém adicionasse uma coluna.
  Teste de ida-e-volta obrigatório.
- **Etapa escolhida é ponto de partida, não trava.** O cliente navega para trás
  livremente; o que não pode mudar já é protegido no servidor (código
  promocional e vínculo travam na primeira atribuição,
  `RegistrationsController.php:116`).
- **Seletor do admin só oferece etapas cujas anteriores estão completas**,
  calculado a partir das linhas associadas existentes, com a primeira
  incompleta pré-selecionada. `nextPage()` valida só a etapa corrente, então
  uma lacuna anterior passaria batido e finalizaria a adesão sem endereço ou
  sem beneficiários.
- **Envio:** copiar link + enviar por e-mail + abrir no WhatsApp
  (`https://wa.me/55<telefone>?text=…`, abre o WhatsApp do próprio admin, sem
  API nem credencial). Os três eventos auditados. E-mail vem preenchido com o
  endereço da adesão, editável, **sem** sobrescrever o cadastro. Template de
  e-mail próprio, informando o prazo de validade.
- **Botão "Atualizar" no passo Plano** + busca automática do estado do servidor
  ao entrar nele, para o caso do cliente ao telefone com o admin. Mesmo
  mecanismo da repopulação, outra porta de entrada.

### Item 3 — status de assinatura e download

- **Webhook como gatilho, `GET` como única fonte de verdade**, espelhando o
  padrão já estabelecido para o Sicoob (`WebhooksController.php:27-29`:
  *"O payload nunca é tratado como fonte de verdade"*), com botão manual no
  admin como alternativa, à semelhança de `checkPixPayment()`. Rota
  `/clicksign/webhook/{token}` com token no path e tabela de tokens, como
  `pix_webhooks`.
- `metadata` dos documentos carrega o id da adesão — a doc diz que metadata é
  enviada de volta nos webhooks, então o webhook chega resolvido sem depender
  de busca por `envelope_id`.
- **O PDF assinado não é armazenado.** Buscado na Clicksign a cada download. A
  contrapartida aceita: se a conta na Clicksign for encerrada ou o documento
  apagado lá, não existe segunda cópia.
- **Download por proxy**, nunca redirect. A URL do S3 é pré-assinada com
  `X-Amz-Expires=299` (~5 min) e **não exige autenticação**: em redirect ela
  entraria no histórico do navegador, em logs de proxy e no `Referer`, dando a
  qualquer portador acesso a um contrato com CPF, dados bancários e DPS. Em
  proxy o único portão continua sendo a sessão do admin, e o download é
  auditável. Falha da Clicksign vira mensagem explícita, nunca 500.
- Admin: badge de status na listagem (`Pendente` / `Assinado` / `Cancelado` /
  `Falhou`) com data, documentos do envelope corrente com download por
  documento, e histórico das tentativas anteriores.

### Item 4 — corretor, valores e parâmetros

- **Corretor sai do modal.** O input visível e os checkboxes de remoção de
  risco desaparecem; a captura silenciosa por `?broker=CODIGO` permanece
  (campo oculto, revalidado no servidor). O admin ganha um select de corretor
  na edição da adesão. Desacoplar risco de corretor **melhora a segurança**:
  hoje quem descobre um código de corretor válido remove riscos na própria
  adesão pelo navegador; passa a exigir login de admin.
- O corretor da adesão **não** substitui o corretor hardcoded do PDF
  (`PdfGeneratorComponent.php:53-56`, "Corretop"). São coisas diferentes.
- **`plan_parameters`**, tabela tipada de linha única com tela no admin:
  mínimo total (R$ 100), taxa de morte (16%), taxa de invalidez (10%), piso de
  morte (R$ 16), piso de invalidez (R$ 10), prazo do `resume_token`. Tabela
  tipada em vez de chave/valor genérica: valores monetários e atuariais em
  coluna `decimal` com validação, não strings cuja unidade cada consumidor
  adivinha.
- **Pisos = limite do override manual do admin**, aplicados só a risco
  contratado. Risco removido não tem piso. O mínimo de R$ 100 vale com ou sem
  risco: sem risco, os R$ 100 inteiros vão para aposentadoria.
- **Capital segurado sempre recalculado pela procedure** a partir do valor de
  risco, com a tabela de custo por idade e os tetos de R$ 1.700.000. O admin
  não digita capital — senão seria possível vender R$ 500.000 de cobertura por
  R$ 16/mês.
- **Procedure colapsada para 4 parâmetros numéricos:**

  ```sql
  simulacao_previdencia(p_data_nascimento date, p_contribuicao_mensal numeric,
                        p_contribuicao_morte numeric, p_contribuicao_invalidez numeric)
  ```

  Os booleanos `p_incluir_morte` / `p_incluir_invalidez` saem. O PHP decide o
  valor de cada risco (`0` se removido, override do admin se houver, senão
  `total × taxa`) e a procedure fica sendo calculadora atuarial pura. Uma
  fonte de verdade para as taxas, zero regra de negócio em PL/pgSQL.
- **`save()` preserva flags e valores em vez de recalculá-los.** Hoje
  `RegistrationsController.php:225-233` recalcula
  `has_survivors_pension`/`has_disability_retirement` a cada submit do passo
  Plano, a partir de `broker_id`. Com o corretor desacoplado isso
  **ressuscitaria os riscos que o admin acabou de remover** — o payload não
  traz mais `removeSurvivorsPension`, então tudo voltaria a `true`, apagando o
  override e reexigindo a DPS. As flags e os valores com override passam a ser
  propriedade exclusiva do admin, fora da lista de campos do `patchEntity` do
  passo Plano. Mesma filosofia já escrita nos comentários do código para o
  código promocional e o vínculo: a tela é conveniência, o POST é forjável.
- **Override do admin ⇒ passo Plano somente-leitura para o cliente**, com
  aviso. Sem isso, um clique em "Recalcular" roda a fórmula de novo e apaga a
  negociação feita por telefone. `personalData[birthDate]` **também** trava:
  os valores foram calculados para a idade daquele momento, e mudar a data
  tornaria o capital atuarialmente impossível (a procedure escolhe custo
  unitário e teto pela idade).
- **`adhesion_audits`**, tabela dedicada (adesão, usuário, ação, diff JSON,
  data), aba "Histórico" própria. Não reaproveitar `integration_logs`: uma
  adesão finalizada gera 15-20 linhas lá, cada uma com request/response
  completos de Clicksign e Sicoob, e "quem baixou a contribuição de R$ 300
  para R$ 250" ficaria inencontrável. Auditar **toda** edição no admin com
  diff (`getDirty()` / `getOriginalValues()`), não só o plano: o
  `AdhesionsController::edit()` já permite reescrever CPF, data de nascimento,
  conta bancária e beneficiários sem rastro nenhum, e essas são perguntas mais
  graves que a do valor. Mais as colunas de estado corrente em
  `adhesion_plans`: `admin_overridden`, `admin_overridden_by_user_id`,
  `admin_overridden_at`.
- **A edição não dispara nada automaticamente.** Salvar grava, audita e para
  aí — não regenera PDF, não mexe no envelope, não cancela cobrança. Cascata
  automática faria um erro de digitação disparar envelope novo e e-mail ao
  cliente. Em vez disso o sistema **detecta a divergência e avisa**
  (comparando `admin_overridden_at` com a data dos documentos e da cobrança),
  com botões explícitos "Regerar documentos e reenviar para assinatura" e
  "Regerar cobrança Pix", ambos auditados.
- **Gating por estado.** Rascunho e finalizada-não-assinada: valores
  editáveis. Assinada ou paga: valores, riscos, beneficiários e conta bancária
  bloqueados, liberados só por uma ação explícita "Reabrir proposta". Dados
  cadastrais (endereço, telefone, e-mail, nome da mãe) seguem editáveis em
  qualquer estado — o que trava é o conteúdo econômico do contrato.
- **`clicksign_data` vira hasMany com `attempt`**, espelhando `pix_transactions`
  (padrão já usado em `AdhesionsController::index():51`). Refinalizar cancela
  o envelope corrente e cria outro; se o envelope já estiver `closed` ele não
  pode ser cancelado — e não deve, fica como prova histórica, superseded pelo
  novo. Sem isso, criar envelope novo sobrescreveria `envelope_id` e perderia
  o rastro do contrato assinado, que é justamente o que o item 3 quer baixar.
- **Migrations novas, com `DROP FUNCTION` explícito** das assinaturas antigas.
  Nas duas migrations existentes o `DROP FUNCTION` está comentado
  (`20251105091907:20`, `20260921010000:24`), e no Postgres
  `CREATE OR REPLACE` com assinatura diferente cria **sobrecarga**, não
  substituição. Já devem coexistir a versão de 2 e a de 4 parâmetros; como os
  params 3 e 4 têm `DEFAULT`, uma chamada com 2 argumentos é **ambígua**
  (`function … is not unique`). Só não explode porque a aplicação sempre chama
  com 4. A versão nova seria a terceira sobrecarga.

## Bugs encontrados fora do pedido

Todos confirmados no código durante a sessão.

1. **IDOR em `RegistrationsController::save()`** (linha 85). `initialDataId` vem
   do POST e vai direto para `->get()`; `storage_uuid` é apenas **escrito**
   (linha 111), nunca conferido. Qualquer visitante sobrescreve a adesão de
   outra pessoa postando `initialDataId=123`.
2. **Colisão de `storage_uuid`.** Sem índice único. `draftUUID` vem do
   `localStorage` e é reutilizado, mas `initialDataId` é variável JS que volta
   a `null` a cada recarregamento — então uma segunda adesão no mesmo navegador
   nasce com o **mesmo** uuid. `PaymentsController::view()` resolve com
   `->first()` **sem `orderBy`** (linha 22-26): qual das duas aparece é
   indeterminado, e alguém pode pagar a cobrança da adesão errada.
3. **Fallback fraco do uuid** (`templates/Simulator/index.php:1892`):
   `Math.random()` + `Date.now()` quando `crypto.randomUUID` não existe —
   adivinhável, e hoje já é o segredo da página de pagamento.
4. **`deleteDocument` apaga 1 de 2** (`RegistrationsController.php:480`):
   `$responseGetDocuments['data'][0]['id']`, e sobe dois documentos em
   seguida. Um envelope que já tinha os dois PDFs termina com três, um
   desatualizado, e o cliente assina o pacote inteiro.
5. **`deleteDocument` num envelope `running` vai falhar.** A API só permite
   `DELETE` de documento em `draft`. Hoje é latente porque ninguém refinaliza;
   o item 2 torna esse o caminho normal.
6. **Cobrança Pix nunca é recriada quando o valor muda.**
   `PixPaymentService::resolve()` (linhas 36-65) devolve a cobrança existente
   se estiver `ATIVA`, **sem comparar `$amount`**. Mudar a contribuição de
   R$ 300 para R$ 250 deixa o cliente com o QR code de R$ 300 para sempre.
7. **Sobrecargas ambíguas da procedure** — ver último bullet do item 4.
8. **DPS nula imprime "Não"** (`templates/layout/pdf/pdf_template.php:503` e
   seguintes): `$statement && $statement->health_problem ? … : 'Não'`. Hoje é
   inofensivo porque sem DPS não há risco e a seção não aparece. O item 4 dá
   ao admin um checkbox de risco, e checkbox também **marca**: religar um
   risco numa adesão sem DPS faria o PDF imprimir "Não" nas oito perguntas —
   o cliente assinaria uma Declaração Pessoal de Saúde negando cardiopatia,
   câncer, HIV e cirurgias que nunca respondeu. O próprio template diz "nunca
   deve ser assinada em branco"; seria pior que em branco, porque parece
   legítima. Correções: `$statement === null` imprime `NÃO DECLARADO`, nunca
   `Não`; e o admin **não consegue religar um risco sem DPS no arquivo** — o
   caminho é o link de retomada apontando para a DPS, ou o admin preenchê-la
   na tela de edição, com registro de quem preencheu. DPS já preenchida
   **nunca** é apagada quando os riscos saem: o PDF já a omite sozinho
   (linha 487), e se o risco voltar os dados voltam com ele.

## Ordem de execução

1. **Fundação** — refatoração dos passos para ids + reordenação (item 1) +
   correções do `storage_uuid` (bugs 1, 2, 3). Comportamento preservado: o
   pulo da DPS continua funcionando pelo campo do corretor, agora expresso
   como `visible()`.
2. **Parâmetros** — `plan_parameters` + tela no admin + procedure colapsada +
   `DROP` das sobrecargas (bug 7) + PHP calcula os valores de risco + teste da
   procedure reescrito.
3. **Auditoria** — `adhesion_audits` + diff de toda edição no admin + colunas
   `admin_overridden*`.
4. **Item 4** — corretor sai do modal + edição de riscos/valores no admin com
   pisos + `save()` preserva + travas do passo Plano e da data de nascimento +
   integridade da DPS (bug 8) + gating de estado.
5. **Item 2** — extração do mapeamento + repopulação + `resume_token` + rota +
   seletor de etapa + envio + botão "Atualizar" + aviso de divergência +
   botões de regerar + `clicksign_data` hasMany + cancelar/recriar envelope
   (bugs 4, 5, 6).
6. **Item 3** — webhook Clicksign + status + download por proxy. Independente
   das demais; pode sair em paralelo.

Depois, em entrega separada: tirar do hardcode os 15 valores institucionais do
PDF (`PdfGeneratorComponent.php:41-63`, todos em uso nos dois templates) para
uma tabela `institution_parameters` com tela no admin — **com snapshot por
adesão**, seguindo o precedente do `association_snapshot`
(`20260921000000:26-29`: *"para que o admin sempre regenere o PDF que foi de
fato assinado, mesmo que o cadastro mude depois"*). Sem snapshot, trocar a
corretora na tela faria o sistema emitir, sob demanda, versões divergentes de
contratos já assinados. Nenhum dos quatro itens do cliente pediu isso.

## Ajustes durante a implementação

Registrados aqui para o documento continuar sendo a fonte da verdade.

- **A DPS tem onze perguntas, não oito.** E "DECLARAÇÕES DO PROPONENTE" é
  título de três seções diferentes da proposta: o questionário de saúde, o
  aviso de "não se aplica" quando não há risco, e uma declaração jurídica
  sobre estatuto e veracidade que existe sempre. Só a primeira é sobre saúde.
- **O gating de adesão assinada saiu da entrega 4 para a 6.** "Assinada" ainda
  não é um estado que o sistema conheça: `clicksign_data.status` só guarda
  `pending`/`sent`/`failed`. A entrega 4 trava o que é conhecível hoje (Pix
  pago); a condição de assinatura entra em `economicallyLocked()` junto com o
  acompanhamento de assinatura. A ação "Reabrir proposta" vai com ela, já que
  depende de cancelar envelope, que é maquinária da entrega 5.
- **O prazo do `resume_token` não entrou em `plan_parameters` na entrega 2.** Um
  parâmetro configurável que não controla nada por três entregas confunde quem
  usar o admin nesse meio-tempo; ele entra na entrega 5, junto do token.
- **A trilha de exclusão virou tabela própria** (`adhesion_deletions`), decidido
  depois da entrega 3: a FK de `adhesion_audits` é CASCADE, então a linha que
  registraria a exclusão seria apagada por ela.
- **`updated` era NULL em toda tabela do projeto** — o behavior Timestamp grava
  `modified`, que não existe aqui. Corrigido para o projeto inteiro com uma
  classe base de tabela (`AppTable`), para que tabela nova acerte sozinha.
- **`Migrator::run()` trunca as tabelas depois de migrar**, então dado semeado
  em migration não chega ao banco de teste. Daí a fixture de `plan_parameters`.
- **"Reabrir proposta" nunca destrava adesão paga**, embora a resposta original
  da pergunta 10 tenha dito "adesão assinada ou paga" com a mesma ação para as
  duas. Na hora de implementar, tratar as duas igual significaria "invalidar a
  cobrança" sem nenhum processo de conciliação por trás -- dinheiro já
  recebido não é destravado por um clique. A ação só existe para o lado da
  assinatura; adesão paga fica bloqueada sem saída pela tela, remetendo a um
  processo manual com o Sicoob.
- **`ClicksignService` tinha um bug real de produção**: qualquer `DELETE`
  bem-sucedido responde 204 sem corpo, e o spread de `null` na resposta
  quebrava com `TypeError` -- toda chamada de exclusão estava crashando do
  lado do cliente mesmo quando funcionava do lado da Clicksign. Achado ao
  validar o CRUD de webhook contra o sandbox real; corrigido, e um envelope
  órfão deixado pelo crash foi limpo manualmente.
- **Terceira ocorrência do mesmo bug de templates com função de topo**
  (`Admin/Adhesions/index.php`), depois de `Simulator/index.php` e
  `Admin/Adhesions/view.php`. Convertido preventivamente para closures. Achado
  mais dois casos do mesmo padrão (`Admin/IntegrationLogs/index.php`,
  `Admin/Partners/view.php`) que ficaram sem mexer, por nada nesta entrega os
  renderizar duas vezes no mesmo processo -- registrado aqui para quem for
  mexer neles depois.

## Testes

Cobrir as falhas **silenciosas** — as que não dão erro, só produzem dado errado:

1. Ida-e-volta do mapeamento de campos (payload → `save()` → apresenta →
   payload idêntico).
2. `save()` preserva flags e valores. Os três testes existentes de
   `RegistrationsControllerSaveTest` são invalidados pelo item 4;
   `testForgedRiskFlagsWithoutBrokerAreIgnored` fica **mais forte** (flags
   forjadas são ignoradas sempre, sem depender de corretor).
3. Pisos de R$ 16 / R$ 10 rejeitados no admin, e só para risco contratado.
4. Procedure reescrita: valores de risco entram, capital/saldo/benefício saem,
   sobrecargas antigas ausentes.
5. `resume_token`: expirado, revogado, inexistente, e de outra adesão.
6. Religar risco sem DPS é bloqueado; PDF imprime `NÃO DECLARADO` com
   `$statement` nulo.

Fora de escopo: testes de integração de Clicksign e Sicoob (exigiriam mocking
de `Cake\Http\Client`, que não existe na suíte) e testes de navegação em JS
(não há runner de JS no projeto).

## Confirmado contra o sandbox na entrega 6

A dúvida ficou resolvida com uma sonda real (não só leitura de doc): um
envelope fechado real no sandbox tem, em `links.files`, as três chaves
`original`, **`signed`** e `ziped` -- todas URLs pré-assinadas do S3 com
`X-Amz-Expires=299` (~5 min), como a doc de `running` já sugeria. O download
usa `signed`. Confirmado também que `getEnvelope()` devolve `status: "closed"`
para um envelope assim, e que `metadata` passada em `createDocument()`
sobrevive exatamente igual numa releitura (`createDocument` → `getDocuments`),
o que sustenta metadata como o mecanismo de resolver a adesão a partir do
documento.

**O que continua sem confirmar:** o formato exato do corpo que a Clicksign
entrega nos eventos `close` e `document_closed` em si. A doc só mostra um
esqueleto de dois campos (`account`, `document`) sem o conteúdo completo, e
confirmar exigiria uma URL pública recebendo o evento de verdade, que este
ambiente não tem como oferecer. `ClicksignWebhookPayload::resolveAdhesionId()`
tenta os caminhos mais plausíveis dado o padrão JSON:API do resto da API; se
nenhum bater, o webhook simplesmente não resolve sozinho -- o botão
"Atualizar status" no admin continua funcionando porque parte do
`envelope_id` já gravado, sem depender de adivinhar esse JSON.

Outros fatos da API confirmados: status do envelope é
`draft → running → closed | canceled`; `auto_close: true` (já usado em
`createEnvelope()`) fecha o envelope quando o último assinante conclui;
`POST /webhooks` aceita `endpoint`, `events` (array) e `status`, devolvendo um
`secret` gerado pela Clicksign (não usado aqui -- ver decisão abaixo); `close`
e `document_closed` foram aceitos como eventos válidos pela API real; rate
limit de 50 req/10s por conta em produção, 20 em sandbox.

**Decisão tomada durante a implementação, não antecipada na entrevista:** o
webhook não verifica o HMAC que a Clicksign oferece (campo `secret` na
criação). O motivo é que a arquitetura já decidida -- payload nunca é fonte de
verdade, só gatilho para uma consulta GET própria -- já torna o pior caso de um
POST forjado inofensivo: na pior hipótese, alguém que descobrisse a URL
dispararia uma reconferência (GET autenticado com nossas próprias
credenciais) de uma adesão que ela mesma escolhesse, sem conseguir alterar
nada que a consulta não confirme de verdade. Adicionar HMAC teria custo
real (guardar o segredo, implementar a verificação) para fechar um risco que
a própria arquitetura já neutraliza.
