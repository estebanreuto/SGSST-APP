<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Exige sesión válida
$u = require_auth($conn);

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

if ($accion === 'subir_planilla') {
    $mes = (int)$_POST['mes'];
    $anio = (int)$_POST['anio'];
    $usuario_id = $_SESSION['usuario_id'];
    
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        // Crear carpeta si no existe
        $dir = 'uploads/planillas/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        // Generar nombre único para no sobreescribir
        $ext = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
        $filename = "planilla_sst_{$anio}_{$mes}_" . time() . ".$ext";
        $filepath = $dir . $filename;
        
        // Mover el archivo subido
        if (move_uploaded_file($_FILES['archivo']['tmp_name'], $filepath)) {
            // Guardar en Base de Datos (Si ya existe, la actualiza)
            $sql = "INSERT INTO estandar2_planillas (mes, anio, archivo_url, subido_por) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE archivo_url = ?, subido_por = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$mes, $anio, $filepath, $usuario_id, $filepath, $usuario_id]);
            
            header("Location: dashboard.php?std=2&msg=subido");
            exit;
        }
    }
    header("Location: dashboard.php?std=2&error=1");
    exit;
} 
elseif ($accion === 'eliminar_planilla') {
    $id = (int)$_GET['id'];
    
    // Buscar archivo para eliminarlo físicamente
    $stmt = $conn->prepare("SELECT archivo_url FROM estandar2_planillas WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetchColumn();
    
    if ($file && file_exists($file)) {
        unlink($file); // Borrar archivo del servidor
    }
    
    // Eliminar de base de datos
    $stmt = $conn->prepare("DELETE FROM estandar2_planillas WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: dashboard.php?std=2&msg=eliminado");
    exit;
}

header("Location: dashboard.php");
exit;