<?php
session_start();
require_once 'config/db.php';
require_once 'config/auth.php';

$uid = $_SESSION['usuario_id'] ?? null;
if ($uid) {
    log_activity($conn, (int)$uid, 'LOGOUT', 'Cierre de sesión');
}

// ==========================================
// NUEVO: DESTRUIR LA COOKIE DE RECORDAR SESIÓN
// ==========================================
if (isset($_COOKIE['sgsst_remember'])) {
    $token = $_COOKIE['sgsst_remember'];
    // Borramos el token de la base de datos por seguridad
    $stmt = $conn->prepare("DELETE FROM sesiones WHERE token = ?");
    $stmt->execute([$token]);
    // Borramos la cookie del navegador poniéndole fecha de ayer
    setcookie('sgsst_remember', '', time() - 3600, '/');
}

revoke_session($conn);
header('Location: login.php'); // Redirigimos al login
exit;
?>