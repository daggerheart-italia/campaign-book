#!/usr/bin/env bash
set -e

echo "⚔️ [Daggerheart Campaign Book] Avvio procedura di deploy..."

# 1. Modalità manutenzione
php artisan down || true

# 2. Aggiornamento codice da repository Git
git pull origin main

# 3. Dipendenze PHP di produzione
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Compilazione degli asset frontend di produzione (Tailwind CSS, Leaflet, Alpine.js, Marked)
npm ci
npm run build

# 5. Migrazione del database
php artisan migrate --force

# 6. Ottimizzazione delle configurazioni e delle rotte
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Disattivazione modalità manutenzione
php artisan up

echo "✨ Deploy completato con successo! Il Grimorio è online."
