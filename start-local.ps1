param(
    [switch]$ResetData,
    [switch]$NoBuild,
    [switch]$WithFrontend,
    [switch]$WithAdmin
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$root = $PSScriptRoot
$backendDir = Join-Path $root 'backend'
$frontendDir = Join-Path $root 'frontend'
$adminDir = Join-Path $root 'admin'
$healthUrl = 'http://localhost:8080/api/health'

if (-not (Test-Path (Join-Path $backendDir 'docker-compose.yml'))) {
    throw "Cannot find backend\docker-compose.yml under: $backendDir"
}

function Get-ComposeCommand {
    try {
        docker compose version | Out-Null
        return @{ File = 'docker'; ArgsPrefix = @('compose') }
    } catch {
        try {
            docker-compose version | Out-Null
            return @{ File = 'docker-compose'; ArgsPrefix = @() }
        } catch {
            throw 'Docker Compose is not available. Please install Docker Desktop.'
        }
    }
}

function Invoke-Compose {
    param(
        [string[]]$Args,
        [string]$WorkingDir,
        [hashtable]$Compose
    )

    Push-Location $WorkingDir
    try {
        $allArgs = @()
        $allArgs += $Compose.ArgsPrefix
        $allArgs += $Args
        & $Compose.File @allArgs
    } finally {
        Pop-Location
    }
}

function Wait-Health {
    param(
        [string]$Url,
        [int]$MaxAttempts = 40,
        [int]$DelaySeconds = 2
    )

    for ($i = 1; $i -le $MaxAttempts; $i++) {
        try {
            $resp = Invoke-WebRequest -UseBasicParsing -Uri $Url -TimeoutSec 3
            if ($resp.StatusCode -eq 200) {
                Write-Host "[ok] Backend health check passed: $Url" -ForegroundColor Green
                return
            }
        } catch {
            # keep retrying
        }
        Start-Sleep -Seconds $DelaySeconds
    }

    throw "Backend health check failed after $MaxAttempts attempts: $Url"
}

$compose = Get-ComposeCommand

Write-Host '[1/4] Starting backend containers...' -ForegroundColor Cyan
if ($ResetData) {
    Write-Host '      ResetData enabled: removing containers + volumes.' -ForegroundColor Yellow
    Invoke-Compose -Compose $compose -WorkingDir $backendDir -Args @('down', '-v')
} else {
    Invoke-Compose -Compose $compose -WorkingDir $backendDir -Args @('down')
}

if ($NoBuild) {
    Invoke-Compose -Compose $compose -WorkingDir $backendDir -Args @('up', '-d')
} else {
    Invoke-Compose -Compose $compose -WorkingDir $backendDir -Args @('up', '-d', '--build')
}

Write-Host '[2/4] Waiting for backend health...' -ForegroundColor Cyan
Wait-Health -Url $healthUrl

Write-Host '[3/4] Optional frontend/admin startup...' -ForegroundColor Cyan
if ($WithFrontend) {
    if (-not (Test-Path (Join-Path $frontendDir 'package.json'))) {
        throw "Cannot find frontend\package.json under: $frontendDir"
    }
    Start-Process powershell -ArgumentList @(
        '-NoExit',
        '-Command',
        "Set-Location '$frontendDir'; npm install; npm run dev"
    ) | Out-Null
    Write-Host '      Frontend started in a new PowerShell window.' -ForegroundColor Green
}

if ($WithAdmin) {
    if (-not (Test-Path (Join-Path $adminDir 'package.json'))) {
        throw "Cannot find admin\package.json under: $adminDir"
    }
    Start-Process powershell -ArgumentList @(
        '-NoExit',
        '-Command',
        "Set-Location '$adminDir'; `$env:VITE_PROXY_TARGET='http://localhost:8080'; npm install; npm run dev"
    ) | Out-Null
    Write-Host '      Admin started in a new PowerShell window (proxy -> http://localhost:8080).' -ForegroundColor Green
}

Write-Host '[4/4] Done.' -ForegroundColor Cyan
Write-Host ''
Write-Host 'Backend API   : http://localhost:8080' -ForegroundColor Green
Write-Host 'Health URL    : http://localhost:8080/api/health' -ForegroundColor Green
if ($WithFrontend) { Write-Host 'Frontend      : check terminal output for dev URL (usually http://localhost:5173)' -ForegroundColor Green }
if ($WithAdmin)    { Write-Host 'Admin         : check terminal output for dev URL' -ForegroundColor Green }
Write-Host ''
Write-Host 'Stop backend  : cd backend; docker compose down' -ForegroundColor Yellow

