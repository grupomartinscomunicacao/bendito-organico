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

# Minúsculas de propósito: o adduser do Debian/Ubuntu valida o nome contra
# NAME_REGEX ("^[a-z][-a-z0-9_]*$") e recusa maiúsculas sem --allow-bad-names.
# Nome com maiúscula também dá atrito com ferramentas que normalizam o case.
APP_USER="bendito-organico"
APP_DIR="/var/www/$DOMAIN"
APP_HOME="/home/$APP_USER"

# Chave pública SSH que poderá entrar como $APP_USER. Se ficar vazia, o script
# reaproveita a que já autentica o root — assim você entra com a MESMA chave
# que usa hoje, só que num usuário sem privilégio.
#   SSH_PUBKEY="ssh-ed25519 AAAA... voce@maquina" sudo -E bash deploy/setup-servidor.sh
SSH_PUBKEY="${SSH_PUBKEY:-}"
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

# --- 2. Usuário de deploy --------------------------------------------------
# Um único usuário faz três papéis: dono dos arquivos, dono do processo
# PHP-FPM e conta de SSH para rodar o deploy. É o mesmo modelo do Laravel
# Forge, e é o que evita o problema clássico de fazer deploy como root —
# arquivo criado por root que o PHP-FPM depois não consegue escrever.
#
# --disabled-password: não existe senha para essa conta, só chave SSH. Como
# consequência o sudo dela precisa ser NOPASSWD (ver passo 2b), e por isso a
# lista de comandos permitidos é curta e específica.
#
# O home fica FORA da pasta do site de propósito: o adduser cria o home (e em
# algumas versões copia o /etc/skel dentro dele), o que deixaria o destino do
# git clone não-vazio e faria o clone falhar. De quebra, os caches do composer
# e do npm ficam longe do diretório publicado.
log "Usuário $APP_USER"
if id "$APP_USER" >/dev/null 2>&1; then
    echo "    já existe"
else
    adduser --disabled-password --gecos "Deploy Bendito Organico" \
        --home "$APP_HOME" --shell /bin/bash "$APP_USER"
    echo "    criado"
fi
mkdir -p "$APP_HOME"
chown "$APP_USER:$APP_USER" "$APP_HOME"
chmod 750 "$APP_HOME"

# --- 2a. Chave SSH ---------------------------------------------------------
# Sem chave, a conta existe mas ninguém entra nela (não há senha).
log "Chave SSH de $APP_USER"
AUTH_DIR="$APP_HOME/.ssh"
AUTH_FILE="$AUTH_DIR/authorized_keys"
mkdir -p "$AUTH_DIR"

if [ -n "$SSH_PUBKEY" ]; then
    KEY_SOURCE="a variável SSH_PUBKEY"
    printf '%s\n' "$SSH_PUBKEY" > "$AUTH_FILE.novo"
elif [ -s /root/.ssh/authorized_keys ]; then
    KEY_SOURCE="/root/.ssh/authorized_keys"
    cp /root/.ssh/authorized_keys "$AUTH_FILE.novo"
else
    KEY_SOURCE=""
fi

if [ -n "$KEY_SOURCE" ]; then
    # Acrescenta sem duplicar, para não apagar chave que já esteja ali.
    touch "$AUTH_FILE"
    cat "$AUTH_FILE" "$AUTH_FILE.novo" | grep -v '^[[:space:]]*$' | sort -u > "$AUTH_FILE.merged"
    mv "$AUTH_FILE.merged" "$AUTH_FILE"
    rm -f "$AUTH_FILE.novo"
    echo "    chave(s) instalada(s) a partir de $KEY_SOURCE"
else
    warn "nenhuma chave SSH encontrada. A conta $APP_USER existe mas NÃO aceita login."
    warn "    Rode isto da sua máquina para liberar o acesso:"
    warn "    ssh-copy-id -i ~/.ssh/id_ed25519.pub $APP_USER@SEU_IP"
    warn "    (ou rode este script com SSH_PUBKEY=\"ssh-ed25519 AAAA...\")"
fi

# O sshd recusa a chave se as permissões estiverem frouxas.
chown -R "$APP_USER:$APP_USER" "$AUTH_DIR"
chmod 700 "$AUTH_DIR"
if [ -f "$AUTH_FILE" ]; then
    chmod 600 "$AUTH_FILE"
fi

# --- 2b. sudo restrito -----------------------------------------------------
# O deploy em si (deploy/deploy.sh) NÃO precisa de sudo: mexe só na pasta do
# site e reinicia a fila via "artisan queue:restart". O sudo aqui é para as
# operações de manutenção — e é limitado a comandos nominais.
#
# Isto NÃO é escalada de privilégio: as units systemd são de root e rodam com
# User=$APP_USER, então reiniciá-las não executa nada como root.
#
# O que deliberadamente NÃO está liberado: editar configuração do nginx,
# mexer em certificado, ou qualquer comando sobre serviços de outro domínio.
log "Regra de sudo restrita"
SUDOERS=/etc/sudoers.d/bendito-organico
if [ -e "$SUDOERS" ]; then
    warn "já existe, NÃO foi tocado: $SUDOERS"
else
    SYSTEMCTL="$(command -v systemctl)"
    # O rascunho é montado FORA de /etc/sudoers.d: nada entra em vigor antes
    # de o visudo aprovar, e uma falha no meio não deixa lixo lá dentro.
    SUDO_TMP="$(mktemp)"
    cat > "$SUDO_TMP" <<SUDOEOF
# Gerado por deploy/setup-servidor.sh — permissões mínimas do usuário de deploy.
#
# Só as unidades DESTE site, e só start/stop/restart. Nenhum curinga: cada
# comando é literal, então não há como passar argumento extra.
#
# "status" e "journalctl" ficaram DE FORA de propósito: os dois abrem um pager
# e, de dentro do less, dá para escapar para um shell — que aqui seria root.
# Nenhum dos dois precisa de sudo mesmo: "systemctl status bendito-queue"
# funciona sem privilégio, e o log do worker está em /var/log/bendito-queue.log,
# que pertence a $APP_USER.
#
# Uma regra por linha em vez de uma lista com continuação: regras do mesmo
# usuário se somam, e assim não há barra invertida para escapar errado.
$APP_USER ALL=(root) NOPASSWD: $SYSTEMCTL start bendito-queue.service
$APP_USER ALL=(root) NOPASSWD: $SYSTEMCTL stop bendito-queue.service
$APP_USER ALL=(root) NOPASSWD: $SYSTEMCTL restart bendito-queue.service
$APP_USER ALL=(root) NOPASSWD: $SYSTEMCTL start bendito-scheduler.timer
$APP_USER ALL=(root) NOPASSWD: $SYSTEMCTL stop bendito-scheduler.timer
$APP_USER ALL=(root) NOPASSWD: $SYSTEMCTL restart bendito-scheduler.timer
SUDOEOF
    # visudo -c valida ANTES de o arquivo entrar em vigor. Um sudoers inválido
    # em /etc/sudoers.d quebra o sudo do servidor inteiro, root incluído.
    if visudo -c -f "$SUDO_TMP" >/dev/null; then
        install -m 440 -o root -g root "$SUDO_TMP" "$SUDOERS"
        rm -f "$SUDO_TMP"
        echo "    criado: $SUDOERS"
    else
        rm -f "$SUDO_TMP"
        die "a regra de sudo não validou. Nada foi instalado — o sudo do servidor segue intacto."
    fi
fi

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

# --- 9. Fila e agendador ligados agora -------------------------------------
# Habilitar aqui, ainda como root, evita ter de voltar ao root depois: o
# usuario de deploy so tem sudo para start/stop/restart, nao para "enable".
log "Habilitando fila e agendador"
systemctl enable --now bendito-queue.service   >/dev/null 2>&1 || \
    warn "bendito-queue nao subiu ainda — normal antes do primeiro deploy."
systemctl enable --now bendito-scheduler.timer >/dev/null 2>&1 || true
echo "    habilitados no boot"

log "Provisionamento concluído"
echo ""
echo "IMPORTANTE: a partir daqui NAO use mais o root para o deploy."
echo ""
echo "  1. Teste o acesso do usuario de deploy, numa OUTRA aba do terminal"
echo "     (nao feche esta sessao root ate confirmar que funciona):"
echo ""
echo "         ssh $APP_USER@SEU_IP"
echo ""
echo "  2. Ja como $APP_USER, envie o .env e o banco SQLite da maquina local"
echo "     e rode o primeiro deploy (passos 3 a 6 do deploy/README.md):"
echo ""
echo "         cd $APP_DIR"
echo "         cp deploy/env.production.example .env"
echo "         php artisan key:generate"
echo "         ./deploy/deploy.sh"
echo ""
echo "  3. Reinicie a fila para ela pegar o codigo novo:"
echo ""
echo "         sudo systemctl restart bendito-queue.service"
echo ""
echo "Conferir que os outros dominios seguem no ar:"
echo "         nginx -T | grep server_name"
echo "         systemctl status nginx php$PHP_VERSION-fpm --no-pager"
echo ""
