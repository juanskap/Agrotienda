<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$user = requireAdmin();
$stats = dashboardStats();
$ordersToday = ordersToday();
$ordersYesterday = ordersYesterday();
$recentOrders = db()->query("SELECT * FROM orders WHERE strftime('%Y-%m-%d', created_at) < strftime('%Y-%m-%d', 'now', '-1 day') ORDER BY id DESC LIMIT 5")->fetchAll();
$recentMessages = recentContactMessages(6);
$criticalStock = lowStockProducts(5);
$pendingCount = pendingOrdersCount();
$monthRevenue = revenueThisMonth();

$statusClasses = [
    'Recibido' => 'warning',
    'Preparando' => 'warning',
    'Enviado' => 'success',
    'Entregado' => 'success',
    'Cancelado' => 'danger',
];

renderHeader('Admin', ['admin_area' => true]);
?>
<section class="toolbar">
  <a class="btn primary" href="admin_products.php"><i class="fa-solid fa-plus"></i> Nuevo producto</a>
  <a class="btn" href="admin_orders.php?status=Recibido"><i class="fa-solid fa-clipboard-list"></i> Pedidos pendientes <?= $pendingCount > 0 ? '(' . $pendingCount . ')' : '' ?></a>
  <a class="btn" href="admin_messages.php?status=Nuevo"><i class="fa-solid fa-message"></i> Mensajes sin leer</a>
  <a class="btn" href="admin_inventory.php?filter=low"><i class="fa-solid fa-triangle-exclamation"></i> Stock bajo</a>
</section>

<section class="stats">
  <a class="stat" href="admin_products.php">
    <strong><?= (int) $stats['products'] ?></strong>
    <span class="muted">productos</span>
  </a>
  <a class="stat" href="admin_users.php">
    <strong><?= (int) $stats['customers'] ?></strong>
    <span class="muted">clientes</span>
  </a>
  <a class="stat" href="admin_orders.php">
    <strong><?= (int) $stats['orders'] ?></strong>
    <span class="muted">pedidos</span>
  </a>
  <a class="stat" href="admin_messages.php">
    <strong><?= (int) $stats['messages'] ?></strong>
    <span class="muted">mensajes</span>
  </a>
  <a class="stat" href="admin_orders.php">
    <strong><?= count($ordersToday) ?></strong>
    <span class="muted">pedidos hoy</span>
  </a>
  <a class="stat" href="admin_orders.php">
    <strong><?= money($stats['revenue']) ?></strong>
    <span class="muted">facturacion total</span>
  </a>
  <a class="stat" href="admin_orders.php">
    <strong><?= money($monthRevenue) ?></strong>
    <span class="muted">facturado este mes</span>
  </a>
</section>

<?php if ($criticalStock): ?>
  <section class="card stock-alert">
    <div class="line">
      <h3>Stock critico</h3>
      <a class="btn" href="admin_inventory.php?filter=low"><i class="fa-solid fa-warehouse"></i> Ver inventario</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th>Stock</th>
            <th>Accion</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($criticalStock as $item): ?>
            <tr>
              <td><?= e($item['name']) ?></td>
              <td><span class="status-pill danger"><?= (int) $item['stock'] ?> uds</span></td>
              <td class="table-actions">
                <a class="btn" href="admin_products.php?id=<?= (int) $item['id'] ?>"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                <a class="btn" href="admin_inventory.php"><i class="fa-solid fa-boxes"></i> Movimiento</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>

<section class="split">
  <article class="card">
    <div class="line">
      <h3>Pedidos de hoy <?= count($ordersToday) > 0 ? '(' . count($ordersToday) . ')' : '' ?></h3>
      <a class="btn" href="admin_orders.php"><i class="fa-solid fa-eye"></i> Ver todos</a>
    </div>
    <?php if (!$ordersToday): ?>
      <div class="empty">Todavia no hay pedidos registrados hoy.</div>
    <?php else: ?>
      <?php foreach ($ordersToday as $order): ?>
        <div class="list-item">
          <div class="line">
            <div>
              <strong>#<?= (int) $order['id'] ?> - <?= e($order['customer_name']) ?></strong>
              <span class="table-subtext"><?= e(date('H:i', strtotime($order['created_at']))) ?></span>
            </div>
            <span class="price"><?= money((float) $order['total']) ?></span>
          </div>
          <div class="line">
            <span class="status-pill <?= e($statusClasses[$order['status']] ?? 'warning') ?>"><?= e($order['status']) ?></span>
            <a class="btn" href="order.php?id=<?= (int) $order['id'] ?>"><i class="fa-solid fa-eye"></i> Ver detalle</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($ordersYesterday): ?>
      <details style="margin-top:16px;">
        <summary style="cursor:pointer;font-weight:700;color:var(--muted);">Pedidos de ayer (<?= count($ordersYesterday) ?>)</summary>
        <div class="list" style="margin-top:10px;">
          <?php foreach ($ordersYesterday as $order): ?>
            <div class="list-item">
              <div class="line">
                <div>
                  <strong>#<?= (int) $order['id'] ?> - <?= e($order['customer_name']) ?></strong>
                  <span class="table-subtext"><?= e(date('H:i', strtotime($order['created_at']))) ?></span>
                </div>
                <span class="price"><?= money((float) $order['total']) ?></span>
              </div>
              <div class="line">
                <span class="status-pill <?= e($statusClasses[$order['status']] ?? 'warning') ?>"><?= e($order['status']) ?></span>
                <a class="btn" href="order.php?id=<?= (int) $order['id'] ?>"><i class="fa-solid fa-eye"></i> Ver detalle</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </details>
    <?php endif; ?>

    <?php if ($recentOrders): ?>
      <details style="margin-top:12px;">
        <summary style="cursor:pointer;font-weight:700;color:var(--muted);">Pedidos anteriores</summary>
        <div class="list" style="margin-top:10px;">
          <?php foreach ($recentOrders as $order): ?>
            <div class="list-item">
              <div class="line">
                <div>
                  <strong>#<?= (int) $order['id'] ?> - <?= e($order['customer_name']) ?></strong>
                  <span class="table-subtext"><?= e(date('d/m', strtotime($order['created_at']))) ?> <?= e(date('H:i', strtotime($order['created_at']))) ?></span>
                </div>
                <span class="price"><?= money((float) $order['total']) ?></span>
              </div>
              <div class="line">
                <span class="status-pill <?= e($statusClasses[$order['status']] ?? 'warning') ?>"><?= e($order['status']) ?></span>
                <a class="btn" href="order.php?id=<?= (int) $order['id'] ?>"><i class="fa-solid fa-eye"></i> Ver detalle</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </details>
    <?php endif; ?>
  </article>
</section>

<section class="card">
  <div class="line">
    <h3>Mensajes de contacto</h3>
    <a class="btn" href="admin_messages.php"><i class="fa-solid fa-inbox"></i> Abrir bandeja</a>
  </div>
  <?php if (!$recentMessages): ?>
    <div class="empty">Todavia no hay mensajes recibidos.</div>
  <?php else: ?>
    <?php foreach ($recentMessages as $message): ?>
      <div class="list-item <?= $message['status'] === 'Nuevo' ? 'is-new' : '' ?>">
        <div class="line">
          <div>
            <strong><?= e($message['name']) ?></strong>
            <?php if ($message['status'] === 'Nuevo'): ?>
              <span class="status-pill warning">Nuevo</span>
            <?php endif; ?>
            <span class="table-subtext"><?= e($message['email']) ?><?= $message['phone'] !== '' ? ' | ' . e($message['phone']) : '' ?></span>
          </div>
          <span class="muted"><?= e(date('d/m/Y H:i', strtotime($message['created_at']))) ?></span>
        </div>
        <p><?= e($message['message']) ?></p>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<?php renderFooter(); ?>
