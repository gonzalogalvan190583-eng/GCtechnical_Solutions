$ErrorActionPreference = 'Stop'

$stateDirectory = Join-Path $PSScriptRoot '.local'
$pidFile = Join-Path $stateDirectory 'php-server.pid'
if (-not (Test-Path $pidFile)) {
    Write-Host 'No hay un servidor local registrado.'
    exit 0
}

$serverId = 0
if (-not [int]::TryParse((Get-Content $pidFile -Raw).Trim(), [ref]$serverId)) {
    Write-Host 'El archivo PID local no es válido; no se detuvo ningún proceso.' -ForegroundColor Red
    exit 1
}

$serverProcess = Get-CimInstance Win32_Process -Filter "ProcessId = $serverId" -ErrorAction SilentlyContinue
if ($null -eq $serverProcess) {
    Remove-Item $pidFile
    Write-Host 'El servidor ya no estaba en ejecución.'
    exit 0
}

$php = Get-Command php -ErrorAction SilentlyContinue
$expectedRoot = $PSScriptRoot.TrimEnd('\')
$isPhpServer = $null -ne $php `
    -and $serverProcess.ExecutablePath -eq $php.Source `
    -and $serverProcess.CommandLine.IndexOf('-S 127.0.0.1:8000', [StringComparison]::OrdinalIgnoreCase) -ge 0 `
    -and $serverProcess.CommandLine.IndexOf($expectedRoot, [StringComparison]::OrdinalIgnoreCase) -ge 0
if (-not $isPhpServer) {
    Write-Host 'El PID registrado ya no corresponde al servidor de este proyecto; no se detuvo ningún proceso.' -ForegroundColor Red
    exit 1
}

Stop-Process -Id $serverId
Remove-Item $pidFile
Write-Host 'Servidor PHP detenido. La base de datos y sus datos no se modificaron.' -ForegroundColor Green
