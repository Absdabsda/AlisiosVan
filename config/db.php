<?php
declare(strict_types=1);

/**
 * Retorna un PDO singleton usando variables de entorno (.env).
 */
function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host    = $_ENV['DB_HOST']    ?? '127.0.0.1';
    $db      = $_ENV['DB_NAME']    ?? 'alisiosvan';
    $user    = $_ENV['DB_USER']    ?? 'root';
    $pass    = $_ENV['DB_PASS']    ?? '';
    $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $opts);
    return $pdo;
}
