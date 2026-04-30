<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        setFlash('error', 'Completa nombre, correo y contrasena.');
        redirect('register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Ingresa un correo valido.');
        redirect('register.php');
    }

    if (strlen($password) < 8) {
        setFlash('error', 'La contrasena debe tener al menos 8 caracteres.');
        redirect('register.php');
    }

    $stmt = db()->prepare(
        'INSERT INTO users (name, email, phone, address, password_hash, role, created_at)
         VALUES (:name, :email, :phone, :address, :password_hash, :role, :created_at)'
    );

    try {
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'customer',
            'created_at' => date('c'),
        ]);
    } catch (Throwable $error) {
        setFlash('error', 'Ese correo ya esta registrado.');
        redirect('register.php');
    }

    $user = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $user->execute(['email' => $email]);
    loginUser($user->fetch());
    setFlash('success', 'Cuenta creada correctamente.');
    redirect('account.php');
}

renderHeader('Registro');
?>
<section class="card" style="max-width:780px; margin:0 auto;">
  <span class="eyebrow">Nueva cuenta</span>
  <h2>Crear usuario cliente</h2>
  <form class="form" method="post">
    <?= csrfField() ?>
    <div class="grid-2">
      <div class="field">
        <label for="name">Nombre completo</label>
        <input id="name" name="name" required>
      </div>
      <div class="field">
        <label for="email">Correo</label>
        <input id="email" name="email" type="email" required>
      </div>
    </div>
    <div class="grid-2">
      <div class="field">
        <label for="phone">Telefono</label>
        <input id="phone" name="phone">
      </div>
      <div class="field">
        <label for="password">Contrasena</label>
        <input id="password" name="password" type="password" required>
      </div>
    </div>
    <div class="field">
      <label for="address">Direccion</label>
      <textarea id="address" name="address"></textarea>
    </div>
    <button class="btn primary" type="submit">Registrarme</button>
  </form>
</section>
<?php renderFooter(); ?>
