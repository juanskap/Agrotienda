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

    if ($action === 'confirm') {
        if ($messages) {
            setFlash('error', implode(' ', $messages));
            redirect('cart.php');
        }

        $user = requireLogin();
        $payload = [
            'customer_name' => trim($_POST['customer_name'] ?? ''),
            'customer_email' => trim($_POST['customer_email'] ?? ''),
            'customer_phone' => trim($_POST['customer_phone'] ?? ''),
            'payment_method' => trim($_POST['payment_method'] ?? 'Efectivo'),
            'notes' => trim($_POST['notes'] ?? ''),
        ];
        $country = 'Ecuador';
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $addressDetail = trim($_POST['address_detail'] ?? '');

        if (in_array('', [$payload['customer_name'], $payload['customer_email'], $payload['customer_phone'], $province, $city, $addressDetail], true)) {
            setFlash('error', 'Completa nombre, correo, telefono, provincia, ciudad y direccion.');
            redirect('cart.php');
        }

        $payload['shipping_address'] = implode(', ', [
            $country,
            $province,
            $city,
            $addressDetail,
        ]);

        if (!filter_var($payload['customer_email'], FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Ingresa un correo valido para el pedido.');
            redirect('cart.php');
        }

        if (!in_array($payload['payment_method'], ['Efectivo', 'Transferencia bancaria', 'Pago contra entrega', 'PayPal'], true)) {
            setFlash('error', 'Selecciona una forma de pago valida.');
            redirect('cart.php');
        }

        $emailOwner = db()->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
        $emailOwner->execute([
            'email' => $payload['customer_email'],
            'id' => $user['id'],
        ]);

        if ($emailOwner->fetch()) {
            setFlash('error', 'Ese correo ya pertenece a otra cuenta.');
            redirect('cart.php');
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
$user = currentUser();
$addressValue = $user ? (string) $user['address'] : '';

renderHeader('Carrito');
?>
<section class="sale-panel" id="sale-panel">
  <header class="sale-header">
    <div>
      <span class="sale-kicker">Carrito</span>
      <h1>Venta actual</h1>
      <p><?= $totals['items'] ? count($totals['items']) . ' producto' . (count($totals['items']) === 1 ? '' : 's') . ' agregado' . (count($totals['items']) === 1 ? '' : 's') . '.' : 'No hay productos agregados.' ?></p>
    </div>
    <div class="sale-header-actions">
      <?php if ($totals['items']): ?>
        <form method="post">
          <?= csrfField() ?>
          <button class="sale-ghost-btn" type="submit" name="action" value="clear" onclick="return confirm('Se vaciara todo el carrito.');">Vaciar</button>
        </form>
      <?php endif; ?>
      <a class="sale-icon-btn" href="store.php" aria-label="Cerrar carrito">x</a>
    </div>
  </header>

  <?php if ($totals['items']): ?>
    <form id="cart-update-form" method="post">
      <?= csrfField() ?>
    </form>
  <?php endif; ?>

  <div class="sale-content">
    <div class="sale-products">
      <div class="sale-section-head">
        <span>Productos</span>
        <?php if ($totals['items']): ?>
          <button class="sale-chip" type="submit" form="cart-update-form" name="action" value="update" formnovalidate>Ajusta cantidades aqui</button>
        <?php endif; ?>
      </div>
    <?php if (!$totals['items']): ?>
      <div class="sale-empty">
        <span>Agrega productos desde el catalogo para iniciar una venta.</span>
      </div>
    <?php else: ?>
      <div class="sale-items">
        <?php foreach ($totals['items'] as $item): ?>
          <article class="sale-item">
            <button class="sale-remove" type="submit" form="cart-update-form" name="remove_id" value="<?= (int) $item['id'] ?>" aria-label="Quitar <?= e($item['name']) ?>" formnovalidate>x</button>
            <a class="sale-item-media" href="product.php?id=<?= (int) $item['id'] ?>">
              <img src="<?= e(productImage($item)) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
            </a>
            <div class="sale-item-copy">
              <strong><?= e($item['name']) ?></strong>
              <span><?= e($item['category']) ?> | Stock <?= (int) $item['stock'] ?></span>
            </div>
            <strong class="sale-item-price"><?= money((float) $item['price']) ?></strong>
            <div class="sale-qty">
              <label for="qty-<?= (int) $item['id'] ?>">Cant.</label>
              <input id="qty-<?= (int) $item['id'] ?>" form="cart-update-form" type="number" name="qty[<?= (int) $item['id'] ?>]" value="<?= (int) $item['quantity'] ?>" min="0" max="<?= (int) $item['stock'] ?>">
            </div>
            <strong class="sale-line-total"><?= money((float) $item['line_total']) ?></strong>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    </div>

    <aside class="sale-side">
      <section class="sale-card sale-summary-card">
        <span class="sale-kicker">Resumen</span>
        <div class="sale-summary-lines">
          <div class="line"><span>Subtotal</span><strong><?= money((float) $totals['subtotal']) ?></strong></div>
          <div class="line"><span>Envio</span><strong><?= money((float) $totals['shipping']) ?></strong></div>
        </div>
        <div class="sale-total">
          <span>Total a cobrar</span>
          <strong><?= money((float) $totals['total']) ?></strong>
        </div>
      </section>

      <section class="sale-card sale-checkout-panel" id="sale-checkout-panel" hidden>
        <span class="sale-kicker">Datos de cobro</span>
        <div class="sale-field">
          <label for="sale_customer">Cliente</label>
          <input id="sale_customer" form="cart-update-form" name="customer_name" value="<?= $user ? e($user['name']) : '' ?>" placeholder="Nombre completo" <?= $totals['items'] ? 'required' : '' ?>>
        </div>
        <div class="sale-field">
          <label for="sale_email">Correo</label>
          <input id="sale_email" form="cart-update-form" name="customer_email" type="email" value="<?= $user ? e($user['email']) : '' ?>" placeholder="correo@ejemplo.com" <?= $totals['items'] ? 'required' : '' ?>>
        </div>
        <div class="sale-field">
          <label for="sale_phone">Telefono</label>
          <input id="sale_phone" form="cart-update-form" name="customer_phone" value="<?= $user ? e($user['phone']) : '' ?>" placeholder="WhatsApp o telefono" <?= $totals['items'] ? 'required' : '' ?>>
        </div>
        <div class="sale-field">
          <label for="sale_payment">Pago</label>
          <select id="sale_payment" form="cart-update-form" name="payment_method">
            <option>Efectivo</option>
            <option>Transferencia bancaria</option>
            <option>Pago contra entrega</option>
            <option>PayPal</option>
          </select>
        </div>
        <div class="sale-field">
          <label for="sale_country">Pais</label>
          <input id="sale_country" value="Ecuador" readonly>
        </div>
        <div class="sale-field">
          <label for="sale_province">Provincia</label>
          <select id="sale_province" form="cart-update-form" name="province" <?= $totals['items'] ? 'required' : '' ?>>
            <option value="">Selecciona provincia</option>
          </select>
        </div>
        <div class="sale-field">
          <label for="sale_city">Ciudad</label>
          <select id="sale_city" form="cart-update-form" name="city" <?= $totals['items'] ? 'required' : '' ?>>
            <option value="">Selecciona ciudad</option>
          </select>
        </div>
        <div class="sale-field">
          <label for="sale_address">Direccion</label>
          <input id="sale_address" form="cart-update-form" name="address_detail" value="<?= e($addressValue) ?>" placeholder="Calle, numero, referencia" <?= $totals['items'] ? 'required' : '' ?>>
        </div>
        <div class="sale-field">
          <label for="sale_note">Nota</label>
          <input id="sale_note" form="cart-update-form" name="notes" placeholder="Observacion de la venta">
        </div>
        <div class="sale-checkout-actions">
          <button class="sale-confirm" type="submit" form="cart-update-form" name="action" value="confirm">Finalizar compra</button>
        </div>
      </section>
    </aside>
  </div>

  <footer class="sale-footer">
    <span>Revisa cantidades y confirma la venta cuando este lista.</span>
    <?php if ($totals['items']): ?>
      <button class="sale-confirm" type="button" id="sale-start-checkout">Confirmar compra</button>
    <?php else: ?>
      <a class="sale-confirm" href="store.php">Agregar productos</a>
    <?php endif; ?>
  </footer>
</section>

<?php if ($totals['items']): ?>
  <div class="sale-modal" id="sale-checkout-modal" hidden>
    <div class="sale-modal-backdrop" data-close-checkout></div>
    <div class="sale-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="sale-modal-title">
      <div class="sale-modal-head">
        <div>
          <span class="sale-kicker">Confirmar compra</span>
          <h2 id="sale-modal-title">Datos de entrega y pago</h2>
        </div>
        <button class="sale-icon-btn" type="button" data-close-checkout aria-label="Cerrar">x</button>
      </div>
      <div id="sale-modal-body"></div>
    </div>
  </div>
<?php endif; ?>

<section class="cart-after-actions">
  <a class="btn" href="store.php">Seguir comprando</a>
</section>
<script>
  const startCheckout = document.getElementById('sale-start-checkout');
  const checkoutPanel = document.getElementById('sale-checkout-panel');
  const checkoutModal = document.getElementById('sale-checkout-modal');
  const modalBody = document.getElementById('sale-modal-body');
  const provinceSelect = document.getElementById('sale_province');
  const citySelect = document.getElementById('sale_city');

  const ecuadorLocations = {
    Azuay: ['Cuenca', 'Gualaceo', 'Paute', 'Santa Isabel'],
    Bolivar: ['Guaranda', 'Chillanes', 'San Miguel'],
    Canar: ['Azogues', 'Biblian', 'La Troncal'],
    Carchi: ['Tulcan', 'Montufar', 'Mira'],
    Chimborazo: ['Riobamba', 'Alausi', 'Guano'],
    Cotopaxi: ['Latacunga', 'La Mana', 'Pujili'],
    'El Oro': ['Machala', 'Pasaje', 'Santa Rosa', 'Huaquillas'],
    Esmeraldas: ['Esmeraldas', 'Atacames', 'Quininde'],
    Galapagos: ['Puerto Baquerizo Moreno', 'Puerto Ayora', 'Puerto Villamil'],
    Guayas: ['Guayaquil', 'Daule', 'Duran', 'Milagro', 'Samborondon'],
    Imbabura: ['Ibarra', 'Otavalo', 'Cotacachi'],
    Loja: ['Loja', 'Catamayo', 'Macara'],
    'Los Rios': ['Babahoyo', 'Quevedo', 'Ventanas', 'Vinces'],
    Manabi: ['Portoviejo', 'Manta', 'Chone', 'Jipijapa', 'Montecristi'],
    'Morona Santiago': ['Macas', 'Gualaquiza', 'Sucua'],
    Napo: ['Tena', 'Archidona', 'El Chaco'],
    Orellana: ['Francisco de Orellana', 'Loreto', 'La Joya de los Sachas'],
    Pastaza: ['Puyo', 'Mera', 'Santa Clara'],
    Pichincha: ['Quito', 'Cayambe', 'Mejia', 'Ruminahui'],
    'Santa Elena': ['Santa Elena', 'La Libertad', 'Salinas'],
    'Santo Domingo de los Tsachilas': ['Santo Domingo', 'La Concordia'],
    Sucumbios: ['Nueva Loja', 'Shushufindi', 'Cascales'],
    Tungurahua: ['Ambato', 'Banos', 'Pelileo'],
    'Zamora Chinchipe': ['Zamora', 'Yantzaza', 'Zumba']
  };

  if (provinceSelect && citySelect) {
    Object.keys(ecuadorLocations).forEach((province) => {
      provinceSelect.add(new Option(province, province));
    });

    provinceSelect.addEventListener('change', () => {
      citySelect.length = 1;
      (ecuadorLocations[provinceSelect.value] || []).forEach((city) => {
        citySelect.add(new Option(city, city));
      });
    });
  }

  if (startCheckout && checkoutPanel && checkoutModal && modalBody) {
    startCheckout.addEventListener('click', () => {
      checkoutPanel.hidden = false;
      checkoutModal.hidden = false;
      modalBody.appendChild(checkoutPanel);
      document.body.classList.add('modal-open');
      startCheckout.hidden = true;
      const firstInput = checkoutPanel.querySelector('input:not([readonly]), select');
      if (firstInput) {
        firstInput.focus();
      }
    });

    document.querySelectorAll('[data-close-checkout]').forEach((closeButton) => {
      closeButton.addEventListener('click', () => {
        checkoutModal.hidden = true;
        document.body.classList.remove('modal-open');
        startCheckout.hidden = false;
      });
    });
  }
</script>
<?php renderFooter(); ?>
