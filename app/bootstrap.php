<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('STORAGE_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'storage');
define('DB_PATH', STORAGE_PATH . DIRECTORY_SEPARATOR . 'agrotienda.sqlite');
define('SESSION_PATH', STORAGE_PATH . DIRECTORY_SEPARATOR . 'sessions');
define('MAIL_LOG_PATH', STORAGE_PATH . DIRECTORY_SEPARATOR . 'mail');

loadEnv();
configureSession();
session_start();

function loadEnv(): void
{
    $envPath = APP_ROOT . DIRECTORY_SEPARATOR . '.env';

    if (!is_file($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($name !== '' && getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

function configureSession(): void
{
    if (!is_dir(SESSION_PATH)) {
        mkdir(SESSION_PATH, 0777, true);
    }

    $secure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.save_path', SESSION_PATH);
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!is_dir(STORAGE_PATH)) {
        mkdir(STORAGE_PATH, 0777, true);
    }

    $needsSetup = !file_exists(DB_PATH);

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($needsSetup) {
        initializeDatabase($pdo);
    } else {
        migrateDatabase($pdo);
    }

    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            phone TEXT DEFAULT "",
            address TEXT DEFAULT "",
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT "customer",
            created_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            category TEXT NOT NULL,
            brand TEXT NOT NULL,
            price REAL NOT NULL,
            stock INTEGER NOT NULL DEFAULT 0,
            emoji TEXT NOT NULL DEFAULT "",
            image_url TEXT NOT NULL DEFAULT "",
            short_description TEXT NOT NULL,
            description TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            customer_name TEXT NOT NULL,
            customer_email TEXT NOT NULL,
            customer_phone TEXT NOT NULL,
            shipping_address TEXT NOT NULL,
            payment_method TEXT NOT NULL DEFAULT "Efectivo",
            notes TEXT DEFAULT "",
            subtotal REAL NOT NULL,
            shipping REAL NOT NULL,
            total REAL NOT NULL,
            status TEXT NOT NULL DEFAULT "Pendiente",
            created_at TEXT NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            product_name TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            unit_price REAL NOT NULL,
            line_total REAL NOT NULL,
            FOREIGN KEY(order_id) REFERENCES orders(id),
            FOREIGN KEY(product_id) REFERENCES products(id)
        )'
    );

    createContactMessagesTable($pdo);

    seedDatabase($pdo);
}

function migrateDatabase(PDO $pdo): void
{
    $columns = $pdo->query('PRAGMA table_info(products)')->fetchAll();
    $columnNames = array_column($columns, 'name');

    if (!in_array('image_url', $columnNames, true)) {
        $pdo->exec('ALTER TABLE products ADD COLUMN image_url TEXT NOT NULL DEFAULT ""');
    }

    syncProductImages($pdo);
    migrateOrdersTable($pdo);
    createContactMessagesTable($pdo);
}

function migrateOrdersTable(PDO $pdo): void
{
    $columns = $pdo->query('PRAGMA table_info(orders)')->fetchAll();
    $columnNames = array_column($columns, 'name');

    if (!in_array('payment_method', $columnNames, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN payment_method TEXT NOT NULL DEFAULT "Efectivo"');
    }
}

function createContactMessagesTable(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS contact_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT DEFAULT "",
            message TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT "Nuevo",
            created_at TEXT NOT NULL
        )'
    );
}

function seedDatabase(PDO $pdo): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, phone, address, password_hash, role, created_at)
         VALUES (:name, :email, :phone, :address, :password_hash, :role, :created_at)'
    );

    $now = date('c');

    $stmt->execute([
        'name' => 'Administrador Agrotienda',
        'email' => 'admin@agrotienda.local',
        'phone' => '0990000000',
        'address' => 'Oficina principal Agrotienda',
        'password_hash' => password_hash(seedPassword('AGROTIENDA_ADMIN_PASSWORD'), PASSWORD_DEFAULT),
        'role' => 'admin',
        'created_at' => $now,
    ]);

    $stmt->execute([
        'name' => 'Juan Carrillo',
        'email' => 'cliente@agrotienda.local',
        'phone' => '0999999999',
        'address' => 'Av. Principal y calle 10',
        'password_hash' => password_hash(seedPassword('AGROTIENDA_CUSTOMER_PASSWORD'), PASSWORD_DEFAULT),
        'role' => 'customer',
        'created_at' => $now,
    ]);

    $products = productSeedData();

    $productStmt = $pdo->prepare(
        'INSERT INTO products (name, category, brand, price, stock, emoji, image_url, short_description, description)
         VALUES (:name, :category, :brand, :price, :stock, :emoji, :image_url, :short_description, :description)'
    );

    foreach ($products as $product) {
        $productStmt->execute($product);
    }

    syncProductImages($pdo);
}

function productSeedData(): array
{
    return [
        [
            'name' => 'Semilla certificada de maiz alto rendimiento',
            'category' => 'Semillas',
            'brand' => 'AgroMax',
            'price' => 24.90,
            'stock' => 30,
            'emoji' => '',
            'image_url' => 'https://images.pexels.com/photos/18014450/pexels-photo-18014450.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'short_description' => 'Semilla para siembra comercial y de prueba.',
            'description' => 'Semilla certificada con buen porcentaje de germinacion, recomendada para productores que buscan estabilidad y rendimiento.',
        ],
        [
            'name' => 'Fertilizante NPK balanceado para hortalizas y frutales',
            'category' => 'Fertilizantes',
            'brand' => 'BioCrop',
            'price' => 31.50,
            'stock' => 28,
            'emoji' => '',
            'image_url' => 'https://images.pexels.com/photos/31492484/pexels-photo-31492484.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'short_description' => 'Formula pensada para nutricion vegetal.',
            'description' => 'Fertilizante de uso versatil para mejorar vigor, floracion y desarrollo de fruto en cultivos de ciclo medio y largo.',
        ],
        [
            'name' => 'Kit de microaspersores para cultivos y huertos',
            'category' => 'Riego',
            'brand' => 'CampoPlus',
            'price' => 18.20,
            'stock' => 42,
            'emoji' => '',
            'image_url' => 'https://images.pexels.com/photos/26903736/pexels-photo-26903736.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'short_description' => 'Riego eficiente para parcelas y huertos.',
            'description' => 'Kit base para microaspersion con piezas listas para instalar en huertos, viveros y pequenos modulos de produccion.',
        ],
        [
            'name' => 'Tijera de poda reforzada para trabajo continuo',
            'category' => 'Herramientas',
            'brand' => 'AgroTools',
            'price' => 22.00,
            'stock' => 18,
            'emoji' => '',
            'image_url' => 'https://images.pexels.com/photos/12142540/pexels-photo-12142540.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'short_description' => 'Herramienta ergonomica para poda diaria.',
            'description' => 'Tijera con hoja reforzada y agarre comodo para labores de poda y mantenimiento en finca o jardin productivo.',
        ],
        [
            'name' => 'Bioinsumo foliar de apoyo vegetativo',
            'category' => 'Bioinsumos',
            'brand' => 'EcoVerde',
            'price' => 16.80,
            'stock' => 35,
            'emoji' => '',
            'image_url' => 'https://images.pexels.com/photos/13253192/pexels-photo-13253192.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'short_description' => 'Complemento para manejo foliar.',
            'description' => 'Bioinsumo foliar para fortalecer el desarrollo vegetativo y acompanar esquemas de nutricion mas sostenibles.',
        ],
        [
            'name' => 'Fumigadora manual ligera de 20 litros',
            'category' => 'Equipos',
            'brand' => 'Pulverix',
            'price' => 42.00,
            'stock' => 12,
            'emoji' => '',
            'image_url' => 'https://images.pexels.com/photos/8279725/pexels-photo-8279725.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'short_description' => 'Equipo liviano para aplicacion de tratamientos.',
            'description' => 'Fumigadora de uso frecuente para aplicaciones agricolas, con arnes comodo y presion uniforme.',
        ],
    ];
}

function syncProductImages(PDO $pdo): void
{
    $stmt = $pdo->prepare('UPDATE products SET image_url = :image_url WHERE name = :name');

    foreach (productSeedData() as $product) {
        $stmt->execute([
            'name' => $product['name'],
            'image_url' => $product['image_url'],
        ]);
    }
}

function seedPassword(string $envName): string
{
    $password = getenv($envName);

    if (is_string($password) && strlen($password) >= 8) {
        return $password;
    }

    return bin2hex(random_bytes(16));
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $cached = $stmt->fetch() ?: null;

    return $cached;
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
}

function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

function requireLogin(): array
{
    $user = currentUser();
    if (!$user) {
        setFlash('error', 'Necesitas iniciar sesion para continuar.');
        redirect('login.php');
    }

    return $user;
}

function requireAdmin(): array
{
    $user = requireLogin();
    if ($user['role'] !== 'admin') {
        setFlash('error', 'Esta zona es solo para administradores.');
        redirect('index.php');
    }

    return $user;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pullFlash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function requireValidCsrfToken(): void
{
    $postedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($postedToken) || !hash_equals(csrfToken(), $postedToken)) {
        setFlash('error', 'La sesion del formulario expiro. Intentalo nuevamente.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }
}

function money(float $value): string
{
    return '$' . number_format($value, 2);
}

function productImage(array $product): string
{
    return $product['image_url'] !== '' ? $product['image_url'] : '';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function allProducts(?string $search = null, ?string $category = null): array
{
    $sql = 'SELECT * FROM products WHERE 1=1';
    $params = [];

    if ($search) {
        $sql .= ' AND (name LIKE :search OR brand LIKE :search OR category LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    if ($category) {
        $sql .= ' AND category = :category';
        $params['category'] = $category;
    }

    $sql .= ' ORDER BY id ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function productById(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

function availableStock(array $product): int
{
    return max(0, (int) ($product['stock'] ?? 0));
}

function normalizeProductData(array $data): array
{
    $normalized = [
        'name' => trim((string) ($data['name'] ?? '')),
        'category' => trim((string) ($data['category'] ?? '')),
        'brand' => trim((string) ($data['brand'] ?? '')),
        'price' => trim((string) ($data['price'] ?? '')),
        'stock' => trim((string) ($data['stock'] ?? '')),
        'image_url' => trim((string) ($data['image_url'] ?? '')),
        'short_description' => trim((string) ($data['short_description'] ?? '')),
        'description' => trim((string) ($data['description'] ?? '')),
    ];

    foreach (['name', 'category', 'brand', 'image_url', 'short_description', 'description'] as $field) {
        if ($normalized[$field] === '') {
            throw new InvalidArgumentException('Completa todos los campos del producto.');
        }
    }

    if (!is_numeric($normalized['price']) || (float) $normalized['price'] < 0) {
        throw new InvalidArgumentException('El precio debe ser un numero mayor o igual a cero.');
    }

    if (!ctype_digit($normalized['stock'])) {
        throw new InvalidArgumentException('El stock debe ser un numero entero mayor o igual a cero.');
    }

    if (!filter_var($normalized['image_url'], FILTER_VALIDATE_URL)) {
        throw new InvalidArgumentException('La URL de la imagen no es valida.');
    }

    $scheme = strtolower((string) parse_url($normalized['image_url'], PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        throw new InvalidArgumentException('La URL de la imagen debe usar http o https.');
    }

    $normalized['price'] = (float) $normalized['price'];
    $normalized['stock'] = (int) $normalized['stock'];

    return $normalized;
}

function createProduct(array $data): int
{
    $data = normalizeProductData($data);

    $stmt = db()->prepare(
        'INSERT INTO products (name, category, brand, price, stock, emoji, image_url, short_description, description)
         VALUES (:name, :category, :brand, :price, :stock, :emoji, :image_url, :short_description, :description)'
    );

    $stmt->execute([
        'name' => $data['name'],
        'category' => $data['category'],
        'brand' => $data['brand'],
        'price' => $data['price'],
        'stock' => $data['stock'],
        'emoji' => '',
        'image_url' => $data['image_url'],
        'short_description' => $data['short_description'],
        'description' => $data['description'],
    ]);

    return (int) db()->lastInsertId();
}

function updateProduct(int $id, array $data): void
{
    $data = normalizeProductData($data);

    $stmt = db()->prepare(
        'UPDATE products
         SET name = :name,
             category = :category,
             brand = :brand,
             price = :price,
             stock = :stock,
             image_url = :image_url,
             short_description = :short_description,
             description = :description
         WHERE id = :id'
    );

    $stmt->execute([
        'id' => $id,
        'name' => $data['name'],
        'category' => $data['category'],
        'brand' => $data['brand'],
        'price' => $data['price'],
        'stock' => $data['stock'],
        'image_url' => $data['image_url'],
        'short_description' => $data['short_description'],
        'description' => $data['description'],
    ]);
}

function deleteProduct(int $id): void
{
    if (productHasOrders($id)) {
        throw new RuntimeException('No puedes eliminar un producto que ya tiene pedidos asociados.');
    }

    $stmt = db()->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function productHasOrders(int $id): bool
{
    $stmt = db()->prepare('SELECT 1 FROM order_items WHERE product_id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);

    return (bool) $stmt->fetchColumn();
}

function categories(): array
{
    $stmt = db()->query('SELECT DISTINCT category FROM products ORDER BY category ASC');
    return array_column($stmt->fetchAll(), 'category');
}

function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function syncCartInventory(): array
{
    $cart = cart();
    $next = [];
    $messages = [];

    foreach ($cart as $productId => $quantity) {
        $product = productById((int) $productId);
        if (!$product) {
            $messages[] = 'Se retiro un producto del carrito porque ya no existe.';
            continue;
        }

        $stock = availableStock($product);
        if ($stock <= 0) {
            $messages[] = 'Se retiro "' . $product['name'] . '" del carrito porque ya no tiene stock.';
            continue;
        }

        $qty = max(0, (int) $quantity);
        if ($qty === 0) {
            continue;
        }

        if ($qty > $stock) {
            $qty = $stock;
            $messages[] = 'La cantidad de "' . $product['name'] . '" se ajusto al stock disponible.';
        }

        $next[(int) $productId] = $qty;
    }

    $_SESSION['cart'] = $next;

    return $messages;
}

function addToCart(int $productId, int $quantity): void
{
    $product = productById($productId);
    if (!$product) {
        throw new RuntimeException('El producto ya no esta disponible.');
    }

    $quantity = max(1, $quantity);
    $stock = availableStock($product);
    if ($stock <= 0) {
        throw new RuntimeException('Este producto esta agotado.');
    }

    $cart = cart();
    $currentQty = (int) ($cart[$productId] ?? 0);
    $nextQty = $currentQty + $quantity;

    if ($nextQty > $stock) {
        throw new RuntimeException('No hay suficiente stock para agregar esa cantidad.');
    }

    $cart[$productId] = $nextQty;
    $_SESSION['cart'] = $cart;
}

function updateCart(array $quantities): void
{
    $next = [];
    foreach ($quantities as $productId => $quantity) {
        $qty = (int) $quantity;
        if ($qty > 0) {
            $product = productById((int) $productId);
            if (!$product) {
                continue;
            }

            $stock = availableStock($product);
            if ($stock <= 0) {
                continue;
            }

            $next[(int) $productId] = min($qty, $stock);
        }
    }
    $_SESSION['cart'] = $next;
}

function clearCart(): void
{
    $_SESSION['cart'] = [];
}

function cartItems(): array
{
    $items = [];
    foreach (cart() as $productId => $quantity) {
        $product = productById((int) $productId);
        if (!$product) {
            continue;
        }

        $product['quantity'] = (int) $quantity;
        $product['line_total'] = $product['price'] * $product['quantity'];
        $items[] = $product;
    }

    return $items;
}

function cartTotals(): array
{
    $items = cartItems();
    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += (float) $item['line_total'];
    }

    $shipping = $subtotal > 0 ? 4.50 : 0.0;
    $total = $subtotal + $shipping;

    return [
        'items' => $items,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $total,
    ];
}

function createOrder(array $user, array $payload): int
{
    syncCartInventory();
    $totals = cartTotals();

    if (empty($totals['items'])) {
        throw new RuntimeException('No hay productos en el carrito.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO orders (
                user_id, customer_name, customer_email, customer_phone, shipping_address, payment_method, notes,
                subtotal, shipping, total, status, created_at
            ) VALUES (
                :user_id, :customer_name, :customer_email, :customer_phone, :shipping_address, :payment_method, :notes,
                :subtotal, :shipping, :total, :status, :created_at
            )'
        );

        $stmt->execute([
            'user_id' => $user['id'],
            'customer_name' => $payload['customer_name'],
            'customer_email' => $payload['customer_email'],
            'customer_phone' => $payload['customer_phone'],
            'shipping_address' => $payload['shipping_address'],
            'payment_method' => $payload['payment_method'] ?? 'Efectivo',
            'notes' => $payload['notes'],
            'subtotal' => $totals['subtotal'],
            'shipping' => $totals['shipping'],
            'total' => $totals['total'],
            'status' => 'Recibido',
            'created_at' => date('c'),
        ]);

        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, line_total)
             VALUES (:order_id, :product_id, :product_name, :quantity, :unit_price, :line_total)'
        );

        $stockStmt = $pdo->prepare(
            'UPDATE products
             SET stock = stock - :quantity
             WHERE id = :id AND stock >= :quantity'
        );

        foreach ($totals['items'] as $item) {
            $currentProduct = productById((int) $item['id']);
            if (!$currentProduct || availableStock($currentProduct) < (int) $item['quantity']) {
                throw new RuntimeException('No hay stock suficiente para completar el pedido.');
            }

            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $item['id'],
                'product_name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'line_total' => $item['line_total'],
            ]);

            $stockStmt->execute([
                'quantity' => $item['quantity'],
                'id' => $item['id'],
            ]);

            if ($stockStmt->rowCount() !== 1) {
                throw new RuntimeException('El stock cambio mientras procesabamos tu pedido.');
            }
        }

        $userUpdate = $pdo->prepare(
            'UPDATE users SET name = :name, email = :email, phone = :phone, address = :address WHERE id = :id'
        );
        $userUpdate->execute([
            'name' => $payload['customer_name'],
            'email' => $payload['customer_email'],
            'phone' => $payload['customer_phone'],
            'address' => $payload['shipping_address'],
            'id' => $user['id'],
        ]);

        $pdo->commit();
        clearCart();

        return $orderId;
    } catch (Throwable $error) {
        $pdo->rollBack();
        throw $error;
    }
}

function ordersForUser(int $userId): array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE user_id = :user_id ORDER BY id DESC');
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function orderById(int $orderId, ?int $userId = null): ?array
{
    $sql = 'SELECT * FROM orders WHERE id = :id';
    $params = ['id' => $orderId];

    if ($userId !== null) {
        $sql .= ' AND user_id = :user_id';
        $params['user_id'] = $userId;
    }

    $stmt = db()->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    $order = $stmt->fetch();

    if (!$order) {
        return null;
    }

    $items = db()->prepare('SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC');
    $items->execute(['order_id' => $orderId]);
    $order['items'] = $items->fetchAll();

    return $order;
}

function sendOrderTicketEmail(int $orderId): bool
{
    $order = orderById($orderId);

    if (!$order) {
        return false;
    }

    $lines = [
        'Ticket Agrotienda',
        'Pedido #' . $order['id'],
        'Estado: ' . $order['status'],
        'Cliente: ' . $order['customer_name'],
        'Telefono: ' . $order['customer_phone'],
        'Forma de pago: ' . ($order['payment_method'] ?? 'Efectivo'),
        'Direccion: ' . $order['shipping_address'],
        '',
        'Productos:',
    ];

    foreach ($order['items'] as $item) {
        $lines[] = '- ' . $item['product_name'] . ' x' . $item['quantity'] . ' = ' . money((float) $item['line_total']);
    }

    $lines[] = '';
    $lines[] = 'Subtotal: ' . money((float) $order['subtotal']);
    $lines[] = 'Envio: ' . money((float) $order['shipping']);
    $lines[] = 'Total: ' . money((float) $order['total']);

    $headers = [
        'From: ' . mailFromName() . ' <' . mailFromAddress() . '>',
        'Content-Type: text/plain; charset=UTF-8',
    ];

    if (mailMode() === 'log') {
        return writeOrderTicketLog($order, $lines, $headers);
    }

    return @mail(
        $order['customer_email'],
        'Ticket de compra Agrotienda #' . $order['id'],
        implode(PHP_EOL, $lines),
        implode("\r\n", $headers)
    );
}

function mailMode(): string
{
    $mode = strtolower((string) getenv('MAIL_MODE'));

    return in_array($mode, ['log', 'mail'], true) ? $mode : 'log';
}

function mailFromAddress(): string
{
    $from = getenv('MAIL_FROM');

    return is_string($from) && $from !== '' ? $from : 'no-reply@agrotienda.local';
}

function mailFromName(): string
{
    $name = getenv('MAIL_FROM_NAME');

    return is_string($name) && $name !== '' ? $name : 'Agrotienda';
}

function writeOrderTicketLog(array $order, array $lines, array $headers): bool
{
    if (!is_dir(MAIL_LOG_PATH)) {
        mkdir(MAIL_LOG_PATH, 0777, true);
    }

    $content = [
        'To: ' . $order['customer_email'],
        'Subject: Ticket de compra Agrotienda #' . $order['id'],
        implode("\r\n", $headers),
        '',
        implode(PHP_EOL, $lines),
    ];

    $file = MAIL_LOG_PATH . DIRECTORY_SEPARATOR . 'pedido-' . (int) $order['id'] . '.txt';

    return file_put_contents($file, implode(PHP_EOL, $content)) !== false;
}

function createContactMessage(array $payload): int
{
    $data = [
        'name' => trim((string) ($payload['name'] ?? '')),
        'email' => trim((string) ($payload['email'] ?? '')),
        'phone' => trim((string) ($payload['phone'] ?? '')),
        'message' => trim((string) ($payload['message'] ?? '')),
    ];

    if ($data['name'] === '' || $data['email'] === '' || $data['message'] === '') {
        throw new InvalidArgumentException('Completa nombre, correo y mensaje.');
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Ingresa un correo valido.');
    }

    $stmt = db()->prepare(
        'INSERT INTO contact_messages (name, email, phone, message, status, created_at)
         VALUES (:name, :email, :phone, :message, :status, :created_at)'
    );
    $stmt->execute([
        'name' => $data['name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'message' => $data['message'],
        'status' => 'Nuevo',
        'created_at' => date('c'),
    ]);

    return (int) db()->lastInsertId();
}

function recentContactMessages(int $limit = 8): array
{
    $limit = max(1, min(50, $limit));
    $stmt = db()->prepare('SELECT * FROM contact_messages ORDER BY id DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function dashboardStats(): array
{
    $pdo = db();

    return [
        'products' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'customers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
        'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'messages' => (int) $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn(),
        'revenue' => (float) $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders')->fetchColumn(),
    ];
}
