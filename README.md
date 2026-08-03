# Sul Previdência Adesão

Aplicação web em CakePHP 5.x para adesão (inscrição) a planos de previdência da Sul Previdência. O usuário preenche um formulário multi-etapas que é salvo em PostgreSQL, gera documentos em PDF, envia para assinatura digital via Clicksign e cria uma cobrança Pix via Sicoob.

## Requisitos

- PHP 8.1+
- Composer
- Docker (para o PostgreSQL local)

## Configuração do ambiente

Copie `config/.env.example` para `config/.env` e preencha as variáveis, entre elas:

- `DB_*` — conexão com PostgreSQL (serviço Docker padrão: `pgsql-sul-prev:5432`, banco `adesao-sulprev-db`)
- `CLICKSIGN_BASE_URL` / `CLICKSIGN_ACCESS_TOKEN` — API do Clicksign
- `SICOOB_*` — API Pix da Sicoob (inclui certificado/chave em Base64 para mTLS)
- `SECURITY_SALT` — salt de segurança do CakePHP

## Comandos

```bash
# Subir/parar o PostgreSQL (Docker)
composer docker:start
composer docker:stop

# Servidor de desenvolvimento
bin/cake server -p 8765

# Banco de dados
composer migrate          # bin/cake migrations migrate
composer migrate:rollback # bin/cake migrations rollback
composer seed             # bin/cake migrations seed

# Testes
composer test
vendor/bin/phpunit tests/TestCase/Controller/RegistrationsControllerTest.php  # arquivo único

# Padrão de código
composer cs-check   # phpcs
composer cs-fix      # phpcbf (correção automática)
```

Os testes usam um banco SQLite em memória (`tmp/tests.sqlite`), configurado em `config/app_local.php` em `Datasources.test`.

## Arquitetura

Visão geral do fluxo de requisições, modelo de dados e integrações está documentada em [`CLAUDE.md`](./CLAUDE.md).

Resumo:

- **Rotas públicas** (`/`) — página inicial, simulador de previdência, formulário de adesão (`RegistrationsController`) e consulta de CBO.
- **Rotas administrativas** (`/admin/`) — protegidas pelo plugin `Authentication`; dashboard, listagem/consulta de adesões e download de PDFs, login de usuários.
- **Integrações**: `ClicksignService` (assinatura digital) e `SicoobService` (cobrança Pix via mTLS).
- **Frontend**: templates PHP simples em `templates/`, com o formulário de adesão em `templates/Pages/home.php` controlado por `webroot/js/application.js`.

## Deploy

```bash
kamal deploy -c config/deploy.yml
kamal app exec "bin/cake migrations seed"
```

## Framework

Construído sobre [CakePHP](https://cakephp.org) 5.x. Código-fonte do framework: [cakephp/cakephp](https://github.com/cakephp/cakephp).
