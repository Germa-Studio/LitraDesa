#!/bin/bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  LitraDesa Initialization Script${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Function to print colored messages
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Check prerequisites
echo "Checking prerequisites..."

# Check Docker
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker Desktop first."
    echo "Visit: https://docs.docker.com/get-docker/"
    exit 1
fi
print_success "Docker found: $(docker --version)"

# Check Docker Compose
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose is not installed."
    exit 1
fi
print_success "Docker Compose found: $(docker-compose --version)"

# Check if Docker daemon is running
if ! docker info &> /dev/null; then
    print_error "Docker daemon is not running. Please start Docker Desktop."
    exit 1
fi
print_success "Docker daemon is running"

# Check if src directory already exists
if [ -d "src" ]; then
    print_error "The 'src' directory already exists!"
    echo "This script is for first-time initialization only."
    echo "If you want to start the existing project, use: ./setup.sh"
    exit 1
fi

echo ""
print_info "Creating Laravel 11 project..."
echo "This may take several minutes depending on your internet connection..."

# Create Laravel project
if ! composer create-project laravel/laravel:^11.0 src --no-interaction; then
    print_error "Failed to create Laravel project"
    exit 1
fi
print_success "Laravel 11 project created"

# Navigate to project directory
cd src

echo ""
print_info "Installing Inertia.js server-side adapter..."
if ! composer require inertiajs/inertia-laravel --no-interaction; then
    print_error "Failed to install Inertia.js"
    exit 1
fi
print_success "Inertia.js server-side installed"

echo ""
print_info "Installing Inertia.js client-side with React..."
if ! npm install @inertiajs/react react react-dom; then
    print_error "Failed to install React dependencies"
    exit 1
fi
print_success "React and Inertia.js client-side installed"

echo ""
print_info "Installing Laravel Breeze with Inertia + React stack..."
if ! composer require laravel/breeze --dev --no-interaction; then
    print_error "Failed to install Laravel Breeze"
    exit 1
fi

if ! php artisan breeze:install react --no-interaction; then
    print_error "Failed to install Breeze with React"
    exit 1
fi
print_success "Laravel Breeze with Inertia + React installed"

echo ""
print_info "Installing Laravel Reverb..."
if ! composer require laravel/reverb --no-interaction; then
    print_error "Failed to install Laravel Reverb"
    exit 1
fi

if ! php artisan reverb:install --no-interaction; then
    print_error "Failed to configure Reverb"
    exit 1
fi
print_success "Laravel Reverb installed"

echo ""
print_info "Configuring environment variables..."

# Update .env file with Docker-specific settings
cat > .env << 'EOF'
APP_NAME=LitraDesa
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta
APP_URL=http://localhost

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=litradesa
DB_USERNAME=litradesa_user
DB_PASSWORD=litradesa_password

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=litradesa
REVERB_APP_KEY=litradesa_key
REVERB_APP_SECRET=litradesa_secret
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_APP_NAME="${APP_NAME}"
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
EOF

# Generate application key
if ! php artisan key:generate --no-interaction; then
    print_error "Failed to generate application key"
    exit 1
fi
print_success "Environment configured and application key generated"

# Return to project root
cd ..

echo ""
print_info "Building Docker containers..."
echo "This will take 10-15 minutes on first build..."

if ! docker-compose build; then
    print_error "Failed to build Docker containers"
    exit 1
fi
print_success "Docker containers built successfully"

echo ""
print_info "Starting Docker containers..."

if ! docker-compose up -d; then
    print_error "Failed to start Docker containers"
    exit 1
fi
print_success "Docker containers started"

echo ""
print_info "Waiting for database to be ready..."
sleep 10

# Check if database is ready
MAX_RETRIES=30
RETRY_COUNT=0
while ! docker-compose exec -T db pg_isready -U litradesa_user -d litradesa &> /dev/null; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
        print_error "Database failed to start after $MAX_RETRIES attempts"
        docker-compose logs db
        exit 1
    fi
    echo "Waiting for database... (attempt $RETRY_COUNT/$MAX_RETRIES)"
    sleep 2
done
print_success "Database is ready"

echo ""
print_info "Running database migrations..."

if ! docker-compose exec -T app php artisan migrate --force; then
    print_error "Failed to run migrations"
    docker-compose logs app
    exit 1
fi
print_success "Database migrations completed"

echo ""
print_info "Installing NPM dependencies..."

if ! docker-compose exec -T node npm install; then
    print_error "Failed to install NPM dependencies"
    exit 1
fi
print_success "NPM dependencies installed"

echo ""
print_info "Building frontend assets..."

if ! docker-compose exec -T node npm run build; then
    print_error "Failed to build frontend assets"
    exit 1
fi
print_success "Frontend assets built"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  ✓ Initialization Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Your LitraDesa application is ready!"
echo ""
echo "Access your application at:"
echo -e "  ${GREEN}Web Application:${NC} http://localhost"
echo -e "  ${GREEN}WebSocket Server:${NC} ws://localhost:8080"
echo -e "  ${GREEN}Vite Dev Server:${NC} http://localhost:5173"
echo ""
echo "Useful commands:"
echo "  ${YELLOW}docker-compose ps${NC}        - Check container status"
echo "  ${YELLOW}docker-compose logs -f${NC}   - View logs"
echo "  ${YELLOW}docker-compose down${NC}      - Stop containers"
echo "  ${YELLOW}./setup.sh${NC}               - Quick start (next time)"
echo ""
echo "To run artisan commands:"
echo "  ${YELLOW}docker-compose exec app php artisan <command>${NC}"
echo ""
echo -e "${GREEN}Happy coding! 🚀${NC}"

# Made with Bob
