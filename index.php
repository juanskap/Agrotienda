<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

    $redirectTo = trim($_POST['_redirect'] ?? 'index.php');

    try {
        addToCart($productId, $quantity);
        setFlash('success', 'Producto agregado al carrito.');
        redirect($redirectTo);
    } catch (RuntimeException $error) {
        setFlash('error', $error->getMessage());
        redirect($redirectTo);
    }
}

$allProducts = allProducts();
$featured = array_slice($allProducts, 0, 5);
$categories = array_slice(categories(), 0, 4);
$user = currentUser();
$favIds = $user ? favoriteProductIds((int) $user['id']) : [];

renderHeader('Inicio');
?>
<section class="promo-hero">
  <div class="promo-copy">
    <span class="eyebrow">Temporada activa</span>
    <h1>Ofertas exclusivas para sembrar, cuidar y cosechar mejor</h1>
    <p>Encuentra semillas, fertilizantes, riego y herramientas en una vitrina pensada para comprar rapido y con inventario real.</p>
    <div class="actions">
      <a class="btn primary" href="store.php"><i class="fa-solid fa-bag-shopping"></i> Comprar ahora</a>
      <?php if ($user): ?>
        <a class="btn light" href="account.php"><i class="fa-solid fa-user"></i> Ver mi cuenta</a>
      <?php else: ?>
        <a class="btn light" href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Ingresar</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="promo-art">
    <?php foreach (array_slice($featured, 0, 3) as $product): ?>
      <article class="promo-tile">
        <img src="<?= e(productImage($product)) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        <div class="promo-tile-copy">
          <strong><?= e($product['category']) ?></strong>
          <span><?= e($product['name']) ?></span>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="spotlight-row">
  <?php
  $spotlightIcons = [
    'Semillas' => 'fa-seedling',
    'Fertilizantes' => 'fa-flask',
    'Riego' => 'fa-water',
    'Herramientas' => 'fa-wrench',
    'Bioinsumos' => 'fa-leaf',
    'Equipos' => 'fa-gear',
    'Macetas' => 'fa-tree',
    'Plagas' => 'fa-shield-halved',
  ];
  ?>
  <?php foreach ($categories as $category): ?>
    <?php $sIcon = $spotlightIcons[$category] ?? 'fa-tag'; ?>
    <a class="spotlight-card" href="store.php?category=<?= urlencode($category) ?>">
      <span class="spotlight-badge"><i class="fa-solid <?= $sIcon ?>"></i></span>
      <strong><?= e($category) ?></strong>
      <small>Ver mas <i class="fa-solid fa-arrow-right"></i></small>
    </a>
  <?php endforeach; ?>
</section>

<section class="section-head">
  <div>
    <span class="eyebrow">Catalogo destacado</span>
    <h2>Lo mas buscado esta aqui</h2>
  </div>
  <a class="btn" href="store.php"><i class="fa-solid fa-store"></i> Ver catalogo completo</a>
</section>

<section class="shop-grid">
  <div class="promo-banner-mini">
    <strong>Compra con inventario actualizado</strong>
    <span>Tu carrito y el checkout respetan el stock disponible en tiempo real.</span>
  </div>
  <div class="product-grid storefront-grid">
    <?php foreach ($featured as $product): ?>
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
            <?php if ($user): ?>
              <form class="fav-form" method="post" action="favorite.php">
                <?= csrfField() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <button class="fav-btn <?= in_array((int) $product['id'], $favIds, true) ? 'is-fav' : '' ?>" type="submit" title="Favorito"><i class="fa-<?= in_array((int) $product['id'], $favIds, true) ? 'solid' : 'regular' ?> fa-heart"></i></button>
              </form>
            <?php endif; ?>
          </div>
          <form class="quick-buy-form" method="post">
            <?= csrfField() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="quantity" value="1">
            <input type="hidden" name="_redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
            <button class="btn primary full" type="submit"><i class="fa-solid fa-cart-plus"></i> Agregar al carrito</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php renderFooter(); ?>
