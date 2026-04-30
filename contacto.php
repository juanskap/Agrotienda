<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    try {
        createContactMessage([
            'name' => $_POST['nombre'] ?? '',
            'email' => $_POST['correo'] ?? '',
            'phone' => $_POST['telefono'] ?? '',
            'message' => $_POST['mensaje'] ?? '',
        ]);
        setFlash('success', 'Mensaje enviado correctamente. Te contactaremos pronto.');
        redirect('contacto.php');
    } catch (InvalidArgumentException $error) {
        setFlash('error', $error->getMessage());
        redirect('contacto.php');
    }
}

renderHeader('Contacto');
?>
<section class="store-hero">
  <div>
    <span class="eyebrow">Contacto</span>
    <h1>Hablemos sobre tu compra o cultivo</h1>
    <p>Estamos listos para ayudarte con productos, disponibilidad, pedidos y seguimiento de entrega.</p>
  </div>
  <a class="btn light" href="cart.php">Ver carrito</a>
</section>

<section class="grid-3">
  <article class="stat">
    <strong>099 000 0000</strong>
    <span class="muted">WhatsApp y llamadas</span>
  </article>
  <article class="stat">
    <strong>08:00 - 18:00</strong>
    <span class="muted">Lunes a sabado</span>
  </article>
  <article class="stat">
    <strong>Nacional</strong>
    <span class="muted">Cobertura de envios</span>
  </article>
</section>

<section class="split">
  <article class="card">
    <h2>Enviar consulta</h2>
    <p class="muted">Completa tus datos y el mensaje. Puedes usarlo para dudas sobre productos, pedidos, disponibilidad o entregas.</p>
    <form class="form" method="post" action="contacto.php">
      <?= csrfField() ?>
      <div class="grid-2">
        <div class="field">
          <label for="nombre">Nombre</label>
          <input id="nombre" name="nombre" placeholder="Tu nombre completo" required>
        </div>
        <div class="field">
          <label for="correo">Correo</label>
          <input id="correo" name="correo" type="email" placeholder="correo@ejemplo.com" required>
        </div>
      </div>
      <div class="field">
        <label for="telefono">Telefono</label>
        <input id="telefono" name="telefono" type="tel" placeholder="0990000000">
      </div>
      <div class="field">
        <label for="mensaje">Mensaje</label>
        <textarea id="mensaje" name="mensaje" placeholder="Escribe aqui tu consulta" required></textarea>
      </div>
      <button class="btn primary" type="submit">Enviar mensaje</button>
    </form>
  </article>

  <aside class="card">
    <h2>Datos de la tienda</h2>
    <div class="list">
      <div class="list-item">
        <strong>Ubicacion</strong>
        <p class="muted">Quito, Ecuador</p>
      </div>
      <div class="list-item">
        <strong>Correo</strong>
        <p class="muted">agrotienda@correo.com</p>
      </div>
      <div class="list-item">
        <strong>Atencion</strong>
        <p class="muted">Ventas, soporte y seguimiento de pedidos.</p>
      </div>
    </div>
    <div class="panel">
      <strong>Referencia de atencion</strong>
      <p class="muted">Aqui podemos integrar mas adelante un mapa real, WhatsApp directo o envio de correos.</p>
    </div>
  </aside>
</section>
<?php renderFooter(); ?>
