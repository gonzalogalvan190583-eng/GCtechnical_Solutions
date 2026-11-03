<?php
declare(strict_types=1);

function load_local_environment(): void
{
    if (!in_array(PHP_SAPI, ['cli', 'cli-server'], true)) {
        return;
    }

    $envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($envFile)) {
        return;
    }

    $lines = @file($envFile, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('No se pudo leer el archivo .env local.');
    }

    foreach ($lines as $lineNumber => $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!preg_match('/^(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
            throw new RuntimeException('El archivo .env tiene un formato no válido en la línea ' . ($lineNumber + 1) . '.');
        }

        $name = $matches[1];
        $value = trim($matches[2]);
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) !== false) {
            continue;
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

set_exception_handler(static function (Throwable $exception): void {
    error_log('Error inesperado en el panel de solicitudes: ' . $exception->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo 'Ocurrió un error interno. Inténtalo más tarde.';
});

load_local_environment();
date_default_timezone_set(getenv('GC_TIMEZONE') ?: 'America/Santiago');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, private');

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function db(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = getenv('GC_DB_HOST') ?: '';
    $name = getenv('GC_DB_NAME') ?: '';
    $user = getenv('GC_DB_USER') ?: '';
    $password = getenv('GC_DB_PASSWORD');
    $appKey = getenv('GC_APP_KEY') ?: '';

    if ($host === '' || $name === '' || $user === '' || $password === false || strlen($appKey) < 32) {
        throw new RuntimeException('Falta configurar la conexión de base de datos o GC_APP_KEY.');
    }

    try {
        $connection = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name),
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        error_log('No se pudo conectar a MySQL: ' . $exception->getMessage());
        throw new RuntimeException('No fue posible conectar con la base de datos.', 0, $exception);
    }

    return $connection;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('La sesión del formulario venció. Vuelve atrás e inténtalo nuevamente.');
    }
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('login.php');
    }
    if (!empty($user['must_change_password']) && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'password.php') {
        redirect('password.php');
    }
    return $user;
}

function require_admin(array $user): void
{
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('No tienes permiso para realizar esta acción.');
    }
}

function page_start(string $title): void
{
    $user = current_user();
    ?>
    <!doctype html>
    <html lang="es">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?= h($title) ?> | GC Technical Solutions</title>
      <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
      <link rel="stylesheet" href="admin.css">
    </head>
    <body class="admin-body">
      <header class="admin-header">
        <a class="admin-brand" href="index.php">GC <span>Solicitudes</span></a>
        <?php if ($user !== null): ?>
          <nav class="admin-nav" aria-label="Navegación del panel">
            <a href="../index.html">Inicio del sitio</a>
            <a href="index.php">Solicitudes</a>
            <a href="users.php">Usuarios</a>
            <a href="password.php">Cambiar contraseña</a>
            <span><?= h($user['display_name']) ?> · <?= $user['role'] === 'admin' ? 'Administrador' : 'Nivel 1' ?></span>
            <form method="post" action="logout.php">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <button type="submit" class="link-button">Salir</button>
            </form>
          </nav>
        <?php endif; ?>
      </header>
      <main class="admin-main">
        <h1><?= h($title) ?></h1>
    <?php
}

function page_end(): void
{
    ?>
      </main>
    </body>
    </html>
    <?php
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function show_flash(): void
{
    if (!isset($_SESSION['flash'])) {
        return;
    }
    $notice = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = $notice['type'] === 'error' ? 'danger' : 'success';
    echo '<div class="alert alert-' . h($type) . '" role="status">' . h($notice['message']) . '</div>';
}

function valid_date(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}
