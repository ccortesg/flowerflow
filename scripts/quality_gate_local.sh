#!/usr/bin/env bash

set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

php artisan test
vendor/bin/pint --test
composer validate --strict --no-check-publish
composer check-platform-reqs --no-dev
composer audit --locked
set +e
corepack yarn audit --groups dependencies --level moderate
yarn_audit_status=$?
set -e
if [[ "$yarn_audit_status" -ne 0 && "$yarn_audit_status" -ne 2 ]]; then
    printf 'Yarn audit detectó vulnerabilidades moderadas, altas o críticas, o no pudo completarse.\n' >&2
    exit "$yarn_audit_status"
fi
scripts/build_frontend_production.sh
php artisan route:list
git diff --check
