<?php
declare(strict_types=1);
require __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION = [];
    session_destroy();
}
redirect(BASE_URL . '/admin/login.php');
