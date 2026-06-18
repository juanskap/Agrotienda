<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Ingresa un correo valido.');
        redirect('recuperar-password.php');
    }

    createPasswordReset($email);

    setFlash('success', 'Si el correo esta registrado, recibiras un enlace para restablecer tu contrasena.');
    redirect('login.php');
}

renderHeader('Recuperar contrasena');
?>
<section class="card" style="max-width:720px; margin:0 auto;">
  <span class="eyebrow">Recuperacion</span>
  <h2>Recuperar contrasena</h2>
  <p class="muted">Ingresa el correo asociado a tu cuenta y te enviaremos un enlace para restablecer tu contrasena.</p>
  <form class="form" method="post">
    <?= csrfField() ?>
    <div class="field">
      <label for="email">Correo electronico</label>
      <input id="email" name="email" type="email" required placeholder="tucorreo@ejemplo.com">
    </div>
    <button class="btn primary" type="submit"><i class="fa-solid fa-paper-plane"></i> Enviar enlace de recuperacion</button>
  </form>
  <div class="actions" style="margin-top:18px;">
    <a class="btn" href="login.php"><i class="fa-solid fa-arrow-left"></i> Volver al login</a>
  </div>
</section>
<?php renderFooter(); ?>
