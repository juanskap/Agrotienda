<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

requireAdmin();

$productId = (int) ($_GET['id'] ?? 0);
$editingProduct = $productId > 0 ? productById($productId) : null;

if ($productId > 0 && !$editingProduct) {
    setFlash('error', 'Producto no encontrado.');
    redirect('admin_products.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $deleteId = (int) ($_POST['id'] ?? 0);
        if ($deleteId > 0) {
            try {
                deleteProduct($deleteId);
                setFlash('success', 'Producto eliminado.');
            } catch (RuntimeException $error) {
                setFlash('error', $error->getMessage());
            }
        }
        redirect('admin_products.php');
    }

    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'category' => trim($_POST['category'] ?? ''),
        'brand' => trim($_POST['brand'] ?? ''),
        'price' => trim($_POST['price'] ?? ''),
        'stock' => trim($_POST['stock'] ?? ''),
        'image_url' => trim($_POST['image_url'] ?? ''),
        'short_description' => trim($_POST['short_description'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
    ];

    try {
        if ($productId > 0) {
            updateProduct($productId, $data);
            setFlash('success', 'Producto actualizado.');
            redirect('admin_products.php?id=' . $productId);
        }

        $newId = createProduct($data);
    } catch (InvalidArgumentException $error) {
        setFlash('error', $error->getMessage());
        $query = $productId > 0 ? '?id=' . $productId : '';
        redirect('admin_products.php' . $query);
    }

    setFlash('success', 'Producto creado.');
    redirect('admin_products.php?id=' . $newId);
}

$products = allProducts();
$formData = $editingProduct ?: [
    'name' => '',
    'category' => '',
    'brand' => '',
    'price' => '',
    'stock' => '',
    'image_url' => '',
    'short_description' => '',
    'description' => '',
];

renderHeader('Admin productos');
?>
<section class="card hero">
  <span class="eyebrow">Admin productos</span>
  <h2>Catalogo editable</h2>
  <p class="muted">Desde aqui puedes crear, editar o eliminar productos, incluyendo nombre, precio, stock y foto en la nube.</p>
  <div class="actions">
    <a class="btn" href="admin.php">Volver al dashboard</a>
    <a class="btn primary" href="admin_products.php">Nuevo producto</a>
  </div>
</section>

<section class="admin-layout">
  <article class="card sticky-card">
    <div class="line">
      <div>
        <h3><?= $editingProduct ? 'Editar producto' : 'Crear producto' ?></h3>
        <p class="hint">Usa enlaces directos de imagen, por ejemplo desde Pexels o tu propio hosting.</p>
      </div>
    </div>
    <form class="form" method="post">
      <?= csrfField() ?>
      <div class="field">
        <label for="name">Nombre</label>
        <input id="name" name="name" value="<?= e((string) $formData['name']) ?>" required>
      </div>
      <div class="grid-2">
        <div class="field">
          <label for="category">Categoria</label>
          <input id="category" name="category" value="<?= e((string) $formData['category']) ?>" required>
        </div>
        <div class="field">
          <label for="brand">Marca</label>
          <input id="brand" name="brand" value="<?= e((string) $formData['brand']) ?>" required>
        </div>
      </div>
      <div class="grid-2">
        <div class="field">
          <label for="price">Precio</label>
          <input id="price" name="price" type="number" step="0.01" min="0" value="<?= e((string) $formData['price']) ?>" required>
        </div>
        <div class="field">
          <label for="stock">Stock</label>
          <input id="stock" name="stock" type="number" min="0" value="<?= e((string) $formData['stock']) ?>" required>
        </div>
      </div>
      <div class="field">
        <label for="image_url">URL de la foto</label>
        <input id="image_url" name="image_url" type="url" value="<?= e((string) $formData['image_url']) ?>" required>
      </div>
      <?php if ($formData['image_url'] !== ''): ?>
        <div class="product-media is-preview">
          <img src="<?= e((string) $formData['image_url']) ?>" alt="Vista previa de <?= e((string) $formData['name']) ?>">
        </div>
      <?php endif; ?>
      <div class="field">
        <label for="short_description">Descripcion corta</label>
        <input id="short_description" name="short_description" value="<?= e((string) $formData['short_description']) ?>" required>
      </div>
      <div class="field">
        <label for="description">Descripcion completa</label>
        <textarea id="description" name="description" required><?= e((string) $formData['description']) ?></textarea>
      </div>
      <button class="btn primary" type="submit">Guardar producto</button>
    </form>
    <?php if ($editingProduct): ?>
      <form method="post" onsubmit="return confirm('Se eliminara este producto del catalogo.');">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int) $editingProduct['id'] ?>">
        <button class="btn danger" type="submit">Eliminar producto</button>
      </form>
    <?php endif; ?>
  </article>

  <article class="card">
    <h3>Productos actuales</h3>
    <?php if (!$products): ?>
      <div class="empty">No hay productos cargados.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th>Categoria</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $product): ?>
            <tr>
              <td><?= e($product['name']) ?></td>
              <td><?= e($product['category']) ?></td>
              <td><?= money((float) $product['price']) ?></td>
              <td><?= (int) $product['stock'] ?></td>
              <td class="table-actions">
                <a class="btn" href="admin_products.php?id=<?= (int) $product['id'] ?>">Editar</a>
                <a class="btn" href="product.php?id=<?= (int) $product['id'] ?>">Ver</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </article>
</section>
<?php renderFooter(); ?>
