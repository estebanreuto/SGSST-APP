<?php
session_start();
unset(
    $_SESSION['demo_pem_prospecto_id'],
    $_SESSION['demo_pem_started_at'],
    $_SESSION['demo_pem_nombre'],
    $_SESSION['demo_pem_empresa'],
    $_SESSION['demo_pem_access_hash'],
    $_SESSION['demo_pem_role']
);
header('Location: demo');
exit;
