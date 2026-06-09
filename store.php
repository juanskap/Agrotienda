<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

    $redirectTo = trim($_POST['_redirect'] ?? 'store.php');

    try {
        addToCart($productId, $quantity);
        setFlash('success', 'Producto agregado al carrito.');
        redirect($redirectTo);
    } catch (RuntimeException $error) {
        setFlash('error', $error->getMessage());
        redirect($redirectTo);
    }
}

$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$allProducts = allProducts($search ?: null, $category ?: null);

$porPagina = 12;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$total = count($allProducts);
$totalPaginas = (int) ceil($total / $porPagina);
$inicio = ($pagina - 1) * $porPagina;
$products = array_slice($allProducts, $inicio, $porPagina);

$user = currentUser();
$favIds = $user ? favoriteProductIds((int) $user['id']) : [];

renderHeader('Tienda', [
    'search' => $search,
    'active_category' => $category,
]);
?>
<section class="store-hero">
  <div>
    <span class="eyebrow">Tienda</span>
    <h1>Encuentra lo que necesitas para tu produccion</h1>
    <p><?= $category !== '' ? 'Categoria activa: ' . e($category) . '.' : 'Explora el catalogo completo, filtra por categoria y compra con inventario actualizado.' ?></p>
  </div>
  <a class="btn light" href="cart.php">Ver carrito</a>
</section>

<section class="store-toolbar">
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

<section class="product-grid storefront-grid">
  <?php if (!$products): ?>
    <div class="card empty">No encontramos productos con ese filtro.</div>
  <?php endif; ?>
  <?php foreach ($products as $product): ?>
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
          <?php if ($user): ?>
            <form class="fav-form" method="post" action="favorite.php">
              <?= csrfField() ?>
              <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
              <button class="fav-btn <?= in_array((int) $product['id'], $favIds, true) ? 'is-fav' : '' ?>" type="submit" title="Favorito"><?= in_array((int) $product['id'], $favIds, true) ? '★' : '☆' ?></button>
            </form>
          <?php endif; ?>
        </div>
        <form class="quick-buy-form" method="post">
          <?= csrfField() ?>
          <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
          <input type="hidden" name="quantity" value="1">
          <input type="hidden" name="_redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
          <button class="btn primary full" type="submit">Agregar al carrito</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</section>

<?php if ($totalPaginas > 1): ?>
  <nav class="pagination">
    <?php if ($pagina > 1): ?>
      <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>">&laquo; Anterior</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
      <a class="page-link <?= $i === $pagina ? 'is-active' : '' ?>" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($pagina < $totalPaginas): ?>
      <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>">Siguiente &raquo;</a>
    <?php endif; ?>
  </nav>
<?php endif; ?>
<?php renderFooter(); ?>
