# ==============================================================================
# Script de Instalación Automatizada - Condominio SaaS (Windows)
# ==============================================================================
$ErrorActionPreference = "Stop"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " Configurando Entorno para Condominio SaaS en Windows    " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Verificar / Instalar Node.js
if (!(Get-Command node -ErrorAction SilentlyContinue)) {
    Write-Host "[1/5] Instalando Node.js LTS mediante Winget..." -ForegroundColor Yellow
    winget install --id OpenJS.NodeJS.LTS --accept-package-agreements --accept-source-agreements --silent
} else {
    Write-Host "[1/5] Node.js ya está instalado." -ForegroundColor Green
}

# 2. Descargar e Instalar PHP 8.3 si no existe
$phpDir = "C:\php"
if (!(Test-Path "$phpDir\php.exe")) {
    Write-Host "[2/5] Descargando y extrayendo PHP 8.3..." -ForegroundColor Yellow
    if (!(Test-Path $phpDir)) { New-Item -ItemType Directory -Path $phpDir -Force | Out-Null }
    $zipPath = "$env:TEMP\php-8.3.zip"
    Invoke-WebRequest -Uri "https://windows.php.net/downloads/releases/php-8.3.33-Win32-vs16-x64.zip" -OutFile $zipPath -UseBasicParsing
    Expand-Archive -Path $zipPath -DestinationPath $phpDir -Force
    Remove-Item $zipPath -Force
} else {
    Write-Host "[2/5] PHP 8.3 ya está presente en $phpDir." -ForegroundColor Green
}

# 3. Configurar php.ini
Write-Host "[3/5] Configurando php.ini con extensiones requeridas..." -ForegroundColor Yellow
$phpIni = Join-Path $phpDir "php.ini"
$phpIniDev = Join-Path $phpDir "php.ini-development"
if (!(Test-Path $phpIni) -and (Test-Path $phpIniDev)) {
    Copy-Item $phpIniDev $phpIni -Force
}
if (Test-Path $phpIni) {
    $content = Get-Content $phpIni -Raw
    $content = $content -replace ';extension_dir = "ext"', 'extension_dir = "ext"'
    $extensions = @('curl', 'fileinfo', 'gd', 'mbstring', 'openssl', 'pdo_pgsql', 'pgsql', 'pdo_sqlite', 'sqlite3', 'zip')
    foreach ($ext in $extensions) {
        $content = $content -replace ";extension=$ext", "extension=$ext"
    }
    Set-Content -Path $phpIni -Value $content
}

# 4. Descargar Composer y crear batch
if (!(Test-Path "$phpDir\composer.phar")) {
    Write-Host "[4/5] Descargando Composer..." -ForegroundColor Yellow
    Invoke-WebRequest -Uri "https://getcomposer.org/composer-stable.phar" -OutFile "$phpDir\composer.phar" -UseBasicParsing
    Set-Content -Path "$phpDir\composer.bat" -Value "@ECHO OFF`r`nphp `"%~dp0composer.phar`" %*"
} else {
    Write-Host "[4/5] Composer ya está listo." -ForegroundColor Green
}

# 5. Configurar PATH de Usuario
Write-Host "[5/5] Actualizando variables de entorno (PATH)..." -ForegroundColor Yellow
$pathsToAdd = @("C:\php", "C:\Program Files\nodejs", "C:\Program Files\PostgreSQL\17\bin", "C:\Program Files\Git\cmd")
$currentPath = [Environment]::GetEnvironmentVariable("Path", "User")
$currentPaths = ($currentPath -split ';') | Where-Object { $_ -ne '' }
foreach ($p in $pathsToAdd) {
    if ((Test-Path $p) -and ($currentPaths -notcontains $p)) {
        $currentPaths += $p
    }
}
$newPath = $currentPaths -join ';'
[Environment]::SetEnvironmentVariable("Path", $newPath, "User")
$env:PATH = "$newPath;$env:PATH"

Write-Host "==========================================================" -ForegroundColor Green
Write-Host " Entorno configurado con éxito. Ejecutando preparación... " -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green

# Preparar proyecto
if (!(Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    & "$phpDir\php.exe" artisan key:generate
}

& "$phpDir\php.exe" "$phpDir\composer.phar" install
npm install --legacy-peer-deps
npm run build

Write-Host "`n¡Todo listo! Para iniciar el proyecto ejecuta:" -ForegroundColor Cyan
Write-Host "  php artisan serve" -ForegroundColor Yellow
Write-Host "  npm run dev`n" -ForegroundColor Yellow
