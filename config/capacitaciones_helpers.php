<?php

function trabajador_tiene_curso(array $curso, int $usuario_id, ?int $grupo_id): bool
{
    if ($curso['dirigido_a'] === 'Toda la empresa') {
        return true;
    }

    if ($curso['dirigido_a'] === 'Trabajador Específico') {
        return !empty($curso['asignado_directo']);
    }

    if (str_starts_with($curso['dirigido_a'], 'Grupo: ')) {
        return $grupo_id && (int)$curso['grupo_asignado_id'] === $grupo_id;
    }

    return false;
}

function obtener_curso_trabajador(PDO $conn, int $curso_id, int $usuario_id): ?array
{
    $stmt_usuario = $conn->prepare("SELECT empresa_id, grupo_id FROM usuarios WHERE id = ? AND rol = 'trabajador'");
    $stmt_usuario->execute([$usuario_id]);
    $usuario = $stmt_usuario->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        return null;
    }

    $stmt = $conn->prepare("
        SELECT c.*, a.nombre_actividad, a.tipo_capacitacion, a.categoria, a.dirigido_a,
               a.descripcion, a.fecha_inicio, a.fecha_fin, a.modalidad,
               EXISTS(
                   SELECT 1 FROM actividades_trabajadores at
                   WHERE at.actividad_id = a.id AND at.usuario_id = ?
               ) AS asignado_directo,
               (
                   SELECT g.id FROM grupos_personal g
                   WHERE g.empresa_id = a.empresa_id
                     AND CONCAT('Grupo: ', g.nombre) = a.dirigido_a
                   LIMIT 1
               ) AS grupo_asignado_id
        FROM capacitaciones_cursos c
        JOIN actividades_capacitacion a ON a.id = c.actividad_id
        WHERE c.id = ? AND a.empresa_id = ?
    ");
    $stmt->execute([$usuario_id, $curso_id, $usuario['empresa_id']]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);

    return $curso && trabajador_tiene_curso($curso, $usuario_id, $usuario['grupo_id'] ? (int)$usuario['grupo_id'] : null)
        ? $curso
        : null;
}

function youtube_embed_url(string $url): ?string
{
    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    return null;
}

function capacitacion_tema(string $tipo): string
{
    $tipo = mb_strtolower($tipo, 'UTF-8');
    if (str_contains($tipo, 're indu') || str_contains($tipo, 'reindu')) {
        return 'teal';
    }
    if (str_contains($tipo, 'indu')) {
        return 'blue';
    }
    if (str_contains($tipo, 'charla')) {
        return 'amber';
    }
    if (str_contains($tipo, 'entrena')) {
        return 'rose';
    }
    return 'violet';
}

function capacitacion_icono(string $tipo): string
{
    $tipo = mb_strtolower($tipo, 'UTF-8');
    if (str_contains($tipo, 're indu') || str_contains($tipo, 'reindu')) {
        return 'fa-arrows-rotate';
    }
    if (str_contains($tipo, 'indu')) {
        return 'fa-compass';
    }
    if (str_contains($tipo, 'entrena')) {
        return 'fa-dumbbell';
    }
    if (str_contains($tipo, 'charla')) {
        return 'fa-shield-halved';
    }
    return 'fa-graduation-cap';
}
