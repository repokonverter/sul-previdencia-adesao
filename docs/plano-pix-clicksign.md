# Plano — Robustez do fluxo PIX / Clicksign

Decidido em sessão de grilling (2026-08-05). Este documento é a fonte da verdade
do que foi acordado; implementação deve seguir a ordem abaixo.

## Problema

O PIX é gerado dentro da requisição final do formulário de adesão e existe
apenas na resposta AJAX + `txid` no banco. O brcode (copia-e-cola) nunca é
persistido. Qualquer interrupção nesse instante — timeout, aba fechada, o
próprio botão "Fechar" que chama `window.location.reload()` — perde o PIX de
forma irrecuperável, inclusive para o suporte reenviar.

Agravante: o `$connection->commit()` em `RegistrationsController::save()`
acontece **depois** do bloco Clicksign. Se qualquer uma das ~9 chamadas ao
Clicksign falhar, a adesão inteira é descartada via `rollBack()` — o cliente
perde dez passos de formulário por causa de uma falha de terceiro.

Diagnóstico em produção (2026-08-05): 34 adesões iniciadas, 18 finalizadas,
27 cobranças PIX criadas (evidência de corrida/duplicação em 3 adesões de
teste), 0 marcadas como pagas. Todas as 18 adesões finalizadas eram testes
internos — nenhum cliente real pagou ou perdeu dinheiro. Base atual pode ser
ignorada/limpa; não é necessário backfill de dados legados.

## Arquitetura decidida

A cobrança PIX deixa de nascer dentro da requisição do formulário e passa a
nascer **sob demanda**, na primeira abertura da página de pagamento. Um único
mecanismo — _consultar o Sicoob (`GET /cob/{txid}`) e agir pelo status real_
— atende quatro entradas:

1. Cliente abre a página de pagamento
2. Webhook do Sicoob (usado apenas como gatilho, nunca como fonte de verdade)
3. Botão "verificar pagamento" no admin
4. Regeneração automática de cobrança expirada

O Sicoob é sempre a fonte da verdade; nada é inferido apenas do estado local.

## Decisões fechadas

| Área                          | Decisão                                                                                                                                                                                                                                                                                                                                                                 |
| ----------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Entrega ao cliente            | Página própria `/pagamento/{storage_uuid}` + e-mail automático com o link                                                                                                                                                                                                                                                                                               |
| Criação da cobrança           | Lazy — só na primeira abertura da página de pagamento                                                                                                                                                                                                                                                                                                                   |
| Lógica ao abrir a página      | `GET /cob/{txid}`: `CONCLUIDA` → mostra pago; `ATIVA` → mostra brcode fresco (vindo da resposta, não do cache); expirada/removida → gera nova cobrança                                                                                                                                                                                                                  |
| Expiração da cobrança         | 24h (`calendario.expiracao = 86400`)                                                                                                                                                                                                                                                                                                                                    |
| Geração do txid               | Nosso, via `PUT /cob/{txid}` (não mais `POST /cob`), formato legível: prefixo + id da adesão + nº da tentativa + preenchimento até 26–35 chars. Determinístico por (adesão, tentativa) — evita duplicidade mesmo com abas concorrentes                                                                                                                                  |
| Ordem da transação            | Commit dos dados da adesão **antes** do bloco Clicksign. Clicksign passa a rodar em request separada, disparada pela página de pagamento                                                                                                                                                                                                                                |
| Rastreio Clicksign            | `clicksign_data` ganha status (pending/sent/failed), contador de tentativas, último erro                                                                                                                                                                                                                                                                                |
| Falha do Clicksign            | Não derruba a adesão. Notifica por e-mail os usuários cadastrados no admin (tabela `users`)                                                                                                                                                                                                                                                                             |
| Webhook Sicoob                | Apenas gatilho — ao receber, confirma via `GET /cob/{txid}` antes de marcar qualquer coisa. Token de segurança **no path**, não em query string: `/sicoob/webhook/{token}/pix` — o Sicoob acrescenta `/pix` ao final da URL cadastrada, o que quebraria um token em query string                                                                                        |
| Admin                         | Tela de CRUD de webhooks cadastrados no Sicoob + botão manual "verificar pagamento" por adesão (mesma lógica do item acima, sob demanda)                                                                                                                                                                                                                                |
| Provedor de e-mail            | Resend                                                                                                                                                                                                                                                                                                                                                                  |
| Remetente                     | `plenoprev@konverter.com.br` — **provisório**, definido só em env var (endereço, nome de exibição, credenciais). Trocar deve ser mudança de config, não de código. Cliente ainda vai definir o domínio definitivo                                                                                                                                                       |
| `App.fullBaseUrl`             | Precisa ser configurado explicitamente — e-mail não tem request HTTP para inferir domínio, os links quebrariam sem isso                                                                                                                                                                                                                                                 |
| Visual da página de pagamento | Herda a identidade visual do simulador (`templates/Simulator/index.php`): `--primary-color: #FF6B00`, texto `#333`, Arial, Bootstrap 5, card branco `border-radius: 24px`, `box-shadow: 0 4px 32px rgba(0,0,0,.10)`, barra superior 10px na cor primária, `max-width: 950px`, logo `logo_sul_transparente.png`. **Não** é o layout enterprise — isso é só para o e-mail |
| Visual do e-mail              | Layout "enterprise" — a ser desenhado, com o mesmo remetente/branding provisório acima                                                                                                                                                                                                                                                                                  |
| Backfill de dados antigos     | Não necessário — base atual é só teste                                                                                                                                                                                                                                                                                                                                  |

## Ordem de execução

1. ✅ **Commit antes do Clicksign** + status/retry em `clicksign_data` + alerta por e-mail ao admin em caso de falha
2. ✅ **Schema + txid próprio + criação lazy + página de pagamento** (o núcleo — resolve a causa raiz da reclamação)
3. ✅ **Resend + e-mail automático ao cliente** com o link de pagamento
4. ✅ **Webhook + CRUD no admin + botão de verificação manual**

Todos os 4 itens implementados em 2026-08-05/06. Arquivos principais:
`PixPaymentService`, `PaymentsController`, `WebhooksController`,
`Admin/PixWebhooksController`, `ResendService`, `EmailTemplates`,
`SicoobService::fromConfigure()`.

### Achado durante a implementação: falha de autenticação em produção

Ao implementar o botão "verificar pagamento" no admin, encontramos que
`Admin/AdhesionsController` importava `App\Controller\AppController` (o
controller base público) em vez de `App\Controller\Admin\AppController`,
nunca carregando o componente de autenticação. Resultado: `/admin/adhesions/*`
— CPF, endereço, dependentes, dados bancários, PDFs — ficou **publicamente
acessível sem login**, tanto localmente quanto (com alta probabilidade) em
produção, desde que o controller existe. Corrigido nesta sessão (troca de uma
linha de `use`); verificado via teste manual (200 → 302 após a correção).
Recomendado: deploy dessa correção com prioridade separada do resto, e avaliar
se há logs de acesso no servidor para checar se os dados foram acessados
enquanto a falha esteve ativa.

## Pendências resolvidas em 2026-08-06 (sessão de grilling)

1. ✅ **`DEBUG: true` em produção.** `config/deploy.yml` agora define
   `DEBUG: false`. Como a rastreabilidade passou a vir da tabela
   `integration_logs` e de stdout (item 2), não há mais motivo para expor
   stack trace no navegador do cliente.
2. ✅ **Logs efêmeros.** Novo env `LOG_TO_STDOUT` (ligado em produção via
   `deploy.yml`) troca os engines `debug`/`error` de `FileLog` (arquivo em
   `logs/`, apagado a cada `kamal deploy`) para `ConsoleLog` em
   stdout/stderr, que o Docker retém e `kamal app logs` lê.
3. ✅ **Logs completos de integração.** Nova tabela `integration_logs`
   (migration `20260806000000_CreateIntegrationLogs`), FK anulável para
   `adhesion_initial_data` (`ON DELETE CASCADE`). `App\Services\IntegrationLogger`
   é o único ponto de escrita: `logHttp()` para chamadas HTTP (sucesso e
   falha), `logEvent()` para marcos internos. Cobre:
   - **Sicoob** — `SicoobService::request()` e `_performAuthRequest()`.
   - **Clicksign** — `ClicksignService::_request()` (ponto único usado pelos
     ~24 métodos públicos do serviço).
   - **Resend** — `ResendService::send()`.
   - **Webhook Sicoob recebido** — `WebhooksController::pix()`, direção
     `inbound`, com o token do path redigido antes de persistir a URL.
   - **Marcos internos** — `adhesion.finalized`, `application.pdfs_generated`,
     `adhesion.save_failed`, `payment_page.opened`, `pix.charge_created`,
     `pix.charge_regenerated`, `pix.payment_confirmed`.

   O rótulo de operação (`sicoob.get_cob`, `clicksign.create_envelope`, ...)
   é derivado automaticamente do nome do método chamador via
   `debug_backtrace()`, evitando editar cada um dos ~20-30 call sites.
   Associação com a adesão é por contexto explícito: os três serviços
   ganharam `forAdhesion(?int $id)`, chamado antes de cada operação (direto
   em `RegistrationsController`, ou propagado por `PixPaymentService` a
   partir do `adhesion_initial_data_id` já resolvido).

   **Redação:** corpos são serializados como JSON; chaves sensíveis
   (`content_base64`, `authorization`, `access_token`, etc.) viram
   `[REDACTED]` ou `[REDACTED, X KB]` para valores grandes. CPF e dados
   bancários **não** são redigidos — já existem em texto na própria adesão e
   são o que se precisa conferir ao depurar. Corpos são truncados em 8 KB
   (`…[truncado]`). Headers (onde vive `Authorization`) nunca são
   persistidos — só request/response bodies.

   Falha ao gravar um log nunca derruba o fluxo: `IntegrationLogger::logHttp()`
   envolve a escrita em `try/catch` e cai para o log de arquivo/stdout se a
   gravação falhar. Coberto por
   `tests/TestCase/Services/IntegrationLoggerTest.php` (redação, truncagem,
   falha de escrita silenciosa).

4. ✅ **Leitura no admin.** Aba "Integrações" em `Admin/Adhesions::view()`
   (linha do tempo da adesão, com request/response expansíveis) +
   `/admin/integration-logs` (`Admin\IntegrationLogsController`) com filtro
   por serviço/status/adesão, para os casos sem adesão associada (auth
   Sicoob, webhook com token inválido).

5. ✅ **Aba ativa após "Verificar pagamento".** A aba ativa agora é refletida
   em `?tab=` na URL: `Admin/Adhesions/view.php` lê o parâmetro no
   server-side para decidir qual aba renderiza como `active` (sem flash de
   JS), e um listener em `shown.bs.tab` faz `history.replaceState` a cada
   troca de aba. O formulário de "Verificar pagamento" carrega um campo
   oculto `tab` com a aba atual, e `checkPixPayment()` devolve o mesmo `tab`
   no redirect — por isso um F5 no meio da checagem também cai na aba
   correta.

Sem rotina de purga para `integration_logs`: o volume de adesões (34 desde
o início) não justifica ainda.
