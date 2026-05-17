#!/usr/bin/env sh

set -eu

PROJECT_ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$PROJECT_ROOT"

DEPLOY_EDGE=${DEPLOY_EDGE:-caddy}
COMPOSE_FILES="-f docker-compose.prod.yml"
EDGE_SERVICES="caddy"

case "$DEPLOY_EDGE" in
    caddy)
        ;;
    ingress)
        COMPOSE_FILES="$COMPOSE_FILES -f docker-compose.ingress.yml"
        EDGE_SERVICES=""
        ;;
    *)
        echo "Unsupported DEPLOY_EDGE '$DEPLOY_EDGE'. Use 'caddy' or 'ingress'."
        exit 1
        ;;
esac

COMPOSE="docker compose --env-file src/.env $COMPOSE_FILES --profile build"

if [ ! -f src/.env ]; then
    echo "Missing src/.env. Copy src/.env.production.example to src/.env and edit it first."
    exit 1
fi

read_env() {
    grep -E "^$1=" src/.env | tail -n 1 | cut -d '=' -f 2- | tr -d "\"'"
}

APP_DOMAIN=$(read_env APP_DOMAIN)
DB_USERNAME=$(read_env DB_USERNAME)
DB_DATABASE=$(read_env DB_DATABASE)

if [ -z "$APP_DOMAIN" ] || [ "$APP_DOMAIN" = "litradesa.example.com" ]; then
    echo "Set APP_DOMAIN in src/.env to your real domain before deploying."
    exit 1
fi

echo "Building production containers..."
$COMPOSE build app web node

echo "Installing PHP dependencies..."
$COMPOSE run --rm --no-deps app composer install --no-dev --optimize-autoloader --no-interaction

if grep -Eq '^APP_KEY=$' src/.env; then
    echo "Generating Laravel application key..."
    $COMPOSE run --rm --no-deps app php artisan key:generate --force
fi

echo "Installing frontend dependencies..."
$COMPOSE run --rm --no-deps node npm ci

echo "Building frontend assets..."
$COMPOSE run --rm --no-deps node npm run build
rm -f src/public/hot

echo "Starting data services..."
$COMPOSE up -d db redis

echo "Waiting for PostgreSQL..."
tries=0
until $COMPOSE exec -T db pg_isready -U "${DB_USERNAME:-litradesa_user}" -d "${DB_DATABASE:-litradesa}" >/dev/null 2>&1; do
    tries=$((tries + 1))
    if [ "$tries" -ge 30 ]; then
        echo "PostgreSQL did not become ready in time."
        $COMPOSE logs db
        exit 1
    fi
    sleep 2
done

echo "Starting application services..."
if [ "$DEPLOY_EDGE" = "ingress" ]; then
    docker stop litradesa_caddy >/dev/null 2>&1 || true
    $COMPOSE stop caddy >/dev/null 2>&1 || true
fi

$COMPOSE up -d app web reverb $EDGE_SERVICES

if [ "$DEPLOY_EDGE" = "ingress" ]; then
    docker stop litradesa_caddy >/dev/null 2>&1 || true
    $COMPOSE stop caddy >/dev/null 2>&1 || true
fi

echo "Preparing Laravel writable directories..."
$COMPOSE exec -T app sh -lc 'mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache'
$COMPOSE exec -T app sh -lc 'chown -R www-data:www-data storage bootstrap/cache || true'
$COMPOSE exec -T app sh -lc 'chmod -R ug+rwX storage bootstrap/cache'

echo "Running Laravel deployment commands..."
$COMPOSE exec -T app php artisan storage:link || true
$COMPOSE exec -T app php artisan migrate --force
$COMPOSE exec -T app php artisan optimize:clear
$COMPOSE exec -T app php artisan config:cache
$COMPOSE exec -T app php artisan route:cache
$COMPOSE exec -T app php artisan view:cache

if [ "$DEPLOY_EDGE" = "ingress" ]; then
    echo "Deployment complete for external ingress: http://${WEB_HOST_BIND:-0.0.0.0}:${WEB_HOST_PORT:-18080}"
else
    echo "Deployment complete: https://$APP_DOMAIN"
fi
$COMPOSE ps
