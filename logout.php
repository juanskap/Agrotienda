<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

logoutUser();
session_regenerate_id(true);
setFlash('success', 'Sesion cerrada.');
redirect('index.php');
