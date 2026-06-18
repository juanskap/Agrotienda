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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous">
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
          <a href="index.php"><i class="fa-solid fa-store"></i> Ver tienda publica</a>
          <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
        </div>
      </div>
    <?php else: ?>
      <div class="utilitybar">
        <div class="utilitybar-group">
          <span><i class="fa-brands fa-whatsapp"></i> WhatsApp</span>
          <span><i class="fa-solid fa-phone"></i> 099 000 0000</span>
          <span><i class="fa-solid fa-truck"></i> Envios a domicilio</span>
        </div>
        <div class="utilitybar-group">
          <a href="contacto.php"><i class="fa-solid fa-envelope"></i> Contacto</a>
          <a href="nosotros.php"><i class="fa-solid fa-seedling"></i> Nosotros</a>
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
          <button class="btn primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
        </form>
      <?php endif; ?>
      <nav class="nav nav-account">
        <?php if ($isAdminArea): ?>
          <a class="<?= $isCurrentGroup(['admin.php', 'admin_products.php']) ?>" href="admin.php"><i class="fa-solid fa-user-shield"></i> Admin</a>
          <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
        <?php else: ?>
          <a class="<?= $isCurrentGroup(['store.php', 'product.php']) ?>" href="store.php"><i class="fa-solid fa-store"></i> Tienda</a>
          <a class="<?= trim($isCurrent('cart.php')) ?>" href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Carrito</a>
          <?php if ($user): ?>
            <div class="dropdown">
              <a class="dropdown-trigger <?= $isCurrentGroup(['account.php', 'order.php']) ?>" href="account.php"><i class="fa-solid fa-user"></i> Perfil <span class="dropdown-arrow">▾</span></a>
              <div class="dropdown-menu">
                <a href="account.php#mi-cuenta">Mi cuenta</a>
                <a href="account.php#mis-pedidos">Pedidos</a>
                <a href="account.php#favoritos">Favoritos</a>
              </div>
            </div>
            <?php if ($user['role'] === 'admin'): ?>
              <a class="<?= $isCurrentGroup(['admin.php', 'admin_products.php']) ?>" href="admin.php"><i class="fa-solid fa-user-shield"></i> Admin</a>
            <?php endif; ?>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
          <?php else: ?>
            <a class="<?= trim($isCurrent('login.php')) ?>" href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Ingresar</a>
            <a class="<?= trim($isCurrent('register.php')) ?>" href="register.php"><i class="fa-solid fa-user-plus"></i> Crear cuenta</a>
          <?php endif; ?>
        <?php endif; ?>
      </nav>
    </header>
    <?php if ($isAdminArea): ?>
      <nav class="section-nav admin-section-nav">
        <a href="admin.php" class="<?= trim($isCurrent('admin.php')) ?>"><i class="fa-solid fa-chart-simple"></i> Resumen</a>
        <a href="admin_inventory.php" class="<?= $isCurrentGroup(['admin_inventory.php', 'admin_products.php']) ?>"><i class="fa-solid fa-warehouse"></i> Inventario</a>
        <a href="admin_orders.php" class="<?= trim($isCurrent('admin_orders.php')) ?>"><i class="fa-solid fa-clipboard-list"></i> Pedidos</a>
        <a href="admin_messages.php" class="<?= trim($isCurrent('admin_messages.php')) ?>"><i class="fa-solid fa-message"></i> Mensajes</a>
        <a href="admin_users.php" class="<?= trim($isCurrent('admin_users.php')) ?>"><i class="fa-solid fa-users"></i> Clientes</a>
        <a href="store.php"><i class="fa-solid fa-store"></i> Tienda publica</a>
      </nav>
    <?php else: ?>
      <nav class="section-nav">
        <a href="store.php" class="<?= $isCurrentGroup(['store.php', 'product.php']) ?>"><i class="fa-solid fa-store"></i> Tienda</a>
        <a href="index.php" class="<?= $currentPage === 'index.php' ? 'is-active' : '' ?>"><i class="fa-solid fa-house"></i> Inicio</a>
        <a href="nosotros.php" class="<?= $currentPage === 'nosotros.php' ? 'is-active' : '' ?>"><i class="fa-solid fa-seedling"></i> Nosotros</a>
        <a href="contacto.php" class="<?= $currentPage === 'contacto.php' ? 'is-active' : '' ?>"><i class="fa-solid fa-envelope"></i> Contacto</a>
      </nav>
    <?php endif; ?>
    <?php if (!$isAdminArea && $categoryLinks):
      $categoryIcons = [
        'Semillas' => 'fa-seedling',
        'Fertilizantes' => 'fa-flask',
        'Riego' => 'fa-water',
        'Herramientas' => 'fa-wrench',
        'Bioinsumos' => 'fa-leaf',
        'Equipos' => 'fa-gear',
        'Macetas' => 'fa-tree',
        'Plagas' => 'fa-shield-halved',
      ];
    ?>
      <nav class="category-strip">
        <a class="category-link <?= $activeCategory === '' ? 'is-active' : '' ?>" href="store.php">
          <span class="category-icon"><i class="fa-solid fa-store"></i></span>
          <span>Tienda</span>
        </a>
        <?php foreach ($categoryLinks as $categoryLink): ?>
          <?php $catIcon = $categoryIcons[$categoryLink] ?? 'fa-tag'; ?>
          <a class="category-link <?= $activeCategory === $categoryLink ? 'is-active' : '' ?>" href="store.php?category=<?= urlencode($categoryLink) ?>">
            <span class="category-icon"><i class="fa-solid <?= $catIcon ?>"></i></span>
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

      <div class="footer-social">
        <span>Siguenos</span>
        <div class="social-links">
          <a href="https://wa.me/593990000000" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
          <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
          <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="https://tiktok.com" target="_blank" rel="noopener" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
          <a href="https://youtube.com" target="_blank" rel="noopener" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
        </div>
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
          var icon = btn.querySelector('.fa-heart');
          if (btn.classList.contains('is-fav')) {
            btn.classList.remove('is-fav');
            icon.className = 'fa-regular fa-heart';
          } else {
            btn.classList.add('is-fav');
            icon.className = 'fa-solid fa-heart';
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
