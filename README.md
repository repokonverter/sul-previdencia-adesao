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

Os testes usam um banco PostgreSQL dedicado (`adesao-sulprev-test`, na mesma instância Docker do dev), configurado por `DATABASE_TEST_URL` em `config/.env`. Precisa ser Postgres: a função do simulador (`simulacao_previdencia`) é PL/pgSQL.

## Arquitetura

Visão geral do fluxo de requisições, modelo de dados e integrações está documentada em [`CLAUDE.md`](./CLAUDE.md). Os termos do domínio (Parceiro, Vínculo associativo, Código promocional, Corretor etc.) estão em [`GLOSSARY.md`](./GLOSSARY.md).

Resumo:

- **Rotas públicas** (`/`) — página inicial, simulador de previdência, formulário de adesão (`RegistrationsController`) e consulta de CBO.
- **Rotas administrativas** (`/admin/`) — protegidas pelo plugin `Authentication`; dashboard, listagem/consulta de adesões (com filtros por parceiro, corretor e período, e um resumo com as contagens do filtro) e download de PDFs, Parceiros e Vínculos associativos (com seus códigos promocionais e corretores), login de usuários.
- **Senhas do admin** — "Esqueci minha senha" no login (link por e-mail, válido por 1 hora e de uso único), convite por e-mail ao criar usuário (o admin não define senha de ninguém) e "Alterar senha" no menu. Regra do NIST SP 800-63B: de 15 a 64 caracteres, sem exigir composição, recusando o nome do serviço e o nome/e-mail do usuário. Login, pedidos de link e senha atual errada têm limite de tentativas; trocar a senha encerra as outras sessões.
- **Links de divulgação** — a página inicial aceita `?promo=<código>` e `?corretor=<token>` e os repassa ao simulador, que abre a adesão com o código ou o corretor já preenchidos. O link de cada corretor é copiado na tela do Parceiro.
- **Integrações**: `ClicksignService` (assinatura digital) e `SicoobService` (cobrança Pix via mTLS).
- **Frontend**: templates PHP simples em `templates/`, com o formulário de adesão em `templates/Pages/home.php` controlado por `webroot/js/application.js`.

## Deploy

```bash
kamal deploy -c config/deploy.yml
kamal app exec "bin/cake migrations seed"
```

## Framework

Construído sobre [CakePHP](https://cakephp.org) 5.x. Código-fonte do framework: [cakephp/cakephp](https://github.com/cakephp/cakephp).
