<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function renderHeader(string $title, array $options = []): void
{
    $user = currentUser();
    $flash = pullFlash();
    $pageClass = $options['page_class'] ?? '';
    $searchValue = trim((string) ($options['search'] ?? ''));
    $activeCategory = trim((string) ($options['active_category'] ?? ''));
    $categoryLinks = categories();
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
    <div class="utilitybar">
      <div class="utilitybar-group">
        <span>WhatsApp</span>
        <span>099 000 0000</span>
        <span>Envios a domicilio</span>
      </div>
      <div class="utilitybar-group">
        <a href="contacto.html">Contacto</a>
        <a href="nosotros.html">Nosotros</a>
      </div>
    </div>
    <header class="topbar">
      <div class="brand-wrap">
        <a class="brand" href="index.php"><span class="brand-mark">A</span>Agro<span>Tienda</span></a>
        <p class="brand-subtitle">Compra insumos, herramientas y soluciones para tu campo.</p>
      </div>
      <form class="searchbar" method="get" action="store.php">
        <input type="search" name="q" value="<?= e($searchValue) ?>" placeholder="Buscar semillas, fertilizantes, herramientas...">
        <?php if ($activeCategory !== ''): ?>
          <input type="hidden" name="category" value="<?= e($activeCategory) ?>">
        <?php endif; ?>
        <button class="btn primary" type="submit">Buscar</button>
      </form>
      <nav class="nav nav-account">
        <a href="store.php">Tienda</a>
        <a href="cart.php">Carrito</a>
        <?php if ($user): ?>
          <a href="account.php">Mi cuenta</a>
          <?php if ($user['role'] === 'admin'): ?>
            <a href="admin.php">Admin</a>
          <?php endif; ?>
          <a href="logout.php">Salir</a>
        <?php else: ?>
          <a href="login.php">Ingresar</a>
        <?php endif; ?>
      </nav>
    </header>
    <nav class="section-nav">
      <a href="store.php" class="<?= $activeCategory === '' ? 'is-active' : '' ?>">Tienda</a>
      <a href="index.php">Inicio</a>
      <a href="nosotros.html">Nosotros</a>
      <a href="contacto.html">Contacto</a>
    </nav>
    <?php if ($categoryLinks): ?>
      <nav class="category-strip">
        <a class="category-link <?= $activeCategory === '' ? 'is-active' : '' ?>" href="store.php">
          <span class="category-icon">T</span>
          <span>Tienda</span>
        </a>
        <?php foreach ($categoryLinks as $categoryLink): ?>
          <a class="category-link <?= $activeCategory === $categoryLink ? 'is-active' : '' ?>" href="store.php?category=<?= urlencode($categoryLink) ?>">
            <span class="category-icon"><?= e(strtoupper(substr($categoryLink, 0, 1))) ?></span>
            <span><?= e($categoryLink) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>
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
