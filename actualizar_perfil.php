<?php
// actualizar_perfil.php
require_once 'config/db.php';
require_once 'config/auth.php';

// Exige sesión válida
$u = require_auth($conn);
$usuario_id = $_SESSION['usuario_id'];
$usuario_rol = $_SESSION['usuario_rol'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // 1. Recoger datos básicos (que todos los roles pueden editar en este modal)
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');

    // Consulta inicial para datos generales
    $sql = "UPDATE usuarios SET 
            nombre = ?, 
            apellido = ?, 
            cedula = ?, 
            telefono = ?, 
            ciudad = ? ";
    
    $params = [$nombre, $apellido, $cedula, $telefono, $ciudad];

    // 2. Si el rol es SST o Representante, agregamos los campos de certificación al UPDATE
    if ($usuario_rol === 'sst' || $usuario_rol === 'representante') {
        $licencia_sst = $_POST['licencia_sst'] ?? 'no';
        $tipo_licencia = trim($_POST['tipo_licencia'] ?? '');
        $numero_licencia = trim($_POST['numero_licencia'] ?? '');
        $fecha_licencia = !empty($_POST['fecha_licencia']) ? $_POST['fecha_licencia'] : null;

        $sql .= ", licencia_sst = ?, tipo_licencia = ?, numero_licencia = ?, fecha_licencia = ? ";
        array_push($params, $licencia_sst, $tipo_licencia, $numero_licencia, $fecha_licencia);
    }

    // 3. Finalizamos la consulta atándola SOLO al ID del usuario en sesión
    $sql .= " WHERE id = ?";
    array_push($params, $usuario_id);

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        // Opcional: Actualizar el nombre en sesión si lo cambia para que la interfaz se actualice
        $_SESSION['usuario_nombre'] = $nombre;

        // Redirigir de vuelta al dashboard con éxito
        header("Location: dashboard.php?update=success");
        exit;
    } catch (PDOException $e) {
        // Redirigir de vuelta al dashboard con error
        header("Location: dashboard.php?update=error");
        exit;
    }
} else {
    // Si entran por GET, expulsarlos
    header("Location: dashboard.php");
    exit;
}