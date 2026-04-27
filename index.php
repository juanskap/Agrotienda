<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$featured = array_slice(allProducts(), 0, 3);
$user = currentUser();

renderHeader('Inicio');
?>
<section class="card hero split">
  <div>
    <span class="eyebrow">MVP funcional</span>
    <h1>Tu agrotienda ya puede vender de verdad</h1>
    <p class="muted">Registrate, inicia sesion, agrega productos al carrito, confirma pedidos y revisa tu historial. Todo corre en PHP con base SQLite dentro de tu proyecto actual.</p>
    <div class="actions">
      <a class="btn primary" href="store.php">Ir a la tienda</a>
      <?php if ($user): ?>
        <a class="btn" href="account.php">Ver mi cuenta</a>
      <?php else: ?>
        <a class="btn" href="login.php">Entrar al sistema</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="panel">
    <h3>Accesos demo</h3>
    <p class="muted">Administrador: <strong>admin@agrotienda.local</strong> / <strong>admin123</strong></p>
    <p class="muted">Cliente: <strong>cliente@agrotienda.local</strong> / <strong>cliente123</strong></p>
  </div>
</section>

<section class="stats">
  <article class="stat"><strong><?= count(allProducts()) ?></strong><span class="muted">productos listos</span></article>
  <article class="stat"><strong>1</strong><span class="muted">flujo de compra activo</span></article>
  <article class="stat"><strong>24/7</strong><span class="muted">sitio disponible en XAMPP</span></article>
  <article class="stat"><strong><?= $user ? 'Si' : 'No' ?></strong><span class="muted">sesion iniciada</span></article>
</section>

<section class="card">
  <div class="line">
    <div>
      <h2>Productos destacados</h2>
      <p class="muted">Una base inicial para que el flujo ya sea usable mientras luego afinamos presentación y contenidos.</p>
    </div>
    <a class="btn" href="store.php">Ver catalogo completo</a>
  </div>
  <div class="product-grid">
    <?php foreach ($featured as $product): ?>
      <article class="product">
        <div class="product-media">
          <img src="<?= e(productImage($product)) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        </div>
        <div class="product-body">
          <small><?= e($product['category']) ?></small>
          <h3><?= e($product['name']) ?></h3>
          <p class="muted"><?= e($product['short_description']) ?></p>
          <div class="line">
            <span class="price"><?= money((float) $product['price']) ?></span>
            <span class="muted">Stock <?= (int) $product['stock'] ?></span>
          </div>
          <div class="actions">
            <a class="btn primary" href="product.php?id=<?= (int) $product['id'] ?>">Ver detalle</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php renderFooter(); ?>
