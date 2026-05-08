<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$user = requireAdmin();
$stats = dashboardStats();
$recentOrders = db()->query('SELECT * FROM orders ORDER BY id DESC LIMIT 8')->fetchAll();
$recentMessages = recentContactMessages(6);
$products = allProducts();

renderHeader('Admin', ['admin_area' => true]);
?>
<section class="stats">
  <article class="stat"><strong><?= $stats['products'] ?></strong><span class="muted">productos</span></article>
  <article class="stat"><strong><?= $stats['customers'] ?></strong><span class="muted">clientes</span></article>
  <article class="stat"><strong><?= $stats['orders'] ?></strong><span class="muted">pedidos</span></article>
  <article class="stat"><strong><?= $stats['messages'] ?></strong><span class="muted">mensajes</span></article>
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
    <div class="actions" style="margin-bottom:12px;">
      <a class="btn" href="admin_inventory.php">Abrir inventario completo</a>
    </div>
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

<section class="card">
  <h3>Mensajes de contacto</h3>
  <div class="actions" style="margin-bottom:12px;">
    <a class="btn" href="admin_messages.php">Abrir bandeja</a>
  </div>
  <?php if (!$recentMessages): ?>
    <div class="empty">Todavia no hay mensajes recibidos.</div>
  <?php else: ?>
    <div class="list">
      <?php foreach ($recentMessages as $message): ?>
        <div class="list-item">
          <div class="line">
            <strong><?= e($message['name']) ?></strong>
            <span class="muted"><?= e(date('d/m/Y H:i', strtotime($message['created_at']))) ?></span>
          </div>
          <p class="muted"><?= e($message['email']) ?><?= $message['phone'] !== '' ? ' | ' . e($message['phone']) : '' ?></p>
          <p><?= e($message['message']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php renderFooter(); ?>
