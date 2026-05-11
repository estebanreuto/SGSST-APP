<?php
$host = "localhost";
$db   = "u261346399_preventwork";
$user = "u261346399_reutorwe_";
$pass = "Sol2026!Casa#";

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

