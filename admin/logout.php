<?php
session_start();
unset($_SESSION['cpanel_admin_id']);
unset($_SESSION['cpanel_admin_nombre']);
header("Location: login.php");
exit;