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
    flash('No se puede eliminar esa cuenta.', 'error');
    redirect('users.php');
}

$database = db();
$database->beginTransaction();
$administrators = $database->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
$target = $database->prepare('SELECT role FROM users WHERE id = :id FOR UPDATE');
$target->execute(['id' => $id]);
$targetRole = $target->fetchColumn();
if ($targetRole === false) {
    $database->rollBack();
    flash('La cuenta ya no existe.', 'error');
    redirect('users.php');
}
if ($targetRole === 'admin' && count($administrators) <= 1) {
    $database->rollBack();
    flash('Debe existir al menos un administrador.', 'error');
    redirect('users.php');
}

$delete = $database->prepare('DELETE FROM users WHERE id = :id');
$delete->execute(['id' => $id]);
$database->commit();
flash('La cuenta se eliminó.');
redirect('users.php');
