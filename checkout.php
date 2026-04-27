<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$user = requireLogin();
$inventoryMessages = syncCartInventory();
if ($inventoryMessages) {
    setFlash('error', implode(' ', $inventoryMessages));
    redirect('cart.php');
}

$totals = cartTotals();

if (!$totals['items']) {
    setFlash('error', 'Tu carrito esta vacio.');
    redirect('cart.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'customer_name' => trim($_POST['customer_name'] ?? ''),
        'customer_email' => trim($_POST['customer_email'] ?? ''),
        'customer_phone' => trim($_POST['customer_phone'] ?? ''),
        'shipping_address' => trim($_POST['shipping_address'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
    ];

    if (in_array('', [$payload['customer_name'], $payload['customer_email'], $payload['customer_phone'], $payload['shipping_address']], true)) {
        setFlash('error', 'Completa los datos del checkout.');
        redirect('checkout.php');
    }

    try {
        $orderId = createOrder($user, $payload);
        setFlash('success', 'Pedido registrado correctamente.');
        redirect('order.php?id=' . $orderId);
    } catch (RuntimeException $error) {
        setFlash('error', $error->getMessage());
        redirect('cart.php');
    }
}

renderHeader('Checkout');
?>
<section class="split">
  <article class="card">
    <h2>Checkout</h2>
    <p class="muted">Confirma tus datos y genera el pedido.</p>
    <form class="form" method="post">
      <div class="grid-2">
        <div class="field">
          <label for="customer_name">Nombre completo</label>
          <input id="customer_name" name="customer_name" value="<?= e($user['name']) ?>" required>
        </div>
        <div class="field">
          <label for="customer_email">Correo</label>
          <input id="customer_email" name="customer_email" type="email" value="<?= e($user['email']) ?>" required>
        </div>
      </div>
      <div class="field">
        <label for="customer_phone">Telefono</label>
        <input id="customer_phone" name="customer_phone" value="<?= e($user['phone']) ?>" required>
      </div>
      <div class="field">
        <label for="shipping_address">Direccion de envio</label>
        <textarea id="shipping_address" name="shipping_address" required><?= e($user['address']) ?></textarea>
      </div>
      <div class="field">
        <label for="notes">Notas del pedido</label>
        <textarea id="notes" name="notes" placeholder="Horario de entrega, referencia, observaciones..."></textarea>
      </div>
      <button class="btn primary" type="submit">Confirmar compra</button>
    </form>
  </article>

  <aside class="card">
    <h2>Resumen final</h2>
    <div class="list">
      <?php foreach ($totals['items'] as $item): ?>
        <div class="list-item">
          <div class="line">
            <strong><?= e($item['name']) ?></strong>
            <span><?= money((float) $item['line_total']) ?></span>
          </div>
          <p class="muted">Cantidad <?= (int) $item['quantity'] ?> | <?= money((float) $item['price']) ?> c/u</p>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="panel">
      <div class="line"><span>Subtotal</span><strong><?= money((float) $totals['subtotal']) ?></strong></div>
      <div class="line"><span>Envio</span><strong><?= money((float) $totals['shipping']) ?></strong></div>
      <div class="line"><span>Total</span><strong class="price"><?= money((float) $totals['total']) ?></strong></div>
    </div>
  </aside>
</section>
<?php renderFooter(); ?>
