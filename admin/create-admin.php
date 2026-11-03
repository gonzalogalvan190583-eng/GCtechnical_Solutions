<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $database = db();
    $existingAdmin = $database->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ((int) $existingAdmin > 0) {
        fwrite(STDERR, "Ya existe una cuenta de administrador. No se creó otra.\n");
        exit(1);
    }

    fwrite(STDOUT, "Usuario administrador: ");
    $username = trim((string) fgets(STDIN));
    fwrite(STDOUT, "Nombre para mostrar: ");
    $displayName = trim((string) fgets(STDIN));

    if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username) || $displayName === '' || mb_strlen($displayName) > 100) {
        fwrite(STDERR, "Usuario inválido (3-50 caracteres: letras, números, punto, guion o guion bajo) o nombre vacío.\n");
        exit(1);
    }

    $temporaryPassword = bin2hex(random_bytes(16));
    $statement = $database->prepare(
        "INSERT INTO users (username, display_name, password_hash, role, must_change_password)
         VALUES (:username, :display_name, :password_hash, 'admin', 1)"
    );
    $statement->execute([
        'username' => $username,
        'display_name' => $displayName,
        'password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
    ]);

    fwrite(STDOUT, "\nCuenta creada. Contraseña inicial (se mostrará una sola vez):\n{$temporaryPassword}\n");
    fwrite(STDOUT, "Inicia sesión y cámbiala inmediatamente.\n");
} catch (RuntimeException $exception) {
    error_log('No se pudo crear el administrador inicial: ' . $exception->getMessage());
    fwrite(STDERR, "No se pudo crear la cuenta. Revisa la configuración y los registros del servidor.\n");
    exit(1);
}
