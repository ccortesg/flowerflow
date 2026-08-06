#!/usr/bin/env bash

set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

php artisan test
vendor/bin/pint --test
composer validate --strict --no-check-publish
composer check-platform-reqs --no-dev
composer audit --locked
corepack yarn audit --groups dependencies --level moderate
scripts/build_frontend_production.sh
php artisan route:list
git diff --check
