param(
    [switch]$RemoveVolumes,
    [switch]$DryRun
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$root = $PSScriptRoot
$backendDir = Join-Path $root 'backend'

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
        [hashtable]$Compose,
        [switch]$PreviewOnly
    )

    $allArgs = @()
    $allArgs += $Compose.ArgsPrefix
    $allArgs += $Args

    if ($PreviewOnly) {
        Write-Host "[dry-run] $($Compose.File) $($allArgs -join ' ')" -ForegroundColor Yellow
        return
    }

    Push-Location $WorkingDir
    try {
        & $Compose.File @allArgs
    } finally {
        Pop-Location
    }
}

$compose = Get-ComposeCommand

if ($RemoveVolumes) {
    Write-Host '[stop] Stopping backend and removing volumes...' -ForegroundColor Cyan
    Invoke-Compose -Compose $compose -WorkingDir $backendDir -Args @('down', '-v') -PreviewOnly:$DryRun
} else {
    Write-Host '[stop] Stopping backend containers...' -ForegroundColor Cyan
    Invoke-Compose -Compose $compose -WorkingDir $backendDir -Args @('down') -PreviewOnly:$DryRun
}

Write-Host '[stop] Done.' -ForegroundColor Green

