<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$user = requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $productId = (int) ($_POST['product_id'] ?? 0);
    $movementType = trim((string) ($_POST['movement_type'] ?? ''));
    $quantity = trim((string) ($_POST['quantity'] ?? ''));
    $note = trim((string) ($_POST['note'] ?? ''));

    if ($productId <= 0 || !ctype_digit($quantity)) {
        setFlash('error', 'Selecciona un producto e ingresa una cantidad valida.');
        redirect('admin_inventory.php');
    }

    try {
        applyInventoryMovement(
            $productId,
            $movementType,
            (int) $quantity,
            $note !== '' ? $note : 'Movimiento manual de inventario',
            (int) $user['id']
        );
        setFlash('success', 'Movimiento de inventario registrado.');
    } catch (InvalidArgumentException $error) {
        setFlash('error', $error->getMessage());
    }

    redirect('admin_inventory.php');
}

$search = trim((string) ($_GET['q'] ?? ''));
$filter = trim((string) ($_GET['filter'] ?? ''));
$categoryFilter = trim((string) ($_GET['category'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = 'WHERE 1=1';
$params = [];
$countParams = [];

if ($search !== '') {
    $where .= ' AND (name LIKE :search OR brand LIKE :search OR category LIKE :search)';
    $params['search'] = '%' . $search . '%';
    $countParams['search'] = '%' . $search . '%';
}

if ($filter === 'low') {
    $where .= ' AND stock > 0 AND stock <= 10';
} elseif ($filter === 'empty') {
    $where .= ' AND stock = 0';
}

if ($categoryFilter !== '') {
    $where .= ' AND category = :category';
    $params['category'] = $categoryFilter;
    $countParams['category'] = $categoryFilter;
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM products $where");
$countStmt->execute($countParams);
$totalProducts = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalProducts / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$sql = "SELECT * FROM products $where ORDER BY stock ASC, name ASC LIMIT :limit OFFSET :offset";
$params['limit'] = $perPage;
$params['offset'] = $offset;

$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$allProducts = allProducts();
$categoryList = categories();

renderHeader('Inventario', ['admin_area' => true]);
?>
<section class="card hero">
  <span class="eyebrow">Inventario</span>
  <h2>Control de entradas y salidas</h2>
  <p class="muted">Registra movimientos, consulta existencias y conserva historial de cada cambio de stock.</p>
  <div class="actions">
    <a class="btn primary" href="admin_products.php">Crear producto</a>
    <a class="btn" href="admin.php">Volver al resumen</a>
  </div>
</section>

<section class="card">
  <h3>Registrar movimiento</h3>
  <form class="inventory-movement-form" method="post">
    <?= csrfField() ?>
    <div class="field">
      <label for="product_id">Producto</label>
      <select id="product_id" name="product_id" required>
        <option value="">Selecciona producto</option>
        <?php foreach ($allProducts as $product): ?>
          <option value="<?= (int) $product['id'] ?>"><?= e($product['name']) ?> | Stock <?= (int) $product['stock'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="movement_type">Movimiento</label>
      <select id="movement_type" name="movement_type" required>
        <option value="entrada">Entrada</option>
        <option value="salida">Salida</option>
        <option value="ajuste">Ajuste</option>
      </select>
    </div>
    <div class="field">
      <label for="quantity">Cantidad</label>
      <input id="quantity" name="quantity" type="number" min="0" required>
    </div>
    <div class="field">
      <label for="note">Motivo</label>
      <input id="note" name="note" placeholder="Compra a proveedor, merma, correccion...">
    </div>
    <button class="btn primary" type="submit">Registrar</button>
  </form>
  <p class="hint">En ajuste, la cantidad se toma como el stock final correcto. En entrada y salida, la cantidad suma o resta al stock actual.</p>
</section>

<section class="card">
  <form class="admin-toolbar" method="get">
    <div class="field">
      <label for="q">Buscar producto</label>
      <input id="q" name="q" value="<?= e($search) ?>" placeholder="Nombre, marca o categoria">
    </div>
    <div class="field">
      <label for="category">Categoria</label>
      <select id="category" name="category" onchange="location=this.form.action+'?'+new URLSearchParams(new FormData(this.form))+'#productos-inventario'">
        <option value="" <?= $categoryFilter === '' ? 'selected' : '' ?>>Todas</option>
        <?php foreach ($categoryList as $cat): ?>
          <option value="<?= e($cat) ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="filter">Estado</label>
      <select id="filter" name="filter">
        <option value="" <?= $filter === '' ? 'selected' : '' ?>>Todos</option>
        <option value="low" <?= $filter === 'low' ? 'selected' : '' ?>>Stock bajo</option>
        <option value="empty" <?= $filter === 'empty' ? 'selected' : '' ?>>Agotados</option>
      </select>
    </div>
    <button class="btn primary" type="submit">Filtrar</button>
  </form>
</section>

<section class="card" id="productos-inventario">
  <h3>Productos en inventario</h3>
  <?php if (!$products): ?>
    <div class="empty">No hay productos con esos filtros.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th>Categoria</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $product): ?>
            <?php
              $stock = (int) $product['stock'];
              $status = $stock === 0 ? 'Agotado' : ($stock <= 10 ? 'Stock bajo' : 'Disponible');
              $statusClass = $stock === 0 ? 'danger' : ($stock <= 10 ? 'warning' : 'success');
            ?>
            <tr>
              <td><?= e($product['name']) ?></td>
              <td><?= e($product['category']) ?></td>
              <td><?= money((float) $product['price']) ?></td>
              <td><strong><?= $stock ?></strong></td>
              <td><span class="status-pill <?= e($statusClass) ?>"><?= e($status) ?></span></td>
              <td class="table-actions">
                <a class="btn" href="admin_products.php?id=<?= (int) $product['id'] ?>">Editar</a>
                <a class="btn" href="product.php?id=<?= (int) $product['id'] ?>">Ver</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a class="btn" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>#productos-inventario">Anterior</a>
      <?php endif; ?>
      <span class="pagination-info">Pagina <?= $page ?> de <?= $totalPages ?> (<?= $totalProducts ?> productos)</span>
      <?php if ($page < $totalPages): ?>
        <a class="btn" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>#productos-inventario">Siguiente</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<?php renderFooter(); ?>
