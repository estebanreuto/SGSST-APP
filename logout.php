<?php
require_once 'config/db.php';
require_once 'config/auth.php';

$uid = $_SESSION['usuario_id'] ?? null;
if ($uid) {
    log_activity($conn, (int)$uid, 'LOGOUT', 'Cierre de sesión');
}

revoke_session($conn);
header('Location: index.php');
exit;

?>
