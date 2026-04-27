<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = db()->prepare(
        'UPDATE users SET name = :name, phone = :phone, address = :address WHERE id = :id'
    );
    $stmt->execute([
        'name' => trim($_POST['name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'id' => $user['id'],
    ]);
    setFlash('success', 'Perfil actualizado.');
    redirect('account.php');
}

$orders = ordersForUser((int) $user['id']);
$user = currentUser();

renderHeader('Mi cuenta');
?>
<section class="split">
  <article class="card">
    <span class="eyebrow">Perfil</span>
    <h2>Mi cuenta</h2>
    <form class="form" method="post">
      <div class="field">
        <label for="name">Nombre</label>
        <input id="name" name="name" value="<?= e($user['name']) ?>" required>
      </div>
      <div class="grid-2">
        <div class="field">
          <label for="email">Correo</label>
          <input id="email" value="<?= e($user['email']) ?>" disabled>
        </div>
        <div class="field">
          <label for="phone">Telefono</label>
          <input id="phone" name="phone" value="<?= e($user['phone']) ?>">
        </div>
      </div>
      <div class="field">
        <label for="address">Direccion</label>
        <textarea id="address" name="address"><?= e($user['address']) ?></textarea>
      </div>
      <button class="btn primary" type="submit">Guardar cambios</button>
    </form>
  </article>

  <aside class="card">
    <h2>Mis pedidos</h2>
    <?php if (!$orders): ?>
      <div class="empty">Todavia no tienes pedidos. Cuando compres algo apareceran aqui.</div>
    <?php else: ?>
      <div class="list">
        <?php foreach ($orders as $order): ?>
          <div class="list-item">
            <div class="line">
              <strong>Pedido #<?= (int) $order['id'] ?></strong>
              <span class="price"><?= money((float) $order['total']) ?></span>
            </div>
            <p class="muted">Estado: <?= e($order['status']) ?> | Fecha: <?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></p>
            <a class="btn" href="order.php?id=<?= (int) $order['id'] ?>">Ver detalle</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </aside>
</section>
<?php renderFooter(); ?>
