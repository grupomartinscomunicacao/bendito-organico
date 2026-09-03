<p align="center">
  <img src="public/images/brand/logo.png" alt="Bendito Orgânico" width="440">
</p>

<p align="center">
  <strong>Loja de hortaliças, verduras e legumes orgânicos</strong><br>
  Laravel 12 · PHP 8.2+ · SQLite (ou MySQL 8) · Bootstrap 5 · Mercado Pago
</p>

---

## Sumário

- [Sobre o projeto](#sobre-o-projeto)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração do `.env`](#configuração-do-env)
- [Banco de dados](#banco-de-dados)
- [Migrations e seeders](#migrations-e-seeders)
- [Usuário administrativo](#usuário-administrativo)
- [Mercado Pago](#mercado-pago)
- [Webhook](#webhook)
- [Desenvolvimento local](#desenvolvimento-local)
- [Build de produção](#build-de-produção)
- [Deploy](#deploy)
- [Testes](#testes)
- [Identidade visual](#identidade-visual)
- [Estrutura do projeto](#estrutura-do-projeto)
- [Decisões de segurança](#decisões-de-segurança)
- [Créditos](#créditos)

---

## Sobre o projeto

O **Bendito Orgânico** é uma loja enxuta para venda direta de hortaliças
orgânicas. O foco é a compra rápida: o cliente escolhe o produto, informa a
quantidade e os dados de entrega, paga pelo Mercado Pago e acompanha o pedido
por um link — **sem criar conta**.

O fluxo completo:

```text
Home → Produto → Quantidade → Dados e endereço → Mercado Pago → Confirmação
                                                        ↓
                                          Webhook confirma o pagamento
                                                        ↓
                            Painel administrativo acompanha e imprime o cartão
```

**O que o projeto entrega**

| Área | Recursos |
| --- | --- |
| Loja | Home, catálogo com busca e paginação, página de produto com SEO, checkout em etapa única, acompanhamento de pedido |
| Pagamento | Checkout Pro do Mercado Pago, webhook assinado, conciliação idempotente, reconsulta manual |
| Painel | Dashboard, CRUD de produtos com upload, gestão de pedidos com filtros, cartão de separação para impressão, pagamentos, usuários, configurações |
| Qualidade | 84 testes automatizados, enums de domínio, Form Requests, Policies, Actions |

---

## Requisitos

| Software | Versão mínima | Observação |
| --- | --- | --- |
| PHP | 8.2 | 8.4 recomendado |
| Composer | 2.x | |
| SQLite | 3.35+ | Padrão do projeto — nenhuma configuração necessária |
| MySQL | 8.0 | Opcional. MariaDB 10.6+ também funciona |
| Node.js | 20 | Para compilar CSS e JS |

**Extensões PHP obrigatórias:** `pdo_sqlite` (ou `pdo_mysql`), `mbstring`, `openssl`, `tokenizer`,
`xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`, `gd` (upload de fotos),
`zip`, `intl`.

Verifique com:

```bash
php -m
```

---

## Instalação

```bash
# 1. Dependências PHP
composer install

# 2. Dependências de front-end
npm install

# 3. Ambiente
cp .env.example .env
php artisan key:generate

# 4. Link para os uploads (fotos de produtos)
php artisan storage:link

# 5. Banco de dados (SQLite, já é o padrão)
touch database/database.sqlite
php artisan migrate --seed

# 6. Assets
npm run build
```

> **Windows:** use `copy .env.example .env` no lugar de `cp`.

---

## Configuração do `.env`

O `.env.example` já vem comentado. Os blocos que exigem atenção:

### Aplicação

```env
APP_NAME="Bendito Orgânico"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=America/Sao_Paulo
APP_LOCALE=pt_BR
```

`APP_URL` precisa refletir a URL real: é a partir dela que são geradas as
`back_urls` enviadas ao Mercado Pago.

### Banco de dados

O projeto vem configurado com **SQLite**, que não exige servidor nem senha:

```env
DB_CONNECTION=sqlite
```

Sem `DB_DATABASE`, o Laravel usa `database/database.sqlite` com caminho
absoluto — o que continua correto mesmo quando o processo roda de outro
diretório (worker de fila, agendador).

Para usar MySQL, troque o bloco por:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bendito_organico
DB_USERNAME=root
DB_PASSWORD=sua-senha
```

### Mercado Pago

```env
MERCADOPAGO_ACCESS_TOKEN=
MERCADOPAGO_PUBLIC_KEY=
MERCADOPAGO_WEBHOOK_SECRET=
```

### Loja

```env
STORE_EMAIL=contato@benditoorganico.com.br
STORE_PHONE="(11) 99999-0000"
STORE_WHATSAPP=5511999990000
```

O `STORE_WHATSAPP` deve conter **apenas dígitos, com código do país**
(`55` + DDD + número).

> **Nunca versione o `.env`.** Ele já está no `.gitignore`.

---

## Banco de dados

### SQLite (padrão)

Basta criar o arquivo vazio — as migrations cuidam do resto:

```bash
# Linux/macOS
touch database/database.sqlite

# Windows (PowerShell)
New-Item -ItemType File database/database.sqlite
```

A conexão SQLite já vem ajustada em `config/database.php` para o cenário
desta loja, em que o webhook do Mercado Pago escreve ao mesmo tempo que o
site:

| Ajuste | Valor | Por quê |
| --- | --- | --- |
| `journal_mode` | `WAL` | Leitores não bloqueiam o escritor, e vice-versa |
| `busy_timeout` | `5000` | Espera 5s por um lock em vez de falhar de imediato |
| `transaction_mode` | `IMMEDIATE` | Pega o lock de escrita no início da transação, evitando o `SQLITE_BUSY` de quem lê primeiro e escreve depois |

> **Limite conhecido:** o SQLite serializa as escritas e ignora
> `lockForUpdate()`. Para uma horta que recebe alguns pedidos por dia isso é
> perfeitamente adequado, e o `transaction_mode` acima preserva o
> comportamento correto. Sob concorrência alta, migre para MySQL — o código
> não muda, só o `.env`.

### MySQL (opcional)

```bash
mysql -u root -p -e "CREATE DATABASE bendito_organico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Em produção, prefira um usuário dedicado em vez do `root`:

```sql
CREATE USER 'bendito'@'localhost' IDENTIFIED BY 'senha-forte-aqui';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES
    ON bendito_organico.* TO 'bendito'@'localhost';
FLUSH PRIVILEGES;
```

As migrations são portáveis: o mesmo schema roda nos dois bancos.

### Modelo de dados

```text
users            Contas da equipe (admin | operator), com soft delete
products         Catálogo: preço, unidade, estoque, foto, SEO, soft delete
orders           Pedido + dados do cliente + situação + situação do pagamento
order_items      Itens do pedido, com snapshot do produto no momento da compra
addresses        Endereço de entrega (1:1 com o pedido)
payments         Transações do Mercado Pago (única por gateway + external_id)
webhook_events   Registro de notificações recebidas — garante idempotência
```

Relacionamentos e integridade:

- `order_items.order_id` → `orders.id` — **cascade on delete**
- `order_items.product_id` → `products.id` — **null on delete**
  (excluir um produto não apaga o histórico de vendas)
- `addresses.order_id` → `orders.id` — **cascade on delete**, `unique`
- `payments.order_id` → `orders.id` — **cascade on delete**
- `payments (gateway, external_id)` — **unique**, base da idempotência
- `webhook_events (source, event_id)` — **unique**, evita reprocessar retentativas

---

## Migrations e seeders

```bash
# Criar as tabelas
php artisan migrate

# Recriar tudo do zero (apaga os dados!)
php artisan migrate:fresh

# Popular com dados iniciais
php artisan db:seed

# Tudo de uma vez
php artisan migrate:fresh --seed
```

### O que os seeders criam

| Seeder | Conteúdo |
| --- | --- |
| `AdminUserSeeder` | Usuário administrador |
| `ProductSeeder` | 14 produtos reais, com foto, preço e estoque |
| `DemoOrderSeeder` | 8 pedidos de exemplo espalhados pelo fluxo — **não roda em produção** |

Os seeders usam `updateOrCreate`, então rodar de novo atualiza os dados em vez
de duplicar.

---

## Usuário administrativo

Credenciais padrão criadas pelo `AdminUserSeeder`:

```text
URL:    http://localhost:8000/admin
E-mail: admin@benditoorganico.com.br
Senha:  BenditoOrganico@2026
```

> **Troque a senha no primeiro acesso.**

Para definir outras credenciais antes de rodar o seeder:

```env
ADMIN_NAME="Seu Nome"
ADMIN_EMAIL=voce@suaempresa.com.br
ADMIN_PASSWORD=uma-senha-bem-forte
```

Perfis disponíveis:

- **Administrador** — acesso total, incluindo usuários e reconsulta de pagamentos
- **Operador** — produtos e pedidos no dia a dia

---

## Mercado Pago

A integração usa **Checkout Pro** consumindo a API REST oficial pelo HTTP client
do Laravel — sem SDK, para que o contrato fique preso à API e não a uma versão
de biblioteca.

Endpoints utilizados:

| Método | Endpoint | Uso |
| --- | --- | --- |
| `POST` | `/checkout/preferences` | Cria a preferência de pagamento |
| `GET` | `/v1/payments/{id}` | Consulta o estado real de um pagamento |
| `GET` | `/merchant_orders/{id}` | Resolve os pagamentos de uma ordem |

### Meios de pagamento aceitos

A loja trabalha **apenas com Pix e cartão**. Boleto (`ticket`) e caixa
eletrônico (`atm`) são bloqueados na própria preferência, via
`excluded_payment_types` — só mudar o texto da página não bastaria, o Checkout
Pro continuaria oferecendo boleto.

```env
MERCADOPAGO_EXCLUDED_PAYMENT_TYPES=ticket,atm
```

Continuam liberados: `bank_transfer` (Pix), `credit_card`, `debit_card` e
`account_money` (saldo Mercado Pago). Para recusar também o saldo, acrescente
`account_money` à lista.

### Obter as credenciais

1. Acesse <https://www.mercadopago.com.br/developers/panel/app>
2. Crie (ou abra) uma aplicação
3. Em **Credenciais de teste**, copie:
   - `Access Token` → `MERCADOPAGO_ACCESS_TOKEN`
   - `Public Key` → `MERCADOPAGO_PUBLIC_KEY`

O sistema detecta o modo sandbox pelo prefixo `TEST-` do access token e passa a
usar o `sandbox_init_point` automaticamente. Ao migrar para produção, troque
pelas credenciais que começam com `APP_USR-`.

### Cartões de teste

| Bandeira | Número | CVV | Validade |
| --- | --- | --- | --- |
| Mastercard | 5031 4332 1540 6351 | 123 | 11/30 |
| Visa | 4235 6477 2802 5682 | 123 | 11/30 |

Use o nome do titular para simular o resultado: `APRO` (aprovado),
`OTHE` (recusado), `CONT` (pendente).

---

## Webhook

A **única** coisa que marca um pedido como pago é o webhook (ou a reconsulta
manual no painel). O retorno do cliente para a página de sucesso nunca é aceito
como prova de pagamento.

```text
POST /webhooks/mercadopago
```

### Configuração

1. No painel do Mercado Pago: **Suas integrações → Webhooks → Configurar notificações**
2. Informe a URL pública: `https://seu-dominio.com.br/webhooks/mercadopago`
3. Marque o evento **Pagamentos**
4. Copie a **assinatura secreta** para `MERCADOPAGO_WEBHOOK_SECRET`

### Testando localmente

O Mercado Pago não alcança `localhost`. Use um túnel:

```bash
# ngrok
ngrok http 8000

# ou Cloudflare
cloudflared tunnel --url http://localhost:8000
```

Depois aponte o `.env` para a URL do túnel:

```env
APP_URL=https://seu-tunel.ngrok-free.app
MERCADOPAGO_WEBHOOK_URL=https://seu-tunel.ngrok-free.app/webhooks/mercadopago
```

### Como a notificação é validada

1. **Assinatura HMAC-SHA256.** O manifesto
   `id:<data.id>;request-id:<x-request-id>;ts:<ts>;` é assinado com o segredo e
   comparado com `v1` do header `x-signature`, em tempo constante.
2. **Janela temporal.** Assinaturas com mais de 5 minutos são recusadas
   (`MERCADOPAGO_SIGNATURE_TOLERANCE`).
3. **Consulta à API.** O corpo da notificação diz apenas *qual* recurso mudou;
   o estado real é buscado em `/v1/payments/{id}` com o access token.
4. **Conferência de valor.** Se o valor pago for menor que o total do pedido, a
   transação é registrada mas o pedido **não** é marcado como pago, e um alerta
   `critical` vai para o log.
5. **Idempotência.** Cada entrega é registrada em `webhook_events` pelo
   `x-request-id`. Retentativas retornam `200` sem reprocessar.

### Códigos de resposta

| Código | Significado | O Mercado Pago vai... |
| --- | --- | --- |
| `200` | Processado ou ignorado | parar de tentar |
| `401` | Assinatura inválida | tentar de novo (e ser recusado) |
| `503` | API do gateway indisponível | tentar de novo em 15 min |
| `500` | Erro interno | tentar de novo em 15 min |

O histórico das notificações fica visível em **Painel → Configurações**.

---

## Desenvolvimento local

Dois terminais:

```bash
# Terminal 1 — aplicação
php artisan serve

# Terminal 2 — assets com hot reload
npm run dev
```

Acesse:

| URL | O quê |
| --- | --- |
| <http://localhost:8000> | Loja |
| <http://localhost:8000/produtos> | Catálogo |
| <http://localhost:8000/admin> | Painel |

Comandos úteis:

```bash
php artisan migrate:fresh --seed   # recomeçar do zero
php artisan optimize:clear         # limpar todos os caches
php artisan route:list             # conferir as rotas
php artisan tinker                 # console interativo
```

---

## Build de produção

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Para desfazer os caches: `php artisan optimize:clear`.

---

## Deploy

### Checklist

- [ ] `APP_ENV=production` e `APP_DEBUG=false`
- [ ] `APP_URL` com o domínio real em **https**
- [ ] `APP_KEY` gerada (`php artisan key:generate`)
- [ ] Credenciais de produção do Mercado Pago (`APP_USR-…`)
- [ ] `MERCADOPAGO_WEBHOOK_SECRET` preenchido
- [ ] `MERCADOPAGO_VERIFY_SIGNATURE=true`
- [ ] Webhook cadastrado no painel do Mercado Pago
- [ ] `php artisan storage:link` executado
- [ ] Permissão de escrita em `storage/` e `bootstrap/cache/`
- [ ] Banco definido: SQLite com backup do arquivo, ou MySQL com usuário dedicado
- [ ] Backup automático do banco configurado
- [ ] Certificado SSL válido
- [ ] Worker de fila ativo (e-mails de confirmação)
- [ ] Senha do administrador trocada

### Document root

Aponte o servidor para a pasta **`public/`**, nunca para a raiz do projeto.

<details>
<summary>Exemplo de configuração Nginx</summary>

```nginx
server {
    listen 443 ssl http2;
    server_name benditoorganico.com.br;
    root /var/www/bendito-organico/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Nunca sirva arquivos ocultos
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

</details>

### Fila e agendador

Os e-mails de confirmação são enfileirados. Mantenha um worker rodando:

```bash
php artisan queue:work --tries=3 --max-time=3600
```

Com `supervisor` ou `systemd`, para reiniciar sozinho.

---

## Testes

```bash
php artisan test                                # tudo
php artisan test --filter=MercadoPagoWebhookTest  # só o webhook
```

A suíte roda em SQLite em memória — não toca no banco de desenvolvimento.

| Arquivo | Cobre |
| --- | --- |
| `CheckoutTest` | Preço vindo do banco, adulteração de formulário, estoque, validação |
| `MercadoPagoWebhookTest` | Assinatura forjada, replay, duplicidade, valor menor, estorno |
| `StorefrontTest` | Renderização das páginas públicas, SEO, honeypot |
| `AdminPanelTest` | Login, throttle, CRUD, transições de situação, impressão, permissões |
| `DomainRulesTest` | Fluxo de situações, mapeamento de status, HMAC, formatação |

---

## Identidade visual

A logo oficial fica em `public/images/brand/`. O arquivo original está
preservado na raiz do projeto (`logo.png`).

| Arquivo | Quando usar |
| --- | --- |
| `logo.png` | Lockup oficial, texto verde-escuro — **fundos claros** |
| `logo-light.png` | Mesmo lockup com o texto em branco — **fundos escuros** (navbar, rodapé, cartão) |
| `logo-mark.png` | Só o emblema circular — espaços reduzidos |
| `favicon.png`, `apple-touch-icon.png` | Ícones do navegador |
| `og-image.png` | Compartilhamento em redes sociais |
| `hero-section.webp` / `-1280` / `-768` | Foto de capa do hero, em tamanhos responsivos |
| `hero-section.jpg` | Fallback da capa para navegadores sem WebP |

> O arquivo original entregue, `hero-section.png`, é **WebP por dentro**
> (assinatura `RIFF…WEBP`). Servido como `image/png`, funciona só porque o
> navegador adivinha o formato — o que quebra em proxies e CDNs que respeitam o
> `Content-Type`. As variantes acima têm extensão coerente com o conteúdo. O
> original foi mantido intacto.

`logo-light.png` é a versão *negativa* da mesma arte — nenhum elemento foi
redesenhado. A classe `.brand-logo` define `width: auto`, então a logo nunca é
distorcida, qualquer que seja a altura.

### Paleta

| Cor | Hex | Uso |
| --- | --- | --- |
| Verde principal | `#11411B` | Navbar, títulos, botões principais |
| Verde secundário | `#2E7D32` | Links, estados positivos |
| Verde destaque | `#5FAE45` | Cards, ícones, detalhes |
| Laranja | `#E8872E` | CTAs, promoções, alertas comerciais |

A paleta alimenta as variáveis do Bootstrap **antes** da compilação
(`resources/scss/_variables.scss`), então botões, badges e alertas já nascem
com as cores da marca — sem `!important` em lugar nenhum. Também estão
disponíveis como custom properties (`--color-primary`, etc.).

### Imagens dos produtos

As fotos em `public/images/products/` são **placeholders da marca**, geradas
para que uma instalação nova não fique com imagens quebradas. Substitua-as pelo
painel, no cadastro de cada produto.

---

## Estrutura do projeto

```text
app/
├── Actions/            Regras de negócio isoladas
│   ├── Orders/         CreateOrder, SyncPaymentFromGateway, UpdateOrderStatus…
│   └── Products/       StoreProductImage
├── Enums/              OrderStatus, PaymentStatus, ProductUnit, UserRole
├── Events/             OrderPaid
├── Exceptions/         CheckoutException, PaymentGatewayException
├── Http/
│   ├── Controllers/
│   │   ├── Admin/      Dashboard, produtos, pedidos, pagamentos, usuários
│   │   ├── Auth/       Login, recuperação de senha
│   │   ├── Web/        Loja, checkout, pedidos
│   │   └── Webhooks/   MercadoPagoWebhookController
│   ├── Middleware/     EnsureUserCanAccessPanel
│   └── Requests/       Form Requests (Admin, Auth, Web)
├── Listeners/          SendOrderPaidNotification
├── Models/             User, Product, Order, OrderItem, Address, Payment…
├── Notifications/      OrderPaidNotification, ContactMessageNotification
├── Policies/           Product, Order, Payment, User
├── Services/           MercadoPagoService, CheckoutSession
└── Support/            Money

resources/
├── js/modules/         Stepper, máscaras, busca de CEP, upload, etc.
├── scss/               _variables, _base, _components, _storefront,
│                       _products, _checkout, _auth, _admin, _print
└── views/
    ├── admin/          Painel
    ├── auth/           Login e senha
    ├── components/     product-card, status-badge, form/*, admin/*…
    ├── errors/         404, 403, 419, 429, 500, 503
    ├── home/  orders/  pages/  products/
    ├── layouts/        app, admin, auth, print
    └── partials/       navbar, footer, sidebar
```

---

## Decisões de segurança

| Risco | Como é tratado |
| --- | --- |
| Adulteração de preço | O total é calculado no `CreateOrder` lendo o preço do banco sob `lockForUpdate`. Nenhum valor monetário enviado pelo formulário é lido. |
| Adulteração de quantidade | Validada contra o estoque real dentro da transação. |
| Produto inativo ou inexistente | `Rule::exists` na validação e nova checagem dentro da transação. |
| Pagamento falso | Só o webhook assinado — ou a reconsulta manual — marca um pedido como pago. A página de sucesso apenas exibe o que já está no banco. |
| Pagamento a menor | Comparado com o total do pedido; divergência registra log `critical` e mantém o pedido pendente. |
| Notificação forjada | HMAC-SHA256 comparado com `hash_equals`, mais janela temporal de 5 minutos. |
| Notificação repetida | `webhook_events (source, event_id)` único; efeitos colaterais protegidos pelo estado do próprio pedido. |
| Venda acima do estoque | `lockForUpdate` na criação e `CASE WHEN … THEN 0` na baixa. |
| Enumeração de pedidos | A URL usa `public_number` com sufixo aleatório, não o id sequencial. |
| Enumeração de contas | Login e recuperação de senha respondem sempre a mesma mensagem. |
| Força bruta no login | 5 tentativas por e-mail + IP, com bloqueio temporário. |
| Upload malicioso | Validação de MIME e dimensões, nome de arquivo regerado no servidor, gravação fora da raiz pública. |
| Mass assignment | `$fillable` em todos os models; `Model::shouldBeStrict()` fora de produção. |
| CSRF | Ativo em todo o site; exceção apenas para o webhook, que se autentica por HMAC. |
| SQL injection | Exclusivamente Eloquent e Query Builder — nenhuma concatenação de entrada do usuário. |
| Vazamento de dados em erro | `APP_DEBUG=false` em produção e páginas de erro próprias. |
| Escalada de privilégio | Policies em todas as ações do painel; um admin não consegue rebaixar nem excluir a si mesmo. |
| Sessão sequestrada | Sessão regenerada no login; contas desativadas são deslogadas na requisição seguinte. |

---

## Créditos

Sistema desenvolvido pela **[Origin Hub](https://originhub.com.br)**.

O crédito aparece no rodapé da loja e no rodapé do painel, e sai de
`config/bendito.php` (`developer.name` / `developer.url`), configurável por
`DEVELOPER_NAME` e `DEVELOPER_URL` no `.env`.

---

<p align="center">
  <sub>Feito com 🌱 para quem planta e para quem come bem.</sub><br>
  <sub>Desenvolvido por <a href="https://originhub.com.br">Origin Hub</a></sub>
</p>
