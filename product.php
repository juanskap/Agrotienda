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

    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    try {
        addToCart($productId, $quantity);
        setFlash('success', 'Producto agregado al carrito.');
        redirect('cart.php');
    } catch (RuntimeException $error) {
        setFlash('error', $error->getMessage());
        redirect('product.php?id=' . $productId);
    }
}

renderHeader('Producto');
?>
<section class="card split">
  <article class="card">
    <div class="product-media is-detail">
      <img src="<?= e(productImage($product)) ?>" alt="<?= e($product['name']) ?>">
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
      <div class="field">
        <label for="quantity">Cantidad</label>
        <input id="quantity" type="number" name="quantity" value="1" min="1" max="<?= (int) $product['stock'] ?>">
      </div>
      <div class="actions">
        <button class="btn primary" type="submit">Agregar al carrito</button>
        <a class="btn" href="store.php">Volver a la tienda</a>
      </div>
    </form>
  </article>
</section>
<?php renderFooter(); ?>
