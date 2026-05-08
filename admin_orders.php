<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

requireAdmin();

$allowedStatuses = ['Recibido', 'Preparando', 'Enviado', 'Entregado', 'Cancelado'];

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

$sql = 'SELECT * FROM orders WHERE 1=1';
$params = [];

if ($activeStatus !== '' && in_array($activeStatus, $allowedStatuses, true)) {
    $sql .= ' AND status = :status';
    $params['status'] = $activeStatus;
}

if ($search !== '') {
    $sql .= ' AND (customer_name LIKE :search OR customer_email LIKE :search OR customer_phone LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$sql .= ' ORDER BY id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

renderHeader('Pedidos', ['admin_area' => true]);
?>
<section class="card hero">
  <span class="eyebrow">Pedidos</span>
  <h2>Gestion de pedidos</h2>
  <p class="muted">Consulta ventas, revisa clientes y cambia el estado de cada pedido desde la zona privada.</p>
</section>

<section class="card">
  <form class="admin-toolbar" method="get">
    <div class="field">
      <label for="q">Buscar cliente</label>
      <input id="q" name="q" value="<?= e($search) ?>" placeholder="Nombre, correo o telefono">
    </div>
    <div class="field">
      <label for="status">Estado</label>
      <select id="status" name="status">
        <option value="" <?= $activeStatus === '' ? 'selected' : '' ?>>Todos</option>
        <?php foreach ($allowedStatuses as $status): ?>
          <option value="<?= e($status) ?>" <?= $activeStatus === $status ? 'selected' : '' ?>><?= e($status) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn primary" type="submit">Filtrar</button>
  </form>
</section>

<section class="card">
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
                <strong><?= e($order['customer_name']) ?></strong>
                <span class="table-subtext"><?= e($order['customer_email']) ?></span>
              </td>
              <td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
              <td><?= money((float) $order['total']) ?></td>
              <td>
                <form class="inline-status-form" method="post">
                  <?= csrfField() ?>
                  <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                  <select name="status" aria-label="Estado del pedido #<?= (int) $order['id'] ?>">
                    <?php foreach ($allowedStatuses as $status): ?>
                      <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn" type="submit">Guardar</button>
                </form>
              </td>
              <td class="table-actions">
                <a class="btn" href="order.php?id=<?= (int) $order['id'] ?>">Detalle</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php renderFooter(); ?>
