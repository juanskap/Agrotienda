<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function renderHeader(string $title, array $options = []): void
{
    $user = currentUser();
    $flash = pullFlash();
    $pageClass = $options['page_class'] ?? '';
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?> | Agrotienda</title>
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <link rel="stylesheet" href="assets/app.css">
</head>
<body class="<?= e($pageClass) ?>">
  <div class="shell">
    <header class="topbar">
      <a class="brand" href="index.php">Agro<span>Tienda</span></a>
      <nav class="nav">
        <a href="index.php">Inicio</a>
        <a href="store.php">Tienda</a>
        <a href="cart.php">Carrito</a>
        <?php if ($user): ?>
          <a href="account.php">Mi cuenta</a>
          <?php if ($user['role'] === 'admin'): ?>
            <a href="admin.php">Admin</a>
            <a href="admin_products.php">Productos</a>
          <?php endif; ?>
          <a href="logout.php">Salir</a>
        <?php else: ?>
          <a href="login.php">Ingresar</a>
          <a href="register.php">Crear cuenta</a>
        <?php endif; ?>
      </nav>
    </header>
    <?php if ($flash): ?>
      <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <main class="page">
<?php
}

function renderFooter(): void
{
    ?>
    </main>
    <footer class="footer">
      <div>
        <strong>Agrotienda funcional</strong>
        <p>Catalogo, cuenta, carrito y pedidos ya conectados en PHP con SQLite.</p>
      </div>
      <div class="footer-note">
        Acceso demo admin: <code>admin@agrotienda.local</code> / <code>admin123</code>
      </div>
    </footer>
  </div>
</body>
</html>
<?php
}
