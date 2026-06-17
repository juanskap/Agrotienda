<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$loggedUser = currentUser();
if ($loggedUser) {
    redirect($loggedUser['role'] === 'admin' ? 'admin.php' : 'store.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        loginUser($user);
        setFlash('success', 'Sesion iniciada correctamente.');
        redirect($user['role'] === 'admin' ? 'admin.php' : 'store.php');
    }

    setFlash('error', 'Correo o contrasena incorrectos.');
    redirect('login.php');
}

renderHeader('Ingresar');
?>
<section class="card" style="max-width:720px; margin:0 auto;">
  <span class="eyebrow">Acceso</span>
  <h2>Iniciar sesion</h2>
  <p class="muted">Usa una cuenta existente para acceder a tus pedidos y datos de compra.</p>
  <form class="form" method="post">
    <?= csrfField() ?>
    <div class="field">
      <label for="email">Correo</label>
      <input id="email" name="email" type="email" required>
    </div>
    <div class="field">
      <label for="password">Contrasena</label>
      <input id="password" name="password" type="password" required>
    </div>
    <button class="btn primary" type="submit">Entrar</button>
  </form>
  <div class="actions" style="margin-top:18px;">
    <a class="btn" href="recuperar-password.php">Recuperar contrasena</a>
    <a class="btn" href="register.php">Crear cuenta de cliente</a>
  </div>
</section>
<?php renderFooter(); ?>
