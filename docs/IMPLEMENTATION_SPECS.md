# LitraDesa Implementation Specifications

## File-by-File Implementation Guide

This document provides detailed specifications for each configuration file, Dockerfile, and script needed to initialize the LitraDesa project.

---

## 1. Docker Configuration Files

### 1.1 docker-compose.yml (Development)

**Location**: `/docker-compose.yml`

**Purpose**: Main Docker Compose configuration for development environment

**Services Configuration**:

```yaml
version: '3.8'

services:
  # PostgreSQL Database
  db:
    image: postgres:16-alpine
    container_name: litradesa_db
    environment:
      POSTGRES_DB: litradesa
      POSTGRES_USER: litradesa_user
      POSTGRES_PASSWORD: litradesa_password
    volumes:
      - litradesa_db_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    networks:
      - litradesa_network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U litradesa_user -d litradesa"]
      interval: 10s
      timeout: 5s
      retries: 5

  # Redis Cache
  redis:
    image: redis:7-alpine
    container_name: litradesa_redis
    ports:
      - "6379:6379"
    volumes:
      - litradesa_redis_data:/data
    networks:
      - litradesa_network
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

  # Laravel Application
  app:
    build:
      context: .
      dockerfile: docker/app/Dockerfile
      target: development
    container_name: litradesa_app
    working_dir: /var/www/html
    volumes:
      - ./src:/var/www/html
      - ./docker/app/php.ini:/usr/local/etc/php/conf.d/custom.ini
    environment:
      - DB_HOST=db
      - DB_PORT=5432
      - DB_DATABASE=litradesa
      - DB_USERNAME=litradesa_user
      - DB_PASSWORD=litradesa_password
      - REDIS_HOST=redis
      - REDIS_PORT=6379
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      - litradesa_network

  # Nginx Web Server
  web:
    build:
      context: .
      dockerfile: docker/web/Dockerfile
    container_name: litradesa_web
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./src:/var/www/html
      - ./docker/web/nginx.conf:/etc/nginx/nginx.conf
      - ./docker/web/conf.d:/etc/nginx/conf.d
    depends_on:
      - app
    networks:
      - litradesa_network

  # Laravel Reverb WebSocket Server
  reverb:
    build:
      context: .
      dockerfile: docker/reverb/Dockerfile
    container_name: litradesa_reverb
    working_dir: /var/www/html
    volumes:
      - ./src:/var/www/html
    ports:
      - "8080:8080"
    environment:
      - REVERB_HOST=0.0.0.0
      - REVERB_PORT=8080
    depends_on:
      - app
      - redis
    networks:
      - litradesa_network
    command: php artisan reverb:start --host=0.0.0.0 --port=8080

  # Node.js for Frontend Development
  node:
    build:
      context: .
      dockerfile: docker/node/Dockerfile
    container_name: litradesa_node
    working_dir: /var/www/html
    volumes:
      - ./src:/var/www/html
      - /var/www/html/node_modules
    ports:
      - "5173:5173"
    environment:
      - VITE_REVERB_HOST=localhost
      - VITE_REVERB_PORT=8080
    command: npm run dev -- --host 0.0.0.0
    networks:
      - litradesa_network

networks:
  litradesa_network:
    driver: bridge

volumes:
  litradesa_db_data:
  litradesa_redis_data:
```

**Key Features**:
- Health checks for database and Redis
- Volume mounts for hot reload
- Named volumes for data persistence
- Bridge network for inter-container communication
- Environment variables for configuration

---

### 1.2 docker-compose.prod.yml (Production Overrides)

**Location**: `/docker-compose.prod.yml`

**Purpose**: Production-specific overrides for docker-compose.yml

**Key Differences**:
- No volume mounts (use built images)
- No node service (assets pre-built)
- Optimized PHP configuration
- SSL/TLS enabled
- Resource limits
- Restart policies

```yaml
version: '3.8'

services:
  app:
    build:
      target: production
    volumes: []  # Remove volume mounts
    restart: unless-stopped
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G

  web:
    restart: unless-stopped
    volumes:
      - ./src/public:/var/www/html/public:ro
    deploy:
      resources:
        limits:
          cpus: '1'
          memory: 512M

  db:
    restart: unless-stopped
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G

  redis:
    restart: unless-stopped
    command: redis-server --appendonly yes --requirepass ${REDIS_PASSWORD}
    deploy:
      resources:
        limits:
          cpus: '0.5'
          memory: 512M

  reverb:
    restart: unless-stopped
    deploy:
      resources:
        limits:
          cpus: '1'
          memory: 512M
```

---

## 2. Dockerfiles

### 2.1 docker/app/Dockerfile (Laravel Application)

**Purpose**: Multi-stage Dockerfile for Laravel application

**Stages**:
1. **base**: Common dependencies
2. **development**: Development tools and configurations
3. **production**: Optimized for production

```dockerfile
# Base stage with common dependencies
FROM php:8.3-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    curl \
    git \
    unzip

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        mbstring \
        exif \
        pcntl \
        bcmath

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Development stage
FROM base AS development

# Install Xdebug for debugging
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Copy custom PHP configuration
COPY docker/app/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Production stage
FROM base AS production

# Copy application files
COPY src /var/www/html

# Install dependencies (no dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Optimize Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Enable OPcache
RUN docker-php-ext-install opcache
COPY docker/app/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
```

---

### 2.2 docker/app/php.ini (PHP Configuration)

**Purpose**: Custom PHP settings for Laravel

```ini
[PHP]
; Performance
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
post_max_size = 100M
upload_max_filesize = 100M

; Error reporting (development)
display_errors = On
display_startup_errors = On
error_reporting = E_ALL

; Date/Time
date.timezone = Asia/Jakarta

; Session
session.gc_maxlifetime = 1440
session.cookie_httponly = On
session.cookie_secure = Off

; OPcache (will be overridden in production)
opcache.enable = 0
```

---

### 2.3 docker/app/opcache.ini (Production OPcache)

**Purpose**: OPcache configuration for production

```ini
[opcache]
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
opcache.fast_shutdown = 1
opcache.enable_cli = 1
opcache.validate_timestamps = 0
```

---

### 2.4 docker/web/Dockerfile (Nginx)

**Purpose**: Nginx web server for serving Laravel

```dockerfile
FROM nginx:alpine

# Copy custom nginx configuration
COPY docker/web/nginx.conf /etc/nginx/nginx.conf
COPY docker/web/conf.d/default.conf /etc/nginx/conf.d/default.conf

# Create necessary directories
RUN mkdir -p /var/www/html/public

# Set permissions
RUN chown -R nginx:nginx /var/www/html

EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]
```

---

### 2.5 docker/web/nginx.conf (Nginx Main Config)

**Purpose**: Main Nginx configuration

```nginx
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
    use epoll;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';

    access_log /var/log/nginx/access.log main;

    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    client_max_body_size 100M;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript 
               application/json application/javascript application/xml+rss 
               application/rss+xml font/truetype font/opentype 
               application/vnd.ms-fontobject image/svg+xml;

    include /etc/nginx/conf.d/*.conf;
}
```

---

### 2.6 docker/web/conf.d/default.conf (Laravel Site Config)

**Purpose**: Laravel-specific Nginx site configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name localhost;
    root /var/www/html/public;

    index index.php index.html;

    charset utf-8;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    error_log /var/log/nginx/laravel_error.log;
    access_log /var/log/nginx/laravel_access.log;
}
```

---

### 2.7 docker/reverb/Dockerfile (Laravel Reverb)

**Purpose**: WebSocket server for real-time features

```dockerfile
FROM php:8.3-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    curl

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    pcntl \
    sockets

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY src /var/www/html

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 8080

CMD ["php", "artisan", "reverb:start", "--host=0.0.0.0", "--port=8080"]
```

---

### 2.8 docker/node/Dockerfile (Node.js Development)

**Purpose**: Frontend asset compilation with Vite

```dockerfile
FROM node:20-alpine

WORKDIR /var/www/html

# Install dependencies for node-gyp
RUN apk add --no-cache python3 make g++

# Copy package files
COPY src/package*.json ./

# Install dependencies
RUN npm ci

# Expose Vite dev server port
EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
```

---

## 3. Initialization Scripts

### 3.1 init.sh (Complete Project Setup)

**Location**: `/init.sh`

**Purpose**: First-time project initialization

**Steps**:
1. Check prerequisites
2. Create Laravel project
3. Install Inertia.js and React
4. Install Laravel Reverb
5. Configure environment
6. Create Docker files
7. Build and start containers
8. Run migrations and seeders

**Key Commands**:
```bash
#!/bin/bash

# Check Docker
docker --version || exit 1

# Create Laravel project
composer create-project laravel/laravel:^11.0 src

# Install Inertia.js
cd src
composer require inertiajs/inertia-laravel
npm install @inertiajs/react react react-dom

# Install Breeze with Inertia
composer require laravel/breeze --dev
php artisan breeze:install react

# Install Reverb
composer require laravel/reverb

# Configure environment
cp .env.example .env
php artisan key:generate

# Build Docker containers
cd ..
docker-compose build
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --seed
```

---

### 3.2 setup.sh (Quick Start)

**Location**: `/setup.sh`

**Purpose**: Start existing project

**Steps**:
1. Check environment
2. Start containers
3. Wait for database
4. Run pending migrations

```bash
#!/bin/bash

# Check if .env exists
if [ ! -f "src/.env" ]; then
    echo "Error: .env file not found. Run init.sh first."
    exit 1
fi

# Start containers
docker-compose up -d

# Wait for database
echo "Waiting for database..."
sleep 10

# Run migrations
docker-compose exec app php artisan migrate

echo "LitraDesa is ready!"
echo "Web: http://localhost"
echo "Reverb: ws://localhost:8080"
```

---

## 4. Environment Configuration

### 4.1 .env.example

**Location**: `/src/.env.example`

**Purpose**: Template for environment variables

```env
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
```

---

## 5. Additional Configuration Files

### 5.1 .dockerignore

**Location**: `/.dockerignore`

```
.git
.gitignore
.env
.env.*
node_modules
vendor
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
bootstrap/cache/*
.DS_Store
Thumbs.db
```

---

### 5.2 .gitignore Updates

**Location**: `/.gitignore`

```
# Docker
docker-compose.override.yml

# Environment
.env
.env.backup
.env.production

# IDE
.vscode/
.idea/

# OS
.DS_Store
Thumbs.db
```

---

## Implementation Order

1. **Phase 1: Docker Infrastructure**
   - Create docker/ directory structure
   - Write all Dockerfiles
   - Create docker-compose.yml
   - Create docker-compose.prod.yml

2. **Phase 2: Configuration Files**
   - PHP configuration (php.ini, opcache.ini)
   - Nginx configuration (nginx.conf, default.conf)
   - Environment template (.env.example)

3. **Phase 3: Initialization Scripts**
   - Write init.sh
   - Write setup.sh
   - Make scripts executable

4. **Phase 4: Documentation**
   - Create comprehensive README.md
   - Add troubleshooting guide
   - Document deployment process

5. **Phase 5: Testing**
   - Test init.sh on clean system
   - Verify all services start correctly
   - Test hot reload functionality
   - Verify database connectivity

---

## Success Criteria

✅ All Docker containers start without errors
✅ Laravel application accessible at http://localhost
✅ Database migrations run successfully
✅ Reverb WebSocket server running on port 8080
✅ Hot reload working for both PHP and React
✅ Redis cache functioning
✅ Sample data seeded correctly

---

**Document Version**: 1.0
**Last Updated**: May 16, 2026