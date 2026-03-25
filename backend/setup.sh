#!/bin/bash
# Trading Signals Backend - Setup Script
# Run this after cloning the repository

set -e

echo "=== Trading Signals Backend Setup ==="

# 1. Install dependencies
echo "[1/8] Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev

# 2. Copy .env
echo "[2/8] Setting up environment..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env from .env.example — please edit it now!"
fi

# 3. Generate app key
echo "[3/8] Generating application key..."
php artisan key:generate

# 4. Generate JWT secret
echo "[4/8] Generating JWT secret..."
php artisan jwt:secret

# 5. Run migrations
echo "[5/8] Running database migrations..."
php artisan migrate --force

# 6. Seed database
echo "[6/8] Seeding database (packages, pairs, admin user)..."
php artisan db:seed --force

# 7. Create storage link
echo "[7/8] Creating storage symlink..."
php artisan storage:link

# 8. Optimize
echo "[8/8] Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "=== Setup Complete ==="
echo ""
echo "Next steps:"
echo "  1. Edit .env with your database, API keys, FCM credentials"
echo "  2. Configure your web server to point to /public"
echo "  3. Set up the cron job:"
echo "     * * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1"
echo "  4. Start the queue worker:"
echo "     php artisan queue:work redis --queue=notifications,default --sleep=3 --tries=3"
echo ""
echo "Admin credentials:"
echo "  Email:    \$ADMIN_EMAIL (from .env)"
echo "  Password: \$ADMIN_PASSWORD (from .env)"
echo ""
