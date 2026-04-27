<?php

declare(strict_types=1);

session_start();

define('APP_ROOT', dirname(__DIR__));
define('DB_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'agrotienda.sqlite');

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!is_dir(dirname(DB_PATH))) {
        mkdir(dirname(DB_PATH), 0777, true);
    }

    $needsSetup = !file_exists(DB_PATH);

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

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
        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin',
        'created_at' => $now,
    ]);

    $stmt->execute([
        'name' => 'Juan Carrillo',
        'email' => 'cliente@agrotienda.local',
        'phone' => '0999999999',
        'address' => 'Av. Principal y calle 10',
        'password_hash' => password_hash('cliente123', PASSWORD_DEFAULT),
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
    $_SESSION['user_id'] = (int) $user['id'];
}

function logoutUser(): void
{
    unset($_SESSION['user_id'], $_SESSION['cart'], $_SESSION['flash']);
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

function createProduct(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO products (name, category, brand, price, stock, emoji, image_url, short_description, description)
         VALUES (:name, :category, :brand, :price, :stock, :emoji, :image_url, :short_description, :description)'
    );

    $stmt->execute([
        'name' => trim($data['name']),
        'category' => trim($data['category']),
        'brand' => trim($data['brand']),
        'price' => (float) $data['price'],
        'stock' => max(0, (int) $data['stock']),
        'emoji' => '',
        'image_url' => trim($data['image_url']),
        'short_description' => trim($data['short_description']),
        'description' => trim($data['description']),
    ]);

    return (int) db()->lastInsertId();
}

function updateProduct(int $id, array $data): void
{
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
        'name' => trim($data['name']),
        'category' => trim($data['category']),
        'brand' => trim($data['brand']),
        'price' => (float) $data['price'],
        'stock' => max(0, (int) $data['stock']),
        'image_url' => trim($data['image_url']),
        'short_description' => trim($data['short_description']),
        'description' => trim($data['description']),
    ]);
}

function deleteProduct(int $id): void
{
    $stmt = db()->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
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

function addToCart(int $productId, int $quantity): void
{
    $quantity = max(1, $quantity);
    $cart = cart();
    $cart[$productId] = ($cart[$productId] ?? 0) + $quantity;
    $_SESSION['cart'] = $cart;
}

function updateCart(array $quantities): void
{
    $next = [];
    foreach ($quantities as $productId => $quantity) {
        $qty = (int) $quantity;
        if ($qty > 0) {
            $next[(int) $productId] = $qty;
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
    $totals = cartTotals();

    if (empty($totals['items'])) {
        throw new RuntimeException('No hay productos en el carrito.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO orders (
                user_id, customer_name, customer_email, customer_phone, shipping_address, notes,
                subtotal, shipping, total, status, created_at
            ) VALUES (
                :user_id, :customer_name, :customer_email, :customer_phone, :shipping_address, :notes,
                :subtotal, :shipping, :total, :status, :created_at
            )'
        );

        $stmt->execute([
            'user_id' => $user['id'],
            'customer_name' => $payload['customer_name'],
            'customer_email' => $payload['customer_email'],
            'customer_phone' => $payload['customer_phone'],
            'shipping_address' => $payload['shipping_address'],
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

        $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id');

        foreach ($totals['items'] as $item) {
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

function dashboardStats(): array
{
    $pdo = db();

    return [
        'products' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'customers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
        'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'revenue' => (float) $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders')->fetchColumn(),
    ];
}
