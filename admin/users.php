<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$user = require_login();
$database = db();
$editing = null;

if (isset($_GET['edit'])) {
    $statement = $database->prepare('SELECT id, username, display_name, role FROM users WHERE id = :id');
    $statement->execute(['id' => (int) $_GET['edit']]);
    $editing = $statement->fetch() ?: null;
    if ($editing !== null && $user['role'] !== 'admin') {
        http_response_code(403);
        exit('Solo el administrador puede editar usuarios.');
    }
}

$users = $database->query('SELECT id, username, display_name, role, created_at FROM users ORDER BY display_name')->fetchAll();
page_start('Usuarios');
show_flash();
?>
<section class="form-card">
  <h2><?= $editing === null ? 'Crear usuario' : 'Editar usuario' ?></h2>
  <?php if ($editing !== null && (int) $editing['id'] === (int) $user['id']): ?>
    <div class="alert alert-info">No puedes cambiar tu propio rol desde esta pantalla.</div>
  <?php endif; ?>
  <form method="post" action="user-save.php" class="user-form">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <?php if ($editing !== null): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
    <label for="username">Usuario</label>
    <input class="form-control" id="username" name="username" required minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" value="<?= h($editing['username'] ?? '') ?>">
    <label for="display_name">Nombre para mostrar</label>
    <input class="form-control" id="display_name" name="display_name" required maxlength="100" value="<?= h($editing['display_name'] ?? '') ?>">
    <label for="role">Nivel de acceso</label>
    <select class="form-select" id="role" name="role" <?= $user['role'] !== 'admin' || ($editing !== null && (int) $editing['id'] === (int) $user['id']) ? 'disabled' : '' ?>>
      <option value="user" <?= ($editing['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>Nivel 1</option>
      <?php if ($user['role'] === 'admin'): ?>
        <option value="admin" <?= ($editing['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
      <?php endif; ?>
    </select>
    <?php if ($editing !== null && (int) $editing['id'] === (int) $user['id']): ?>
      <input type="hidden" name="role" value="<?= h($editing['role']) ?>">
    <?php endif; ?>
    <button class="btn btn-primary" type="submit"><?= $editing === null ? 'Crear usuario' : 'Guardar cambios' ?></button>
    <?php if ($editing !== null): ?><a class="btn btn-outline-secondary" href="users.php">Cancelar</a><?php endif; ?>
  </form>
  <?php if ($editing === null): ?>
    <p class="form-hint">La contraseña temporal se generará al crear la cuenta y se mostrará una sola vez. El usuario deberá cambiarla al ingresar.</p>
    <?php if ($user['role'] !== 'admin'): ?><p class="form-hint">Las cuentas creadas por nivel 1 tendrán ese mismo nivel.</p><?php endif; ?>
  <?php endif; ?>
</section>

<section class="form-card">
  <h2>Cuentas existentes</h2>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Nombre</th><th>Usuario</th><th>Acceso</th><?php if ($user['role'] === 'admin'): ?><th>Acciones</th><?php endif; ?></tr></thead>
      <tbody>
      <?php foreach ($users as $account): ?>
        <tr>
          <td><?= h($account['display_name']) ?></td>
          <td><?= h($account['username']) ?></td>
          <td><?= $account['role'] === 'admin' ? 'Administrador' : 'Nivel 1' ?></td>
          <?php if ($user['role'] === 'admin'): ?>
            <td class="actions">
              <a class="btn btn-sm btn-outline-primary" href="users.php?edit=<?= (int) $account['id'] ?>">Editar</a>
              <?php if ((int) $account['id'] !== (int) $user['id']): ?>
                <form method="post" action="reset-password.php" onsubmit="return confirm('¿Generar una contraseña temporal nueva para este usuario?')">
                  <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                  <button class="btn btn-sm btn-outline-secondary" type="submit">Restablecer contraseña</button>
                </form>
                <form method="post" action="user-delete.php" onsubmit="return confirm('¿Eliminar esta cuenta?')">
                  <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                </form>
              <?php endif; ?>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php page_end(); ?>
