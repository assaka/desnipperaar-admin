#!/usr/bin/env bash
# DeSnipperaar admin — deploy / post-deploy hook.
# Idempotent: safe to run on every deploy.

set -euo pipefail

# Colors for humans reading the log
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
log() { echo -e "${GREEN}▶ $*${NC}"; }
warn() { echo -e "${YELLOW}! $*${NC}"; }

cd "$(dirname "$0")"

# 1. .env sanity
if [ ! -f .env ]; then
  warn ".env missing — copying from .env.example. Fill in secrets and redeploy."
  cp .env.example .env
fi

# 2. Composer (production deps only)
log "composer install"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# 3. App key (only if missing)
if ! grep -q '^APP_KEY=base64:' .env; then
  log "Generating APP_KEY"
  php artisan key:generate --force
fi

# 4. Front-end build (skip until Vite assets + package-lock.json exist)
if [ -f package-lock.json ]; then
  log "npm ci && npm run build"
  npm ci --silent
  npm run build
else
  warn "No package-lock.json — skipping front-end build"
fi

# 5. Storage symlink (no-op if already linked)
log "storage:link"
php artisan storage:link 2>/dev/null || true

# 6. DB migrations (safe on production — won't prompt)
log "artisan migrate --force"
php artisan migrate --force

# 7. Clear + warm caches
log "Caching config / routes / views / events"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Permissions for web user (defaults to www-data; override with WEB_USER env).
# Steps 6+7 above run as whoever invoked deploy.sh and write cache files as
# that user. If that's root and PHP-FPM runs as www-data, the next email
# render fails with EACCES because Mailer can't compile a new view template
# into storage/framework/views/. Chown unconditionally to keep cache writable.
WEB_USER="${WEB_USER:-www-data}"
log "chown ${WEB_USER} on storage + bootstrap/cache"
chown -R "${WEB_USER}:${WEB_USER}" storage bootstrap/cache || true
chmod -R ug+rwX storage bootstrap/cache || true

# 9. Restart queue workers (supervisord / systemd will pick up the signal)
log "queue:restart"
php artisan queue:restart

log "✅ Deploy complete"
