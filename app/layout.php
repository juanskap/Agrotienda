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
    $isAdminArea = (bool) ($options['admin_area'] ?? false);
    $categoryLinks = categories();
    $assetVersion = (string) (@filemtime(APP_ROOT . '/assets/app.css') ?: time());
    $currentPage = basename((string) parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH));
    $isCurrent = static fn (string $page): string => $currentPage === $page ? ' is-active' : '';
    $isCurrentGroup = static fn (array $pages): string => in_array($currentPage, $pages, true) ? 'is-active' : '';
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?> | Agrotienda</title>
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <link rel="stylesheet" href="assets/app.css?v=<?= e($assetVersion) ?>">
</head>
<body class="<?= e(trim($pageClass . ($isAdminArea ? ' admin-area' : ''))) ?>">
  <div class="shell">
    <?php if ($isAdminArea): ?>
      <div class="utilitybar admin-utilitybar">
        <div class="utilitybar-group">
          <span>Zona privada</span>
          <span>Administrador</span>
        </div>
        <div class="utilitybar-group">
          <a href="index.php">Ver tienda publica</a>
          <a href="logout.php">Salir</a>
        </div>
      </div>
    <?php else: ?>
      <div class="utilitybar">
        <div class="utilitybar-group">
          <span>WhatsApp</span>
          <span>099 000 0000</span>
          <span>Envios a domicilio</span>
        </div>
        <div class="utilitybar-group">
          <a href="contacto.php">Contacto</a>
          <a href="nosotros.php">Nosotros</a>
        </div>
      </div>
    <?php endif; ?>
    <header class="topbar">
      <div class="brand-wrap">
        <a class="brand" href="<?= $isAdminArea ? 'admin.php' : 'index.php' ?>"><span class="brand-mark">A</span>Agro<span>Tienda</span></a>
        <p class="brand-subtitle"><?= $isAdminArea ? 'Administrador: ' . e($user['name']) . ' | Panel privado para administrar productos, pedidos y mensajes.' : 'Compra insumos, herramientas y soluciones para tu campo.' ?></p>
      </div>
      <?php if (!$isAdminArea): ?>
        <form class="searchbar" method="get" action="store.php">
          <input type="search" name="q" value="<?= e($searchValue) ?>" placeholder="Buscar semillas, fertilizantes, herramientas...">
          <?php if ($activeCategory !== ''): ?>
            <input type="hidden" name="category" value="<?= e($activeCategory) ?>">
          <?php endif; ?>
          <button class="btn primary" type="submit"><span class="btn-symbol">⌕</span> Buscar</button>
        </form>
      <?php endif; ?>
      <nav class="nav nav-account">
        <?php if ($isAdminArea): ?>
          <a class="<?= $isCurrentGroup(['admin.php', 'admin_products.php']) ?>" href="admin.php">Admin</a>
          <a href="logout.php">Salir</a>
        <?php else: ?>
          <a class="<?= $isCurrentGroup(['store.php', 'product.php']) ?>" href="store.php"><svg class="btn-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10h16"></path><path d="M5 10l2-5h10l2 5"></path><path d="M6 10v9h12v-9"></path><path d="M9 19v-5h6v5"></path></svg> Tienda</a>
          <a class="<?= trim($isCurrent('cart.php')) ?>" href="cart.php"><svg class="btn-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.4 11h10.2l2-7H7"></path><circle cx="10" cy="20" r="1.5"></circle><circle cx="17" cy="20" r="1.5"></circle></svg> Carrito</a>
          <?php if ($user): ?>
            <a class="<?= $isCurrentGroup(['account.php', 'order.php']) ?>" href="account.php">Mi cuenta</a>
            <?php if ($user['role'] === 'admin'): ?>
              <a class="<?= $isCurrentGroup(['admin.php', 'admin_products.php']) ?>" href="admin.php">Admin</a>
            <?php endif; ?>
            <a href="logout.php">Salir</a>
          <?php else: ?>
            <a class="<?= trim($isCurrent('login.php')) ?>" href="login.php"><svg class="btn-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M5 20c1.5-4 12.5-4 14 0"></path></svg> Ingresar</a>
            <a class="<?= trim($isCurrent('register.php')) ?>" href="register.php"><span class="btn-symbol">+</span> Crear cuenta</a>
          <?php endif; ?>
        <?php endif; ?>
      </nav>
    </header>
    <?php if ($isAdminArea): ?>
      <nav class="section-nav admin-section-nav">
        <a href="admin.php" class="<?= trim($isCurrent('admin.php')) ?>">Resumen</a>
        <a href="admin_inventory.php" class="<?= $isCurrentGroup(['admin_inventory.php', 'admin_products.php']) ?>">Inventario</a>
        <a href="admin_orders.php" class="<?= trim($isCurrent('admin_orders.php')) ?>">Pedidos</a>
        <a href="admin_messages.php" class="<?= trim($isCurrent('admin_messages.php')) ?>">Mensajes</a>
        <a href="admin_users.php" class="<?= trim($isCurrent('admin_users.php')) ?>">Clientes</a>
        <a href="store.php">Tienda publica</a>
      </nav>
    <?php else: ?>
      <nav class="section-nav">
        <a href="store.php" class="<?= $isCurrentGroup(['store.php', 'product.php']) ?>"><svg class="btn-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10h16"></path><path d="M5 10l2-5h10l2 5"></path><path d="M6 10v9h12v-9"></path><path d="M9 19v-5h6v5"></path></svg> Tienda</a>
        <a href="index.php" class="<?= $currentPage === 'index.php' ? 'is-active' : '' ?>">Inicio</a>
        <a href="nosotros.php" class="<?= $currentPage === 'nosotros.php' ? 'is-active' : '' ?>">Nosotros</a>
        <a href="contacto.php" class="<?= $currentPage === 'contacto.php' ? 'is-active' : '' ?>">Contacto</a>
      </nav>
    <?php endif; ?>
    <?php if (!$isAdminArea && $categoryLinks): ?>
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
        Panel administrativo protegido por cuenta autorizada.
      </div>
    </footer>
  </div>
  <script>
  function submitForm(form) {
    var btn = form.querySelector('.btn, .fav-btn');
    var isFav = form.classList.contains('fav-form');
    var data = new FormData(form);
    fetch(form.getAttribute('action') || window.location.href, { method: 'POST', body: data })
      .then(function(r) { return r.text(); })
      .then(function() {
        if (isFav) {
          var star = btn;
          if (star.classList.contains('is-fav')) {
            star.classList.remove('is-fav');
            star.textContent = '\u2606';
          } else {
            star.classList.add('is-fav');
            star.textContent = '\u2605';
          }
        } else {
          var originalText = btn.textContent;
          btn.disabled = true;
          btn.textContent = '\u2713';
          setTimeout(function() {
            btn.disabled = false;
            btn.textContent = originalText;
          }, 1500);
        }
      });
  }

  document.querySelectorAll('.quick-buy-form, .fav-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      submitForm(form);
    });
  });
  </script>
</body>
</html>
<?php
}
