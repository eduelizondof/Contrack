# Script para levantar el ambiente de desarrollo completo
# Ejecutar desde la raíz del proyecto: .\start-dev.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Iniciando VuePOS - Ambiente de Desarrollo" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verificar que estamos en la raíz del proyecto
if (-not (Test-Path "backend") -or -not (Test-Path "frontend")) {
    Write-Host "Error: Este script debe ejecutarse desde la raíz del proyecto VuePOS" -ForegroundColor Red
    exit 1
}

# Verificar que existe .env en backend
if (-not (Test-Path "backend\.env")) {
    Write-Host "Advertencia: No se encontró backend\.env" -ForegroundColor Yellow
    Write-Host "Copiando backend\.env.example a backend\.env..." -ForegroundColor Yellow
    Copy-Item "backend\.env.example" "backend\.env"
    Write-Host "Por favor, configura backend\.env antes de continuar" -ForegroundColor Yellow
    exit 1
}

# Verificar que existe .env en frontend
if (-not (Test-Path "frontend\.env")) {
    Write-Host "Advertencia: No se encontró frontend\.env" -ForegroundColor Yellow
    Write-Host "Copiando frontend\.env.example a frontend\.env..." -ForegroundColor Yellow
    Copy-Item "frontend\.env.example" "frontend\.env"
}

# Limpiar caché de Laravel
Write-Host "Limpiando caché de Laravel..." -ForegroundColor Yellow
Set-Location backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear
Set-Location ..

Write-Host ""
Write-Host "Iniciando servidores..." -ForegroundColor Green
Write-Host ""

# Verificar configuración antes de iniciar
Write-Host "Verificando configuración..." -ForegroundColor Yellow
if (Test-Path "backend\.env") {
    $backendEnv = Get-Content "backend\.env" -Raw
    if ($backendEnv -notmatch "APP_URL=http://localhost") {
        Write-Host "  ADVERTENCIA: APP_URL en backend\.env debe ser http://localhost:8000" -ForegroundColor Yellow
    }
    if ($backendEnv -notmatch "FRONTEND_URL=") {
        Write-Host "  ADVERTENCIA: FRONTEND_URL no está configurado en backend\.env" -ForegroundColor Yellow
        Write-Host "  Agrega: FRONTEND_URL=http://localhost:5173" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Iniciando servidores en ventanas separadas..." -ForegroundColor Green
Write-Host ""

# Obtener el directorio actual
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path

# Iniciar backend en una ventana separada
Write-Host "Iniciando Backend (Laravel) en http://localhost:8000..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$scriptPath\backend'; php artisan serve --host=localhost --port=8000"

# Esperar un momento para que el backend inicie
Start-Sleep -Seconds 3

# Iniciar frontend en otra ventana separada
Write-Host "Iniciando Frontend (Vue.js) en http://localhost:5173..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$scriptPath\frontend'; npm run dev"

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  Servidores iniciados correctamente" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Backend:  http://localhost:8000" -ForegroundColor Cyan
Write-Host "Frontend: http://localhost:5173" -ForegroundColor Cyan
Write-Host ""
Write-Host "Las ventanas de los servidores se abrieron por separado" -ForegroundColor Yellow
Write-Host "Cierra las ventanas para detener los servidores" -ForegroundColor Yellow
Write-Host ""
Write-Host "Presiona cualquier tecla para salir..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")




