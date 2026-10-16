<#
.SYNOPSIS
    Runner centralizado y unificado de desarrollo para Pulso Cívico.
.DESCRIPTION
    Punto único de entrada para todas las tareas internas (lint, git, health check, etc.).
    Al otorgar permiso a este script una sola vez, Antigravity puede ejecutar tareas
    en lote y diagnósticos internos sin solicitar confirmación repetitiva.
#>
param (
    [Parameter(Position = 0, Mandatory = $false)]
    [string]$Action = "help",

    [Parameter(Position = 1, Mandatory = $false)]
    [string]$Target = "",

    [Parameter(Position = 2, Mandatory = $false, ValueFromRemainingArguments = $true)]
    [string[]]$Extra = @()
)

$extraStr = $Extra -join " "


$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$gatewayPhp = Join-Path $scriptDir "gateway.php"

if (-not (Test-Path $gatewayPhp)) {
    Write-Error "No se encontro gateway.php en $scriptDir"
    exit 1
}

# Auto-detección inteligente del ejecutable de PHP
$phpCmd = "php"
if (-not (Get-Command "php" -ErrorAction SilentlyContinue)) {
    $possiblePaths = @(
        "E:\xampp\php\php.exe",
        "C:\xampp\php\php.exe",
        "D:\xampp\php\php.exe"
    )
    foreach ($p in $possiblePaths) {
        if (Test-Path $p) {
            $phpCmd = $p
            break
        }
    }
}

# Ejecución directa y resiliente para comandos git si es necesario
if ($Action -eq "git:raw") {
    & git $Target $Extra
    exit $LASTEXITCODE
}

# Ejecución para entorno activo de Python
if ($Action -eq "python" -or $Action -eq "py") {
    $uvPath = "C:\Users\pc\.local\bin\uv.exe"
    if (Test-Path $uvPath) {
        & $uvPath run python $Target $Extra
    } else {
        & python $Target $Extra
    }
    exit $LASTEXITCODE
}

# Ejecutar el gateway PHP pasando los argumentos
& $phpCmd $gatewayPhp $Action $Target $extraStr


