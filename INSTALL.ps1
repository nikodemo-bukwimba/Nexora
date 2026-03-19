# ═══════════════════════════════════════════════════════════════════
# Nexora — Platform Core Module Installer
# Run from your project root: D:\projects\nexora\nexora
# Usage: .\INSTALL.ps1 -ProjectPath "D:\projects\nexora\nexora"
# ═══════════════════════════════════════════════════════════════════

param(
    [string]$ProjectPath = (Get-Location).Path
)

Write-Host ""
Write-Host "Nexora Platform Core — Installing..." -ForegroundColor Cyan
Write-Host "Target: $ProjectPath" -ForegroundColor Gray
Write-Host ""

# ── Verify we are in a Laravel project ──────────────────────────
if (-not (Test-Path "$ProjectPath\artisan")) {
    Write-Host "ERROR: artisan not found. Run this script from your Laravel project root." -ForegroundColor Red
    exit 1
}

# ── Create module directory structure ───────────────────────────
$dirs = @(
    "modules\Platform\Config",
    "modules\Platform\Contracts\Repositories",
    "modules\Platform\Contracts\Services",
    "modules\Platform\Database\Migrations",
    "modules\Platform\Database\Seeders",
    "modules\Platform\Database\Factories",
    "modules\Platform\Events",
    "modules\Platform\Exceptions",
    "modules\Platform\Http\Controllers\Api",
    "modules\Platform\Http\Controllers\Web",
    "modules\Platform\Http\Middleware",
    "modules\Platform\Http\Requests",
    "modules\Platform\Http\Resources",
    "modules\Platform\Models",
    "modules\Platform\Providers",
    "modules\Platform\Repositories",
    "modules\Platform\Routes",
    "modules\Platform\Services",
    "modules\Platform\Traits"
)

Write-Host "Creating directory structure..." -ForegroundColor Yellow
foreach ($dir in $dirs) {
    $full = Join-Path $ProjectPath $dir
    if (-not (Test-Path $full)) {
        New-Item -ItemType Directory -Path $full -Force | Out-Null
        Write-Host "  + $dir" -ForegroundColor Green
    } else {
        Write-Host "  = $dir (exists)" -ForegroundColor Gray
    }
}

# ── Copy module files ────────────────────────────────────────────
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$sourceBase = Join-Path $scriptDir "modules\Platform"
$destBase   = Join-Path $ProjectPath "modules\Platform"

Write-Host ""
Write-Host "Copying module files..." -ForegroundColor Yellow

$files = Get-ChildItem -Path $sourceBase -Recurse -File
foreach ($file in $files) {
    $relative = $file.FullName.Substring($sourceBase.Length + 1)
    $dest     = Join-Path $destBase $relative
    $destDir  = Split-Path -Parent $dest

    if (-not (Test-Path $destDir)) {
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    }

    Copy-Item -Path $file.FullName -Destination $dest -Force
    Write-Host "  + modules\Platform\$relative" -ForegroundColor Green
}

# ── Register PlatformServiceProvider in bootstrap/providers.php ──
Write-Host ""
Write-Host "Registering PlatformServiceProvider..." -ForegroundColor Yellow

$providersFile = Join-Path $ProjectPath "bootstrap\providers.php"
$providerClass = "Modules\Platform\Providers\PlatformServiceProvider::class,"
$content       = Get-Content $providersFile -Raw

if ($content -notmatch "PlatformServiceProvider") {
    $content = $content -replace "(return \[)", "`$1`n    $providerClass"
    Set-Content $providersFile $content -Encoding UTF8
    Write-Host "  + PlatformServiceProvider registered" -ForegroundColor Green
} else {
    Write-Host "  = PlatformServiceProvider already registered" -ForegroundColor Gray
}

# ── Register DatabaseSeeder to call PlatformSeeder ───────────────
Write-Host ""
Write-Host "Checking DatabaseSeeder..." -ForegroundColor Yellow

$seederFile = Join-Path $ProjectPath "database\seeders\DatabaseSeeder.php"
if (Test-Path $seederFile) {
    $seederContent = Get-Content $seederFile -Raw
    if ($seederContent -notmatch "PlatformSeeder") {
        Write-Host "  ! Add this to your DatabaseSeeder manually:" -ForegroundColor Yellow
        Write-Host '    $this->call(\Modules\Platform\Database\Seeders\PlatformSeeder::class);' -ForegroundColor White
    } else {
        Write-Host "  = PlatformSeeder already referenced" -ForegroundColor Gray
    }
}

# ── Add Platform autoload to composer.json ───────────────────────
Write-Host ""
Write-Host "Checking composer.json autoload..." -ForegroundColor Yellow

$composerFile = Join-Path $ProjectPath "composer.json"
$composer     = Get-Content $composerFile -Raw | ConvertFrom-Json

$psr4 = $composer.autoload.'psr-4'
if (-not ($psr4.'Modules\')) {
    Write-Host "  ! Add this to composer.json autoload.psr-4 manually:" -ForegroundColor Yellow
    Write-Host '    "Modules\\": "modules/"' -ForegroundColor White
} else {
    Write-Host "  = Modules autoload already registered" -ForegroundColor Gray
}

# ── Summary ──────────────────────────────────────────────────────
Write-Host ""
Write-Host "════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host " Installation complete." -ForegroundColor Cyan
Write-Host ""
Write-Host " Next steps:" -ForegroundColor White
Write-Host ""
Write-Host " 1. Add to composer.json autoload.psr-4 if not done:" -ForegroundColor White
Write-Host '      "Modules\\": "modules/"' -ForegroundColor Yellow
Write-Host ""
Write-Host " 2. Run:" -ForegroundColor White
Write-Host "      composer dump-autoload" -ForegroundColor Yellow
Write-Host ""
Write-Host " 3. Run migrations:" -ForegroundColor White
Write-Host "      php artisan migrate" -ForegroundColor Yellow
Write-Host ""
Write-Host " 4. Run seeders:" -ForegroundColor White
Write-Host "      php artisan db:seed" -ForegroundColor Yellow
Write-Host ""
Write-Host "════════════════════════════════════════════════" -ForegroundColor Cyan
