<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        setFlash('error', 'El nombre no puede estar vacio.');
        redirect('account.php');
    }

    $stmt = db()->prepare(
        'UPDATE users SET name = :name, phone = :phone, address = :address WHERE id = :id'
    );
    $stmt->execute([
        'name' => $name,
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'id' => $user['id'],
    ]);
    setFlash('success', 'Perfil actualizado.');
    redirect('account.php');
}

$orders = ordersForUser((int) $user['id']);
$favorites = userFavorites((int) $user['id']);
$user = currentUser();

renderHeader('Mi cuenta');
?>
<section class="split">
  <article class="card" id="mi-cuenta">
    <span class="eyebrow">Perfil</span>
    <h2>Mi cuenta</h2>
    <form class="form" method="post">
      <?= csrfField() ?>
      <div class="field">
        <label for="name">Nombre</label>
        <input id="name" name="name" value="<?= e($user['name']) ?>" required>
      </div>
      <div class="grid-2">
        <div class="field">
          <label for="email">Correo</label>
          <input id="email" value="<?= e($user['email']) ?>" disabled>
        </div>
        <div class="field">
          <label for="phone">Telefono</label>
          <input id="phone" name="phone" value="<?= e($user['phone']) ?>">
        </div>
      </div>
      <div class="field">
        <label for="address">Direccion</label>
        <textarea id="address" name="address"><?= e($user['address']) ?></textarea>
      </div>
      <button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button>
    </form>
  </article>

  <aside class="card" id="mis-pedidos">
    <h2>Mis pedidos</h2>
    <?php if (!$orders): ?>
      <div class="empty">Todavia no tienes pedidos. Cuando compres algo apareceran aqui.</div>
    <?php else: ?>
      <div class="list">
        <?php foreach ($orders as $order): ?>
          <div class="list-item">
            <div class="line">
              <strong>Pedido #<?= (int) $order['id'] ?></strong>
              <span class="price"><?= money((float) $order['total']) ?></span>
            </div>
            <p class="muted">Estado: <?= e($order['status']) ?> | Fecha: <?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></p>
            <a class="btn" href="order.php?id=<?= (int) $order['id'] ?>"><i class="fa-solid fa-eye"></i> Ver detalle</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </aside>
</section>

<section id="favoritos">
<section class="section-head" style="margin-top:32px;">
  <div>
    <span class="eyebrow">Favoritos</span>
    <h2>Mis productos favoritos</h2>
  </div>
  <a class="btn" href="store.php"><i class="fa-solid fa-store"></i> Ir a la tienda</a>
</section>

<section class="product-grid storefront-grid">
  <?php if (!$favorites): ?>
    <div class="card empty">No tienes productos favoritos todavia. Marca la estrella en cualquier producto para agregarlo.</div>
  <?php endif; ?>
  <?php foreach ($favorites as $product): ?>
    <article class="product product-storefront">
      <a class="product-media" href="product.php?id=<?= (int) $product['id'] ?>">
        <img src="<?= e(productImage($product)) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        <span class="product-quick-view">
          <strong>Ver detalle</strong>
          <small><?= e($product['short_description']) ?></small>
        </span>
      </a>
      <div class="product-body">
        <small><?= e($product['category']) ?> | <?= e($product['brand']) ?></small>
        <h3><?= e($product['name']) ?></h3>
        <p class="muted"><?= e($product['short_description']) ?></p>
        <div class="product-purchase">
          <span class="price"><?= money((float) $product['price']) ?></span>
          <form class="fav-form" method="post" action="favorite.php">
            <?= csrfField() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <button class="fav-btn is-fav" type="submit" title="Quitar de favoritos"><i class="fa-solid fa-heart"></i></button>
          </form>
        </div>
        <form class="quick-buy-form" method="post" action="store.php">
          <?= csrfField() ?>
          <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
          <input type="hidden" name="quantity" value="1">
          <input type="hidden" name="_redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
          <button class="btn primary full" type="submit"><i class="fa-solid fa-cart-plus"></i> Agregar al carrito</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</section>
<?php renderFooter(); ?>
