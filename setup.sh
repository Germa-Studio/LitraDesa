#!/bin/bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  LitraDesa Quick Start${NC}"
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

# Check if src directory exists
if [ ! -d "src" ]; then
    print_error "The 'src' directory does not exist!"
    echo "This script is for starting an existing project."
    echo "For first-time setup, run: ./init.sh"
    exit 1
fi

# Check if .env exists
if [ ! -f "src/.env" ]; then
    print_error "The .env file does not exist in src/ directory!"
    echo "Please run ./init.sh for first-time initialization."
    exit 1
fi

print_info "Starting Docker containers..."

if ! docker-compose up -d; then
    print_error "Failed to start Docker containers"
    exit 1
fi
print_success "Docker containers started"

echo ""
print_info "Waiting for database to be ready..."
sleep 5

# Check if database is ready
MAX_RETRIES=15
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
print_info "Running pending migrations..."

if docker-compose exec -T app php artisan migrate --force &> /dev/null; then
    print_success "Migrations completed"
else
    print_info "No pending migrations or migrations failed (check logs if needed)"
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  ✓ LitraDesa is Ready!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Your application is running at:"
echo -e "  ${GREEN}Web Application:${NC} http://localhost"
echo -e "  ${GREEN}WebSocket Server:${NC} ws://localhost:8080"
echo -e "  ${GREEN}Vite Dev Server:${NC} http://localhost:5173"
echo ""
echo "Container status:"
docker-compose ps
echo ""
echo "Useful commands:"
echo "  ${YELLOW}docker-compose logs -f${NC}        - View logs"
echo "  ${YELLOW}docker-compose down${NC}           - Stop containers"
echo "  ${YELLOW}docker-compose restart <service>${NC} - Restart a service"
echo ""
echo -e "${GREEN}Happy coding! 🚀${NC}"

# Made with Bob
