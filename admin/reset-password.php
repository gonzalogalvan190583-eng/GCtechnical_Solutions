<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$user = require_login();
require_admin($user);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}
verify_csrf();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || (int) $id === (int) $user['id']) {
    http_response_code(400);
    exit('La cuenta indicada no es válida.');
}

$findUser = db()->prepare('SELECT username FROM users WHERE id = :id');
$findUser->execute(['id' => $id]);
$username = $findUser->fetchColumn();
if ($username === false) {
    http_response_code(404);
    exit('La cuenta ya no existe.');
}

$temporaryPassword = bin2hex(random_bytes(16));
$database = db();
$update = $database->prepare(
    'UPDATE users SET password_hash = :password_hash, must_change_password = 1 WHERE id = :id'
);
$update->execute([
    'password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
    'id' => $id,
]);

$_SESSION['new_user_password'] = $temporaryPassword;
$_SESSION['new_user_name'] = (string) $username;
$_SESSION['new_user_message'] = 'Contraseña restablecida. Entrega la nueva contraseña temporal al usuario.';
redirect('user-created.php');
