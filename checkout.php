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
    requireValidCsrfToken();

    $payload = [
        'customer_name' => trim($_POST['customer_name'] ?? ''),
        'customer_email' => trim($_POST['customer_email'] ?? ''),
        'customer_phone' => trim($_POST['customer_phone'] ?? ''),
        'payment_method' => trim($_POST['payment_method'] ?? 'Efectivo'),
        'shipping_address' => trim($_POST['shipping_address'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
    ];

    if (in_array('', [$payload['customer_name'], $payload['customer_email'], $payload['customer_phone'], $payload['shipping_address']], true)) {
        setFlash('error', 'Completa los datos del checkout.');
        redirect('checkout.php');
    }

    if (!filter_var($payload['customer_email'], FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Ingresa un correo valido para el pedido.');
        redirect('checkout.php');
    }

    $emailOwner = db()->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
    $emailOwner->execute([
        'email' => $payload['customer_email'],
        'id' => $user['id'],
    ]);

    if ($emailOwner->fetch()) {
        setFlash('error', 'Ese correo ya pertenece a otra cuenta.');
        redirect('checkout.php');
    }

    try {
        $orderId = createOrder($user, $payload);
        $emailSent = sendOrderTicketEmail($orderId);
        setFlash(
            'success',
            $emailSent
                ? 'Pedido registrado correctamente. Enviamos el ticket al correo indicado.'
                : 'Pedido registrado correctamente. El ticket esta listo; configura SMTP en XAMPP para enviarlo por correo automaticamente.'
        );
        redirect('order.php?id=' . $orderId);
    } catch (RuntimeException $error) {
        setFlash('error', $error->getMessage());
        redirect('cart.php');
    }
}

renderHeader('Checkout');
?>
<section class="store-hero checkout-hero">
  <div>
    <span class="eyebrow">Checkout</span>
    <h1>Datos de entrega</h1>
    <p>Confirma quien recibe el pedido, a donde debe llegar y cualquier referencia importante.</p>
  </div>
  <a class="btn light" href="cart.php">Editar carrito</a>
</section>

<section class="split checkout-layout">
  <article class="card checkout-card">
    <div>
      <span class="eyebrow">Paso final</span>
      <h2>Confirmar compra</h2>
      <p class="muted">Estos datos se guardaran en el pedido para coordinar la entrega.</p>
    </div>
    <form class="form checkout-form" method="post">
      <?= csrfField() ?>
      <div class="checkout-section">
        <h3>Contacto</h3>
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
          <label for="customer_phone">Telefono o WhatsApp</label>
          <input id="customer_phone" name="customer_phone" value="<?= e($user['phone']) ?>" required>
        </div>
      </div>

      <div class="checkout-section">
        <h3>Entrega</h3>
        <div class="field">
          <label for="payment_method">Forma de pago</label>
          <select id="payment_method" name="payment_method">
            <option>Efectivo</option>
            <option>Transferencia bancaria</option>
            <option>Pago contra entrega</option>
            <option>PayPal</option>
          </select>
        </div>
        <div class="field">
          <label for="shipping_address">Direccion donde quieres recibir el pedido</label>
          <textarea id="shipping_address" name="shipping_address" placeholder="Ciudad, sector, calle principal, numeracion y referencia..." required><?= e($user['address']) ?></textarea>
        </div>
        <div class="field">
          <label for="notes">Notas para el pedido</label>
          <textarea id="notes" name="notes" placeholder="Horario de entrega, referencia, persona que recibe, observaciones..."></textarea>
        </div>
      </div>

      <div class="checkout-actions">
        <a class="btn" href="cart.php">Volver al carrito</a>
        <button class="btn primary" type="submit">Confirmar compra</button>
      </div>
    </form>
  </article>

  <aside class="card checkout-summary">
    <span class="eyebrow">Resumen</span>
    <h2>Pedido listo</h2>
    <div class="list">
      <?php foreach ($totals['items'] as $item): ?>
        <div class="checkout-mini-item">
          <img src="<?= e(productImage($item)) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
          <div>
            <strong><?= e($item['name']) ?></strong>
            <span class="muted">Cantidad <?= (int) $item['quantity'] ?> | <?= money((float) $item['price']) ?> c/u</span>
          </div>
          <strong><?= money((float) $item['line_total']) ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="panel summary-lines">
      <div class="line"><span>Subtotal</span><strong><?= money((float) $totals['subtotal']) ?></strong></div>
      <div class="line"><span>Envio</span><strong><?= money((float) $totals['shipping']) ?></strong></div>
      <div class="line total-line"><span>Total</span><strong class="price"><?= money((float) $totals['total']) ?></strong></div>
    </div>
    <div class="panel">
      <strong>Mi idea para este flujo</strong>
      <p class="muted">Carrito para revisar cantidades; checkout para direccion, contacto y notas; pedido final para seguimiento.</p>
    </div>
  </aside>
</section>
<?php renderFooter(); ?>
