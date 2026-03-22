<?php
session_start();

// Destruimos únicamente las variables de sesión del admin
unset($_SESSION['cpanel_admin_id']);
unset($_SESSION['cpanel_admin_nombre']);

// Destruimos la sesión completa
session_destroy();

// Redirigimos al login del admin
header("Location: login.php");
exit;
?>