$ErrorActionPreference = 'Stop'

$projectRoot = $PSScriptRoot
$envFile = Join-Path $projectRoot '.env'
$exampleFile = Join-Path $projectRoot '.env.example'
$stateDirectory = Join-Path $projectRoot '.local'
$pidFile = Join-Path $stateDirectory 'php-server.pid'
$stdoutLog = Join-Path $stateDirectory 'php-server.out.log'
$stderrLog = Join-Path $stateDirectory 'php-server.err.log'
$serverUrl = 'http://127.0.0.1:8000/admin/login.php'

function Stop-WithMessage([string] $message) {
    Write-Host $message -ForegroundColor Red
    exit 1
}

$php = Get-Command php -ErrorAction SilentlyContinue
if ($null -eq $php) {
    Stop-WithMessage 'No se encontró PHP en PATH. Instala PHP 8.3+ con pdo_mysql y mbstring, y vuelve a intentarlo.'
}
$phpModules = & $php.Source -m
if ($LASTEXITCODE -ne 0) {
    Stop-WithMessage 'No se pudo ejecutar PHP. Revisa la instalación y PATH.'
}
foreach ($requiredModule in @('pdo_mysql', 'mbstring')) {
    if ($phpModules -notcontains $requiredModule) {
        Stop-WithMessage "PHP no tiene habilitada la extensión $requiredModule."
    }
}

if (-not (Test-Path $envFile)) {
    if (-not (Test-Path $exampleFile)) {
        Stop-WithMessage 'Falta .env.example; vuelve a obtener los archivos del repositorio.'
    }

    Copy-Item $exampleFile $envFile
    $bytes = New-Object byte[] 32
    $random = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try {
        $random.GetBytes($bytes)
    } finally {
        $random.Dispose()
    }
    $appKey = [BitConverter]::ToString($bytes).Replace('-', '').ToLowerInvariant()
    $contents = Get-Content $envFile -Raw
    $contents = [regex]::Replace($contents, '(?m)^GC_APP_KEY=.*$', "GC_APP_KEY=$appKey")
    Set-Content -Path $envFile -Value $contents -NoNewline
    Write-Host 'Se creó .env local y una clave privada para esta instalación.' -ForegroundColor Green
}

$configuration = @{}
$lineNumber = 0
foreach ($line in Get-Content $envFile) {
    $lineNumber++
    $trimmedLine = $line.Trim()
    if ($trimmedLine -eq '' -or $trimmedLine.StartsWith('#')) {
        continue
    }
    if ($trimmedLine -notmatch '^(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$') {
        Stop-WithMessage "Formato no válido en .env, línea $lineNumber."
    }

    $name = $Matches[1]
    $value = $Matches[2].Trim()
    if ($value.Length -ge 2 -and (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'")))) {
        $value = $value.Substring(1, $value.Length - 2)
    }
    $configuration[$name] = $value
}

$missing = @('GC_DB_HOST', 'GC_DB_NAME', 'GC_DB_USER', 'GC_APP_KEY') |
    Where-Object { -not $configuration.ContainsKey($_) -or [string]::IsNullOrWhiteSpace($configuration[$_]) }
if ($missing.Count -gt 0) {
    Stop-WithMessage ("Completa en .env estos valores para tu servidor MySQL: " + ($missing -join ', ') + ". No compartas ese archivo ni lo subas a GitHub.")
}
if ($configuration['GC_APP_KEY'].Length -lt 32) {
    Stop-WithMessage 'GC_APP_KEY en .env debe tener al menos 32 caracteres.'
}

$setupCheck = & $php.Source (Join-Path $projectRoot 'admin\check-local.php') 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host ($setupCheck -join [Environment]::NewLine) -ForegroundColor Red
    Stop-WithMessage 'Corrige la configuración de MySQL o instala admin/schema.sql antes de iniciar el servidor.'
}
Write-Host ($setupCheck -join [Environment]::NewLine)

$listener = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue |
    Select-Object -First 1
if ($null -ne $listener) {
    try {
        $response = Invoke-WebRequest -UseBasicParsing -Uri $serverUrl -TimeoutSec 5
        if ($response.StatusCode -eq 200 -and $response.Content.Contains('GC Technical Solutions')) {
            Write-Host 'El servidor ya está en ejecución. Abre http://127.0.0.1:8000/ en el navegador.' -ForegroundColor Green
            exit 0
        }
    } catch {
    }
    Stop-WithMessage 'El puerto 8000 ya está ocupado por otro proceso. Cierra ese proceso o cambia el puerto en start-local.ps1.'
}

New-Item -ItemType Directory -Path $stateDirectory -Force | Out-Null
$arguments = '-S 127.0.0.1:8000 -t "' + $projectRoot + '"'
$server = Start-Process -FilePath $php.Source `
    -ArgumentList $arguments `
    -WorkingDirectory $projectRoot `
    -RedirectStandardOutput $stdoutLog `
    -RedirectStandardError $stderrLog `
    -WindowStyle Hidden `
    -PassThru
Set-Content -Path $pidFile -Value $server.Id

$response = $null
for ($attempt = 0; $attempt -lt 30; $attempt++) {
    if ($server.HasExited) {
        break
    }
    try {
        $response = Invoke-WebRequest -UseBasicParsing -Uri $serverUrl -TimeoutSec 3
        break
    } catch {
        $statusCode = 0
        if ($null -ne $_.Exception.Response) {
            $statusCode = [int]$_.Exception.Response.StatusCode
        }
        if ($statusCode -ge 400) {
            break
        }
        Start-Sleep -Milliseconds 500
    }
}

if ($null -eq $response -or $response.StatusCode -ne 200 -or -not $response.Content.Contains('GC Technical Solutions')) {
    if (-not $server.HasExited) {
        Stop-Process -Id $server.Id
    }
    Remove-Item $pidFile -ErrorAction SilentlyContinue
    Write-Host 'No se pudo iniciar el panel. Comprueba que MySQL esté activo, que la base de datos exista y que sus tablas se hayan creado con admin/schema.sql.' -ForegroundColor Red
    foreach ($log in @($stderrLog, $stdoutLog)) {
        if (Test-Path $log) {
            Get-Content $log -Tail 15
        }
    }
    exit 1
}

Write-Host 'Servidor PHP activo de forma independiente de VS Code.' -ForegroundColor Green
Write-Host 'Panel: http://127.0.0.1:8000/admin/login.php'
Write-Host 'Inicio: http://127.0.0.1:8000/'
Write-Host 'Para detenerlo: powershell -NoProfile -ExecutionPolicy Bypass -File .\stop-local.ps1'
