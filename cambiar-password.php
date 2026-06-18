<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($password) < 8) {
        setFlash('error', 'La contrasena debe tener al menos 8 caracteres.');
        redirect('cambiar-password.php?token=' . urlencode($token));
    }

    if ($password !== $confirm) {
        setFlash('error', 'Las contrasenas no coinciden.');
        redirect('cambiar-password.php?token=' . urlencode($token));
    }

    if (!completePasswordReset($token, $password)) {
        setFlash('error', 'El enlace de recuperacion no es valido o ha expirado.');
        redirect('recuperar-password.php');
    }

    setFlash('success', 'Contrasena actualizada correctamente. Ahora puedes iniciar sesion.');
    redirect('login.php');
}

$valid = validatePasswordResetToken($token);

renderHeader('Cambiar contrasena');
?>
<section class="card" style="max-width:720px; margin:0 auto;">
  <?php if ($valid): ?>
    <span class="eyebrow">Restablecer</span>
    <h2>Crear nueva contrasena</h2>
    <p class="muted">Ingresa tu nueva contrasena de acceso.</p>
    <form class="form" method="post">
      <?= csrfField() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="field">
        <label for="password">Nueva contrasena</label>
        <input id="password" name="password" type="password" required minlength="8" placeholder="Minimo 8 caracteres">
      </div>
      <div class="field">
        <label for="confirm">Confirmar contrasena</label>
        <input id="confirm" name="confirm" type="password" required minlength="8" placeholder="Repite la contrasena">
      </div>
      <button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Actualizar contrasena</button>
    </form>
  <?php else: ?>
    <span class="eyebrow">Error</span>
    <h2>Enlace invalido o expirado</h2>
    <p class="muted">El enlace de recuperacion no es valido o ya ha expirado. Solicita uno nuevo.</p>
    <div class="actions" style="margin-top:18px;">
      <a class="btn primary" href="recuperar-password.php"><i class="fa-solid fa-paper-plane"></i> Solicitar nuevo enlace</a>
    </div>
  <?php endif; ?>
  <div class="actions" style="margin-top:18px;">
    <a class="btn" href="login.php"><i class="fa-solid fa-arrow-left"></i> Volver al login</a>
  </div>
</section>
<?php renderFooter(); ?>
