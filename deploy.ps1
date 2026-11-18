[CmdletBinding()]
param (
    [string]$Branch = "main",
    [switch]$SkipPush,
    [switch]$SkipBuild,
    [switch]$SkipMigrate
)

$ErrorActionPreference = "Stop"
$Server = "root@192.168.1.50"
$RemoteDir = "/var/www/synemax"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   SynemaX Direct Deployment Script     " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Target Server : $Server" -ForegroundColor Yellow
Write-Host "Remote Path   : $RemoteDir" -ForegroundColor Yellow
Write-Host "Branch        : $Branch" -ForegroundColor Yellow

# 1. Push to GitHub if not skipped
if (-not $SkipPush) {
    Write-Host "`n[1/3] Checking local Git status and pushing to origin/$Branch..." -ForegroundColor Green
    $uncommitted = git status --porcelain
    if ($uncommitted) {
        Write-Warning "Peringatan: Ada file perubahan lokal yang belum di-commit:"
        git status --short
        $confirm = Read-Host "Apakah Anda ingin tetap melanjutkan deploy? (y/n)"
        if ($confirm -ne "y") {
            Write-Host "Deploy dibatalkan." -ForegroundColor Red
            exit 1
        }
    }
    
    Write-Host "Pushing ke origin $Branch..." -ForegroundColor Green
    git push origin $Branch
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Gagal melakukan git push ke origin $Branch. Periksa koneksi GitHub Anda."
        exit 1
    }
} else {
    Write-Host "`n[1/3] Skip push (-SkipPush aktif)..." -ForegroundColor Yellow
}

# 2. Build remote commands
$remoteCommands = @(
    "set -e",
    "export PATH=/usr/local/bin:`$PATH",
    "export COMPOSER_ALLOW_SUPERUSER=1",
    "echo '>>> [Server] Mengakses $RemoteDir'",
    "cd $RemoteDir",
    "echo '>>> [Server] Menarik pembaruan dari Git ($Branch)...'",
    "git fetch origin $Branch",
    "git checkout $Branch",
    "git pull origin $Branch",
    "echo '>>> [Server] Mengupdate dependensi Composer...'",
    "composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction"
)

if (-not $SkipBuild) {
    $remoteCommands += "echo '>>> [Server] Membangun aset Vite produksi...'"
    $remoteCommands += "npm install --legacy-peer-deps --no-audit --no-fund"
    $remoteCommands += "npm run build"
    $remoteCommands += "rm -f public/hot"
}

if (-not $SkipMigrate) {
    $remoteCommands += "echo '>>> [Server] Menjalankan migrasi database...'"
    $remoteCommands += "php artisan migrate --force"
}

$remoteCommands += @(
    "echo '>>> [Server] Merefresh dan mengoptimasi cache aplikasi...'",
    "php artisan optimize:clear",
    "php artisan optimize",
    "echo '>>> [Server] Mengatur izin kepemilikan storage...'",
    "chown -R www-data:www-data $RemoteDir/storage $RemoteDir/bootstrap/cache",
    "echo '>>> [Server] Merefresh layanan PHP-FPM dan Nginx...'",
    "systemctl reload php8.4-fpm",
    "systemctl reload nginx",
    "echo '>>> [Server] Deployment berhasil diselesaikan pada: '`$(date)"
)

$combinedRemoteScript = $remoteCommands -join " && "

# 3. Execute on server via SSH
Write-Host "`n[2/3] Menghubungkan ke $Server via SSH..." -ForegroundColor Green
ssh -o BatchMode=yes -o ConnectTimeout=10 $Server "$combinedRemoteScript"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n========================================" -ForegroundColor Green
    Write-Host "  DEPLOYMENT BERHASIL! (https://synemax.web.id)  " -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
} else {
    Write-Host "`n========================================" -ForegroundColor Red
    Write-Host "  DEPLOYMENT GAGAL (Kode Error: $LASTEXITCODE)  " -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    exit $LASTEXITCODE
}
