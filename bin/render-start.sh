#!/usr/bin/env bash
set -euo pipefail

# Render provides DATABASE_URL; Laravel reads DB_URL.
export DB_URL="${DB_URL:-${DATABASE_URL:-}}"
export DB_SSLMODE="${DB_SSLMODE:-require}"

echo "Starting SSMS API..."
echo "DB_CONNECTION=${DB_CONNECTION:-}"
echo "PORT=${PORT:-8000}"

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force
php artisan db:seed --class=SidebarAccessSeeder --force

USER_COUNT="$(php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); echo App\\Models\\User::query()->count();")"
if [[ "${USER_COUNT}" == "0" ]]; then
  echo "No users found. Running DatabaseSeeder..."
  php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache

php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
