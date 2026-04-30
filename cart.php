<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $action = $_POST['action'] ?? 'update';

    if ($action === 'clear') {
        clearCart();
        setFlash('success', 'Carrito vaciado.');
        redirect('cart.php');
    }

    $quantities = $_POST['qty'] ?? [];
    $removeId = (int) ($_POST['remove_id'] ?? 0);

    if ($removeId > 0) {
        unset($quantities[$removeId]);
    }

    updateCart($quantities);
    $messages = syncCartInventory();
    $successMessage = $removeId > 0 ? 'Producto retirado del carrito.' : 'Carrito actualizado.';
    setFlash($messages ? 'error' : 'success', $messages ? implode(' ', $messages) : $successMessage);
    redirect('cart.php');
}

$inventoryMessages = syncCartInventory();
if ($inventoryMessages) {
    setFlash('error', implode(' ', $inventoryMessages));
    redirect('cart.php');
}

$totals = cartTotals();

renderHeader('Carrito');
?>
<section class="cart-showcase">
  <div>
    <span class="eyebrow">Carrito</span>
    <h1>Shopping Cart</h1>
    <p>Revisa productos, cantidades y total antes de pasar a los datos de entrega.</p>
  </div>
  <a class="btn light" href="store.php">Seguir comprando</a>
</section>

<section class="card cart-board">
  <div class="cart-window-bar" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>
  <div class="cart-board-head">
    <h2>Tu carrito</h2>
    <?php if ($totals['items']): ?>
      <span class="stock-pill"><?= count($totals['items']) ?> producto<?= count($totals['items']) === 1 ? '' : 's' ?></span>
    <?php endif; ?>
  </div>
    <?php if (!$totals['items']): ?>
      <div class="empty cart-empty">
        <strong>Tu carrito esta vacio.</strong>
        <span>Ve a la tienda y agrega algunos productos para iniciar tu pedido.</span>
        <a class="btn primary" href="store.php">Explorar tienda</a>
      </div>
    <?php else: ?>
      <form class="form cart-form" method="post">
        <?= csrfField() ?>
        <div class="cart-table-wrap">
          <table class="cart-table">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($totals['items'] as $item): ?>
                <tr>
                  <td data-label="Producto">
                    <div class="cart-product-cell">
                      <button class="cart-remove" type="submit" name="remove_id" value="<?= (int) $item['id'] ?>" aria-label="Quitar <?= e($item['name']) ?>">x</button>
                      <a class="cart-item-media" href="product.php?id=<?= (int) $item['id'] ?>">
                        <img src="<?= e(productImage($item)) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
                      </a>
                      <div>
                        <strong><?= e($item['name']) ?></strong>
                        <span><?= e($item['category']) ?> | Stock <?= (int) $item['stock'] ?></span>
                      </div>
                    </div>
                  </td>
                  <td data-label="Precio"><strong><?= money((float) $item['price']) ?></strong></td>
                  <td data-label="Cantidad">
                    <label class="visually-hidden" for="qty-<?= (int) $item['id'] ?>">Cantidad de <?= e($item['name']) ?></label>
                    <input id="qty-<?= (int) $item['id'] ?>" class="qty-input" type="number" name="qty[<?= (int) $item['id'] ?>]" value="<?= (int) $item['quantity'] ?>" min="0" max="<?= (int) $item['stock'] ?>">
                  </td>
                  <td data-label="Subtotal"><strong><?= money((float) $item['line_total']) ?></strong></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="cart-form-actions">
          <button class="btn primary" type="submit" name="action" value="update">Actualizar carrito</button>
          <button class="btn" type="submit" name="action" value="clear" onclick="return confirm('Se vaciara todo el carrito.');">Vaciar carrito</button>
        </div>
      </form>
    <?php endif; ?>

  <div class="cart-bottom">
    <div class="coupon-box">
      <label for="coupon_code">Codigo promocional</label>
      <div class="coupon-row">
        <input id="coupon_code" name="coupon_code" placeholder="Codigo de cupon" disabled>
        <button class="btn" type="button" disabled>Aplicar cupon</button>
      </div>
      <p class="muted">Dejamos este espacio listo para activar cupones despues.</p>
    </div>
    <aside class="cart-summary">
      <h2>Totales del carrito</h2>
      <div class="summary-lines">
        <div class="line"><span>Subtotal</span><strong><?= money((float) $totals['subtotal']) ?></strong></div>
        <div class="line"><span>Envio</span><strong><?= money((float) $totals['shipping']) ?></strong></div>
        <div class="line total-line"><span>Total</span><strong class="price"><?= money((float) $totals['total']) ?></strong></div>
      </div>
      <?php if ($totals['items']): ?>
        <a class="btn primary full" href="checkout.php">Proceder al checkout</a>
      <?php endif; ?>
    </aside>
  </div>
</section>
<?php renderFooter(); ?>
