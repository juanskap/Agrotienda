<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$user = requireAdmin();
$stats = dashboardStats();
$recentOrders = db()->query('SELECT * FROM orders ORDER BY id DESC LIMIT 8')->fetchAll();
$products = allProducts();

renderHeader('Admin');
?>
<section class="card hero">
  <span class="eyebrow">Zona privada</span>
  <h2>Panel de administracion</h2>
  <p class="muted">Esta version ya lee datos reales de usuarios, productos y pedidos guardados en la base local.</p>
  <div class="actions">
    <a class="btn primary" href="admin_products.php">Gestionar productos</a>
  </div>
</section>

<section class="stats">
  <article class="stat"><strong><?= $stats['products'] ?></strong><span class="muted">productos</span></article>
  <article class="stat"><strong><?= $stats['customers'] ?></strong><span class="muted">clientes</span></article>
  <article class="stat"><strong><?= $stats['orders'] ?></strong><span class="muted">pedidos</span></article>
  <article class="stat"><strong><?= money($stats['revenue']) ?></strong><span class="muted">facturacion</span></article>
</section>

<section class="split">
  <article class="card">
    <h3>Ultimos pedidos</h3>
    <?php if (!$recentOrders): ?>
      <div class="empty">Todavia no hay pedidos registrados.</div>
    <?php else: ?>
      <div class="list">
        <?php foreach ($recentOrders as $order): ?>
          <div class="list-item">
            <div class="line">
              <strong>Pedido #<?= (int) $order['id'] ?></strong>
              <span class="price"><?= money((float) $order['total']) ?></span>
            </div>
            <p class="muted"><?= e($order['customer_name']) ?> | <?= e($order['status']) ?></p>
            <a class="btn" href="order.php?id=<?= (int) $order['id'] ?>">Ver detalle</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>

  <article class="card">
    <h3>Inventario base</h3>
    <div class="list">
      <?php foreach ($products as $product): ?>
        <div class="list-item">
          <div class="line">
            <strong><?= e($product['name']) ?></strong>
            <span><?= money((float) $product['price']) ?></span>
          </div>
          <p class="muted"><?= e($product['category']) ?> | Stock <?= (int) $product['stock'] ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </article>
</section>
<?php renderFooter(); ?>
