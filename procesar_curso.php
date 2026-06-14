<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/capacitaciones_schema.php';
require_once 'config/capacitaciones_helpers.php';

$u = require_auth($conn);
ensure_capacitaciones_schema($conn);

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
if (($_SESSION['usuario_rol'] ?? '') !== 'trabajador' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$curso_id = (int)($_POST['curso_id'] ?? 0);
$curso = obtener_curso_trabajador($conn, $curso_id, $usuario_id);
if (!$curso) {
    header('Location: capacitaciones.php');
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    $conn->beginTransaction();

    if ($accion === 'presentar_examen') {
        $ahora = time();
        if ($ahora < strtotime($curso['fecha_inicio']) || $ahora > strtotime($curso['fecha_fin'])) {
            throw new RuntimeException('El curso no está dentro de su periodo de disponibilidad.');
        }

        $stmt_preguntas = $conn->prepare("SELECT * FROM capacitaciones_preguntas WHERE curso_id = ? ORDER BY orden, id");
        $stmt_preguntas->execute([$curso_id]);
        $preguntas = $stmt_preguntas->fetchAll(PDO::FETCH_ASSOC);
        $total_puntos = array_sum(array_column($preguntas, 'puntos'));
        $obtenido = 0.0;
        $resultados = [];

        foreach ($preguntas as $pregunta) {
            $stmt_correctas = $conn->prepare("SELECT id FROM capacitaciones_opciones WHERE pregunta_id = ? AND es_correcta = 1 ORDER BY id");
            $stmt_correctas->execute([$pregunta['id']]);
            $correctas = array_map('intval', $stmt_correctas->fetchAll(PDO::FETCH_COLUMN));
            $seleccionadas = array_map('intval', (array)($_POST['respuestas'][$pregunta['id']] ?? []));
            sort($correctas);
            sort($seleccionadas);
            $es_correcta = $correctas === $seleccionadas;
            $puntos = $es_correcta ? (float)$pregunta['puntos'] : 0.0;
            $obtenido += $puntos;
            $resultados[] = [$pregunta['id'], $seleccionadas, $es_correcta, $puntos];
        }

        $escala = (float)$curso['escala_calificacion'];
        $nota = $total_puntos > 0 ? round(($obtenido / $total_puntos) * $escala, 2) : 0;
        $aprobado = $nota >= (float)$curso['puntaje_aprobacion'] ? 1 : 0;

        $stmt_intento = $conn->prepare("
            INSERT INTO capacitaciones_intentos
                (curso_id, usuario_id, puntaje_obtenido, puntaje_escala, aprobado, iniciado_en, finalizado_en)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE puntaje_obtenido=VALUES(puntaje_obtenido),
                puntaje_escala=VALUES(puntaje_escala), aprobado=VALUES(aprobado), finalizado_en=NOW()
        ");
        $stmt_intento->execute([$curso_id, $usuario_id, $obtenido, $nota, $aprobado]);
        $stmt_id = $conn->prepare("SELECT id FROM capacitaciones_intentos WHERE curso_id = ? AND usuario_id = ?");
        $stmt_id->execute([$curso_id, $usuario_id]);
        $intento_id = (int)$stmt_id->fetchColumn();
        $conn->prepare("DELETE FROM capacitaciones_respuestas WHERE intento_id = ?")->execute([$intento_id]);

        $stmt_respuesta = $conn->prepare("
            INSERT INTO capacitaciones_respuestas
                (intento_id, pregunta_id, opciones_json, correcta, puntos_obtenidos)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($resultados as [$pregunta_id, $seleccionadas, $correcta, $puntos]) {
            $stmt_respuesta->execute([$intento_id, $pregunta_id, json_encode($seleccionadas), $correcta ? 1 : 0, $puntos]);
        }
        $stmt_secciones = $conn->prepare("SELECT COUNT(*) FROM capacitaciones_materiales WHERE curso_id = ?");
        $stmt_secciones->execute([$curso_id]);
        $seccion_actual = (int)$stmt_secciones->fetchColumn();
        $stmt_progreso = $conn->prepare("
            INSERT INTO capacitaciones_progreso
                (curso_id, usuario_id, porcentaje, seccion_actual, completado_en)
            VALUES (?, ?, 100, ?, NOW())
            ON DUPLICATE KEY UPDATE porcentaje=100, seccion_actual=VALUES(seccion_actual),
                completado_en=NOW()
        ");
        $stmt_progreso->execute([$curso_id, $usuario_id, $seccion_actual]);
        $conn->commit();
        header('Location: curso.php?id=' . $curso_id . '&resultado=1');
        exit;
    }

    if ($accion === 'firmar_acta') {
        $firma = $_POST['firma'] ?? '';
        if (!str_starts_with($firma, 'data:image/png;base64,')) {
            throw new RuntimeException('Debes dibujar tu firma.');
        }
        $stmt_intento = $conn->prepare("SELECT id FROM capacitaciones_intentos WHERE curso_id = ? AND usuario_id = ? AND aprobado = 1");
        $stmt_intento->execute([$curso_id, $usuario_id]);
        $intento_id = (int)$stmt_intento->fetchColumn();
        if (!$intento_id) {
            throw new RuntimeException('Primero debes aprobar la evaluación.');
        }
        $aceptacion = 'Declaro que participé en la actividad asignada, presenté y aprobé la evaluación correspondiente.';
        $stmt_acta = $conn->prepare("
            INSERT INTO capacitaciones_actas (curso_id, usuario_id, intento_id, firma, aceptacion)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE intento_id=VALUES(intento_id), firma=VALUES(firma),
                aceptacion=VALUES(aceptacion), enviada_en=NOW()
        ");
        $stmt_acta->execute([$curso_id, $usuario_id, $intento_id, $firma, $aceptacion]);
        $conn->commit();
        header('Location: curso.php?id=' . $curso_id . '&firmado=1');
        exit;
    }

    throw new RuntimeException('Acción no válida.');
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    header('Location: curso.php?id=' . $curso_id . '&error=' . urlencode($e->getMessage()));
    exit;
}
