<?php

declare(strict_types=1);

require_once __DIR__ . '/app/layout.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($productId <= 0) {
        setFlash('error', 'Producto no valido.');
        redirect('store.php');
    }

    $nowFav = toggleFavorite((int) $user['id'], $productId);

    setFlash('success', $nowFav
        ? 'Producto agregado a favoritos.'
        : 'Producto eliminado de favoritos.');

    $referer = $_SERVER['HTTP_REFERER'] ?? 'store.php';
    redirect($referer);
}

redirect('store.php');
