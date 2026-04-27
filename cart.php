<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    updateCart($_POST['qty'] ?? []);
    setFlash('success', 'Carrito actualizado.');
    redirect('cart.php');
}

$totals = cartTotals();

renderHeader('Carrito');
?>
<section class="split">
  <article class="card">
    <h2>Tu carrito</h2>
    <p class="muted">Ajusta cantidades y continua al checkout cuando todo esté listo.</p>
    <?php if (!$totals['items']): ?>
      <div class="empty">Tu carrito esta vacio. Ve a la tienda y agrega algunos productos.</div>
    <?php else: ?>
      <form class="form" method="post">
        <?php foreach ($totals['items'] as $item): ?>
          <div class="panel">
            <div class="line">
              <div>
                <strong><?= e($item['name']) ?></strong>
                <p class="muted"><?= e($item['short_description']) ?></p>
              </div>
              <span class="price"><?= money((float) $item['line_total']) ?></span>
            </div>
            <div class="grid-2">
              <div class="field">
                <label>Cantidad</label>
                <input type="number" name="qty[<?= (int) $item['id'] ?>]" value="<?= (int) $item['quantity'] ?>" min="0">
              </div>
              <div class="field">
                <label>Precio unitario</label>
                <input value="<?= money((float) $item['price']) ?>" disabled>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <button class="btn primary" type="submit">Actualizar carrito</button>
      </form>
    <?php endif; ?>
  </article>

  <aside class="card">
    <h2>Resumen</h2>
    <div class="panel list">
      <div class="line"><span>Subtotal</span><strong><?= money((float) $totals['subtotal']) ?></strong></div>
      <div class="line"><span>Envio</span><strong><?= money((float) $totals['shipping']) ?></strong></div>
      <div class="line"><span>Total</span><strong class="price"><?= money((float) $totals['total']) ?></strong></div>
    </div>
    <div class="actions">
      <a class="btn" href="store.php">Seguir comprando</a>
      <?php if ($totals['items']): ?>
        <a class="btn primary" href="checkout.php">Ir al checkout</a>
      <?php endif; ?>
    </div>
  </aside>
</section>
<?php renderFooter(); ?>
