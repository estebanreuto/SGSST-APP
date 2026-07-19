<?php
session_start();
require_once '../config/db.php';
require_once '../config/storage_schema.php';

// Bloquear acceso si no es admin
if (!isset($_SESSION['cpanel_admin_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensure_storage_schema($conn);
    $id = $_POST['id'] ?? 0;
    $nombre = $_POST['nombre'] ?? '';
    $trabajadores = $_POST['trabajadores'] ?? 1; // <--- Nuevo
    $almacenamiento_gb = max(1, min(10000, (float)($_POST['almacenamiento_gb'] ?? 30)));
    $precio_normal = $_POST['precio_normal'] ?? 0;
    $precio_descuento = $_POST['precio_descuento'] ?? 0;

    $precio_descuento = empty($precio_descuento) ? 0 : $precio_descuento;

    if ($id) {
        // Añadimos "trabajadores = ?" a la consulta SQL
        $stmt = $conn->prepare("UPDATE planes SET nombre = ?, trabajadores = ?, almacenamiento_gb = ?, precio_normal = ?, precio_descuento = ? WHERE id = ?");
        $stmt->execute([$nombre, $trabajadores, $almacenamiento_gb, $precio_normal, $precio_descuento, $id]);
        // 2. Actualizar características (Borramos las viejas y guardamos las nuevas)
        $stmt_del = $conn->prepare("DELETE FROM plan_caracteristicas WHERE plan_id = ?");
        $stmt_del->execute([$id]);

        if (isset($_POST['feat_text']) && is_array($_POST['feat_text'])) {
            $stmt_ins = $conn->prepare("INSERT INTO plan_caracteristicas (plan_id, texto, incluido) VALUES (?, ?, ?)");

            // Recorremos todos los inputs de características que envió el modal
            foreach ($_POST['feat_text'] as $index => $texto) {
                if (trim($texto) !== '') { // Evitar guardar filas vacías
                    $incluido = $_POST['feat_status'][$index] ?? 0;
                    $stmt_ins->execute([$id, trim($texto), $incluido]);
                }
            }
        }
    }

    // Redirigir de nuevo a la página de planes
    header("Location: planes.php");
    exit;
}
