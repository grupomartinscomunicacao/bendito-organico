# Deploy — Bendito Orgânico

Publicação em VPS Hostinger (Ubuntu/Debian + Nginx + PHP-FPM), domínio
**benditoorganico.com.br**, com o mesmo banco SQLite usado em desenvolvimento.

> **A VPS hospeda outros domínios.** Todo o procedimento foi montado para não
> encostar neles. O resumo do porquê está em [Isolamento](#isolamento) — vale
> ler antes de rodar qualquer coisa.

---

## O que já está no repositório

| Arquivo | Para quê |
|---|---|
| `setup-servidor.sh` | Provisionamento inicial. Roda **uma vez**, como root. |
| `deploy.sh` | Deploy e atualizações. Roda a cada nova versão, como `bendito`. |
| `env.production.example` | Modelo do `.env` de produção (sem segredo nenhum). |
| `nginx/bootstrap-http.conf` | Vhost temporário, só para o certbot validar o domínio. |
| `nginx/benditoorganico.com.br.conf` | Vhost definitivo, com HTTPS. |
| `php-fpm/bendito-organico.conf` | Pool PHP-FPM exclusivo deste site. |
| `systemd/bendito-queue.service` | Worker da fila. |
| `systemd/bendito-scheduler.*` | Agendador do Laravel (preventivo). |

---

## Antes de começar

Confira na VPS:

```bash
php -v                       # precisa ser >= 8.2 (Laravel 12)
nginx -v && node -v && npm -v
composer --version
which certbot sqlite3 git

# Fotografe o estado atual dos outros domínios, para comparar depois:
nginx -T | grep server_name | sort > /root/dominios-antes.txt
cat /root/dominios-antes.txt
```

Se faltar Node, Composer ou sqlite3:

```bash
sudo apt update
sudo apt install -y composer sqlite3 unzip
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

O DNS de `benditoorganico.com.br` e `www` já aponta para a VPS — sem isso o
certbot não emite o certificado. Confirme com `dig +short benditoorganico.com.br`.

---

## Passo 1 — Subir o código para o GitHub

Na **máquina local**:

```powershell
git add -A
git commit -m "Adiciona a infraestrutura de deploy"
git push origin master
```

O `.env` e o `database/database.sqlite` **não** vão nesse push — os dois estão
no `.gitignore` de propósito, porque carregam segredo e dado de cliente. Eles
são enviados à parte, nos passos 4 e 5.

---

## Passo 2 — Provisionar o servidor

```bash
ssh root@SEU_IP
git clone https://github.com/grupomartinscomunicacao/bendito-organico.git /tmp/bendito-setup
sudo bash /tmp/bendito-setup/deploy/setup-servidor.sh
```

O script cria o usuário `bendito`, clona o projeto em
`/var/www/benditoorganico.com.br`, instala o pool PHP-FPM e o vhost, emite o
certificado e registra os serviços de fila.

**Ele só cria arquivos novos.** Se algum destino já existir, avisa e segue sem
sobrescrever. E valida (`nginx -t`, `php-fpm -t`) antes de qualquer reload: se
a configuração não passar, o script desfaz o que criou e aborta **sem
recarregar nada** — nenhum domínio cai.

---

## Passo 3 — Criar o `.env` de produção

```bash
cd /var/www/benditoorganico.com.br
sudo -u bendito -H cp deploy/env.production.example .env
sudo -u bendito -H php artisan key:generate
sudo -u bendito -H chmod 640 .env
sudo -u bendito -H nano .env
```

Revise antes de sair:

- `APP_DEBUG=false` — obrigatório. Com `true`, uma página de erro entrega
  caminho de arquivo e trecho de configuração para qualquer visitante.
- `APP_URL=https://benditoorganico.com.br`.
- `STORE_PHONE`, `STORE_WHATSAPP`, `STORE_CITY` — os valores herdados do
  ambiente local ainda são de exemplo (`(11) 99999-0000`) e aparecem no
  rodapé e no card de pedido impresso.

A chave gerada aqui é diferente da de desenvolvimento, de propósito. Nenhuma
coluna do banco é criptografada, então isso não perde dado — só invalida
sessões abertas.

---

## Passo 4 — Enviar o banco SQLite

Este é o passo que traz **o mesmo banco do ambiente local**, com produtos,
pedidos e usuários do painel.

Na **máquina local**, feche o `php artisan serve` antes de copiar (evita pegar
o arquivo no meio de uma escrita) e gere uma cópia consistente:

```powershell
cd C:\Users\teste\OneDrive\Desktop\PROJETOS\bendito-organico
sqlite3 database/database.sqlite ".backup 'database/deploy-inicial.sqlite'"
scp database/deploy-inicial.sqlite root@SEU_IP:/tmp/database.sqlite
```

> Sem o `sqlite3.exe` no Windows, dá para copiar `database/database.sqlite`
> direto — mas só com o servidor local **parado**. O banco roda em modo WAL, e
> um `cp` com escrita em andamento pode gerar um arquivo corrompido.

No **servidor**:

```bash
install -o bendito -g bendito -m 664 /tmp/database.sqlite \
    /var/www/benditoorganico.com.br/database/database.sqlite
rm /tmp/database.sqlite

# O SQLite grava os arquivos -wal e -shm ao lado do banco: a PASTA também
# precisa ser gravável, não só o arquivo.
chown bendito:bendito /var/www/benditoorganico.com.br/database
chmod 775 /var/www/benditoorganico.com.br/database

# Confere que veio íntegro:
sudo -u bendito -H sqlite3 /var/www/benditoorganico.com.br/database/database.sqlite \
    "PRAGMA integrity_check; SELECT COUNT(*) || ' produtos' FROM products;"
```

---

## Passo 5 — Enviar as fotos de produto (se houver)

As fotos enviadas pelo painel ficam em `storage/app/public/products` e não vão
pelo git. Hoje essa pasta está vazia no ambiente local — todas as imagens do
catálogo vêm de `public/images/products`, que **é** versionada. Se você tiver
subido fotos pelo painel desde então:

```powershell
scp -r storage/app/public/products/* root@SEU_IP:/tmp/produtos/
```

```bash
mkdir -p /var/www/benditoorganico.com.br/storage/app/public/products
cp /tmp/produtos/* /var/www/benditoorganico.com.br/storage/app/public/products/
chown -R bendito:bendito /var/www/benditoorganico.com.br/storage/app/public
```

---

## Passo 6 — Primeiro deploy

```bash
sudo -u bendito -H bash -c "cd /var/www/benditoorganico.com.br && ./deploy/deploy.sh"
```

O script faz backup do banco, instala dependências, compila os assets do Vite,
roda as migrations, cria o link do storage e reconstrói os caches.

---

## Passo 7 — Ligar fila e agendador

```bash
sudo systemctl enable --now bendito-queue.service
sudo systemctl enable --now bendito-scheduler.timer
systemctl status bendito-queue --no-pager
```

O worker não é opcional: `QUEUE_CONNECTION=database` e as notificações de
pedido pago e de contato implementam `ShouldQueue`. Sem ele, os jobs entram na
tabela `jobs` e nunca saem.

---

## Passo 8 — Verificação

```bash
# A loja
curl -sI https://benditoorganico.com.br/up | head -1     # HTTP/2 200
curl -sI https://www.benditoorganico.com.br | head -3     # 301 para a raiz
curl -sI http://benditoorganico.com.br | head -3          # 301 para https

# O painel
curl -sI https://benditoorganico.com.br/admin/login | head -1

# Nada sensível exposto
curl -s -o /dev/null -w '%{http_code}\n' https://benditoorganico.com.br/.env
# esperado: 403 ou 404 — NUNCA 200
```

**E confirme que os outros domínios continuam no ar:**

```bash
nginx -T | grep server_name | sort > /root/dominios-depois.txt
diff /root/dominios-antes.txt /root/dominios-depois.txt
# esperado: só as linhas novas de benditoorganico.com.br

systemctl status nginx --no-pager
for d in outrodominio1.com.br outrodominio2.com.br; do
    echo -n "$d: "; curl -sI "https://$d" | head -1
done
```

No navegador, com o cadeado aberto: home, `/produtos`, uma página de produto,
`/contato`, e o login em `/admin/login`.

---

## Atualizações depois disso

```bash
sudo -u bendito -H bash -c "cd /var/www/benditoorganico.com.br && ./deploy/deploy.sh"
```

Só isso. O script põe o site em manutenção, atualiza, e sai da manutenção
mesmo se algum passo falhar no meio.

---

## Isolamento

Por que os outros domínios da VPS não são afetados:

**Usuário próprio.** O site roda como `bendito`, não como `www-data`. Os
arquivos dos outros domínios não são legíveis por esse usuário, então um
problema aqui fica contido aqui.

**Pool PHP-FPM dedicado.** `bendito-organico.conf` cria um pool separado, com
socket próprio (`/run/php/bendito-organico.sock`). O pool `www` que serve os
outros sites continua intacto: mesmos limites, mesmos processos. Um pico de
tráfego nesta loja não consome os workers dos vizinhos. O `open_basedir` do
pool ainda impede que um script daqui leia `/var/www` de outro domínio.

**Vhost isolado.** Arquivo novo em `sites-available`, sem `default_server`, com
`server_name` fechado apenas em `benditoorganico.com.br` e `www`. Nenhum
arquivo existente do nginx é editado.

**Certificado sem tocar em vhost.** `certbot certonly --webroot` só emite o
certificado — ao contrário de `certbot --nginx`, ele não reescreve configuração
nenhuma.

**Validar antes de recarregar.** Os scripts rodam `nginx -t` e `php-fpm -t`
antes de qualquer `reload`, e abortam desfazendo o que criaram se a validação
falhar. E usam sempre `reload` (gracioso), nunca `restart`.

O único momento em que um serviço compartilhado é tocado é o `systemctl reload`
do nginx e do PHP-FPM. Reload é gracioso: as conexões em andamento terminam e
os outros sites não perdem uma requisição sequer.

---

## Se algo der errado

**`nginx -t` falha** — nada foi recarregado, os outros domínios seguem no ar.
Leia a mensagem, corrija, teste de novo.

**Reverter o site inteiro:**

```bash
sudo rm /etc/nginx/sites-enabled/benditoorganico.com.br
sudo nginx -t && sudo systemctl reload nginx
```

Os outros domínios voltam ao estado exato de antes.

**Erro 502 Bad Gateway** — o pool PHP-FPM não subiu:

```bash
sudo systemctl status php8.3-fpm --no-pager
ls -l /run/php/bendito-organico.sock
sudo tail -50 /var/log/php-fpm-bendito-organico.log
```

**Erro 500** — com `APP_DEBUG=false` o detalhe fica no log:

```bash
sudo -u bendito -H tail -50 /var/www/benditoorganico.com.br/storage/logs/laravel-*.log
sudo tail -30 /var/log/nginx/benditoorganico.error.log
```

**"database is locked" ou "readonly database"** — permissão da pasta
`database/`, não do arquivo (o SQLite precisa criar `-wal` e `-shm` ao lado):

```bash
sudo chown -R bendito:bendito /var/www/benditoorganico.com.br/database
sudo chmod 775 /var/www/benditoorganico.com.br/database
sudo chmod 664 /var/www/benditoorganico.com.br/database/database.sqlite
```

**Restaurar o banco** — o `deploy.sh` guarda os 10 backups mais recentes:

```bash
ls -lt /var/www/benditoorganico.com.br/storage/backups/
sudo systemctl stop bendito-queue
sudo -u bendito -H cp storage/backups/database-AAAAMMDD-HHMMSS.sqlite \
    database/database.sqlite
sudo systemctl start bendito-queue
```

**Mudou o `.env` e nada aconteceu** — em produção o `.env` fica em cache:

```bash
sudo -u bendito -H php artisan config:cache
sudo -u bendito -H php artisan queue:restart
```

---

## Pendências conhecidas

Estas ficaram de fora por decisão, não por esquecimento. O site sobe e funciona
sem elas, mas cada uma tem consequência:

**1. Mercado Pago sem credenciais.** Catálogo, carrinho e registro de pedido
funcionam; a etapa de **pagamento falha**. Para ativar:

```bash
sudo -u bendito -H nano .env    # MERCADOPAGO_ACCESS_TOKEN, _PUBLIC_KEY, _WEBHOOK_SECRET
sudo -u bendito -H php artisan config:cache
```

E cadastre no painel do Mercado Pago (Webhooks → Configurar notificações):

```
https://benditoorganico.com.br/webhooks/mercadopago
```

A "Assinatura secreta" que aparece nessa tela é o `MERCADOPAGO_WEBHOOK_SECRET`.
Sem ela, com `MERCADOPAGO_VERIFY_SIGNATURE=true`, o webhook rejeita toda
notificação — e o pedido nunca é marcado como pago.

**2. E-mail em `log`.** Nada é enviado de verdade. Na prática: a **recuperação
de senha do painel não funciona** (o link só aparece em
`storage/logs/laravel-*.log`) e o aviso de pedido pago não chega. Enquanto
estiver assim, crie e troque senhas de admin por
`php artisan tinker`. Para ligar o SMTP, o bloco já está comentado no `.env`.

**3. HSTS desligado.** A linha `Strict-Transport-Security` está comentada no
vhost. Ligue **depois** de confirmar que o HTTPS está estável por alguns dias:
um HSTS precipitado prende o domínio no navegador do cliente por um ano.

**4. Dados de contato de exemplo.** `STORE_PHONE`, `STORE_WHATSAPP` e
`STORE_CITY` ainda são placeholders e aparecem no rodapé do site.

**5. Backup fora da VPS.** O `deploy.sh` faz backup do banco antes de cada
deploy, mas no mesmo disco. Se a VPS morrer, o backup morre junto. Vale um
`rsync` diário de `storage/backups/` para fora.
