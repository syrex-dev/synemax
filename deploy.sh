#!/usr/bin/env bash
set -e

BRANCH="${1:-main}"
SERVER="root@192.168.1.50"
REMOTE_DIR="/var/www/synemax"

echo "========================================"
echo "   SynemaX Direct Deployment Script     "
echo "========================================"
echo "Target Server : $SERVER"
echo "Remote Path   : $REMOTE_DIR"
echo "Branch        : $BRANCH"

if [[ "$2" != "--skip-push" ]]; then
    echo ""
    echo "[1/3] Pushing to origin $BRANCH..."
    git push origin "$BRANCH"
else
    echo ""
    echo "[1/3] Skip push (--skip-push)..."
fi

echo ""
echo "[2/3] Executing deployment on server via SSH..."
ssh -o BatchMode=yes -o ConnectTimeout=10 "$SERVER" << EOF
set -e
export PATH="/usr/local/bin:\$PATH"
export COMPOSER_ALLOW_SUPERUSER=1

cd "$REMOTE_DIR"
echo ">>> [Server] Menarik pembaruan dari Git ($BRANCH)..."
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull origin "$BRANCH"

echo ">>> [Server] Mengupdate dependensi Composer..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo ">>> [Server] Membangun aset Vite produksi..."
npm install --legacy-peer-deps --no-audit --no-fund
npm run build
rm -f public/hot

echo ">>> [Server] Menjalankan migrasi database..."
php artisan migrate --force

echo ">>> [Server] Merefresh dan mengoptimasi cache aplikasi..."
php artisan optimize:clear
php artisan optimize

echo ">>> [Server] Mengatur izin kepemilikan storage..."
chown -R www-data:www-data "$REMOTE_DIR/storage" "$REMOTE_DIR/bootstrap/cache"

echo ">>> [Server] Merefresh layanan PHP-FPM dan Nginx..."
systemctl reload php8.4-fpm
systemctl reload nginx

echo ">>> [Server] Deployment berhasil diselesaikan pada: \$(date)"
EOF

echo ""
echo "========================================"
echo "  DEPLOYMENT BERHASIL! (https://synemax.web.id)"
echo "========================================"
