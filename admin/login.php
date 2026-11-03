<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (current_user() !== null) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $statement = db()->prepare(
        'SELECT id, username, display_name, password_hash, role, must_change_password
         FROM users WHERE username = :username LIMIT 1'
    );
    $statement->execute(['username' => $username]);
    $user = $statement->fetch();

    if ($user === false || !password_verify($password, $user['password_hash'])) {
        $error = 'Usuario o contraseña incorrectos.';
    } else {
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        redirect($user['must_change_password'] ? 'password.php' : 'index.php');
    }
}

page_start('Iniciar sesión');
?>
<section class="login-card">
  <p>Acceso privado para gestionar las solicitudes de clientes.</p>
  <?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
  <form method="post" autocomplete="on">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <label for="username">Usuario</label>
    <input class="form-control" id="username" name="username" autocomplete="username" required maxlength="50">
    <label for="password">Contraseña</label>
    <input class="form-control" id="password" type="password" name="password" autocomplete="current-password" maxlength="72" required>
    <button class="btn btn-primary w-100" type="submit">Ingresar</button>
  </form>
  <a class="btn btn-outline-secondary w-100 mt-2" href="../index.html">Volver a la página de inicio</a>
</section>
<?php page_end(); ?>
