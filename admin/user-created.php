<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_login();

$password = $_SESSION['new_user_password'] ?? null;
$username = $_SESSION['new_user_name'] ?? null;
$message = $_SESSION['new_user_message'] ?? 'Cuenta creada. Entrega esta contraseña temporal al usuario.';
unset($_SESSION['new_user_password'], $_SESSION['new_user_name']);
unset($_SESSION['new_user_message']);
if (!is_string($password) || !is_string($username)) {
    redirect('users.php');
}

page_start('Credencial temporal');
?>
<section class="form-card narrow-card">
  <div class="alert alert-success"><?= h($message) ?> <strong><?= h($username) ?></strong></div>
  <p>Solo se muestra esta vez. El usuario deberá cambiarla al iniciar sesión.</p>
  <label for="temporary-password">Contraseña temporal</label>
  <input class="form-control temporary-password" id="temporary-password" value="<?= h($password) ?>" readonly>
  <a class="btn btn-primary mt-3" href="users.php">Volver a usuarios</a>
</section>
<?php page_end(); ?>
