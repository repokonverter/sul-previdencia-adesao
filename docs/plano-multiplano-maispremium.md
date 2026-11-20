# Plano — Multiplano (PlenoPrev + MaisPremium)

Decidido em sessão de grilling (2026-11-20). Este documento é a fonte da verdade
do que foi acordado; implementação deve seguir a ordem abaixo.

## Problema

O cliente vai lançar um segundo plano, o MaisPremium, em
`maispremium.konverter.com.br`. O formulário e as informações coletadas são os
mesmos do PlenoPrev (`plenoprev.konverter.com.br`); mudam os dados do plano
(CNPJ, CNPB, instituidor, seguradora, regulamento…) e o banco que recebe o Pix
— o MaisPremium usa **Banco do Brasil**, não Sicoob. O admin é único: quem tem
login vê as adesões dos dois planos.

Hoje tudo que é do plano está cravado no código:

- `PdfGeneratorComponent::applicationFormHtml()` — CNPJ do plano
  `48.307.525/0001-09`, instituidor CEPREV/009, códigos da seguradora
  (100350, AD2550, AM0493, F22, 01, MT 8002897), corretora Corretop.
- `templates/layout/pdf/pdf_template.php` — "PLENOPREV" no cabeçalho, rodapé
  "BFX - Proposta Plenoprev", CNPB `2011/0017-65`, Mongeral Aegon
  (CNPJ 33.608.308/0001-73, carregamento 30%), "regulamento do plano PlenoPrev".
- `templates/layout/pdf/pdf_plan_simulation.php` — "Plano PlenoPrev".
- `PdfGeneratorComponent::generatePdfApplicationForm()` — nome do arquivo
  `proposta-plenoprev-*.pdf`; prefixo `BFX-` do número da proposta.
- `PixPaymentService::generateTxid()` — prefixo `PLENOPREV` do txid.
- `templates/Pages/home.php` — select `plano` com a única opção `pleno_prev`.
- `SICOOB_*` e `APP_FULL_BASE_URL` — uma única configuração por deploy.
- `plan_parameters` — linha única.

## Arquitetura decidida

**Um deploy, um banco.** O plano da requisição pública é resolvido pelo host;
a adesão grava o plano em que nasceu e, dali em diante, tudo que diz respeito a
ela (PDF, Pix, links, e-mails) lê o plano **da adesão**, nunca o host da
requisição — webhooks e comandos rodam sem host.

## Decisões fechadas

### Resolução do plano

1. Middleware mapeia host → plano (`plenoprev`, `maispremium`).
2. `www.<host>` redireciona para `<host>`.
3. Host fora do mapa (IP, `konverter.com.br`…) → 404 nas rotas públicas.
4. Em desenvolvimento, `DEFAULT_PLAN` no `.env` define o plano de `localhost`;
   desligado em produção.
5. Link de adesão (pagamento, retomada) aberto no host do outro plano
   redireciona para o mesmo caminho no host do plano da adesão.

### Configuração por plano

6. Identidade do plano em `config/plans.php`, versionado (mudança passa por
   deploy, já que altera documento jurídico): nome, CNPJ, CNPB, instituidor
   padrão (nome e número), seguradora (nome, CNPJ, carregamento, código,
   convênio, ação de marketing, filial, alternativa, corretor), corretora,
   texto do regulamento, prefixo da proposta, prefixo do txid, host, provedor
   Pix e chave Pix. Segredos ficam em env com prefixo por plano.
7. `plan_parameters` passa a ter uma linha por plano (coluna `plan`), editável
   no admin com um formulário por plano. A procedure do simulador é a mesma;
   recebe os parâmetros do plano.
8. Sem logo por plano: site público, simulador, página de pagamento, admin e
   e-mails continuam com a logo da Sul Previdência. O nome do plano aparece
   como texto nos PDFs (cabeçalho, rodapé, regulamento, simulação) e no texto
   do e-mail de adesão. O `Plenoprev.png` não é usado em lugar nenhum.
9. Select `plano` da home passa a ser o plano do host, sem escolha.
10. Clicksign e remetente de e-mail (Resend) são compartilhados.

### Pix

11. Interface `PixGateway` (criar cobrança, consultar cobrança, configurar
    webhook) com duas implementações: `SicoobService` (PlenoPrev) e a nova
    `BancoDoBrasilService` (MaisPremium). `PixPaymentService` recebe o gateway
    do plano da adesão em vez de depender do Sicoob.
12. `pix_transactions.provider` registra o banco de cada cobrança; confirmação
    e webhook consultam o banco certo.
13. Webhook com rota e cadastro por provedor; a tela de webhooks Pix do admin
    registra o webhook de cada plano no seu banco.
14. QR code continua gerado localmente (`endroid/qr-code`); a API Pix do BB
    segue o padrão Bacen (`/cob`, `/cob/{txid}`, webhook `/pix`).
15. Txid com prefixo por plano; número da proposta com prefixo por plano.
    O `id` da adesão segue sequência única entre os planos.

### Links e URLs

16. Links de adesão (pagamento, retomada, e-mails ao participante) usam o host
    do plano da adesão.
17. Links de e-mails do admin (convite, redefinição de senha) usam host fixo
    `plenoprev.`.

### Parceiros

18. Cada parceiro pertence a um plano, escolhido no cadastro e **imutável**
    depois. Link gerado usa o host do plano. Parceiro que atua nos dois é
    cadastrado duas vezes. Listagem com coluna e filtro de plano.
19. Código promocional continua único no sistema inteiro; fora do plano do
    seu parceiro se comporta como código inexistente.

### Admin

20. `/admin` responde nos dois hosts, mesmos usuários; sessão é por host.
    Todos os usuários veem os dois planos (sem restrição por papel).
21. Listagem/busca de adesões com coluna "Plano" e filtro (padrão "Todos");
    dashboard com totais por plano.
22. Simulação do admin tem seletor de plano — muda os parâmetros do cálculo e
    o texto do PDF. O PDF de simulação a partir de uma adesão
    (`AdhesionsController::planPdf`) usa o plano da adesão.
23. Cadastro manual de adesão no admin tem seletor de plano; o plano da adesão
    é **imutável** depois de criada (Pix, Clicksign e parceiro já ficam
    ligados a ele). Plano errado → exclusão auditada e refazer.

### Dados

24. Migration marca tudo que existe como `plenoprev` (adesões, parceiros,
    `plan_parameters`) e `provider = sicoob` (`pix_transactions`); colunas
    `NOT NULL` ao final.
25. Sem snapshot dos dados do plano: PDF regerado usa o `config/plans.php`
    atual (comportamento de hoje). O documento assinado fica na Clicksign.
26. Sem restrição de CPF entre planos: a mesma pessoa pode aderir aos dois.

## Ordem de implementação

1. **Refatoração multiplano só com o PlenoPrev** _(implementada no branch
   `feat/multiplano`; a coluna se chama `benefit_plan`, para não confundir
   com `adhesion_plans`)_ — coluna `plan`,
   `config/plans.php`, middleware de host, `PixGateway` com Sicoob, admin com
   filtros/seletores, migration de dados. Vai para produção sem nenhuma
   mudança visível.
2. **Integração Banco do Brasil** — `BancoDoBrasilService` contra o sandbox do
   BB, em paralelo.
3. **Go-live do MaisPremium** — configuração do plano, DNS de
   `maispremium.konverter.com.br` para o mesmo servidor, host no proxy do
   Kamal (`config/deploy.yml`), secrets do BB. Só abre ao público depois do Pix
   do BB validado em produção — não existe modo "sem pagamento".

## Pendências com o cliente

- CNPJ e CNPB do MaisPremium
- Instituidor padrão (nome e número)
- Seguradora, CNPJ e carregamento; códigos da seguradora e corretor
- Corretora
- Texto do regulamento
- Prefixo da proposta (o que significa "BFX"?)
- Chave Pix da conta BB
- Parâmetros iniciais (contribuição mínima, % dos riscos, pisos)
- Credenciais BB Developers: client_id, client_secret, `gw-dev-app-key`,
  certificado para mTLS em produção, API Pix liberada na conta
