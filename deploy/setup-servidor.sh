#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Bendito Orgânico — provisionamento inicial da VPS (rodar UMA vez, como root)
#
#     sudo bash deploy/setup-servidor.sh
#
# SEGURANÇA PARA OS OUTROS DOMÍNIOS DA VPS:
#   * só CRIA arquivos novos; se um arquivo de destino já existe, o script
#     avisa e não sobrescreve;
#   * nunca edita nginx.conf, php.ini global nem qualquer vhost existente;
#   * roda "nginx -t" e "php-fpm -t" ANTES de qualquer reload — se a
#     configuração não validar, desfaz o que criou e aborta sem recarregar;
#   * usa reload (gracioso), nunca restart, no nginx e no PHP-FPM.
# ---------------------------------------------------------------------------

set -euo pipefail

DOMAIN="benditoorganico.com.br"
APP_USER="bendito"
APP_DIR="/var/www/$DOMAIN"
APP_HOME="/home/$APP_USER"
REPO="https://github.com/grupomartinscomunicacao/bendito-organico.git"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

log()  { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m /!\\ %s\033[0m\n' "$*"; }
die()  { printf '\n\033[1;31mERRO: %s\033[0m\n' "$*" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || die "rode como root (sudo)."

# Copia só se o destino não existir. Um arquivo já presente pode ser de outro
# site ou uma customização feita à mão — nunca é sobrescrito.
install_new() {
    local src="$1" dst="$2"
    if [ -e "$dst" ]; then
        warn "já existe, NÃO foi tocado: $dst"
        return 1
    fi
    install -m "${3:-644}" "$src" "$dst"
    echo "    criado: $dst"
}

# --- 0. Versão do PHP ------------------------------------------------------
PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || true)"
[ -n "$PHP_VERSION" ] || die "PHP não encontrado no PATH."
php -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' || die "Laravel 12 exige PHP >= 8.2 (achei $PHP_VERSION)."
log "PHP $PHP_VERSION detectado"

FPM_POOL_DIR="/etc/php/$PHP_VERSION/fpm/pool.d"
[ -d "$FPM_POOL_DIR" ] || die "pool.d não encontrado em $FPM_POOL_DIR — o PHP-FPM $PHP_VERSION está instalado?"

# --- 1. Extensões PHP obrigatórias -----------------------------------------
# sqlite3/pdo_sqlite: o banco. gd: a validação "dimensions" da foto de produto
# usa getimagesize(). fileinfo: checagem de MIME do upload. curl/openssl: a
# chamada ao Mercado Pago. mbstring: acentuação em pt-BR.
log "Conferindo extensões PHP"
MISSING=""
for ext in pdo_sqlite sqlite3 mbstring curl openssl fileinfo gd xml zip; do
    php -m | grep -qix "$ext" || MISSING="$MISSING php$PHP_VERSION-$ext"
done
if [ -n "$MISSING" ]; then
    die "faltam extensões PHP. Instale e rode de novo: sudo apt install -y$MISSING"
fi
echo "    todas presentes"

for bin in git composer node npm nginx certbot sqlite3; do
    command -v "$bin" >/dev/null 2>&1 || warn "'$bin' não encontrado no PATH."
done

# --- 2. Usuário do sistema -------------------------------------------------
# Usuário próprio, sem senha: isola este site dos outros domínios da VPS no
# nível do sistema de arquivos.
#
# O home fica FORA da pasta do site de propósito: o adduser cria o home (e em
# algumas versões copia o /etc/skel dentro dele), o que deixaria o destino do
# git clone não-vazio e faria o clone falhar. De quebra, os caches do composer
# e do npm ficam longe do diretório publicado.
log "Usuário $APP_USER"
if id "$APP_USER" >/dev/null 2>&1; then
    echo "    já existe"
else
    adduser --system --group --home "$APP_HOME" --shell /bin/bash --disabled-password "$APP_USER"
    echo "    criado"
fi
mkdir -p "$APP_HOME"
chown "$APP_USER:$APP_USER" "$APP_HOME"

# --- 3. Código -------------------------------------------------------------
log "Código em $APP_DIR"
if [ -d "$APP_DIR/.git" ]; then
    echo "    repositório já clonado"
else
    # Clona num diretório temporário e copia: se $APP_DIR já existir com algum
    # arquivo solto, o "git clone" direto falharia.
    if [ -d "$APP_DIR" ] && [ -n "$(ls -A "$APP_DIR" 2>/dev/null)" ]; then
        die "$APP_DIR já existe e não está vazio, mas não é um repositório git. Confira o que há ali antes de continuar."
    fi
    TMP_CLONE="$(mktemp -d)"
    git clone "$REPO" "$TMP_CLONE/app"
    mkdir -p "$APP_DIR"
    # O ponto-barra copia inclusive os arquivos ocultos (.git, .env.example).
    cp -a "$TMP_CLONE/app/." "$APP_DIR/"
    rm -rf "$TMP_CLONE"
    echo "    clonado"
fi
chown -R "$APP_USER:$APP_USER" "$APP_DIR"
# 755 no diretório: o nginx (www-data) precisa atravessá-lo para ler public/.
chmod 755 "$APP_DIR"

# --- 4. Pool PHP-FPM dedicado ----------------------------------------------
log "Pool PHP-FPM"
if install_new "$SRC/php-fpm/bendito-organico.conf" "$FPM_POOL_DIR/bendito-organico.conf"; then
    if "php-fpm$PHP_VERSION" -t; then
        systemctl reload "php$PHP_VERSION-fpm"
        echo "    PHP-FPM recarregado (gracioso — os outros pools não caíram)"
    else
        rm -f "$FPM_POOL_DIR/bendito-organico.conf"
        die "a configuração do PHP-FPM não validou. O arquivo foi removido e nada foi recarregado."
    fi
fi

# --- 5. Nginx: etapa 1, só HTTP para o certbot validar ---------------------
log "Nginx (etapa 1: HTTP para validação do certificado)"
NGINX_AVAIL="/etc/nginx/sites-available/$DOMAIN"
NGINX_ENABL="/etc/nginx/sites-enabled/$DOMAIN"

if [ ! -e "$NGINX_AVAIL" ]; then
    install -m 644 "$SRC/nginx/bootstrap-http.conf" "$NGINX_AVAIL"
    ln -sfn "$NGINX_AVAIL" "$NGINX_ENABL"
    if nginx -t; then
        systemctl reload nginx
        echo "    nginx recarregado"
    else
        rm -f "$NGINX_ENABL" "$NGINX_AVAIL"
        die "nginx -t falhou. Os arquivos novos foram removidos e o nginx NAO foi recarregado — os outros dominios seguem no ar."
    fi
else
    warn "já existe $NGINX_AVAIL — não foi tocado."
fi

# --- 6. Certificado --------------------------------------------------------
# "certonly --webroot" apenas emite o certificado: o certbot não edita nenhum
# vhost. É o que garante que a configuração dos outros domínios não é mexida.
log "Certificado Let's Encrypt"
if [ -d "/etc/letsencrypt/live/$DOMAIN" ]; then
    echo "    já emitido"
else
    mkdir -p "$APP_DIR/public/.well-known/acme-challenge"
    chown -R "$APP_USER:$APP_USER" "$APP_DIR/public/.well-known"
    certbot certonly --webroot -w "$APP_DIR/public" \
        -d "$DOMAIN" -d "www.$DOMAIN" \
        --non-interactive --agree-tos --register-unsafely-without-email \
        || die "emissão falhou. Confira se o DNS de $DOMAIN e www.$DOMAIN aponta para esta VPS."
fi

# A renovação automática já vem configurada pelo pacote do certbot (timer
# systemd). Como a emissão foi por webroot, a renovação também será — e o
# nginx precisa recarregar para passar a servir o certificado novo.
#
# O hook fica na pasta global de deploy hooks, mas sai na primeira linha se a
# renovação não for a DESTE domínio: a renovação dos certificados dos outros
# sites continua se comportando exatamente como antes.
mkdir -p /etc/letsencrypt/renewal-hooks/deploy
HOOK=/etc/letsencrypt/renewal-hooks/deploy/reload-nginx-bendito.sh
if [ ! -e "$HOOK" ]; then
    cat > "$HOOK" <<HOOKEOF
#!/bin/sh
[ "\$RENEWED_LINEAGE" = "/etc/letsencrypt/live/$DOMAIN" ] || exit 0
nginx -t && systemctl reload nginx
HOOKEOF
    chmod +x "$HOOK"
    echo "    hook de recarga pós-renovação criado"
fi

# --- 7. Nginx: etapa 2, vhost definitivo com HTTPS -------------------------
log "Nginx (etapa 2: vhost definitivo)"
install -m 644 "$SRC/nginx/$DOMAIN.conf" "$NGINX_AVAIL"
ln -sfn "$NGINX_AVAIL" "$NGINX_ENABL"

if nginx -t; then
    systemctl reload nginx
    echo "    nginx recarregado com o vhost definitivo"
else
    # Volta para a etapa 1, que já validava, e não recarrega nada quebrado.
    install -m 644 "$SRC/nginx/bootstrap-http.conf" "$NGINX_AVAIL"
    die "nginx -t falhou com o vhost definitivo. Restaurei o vhost HTTP e NAO recarreguei — nenhum dominio caiu."
fi

# --- 8. Serviços de fila e agendamento -------------------------------------
log "Serviços systemd"
install_new "$SRC/systemd/bendito-queue.service"     /etc/systemd/system/bendito-queue.service     || true
install_new "$SRC/systemd/bendito-scheduler.service" /etc/systemd/system/bendito-scheduler.service || true
install_new "$SRC/systemd/bendito-scheduler.timer"   /etc/systemd/system/bendito-scheduler.timer   || true

# O ExecStart aponta para /usr/bin/php; se o binário estiver noutro caminho,
# corrige aqui em vez de deixar o serviço falhar no boot.
PHP_BIN="$(command -v php)"
if [ "$PHP_BIN" != "/usr/bin/php" ]; then
    sed -i "s#/usr/bin/php#$PHP_BIN#" \
        /etc/systemd/system/bendito-queue.service \
        /etc/systemd/system/bendito-scheduler.service
    echo "    ExecStart ajustado para $PHP_BIN"
fi

touch /var/log/bendito-queue.log /var/log/bendito-scheduler.log
chown "$APP_USER:$APP_USER" /var/log/bendito-queue.log /var/log/bendito-scheduler.log
systemctl daemon-reload

log "Provisionamento concluído"
echo ""
echo "Falta fazer, nesta ordem (detalhes em deploy/README.md):"
echo ""
echo "  1. Enviar o .env e o banco SQLite da maquina local por scp (passos 4 e 5)."
echo "  2. Primeiro deploy:"
echo "         sudo -u $APP_USER -H bash -c \"cd $APP_DIR && ./deploy/deploy.sh\""
echo "  3. Ligar fila e agendador:"
echo "         sudo systemctl enable --now bendito-queue.service"
echo "         sudo systemctl enable --now bendito-scheduler.timer"
echo ""
echo "Conferir que os outros dominios seguem no ar:"
echo "         nginx -T | grep server_name"
echo "         systemctl status nginx php$PHP_VERSION-fpm --no-pager"
echo ""
