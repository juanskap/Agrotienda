<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

requireAdmin();

$search = trim((string) ($_GET['q'] ?? ''));

$sql = "SELECT
            users.id,
            users.name,
            users.email,
            users.phone,
            users.address,
            users.role,
            users.created_at,
            COUNT(orders.id) AS order_count,
            COALESCE(SUM(orders.total), 0) AS total_spent
        FROM users
        LEFT JOIN orders ON orders.user_id = users.id
        WHERE users.role = 'customer'";
$params = [];

if ($search !== '') {
    $sql .= ' AND (users.name LIKE :search OR users.email LIKE :search OR users.phone LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$sql .= ' GROUP BY users.id ORDER BY users.id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

renderHeader('Clientes', ['admin_area' => true]);
?>
<section class="card hero">
  <span class="eyebrow">Clientes</span>
  <h2>Usuarios registrados</h2>
  <p class="muted">Consulta clientes, datos de contacto y actividad de compra. Las cuentas admin no se crean desde esta pantalla.</p>
</section>

<section class="card">
  <form class="admin-toolbar" method="get">
    <div class="field">
      <label for="q">Buscar cliente</label>
      <input id="q" name="q" value="<?= e($search) ?>" placeholder="Nombre, correo o telefono">
    </div>
    <button class="btn primary" type="submit">Buscar</button>
  </form>
</section>

<section class="card">
  <h3>Clientes</h3>
  <?php if (!$users): ?>
    <div class="empty">No hay clientes con esos filtros.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Cliente</th>
            <th>Telefono</th>
            <th>Direccion</th>
            <th>Pedidos</th>
            <th>Total comprado</th>
            <th>Registro</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td>
                <strong><?= e($user['name']) ?></strong>
                <span class="table-subtext"><?= e($user['email']) ?></span>
              </td>
              <td><?= e($user['phone'] ?: 'Sin telefono') ?></td>
              <td><?= e($user['address'] ?: 'Sin direccion') ?></td>
              <td><?= (int) $user['order_count'] ?></td>
              <td><?= money((float) $user['total_spent']) ?></td>
              <td><?= e(date('d/m/Y', strtotime($user['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php renderFooter(); ?>
