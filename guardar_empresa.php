<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// 1. Exige sesión válida
$u = require_auth($conn);

// 2. Validar que solo el representante pueda guardar esto
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'representante') {
    header("Location: dashboard.php?error=no_permission");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];

    // 3. Recibir y limpiar datos del formulario
    $nombre_empresa = trim($_POST['nombre_empresa'] ?? '');
    $tipo_persona = trim($_POST['tipo_persona'] ?? '');
    $regimen_tributario = trim($_POST['regimen_tributario'] ?? '');
    $tipo_doc_empresa = trim($_POST['tipo_doc_empresa'] ?? '');
    $num_doc_empresa = trim($_POST['num_doc_empresa'] ?? '');
    $actividades_economicas = trim($_POST['actividades_economicas'] ?? '');

    // Validación básica de seguridad
    if (empty($nombre_empresa) || empty($num_doc_empresa) || empty($tipo_doc_empresa)) {
        header("Location: dashboard.php?error=empty_fields");
        exit;
    }

    // 4. Lógica para manejar el Logo de la Empresa (Si suben uno)
    $logo_path = null;
    
    if (isset($_FILES['logo_empresa']) && $_FILES['logo_empresa']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/logos/';
        
        // Crea la carpeta si no existe protegiendo permisos
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file = $_FILES['logo_empresa'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validar extensiones de imagen
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            // Nombre único: logo_1_168000000.png
            $fileName = 'logo_empresa_' . $usuario_id . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $logo_path = $destination;

                // BORRADO DEL LOGO ANTERIOR (Para no llenar el servidor de basura)
                $stmtOld = $conn->prepare("SELECT logo_empresa FROM usuarios WHERE id = ?");
                $stmtOld->execute([$usuario_id]);
                $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

                if ($oldData && !empty($oldData['logo_empresa']) && file_exists($oldData['logo_empresa'])) {
                    unlink($oldData['logo_empresa']); // Elimina el archivo físico viejo
                }
            }
        }
    }

    // 5. Actualizar la Base de Datos (Tabla usuarios)
    try {
        if ($logo_path !== null) {
            // Actualiza los datos INCLUYENDO el logo nuevo
            $sql = "UPDATE usuarios 
                    SET nombre_empresa = ?, 
                        tipo_persona = ?, 
                        regimen_tributario = ?, 
                        tipo_doc_empresa = ?, 
                        num_doc_empresa = ?, 
                        actividad_economica = ?, 
                        logo_empresa = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $nombre_empresa, 
                $tipo_persona, 
                $regimen_tributario, 
                $tipo_doc_empresa, 
                $num_doc_empresa, 
                $actividades_economicas, 
                $logo_path, 
                $usuario_id
            ]);
        } else {
            // Actualiza los datos SIN TOCAR el logo (por si el usuario no subió uno nuevo)
            $sql = "UPDATE usuarios 
                    SET nombre_empresa = ?, 
                        tipo_persona = ?, 
                        regimen_tributario = ?, 
                        tipo_doc_empresa = ?, 
                        num_doc_empresa = ?, 
                        actividad_economica = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $nombre_empresa, 
                $tipo_persona, 
                $regimen_tributario, 
                $tipo_doc_empresa, 
                $num_doc_empresa, 
                $actividades_economicas, 
                $usuario_id
            ]);
        }

        // 6. Redirigir de vuelta con el mensaje de éxito
        // Detecta de qué página vino para devolverlo ahí mismo (ej. dashboard.php)
        $referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
        $url_parts = parse_url($referer);
        $base_url = $url_parts['path'] ?? 'dashboard.php';
        
        // Esto dispara la alerta verde superior que hicimos en el dashboard
        header("Location: " . $base_url . "?update=success");
        exit;

    } catch (PDOException $e) {
        // En caso de error en base de datos
        error_log("Error guardando empresa: " . $e->getMessage());
        header("Location: dashboard.php?error=db_error");
        exit;
    }

} else {
    // Si intentan entrar por URL directamente sin enviar el formulario
    header("Location: dashboard.php");
    exit;
}
?>