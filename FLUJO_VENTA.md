# 📊 Flujo de Venta - Agrotienda

## 1️⃣ ETAPA 1: VISUALIZACIÓN Y SELECCIÓN DE PRODUCTOS
**Archivo:** `product.php`

```
Tienda (store.php) 
    ↓
Detalle de Producto (product.php?id=X)
    ↓
Cliente selecciona cantidad
    ↓
Clic en "Agregar al carrito"
```

### Acciones:
- ✅ Obtiene producto por ID
- ✅ Valida que exista stock
- ✅ Validación CSRF
- ✅ Llama `addToCart($productId, $quantity)`
- ✅ Redirige a carrito con mensaje de éxito

### Validaciones:
- Cantidad mínima: 1
- Cantidad máxima: Stock disponible

---

## 2️⃣ ETAPA 2: GESTIÓN DEL CARRITO
**Archivo:** `cart.php`

```
Carrito (cart.php)
    ↓
Cliente revisa productos/cantidades
    ↓
Opciones:
    - Actualizar cantidad
    - Eliminar producto
    - Vaciar carrito
    - Proceder al checkout
```

### Acciones:
- ✅ Validación CSRF en POST
- ✅ `updateCart($quantities)` - actualiza cantidades
- ✅ `syncCartInventory()` - sincroniza con stock real
- ✅ Calcula totales con `cartTotals()`
- ✅ Eliminar productos individuales
- ✅ Opción "Vaciar carrito"

### Flujo de Checkout desde Carrito:
Si cliente presiona "Confirmar compra":
- Valida datos del cliente (nombre, email, teléfono, provincia, ciudad, dirección)
- Valida email válido
- Verifica que email no pertenezca a otra cuenta
- Valida forma de pago: Efectivo, Transferencia, Pago contra entrega, PayPal
- Crea orden con `createOrder($user, $payload)`
- Envía ticket por email con `sendOrderTicketEmail($orderId)`
- Redirige a `order.php?id=$orderId`

---

## 3️⃣ ETAPA 3: CHECKOUT - DATOS DE ENVÍO Y PAGO
**Archivo:** `checkout.php`

```
Checkout (checkout.php)
    ↓
Validación: ¿Carrito tiene items?
    ↓
Sincronización de inventario
    ↓
Formulario de datos:
    - Nombre, Email, Teléfono
    - Dirección de envío
    - Forma de pago
    - Notas (opcional)
    ↓
Submit: Confirmar compra
```

### Validaciones:
- ✅ Usuario debe estar logueado
- ✅ Carrito NO puede estar vacío
- ✅ Stock actualizado (avisos si hay cambios)
- ✅ Nombre, email, teléfono, dirección OBLIGATORIOS
- ✅ Email válido (FILTER_VALIDATE_EMAIL)
- ✅ Email no pertenece a otra cuenta

### Formas de Pago:
1. **Efectivo**
2. **Transferencia bancaria**
3. **Pago contra entrega**
4. **PayPal**

### Datos Guardados:
```php
[
    'customer_name' => string,
    'customer_email' => string,
    'customer_phone' => string,
    'payment_method' => string (default: 'Efectivo'),
    'shipping_address' => "$country, $province, $city, $addressDetail",
    'notes' => string (opcional)
]
```

---

## 4️⃣ ETAPA 4: CONFIRMACIÓN DE PEDIDO
**Archivo:** `order.php`

```
Pedido Confirmado (order.php?id=X)
    ↓
Pantalla de éxito
    ↓
Resumen del pedido:
    - Número de pedido
    - Datos de cliente
    - Dirección de envío
    - Forma de pago
    - Detalles de productos
    - Totales (Subtotal, Envío, Total)
    ↓
Opciones:
    - Imprimir ticket
    - Seguir comprando
```

### Información Mostrada:
- Número de pedido (#ID)
- Email del cliente
- Detalles de envío
- Productos ordenados (Producto, Cantidad, Precio, Total)
- Totales calculados

### Funcionalidades:
- ✅ Ver ticket (con estado y fecha)
- ✅ Imprimir para cliente
- ✅ Reenviar a tienda

---

## 📋 FLUJO COMPLETO (Visual)

```
┌─────────────────┐
│ PRODUCTOS       │  1. Cliente busca productos
│ store.php       │     en la tienda
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│ DETALLE         │  2. Abre detalle del
│ product.php     │     producto y elige
│ ?id=X           │     cantidad
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│ CARRITO         │  3. Revisa/edita carrito
│ cart.php        │     (cantidades, eliminar,
│                 │      sincroniza stock)
└────────┬────────┘
         │
         ↓ [Confirmar compra]
         │
┌─────────────────┐
│ CHECKOUT        │  4. Ingresa datos de
│ checkout.php    │     envío, pago y contacto
└────────┬────────┘
         │
         ↓ [Validaciones exitosas]
         │
┌─────────────────┐
│ CREAR ORDEN     │  5. createOrder() → DB
│ order.php       │     Guarda pedido
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│ CONFIRMACIÓN    │  6. Pantalla de éxito
│ order.php?id=X  │     Ticket generado
│                 │     Email enviado
└─────────────────┘
```

---

## 🔐 VALIDACIONES CLAVE

### En Carrito:
- [x] Validación CSRF
- [x] Sincronización stock
- [x] Cantidades válidas
- [x] Items presentes

### En Checkout:
- [x] Usuario logueado
- [x] Datos completos
- [x] Email válido
- [x] Email no duplicado
- [x] Forma de pago válida
- [x] Stock actualizado

### En Confirmación:
- [x] Orden existe
- [x] Usuario autorizado (propietario o admin)
- [x] Datos completos registrados

---

## 📧 EMAIL

**Ticket automático:**
- ✅ Se intenta enviar automáticamente con `sendOrderTicketEmail($orderId)`
- ✅ Si SMTP no está configurado, muestra mensaje informativo
- ✅ El ticket siempre se genera (listo para imprimir)

**Configuración necesaria:**
- Configura SMTP en XAMPP para envío automático

---

## 💾 DATOS GUARDADOS EN BD

### Tabla: `orders`
```
id              → Número único del pedido
user_id         → Usuario que realiza compra
customer_name   → Nombre de contacto
customer_email  → Email para contacto
customer_phone  → Teléfono
payment_method  → Forma de pago
shipping_address→ Dirección completa
notes           → Anotaciones especiales
status          → Estado (pending, completed, etc.)
subtotal        → Monto productos
shipping        → Costo envío
total           → Monto final
created_at      → Fecha/hora creación
```

### Tabla: `order_items`
```
id              → Línea única
order_id        → Referencia a orden
product_id      → Producto ordenado
product_name    → Nombre guardado
quantity        → Cantidad
unit_price      → Precio unitario
line_total      → Subtotal línea
```

---

## 🎯 PUNTOS DE MEJORA POTENCIAL

1. **Pasarela de pago:** Integrar PayPal/Stripe
2. **Descuentos/Cupones:** Sistema no implementado (UI existe)
3. **Cálculo dinámico envío:** Por peso/provincia
4. **Estimado de entrega:** Indicar tiempo de despacho
5. **Seguimiento:** Sistema de tracking para cliente
6. **Retorno de productos:** Flujo de devoluciones
7. **Generación PDF:** Ticket descargable
8. **Notificaciones:** SMS/WhatsApp opcionales
9. **Carrito persistente:** En sesión/BD
10. **Múltiples direcciones:** Guardar favoritas del cliente

---

## ✅ ESTADO ACTUAL

- ✅ Flujo básico completo
- ✅ Validaciones funcionales
- ✅ Generación de pedidos
- ✅ Sistema de email integrado
- ✅ Impresión de tickets
- ⏳ Pasarelas de pago (pendiente)
- ⏳ Sistema de seguimiento (pendiente)
