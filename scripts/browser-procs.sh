#!/usr/bin/env bash
# Elenca (o con --kill termina) i processi Playwright/Chromium/Pest rimasti nel container Sail.
# Uso: scripts/browser-procs.sh [--kill]

set -euo pipefail

cd "$(dirname "$0")/.."

# Le parentesi quadre evitano che grep trovi sé stesso.
PATTERN='[p]laywright|[c]hrome-headless|[v]endor/bin/pest'

if [[ "${1:-}" == "--kill" ]]; then
    vendor/bin/sail exec -T laravel.test bash -c \
        "ps -eo pid,args | grep -E '$PATTERN' | awk '{print \$1}' | xargs -r kill; sleep 1; echo \"Rimasti: \$(ps -eo args | grep -cE '$PATTERN')\""
else
    vendor/bin/sail exec -T laravel.test bash -c \
        "ps -eo pid,etime,args | grep -E '$PATTERN' | cut -c1-150 || echo 'Nessun processo appeso.'"
fi
