#!/usr/bin/env bash
set -euo pipefail

php artisan config:cache
php artisan route:cache
php artisan migrate --force
php artisan db:seed --class=SidebarAccessSeeder --force

# First deploy only: create default admin/user if DB is empty
USER_COUNT="$(php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); echo App\\Models\\User::query()->count();")"
if [[ "${USER_COUNT}" == "0" ]]; then
  php artisan db:seed --force
fi

php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
