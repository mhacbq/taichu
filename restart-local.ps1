param(
    [switch]$ResetData,
    [switch]$NoBuild,
    [switch]$WithFrontend,
    [switch]$WithAdmin,
    [switch]$DryRun
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$root = $PSScriptRoot
$stopScript = Join-Path $root 'stop-local.ps1'
$startScript = Join-Path $root 'start-local.ps1'

if (-not (Test-Path $stopScript)) {
    throw "Cannot find script: $stopScript"
}
if (-not (Test-Path $startScript)) {
    throw "Cannot find script: $startScript"
}

Write-Host '[restart] Step 1/2: stopping current local stack...' -ForegroundColor Cyan
$stopArgs = @()
if ($ResetData) { $stopArgs += '-RemoveVolumes' }
if ($DryRun) { $stopArgs += '-DryRun' }
& $stopScript @stopArgs

Write-Host '[restart] Step 2/2: starting local stack...' -ForegroundColor Cyan
$startArgs = @()
if ($ResetData) { $startArgs += '-ResetData' }
if ($NoBuild) { $startArgs += '-NoBuild' }
if ($WithFrontend) { $startArgs += '-WithFrontend' }
if ($WithAdmin) { $startArgs += '-WithAdmin' }

if ($DryRun) {
    Write-Host "[dry-run] $startScript $($startArgs -join ' ')" -ForegroundColor Yellow
} else {
    & $startScript @startArgs
}

Write-Host '[restart] Done.' -ForegroundColor Green

