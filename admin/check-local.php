<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/_bootstrap.php';

try {
    $database = db();
    $tables = $database->query(
        "SELECT table_name FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name IN ('users', 'service_requests', 'request_rate_limits')"
    )->fetchAll(PDO::FETCH_COLUMN);
    $missingTables = array_diff(['users', 'service_requests', 'request_rate_limits'], $tables);

    if ($missingTables !== []) {
        fwrite(STDERR, 'Faltan tablas en la base de datos: ' . implode(', ', $missingTables) . ". Ejecuta admin/schema.sql.\n");
        exit(1);
    }

    fwrite(STDOUT, "Conexión MySQL y tablas del panel verificadas.\n");
} catch (PDOException | RuntimeException $exception) {
    fwrite(STDERR, "No se pudo validar MySQL: " . $exception->getMessage() . "\n");
    exit(1);
}
