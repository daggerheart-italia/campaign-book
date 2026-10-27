#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${DEPLOY_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BRANCH="${1:-main}"

if [ -f "${APP_DIR}/composer.phar" ]; then
    COMPOSER=(php "${APP_DIR}/composer.phar")
else
    COMPOSER=(composer)
fi

echo "⚔️ [Daggerheart Campaign Book] Deploy del branch '${BRANCH}' in ${APP_DIR}"

cd "${APP_DIR}"

echo "==> Attivazione modalità manutenzione"
php artisan down || true

# Se qualcosa fallisce, l'app torna online comunque
trap 'echo "==> Errore: disattivazione modalità manutenzione"; php artisan up || true' ERR

echo "==> Aggiornamento del codice"
git fetch origin
git reset --hard "origin/${BRANCH}"

echo "==> Dipendenze PHP di produzione"
"${COMPOSER[@]}" install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "==> Dipendenze Node"
npm ci --omit=dev

echo "==> Compilazione degli asset"
npm run build

echo "==> Migrazione del database"
php artisan migrate --force --no-interaction

echo "==> Pulizia e rigenerazione delle cache"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

echo "==> Disattivazione modalità manutenzione"
php artisan up

echo "✨ Deploy completato con successo! Il Grimorio è online."
