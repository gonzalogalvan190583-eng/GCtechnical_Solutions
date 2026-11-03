<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}
verify_csrf();

$username = trim((string) ($_POST['username'] ?? ''));
$displayName = trim((string) ($_POST['display_name'] ?? ''));
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$role = (string) ($_POST['role'] ?? 'user');

if (array_key_exists('id', $_POST) && ($id === false || $id === null)) {
    http_response_code(400);
    exit('El identificador de usuario no es válido.');
}

if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username) || $displayName === '' || mb_strlen($displayName) > 100) {
    flash('Revisa el usuario y el nombre ingresados.', 'error');
    redirect('users.php');
}

try {
    $database = db();
    if ($id === false || $id === null) {
        $role = $user['role'] === 'admin' && $role === 'admin' ? 'admin' : 'user';
        $temporaryPassword = bin2hex(random_bytes(16));
        $statement = $database->prepare(
            'INSERT INTO users (username, display_name, password_hash, role, must_change_password)
             VALUES (:username, :display_name, :password_hash, :role, 1)'
        );
        $statement->execute([
            'username' => $username,
            'display_name' => $displayName,
            'password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
            'role' => $role,
        ]);
        $_SESSION['new_user_password'] = $temporaryPassword;
        $_SESSION['new_user_name'] = $username;
        $_SESSION['new_user_message'] = 'Cuenta creada. Entrega esta contraseña temporal al usuario.';
        redirect('user-created.php');
    }

    require_admin($user);
    if ((int) $id === (int) $user['id']) {
        $statement = $database->prepare('UPDATE users SET username = :username, display_name = :display_name WHERE id = :id');
        $statement->execute(['username' => $username, 'display_name' => $displayName, 'id' => $id]);
    } else {
        if (!in_array($role, ['admin', 'user'], true)) {
            flash('El nivel de acceso no es válido.', 'error');
            redirect('users.php');
        }
        $database->beginTransaction();
        $administrators = $database->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
        $targetRole = $database->prepare('SELECT role FROM users WHERE id = :id FOR UPDATE');
        $targetRole->execute(['id' => $id]);
        $currentRole = $targetRole->fetchColumn();
        if ($currentRole === false) {
            $database->rollBack();
            flash('La cuenta ya no existe.', 'error');
            redirect('users.php');
        }
        if ($currentRole === 'admin' && $role !== 'admin' && count($administrators) <= 1) {
            $database->rollBack();
            flash('Debe existir al menos un administrador.', 'error');
            redirect('users.php');
        }
        $statement = $database->prepare(
            'UPDATE users SET username = :username, display_name = :display_name, role = :role WHERE id = :id'
        );
        $statement->execute([
            'username' => $username,
            'display_name' => $displayName,
            'role' => $role,
            'id' => $id,
        ]);
        $database->commit();
    }
    flash('Los datos del usuario se actualizaron.');
    redirect('users.php');
} catch (PDOException $exception) {
    if (isset($database) && $database->inTransaction()) {
        $database->rollBack();
    }
    if ($exception->getCode() === '23000') {
        flash('Ese nombre de usuario ya está en uso.', 'error');
        redirect('users.php' . ($id !== false && $id !== null ? '?edit=' . $id : ''));
    }
    throw $exception;
}
