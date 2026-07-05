<?php
require_once __DIR__ . '/env.php';

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';

if ($db === '' || $user === '') {
    throw new RuntimeException('Faltan DB_NAME o DB_USER en el archivo .env.');
}

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Error de conexion a la base de datos.");
}

