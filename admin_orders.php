<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

requireAdmin();

$allowedStatuses = ['Recibido', 'Preparando', 'Enviado', 'Entregado', 'Cancelado'];

function statusPillClass(string $status): string
{
    $map = [
        'Recibido' => 'warning',
        'Preparando' => 'warning',
        'Enviado' => 'success',
        'Entregado' => 'success',
        'Cancelado' => 'danger',
    ];
    return $map[$status] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));

    if ($orderId <= 0 || !in_array($status, $allowedStatuses, true)) {
        setFlash('error', 'Estado de pedido no valido.');
        redirect('admin_orders.php');
    }

    $stmt = db()->prepare('UPDATE orders SET status = :status WHERE id = :id');
    $stmt->execute([
        'status' => $status,
        'id' => $orderId,
    ]);

    setFlash('success', 'Estado del pedido actualizado.');
    redirect('admin_orders.php');
}

$activeStatus = trim((string) ($_GET['status'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$dateFrom = trim((string) ($_GET['from'] ?? ''));
$dateTo = trim((string) ($_GET['to'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = 'WHERE 1=1';
$params = [];
$countParams = [];

if ($activeStatus !== '' && in_array($activeStatus, $allowedStatuses, true)) {
    $where .= ' AND status = :status';
    $params['status'] = $activeStatus;
    $countParams['status'] = $activeStatus;
}

if ($search !== '') {
    $where .= ' AND (customer_name LIKE :search OR customer_email LIKE :search OR customer_phone LIKE :search)';
    $params['search'] = '%' . $search . '%';
    $countParams['search'] = '%' . $search . '%';
}

if ($dateFrom !== '') {
    $where .= ' AND date(created_at) >= :date_from';
    $params['date_from'] = $dateFrom;
    $countParams['date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $where .= ' AND date(created_at) <= :date_to';
    $params['date_to'] = $dateTo;
    $countParams['date_to'] = $dateTo;
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM orders $where");
$countStmt->execute($countParams);
$totalOrders = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalOrders / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$sql = "SELECT * FROM orders $where ORDER BY id ASC LIMIT :limit OFFSET :offset";
$params['limit'] = $perPage;
$params['offset'] = $offset;

$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pendingCount = pendingOrdersCount();
$monthRevenue = revenueThisMonth();

renderHeader('Pedidos', ['admin_area' => true]);
?>
<section class="card hero">
  <span class="eyebrow">Pedidos</span>
  <h2>Gestion de pedidos</h2>
  <p class="muted">Consulta ventas, revisa clientes y cambia el estado de cada pedido desde la zona privada.</p>
  <div class="stats" style="margin-top:12px">
    <div class="stat">
      <small class="muted">Pendientes</small>
      <strong><?= $pendingCount ?></strong>
    </div>
    <div class="stat">
      <small class="muted">Ingresos del mes</small>
      <strong><?= money($monthRevenue) ?></strong>
    </div>
    <div class="stat">
      <small class="muted">Total pedidos</small>
      <strong><?= $totalOrders ?></strong>
    </div>
  </div>
</section>

<section class="card">
  <form class="admin-toolbar" method="get" style="flex-wrap:wrap">
    <div class="field">
      <label for="q">Cliente</label>
      <input id="q" name="q" value="<?= e($search) ?>" placeholder="Nombre, correo o telefono" style="min-width:180px">
    </div>
    <div class="field">
      <label for="from">Desde</label>
      <input id="from" name="from" type="date" value="<?= e($dateFrom) ?>" style="min-width:130px">
    </div>
    <div class="field">
      <label for="to">Hasta</label>
      <input id="to" name="to" type="date" value="<?= e($dateTo) ?>" style="min-width:130px">
    </div>
    <button class="btn primary" type="submit">Filtrar</button>
    <?php if ($search !== '' || $dateFrom !== '' || $dateTo !== '' || $activeStatus !== ''): ?>
      <a class="btn" href="admin_orders.php#pedidos-lista">Limpiar</a>
    <?php endif; ?>
  </form>
</section>

<nav class="section-nav" style="grid-template-columns:repeat(auto-fit,minmax(100px,1fr))">
  <?php
    $baseParams = [];
    if ($search !== '') $baseParams['q'] = $search;
    if ($dateFrom !== '') $baseParams['from'] = $dateFrom;
    if ($dateTo !== '') $baseParams['to'] = $dateTo;
    $querySuffix = $baseParams ? '&' . http_build_query($baseParams) : '';
  ?>
  <a href="admin_orders.php<?= $querySuffix ?>#pedidos-lista" class="<?= $activeStatus === '' ? 'is-active' : '' ?>">Todos</a>
  <?php foreach ($allowedStatuses as $status): ?>
    <a href="?status=<?= urlencode($status) . $querySuffix ?>#pedidos-lista" class="<?= $activeStatus === $status ? 'is-active' : '' ?>"><?= e($status) ?></a>
  <?php endforeach; ?>
</nav>

<section class="card" id="pedidos-lista">
  <h3>Pedidos registrados</h3>
  <?php if (!$orders): ?>
    <div class="empty">No hay pedidos con esos filtros.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Pedido</th>
            <th>Cliente</th>
            <th>Fecha</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td>#<?= (int) $order['id'] ?></td>
              <td>
                <a href="?q=<?= urlencode($order['customer_name']) ?>" style="text-decoration:none">
                  <strong><?= e($order['customer_name']) ?></strong>
                </a>
                <span class="table-subtext"><?= e($order['customer_email']) ?></span>
              </td>
              <td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
              <td><?= money((float) $order['total']) ?></td>
              <td>
                <span class="status-pill <?= e(statusPillClass($order['status'])) ?>"><?= e($order['status']) ?></span>
              </td>
              <td class="table-actions">
                <a class="btn" href="order.php?id=<?= (int) $order['id'] ?>">Detalle</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a class="btn" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>#pedidos-lista">Anterior</a>
        <?php endif; ?>
        <span class="pagination-info">Pagina <?= $page ?> de <?= $totalPages ?> (<?= $totalOrders ?> pedidos)</span>
        <?php if ($page < $totalPages): ?>
          <a class="btn" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>#pedidos-lista">Siguiente</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</section>
<?php renderFooter(); ?>
