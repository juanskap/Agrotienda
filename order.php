<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$user = requireLogin();
$orderId = (int) ($_GET['id'] ?? 0);
$order = orderById($orderId, $user['role'] === 'admin' ? null : (int) $user['id']);

if (!$order) {
    setFlash('error', 'Pedido no encontrado.');
    redirect('account.php');
}

renderHeader('Pedido');
?>
<section class="order-confirmation card">
  <div>
    <span class="eyebrow">Compra confirmada</span>
    <h2>Tu pedido fue registrado correctamente</h2>
    <p class="muted">Generamos el ticket del pedido #<?= (int) $order['id'] ?> para el correo <?= e($order['customer_email']) ?>. Si el correo del servidor esta configurado, se enviara automaticamente.</p>
  </div>
  <div class="order-actions">
    <button class="btn primary" type="button" onclick="window.print()">Imprimir ticket</button>
    <a class="btn" href="store.php">Seguir comprando</a>
  </div>
</section>

<section class="card printable-order">
  <div class="line">
    <div>
      <strong class="print-brand">Agrotienda</strong>
      <span class="eyebrow">Pedido #<?= (int) $order['id'] ?></span>
      <h2>Detalle del pedido</h2>
      <p class="muted">Estado <?= e($order['status']) ?> | Fecha <?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></p>
    </div>
    <div class="order-actions">
      <button class="btn primary" type="button" onclick="window.print()">Imprimir</button>
      <a class="btn" href="<?= $user['role'] === 'admin' ? 'admin.php' : 'account.php' ?>">Volver</a>
    </div>
  </div>

  <div class="grid-2">
    <article class="panel">
      <h3>Cliente y envio</h3>
      <p class="muted"><strong>Nombre:</strong> <?= e($order['customer_name']) ?></p>
      <p class="muted"><strong>Correo:</strong> <?= e($order['customer_email']) ?></p>
      <p class="muted"><strong>Telefono:</strong> <?= e($order['customer_phone']) ?></p>
      <p class="muted"><strong>Forma de pago:</strong> <?= e($order['payment_method'] ?? 'Efectivo') ?></p>
      <p class="muted"><strong>Direccion:</strong> <?= e($order['shipping_address']) ?></p>
      <p class="muted"><strong>Notas:</strong> <?= e($order['notes'] ?: 'Sin notas') ?></p>
    </article>
    <article class="panel">
      <h3>Totales</h3>
      <div class="line"><span>Subtotal</span><strong><?= money((float) $order['subtotal']) ?></strong></div>
      <div class="line"><span>Envio</span><strong><?= money((float) $order['shipping']) ?></strong></div>
      <div class="line"><span>Total</span><strong class="price"><?= money((float) $order['total']) ?></strong></div>
    </article>
  </div>

  <article class="card">
    <h3>Productos</h3>
    <table>
      <thead>
        <tr>
          <th>Producto</th>
          <th>Cantidad</th>
          <th>Precio</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($order['items'] as $item): ?>
          <tr>
            <td><?= e($item['product_name']) ?></td>
            <td><?= (int) $item['quantity'] ?></td>
            <td><?= money((float) $item['unit_price']) ?></td>
            <td><?= money((float) $item['line_total']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </article>
</section>
<?php renderFooter(); ?>
