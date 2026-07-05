<?php
require_once __DIR__ . '/env.php';

$host = getenv('ONLINE_DB_HOST') ?: (getenv('DB_HOST') ?: 'localhost');
$db   = getenv('ONLINE_DB_NAME') ?: (getenv('DB_NAME') ?: '');
$user = getenv('ONLINE_DB_USER') ?: (getenv('DB_USER') ?: '');
$pass = getenv('ONLINE_DB_PASS');
$pass = $pass !== false ? $pass : (getenv('DB_PASS') ?: '');

if ($db === '' || $user === '') {
    throw new RuntimeException('Faltan ONLINE_DB_NAME o ONLINE_DB_USER en el archivo .env.');
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

