#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Bendito Orgânico — deploy / atualização
#
# Uso — conecte como o usuário de deploy, NÃO como root:
#     ssh bendito-organico@SEU_IP
#     cd /var/www/benditoorganico.com.br
#     ./deploy/deploy.sh
#
# É idempotente: pode rodar quantas vezes quiser. Não toca em nada fora da
# pasta do site — nginx, PHP-FPM e os outros domínios da VPS ficam intactos.
# Não precisa de sudo em nenhum passo.
# ---------------------------------------------------------------------------

set -euo pipefail

APP_USER="bendito-organico"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

BACKUP_DIR="$APP_DIR/storage/backups"
STAMP="$(date +%Y%m%d-%H%M%S)"

log()  { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
warn() { printf '\n\033[1;33m /!\ %s\033[0m\n' "$*"; }
die()  { printf '\n\033[1;31m ERRO: %s\033[0m\n' "$*" >&2; exit 1; }

# --- Pré-condições ---------------------------------------------------------
[ -f "$APP_DIR/artisan" ] || die "não parece a raiz do Laravel: $APP_DIR"
[ -f "$APP_DIR/.env" ]    || die ".env não existe. Copie de deploy/env.production.example primeiro."

# Deploy como root é o erro que mais dá trabalho para desfazer: composer, npm e
# artisan criam arquivo pertencendo a root em vendor/, node_modules/, storage/ e
# bootstrap/cache/, e aí o PHP-FPM — que roda como $APP_USER — passa a receber
# "permission denied" em produção. Por isso o script recusa em vez de avisar.
if [ "$(id -u)" -eq 0 ]; then
    die "não rode o deploy como root.
    Conecte como o usuário de deploy:  ssh $APP_USER@SEU_IP
    Ou, se já está no root:            sudo -u $APP_USER -H bash -c 'cd $APP_DIR && ./deploy/deploy.sh'"
fi

# Rodar como um usuário que não é o dono dá no mesmo problema, ao contrário:
# os arquivos novos saem com o dono errado e o PHP-FPM não escreve neles.
OWNER="$(stat -c '%U' "$APP_DIR/artisan")"
if [ "$(id -un)" != "$OWNER" ]; then
    die "você é '$(id -un)', mas os arquivos do site pertencem a '$OWNER'.
    Rode o deploy como '$OWNER', senão os arquivos gerados ficam com o dono errado."
fi

grep -q '^APP_ENV=production' .env || warn "APP_ENV não está como production."
grep -q '^APP_DEBUG=false'     .env || warn "APP_DEBUG não está false — corrija antes de abrir ao público."

# --- Sempre sai da manutenção, mesmo se algo falhar no meio ----------------
restore_up() {
    local code=$?
    if [ -f "$APP_DIR/storage/framework/down" ]; then
        # Se o artisan não roda mais (vendor quebrado no meio do deploy),
        # apagar o arquivo de flag já tira o site da manutenção.
        php artisan up >/dev/null 2>&1 || rm -f "$APP_DIR/storage/framework/down"
        warn "site retirado da manutenção após falha."
    fi
    if [ "$code" -ne 0 ]; then
        printf '\n\033[1;31mDeploy interrompido (código %s). O site continua na versão anterior.\033[0m\n' "$code"
    fi
    exit "$code"
}
trap restore_up EXIT

# --- 1. Backup do banco ANTES de qualquer migration ------------------------
# É o único passo irreversível do deploy, então vem primeiro.
log "Backup do banco"
mkdir -p "$BACKUP_DIR"
if [ -f database/database.sqlite ]; then
    if command -v sqlite3 >/dev/null 2>&1; then
        # .backup é consistente com WAL ligado; um "cp" simples pode capturar
        # o arquivo no meio de uma escrita e sair corrompido.
        sqlite3 database/database.sqlite ".backup '$BACKUP_DIR/database-$STAMP.sqlite'"
    else
        warn "sqlite3 não instalado — usando cp (menos seguro com WAL). Instale: sudo apt install sqlite3"
        cp database/database.sqlite "$BACKUP_DIR/database-$STAMP.sqlite"
    fi
    echo "    -> $BACKUP_DIR/database-$STAMP.sqlite"
    # Mantém os 10 backups mais recentes; o resto sai para não encher o disco.
    ls -1t "$BACKUP_DIR"/database-*.sqlite 2>/dev/null | tail -n +11 | xargs -r rm -f
else
    warn "database/database.sqlite não existe ainda (primeiro deploy?)."
fi

# --- 2. Manutenção ---------------------------------------------------------
log "Entrando em manutenção"
php artisan down --retry=15 || warn "não foi possível entrar em manutenção (primeiro deploy?)"

# --- 3. Código -------------------------------------------------------------
log "Atualizando o código"
git fetch --prune origin
git checkout master
git reset --hard origin/master

# --- 4. Dependências PHP ---------------------------------------------------
# --no-dev tira phpunit, faker e afins: menos código em produção, menos
# superfície de ataque. --optimize-autoloader gera o classmap.
log "Dependências PHP"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# --- 5. Assets -------------------------------------------------------------
# public/build é ignorado pelo git, então precisa ser gerado aqui.
log "Compilando assets (Vite)"
npm ci --no-audit --no-fund
npm run build

# --- 6. Banco --------------------------------------------------------------
log "Migrations"
php artisan migrate --force

# --- 7. Link do storage ----------------------------------------------------
# public/storage -> storage/app/public, onde ficam as fotos enviadas pelo painel.
log "Link do storage"
[ -L public/storage ] || php artisan storage:link

# --- 8. Caches -------------------------------------------------------------
# Reconstruir e não só limpar: um cache limpo deixa a primeira visita lenta,
# e "config:cache" é o que faz o Laravel parar de ler o .env a cada request.
log "Reconstruindo caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# --- 9. Fila ---------------------------------------------------------------
# O worker guarda o código antigo em memória; sem isto ele continuaria
# rodando a versão anterior. queue:restart é gracioso: termina o job atual,
# sai, e o systemd sobe de novo.
log "Reiniciando o worker de fila"
php artisan queue:restart

# --- 10. Permissões --------------------------------------------------------
log "Ajustando permissões de escrita"
chmod -R ug+rwX storage bootstrap/cache
if [ -f database/database.sqlite ]; then
    chmod 664 database/database.sqlite
fi
# O SQLite precisa escrever os arquivos -wal e -shm AO LADO do banco, então a
# pasta também tem que ser gravável — não só o arquivo.
chmod 775 database

# --- 11. Fora da manutenção ------------------------------------------------
log "Saindo da manutenção"
php artisan up

trap - EXIT

log "Deploy concluído"
echo "    Versão:  $(git rev-parse --short HEAD) — $(git log -1 --pretty=%s)"
echo "    Saúde:   curl -sI https://benditoorganico.com.br/up"
