<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$products = allProducts($search ?: null, $category ?: null);

renderHeader('Tienda');
?>
<section class="card">
  <div class="line">
    <div>
      <h2>Catalogo de productos</h2>
      <p class="muted">Busca, filtra por categoria y entra al detalle para comprar.</p>
    </div>
    <a class="btn" href="cart.php">Ver carrito</a>
  </div>
  <form class="grid-3 form" method="get">
    <div class="field">
      <label for="q">Buscar</label>
      <input id="q" name="q" value="<?= e($search) ?>" placeholder="Semillas, riego, fertilizantes...">
    </div>
    <div class="field">
      <label for="category">Categoria</label>
      <select id="category" name="category">
        <option value="">Todas</option>
        <?php foreach (categories() as $option): ?>
          <option value="<?= e($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= e($option) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>&nbsp;</label>
      <button class="btn primary full" type="submit">Filtrar productos</button>
    </div>
  </form>
</section>

<section class="product-grid">
  <?php if (!$products): ?>
    <div class="card empty">No encontramos productos con ese filtro.</div>
  <?php endif; ?>
  <?php foreach ($products as $product): ?>
    <article class="product">
      <div class="product-media">
        <img src="<?= e(productImage($product)) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
      </div>
      <div class="product-body">
        <small><?= e($product['category']) ?> - <?= e($product['brand']) ?></small>
        <h3><?= e($product['name']) ?></h3>
        <p class="muted"><?= e($product['short_description']) ?></p>
        <div class="line">
          <span class="price"><?= money((float) $product['price']) ?></span>
          <span class="muted">Stock <?= (int) $product['stock'] ?></span>
        </div>
        <div class="actions">
          <a class="btn primary" href="product.php?id=<?= (int) $product['id'] ?>">Detalle</a>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</section>
<?php renderFooter(); ?>
