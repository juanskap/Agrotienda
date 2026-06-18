<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$user = requireLogin();
$favProducts = userFavorites((int) $user['id']);
$favIds = array_column($favProducts, 'id');

renderHeader('Favoritos');
?>
<section class="store-hero" style="background:linear-gradient(135deg,#f5e6d3,#faf0e0)">
  <div>
    <span class="eyebrow">Favoritos</span>
    <h1>Tus productos guardados</h1>
    <p class="muted">Productos que marcaste para volver mas tarde o comparar antes de comprar.</p>
  </div>
  <a class="btn light" href="store.php"><i class="fa-solid fa-store"></i> Ir a la tienda</a>
</section>

<?php if (!$favProducts): ?>
  <section class="card" style="text-align:center;padding:48px">
    <p class="muted" style="font-size:1.1rem">Todavia no tienes productos favoritos.</p>
    <a class="btn primary" href="store.php" style="margin-top:12px"><i class="fa-solid fa-store"></i> Explorar productos</a>
  </section>
<?php else: ?>
  <section class="product-grid storefront-grid">
    <?php foreach ($favProducts as $product): ?>
      <article class="product product-storefront">
        <a class="product-media" href="product.php?id=<?= (int) $product['id'] ?>">
          <img src="<?= e(productImage($product)) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
          <span class="product-quick-view">
            <strong><i class="fa-solid fa-eye"></i> Ver detalle</strong>
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
          <form class="quick-buy-form" method="post">
            <?= csrfField() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="quantity" value="1">
            <input type="hidden" name="_redirect" value="favoritos.php">
            <button class="btn primary full" type="submit"><i class="fa-solid fa-cart-plus"></i> Agregar al carrito</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>
<?php renderFooter(); ?>
