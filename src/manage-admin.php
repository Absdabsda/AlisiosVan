<?php
// src/manage-admin.php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap_env.php';

// Requiere cookie de admin válida
$adminKeyEnv = env('ADMIN_KEY','');
$cookieKey   = $_COOKIE['admin_key'] ?? '';
if (!$adminKeyEnv || !$cookieKey || !hash_equals($adminKeyEnv, (string)$cookieKey)) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

// Señaliza a manage.php que debe mostrar UI/admin y actuar como admin
define('MANAGE_FORCE_ADMIN_UI', true);

require __DIR__ . '/manage.php';
