<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$productId = (int) ($_GET['id'] ?? 0);
$product = productById($productId);

if (!$product) {
    setFlash('error', 'Producto no encontrado.');
    redirect('store.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $redirectTo = trim($_POST['_redirect'] ?? 'product.php?id=' . $productId);
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    try {
        addToCart($productId, $quantity);
        setFlash('success', 'Producto agregado al carrito.');
        redirect($redirectTo);
    } catch (RuntimeException $error) {
        setFlash('error', $error->getMessage());
        redirect($redirectTo);
    }
}

$user = currentUser();
$isFav = $user && isFavorite((int) $user['id'], $productId);

renderHeader('Producto');
?>
<section class="card split">
  <article class="card">
    <div class="product-media is-detail">
      <img src="<?= e(productImage($product)) ?>" alt="<?= e($product['name']) ?>">
      <?php if ($user): ?>
        <form class="fav-form is-detail-fav" method="post" action="favorite.php">
          <?= csrfField() ?>
          <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
          <button class="fav-btn <?= $isFav ? 'is-fav' : '' ?>" type="submit" title="Favorito"><i class="fa-<?= $isFav ? 'solid' : 'regular' ?> fa-heart"></i></button>
        </form>
      <?php endif; ?>
    </div>
  </article>
  <article class="card">
    <span class="eyebrow"><?= e($product['category']) ?></span>
    <h2><?= e($product['name']) ?></h2>
    <p class="muted"><?= e($product['description']) ?></p>
    <div class="line">
      <span class="price"><?= money((float) $product['price']) ?></span>
      <span class="muted">Marca <?= e($product['brand']) ?> | Stock <?= (int) $product['stock'] ?></span>
    </div>
    <form class="form" method="post">
      <?= csrfField() ?>
      <input type="hidden" name="_redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
      <div class="field">
        <label for="quantity">Cantidad</label>
        <input id="quantity" type="number" name="quantity" value="1" min="1" max="<?= (int) $product['stock'] ?>">
      </div>
      <div class="actions">
        <button class="btn primary" type="submit"><i class="fa-solid fa-cart-plus"></i> Agregar al carrito</button>
        <a class="btn" href="store.php"><i class="fa-solid fa-store"></i> Volver a la tienda</a>
      </div>
    </form>
  </article>
</section>
<?php renderFooter(); ?>
