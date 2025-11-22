<?php
// Database configuration for Hostinger MySQL
// Update DB_HOST only if Hostinger provided a remote hostname; otherwise keep 'localhost'.
const DB_HOST = 'localhost';
const DB_NAME = 'u404968876_estate';
const DB_USER = 'u404968876_estate';
const DB_PASS = 'Estate00-$';
const DB_CHARSET = 'utf8mb4';

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Error al conectar con la base de datos. Verifica credenciales.';
    error_log('DB connection failed: ' . $e->getMessage());
    exit;
}
