<?php
/**
 * Central PDO factory. Hardcoded credentials, per project spec.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=127.0.0.1;port=3309;dbname=landbridge;charset=utf8mb4';
    $pdo = new PDO($dsn, 'landbridge', 'landbridge123', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}