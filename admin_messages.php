<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

requireAdmin();

$allowedStatuses = ['Nuevo', 'Leido', 'Respondido'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $messageId = (int) ($_POST['message_id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));

    if ($messageId <= 0 || !in_array($status, $allowedStatuses, true)) {
        setFlash('error', 'Estado de mensaje no valido.');
        redirect('admin_messages.php');
    }

    $stmt = db()->prepare('UPDATE contact_messages SET status = :status WHERE id = :id');
    $stmt->execute([
        'status' => $status,
        'id' => $messageId,
    ]);

    setFlash('success', 'Mensaje actualizado.');
    redirect('admin_messages.php');
}

$activeStatus = trim((string) ($_GET['status'] ?? ''));

$sql = 'SELECT * FROM contact_messages WHERE 1=1';
$params = [];

if ($activeStatus !== '' && in_array($activeStatus, $allowedStatuses, true)) {
    $sql .= ' AND status = :status';
    $params['status'] = $activeStatus;
}

$sql .= ' ORDER BY id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

renderHeader('Mensajes', ['admin_area' => true]);
?>
<section class="card hero">
  <span class="eyebrow">Mensajes</span>
  <h2>Bandeja de contacto</h2>
  <p class="muted">Revisa consultas de clientes y marca cada mensaje segun su avance.</p>
</section>

<section class="card">
  <form class="admin-toolbar" method="get">
    <div class="field">
      <label for="status">Estado</label>
      <select id="status" name="status">
        <option value="" <?= $activeStatus === '' ? 'selected' : '' ?>>Todos</option>
        <?php foreach ($allowedStatuses as $status): ?>
          <option value="<?= e($status) ?>" <?= $activeStatus === $status ? 'selected' : '' ?>><?= e($status) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn primary" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
  </form>
</section>

<section class="card">
  <h3>Mensajes recibidos</h3>
  <?php if (!$messages): ?>
    <div class="empty">No hay mensajes con ese estado.</div>
  <?php else: ?>
    <div class="list">
      <?php foreach ($messages as $message): ?>
        <article class="list-item message-item">
          <div class="line">
            <div>
              <strong><?= e($message['name']) ?></strong>
              <p class="muted"><?= e($message['email']) ?><?= $message['phone'] !== '' ? ' | ' . e($message['phone']) : '' ?></p>
            </div>
            <span class="muted"><?= e(date('d/m/Y H:i', strtotime($message['created_at']))) ?></span>
          </div>
          <p><?= e($message['message']) ?></p>
          <form class="inline-status-form" method="post">
            <?= csrfField() ?>
            <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
            <select name="status" aria-label="Estado del mensaje de <?= e($message['name']) ?>">
              <?php foreach ($allowedStatuses as $status): ?>
                <option value="<?= e($status) ?>" <?= $message['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar estado</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php renderFooter(); ?>
