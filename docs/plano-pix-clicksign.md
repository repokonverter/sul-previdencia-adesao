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
mecanismo — *consultar o Sicoob (`GET /cob/{txid}`) e agir pelo status real*
— atende quatro entradas:

1. Cliente abre a página de pagamento
2. Webhook do Sicoob (usado apenas como gatilho, nunca como fonte de verdade)
3. Botão "verificar pagamento" no admin
4. Regeneração automática de cobrança expirada

O Sicoob é sempre a fonte da verdade; nada é inferido apenas do estado local.

## Decisões fechadas

| Área | Decisão |
|---|---|
| Entrega ao cliente | Página própria `/pagamento/{storage_uuid}` + e-mail automático com o link |
| Criação da cobrança | Lazy — só na primeira abertura da página de pagamento |
| Lógica ao abrir a página | `GET /cob/{txid}`: `CONCLUIDA` → mostra pago; `ATIVA` → mostra brcode fresco (vindo da resposta, não do cache); expirada/removida → gera nova cobrança |
| Expiração da cobrança | 24h (`calendario.expiracao = 86400`) |
| Geração do txid | Nosso, via `PUT /cob/{txid}` (não mais `POST /cob`), formato legível: prefixo + id da adesão + nº da tentativa + preenchimento até 26–35 chars. Determinístico por (adesão, tentativa) — evita duplicidade mesmo com abas concorrentes |
| Ordem da transação | Commit dos dados da adesão **antes** do bloco Clicksign. Clicksign passa a rodar em request separada, disparada pela página de pagamento |
| Rastreio Clicksign | `clicksign_data` ganha status (pending/sent/failed), contador de tentativas, último erro |
| Falha do Clicksign | Não derruba a adesão. Notifica por e-mail os usuários cadastrados no admin (tabela `users`) |
| Webhook Sicoob | Apenas gatilho — ao receber, confirma via `GET /cob/{txid}` antes de marcar qualquer coisa. Token de segurança **no path**, não em query string: `/sicoob/webhook/{token}/pix` — o Sicoob acrescenta `/pix` ao final da URL cadastrada, o que quebraria um token em query string |
| Admin | Tela de CRUD de webhooks cadastrados no Sicoob + botão manual "verificar pagamento" por adesão (mesma lógica do item acima, sob demanda) |
| Provedor de e-mail | Resend |
| Remetente | `plenoprev@konverter.com.br` — **provisório**, definido só em env var (endereço, nome de exibição, credenciais). Trocar deve ser mudança de config, não de código. Cliente ainda vai definir o domínio definitivo |
| `App.fullBaseUrl` | Precisa ser configurado explicitamente — e-mail não tem request HTTP para inferir domínio, os links quebrariam sem isso |
| Visual da página de pagamento | Herda a identidade visual do simulador (`templates/Simulator/index.php`): `--primary-color: #FF6B00`, texto `#333`, Arial, Bootstrap 5, card branco `border-radius: 24px`, `box-shadow: 0 4px 32px rgba(0,0,0,.10)`, barra superior 10px na cor primária, `max-width: 950px`, logo `logo_sul_transparente.png`. **Não** é o layout enterprise — isso é só para o e-mail |
| Visual do e-mail | Layout "enterprise" — a ser desenhado, com o mesmo remetente/branding provisório acima |
| Backfill de dados antigos | Não necessário — base atual é só teste |

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

## Pendências levantadas mas fora de escopo desta rodada

Registradas para retomar em sessão futura — não implementar agora:

1. **`DEBUG: true` em produção** (`config/deploy.yml`). Em um app que trafega
   CPF, dados bancários e dependentes, qualquer exceção não tratada expõe
   stack trace (incluindo trechos de configuração) no navegador do cliente.
2. **Logs efêmeros.** Não há volume Docker para `logs/` no `deploy.yml`, então
   todo `kamal deploy` apaga o histórico de erro. Com webhook, Resend e retry
   de Clicksign entrando, isso vai doer na hora de depurar. Sugestão: logar em
   stdout (`kamal app logs`).
3. **Incoerência de meio de pagamento.** O passo `paymentDetail` do formulário
   oferece apenas "Débito em conta (Somente BB)" e "Boleto bancário" como
   opções — PIX não é uma opção visível — mas o sistema gera cobrança PIX para
   todo cliente, independente da escolha. A nova página de pagamento vai pedir
   PIX de alguém que escolheu débito em conta. É decisão de produto, não bug
   de código; precisa de alguém do lado do cliente definir o comportamento
   correto (esconder o PIX se o meio escolhido for outro? substituir as
   opções por "PIX" apenas? gerar cobrança só se compatível?).
