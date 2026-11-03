<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$user = require_login();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmation = (string) ($_POST['confirm_password'] ?? '');

    $statement = db()->prepare('SELECT password_hash FROM users WHERE id = :id');
    $statement->execute(['id' => $user['id']]);
    $passwordHash = $statement->fetchColumn();

    if (!is_string($passwordHash) || !password_verify($currentPassword, $passwordHash)) {
        $error = 'La contraseña actual no es correcta.';
    } elseif (strlen($newPassword) < 12 || strlen($newPassword) > 72) {
        $error = 'La nueva contraseña debe tener entre 12 y 72 bytes.';
    } elseif ($newPassword !== $confirmation) {
        $error = 'La confirmación no coincide.';
    } elseif (password_verify($newPassword, $passwordHash)) {
        $error = 'La nueva contraseña debe ser diferente de la actual.';
    } else {
        $update = db()->prepare(
            'UPDATE users SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id'
        );
        $update->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $user['id'],
        ]);
        $_SESSION['user']['must_change_password'] = 0;
        flash('Tu contraseña se actualizó correctamente.');
        redirect('index.php');
    }
}

page_start('Cambiar contraseña');
show_flash();
?>
<section class="form-card narrow-card">
  <?php if (!empty($user['must_change_password'])): ?>
    <div class="alert alert-warning">Debes cambiar tu contraseña inicial antes de continuar.</div>
  <?php endif; ?>
  <?php if ($error !== ''): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
  <p>Usa una contraseña nueva de al menos 12 caracteres y máximo 72 bytes.</p>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <label for="current_password">Contraseña actual</label>
    <input class="form-control" id="current_password" type="password" name="current_password" autocomplete="current-password" required>
    <label for="new_password">Nueva contraseña</label>
    <input class="form-control" id="new_password" type="password" name="new_password" minlength="12" maxlength="72" autocomplete="new-password" required>
    <label for="confirm_password">Confirmar nueva contraseña</label>
    <input class="form-control" id="confirm_password" type="password" name="confirm_password" minlength="12" maxlength="72" autocomplete="new-password" required>
    <button class="btn btn-primary" type="submit">Guardar contraseña</button>
  </form>
</section>
<?php page_end(); ?>
