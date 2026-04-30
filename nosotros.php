<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$featured = array_slice(allProducts(), 0, 2);

renderHeader('Nosotros');
?>
<section class="store-hero">
  <div>
    <span class="eyebrow">Nosotros</span>
    <h1>Aliados del productor y del campo</h1>
    <p>Agrotienda conecta insumos, herramientas y atencion cercana para que comprar sea simple, confiable y con inventario real.</p>
  </div>
  <a class="btn light" href="store.php">Ver tienda</a>
</section>

<section class="split">
  <article class="card">
    <span class="eyebrow">Nuestra historia</span>
    <h2>Una tienda creada para comprar mejor</h2>
    <p class="muted">Trabajamos con una experiencia ordenada para productores, huertos familiares y clientes que necesitan resolver compras agricolas sin perder tiempo.</p>
    <p class="muted">El catalogo, el carrito y los pedidos estan conectados para cuidar el stock disponible y mantener el historial de compra claro.</p>
    <div class="actions">
      <a class="btn primary" href="store.php">Explorar catalogo</a>
      <a class="btn" href="contacto.php">Contactar</a>
    </div>
  </article>

  <article class="card">
    <h3>Compromiso Agrotienda</h3>
    <div class="list">
      <div class="list-item">
        <strong>Inventario actualizado</strong>
        <p class="muted">Las compras respetan el stock registrado para evitar pedidos imposibles de cumplir.</p>
      </div>
      <div class="list-item">
        <strong>Atencion cercana</strong>
        <p class="muted">Canales claros para consultas, seguimiento y soporte antes o despues de la compra.</p>
      </div>
      <div class="list-item">
        <strong>Gestion ordenada</strong>
        <p class="muted">Panel administrativo para mantener productos, precios y existencias al dia.</p>
      </div>
    </div>
  </article>
</section>

<section class="grid-3">
  <article class="spotlight-card">
    <span class="spotlight-badge">M</span>
    <strong>Mision</strong>
    <small>Acercar productos agricolas y herramientas con una compra rapida, clara y confiable.</small>
  </article>
  <article class="spotlight-card">
    <span class="spotlight-badge">V</span>
    <strong>Vision</strong>
    <small>Ser una tienda digital preparada para crecer en catalogo, pedidos y atencion personalizada.</small>
  </article>
  <article class="spotlight-card">
    <span class="spotlight-badge">P</span>
    <strong>Propuesta</strong>
    <small>Unir catalogo, control de inventario y soporte en una experiencia sencilla para el cliente.</small>
  </article>
</section>

<?php if ($featured): ?>
  <section class="section-head">
    <div>
      <span class="eyebrow">Catalogo activo</span>
      <h2>Productos que respaldan la tienda</h2>
    </div>
    <a class="btn" href="store.php">Ver mas productos</a>
  </section>

  <section class="product-grid">
    <?php foreach ($featured as $product): ?>
      <article class="product product-storefront">
        <a class="product-media" href="product.php?id=<?= (int) $product['id'] ?>">
          <img src="<?= e(productImage($product)) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        </a>
        <div class="product-body">
          <small><?= e($product['category']) ?> | <?= e($product['brand']) ?></small>
          <h3><?= e($product['name']) ?></h3>
          <p class="muted"><?= e($product['short_description']) ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>
<?php renderFooter(); ?>
