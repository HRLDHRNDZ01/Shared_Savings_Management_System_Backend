#!/usr/bin/env bash
set -euo pipefail

composer install --no-dev --optimize-autoloader --no-interaction
php artisan package:discover --ansi
