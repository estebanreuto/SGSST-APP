<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/estandar5_schema.php';

date_default_timezone_set('America/Bogota');
$u = require_auth($conn);
ensure_estandar5_schema($conn);

$usuario_rol = $_SESSION['usuario_rol'] ?? '';
if (!in_array($usuario_rol, ['sst', 'representante'], true)) {
    header('Location: dashboard.php');
    exit;
}

$submodulos = [
    'sociodemografica' => [
        'codigo' => '3.1.1',
        'titulo' => 'Descripción sociodemográfica - Diagnóstico de condiciones de salud',
        'descripcion' => 'Base para consolidar información poblacional y hallazgos generales de salud.',
        'icono' => 'fa-users-viewfinder',
    ],
    'promocion-prevencion' => [
        'codigo' => '3.1.2',
        'titulo' => 'Actividades de Promoción y Prevención en Salud',
        'descripcion' => 'Planificación y seguimiento de acciones preventivas derivadas del diagnóstico.',
        'icono' => 'fa-heart-pulse',
    ],
    'perfiles-cargo' => [
        'codigo' => '3.1.3',
        'titulo' => 'Información al médico de los perfiles de cargo',
        'descripcion' => 'Insumos del cargo, peligros y funciones para orientar los exámenes médicos.',
        'icono' => 'fa-user-doctor',
    ],
    'evaluaciones-medicas' => [
        'codigo' => '3.1.4',
        'titulo' => 'Realización de Evaluaciones Médicas Ocupacionales - Peligros - Periodicidad',
        'descripcion' => 'Control de ingreso, periódicos, egreso y periodicidad según exposición.',
        'icono' => 'fa-notes-medical',
    ],
    'historias-clinicas' => [
        'codigo' => '3.1.5',
        'titulo' => 'Custodia de Historias Clínicas',
        'descripcion' => 'Gestión de soporte y evidencia de custodia por entidad competente.',
        'icono' => 'fa-folder-open',
    ],
    'restricciones' => [
        'codigo' => '3.1.6',
        'titulo' => 'Restricciones y recomendaciones médico/laborales',
        'descripcion' => 'Seguimiento ejecutivo de restricciones, recomendaciones y cierre de acciones.',
        'icono' => 'fa-clipboard-list',
    ],
];

$modulo_actual = $_GET['modulo'] ?? '';
if ($modulo_actual !== '' && !isset($submodulos[$modulo_actual])) {
    $modulo_actual = '';
}
$modulo = $modulo_actual !== '' ? $submodulos[$modulo_actual] : null;

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$stmt_empresa = $conn->prepare("SELECT empresa_id, nombre_empresa FROM usuarios WHERE id = ?");
$stmt_empresa->execute([$usuario_id]);
$usuario_empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC) ?: [];
$empresa_id = (int)($usuario_empresa['empresa_id'] ?? 0);
$empresa_nombre = $usuario_empresa['nombre_empresa'] ?? 'Empresa';

function estandar5_socio_preguntas(): array
{
    return [
        'edad' => ['label' => 'Edad', 'icon' => 'fa-hourglass-half'],
        'estado_civil' => ['label' => 'Estado civil', 'icon' => 'fa-heart'],
        'genero' => ['label' => 'Género', 'icon' => 'fa-person-half-dress'],
        'personas_cargo' => ['label' => 'Personas a cargo', 'icon' => 'fa-people-roof'],
        'escolaridad' => ['label' => 'Escolaridad', 'icon' => 'fa-graduation-cap'],
        'vivienda' => ['label' => 'Vivienda', 'icon' => 'fa-house'],
        'tiempo_libre' => ['label' => 'Tiempo libre', 'icon' => 'fa-clock'],
        'experiencia' => ['label' => 'Experiencia', 'icon' => 'fa-briefcase'],
        'estrato' => ['label' => 'Estrato', 'icon' => 'fa-layer-group'],
        'convive_con' => ['label' => 'Convive con', 'icon' => 'fa-users'],
        'raza' => ['label' => 'Raza', 'icon' => 'fa-earth-americas'],
        'tipo_contrato' => ['label' => 'Tipo de contrato', 'icon' => 'fa-file-signature'],
        'turno' => ['label' => 'Turno', 'icon' => 'fa-calendar-days'],
        'antiguedad' => ['label' => 'Antigüedad', 'icon' => 'fa-chart-line'],
        'enfermedad' => ['label' => 'Enfermedad diagnosticada', 'icon' => 'fa-notes-medical'],
        'fuma' => ['label' => 'Fuma', 'icon' => 'fa-smoking'],
        'alcohol' => ['label' => 'Consumo de alcohol', 'icon' => 'fa-wine-glass'],
        'deporte' => ['label' => 'Actividad física/deporte', 'icon' => 'fa-person-running'],
        'tipo_personal' => ['label' => 'Tipo de personal', 'icon' => 'fa-id-badge'],
    ];
}

function estandar5_socio_respuesta($valor): string
{
    $texto = trim((string)$valor);
    return $texto === '' ? 'Sin respuesta' : $texto;
}

function estandar5_socio_rows(PDO $conn, int $empresa_id): array
{
    if ($empresa_id <= 0) {
        return [];
    }

    $stmt = $conn->prepare("
        SELECT e.*, u.nombre, u.apellido, u.cedula, u.email, u.activo
        FROM encuesta_sociodemografica e
        INNER JOIN usuarios u ON u.id = e.usuario_id
        WHERE u.empresa_id = ? AND u.rol = 'trabajador'
        ORDER BY u.nombre ASC, u.apellido ASC
    ");
    $stmt->execute([$empresa_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function estandar5_socio_stats(array $rows, array $preguntas): array
{
    $stats = [];
    $total = count($rows);
    foreach ($preguntas as $campo => $meta) {
        $conteos = [];
        foreach ($rows as $row) {
            $respuesta = estandar5_socio_respuesta($row[$campo] ?? '');
            $conteos[$respuesta] = ($conteos[$respuesta] ?? 0) + 1;
        }
        arsort($conteos);
        $items = [];
        foreach ($conteos as $respuesta => $cantidad) {
            $items[] = [
                'respuesta' => $respuesta,
                'cantidad' => $cantidad,
                'porcentaje' => $total > 0 ? round(($cantidad / $total) * 100, 1) : 0,
            ];
        }
        $stats[$campo] = [
            'label' => $meta['label'],
            'icon' => $meta['icon'],
            'total' => $total,
            'items' => $items,
            'principal' => $items[0] ?? ['respuesta' => 'Sin datos', 'cantidad' => 0, 'porcentaje' => 0],
        ];
    }
    return $stats;
}

function estandar5_socio_export_html(array $stats, array $preguntas, string $empresa_nombre, ?string $pregunta_filtro = null, string $buscar_filtro = ''): string
{
    $fecha = date('d/m/Y H:i');
    $html = '<!doctype html><html lang="es"><head><meta charset="UTF-8"><style>
        body{font-family:DejaVu Sans,Arial,sans-serif;color:#1f2d3d;font-size:12px}
        h1{color:#1e3a8a;font-size:20px;margin:0 0 5px}
        h2{color:#1e3a8a;font-size:15px;margin:22px 0 8px;border-bottom:1px solid #dbe3ec;padding-bottom:6px}
        .meta{color:#64748b;margin-bottom:16px}.question{page-break-inside:avoid;margin-bottom:16px}
        table{width:100%;border-collapse:collapse;margin-top:8px}th,td{border:1px solid #dbe3ec;padding:7px;text-align:left}
        th{background:#f8fafc;color:#334155}.bar{height:9px;background:#e2e8f0;border-radius:20px;overflow:hidden}
        .fill{height:9px;background:#ff7a00}.summary{background:#fff7ed;border:1px solid #fed7aa;padding:8px;border-radius:6px;color:#9a3412;margin:8px 0}
    </style></head><body>';
    $html .= '<h1>Informe perfil sociodemográfico</h1>';
    $html .= '<div class="meta">Empresa: ' . htmlspecialchars($empresa_nombre) . ' | Generado: ' . htmlspecialchars($fecha) . '</div>';

    foreach ($preguntas as $campo => $meta) {
        if ($pregunta_filtro && $pregunta_filtro !== $campo) {
            continue;
        }
        $dato = $stats[$campo] ?? null;
        if (!$dato) {
            continue;
        }
        if ($buscar_filtro !== '' && stripos($meta['label'], $buscar_filtro) === false) {
            $coincide = false;
            foreach ($dato['items'] as $item_busqueda) {
                if (stripos($item_busqueda['respuesta'], $buscar_filtro) !== false) {
                    $coincide = true;
                    break;
                }
            }
            if (!$coincide) {
                continue;
            }
        }
        $html .= '<section class="question">';
        $html .= '<h2>' . htmlspecialchars($meta['label']) . '</h2>';
        $html .= '<div class="summary">Respuesta predominante: <strong>' . htmlspecialchars($dato['principal']['respuesta']) . '</strong> (' . htmlspecialchars((string)$dato['principal']['porcentaje']) . '%)</div>';
        $html .= '<table><thead><tr><th>Respuesta</th><th>Cantidad</th><th>Porcentaje</th><th>Gráfica</th></tr></thead><tbody>';
        foreach ($dato['items'] as $item) {
            if ($buscar_filtro !== '' && stripos($meta['label'], $buscar_filtro) === false && stripos($item['respuesta'], $buscar_filtro) === false) {
                continue;
            }
            $html .= '<tr><td>' . htmlspecialchars($item['respuesta']) . '</td><td>' . (int)$item['cantidad'] . '</td><td>' . htmlspecialchars((string)$item['porcentaje']) . '%</td><td><div class="bar"><div class="fill" style="width:' . htmlspecialchars((string)$item['porcentaje']) . '%"></div></div></td></tr>';
        }
        if (empty($dato['items'])) {
            $html .= '<tr><td colspan="4">No hay respuestas registradas.</td></tr>';
        }
        $html .= '</tbody></table></section>';
    }
    return $html . '</body></html>';
}

function estandar5_pyp_categorias(): array
{
    return [
        'Estilos de Vida Saludable (Alimentación, Ejercicio, Lavado de Manos)' => [
            'titulo' => 'Estilos de vida saludable',
            'icon' => 'fa-person-running',
            'descripcion' => 'Alimentación, ejercicio, lavado de manos y hábitos protectores.',
        ],
        'Prevención Consumo de Sustancias (Alcohol, Drogas, Fármacos, Tabaco)' => [
            'titulo' => 'Prevención consumo de sustancias',
            'icon' => 'fa-ban-smoking',
            'descripcion' => 'Alcohol, drogas, fármacos, tabaco y autocuidado.',
        ],
        'Bienestar Emocional y Mental' => [
            'titulo' => 'Bienestar emocional y mental',
            'icon' => 'fa-brain',
            'descripcion' => 'Promoción de salud mental, equilibrio y manejo emocional.',
        ],
        'Controles Médicos Periódicos (Autocuidado)' => [
            'titulo' => 'Controles médicos periódicos',
            'icon' => 'fa-stethoscope',
            'descripcion' => 'Autocuidado y seguimiento preventivo de condiciones de salud.',
        ],
    ];
}

function estandar5_pyp_rows(PDO $conn, int $empresa_id): array
{
    if ($empresa_id <= 0) {
        return [];
    }

    $stmt = $conn->prepare("
        SELECT
            a.*,
            (SELECT c.id FROM capacitaciones_cursos c WHERE c.actividad_id = a.id LIMIT 1) AS curso_id,
            (SELECT COUNT(DISTINCT at.usuario_id) FROM actividades_trabajadores at WHERE at.actividad_id = a.id) AS trabajadores_asignados,
            (
                SELECT COUNT(DISTINCT cp.usuario_id)
                FROM capacitaciones_progreso cp
                INNER JOIN capacitaciones_cursos c2 ON c2.id = cp.curso_id
                WHERE c2.actividad_id = a.id
            ) AS trabajadores_con_progreso,
            (
                SELECT COUNT(DISTINCT cp.usuario_id)
                FROM capacitaciones_progreso cp
                INNER JOIN capacitaciones_cursos c3 ON c3.id = cp.curso_id
                WHERE c3.actividad_id = a.id AND (cp.porcentaje >= 100 OR cp.completado_en IS NOT NULL)
            ) AS trabajadores_completaron,
            (
                SELECT COUNT(DISTINCT ca.usuario_id)
                FROM capacitaciones_actas ca
                INNER JOIN capacitaciones_cursos c4 ON c4.id = ca.curso_id
                WHERE c4.actividad_id = a.id
            ) AS actas_firmadas
        FROM actividades_capacitacion a
        WHERE a.empresa_id = ? AND a.tipo_capacitacion = 'Campaña PyP en Salud'
        ORDER BY a.fecha_inicio DESC, a.id DESC
    ");
    $stmt->execute([$empresa_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function estandar5_pyp_total_trabajadores(PDO $conn, int $empresa_id): int
{
    if ($empresa_id <= 0) {
        return 0;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = ? AND rol = 'trabajador' AND COALESCE(activo, 1) = 1");
    $stmt->execute([$empresa_id]);
    return (int)$stmt->fetchColumn();
}

function estandar5_pyp_estado(array $row): array
{
    $estado = strtolower((string)($row['estado'] ?? 'programada'));
    if (in_array($estado, ['completada', 'ejecutada'], true)) {
        return ['key' => 'ejecutada', 'label' => 'Ejecutada'];
    }
    if (in_array($estado, ['cancelada', 'no_ejecutada'], true)) {
        return ['key' => 'cancelada', 'label' => $estado === 'cancelada' ? 'Cancelada' : 'No ejecutada'];
    }
    if ($estado === 'en_proceso') {
        return ['key' => 'en_proceso', 'label' => 'En proceso'];
    }

    $fin = !empty($row['fecha_fin']) ? strtotime((string)$row['fecha_fin']) : false;
    $inicio = !empty($row['fecha_inicio']) ? strtotime((string)$row['fecha_inicio']) : false;
    $ahora = time();
    if ($fin && $fin < $ahora) {
        return ['key' => 'vencida', 'label' => 'Por cerrar'];
    }
    if ($inicio && $inicio <= $ahora && (!$fin || $fin >= $ahora)) {
        return ['key' => 'en_proceso', 'label' => 'En proceso'];
    }
    return ['key' => 'programada', 'label' => 'Programada'];
}

function estandar5_pyp_fecha($fecha): string
{
    if (empty($fecha)) {
        return 'Sin fecha';
    }
    $ts = strtotime((string)$fecha);
    return $ts ? date('d/m/Y h:i A', $ts) : 'Sin fecha';
}

function estandar5_pyp_alcance(array $row, int $total_trabajadores): int
{
    $asignados = (int)($row['trabajadores_asignados'] ?? 0);
    $dirigido = (string)($row['dirigido_a'] ?? '');
    if ($asignados === 0 && stripos($dirigido, 'Toda la empresa') !== false) {
        return $total_trabajadores;
    }
    return $asignados;
}

function estandar5_pyp_resumen(array $rows, array $categorias, int $total_trabajadores): array
{
    $resumen = [
        'total' => count($rows),
        'programadas' => 0,
        'en_proceso' => 0,
        'ejecutadas' => 0,
        'vencidas' => 0,
        'personas_alcance' => 0,
        'con_evaluacion' => 0,
        'categorias' => [],
    ];

    foreach ($categorias as $categoria => $meta) {
        $resumen['categorias'][$categoria] = [
            'meta' => $meta,
            'total' => 0,
            'programadas' => 0,
            'en_proceso' => 0,
            'ejecutadas' => 0,
            'vencidas' => 0,
            'personas_alcance' => 0,
            'ultima' => null,
            'proxima' => null,
            'rows' => [],
        ];
    }

    foreach ($rows as $row) {
        $categoria = (string)($row['categoria'] ?? 'Sin categoría');
        if (!isset($resumen['categorias'][$categoria])) {
            $resumen['categorias'][$categoria] = [
                'meta' => [
                    'titulo' => $categoria,
                    'icon' => 'fa-circle-plus',
                    'descripcion' => 'Categoría personalizada registrada desde capacitación.',
                ],
                'total' => 0,
                'programadas' => 0,
                'en_proceso' => 0,
                'ejecutadas' => 0,
                'vencidas' => 0,
                'personas_alcance' => 0,
                'ultima' => null,
                'proxima' => null,
                'rows' => [],
            ];
        }

        $estado = estandar5_pyp_estado($row);
        $row['_estado_key'] = $estado['key'];
        $row['_estado_label'] = $estado['label'];
        $alcance = estandar5_pyp_alcance($row, $total_trabajadores);
        $row['_alcance'] = $alcance;
        $row['_avance'] = $alcance > 0 ? round(((int)($row['trabajadores_completaron'] ?? 0) / $alcance) * 100) : 0;
        $row['_tiene_evaluacion'] = !empty($row['curso_id']);

        $bucket =& $resumen['categorias'][$categoria];
        $bucket['total']++;
        $bucket['personas_alcance'] += $alcance;
        $bucket['rows'][] = $row;

        if (isset($bucket[$estado['key']])) {
            $bucket[$estado['key']]++;
        }
        if (isset($resumen[$estado['key']])) {
            $resumen[$estado['key']]++;
        }
        if ($row['_tiene_evaluacion']) {
            $resumen['con_evaluacion']++;
        }
        $resumen['personas_alcance'] += $alcance;

        $inicio = !empty($row['fecha_inicio']) ? strtotime((string)$row['fecha_inicio']) : false;
        if ($inicio) {
            if (!$bucket['ultima'] || $inicio > strtotime((string)$bucket['ultima']['fecha_inicio'])) {
                $bucket['ultima'] = $row;
            }
            if ($inicio >= time() && (!$bucket['proxima'] || $inicio < strtotime((string)$bucket['proxima']['fecha_inicio']))) {
                $bucket['proxima'] = $row;
            }
        }
        unset($bucket);
    }

    return $resumen;
}

function estandar5_decode_list(?string $json): array
{
    $data = json_decode((string)$json, true);
    return is_array($data) ? array_values(array_filter($data, fn($item) => trim((string)$item) !== '')) : [];
}

function estandar5_herramientas_por_grupo(): array
{
    return [
        'administrativo' => [
            'titulo' => 'Herramientas administrativas',
            'icon' => 'fa-briefcase',
            'items' => ['Equipos de cómputo', 'Grapadora', 'Sacaganchos', 'Tijeras', 'Bisturí'],
        ],
        'menores' => [
            'titulo' => 'Herramientas menores',
            'icon' => 'fa-hammer',
            'items' => ['Martillo', 'Destornilladores', 'Alicates', 'Pinzas', 'Llaves de presión', 'Llaves mixtas', 'Cortafrío', 'Flexómetro'],
        ],
        'electricas' => [
            'titulo' => 'Herramientas eléctricas',
            'icon' => 'fa-plug-circle-bolt',
            'items' => ['Taladro', 'Pulidora', 'Sierra eléctrica', 'Caladora', 'Esmeril', 'Hidrolavadora', 'Soldador eléctrico', 'Compresor', 'Extensión eléctrica', 'Multímetro'],
        ],
    ];
}

function estandar5_tareas_alto_riesgo(): array
{
    return [
        'Trabajo en alturas',
        'Trabajo en excavaciones',
        'Trabajo en caliente',
        'Trabajo con energías peligrosas',
        'Trabajo con productos químicos',
        'Trabajo en espacios confinados',
        'Conductor de vehículo liviano',
        'Operario de maquinaria amarilla',
        'Conductor de vehículo pesado',
    ];
}

function estandar5_centros_medicos_rows(PDO $conn, int $empresa_id): array
{
    if ($empresa_id <= 0) {
        return [];
    }
    $stmt = $conn->prepare("
        SELECT *
        FROM estandar5_centros_medicos
        WHERE empresa_id = ? AND estado = 'activo'
        ORDER BY nombre ASC
    ");
    $stmt->execute([$empresa_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function estandar5_custodia_historias_rows(PDO $conn, int $empresa_id): array
{
    if ($empresa_id <= 0) {
        return [];
    }
    $stmt = $conn->prepare("
        SELECT h.*, c.nombre AS centro_nombre, c.nit AS centro_nit, c.direccion_principal,
               c.telefono, c.correo
        FROM estandar5_historia_clinica_custodias h
        INNER JOIN estandar5_centros_medicos c ON c.id = h.centro_medico_id
        WHERE h.empresa_id = ?
        ORDER BY h.creado_en DESC, h.id DESC
    ");
    $stmt->execute([$empresa_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function estandar5_custodia_status(?string $fecha_emision, ?string $fecha_renovacion): array
{
    if (!$fecha_renovacion && $fecha_emision) {
        $emision = DateTimeImmutable::createFromFormat('Y-m-d', substr($fecha_emision, 0, 10));
        $fecha_renovacion = $emision ? $emision->modify('+1 year')->format('Y-m-d') : null;
    }
    if (!$fecha_renovacion) {
        return ['estado' => 'sin-fecha', 'texto' => 'Sin fecha base', 'dias' => null, 'fecha' => null];
    }

    $target = DateTimeImmutable::createFromFormat('Y-m-d', substr($fecha_renovacion, 0, 10));
    if (!$target) {
        return ['estado' => 'sin-fecha', 'texto' => 'Fecha inválida', 'dias' => null, 'fecha' => null];
    }

    $today = new DateTimeImmutable('today');
    $days = (int)$today->diff($target)->format('%r%a');
    if ($days < 0) {
        return ['estado' => 'vencido', 'texto' => 'Renovación vencida hace ' . abs($days) . ' día(s)', 'dias' => $days, 'fecha' => $target->format('Y-m-d')];
    }
    if ($days <= 15) {
        return ['estado' => 'critico', 'texto' => 'Solicitar renovación en ' . $days . ' día(s)', 'dias' => $days, 'fecha' => $target->format('Y-m-d')];
    }
    return ['estado' => 'vigente', 'texto' => 'Vigente', 'dias' => $days, 'fecha' => $target->format('Y-m-d')];
}

function estandar5_custodia_alertas(array $custodias): array
{
    $alertas = [];
    foreach ($custodias as $custodia) {
        $status = estandar5_custodia_status($custodia['fecha_emision'] ?? null, $custodia['fecha_renovacion'] ?? null);
        if (in_array($status['estado'], ['vencido', 'critico', 'sin-fecha'], true)) {
            $alertas[] = ['custodia' => $custodia, 'status' => $status];
        }
    }
    usort($alertas, fn($a, $b) => ($a['status']['dias'] ?? 999999) <=> ($b['status']['dias'] ?? 999999));
    return $alertas;
}

function estandar5_restriccion_pve_programas(): array
{
    return [
        'Prevención DME',
        'Prevención hipoacusia neurosensorial',
        'Cuidado respiratorio',
        'Estilos de vida saludable',
        'Factores de riesgo psicosocial',
    ];
}

function estandar5_restriccion_tipos(): array
{
    return [
        'No tiene Restricción',
        'Física (Levantamiento, Movimientos Repetitivos)',
        'Trabajo en Alturas',
        'Espacios Confinados',
        'Emocionales o Psicológicos',
    ];
}

function estandar5_restriccion_estados(): array
{
    return [
        'No presenta Gestión - Se reprograma',
        'Cita Programada',
        'Ya tuvo Cita y se remite a Especialista',
        'Cita programada con Especialista',
        'Ya tuvo Cita con Especialista',
        'Se encuentra en Tratamiento',
        'Cerrado',
    ];
}

function estandar5_restricciones_rows(PDO $conn, int $empresa_id): array
{
    if ($empresa_id <= 0) {
        return [];
    }
    $stmt = $conn->prepare("
        SELECT r.*, u.nombre, u.apellido, u.cedula, u.email
        FROM estandar5_restricciones_recomendaciones r
        INNER JOIN usuarios u ON u.id = r.trabajador_id
        WHERE r.empresa_id = ?
        ORDER BY r.actualizado_en DESC, r.id DESC
        LIMIT 80
    ");
    $stmt->execute([$empresa_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function estandar5_perfiles_cargo_rows(PDO $conn, int $empresa_id): array
{
    if ($empresa_id <= 0) {
        return [];
    }
    $stmt = $conn->prepare("
        SELECT p.*, c.nombre AS centro_nombre
        FROM estandar5_perfiles_cargo p
        LEFT JOIN estandar5_centros_medicos c ON c.id = p.centro_medico_id
        WHERE p.empresa_id = ? AND p.estado = 'activo'
        ORDER BY p.actualizado_en DESC, p.id DESC
    ");
    $stmt->execute([$empresa_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function estandar5_procesos_perfil_rows(PDO $conn, int $empresa_id): array
{
    if ($empresa_id <= 0) {
        return [];
    }
    $stmt = $conn->prepare("
        SELECT nombre
        FROM estandar5_procesos_perfil
        WHERE empresa_id = ?
        ORDER BY nombre ASC
    ");
    $stmt->execute([$empresa_id]);
    return array_map(fn($row) => $row['nombre'], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function estandar5_perfil_cargo_find(PDO $conn, int $empresa_id, int $perfil_id): ?array
{
    $stmt = $conn->prepare("
        SELECT p.*, c.nombre AS centro_nombre, c.nit AS centro_nit, c.direccion_principal AS centro_direccion,
               c.telefono AS centro_telefono, c.correo AS centro_correo
        FROM estandar5_perfiles_cargo p
        LEFT JOIN estandar5_centros_medicos c ON c.id = p.centro_medico_id
        WHERE p.id = ? AND p.empresa_id = ? AND p.estado = 'activo'
        LIMIT 1
    ");
    $stmt->execute([$perfil_id, $empresa_id]);
    $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
    return $perfil ?: null;
}

function estandar5_trabajadores_medicos_rows(PDO $conn, int $empresa_id): array
{
    if ($empresa_id <= 0) {
        return [];
    }
    $stmt = $conn->prepare("
        SELECT
            u.id, u.nombre, u.apellido, u.cedula, u.email, u.activo,
            g.nombre AS grupo_nombre,
            e.tipo_personal,
            em.estado AS ultima_eval_estado,
            em.tipo_examen AS ultimo_tipo_examen,
            em.creado_en AS ultima_eval_fecha
        FROM usuarios u
        LEFT JOIN grupos_personal g ON g.id = u.grupo_id
        LEFT JOIN encuesta_sociodemografica e ON e.usuario_id = u.id
        LEFT JOIN (
            SELECT x.*
            FROM estandar5_evaluaciones_medicas x
            INNER JOIN (
                SELECT trabajador_id, MAX(id) AS max_id
                FROM estandar5_evaluaciones_medicas
                WHERE empresa_id = ?
                GROUP BY trabajador_id
            ) ult ON ult.max_id = x.id
        ) em ON em.trabajador_id = u.id
        WHERE u.empresa_id = ? AND u.rol = 'trabajador'
        ORDER BY u.nombre ASC, u.apellido ASC
    ");
    $stmt->execute([$empresa_id, $empresa_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function estandar5_eval_cargo_trabajador(array $trabajador): string
{
    $cargo = trim((string)($trabajador['grupo_nombre'] ?? ''));
    if ($cargo === '') {
        $cargo = trim((string)($trabajador['tipo_personal'] ?? ''));
    }
    return $cargo === '' ? 'Sin cargo registrado' : $cargo;
}

function estandar5_evaluaciones_medicas_rows(PDO $conn, int $empresa_id): array
{
    if ($empresa_id <= 0) {
        return [];
    }
    $stmt = $conn->prepare("
        SELECT em.*, u.nombre, u.apellido, u.cedula, u.email, p.nombre_cargo, c.nombre AS centro_nombre
        FROM estandar5_evaluaciones_medicas em
        INNER JOIN usuarios u ON u.id = em.trabajador_id
        INNER JOIN estandar5_perfiles_cargo p ON p.id = em.perfil_cargo_id
        INNER JOIN estandar5_centros_medicos c ON c.id = em.centro_medico_id
        WHERE em.empresa_id = ?
        ORDER BY em.creado_en DESC
        LIMIT 12
    ");
    $stmt->execute([$empresa_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function estandar5_evaluaciones_soportes_rows(PDO $conn, int $empresa_id): array
{
    if ($empresa_id <= 0) {
        return [];
    }
    $stmt = $conn->prepare("
        SELECT s.*, p.nombre_cargo, p.tipo_operacion, c.nombre AS centro_registrado
        FROM estandar5_evaluaciones_medicas_soportes s
        LEFT JOIN estandar5_perfiles_cargo p ON p.id = s.perfil_cargo_id
        LEFT JOIN estandar5_centros_medicos c ON c.id = s.centro_medico_id
        WHERE s.empresa_id = ?
        ORDER BY s.creado_en DESC
        LIMIT 40
    ");
    $stmt->execute([$empresa_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function estandar5_eval_alert_status(?string $date): ?array
{
    if (!$date) {
        return null;
    }
    $target = DateTimeImmutable::createFromFormat('Y-m-d', substr($date, 0, 10));
    if (!$target) {
        return null;
    }
    $today = new DateTimeImmutable('today');
    $days = (int)$today->diff($target)->format('%r%a');
    if ($days < 0) {
        return ['estado' => 'vencido', 'dias' => $days, 'texto' => 'Vencido hace ' . abs($days) . ' dia(s)'];
    }
    if ($days <= 30) {
        return ['estado' => 'critico', 'dias' => $days, 'texto' => 'Vence en ' . $days . ' dia(s)'];
    }
    if ($days <= 90) {
        return ['estado' => 'alerta', 'dias' => $days, 'texto' => 'Programar en ' . $days . ' dia(s)'];
    }
    return ['estado' => 'vigente', 'dias' => $days, 'texto' => 'Vigente'];
}

function estandar5_eval_alertas(array $soportes): array
{
    $alertas = [];
    $campos = [
        'fecha_vencimiento' => 'Examen medico laboral',
    ];
    foreach ($soportes as $soporte) {
        foreach ($campos as $campo => $tipo) {
            $status = estandar5_eval_alert_status($soporte[$campo] ?? null);
            if (!$status || $status['estado'] === 'vigente') {
                continue;
            }
            $alertas[] = [
                'trabajador' => $soporte['nombre_trabajador'] ?? '',
                'cedula' => $soporte['cedula'] ?? '',
                'cargo' => $soporte['cargo'] ?: ($soporte['nombre_cargo'] ?? ''),
                'tipo' => $tipo,
                'fecha' => $soporte[$campo],
                'estado' => $status['estado'],
                'dias' => $status['dias'],
                'texto' => $status['texto'],
            ];
        }
    }
    usort($alertas, fn($a, $b) => $a['dias'] <=> $b['dias']);
    return $alertas;
}

function estandar5_perfil_export_html(array $perfil, string $empresa_nombre): string
{
    $tareas = estandar5_decode_list($perfil['tareas_json'] ?? '');
    $herramientas = estandar5_decode_list($perfil['herramientas_json'] ?? '');
    $tareas_alto_riesgo = estandar5_decode_list($perfil['tareas_alto_riesgo_json'] ?? '');
    $html = '<!doctype html><html lang="es"><head><meta charset="UTF-8"><style>
        body{font-family:DejaVu Sans,Arial,sans-serif;color:#1f2d3d;font-size:12px}
        h1{color:#1e3a8a;font-size:20px;margin:0 0 4px} h2{color:#1e3a8a;font-size:15px;margin:20px 0 8px}
        .meta{color:#64748b;margin-bottom:16px}.box{border:1px solid #dbe3ec;border-radius:6px;padding:10px;margin-bottom:10px}
        table{width:100%;border-collapse:collapse}td,th{border:1px solid #dbe3ec;padding:7px;text-align:left;vertical-align:top}
        th{background:#f8fafc;color:#334155}.pill{display:inline-block;background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;border-radius:20px;padding:4px 8px;margin:2px}
    </style></head><body>';
    $html .= '<h1>Perfil de cargo - ' . htmlspecialchars($perfil['nombre_cargo']) . '</h1>';
    $html .= '<div class="meta">Empresa: ' . htmlspecialchars($empresa_nombre) . ' | Generado: ' . date('d/m/Y H:i') . '</div>';
    $html .= '<table><tr><th>Proceso</th><td>' . htmlspecialchars($perfil['tipo_proceso']) . '</td></tr>';
    $html .= '<tr><th>Tipo de operación</th><td>' . htmlspecialchars($perfil['tipo_operacion'] ?? 'Mixto') . '</td></tr>';
    $html .= '<tr><th>Jefe inmediato</th><td>' . htmlspecialchars($perfil['jefe_inmediato']) . '</td></tr>';
    $html .= '<tr><th>Centro médico autorizado</th><td>' . htmlspecialchars($perfil['centro_nombre'] ?: 'No asignado') . '</td></tr></table>';
    $html .= '<h2>Tareas del cargo</h2><ol>';
    foreach ($tareas as $tarea) {
        $html .= '<li>' . htmlspecialchars($tarea) . '</li>';
    }
    $html .= '</ol><h2>Herramientas eléctricas autorizadas</h2><div class="box">';
    if ($herramientas) {
        foreach ($herramientas as $herramienta) {
            $html .= '<span class="pill">' . htmlspecialchars($herramienta) . '</span>';
        }
    } else {
        $html .= 'Sin herramientas registradas.';
    }
    $html .= '</div><h2>Tareas de alto riesgo a las que se expone</h2><div class="box">';
    if ($tareas_alto_riesgo) {
        foreach ($tareas_alto_riesgo as $riesgo) {
            $html .= '<span class="pill">' . htmlspecialchars($riesgo) . '</span>';
        }
    } else {
        $html .= 'Sin tareas de alto riesgo registradas.';
    }
    $html .= '</div><h2>Información para el médico</h2><p>Este perfil consolida tareas, herramientas autorizadas y exposición a tareas de alto riesgo como insumo para orientar evaluaciones médicas ocupacionales.</p>';
    return $html . '</body></html>';
}

$preguntas_socio = estandar5_socio_preguntas();
$pregunta_filtro = $_GET['pregunta'] ?? '';
$pregunta_filtro = isset($preguntas_socio[$pregunta_filtro]) ? $pregunta_filtro : '';
$buscar_filtro = trim((string)($_GET['buscar'] ?? ''));
$vista_resumen_estandar5 = $modulo_actual === '';
$socio_rows = ($modulo_actual === 'sociodemografica' || $vista_resumen_estandar5) ? estandar5_socio_rows($conn, $empresa_id) : [];
$socio_stats = ($modulo_actual === 'sociodemografica' || $vista_resumen_estandar5) ? estandar5_socio_stats($socio_rows, $preguntas_socio) : [];
$pyp_categorias = estandar5_pyp_categorias();
$pyp_total_trabajadores = ($modulo_actual === 'promocion-prevencion' || $vista_resumen_estandar5) ? estandar5_pyp_total_trabajadores($conn, $empresa_id) : 0;
$pyp_rows = ($modulo_actual === 'promocion-prevencion' || $vista_resumen_estandar5) ? estandar5_pyp_rows($conn, $empresa_id) : [];
$pyp_resumen = ($modulo_actual === 'promocion-prevencion' || $vista_resumen_estandar5) ? estandar5_pyp_resumen($pyp_rows, $pyp_categorias, $pyp_total_trabajadores) : [];
$herramientas_grupos = estandar5_herramientas_por_grupo();
$tareas_alto_riesgo_base = estandar5_tareas_alto_riesgo();
$centros_medicos = ($modulo_actual === 'perfiles-cargo' || $vista_resumen_estandar5) ? estandar5_centros_medicos_rows($conn, $empresa_id) : [];
$perfiles_cargo = ($modulo_actual === 'perfiles-cargo' || $vista_resumen_estandar5) ? estandar5_perfiles_cargo_rows($conn, $empresa_id) : [];
$procesos_perfil = $modulo_actual === 'perfiles-cargo' ? estandar5_procesos_perfil_rows($conn, $empresa_id) : [];
$eval_centros_medicos = ($modulo_actual === 'evaluaciones-medicas' || $vista_resumen_estandar5) ? estandar5_centros_medicos_rows($conn, $empresa_id) : [];
$eval_perfiles_cargo = ($modulo_actual === 'evaluaciones-medicas' || $vista_resumen_estandar5) ? estandar5_perfiles_cargo_rows($conn, $empresa_id) : [];
$eval_trabajadores = ($modulo_actual === 'evaluaciones-medicas' || $vista_resumen_estandar5) ? estandar5_trabajadores_medicos_rows($conn, $empresa_id) : [];
$eval_solicitudes = ($modulo_actual === 'evaluaciones-medicas' || $vista_resumen_estandar5) ? estandar5_evaluaciones_medicas_rows($conn, $empresa_id) : [];
$eval_soportes = ($modulo_actual === 'evaluaciones-medicas' || $vista_resumen_estandar5) ? estandar5_evaluaciones_soportes_rows($conn, $empresa_id) : [];
$eval_alertas = ($modulo_actual === 'evaluaciones-medicas' || $vista_resumen_estandar5) ? estandar5_eval_alertas($eval_soportes) : [];
$hist_centros_medicos = ($modulo_actual === 'historias-clinicas' || $vista_resumen_estandar5) ? estandar5_centros_medicos_rows($conn, $empresa_id) : [];
$hist_custodias = ($modulo_actual === 'historias-clinicas' || $vista_resumen_estandar5) ? estandar5_custodia_historias_rows($conn, $empresa_id) : [];
$restriccion_trabajadores = $modulo_actual === 'restricciones' ? estandar5_trabajadores_medicos_rows($conn, $empresa_id) : [];
$restriccion_rows = ($modulo_actual === 'restricciones' || $vista_resumen_estandar5) ? estandar5_restricciones_rows($conn, $empresa_id) : [];
$restriccion_pve_programas = estandar5_restriccion_pve_programas();
$restriccion_tipos = estandar5_restriccion_tipos();
$restriccion_estados = estandar5_restriccion_estados();
$eval_soportes_por_trabajador = [];
if ($modulo_actual === 'evaluaciones-medicas' || $vista_resumen_estandar5) {
    foreach ($eval_soportes as $soporte) {
        $trabajador_key = (int)($soporte['trabajador_id'] ?? 0);
        if ($trabajador_key > 0 && !isset($eval_soportes_por_trabajador[$trabajador_key])) {
            $eval_soportes_por_trabajador[$trabajador_key] = $soporte;
        }
    }
}

if ($modulo_actual === 'perfiles-cargo' && isset($_GET['export_perfil'])) {
    $perfil = estandar5_perfil_cargo_find($conn, $empresa_id, (int)$_GET['export_perfil']);
    if (!$perfil) {
        header('Location: estandar5.php?modulo=perfiles-cargo&msg=' . urlencode('No se encontró el perfil solicitado.') . '&tipo=error');
        exit;
    }
    require_once 'vendor/autoload.php';
    $options = new Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml(estandar5_perfil_export_html($perfil, $empresa_nombre), 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $filename = 'perfil_cargo_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $perfil['nombre_cargo']) . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}

if ($modulo_actual === 'sociodemografica' && isset($_GET['export'])) {
    $export = $_GET['export'];
    if ($export === 'excel') {
        $filename = 'perfil_sociodemografico_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        echo estandar5_socio_export_html($socio_stats, $preguntas_socio, $empresa_nombre, $pregunta_filtro ?: null, $buscar_filtro);
        exit;
    }
    if ($export === 'pdf') {
        require_once 'vendor/autoload.php';
        $options = new Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf\Dompdf($options);
        $dompdf->loadHtml(estandar5_socio_export_html($socio_stats, $preguntas_socio, $empresa_nombre, $pregunta_filtro ?: null, $buscar_filtro), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('perfil_sociodemografico_' . date('Ymd_His') . '.pdf', ['Attachment' => true]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estándar 5 | Evaluaciones médicas ocupacionales</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/estandar5-perfiles.css">
    <style>
        :root { --primary:#ff8a1f; --primary2:#ff7a00; --blue-dark:#1e3a8a; --bg1:#edf4fb; --bg2:#f7f9fc; --card:#fff; --text:#1f2d3d; --muted:#64748b; --border:#dbe3ec; --green:#16a34a; --amber:#d97706; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; font-family:Inter,sans-serif; background:linear-gradient(180deg,var(--bg1),var(--bg2)); color:var(--text); font-size:.82rem; }
        .main-wrapper { margin-left:260px; width:calc(100% - 260px); min-height:100vh; }
        .content-area { width:100%; padding:30px clamp(24px,2.5vw,44px) 60px; }
        .page-head { display:flex; align-items:center; justify-content:space-between; gap:18px; margin-bottom:18px; }
        .head-copy { display:flex; align-items:center; gap:13px; min-width:0; }
        .head-icon { width:46px; height:46px; border-radius:10px; display:grid; place-items:center; color:var(--primary); background:#fff3e8; border:1px solid #fed7aa; font-size:1.05rem; flex:none; }
        .head-copy h1 { margin:0; color:var(--blue-dark); font-size:1.18rem; line-height:1.2; }
        .head-copy p { margin:5px 0 0; color:var(--muted); font-size:.76rem; line-height:1.35; }
        .status-pill { display:inline-flex; align-items:center; gap:7px; height:34px; padding:0 12px; border-radius:999px; border:1px solid #fed7aa; background:#fff7ed; color:#c2410c; font-weight:800; font-size:.68rem; text-transform:uppercase; white-space:nowrap; }
        .intro-panel { position:relative; overflow:hidden; background:#fff; border:1px solid var(--border); border-left:4px solid var(--primary2); border-radius:10px; padding:18px; margin-bottom:16px; box-shadow:0 8px 24px rgba(15,23,42,.05); }
        .intro-panel::after { content:""; position:absolute; right:-52px; top:-74px; width:180px; height:180px; border-radius:50%; background:#fff3e8; opacity:.75; }
        .intro-content { position:relative; z-index:1; display:grid; grid-template-columns:minmax(250px,.85fr) minmax(300px,1.15fr); gap:18px; align-items:center; }
        .intro-kicker { color:var(--primary2); font-size:.68rem; font-weight:850; text-transform:uppercase; letter-spacing:.02em; }
        .intro-content h2 { margin:5px 0 8px; color:var(--blue-dark); font-size:1.06rem; }
        .intro-content p { margin:0; color:var(--muted); line-height:1.45; font-size:.76rem; }
        .module-tabs { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
        .module-tab { display:flex; align-items:flex-start; gap:8px; min-width:0; padding:10px; border:1px solid var(--border); border-radius:8px; background:#f8fafc; color:#334155; text-decoration:none; transition:border-color .2s ease, background .2s ease, transform .2s ease; }
        .module-tab:hover { border-color:#fdba74; background:#fff8f3; transform:translateY(-1px); }
        .module-tab.active { border-color:#fdba74; background:#fff3e8; color:#c2410c; }
        .module-tab strong { display:block; color:var(--blue-dark); font-size:.67rem; margin-bottom:2px; }
        .module-tab span { display:block; color:inherit; font-size:.64rem; line-height:1.25; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .e5-home-hero { position:relative; overflow:hidden; display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:22px; margin-bottom:14px; padding:22px; border:1px solid #fed7aa; border-left:4px solid var(--primary2); border-radius:12px; background:linear-gradient(135deg,#fff 0%,#fff8f1 100%); box-shadow:0 10px 28px rgba(15,23,42,.055); }
        .e5-home-watermark { position:absolute; right:24px; top:50%; color:var(--primary2); font-size:8.5rem; opacity:.045; transform:translateY(-50%) rotate(-8deg); pointer-events:none; }
        .e5-home-copy { position:relative; z-index:1; }
        .e5-home-kicker { display:inline-flex; align-items:center; gap:6px; margin-bottom:7px; color:#c2410c; font-size:.64rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; }
        .e5-home-copy h2 { margin:0; max-width:650px; color:var(--blue-dark); font-size:1.16rem; line-height:1.25; }
        .e5-home-copy p { margin:7px 0 0; max-width:720px; color:#64748b; font-size:.73rem; line-height:1.5; }
        .e5-home-badges { position:relative; z-index:1; display:flex; align-items:stretch; gap:8px; }
        .e5-home-badge { min-width:108px; padding:10px 12px; border:1px solid rgba(255,122,0,.18); border-radius:9px; background:rgba(255,255,255,.82); backdrop-filter:blur(3px); }
        .e5-home-badge span { display:block; color:#64748b; font-size:.54rem; font-weight:850; text-transform:uppercase; }
        .e5-home-badge strong { display:block; margin-top:5px; color:var(--blue-dark); font-size:.86rem; line-height:1.15; }
        .e5-module-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
        .e5-module-card { --module-color:#ea580c; --module-soft:#fff3e8; position:relative; overflow:hidden; display:flex; flex-direction:column; min-height:228px; padding:15px; border:1px solid var(--border); border-radius:11px; background:#fff; color:inherit; text-decoration:none; box-shadow:0 8px 24px rgba(15,23,42,.04); transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease; }
        .e5-module-card:hover { transform:translateY(-3px); border-color:var(--module-color); box-shadow:0 16px 34px rgba(15,23,42,.09); }
        .e5-module-watermark { position:absolute; right:-12px; top:24px; color:var(--module-color); font-size:6.2rem; opacity:.045; transform:rotate(-10deg); pointer-events:none; transition:opacity .22s ease,transform .22s ease; }
        .e5-module-card:hover .e5-module-watermark { opacity:.08; transform:rotate(-6deg) scale(1.03); }
        .e5-module-head { position:relative; z-index:1; display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
        .e5-module-code { display:inline-flex; min-height:23px; align-items:center; padding:0 7px; border-radius:999px; background:var(--module-soft); color:var(--module-color); font-size:.58rem; font-weight:900; }
        .e5-module-icon { width:35px; height:35px; border-radius:9px; display:grid; place-items:center; background:var(--module-soft); color:var(--module-color); flex:none; }
        .e5-module-card h3 { position:relative; z-index:1; margin:11px 0 4px; max-width:88%; color:var(--blue-dark); font-size:.84rem; line-height:1.3; }
        .e5-module-card > p { position:relative; z-index:1; margin:0; color:#64748b; font-size:.65rem; line-height:1.4; }
        .e5-module-metrics { position:relative; z-index:1; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:7px; margin-top:12px; }
        .e5-module-metric { min-width:0; padding:8px; border:1px solid #eef2f7; border-radius:8px; background:#f8fafc; }
        .e5-module-metric span { display:block; color:#64748b; font-size:.51rem; font-weight:850; text-transform:uppercase; }
        .e5-module-metric strong { display:block; margin-top:4px; color:var(--blue-dark); font-size:.92rem; line-height:1; }
        .e5-module-progress { position:relative; z-index:1; margin-top:9px; }
        .e5-module-progress-copy { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:5px; color:#64748b; font-size:.55rem; font-weight:750; }
        .e5-module-progress-copy strong { color:var(--module-color); }
        .e5-module-track { height:6px; overflow:hidden; border-radius:999px; background:#e2e8f0; }
        .e5-module-fill { height:100%; border-radius:inherit; background:var(--module-color); }
        .e5-module-link { position:relative; z-index:1; display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:auto; padding-top:11px; color:var(--module-color); font-size:.62rem; font-weight:900; }
        .e5-module-link i { transition:transform .2s ease; }
        .e5-module-card:hover .e5-module-link i { transform:translateX(3px); }
        .rep-e5-hero { background:#fff; border:1px solid var(--border); border-left:4px solid var(--primary2); border-radius:10px; padding:18px; margin-bottom:16px; box-shadow:0 8px 24px rgba(15,23,42,.05); }
        .rep-e5-hero h2 { margin:0 0 6px; color:var(--blue-dark); font-size:1.1rem; }
        .rep-e5-hero p { margin:0; color:#64748b; line-height:1.5; font-size:.82rem; }
        .rep-e5-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
        .rep-e5-card { background:#fff; border:1px solid var(--border); border-radius:10px; padding:14px; box-shadow:0 8px 24px rgba(15,23,42,.04); min-width:0; }
        .rep-e5-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:12px; }
        .rep-e5-card h3 { margin:0; color:var(--blue-dark); font-size:.98rem; }
        .rep-e5-card p { margin:4px 0 0; color:#64748b; font-size:.8rem; line-height:1.42; }
        .rep-e5-icon { width:38px; height:38px; border-radius:10px; display:grid; place-items:center; background:#fff3e8; color:var(--primary2); flex:0 0 auto; }
        .rep-e5-metrics { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
        .rep-e5-metric { border:1px solid #e2e8f0; background:#f8fafc; border-radius:9px; padding:10px; min-height:70px; }
        .rep-e5-metric span { display:block; color:#64748b; font-size:.58rem; font-weight:850; text-transform:uppercase; letter-spacing:.03em; }
        .rep-e5-metric strong { display:block; margin-top:6px; color:var(--blue-dark); font-size:1.15rem; }
        .rep-e5-bars { display:flex; flex-direction:column; gap:8px; margin-top:12px; }
        .rep-e5-bar { display:grid; grid-template-columns:120px 1fr auto; gap:8px; align-items:center; font-size:.74rem; color:#334155; font-weight:750; }
        .rep-e5-track { height:9px; border-radius:999px; background:#e2e8f0; overflow:hidden; }
        .rep-e5-fill { height:100%; border-radius:999px; background:var(--bar,#ff8a1f); }
        .rep-e5-wide { grid-column:span 2; }
        .rep-hide { display:none !important; }
        .rep-e5-list { margin-top:12px; display:grid; gap:8px; }
        .rep-e5-row { border:1px solid #e2e8f0; background:#f8fafc; border-radius:9px; padding:10px; display:grid; gap:4px; }
        .rep-e5-row strong { color:var(--blue-dark); font-size:.82rem; }
        .rep-e5-row span { color:#64748b; font-size:.72rem; line-height:1.35; }
        .workspace-grid { display:grid; grid-template-columns:minmax(280px,.78fr) minmax(360px,1.22fr); gap:14px; align-items:start; }
        .module-card, .detail-card { background:#fff; border:1px solid var(--border); border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,.045); }
        .module-card { overflow:hidden; }
        .module-card-head { background:linear-gradient(135deg,#172554,#1e3a8a); color:#fff; padding:16px; min-height:145px; display:flex; flex-direction:column; justify-content:space-between; gap:18px; }
        .module-code { align-self:flex-start; display:inline-flex; align-items:center; gap:7px; padding:6px 9px; border-radius:7px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.16); font-size:.68rem; font-weight:850; }
        .module-card-head h2 { margin:0; font-size:1.02rem; line-height:1.25; }
        .module-card-body { padding:15px; display:grid; gap:12px; }
        .module-card-body p { margin:0; color:var(--muted); line-height:1.45; }
        .state-row { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
        .state-box { border:1px solid #e2e8f0; background:#f8fafc; border-radius:8px; padding:10px; }
        .state-box span { display:block; color:#64748b; font-size:.58rem; font-weight:850; text-transform:uppercase; }
        .state-box strong { display:block; margin-top:4px; color:var(--blue-dark); font-size:.92rem; }
        .detail-card { padding:16px; }
        .detail-card h3 { margin:0; color:var(--blue-dark); font-size:.95rem; }
        .detail-card p { margin:5px 0 14px; color:var(--muted); line-height:1.45; }
        .detail-list { display:grid; gap:8px; margin:0; padding:0; list-style:none; }
        .detail-list li { display:flex; align-items:flex-start; gap:9px; padding:11px; border:1px solid #eef2f7; border-radius:8px; background:#fbfdff; color:#475569; line-height:1.35; }
        .detail-list i { color:var(--primary2); margin-top:2px; }
        .notice { margin-top:14px; display:flex; align-items:flex-start; gap:9px; border:1px solid #fed7aa; background:#fff7ed; color:#9a3412; border-radius:8px; padding:11px; font-size:.72rem; line-height:1.4; }
        .socio-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; background:#fff; border:1px solid var(--border); border-radius:10px; padding:12px; box-shadow:0 8px 24px rgba(15,23,42,.045); }
        .socio-filters { display:flex; align-items:center; gap:8px; flex:1; min-width:0; }
        .socio-field { height:38px; border:1px solid var(--border); border-radius:8px; background:#f8fafc; color:#334155; padding:0 11px; font:inherit; font-size:.76rem; outline:0; min-width:180px; }
        .socio-field:focus { border-color:#fdba74; background:#fff; box-shadow:0 0 0 3px rgba(255,138,31,.12); }
        .socio-search { flex:1; min-width:180px; }
        .socio-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
        .socio-btn { height:38px; border:1px solid var(--border); background:#fff; color:#334155; border-radius:8px; padding:0 12px; display:inline-flex; align-items:center; gap:7px; text-decoration:none; font-weight:800; font-size:.72rem; white-space:nowrap; cursor:pointer; }
        .socio-btn:hover { border-color:#fdba74; color:var(--primary2); background:#fff8f3; }
        .socio-btn.primary { background:var(--primary2); border-color:var(--primary2); color:#fff; }
        .socio-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:14px; }
        .socio-kpi { --kpi-color:#ea580c; --kpi-soft:#fff3e8; background:#fff; border:1px solid var(--border); border-radius:10px; padding:13px; min-height:116px; box-shadow:0 8px 24px rgba(15,23,42,.04); position:relative; overflow:hidden; transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease; }
        .socio-kpi:hover { transform:translateY(-2px); border-color:#cbd5e1; box-shadow:0 13px 30px rgba(15,23,42,.075); }
        .socio-kpi.kpi-blue { --kpi-color:#2563eb; --kpi-soft:#eff6ff; }
        .socio-kpi.kpi-green { --kpi-color:#059669; --kpi-soft:#ecfdf5; }
        .socio-kpi.kpi-violet { --kpi-color:#7c3aed; --kpi-soft:#f5f3ff; }
        .socio-kpi-watermark { position:absolute; right:-10px; bottom:-20px; color:var(--kpi-color); font-size:4.8rem; line-height:1; opacity:.055; transform:rotate(-10deg); pointer-events:none; transition:transform .25s ease,opacity .25s ease; }
        .socio-kpi:hover .socio-kpi-watermark { opacity:.085; transform:rotate(-6deg) scale(1.04); }
        .socio-kpi-top { display:flex; align-items:center; justify-content:space-between; gap:12px; position:relative; z-index:1; }
        .socio-kpi-icon { width:35px; height:35px; border-radius:9px; display:grid; place-items:center; color:var(--kpi-color); background:var(--kpi-soft); font-size:.92rem; }
        .socio-kpi-label { display:block; margin-top:9px; color:#475569; font-size:.62rem; font-weight:850; text-transform:uppercase; position:relative; z-index:1; }
        .socio-kpi strong { display:block; color:var(--kpi-color); font-size:1.45rem; line-height:1; position:relative; z-index:1; }
        .socio-kpi strong.socio-kpi-value-text { max-width:118px; font-size:.93rem; line-height:1.1; text-align:right; }
        .socio-kpi small { display:block; margin-top:5px; max-width:86%; color:#64748b; line-height:1.35; position:relative; z-index:1; }
        .socio-role-note { display:flex; align-items:flex-start; gap:11px; margin-bottom:14px; padding:13px 15px; border:1px solid #bfdbfe; border-left:4px solid #2563eb; border-radius:10px; background:#eff6ff; color:#1e3a8a; box-shadow:0 8px 24px rgba(37,99,235,.06); }
        .socio-role-note i { margin-top:2px; color:#2563eb; font-size:1rem; }
        .socio-role-note strong { display:block; margin-bottom:3px; font-size:.78rem; }
        .socio-role-note span { color:#475569; font-size:.7rem; line-height:1.45; }
        .socio-results-badge { display:inline-flex; align-items:center; gap:6px; min-height:30px; padding:0 9px; border-radius:999px; background:#f1f5f9; color:#475569; font-size:.62rem; font-weight:850; white-space:nowrap; }
        .socio-grid { display:grid; grid-template-columns:1fr; gap:9px; margin-bottom:14px; }
        .socio-question { background:#fff; border:1px solid var(--border); border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,.04); min-width:0; overflow:hidden; position:relative; transition:border-color .22s ease,box-shadow .22s ease,transform .22s ease; }
        .socio-question:hover { border-color:#cbd5e1; box-shadow:0 12px 28px rgba(15,23,42,.07); transform:translateY(-1px); }
        .socio-question.is-open { border-color:#fdba74; box-shadow:0 14px 32px rgba(255,122,0,.1); }
        .socio-question-watermark { position:absolute; right:-11px; top:-16px; color:var(--primary2); font-size:5.4rem; line-height:1; opacity:.04; transform:rotate(-11deg); pointer-events:none; transition:opacity .25s ease,transform .25s ease; }
        .socio-question:hover .socio-question-watermark,.socio-question.is-open .socio-question-watermark { opacity:.07; transform:rotate(-7deg) scale(1.03); }
        .socio-question-toggle { width:100%; min-height:96px; border:0; background:transparent; padding:12px 14px; display:grid; grid-template-columns:minmax(190px,1fr) minmax(170px,.85fr) minmax(220px,1.15fr) auto; grid-template-areas:"title insight progress actions"; align-items:center; gap:16px; text-align:left; font:inherit; cursor:pointer; position:relative; z-index:1; }
        .socio-question-title { grid-area:title; display:flex; align-items:center; gap:11px; min-width:0; }
        .socio-question-title i { width:38px; height:38px; border-radius:9px; display:grid; place-items:center; background:#fff3e8; color:var(--primary2); flex:none; }
        .socio-question-copy { min-width:0; }
        .socio-question-copy strong { display:block; margin:0; color:var(--blue-dark); font-size:.86rem; line-height:1.25; }
        .socio-question-copy small { display:block; margin-top:3px; color:#64748b; font-size:.65rem; }
        .socio-question-insight { grid-area:insight; min-width:0; padding-left:14px; border-left:1px solid #e2e8f0; }
        .socio-question-insight > small { display:block; color:#94a3b8; font-size:.5rem; font-weight:900; text-transform:uppercase; letter-spacing:.03em; }
        .socio-question-insight > strong { display:block; margin-top:4px; color:#172554; font-size:.72rem; line-height:1.25; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .socio-question-insight-meta { display:flex; align-items:center; gap:5px; margin-top:5px; color:#64748b; font-size:.56rem; }
        .socio-question-insight-meta i { color:var(--primary2); }
        .socio-question-progress { grid-area:progress; min-width:0; }
        .socio-question-progress-head { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:6px; }
        .socio-question-progress-head span { color:#64748b; font-size:.52rem; font-weight:850; text-transform:uppercase; }
        .socio-question-progress-head strong { color:var(--primary2); font-size:.9rem; line-height:1; }
        .socio-question-preview-track { display:block; height:7px; overflow:hidden; border-radius:999px; background:#e2e8f0; }
        .socio-question-preview-fill { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#ff8a1f,#1e3a8a); }
        .socio-question-preview-meta { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-top:6px; }
        .socio-question-preview-meta span { display:inline-flex; align-items:center; gap:4px; min-height:20px; padding:0 6px; border-radius:999px; background:#f1f5f9; color:#64748b; font-size:.51rem; font-weight:800; }
        .socio-question-preview-meta i { color:#94a3b8; }
        .socio-question-actions { grid-area:actions; display:flex; align-items:center; gap:8px; flex:none; }
        .socio-expand-label { display:inline-flex; align-items:center; min-height:28px; padding:0 9px; border-radius:999px; background:#fff3e8; color:#c2410c; font-size:.55rem; font-weight:900; white-space:nowrap; }
        .socio-expand-label .when-open { display:none; }
        .socio-question.is-open .socio-expand-label .when-closed { display:none; }
        .socio-question.is-open .socio-expand-label .when-open { display:inline; }
        .socio-dominant { text-align:right; }
        .socio-dominant span { display:block; margin-bottom:3px; color:#94a3b8; font-size:.52rem; font-weight:850; text-transform:uppercase; letter-spacing:.03em; }
        .socio-main-percent { color:var(--primary2); font-size:1.3rem; font-weight:850; line-height:1; white-space:nowrap; }
        .socio-chevron { color:#94a3b8; transition:transform .22s ease,color .22s ease; }
        .socio-question.is-open .socio-chevron { color:var(--primary2); transform:rotate(180deg); }
        .socio-distribution { max-height:0; opacity:0; overflow:hidden; position:relative; z-index:1; transition:max-height .34s ease,opacity .2s ease; }
        .socio-question.is-open .socio-distribution { max-height:12000px; opacity:1; }
        .socio-dominant-answer { margin:0 14px 12px; padding:10px 11px; border:1px solid #fed7aa; border-radius:8px; background:#fff7ed; display:flex; align-items:center; gap:8px; flex-wrap:wrap; color:#9a3412; }
        .socio-dominant-answer span { font-size:.58rem; font-weight:850; text-transform:uppercase; }
        .socio-dominant-answer strong { color:#172554; font-size:.72rem; }
        .socio-dominant-answer small { margin-left:auto; color:#64748b; font-size:.6rem; font-weight:750; }
        .answer-bars { display:grid; gap:9px; padding:0 14px 14px; }
        .answer-row { display:grid; grid-template-columns:minmax(120px,.8fr) minmax(160px,1fr) 54px; gap:9px; align-items:center; color:#475569; font-size:.68rem; }
        .answer-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:700; }
        .answer-track { height:9px; background:#e2e8f0; border-radius:99px; overflow:hidden; }
        .answer-fill { height:100%; background:linear-gradient(90deg,#ff8a1f,#1e3a8a); border-radius:inherit; min-width:2px; }
        .answer-percent { text-align:right; color:#172554; font-weight:850; }
        .socio-detail { margin:0 14px 14px; padding-top:12px; border-top:1px solid #eef2f7; }
        .socio-detail-head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:9px; }
        .socio-detail-head strong { display:flex; align-items:center; gap:7px; color:#172554; font-size:.72rem; }
        .socio-detail-head strong i { color:var(--primary2); }
        .socio-detail-head span { color:#64748b; font-size:.6rem; line-height:1.4; text-align:right; }
        .socio-detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:9px; }
        .socio-detail-item { min-width:0; padding:11px; border:1px solid #e2e8f0; border-radius:9px; background:linear-gradient(145deg,#fff,#f8fafc); }
        .socio-detail-item.is-principal { border-color:#fdba74; background:linear-gradient(145deg,#fffaf5,#fff); box-shadow:0 7px 18px rgba(234,88,12,.07); }
        .socio-detail-badges { display:flex; align-items:center; gap:5px; flex-wrap:wrap; margin-bottom:8px; }
        .socio-detail-badge { display:inline-flex; align-items:center; min-height:21px; padding:0 6px; border-radius:999px; background:#eef2f7; color:#64748b; font-size:.51rem; font-weight:900; text-transform:uppercase; letter-spacing:.02em; }
        .socio-detail-badge.level { background:#eff6ff; color:#1d4ed8; }
        .socio-detail-badge.principal { background:#fff3e8; color:#c2410c; }
        .socio-detail-item-top { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
        .socio-detail-item-name { min-width:0; color:#172554; font-size:.74rem; font-weight:850; line-height:1.3; overflow-wrap:anywhere; }
        .socio-detail-item-percent { color:var(--primary2); font-size:1rem; font-weight:900; white-space:nowrap; }
        .socio-detail-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:6px; margin-top:10px; }
        .socio-detail-stat { min-width:0; padding:7px; border:1px solid #eef2f7; border-radius:7px; background:rgba(248,250,252,.88); }
        .socio-detail-stat span { display:block; color:#94a3b8; font-size:.48rem; font-weight:850; text-transform:uppercase; }
        .socio-detail-stat strong { display:block; margin-top:3px; color:#334155; font-size:.66rem; line-height:1.2; }
        .socio-detail-reading { display:flex; align-items:flex-start; gap:6px; margin-top:8px; color:#64748b; font-size:.57rem; line-height:1.4; }
        .socio-detail-reading i { margin-top:1px; color:var(--primary2); }
        .socio-detail-mini-track { height:5px; overflow:hidden; margin-top:8px; border-radius:999px; background:#e2e8f0; }
        .socio-detail-mini-fill { height:100%; border-radius:inherit; background:linear-gradient(90deg,#ff8a1f,#1e3a8a); }
        .empty-socio { background:#fff; border:1px dashed #cbd5e1; border-radius:10px; padding:34px 18px; text-align:center; color:#64748b; }
        .empty-socio i { display:block; color:#cbd5e1; font-size:1.8rem; margin-bottom:10px; }
        .pyp-hero { background:#fff; border:1px solid var(--border); border-left:4px solid var(--primary2); border-radius:10px; padding:16px; margin-bottom:14px; box-shadow:0 8px 24px rgba(15,23,42,.045); display:grid; grid-template-columns:minmax(260px,1fr) auto; gap:14px; align-items:center; }
        .pyp-hero h2 { margin:0; color:var(--blue-dark); font-size:1rem; }
        .pyp-hero p { margin:5px 0 0; color:var(--muted); line-height:1.45; }
        .pyp-hero-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
        .pyp-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:14px; }
        .pyp-kpi { --pyp-metric:#ea580c; --pyp-soft:#fff3e8; position:relative; overflow:hidden; background:#fff; border:1px solid var(--border); border-radius:10px; padding:13px; min-height:104px; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .pyp-kpi.blue { --pyp-metric:#2563eb; --pyp-soft:#eff6ff; }
        .pyp-kpi.green { --pyp-metric:#059669; --pyp-soft:#ecfdf5; }
        .pyp-kpi.violet { --pyp-metric:#7c3aed; --pyp-soft:#f5f3ff; }
        .pyp-kpi-watermark { position:absolute; right:-8px; bottom:-17px; color:var(--pyp-metric); font-size:4.5rem; opacity:.055; transform:rotate(-10deg); pointer-events:none; }
        .pyp-kpi span { position:relative; z-index:1; display:block; color:#64748b; font-size:.62rem; font-weight:850; text-transform:uppercase; }
        .pyp-kpi strong { position:relative; z-index:1; display:block; color:var(--pyp-metric); font-size:1.32rem; margin-top:8px; line-height:1; }
        .pyp-kpi small { position:relative; z-index:1; display:block; max-width:82%; color:#64748b; margin-top:6px; line-height:1.35; }
        .pyp-empty-state { position:relative; overflow:hidden; display:grid; justify-items:center; padding:38px 20px; border:1px dashed #cbd5e1; border-radius:10px; background:#fff; text-align:center; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .pyp-empty-icon { width:48px; height:48px; display:grid; place-items:center; border-radius:11px; background:#fff3e8; color:#ea580c; font-size:1.15rem; }
        .pyp-empty-state h3 { margin:12px 0 5px; color:var(--blue-dark); font-size:.9rem; }
        .pyp-empty-state p { max-width:520px; margin:0; color:#64748b; font-size:.68rem; line-height:1.45; }
        .pyp-empty-actions { display:flex; gap:8px; margin-top:14px; }
        .pyp-category-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin-bottom:14px; }
        .pyp-category-card { background:#fff; border:1px solid var(--border); border-radius:10px; padding:14px; box-shadow:0 8px 24px rgba(15,23,42,.04); min-width:0; }
        .pyp-category-head { display:flex; align-items:flex-start; gap:10px; margin-bottom:12px; }
        .pyp-icon { width:36px; height:36px; border-radius:9px; background:#fff3e8; color:var(--primary2); display:grid; place-items:center; flex:none; }
        .pyp-category-head h3 { margin:0; color:var(--blue-dark); font-size:.86rem; line-height:1.25; }
        .pyp-category-head p { margin:4px 0 0; color:#64748b; font-size:.66rem; line-height:1.35; }
        .pyp-card-metrics { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:7px; margin-bottom:11px; }
        .pyp-card-metrics div { border:1px solid #eef2f7; background:#f8fafc; border-radius:8px; padding:8px; min-width:0; }
        .pyp-card-metrics span { display:block; color:#64748b; font-size:.54rem; font-weight:850; text-transform:uppercase; }
        .pyp-card-metrics strong { display:block; color:#172554; font-size:.88rem; margin-top:3px; }
        .pyp-card-foot { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; color:#64748b; font-size:.66rem; }
        .pyp-card-foot b { display:block; color:#334155; margin-bottom:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .pyp-table-card { background:#fff; border:1px solid var(--border); border-radius:10px; overflow:hidden; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .pyp-table-head { padding:14px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .pyp-table-head h2 { margin:0; color:var(--blue-dark); font-size:.95rem; }
        .pyp-table-head p { margin:3px 0 0; color:#64748b; font-size:.68rem; }
        .pyp-table-wrap { overflow-x:auto; }
        .pyp-table { width:100%; border-collapse:collapse; min-width:880px; }
        .pyp-table th { text-align:left; padding:10px 9px; background:#f8fafc; color:#64748b; font-size:.58rem; text-transform:uppercase; border-bottom:1px solid var(--border); }
        .pyp-table td { padding:10px 9px; border-bottom:1px solid #eef2f7; color:#334155; font-size:.68rem; vertical-align:top; }
        .pyp-table tr:last-child td { border-bottom:0; }
        .pyp-activity-name { color:#172554; font-weight:850; }
        .pyp-activity-desc { display:block; margin-top:3px; color:#64748b; font-size:.62rem; line-height:1.35; max-width:280px; }
        .pyp-badge { display:inline-flex; align-items:center; gap:5px; border-radius:999px; padding:5px 8px; font-size:.58rem; font-weight:850; text-transform:uppercase; white-space:nowrap; }
        .pyp-badge.programada { background:#eff6ff; color:#1d4ed8; }
        .pyp-badge.en_proceso { background:#ecfdf5; color:#047857; }
        .pyp-badge.ejecutada { background:#f0fdf4; color:#15803d; }
        .pyp-badge.vencida { background:#fff7ed; color:#c2410c; }
        .pyp-badge.cancelada { background:#fef2f2; color:#b91c1c; }
        .pyp-progress { min-width:120px; }
        .pyp-progress-track { height:8px; border-radius:99px; background:#e2e8f0; overflow:hidden; margin-top:5px; }
        .pyp-progress-fill { height:100%; border-radius:inherit; background:linear-gradient(90deg,#ff8a1f,#16a34a); min-width:2px; }
        .pyp-demo-note { display:grid; grid-template-columns:auto minmax(0,1fr) auto; align-items:center; gap:11px; margin-bottom:14px; padding:12px 14px; border:1px solid #bfdbfe; border-left:4px solid #2563eb; border-radius:10px; background:#eff6ff; box-shadow:0 8px 22px rgba(37,99,235,.06); }
        .pyp-demo-note-icon { width:34px; height:34px; border-radius:9px; display:grid; place-items:center; background:#dbeafe; color:#2563eb; }
        .pyp-demo-note strong { display:block; color:#1e3a8a; font-size:.75rem; }
        .pyp-demo-note p { margin:3px 0 0; color:#475569; font-size:.64rem; line-height:1.4; }
        .pyp-demo-label { display:inline-flex; align-items:center; min-height:27px; padding:0 8px; border-radius:999px; background:#fff; color:#1d4ed8; font-size:.55rem; font-weight:900; text-transform:uppercase; white-space:nowrap; }
        .pyp-demo-category-card { position:relative; overflow:hidden; }
        .pyp-demo-corner { position:absolute; right:0; top:0; padding:5px 8px; border-radius:0 9px 0 8px; background:#fff3e8; color:#c2410c; font-size:.49rem; font-weight:900; text-transform:uppercase; }
        .pyp-demo-category-progress { margin:0 0 11px; }
        .pyp-demo-category-progress > div:first-child { display:flex; align-items:center; justify-content:space-between; gap:8px; color:#64748b; font-size:.56rem; font-weight:800; }
        .pyp-demo-category-progress strong { color:var(--primary2); }
        .pyp-demo-activities { overflow:hidden; border:1px solid var(--border); border-radius:10px; background:#fff; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .pyp-demo-activities-head { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px; border-bottom:1px solid #e2e8f0; background:linear-gradient(135deg,#fff,#f8fafc); }
        .pyp-demo-eyebrow { display:block; margin-bottom:3px; color:var(--primary2); font-size:.54rem; font-weight:900; text-transform:uppercase; }
        .pyp-demo-activities-head h2 { margin:0; color:var(--blue-dark); font-size:.92rem; }
        .pyp-demo-activities-head p { margin:4px 0 0; color:#64748b; font-size:.64rem; }
        .pyp-demo-count { display:inline-flex; align-items:center; gap:6px; min-height:30px; padding:0 9px; border-radius:999px; background:#fff3e8; color:#c2410c; font-size:.58rem; font-weight:900; white-space:nowrap; }
        .pyp-demo-activity-list { display:grid; }
        .pyp-demo-activity { display:grid; grid-template-columns:auto minmax(210px,1.2fr) minmax(125px,.65fr) minmax(170px,.8fr) minmax(150px,.75fr) auto; align-items:center; gap:13px; padding:12px 14px; border-bottom:1px solid #eef2f7; }
        .pyp-demo-activity:last-child { border-bottom:0; }
        .pyp-demo-activity:hover { background:#fbfdff; }
        .pyp-demo-activity-icon { width:36px; height:36px; border-radius:9px; display:grid; place-items:center; background:#fff3e8; color:var(--primary2); }
        .pyp-demo-activity-copy { min-width:0; }
        .pyp-demo-activity-copy > span { display:block; color:#94a3b8; font-size:.5rem; font-weight:850; text-transform:uppercase; }
        .pyp-demo-activity-copy > strong { display:block; margin-top:3px; color:#172554; font-size:.7rem; line-height:1.25; }
        .pyp-demo-activity-copy > small { display:block; margin-top:4px; color:#64748b; font-size:.55rem; }
        .pyp-demo-activity-status { display:grid; justify-items:start; gap:5px; }
        .pyp-demo-activity-status small,.pyp-demo-activity-progress small,.pyp-demo-activity-evidence small { color:#64748b; font-size:.54rem; }
        .pyp-demo-activity-progress > div:first-child { display:flex; align-items:center; justify-content:space-between; gap:8px; color:#64748b; font-size:.55rem; }
        .pyp-demo-activity-progress strong { color:#172554; }
        .pyp-demo-activity-evidence { display:grid; gap:5px; color:#334155; font-size:.58rem; font-weight:800; }
        .pyp-demo-activity-evidence i { color:#16a34a; }
        .pyp-campaign-link { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-height:32px; padding:0 10px; border:1px solid #fed7aa; border-radius:8px; background:#fff7ed; color:#c2410c; text-decoration:none; font-size:.57rem; font-weight:900; white-space:nowrap; transition:.18s ease; }
        .pyp-campaign-link:hover { border-color:#fb923c; background:#ffedd5; transform:translateY(-1px); }
        .pyp-campaign-link i { font-size:.58rem; }
        .pyp-empty { background:#fff; border:1px dashed #cbd5e1; border-radius:10px; padding:34px 18px; text-align:center; color:#64748b; }
        .pyp-empty i { display:block; color:#cbd5e1; font-size:1.8rem; margin-bottom:10px; }
        .cargo-alert { margin-bottom:14px; border-radius:10px; padding:11px 13px; display:flex; align-items:flex-start; gap:9px; font-weight:700; }
        .cargo-alert.ok { border:1px solid #bbf7d0; background:#f0fdf4; color:#15803d; }
        .cargo-alert.error { border:1px solid #fecaca; background:#fef2f2; color:#b91c1c; }
        .cargo-layout { display:grid; grid-template-columns:minmax(320px,.92fr) minmax(360px,1.08fr); gap:14px; align-items:start; }
        .cargo-panel { background:#fff; border:1px solid var(--border); border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,.045); overflow:hidden; }
        .cargo-panel-head { padding:15px; border-bottom:1px solid var(--border); display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
        .cargo-panel-title { display:flex; align-items:flex-start; gap:10px; min-width:0; }
        .cargo-panel-icon { width:36px; height:36px; border-radius:9px; display:grid; place-items:center; background:#fff3e8; color:var(--primary2); flex:none; }
        .cargo-panel h2 { margin:0; color:var(--blue-dark); font-size:.98rem; }
        .cargo-panel p { margin:4px 0 0; color:var(--muted); line-height:1.38; font-size:.68rem; }
        .cargo-form { padding:15px; display:grid; gap:12px; }
        .cargo-grid-2 { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .cargo-field { display:grid; gap:5px; }
        .cargo-field.full { grid-column:1 / -1; }
        .cargo-field label { color:#334155; font-size:.62rem; font-weight:850; text-transform:uppercase; letter-spacing:.02em; }
        .cargo-field input, .cargo-field select, .cargo-field textarea { width:100%; border:1px solid var(--border); border-radius:8px; background:#f8fafc; color:#1f2d3d; padding:10px 11px; font:inherit; font-size:.74rem; outline:0; }
        .cargo-field textarea { min-height:72px; resize:vertical; }
        .cargo-field input:focus, .cargo-field select:focus, .cargo-field textarea:focus { border-color:#fdba74; background:#fff; box-shadow:0 0 0 3px rgba(255,138,31,.12); }
        .cargo-file { min-height:72px; border:1px dashed #cbd5e1; border-radius:8px; background:#fbfdff; padding:10px; }
        .cargo-file input { border:0; background:transparent; padding:0; box-shadow:none; }
        .cargo-dynamic { display:grid; gap:8px; }
        .cargo-dynamic-row { display:grid; grid-template-columns:1fr 36px; gap:8px; align-items:center; }
        .tools-check-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
        .tools-groups { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .tool-group { display:none; border:1px solid #e2e8f0; background:#fbfdff; border-radius:9px; padding:10px; }
        .tool-group.active { display:grid; gap:9px; align-content:start; }
        .tool-group h3 { margin:0; color:#172554; font-size:.74rem; display:flex; align-items:center; gap:7px; }
        .tool-group h3 i { color:var(--primary2); }
        .tool-group .tools-check-grid { grid-template-columns:1fr; }
        .tool-check { display:flex; align-items:flex-start; gap:8px; border:1px solid #e2e8f0; background:#f8fafc; border-radius:8px; padding:9px; color:#334155; font-size:.7rem; font-weight:750; line-height:1.25; cursor:pointer; }
        .tool-check input { width:16px; height:16px; margin:1px 0 0; accent-color:var(--primary2); flex:none; }
        .tool-check:has(input:checked) { border-color:#fdba74; background:#fff7ed; color:#c2410c; }
        .risk-check-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
        .risk-check { display:flex; align-items:flex-start; gap:8px; border:1px solid #e2e8f0; background:#f8fafc; border-radius:8px; padding:9px; color:#334155; font-size:.7rem; font-weight:750; line-height:1.25; cursor:pointer; }
        .risk-check input { width:16px; height:16px; margin:1px 0 0; accent-color:var(--primary2); flex:none; }
        .risk-check:has(input:checked) { border-color:#fdba74; background:#fff7ed; color:#c2410c; }
        .field-hint { color:#64748b; font-size:.62rem; line-height:1.35; margin-top:-2px; }
        .process-new-panel { display:none; border:1px solid #fed7aa; background:#fff7ed; border-radius:8px; padding:10px; gap:9px; }
        .process-new-panel.active { display:grid; }
        .process-save-options { display:flex; flex-wrap:wrap; gap:8px; color:#9a3412; font-size:.68rem; font-weight:800; }
        .process-save-options label { display:flex; align-items:center; gap:6px; text-transform:none; letter-spacing:0; color:#9a3412; }
        .process-save-options input { width:auto; accent-color:var(--primary2); }
        .cargo-icon-btn { width:36px; height:36px; border:1px solid var(--border); border-radius:8px; background:#fff; color:#64748b; display:grid; place-items:center; cursor:pointer; }
        .cargo-icon-btn:hover { border-color:#fdba74; color:var(--primary2); background:#fff8f3; }
        .cargo-add-btn { justify-self:start; height:34px; border:1px solid #fed7aa; background:#fff7ed; color:#c2410c; border-radius:8px; padding:0 11px; font-weight:850; font-size:.68rem; display:inline-flex; align-items:center; gap:7px; cursor:pointer; }
        .cargo-submit { height:40px; border:0; border-radius:8px; background:var(--primary2); color:#fff; font-weight:850; font-size:.74rem; display:inline-flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; }
        .cargo-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-bottom:14px; }
        .cargo-summary.compact { grid-template-columns:repeat(2,minmax(0,1fr)); max-width:720px; }
        .cargo-kpi { background:#fff; border:1px solid var(--border); border-radius:10px; padding:12px; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .cargo-kpi span { display:block; color:#64748b; font-size:.58rem; font-weight:850; text-transform:uppercase; }
        .cargo-kpi strong { display:block; margin-top:7px; color:var(--blue-dark); font-size:1.15rem; }
        .cargo-list { padding:0 15px 15px; display:grid; gap:9px; }
        .cargo-item { border:1px solid #eef2f7; border-radius:9px; background:#fbfdff; padding:11px; min-width:0; }
        .cargo-item-head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
        .cargo-item h3 { margin:0; color:#172554; font-size:.8rem; line-height:1.25; }
        .cargo-item p { margin:4px 0 0; color:#64748b; font-size:.64rem; line-height:1.35; }
        .cargo-tags { margin-top:8px; display:flex; flex-wrap:wrap; gap:5px; }
        .cargo-tag { display:inline-flex; align-items:center; gap:5px; border-radius:999px; background:#f1f5f9; color:#475569; padding:4px 7px; font-size:.58rem; font-weight:800; }
        .cargo-link { color:var(--primary2); text-decoration:none; font-weight:850; font-size:.64rem; white-space:nowrap; }
        .cargo-empty { padding:22px 15px; text-align:center; color:#64748b; border-top:1px dashed #cbd5e1; }
        .cargo-empty i { display:block; color:#cbd5e1; font-size:1.5rem; margin-bottom:8px; }
        .med-alert { margin-bottom:14px; border-radius:10px; padding:11px 13px; display:flex; align-items:flex-start; gap:9px; font-weight:700; }
        .med-alert.ok { border:1px solid #bbf7d0; background:#f0fdf4; color:#15803d; }
        .med-alert.error { border:1px solid #fecaca; background:#fef2f2; color:#b91c1c; }
        .med-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-bottom:14px; }
        .med-kpi { --med-metric:#ea580c; position:relative; overflow:hidden; min-height:104px; background:#fff; border:1px solid var(--border); border-radius:10px; padding:13px; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .med-kpi.blue { --med-metric:#2563eb; }
        .med-kpi.green { --med-metric:#059669; }
        .med-kpi.violet { --med-metric:#7c3aed; }
        .med-kpi-watermark { position:absolute; right:-8px; bottom:-18px; color:var(--med-metric); font-size:4.5rem; line-height:1; opacity:.055; transform:rotate(-10deg); pointer-events:none; }
        .med-kpi span { position:relative; z-index:1; display:block; color:#64748b; font-size:.62rem; font-weight:850; text-transform:uppercase; }
        .med-kpi strong { position:relative; z-index:1; display:block; margin-top:8px; color:var(--med-metric); font-size:1.32rem; line-height:1; }
        .med-kpi small { position:relative; z-index:1; display:block; max-width:82%; margin-top:7px; color:#64748b; font-size:.6rem; line-height:1.35; }
        .med-demo-wrap { margin-bottom:14px; display:grid; gap:12px; }
        .med-demo-note { border:1px solid #bfdbfe; background:linear-gradient(135deg,#eff6ff 0%,#fff 74%); border-radius:11px; padding:12px 14px; display:flex; align-items:center; gap:11px; color:#1e3a8a; box-shadow:0 8px 24px rgba(37,99,235,.06); }
        .med-demo-note-icon { width:36px; height:36px; border-radius:9px; background:#dbeafe; color:#2563eb; display:grid; place-items:center; flex:none; }
        .med-demo-note strong { display:block; color:#172554; font-size:.72rem; margin-bottom:2px; }
        .med-demo-note span { display:block; color:#526887; font-size:.64rem; line-height:1.4; }
        .med-demo-label { margin-left:auto; border-radius:999px; background:#fff; color:#2563eb !important; border:1px solid #bfdbfe; padding:5px 9px; font-size:.55rem !important; font-weight:900; text-transform:uppercase; letter-spacing:.03em; white-space:nowrap; }
        .med-demo-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
        .med-demo-kpi { position:relative; overflow:hidden; min-height:104px; background:#fff; border:1px solid var(--border); border-radius:11px; padding:13px; box-shadow:0 8px 24px rgba(15,23,42,.045); }
        .med-demo-kpi > * { position:relative; z-index:1; }
        .med-demo-kpi .watermark { position:absolute; z-index:1; right:-8px; bottom:-18px; color:var(--demo-color,#2563eb); font-size:4.5rem; line-height:1; opacity:.055; transform:rotate(-10deg); pointer-events:none; }
        .med-demo-kpi span { display:block; color:#64748b; font-size:.57rem; font-weight:900; text-transform:uppercase; letter-spacing:.02em; }
        .med-demo-kpi strong { display:block; margin-top:7px; color:#172554; font-size:1.3rem; line-height:1; }
        .med-demo-kpi small { display:block; max-width:82%; margin-top:7px; color:#64748b; font-size:.6rem; line-height:1.35; }
        .med-demo-board { background:#fff; border:1px solid var(--border); border-radius:11px; box-shadow:0 8px 24px rgba(15,23,42,.045); overflow:hidden; }
        .med-demo-board-head { padding:14px 15px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; gap:12px; align-items:flex-start; }
        .med-demo-board-head h2 { margin:0; color:var(--blue-dark); font-size:.96rem; }
        .med-demo-board-head p { margin:4px 0 0; color:#64748b; font-size:.65rem; line-height:1.4; }
        .med-demo-count { border-radius:999px; background:#fff7ed; color:#c2410c; padding:6px 9px; font-size:.58rem; font-weight:900; white-space:nowrap; }
        .med-demo-list { display:grid; }
        .med-demo-case { display:grid; grid-template-columns:42px minmax(180px,1.25fr) minmax(135px,.7fr) minmax(185px,.9fr) minmax(155px,.75fr) auto; gap:12px; align-items:center; padding:12px 15px; border-bottom:1px solid #eef2f7; }
        .med-demo-case:last-child { border-bottom:0; }
        .med-demo-case:hover { background:#fbfdff; }
        .med-demo-case-icon { width:40px; height:40px; border-radius:10px; background:#fff7ed; color:var(--primary2); display:grid; place-items:center; font-size:.9rem; }
        .med-demo-worker small { display:block; color:#94a3b8; font-size:.53rem; font-weight:850; text-transform:uppercase; margin-bottom:3px; }
        .med-demo-worker strong { display:block; color:#172554; font-size:.72rem; line-height:1.25; }
        .med-demo-worker span,.med-demo-meta span,.med-demo-evidence span { display:block; color:#64748b; font-size:.59rem; line-height:1.4; margin-top:3px; }
        .med-demo-meta strong,.med-demo-evidence strong { display:block; color:#334155; font-size:.64rem; line-height:1.3; }
        .med-demo-progress-head { display:flex; justify-content:space-between; gap:8px; color:#64748b; font-size:.57rem; margin-bottom:5px; }
        .med-demo-progress-head strong { color:#172554; font-size:.6rem; }
        .med-demo-progress { height:6px; border-radius:999px; background:#e2e8f0; overflow:hidden; }
        .med-demo-progress > span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,var(--primary2),#22c55e); }
        .med-demo-action { min-height:32px; display:inline-flex; align-items:center; justify-content:center; gap:6px; border:1px solid #dbe3ec; border-radius:8px; background:#fff; color:#1e3a8a; padding:0 10px; font-size:.6rem; font-weight:850; white-space:nowrap; }
        .med-demo-footer { border-top:1px solid #eef2f7; background:#f8fafc; padding:10px 15px; display:flex; align-items:center; justify-content:space-between; gap:12px; color:#64748b; font-size:.6rem; }
        .med-demo-footer strong { color:#172554; }
        .med-control-access { position:relative; overflow:hidden; background:linear-gradient(135deg,#fff 0%,#f8fbff 100%); border:1px solid #dbe7f3; border-radius:11px; padding:15px; margin-bottom:14px; display:flex; align-items:center; justify-content:space-between; gap:18px; box-shadow:0 8px 24px rgba(15,23,42,.04); order:2; }
        .med-control-access-copy { display:flex; align-items:center; gap:12px; min-width:0; }
        .med-control-access-copy > i { color:#2563eb; font-size:1.18rem; flex:none; }
        .med-control-access h2 { margin:0; color:#172554; font-size:.88rem; }
        .med-control-access p { margin:4px 0 0; color:#64748b; font-size:.64rem; line-height:1.4; }
        .med-control-access-meta { display:flex; align-items:center; justify-content:flex-end; gap:8px; flex-wrap:wrap; }
        .med-control-access-stat { border:1px solid #e2e8f0; border-radius:8px; background:#fff; padding:7px 9px; min-width:72px; }
        .med-control-access-stat span { display:block; color:#94a3b8; font-size:.52rem; font-weight:850; text-transform:uppercase; }
        .med-control-access-stat strong { display:block; margin-top:3px; color:#172554; font-size:.78rem; }
        .med-control-access-link { min-height:36px; border-radius:8px; background:var(--primary2); color:#fff; padding:0 12px; display:inline-flex; align-items:center; gap:7px; text-decoration:none; font-size:.64rem; font-weight:900; white-space:nowrap; }
        .med-control-access-watermark { position:absolute; right:230px; bottom:-30px; color:#2563eb; font-size:6rem; opacity:.035; transform:rotate(-10deg); pointer-events:none; }
        .med-program-body { padding:14px; display:grid; gap:12px; }
        .med-program-config { display:grid; grid-template-columns:140px minmax(180px,1fr) minmax(200px,1fr) minmax(180px,1fr); gap:9px; padding:12px; border:1px solid #e2e8f0; border-radius:10px; background:#f8fafc; }
        .med-program-config .wide { grid-column:span 2; }
        .med-program-toolbar { display:flex; align-items:end; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .med-worker-search { min-width:260px; max-width:430px; flex:1; }
        .med-worker-search-box { position:relative; }
        .med-worker-search-box i { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:#94a3b8; }
        .med-worker-search-box input { padding-left:34px; }
        .med-program-selection { display:flex; align-items:center; gap:9px; flex-wrap:wrap; }
        .med-select-visible { display:flex; align-items:center; gap:7px; color:#475569; font-size:.62rem; font-weight:800; cursor:pointer; }
        .med-select-visible input { width:17px; height:17px; }
        .med-selected-count { border-radius:999px; background:#fff7ed; color:#c2410c; padding:6px 9px; font-size:.58rem; font-weight:900; }
        .med-worker-list { display:grid; gap:8px; }
        .med-worker-item { display:grid; grid-template-columns:22px minmax(190px,1.15fr) minmax(160px,.8fr) minmax(150px,.7fr); gap:12px; align-items:center; padding:11px 12px; border:1px solid #e2e8f0; border-radius:9px; background:#fff; transition:border-color .18s ease,background .18s ease,box-shadow .18s ease; }
        .med-worker-item:hover { border-color:#fdba74; box-shadow:0 6px 18px rgba(15,23,42,.04); }
        .med-worker-item:has(input:checked) { border-color:#fb923c; background:#fffaf5; }
        .med-worker-check,.med-select-visible input { appearance:none; -webkit-appearance:none; position:relative; flex:none; border:1.5px solid #94a3b8; border-radius:4px; background:#fff; cursor:pointer; display:grid; place-items:center; transition:background .16s ease,border-color .16s ease,box-shadow .16s ease; }
        .med-worker-check { width:18px; height:18px; }
        .med-worker-check::after,.med-select-visible input::after { content:""; width:4px; height:8px; border:solid #fff; border-width:0 2px 2px 0; transform:translateY(-1px) rotate(45deg) scale(0); transform-origin:center; transition:transform .12s ease; }
        .med-worker-check:checked,.med-select-visible input:checked { border-color:var(--primary2); background:var(--primary2); box-shadow:0 0 0 3px rgba(255,138,31,.12); }
        .med-worker-check:checked::after,.med-select-visible input:checked::after { transform:translateY(-1px) rotate(45deg) scale(1); }
        .med-worker-check:focus-visible,.med-select-visible input:focus-visible { outline:2px solid #2563eb; outline-offset:2px; }
        .med-worker-primary strong { display:block; color:#172554; font-size:.7rem; }
        .med-worker-primary span,.med-worker-secondary span { display:block; margin-top:3px; color:#64748b; font-size:.59rem; line-height:1.35; }
        .med-worker-secondary strong { display:block; color:#334155; font-size:.64rem; }
        .med-worker-status { justify-self:start; }
        .med-worker-empty { padding:24px; border:1px dashed #cbd5e1; border-radius:9px; text-align:center; color:#64748b; font-size:.65rem; }
        .med-program-footer { display:flex; justify-content:space-between; align-items:center; gap:12px; padding-top:2px; flex-wrap:wrap; }
        .med-pagination { display:flex; align-items:center; gap:7px; }
        .med-page-btn { width:32px; height:32px; border:1px solid #dbe3ec; border-radius:8px; background:#fff; color:#334155; display:grid; place-items:center; cursor:pointer; }
        .med-page-btn:disabled { opacity:.4; cursor:not-allowed; }
        .med-page-status { color:#64748b; font-size:.6rem; min-width:90px; text-align:center; }
        .med-page-size { display:flex; align-items:center; gap:7px; color:#64748b; font-size:.6rem; font-weight:750; }
        .med-page-size select { min-height:32px; border:1px solid #dbe3ec; border-radius:8px; background:#fff; color:#334155; padding:0 26px 0 9px; font:inherit; font-weight:850; outline:0; }
        .med-bulk-submit { min-height:38px; border:0; border-radius:8px; background:var(--primary2); color:#fff; padding:0 13px; display:inline-flex; align-items:center; gap:7px; font-size:.64rem; font-weight:900; cursor:pointer; }
        .med-bulk-submit:disabled { opacity:.5; cursor:not-allowed; }
        .med-personnel-overview { padding:14px 14px 0; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; }
        .med-personnel-metric { position:relative; overflow:hidden; border:1px solid #e2e8f0; border-radius:9px; background:#f8fafc; padding:10px; min-height:70px; }
        .med-personnel-metric span { display:block; color:#64748b; font-size:.52rem; font-weight:900; text-transform:uppercase; }
        .med-personnel-metric strong { display:block; margin-top:6px; color:#172554; font-size:.92rem; }
        .med-personnel-metric small { display:block; margin-top:3px; color:#94a3b8; font-size:.54rem; line-height:1.3; }
        .med-personnel-metric > i { position:absolute; right:-5px; bottom:-10px; color:#2563eb; font-size:3.2rem; opacity:.045; transform:rotate(-10deg); }
        .med-personnel-list,.med-request-list { padding:14px; display:grid; gap:8px; }
        .med-personnel-item { position:relative; overflow:hidden; border:1px solid #e2e8f0; border-radius:10px; background:#fff; padding:12px; }
        .med-personnel-item:hover { border-color:#bfdbfe; background:#fbfdff; }
        .med-personnel-top { position:relative; z-index:1; display:flex; align-items:center; justify-content:space-between; gap:12px; padding-bottom:10px; border-bottom:1px solid #eef2f7; }
        .med-personnel-person { display:flex; align-items:center; gap:10px; min-width:0; }
        .med-personnel-icon { width:34px; height:34px; margin:0; border-radius:8px; background:#eff6ff; color:#2563eb; display:grid; place-items:center; flex:none; font-size:.78rem; line-height:1; }
        .med-personnel-icon i { display:block; line-height:1; }
        .med-personnel-person strong,.med-personnel-data strong { display:block; color:#172554; font-size:.68rem; line-height:1.3; }
        .med-personnel-copy > span,.med-personnel-data span { display:block; margin-top:3px; color:#64748b; font-size:.58rem; line-height:1.4; }
        .med-personnel-actions { display:flex; align-items:center; justify-content:flex-end; gap:8px; }
        .med-personnel-body { position:relative; z-index:1; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; padding-top:10px; }
        .med-personnel-data { border:1px solid #edf2f7; border-radius:8px; background:#fbfdff; padding:9px; }
        .med-personnel-data > small { display:block; margin-bottom:5px; color:#94a3b8; font-size:.5rem; font-weight:900; text-transform:uppercase; }
        .med-personnel-action-link { min-height:32px; border:1px solid #fed7aa; border-radius:8px; background:#fff7ed; color:#c2410c; padding:0 9px; display:inline-flex; align-items:center; gap:6px; text-decoration:none; font-size:.58rem; font-weight:900; white-space:nowrap; }
        .med-personnel-watermark { position:absolute; right:-8px; bottom:-19px; color:#2563eb; font-size:4.6rem; opacity:.025; transform:rotate(-10deg); pointer-events:none; }
        .med-alert-empty { position:relative; overflow:hidden; margin:14px; border:1px solid #bbf7d0; border-radius:11px; background:linear-gradient(135deg,#f0fdf4,#fff); padding:14px; display:grid; grid-template-columns:46px minmax(180px,1fr) minmax(330px,1.25fr) auto; gap:14px; align-items:center; }
        .med-alert-empty-icon { width:44px; height:44px; border-radius:10px; background:#dcfce7; color:#16a34a; display:grid; place-items:center; font-size:1.05rem; }
        .med-alert-empty-copy strong { display:block; color:#14532d; font-size:.78rem; }
        .med-alert-empty-copy span { display:block; margin-top:4px; color:#4b6b57; font-size:.59rem; line-height:1.4; }
        .med-alert-empty-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:7px; }
        .med-alert-empty-stat { border:1px solid #dcfce7; border-radius:8px; background:rgba(255,255,255,.8); padding:8px; }
        .med-alert-empty-stat span { display:block; color:#64748b; font-size:.49rem; font-weight:900; text-transform:uppercase; }
        .med-alert-empty-stat strong { display:block; margin-top:4px; color:#166534; font-size:.66rem; }
        .med-alert-empty-link { min-height:34px; border:1px solid #86efac; border-radius:8px; background:#fff; color:#15803d; padding:0 10px; display:inline-flex; align-items:center; gap:6px; text-decoration:none; font-size:.58rem; font-weight:900; white-space:nowrap; }
        .med-alert-empty-watermark { position:absolute; right:130px; bottom:-24px; color:#16a34a; font-size:6rem; opacity:.025; transform:rotate(-10deg); pointer-events:none; }
        .med-request-item { position:relative; overflow:hidden; border:1px solid #e2e8f0; border-radius:10px; background:#fff; }
        .med-request-item:hover { border-color:#fdba74; box-shadow:0 6px 18px rgba(15,23,42,.04); }
        .med-request-item[open] { border-color:#fdba74; box-shadow:0 8px 22px rgba(15,23,42,.05); }
        .med-request-summary { position:relative; z-index:1; display:grid; grid-template-columns:40px minmax(180px,1fr) minmax(150px,.75fr) minmax(190px,.9fr) minmax(105px,.5fr) 32px; gap:12px; align-items:center; padding:12px; cursor:pointer; list-style:none; }
        .med-request-summary::-webkit-details-marker { display:none; }
        .med-request-icon { width:38px; height:38px; border-radius:9px; background:#fff7ed; color:var(--primary2); display:grid; place-items:center; }
        .med-request-copy strong,.med-request-meta strong { display:block; color:#172554; font-size:.68rem; line-height:1.3; }
        .med-request-copy span,.med-request-meta span { display:block; margin-top:3px; color:#64748b; font-size:.58rem; line-height:1.4; }
        .med-request-status { display:flex; align-items:flex-end; flex-direction:column; gap:5px; }
        .med-request-toggle { width:30px; height:30px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; color:#64748b; display:grid; place-items:center; justify-self:end; }
        .med-request-toggle i { transition:transform .2s ease; }
        .med-request-item[open] .med-request-toggle { border-color:#fed7aa; background:#fff7ed; color:#c2410c; }
        .med-request-item[open] .med-request-toggle i { transform:rotate(180deg); }
        .med-request-watermark { position:absolute; right:34px; bottom:-18px; color:var(--primary2); font-size:4.5rem; opacity:.025; transform:rotate(-10deg); pointer-events:none; }
        .med-request-detail { position:relative; z-index:1; border-top:1px solid #eef2f7; background:#fbfdff; padding:12px; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; }
        .med-request-detail-box { border:1px solid #e8eef5; border-radius:8px; background:#fff; padding:9px; min-width:0; }
        .med-request-detail-box.wide { grid-column:span 2; }
        .med-request-detail-box span { display:block; color:#94a3b8; font-size:.51rem; font-weight:900; text-transform:uppercase; letter-spacing:.02em; }
        .med-request-detail-box strong { display:block; margin-top:5px; color:#334155; font-size:.62rem; line-height:1.4; overflow-wrap:anywhere; }
        .med-request-next { color:#c2410c!important; }
        .med-card { background:#fff; border:1px solid var(--border); border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,.045); overflow:hidden; margin-bottom:14px; }
        .med-card-head { padding:15px; border-bottom:1px solid var(--border); display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
        .med-card-head h2 { margin:0; color:var(--blue-dark); font-size:.98rem; }
        .med-card-head p { margin:4px 0 0; color:var(--muted); line-height:1.38; font-size:.68rem; }
        .med-card.program-list { order:1; }
        .med-card.request-history { order:3; }
        .med-card.upload-exam { order:3; }
        .med-card.personnel-matrix { order:4; }
        .med-card.alert-panel { order:5; }
        .content-area:has(.med-card.program-list) { display:flex; flex-direction:column; }
        .med-table-wrap { overflow-x:auto; }
        .med-table { width:100%; border-collapse:collapse; min-width:980px; }
        .med-table th { text-align:left; padding:10px 9px; background:#f8fafc; color:#64748b; font-size:.58rem; text-transform:uppercase; border-bottom:1px solid var(--border); }
        .med-table td { padding:10px 9px; border-bottom:1px solid #eef2f7; color:#334155; font-size:.68rem; vertical-align:top; }
        .med-table tr:last-child td { border-bottom:0; }
        .med-worker-name { color:#172554; font-weight:850; display:block; margin-bottom:3px; }
        .med-muted { color:#64748b; font-size:.62rem; line-height:1.35; }
        .med-form-grid { display:grid; grid-template-columns:130px 1fr 1fr minmax(140px,.7fr) 112px; gap:8px; align-items:start; }
        .med-field { width:100%; min-height:36px; border:1px solid var(--border); border-radius:8px; background:#f8fafc; color:#334155; padding:8px 9px; font:inherit; font-size:.66rem; outline:0; }
        .upload-exam .med-field { border-radius:6px; min-height:34px; }
        .med-field:focus { border-color:#fdba74; background:#fff; box-shadow:0 0 0 3px rgba(255,138,31,.12); }
        .med-btn { min-height:36px; border:0; border-radius:8px; background:var(--primary2); color:#fff; font-weight:850; font-size:.66rem; display:inline-flex; align-items:center; justify-content:center; gap:6px; cursor:pointer; padding:0 10px; }
        .med-btn:disabled { opacity:.55; cursor:not-allowed; }
        .med-badge { display:inline-flex; align-items:center; gap:5px; border-radius:999px; padding:5px 8px; font-size:.58rem; font-weight:850; text-transform:uppercase; background:#eff6ff; color:#1d4ed8; white-space:nowrap; }
        .med-badge.solicitada { background:#fff7ed; color:#c2410c; }
        .med-badge.programada { background:#eff6ff; color:#1d4ed8; }
        .med-badge.realizada { background:#f0fdf4; color:#15803d; }
        .med-badge.cancelada { background:#fef2f2; color:#b91c1c; }
        .med-badge.vencido { background:#fef2f2; color:#b91c1c; }
        .med-badge.critico { background:#fff7ed; color:#c2410c; }
        .med-badge.alerta { background:#fffbeb; color:#92400e; }
        .med-badge.vigente { background:#f0fdf4; color:#15803d; }
        .med-badge.sin-fecha { background:#f1f5f9; color:#475569; }
        .med-empty { padding:28px 15px; text-align:center; color:#64748b; }
        .med-empty i { display:block; color:#cbd5e1; font-size:1.6rem; margin-bottom:8px; }
        .med-upload-grid { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:10px; align-items:end; }
        .med-upload-grid .wide { grid-column:span 2; }
        .med-upload-grid .full { grid-column:1 / -1; }
        .med-label { display:block; color:#334155; font-size:.62rem; font-weight:850; text-transform:uppercase; margin-bottom:6px; letter-spacing:.02em; }
        .upload-exam .med-label { color:#0f172a; font-size:.56rem; letter-spacing:.03em; }
        .med-file { border:1px dashed #fdba74; border-radius:10px; background:#fff7ed; padding:12px; color:#9a3412; font-size:.68rem; }
        .med-file input { width:100%; font-size:.68rem; }
        .med-section-title { grid-column:1 / -1; display:flex; align-items:center; gap:8px; color:#1e3a8a; font-size:.78rem; font-weight:900; border-top:1px solid #edf2f7; padding-top:12px; margin-top:4px; }
        .med-extract-box { min-height:86px; resize:vertical; }
        .med-note { color:#64748b; font-size:.64rem; line-height:1.45; }
        .med-alert-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-bottom:14px; }
        .alert-panel .med-alert-grid { padding:14px; margin:0; }
        .med-alert-item { position:relative; overflow:hidden; border:1px solid var(--border); border-radius:10px; background:#f8fafc; padding:12px; display:grid; grid-template-columns:28px minmax(0,1fr); gap:9px; align-items:start; min-width:0; }
        .med-alert-item > i:not(.med-alert-watermark) { margin-top:2px; font-size:.92rem; }
        .med-alert-watermark { position:absolute; right:-7px; bottom:-16px; font-size:4.2rem; opacity:.045; transform:rotate(-10deg); pointer-events:none; }
        .med-alert-item.vencido { border-color:#fecaca; background:#fef2f2; color:#991b1b; }
        .med-alert-item.critico { border-color:#fed7aa; background:#fff7ed; color:#9a3412; }
        .med-alert-item.alerta { border-color:#fde68a; background:#fffbeb; color:#92400e; }
        .med-alert-item strong { display:block; color:#172554; font-size:.72rem; margin-bottom:2px; }
        .med-alert-item span { display:block; font-size:.62rem; line-height:1.35; }
        .med-alert-date { margin-top:7px; display:inline-flex!important; width:max-content; align-items:center; gap:5px; border-radius:999px; background:rgba(255,255,255,.72); padding:4px 7px; font-weight:800; }
        .med-notification { margin-bottom:14px; border:1px solid #fed7aa; background:#fff7ed; color:#9a3412; border-radius:10px; padding:11px 13px; display:flex; align-items:center; justify-content:space-between; gap:12px; font-size:.7rem; font-weight:800; }
        .med-notification strong { color:#172554; }
        .med-notification .med-badge { background:#ffedd5; color:#c2410c; }
        .med-actions-row { display:flex; gap:8px; align-items:center; justify-content:flex-end; flex-wrap:wrap; }
        .med-link { color:#1d4ed8; font-weight:800; text-decoration:none; font-size:.64rem; }
        .med-filter-row { padding:12px 15px 0; display:flex; align-items:end; gap:10px; flex-wrap:wrap; border-top:1px solid #f1f5f9; }
        .med-filter-row .med-filter-field { min-width:230px; flex:1; max-width:360px; }
        .med-filter-row .med-filter-actions { display:flex; align-items:center; gap:8px; padding-bottom:1px; }
        .med-ghost-btn { min-height:34px; border:1px solid var(--border); border-radius:7px; background:#fff; color:#334155; padding:0 10px; font-size:.64rem; font-weight:850; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
        .med-ghost-btn:hover { border-color:#fdba74; color:#c2410c; background:#fff7ed; }
        .upload-exam .med-upload-grid { grid-template-columns:150px 165px 170px 180px 250px 240px 140px 150px 150px 150px 150px 150px 150px 130px; overflow-x:auto; padding:12px 15px 15px; border-top:1px solid #f1f5f9; }
        .upload-exam .med-upload-grid > div { min-width:0; }
        .upload-exam .med-upload-grid .wide { grid-column:span 2; }
        .upload-exam .med-upload-grid .full { grid-column:1 / -1; min-width:100%; }
        .upload-exam .med-section-title { grid-column:1 / -1; margin:0; padding:0 0 5px; border-top:0; border-bottom:1px solid #dbe3ec; }
        .upload-exam .med-actions-row { position:sticky; right:0; background:#fff; padding-top:8px; }
        .history-center-list { display:grid; gap:10px; padding:15px; }
        .history-center-item { border:1px solid #eef2f7; border-radius:10px; background:#fbfdff; padding:12px; display:grid; grid-template-columns:minmax(220px,.9fr) minmax(360px,1.1fr); gap:12px; align-items:start; }
        .history-center-info h3 { margin:0; color:#172554; font-size:.82rem; line-height:1.25; }
        .history-center-info p { margin:5px 0 0; color:#64748b; font-size:.64rem; line-height:1.4; }
        .history-upload-form { display:grid; grid-template-columns:minmax(150px,1fr) 140px minmax(170px,1fr) 120px; gap:8px; align-items:end; }
        .history-upload-form .med-file { padding:9px; }
        .history-upload-form .med-file input { font-size:.62rem; }
        .hist-demo-center-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; padding:14px; }
        .hist-demo-center { position:relative; overflow:hidden; border:1px solid #e2e8f0; border-radius:11px; background:linear-gradient(145deg,#fff 0%,#fbfdff 100%); padding:13px; min-width:0; }
        .hist-demo-center.wide { grid-column:1 / -1; }
        .hist-demo-center-watermark { position:absolute; right:-10px; bottom:-24px; color:#2563eb; font-size:5.7rem; opacity:.035; transform:rotate(-10deg); pointer-events:none; }
        .hist-demo-center-top { position:relative; z-index:1; display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding-bottom:10px; border-bottom:1px solid #eef2f7; }
        .hist-demo-center-title { display:flex; align-items:center; gap:10px; min-width:0; }
        .hist-demo-center-icon { color:#2563eb; font-size:1.05rem; flex:none; }
        .hist-demo-center-title strong { display:block; color:#172554; font-size:.76rem; line-height:1.3; }
        .hist-demo-center-title span { display:block; margin-top:3px; color:#64748b; font-size:.58rem; }
        .hist-demo-center-body { position:relative; z-index:1; display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; padding-top:10px; }
        .hist-demo-data { border:1px solid #edf2f7; border-radius:8px; background:rgba(248,250,252,.82); padding:8px; min-width:0; }
        .hist-demo-data small { display:block; color:#94a3b8; font-size:.49rem; font-weight:900; text-transform:uppercase; }
        .hist-demo-data strong { display:block; margin-top:4px; color:#334155; font-size:.62rem; line-height:1.35; overflow-wrap:anywhere; }
        .hist-demo-center-footer { position:relative; z-index:1; display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:10px; }
        .hist-demo-center-footer span { color:#64748b; font-size:.57rem; line-height:1.35; }
        .hist-demo-button { min-height:32px; border:1px solid #fed7aa; border-radius:8px; background:#fff7ed; color:#c2410c; padding:0 10px; display:inline-flex; align-items:center; gap:6px; font-size:.58rem; font-weight:900; white-space:nowrap; }
        .hist-upload-details { position:relative; z-index:1; margin-top:10px; border-top:1px solid #eef2f7; }
        .hist-upload-details > summary { list-style:none; padding-top:10px; display:flex; align-items:center; justify-content:space-between; gap:10px; color:#64748b; font-size:.57rem; cursor:pointer; }
        .hist-upload-details > summary::-webkit-details-marker { display:none; }
        .hist-upload-details[open] > summary { padding-bottom:10px; }
        .hist-upload-details .history-upload-form { border-top:1px solid #eef2f7; padding-top:10px; }
        .hist-demo-support-list { padding:14px; display:grid; gap:9px; }
        .hist-demo-support { position:relative; overflow:hidden; border:1px solid #e2e8f0; border-radius:10px; background:#fff; }
        .hist-demo-support[open] { border-color:#fdba74; box-shadow:0 7px 20px rgba(15,23,42,.045); }
        .hist-demo-support-summary { position:relative; z-index:1; display:grid; grid-template-columns:38px minmax(190px,1.25fr) minmax(140px,.7fr) minmax(150px,.75fr) auto 28px; gap:11px; align-items:center; padding:11px 12px; cursor:pointer; list-style:none; }
        .hist-demo-support-summary::-webkit-details-marker { display:none; }
        .hist-demo-support-icon { color:#ea580c; font-size:1.05rem; display:grid; place-items:center; }
        .hist-demo-support-copy strong,.hist-demo-support-meta strong { display:block; color:#172554; font-size:.67rem; line-height:1.3; }
        .hist-demo-support-copy span,.hist-demo-support-meta span { display:block; margin-top:3px; color:#64748b; font-size:.56rem; line-height:1.35; }
        .hist-demo-toggle { width:26px; height:26px; border:1px solid #e2e8f0; border-radius:7px; color:#64748b; display:grid; place-items:center; font-size:.58rem; }
        .hist-demo-support[open] .hist-demo-toggle i { transform:rotate(180deg); }
        .hist-demo-support-detail { position:relative; z-index:1; border-top:1px solid #eef2f7; background:#f8fafc; padding:10px 12px; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; }
        .hist-demo-support-detail .hist-demo-data { background:#fff; }
        .hist-demo-support-watermark { position:absolute; right:-8px; bottom:-20px; color:#ea580c; font-size:5rem; opacity:.025; transform:rotate(-10deg); pointer-events:none; }
        .restr-form { padding:15px; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; align-items:end; }
        .restr-form .wide { grid-column:span 2; }
        .restr-form .full { grid-column:1 / -1; }
        .restr-checks { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:8px; }
        .restr-check { display:flex; align-items:flex-start; gap:7px; border:1px solid #e2e8f0; background:#f8fafc; border-radius:8px; padding:8px; color:#334155; font-size:.64rem; font-weight:750; line-height:1.25; }
        .restr-check input { width:15px; height:15px; margin:1px 0 0; accent-color:var(--primary2); flex:none; }
        .restr-check:has(input:checked) { border-color:#fdba74; background:#fff7ed; color:#c2410c; }
        .restr-matrix .med-table { min-width:1850px; }
        .restr-matrix th { text-align:center; vertical-align:middle; color:#172554; }
        .restr-matrix .group-head th { background:#fff7ed; color:#9a3412; border-bottom:1px solid #fed7aa; }
        .restr-matrix td { vertical-align:middle; }
        .restr-mini { display:block; color:#64748b; font-size:.58rem; line-height:1.3; margin-top:3px; }
        .restr-pill-list { display:flex; flex-wrap:wrap; gap:4px; min-width:180px; }
        .restr-pill { border-radius:999px; background:#eff6ff; color:#1d4ed8; padding:3px 6px; font-size:.56rem; font-weight:850; }
        .restr-alert-row { margin-bottom:14px; display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .restr-alert-card { background:#fff; border:1px solid var(--border); border-radius:10px; padding:12px; box-shadow:0 8px 24px rgba(15,23,42,.04); }
        .restr-alert-card span { display:block; color:#64748b; font-size:.58rem; font-weight:850; text-transform:uppercase; }
        .restr-alert-card strong { display:block; margin-top:7px; color:var(--blue-dark); font-size:1.15rem; }
        .restr-dashboard-actions { position:relative; overflow:hidden; margin-bottom:14px; border:1px solid #dbe7f3; border-radius:11px; background:linear-gradient(135deg,#fff 0%,#f8fbff 100%); padding:15px; box-shadow:0 8px 24px rgba(15,23,42,.04); display:flex; align-items:center; justify-content:space-between; gap:18px; }
        .restr-dashboard-copy { display:flex; align-items:center; gap:12px; min-width:0; }
        .restr-dashboard-copy > i { color:#d97706; font-size:1.25rem; flex:none; }
        .restr-dashboard-copy h2 { margin:0; color:#172554; font-size:.9rem; }
        .restr-dashboard-copy p { margin:4px 0 0; color:#64748b; font-size:.63rem; line-height:1.4; }
        .restr-dashboard-buttons { display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
        .restr-dashboard-link { min-height:37px; border-radius:8px; padding:0 11px; display:inline-flex; align-items:center; gap:7px; text-decoration:none; font-size:.62rem; font-weight:900; white-space:nowrap; }
        .restr-dashboard-link.primary { background:var(--primary2); color:#fff; }
        .restr-dashboard-link.secondary { border:1px solid #dbe3ec; background:#fff; color:#1e3a8a; }
        .restr-dashboard-watermark { position:absolute; right:310px; bottom:-34px; color:#d97706; font-size:6.3rem; opacity:.03; transform:rotate(-10deg); pointer-events:none; }
        .restr-overview { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:9px; margin-bottom:14px; }
        .restr-overview-card { position:relative; overflow:hidden; border:1px solid #e2e8f0; border-radius:10px; background:#fff; padding:12px; box-shadow:0 7px 20px rgba(15,23,42,.035); }
        .restr-overview-card small { display:block; color:#94a3b8; font-size:.5rem; font-weight:900; text-transform:uppercase; }
        .restr-overview-card strong { display:block; margin-top:6px; color:#172554; font-size:.72rem; }
        .restr-overview-card span { display:block; margin-top:4px; color:#64748b; font-size:.57rem; line-height:1.4; }
        .restr-overview-card > i { position:absolute; right:-7px; bottom:-14px; color:#d97706; font-size:4.2rem; opacity:.04; transform:rotate(-10deg); }
        .restr-progress { height:6px; margin-top:9px; border-radius:999px; background:#e2e8f0; overflow:hidden; }
        .restr-progress > span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#f97316,#16a34a); }
        .restr-record-list { padding:14px; display:grid; gap:9px; }
        .restr-record { position:relative; overflow:hidden; border:1px solid #e2e8f0; border-radius:10px; background:#fff; }
        .restr-record[open] { border-color:#fdba74; box-shadow:0 7px 20px rgba(15,23,42,.045); }
        .restr-record-summary { position:relative; z-index:1; display:grid; grid-template-columns:38px minmax(190px,1.15fr) minmax(145px,.7fr) minmax(155px,.75fr) auto 28px; gap:11px; align-items:center; padding:11px 12px; list-style:none; cursor:pointer; }
        .restr-record-summary::-webkit-details-marker { display:none; }
        .restr-record-icon { color:#d97706; font-size:1.02rem; display:grid; place-items:center; }
        .restr-record-copy strong,.restr-record-meta strong { display:block; color:#172554; font-size:.67rem; line-height:1.3; }
        .restr-record-copy span,.restr-record-meta span { display:block; margin-top:3px; color:#64748b; font-size:.56rem; line-height:1.35; }
        .restr-record-toggle { width:26px; height:26px; border:1px solid #e2e8f0; border-radius:7px; color:#64748b; display:grid; place-items:center; font-size:.58rem; }
        .restr-record[open] .restr-record-toggle i { transform:rotate(180deg); }
        .restr-record-detail { position:relative; z-index:1; border-top:1px solid #eef2f7; background:#f8fafc; padding:10px 12px; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; }
        .restr-record-data { border:1px solid #edf2f7; border-radius:8px; background:#fff; padding:9px; min-width:0; }
        .restr-record-data.wide { grid-column:span 2; }
        .restr-record-data small { display:block; color:#94a3b8; font-size:.49rem; font-weight:900; text-transform:uppercase; }
        .restr-record-data strong { display:block; margin-top:5px; color:#334155; font-size:.61rem; line-height:1.4; overflow-wrap:anywhere; }
        .restr-record-tags { display:flex; flex-wrap:wrap; gap:4px; margin-top:5px; }
        .restr-record-watermark { position:absolute; right:-9px; bottom:-22px; color:#d97706; font-size:5.2rem; opacity:.025; transform:rotate(-10deg); pointer-events:none; }
        @media (max-width: 980px) {
            .intro-content, .workspace-grid { grid-template-columns:1fr; }
            .module-tabs { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .e5-home-hero { grid-template-columns:1fr; }
            .e5-home-badges { justify-content:flex-start; }
            .e5-module-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .rep-e5-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .rep-e5-wide { grid-column:span 2; }
            .socio-summary { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .socio-grid { grid-template-columns:1fr; }
            .socio-question-toggle { grid-template-columns:minmax(200px,1fr) minmax(180px,1fr) auto; grid-template-areas:"title insight actions" "progress progress progress"; }
            .socio-detail-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .pyp-hero { grid-template-columns:1fr; }
            .pyp-hero-actions { justify-content:flex-start; }
            .pyp-summary { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .pyp-category-grid { grid-template-columns:1fr; }
            .pyp-demo-activity { grid-template-columns:auto minmax(0,1fr) auto; }
            .pyp-demo-activity-progress { grid-column:2/3; }
            .pyp-demo-activity-evidence { grid-column:3/4; }
            .pyp-demo-activity-action { grid-column:3/4; }
            .cargo-layout { grid-template-columns:1fr; }
            .med-summary { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .med-demo-summary { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .med-demo-case { grid-template-columns:42px minmax(180px,1fr) minmax(130px,.65fr) minmax(180px,.85fr); }
            .med-demo-progress-box,.med-demo-action { grid-column:4/5; }
            .med-program-config { grid-template-columns:1fr 1fr; }
            .med-worker-item { grid-template-columns:22px minmax(180px,1fr) minmax(150px,.8fr); }
            .med-worker-status { grid-column:3/4; }
            .med-personnel-overview,.med-personnel-body { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .med-alert-empty { grid-template-columns:46px minmax(0,1fr) auto; }
            .med-alert-empty-stats { grid-column:2/4; }
            .med-request-summary { grid-template-columns:40px minmax(0,1fr) 32px; align-items:start; }
            .med-request-summary .med-request-icon { grid-column:1/2; grid-row:1; }
            .med-request-summary .med-request-copy { grid-column:2/3; grid-row:1; }
            .med-request-summary .med-request-toggle { grid-column:3/4; grid-row:1; }
            .med-request-summary .med-request-meta,.med-request-summary .med-request-status { grid-column:2/4; align-items:flex-start; }
            .med-request-detail { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .med-upload-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
            .med-alert-grid { grid-template-columns:1fr; }
            .history-center-item { grid-template-columns:1fr; }
            .history-upload-form { grid-template-columns:1fr 1fr; }
            .hist-demo-center-list { grid-template-columns:1fr; }
            .hist-demo-center.wide { grid-column:auto; }
            .hist-demo-support-summary { grid-template-columns:38px minmax(180px,1fr) minmax(130px,.7fr) auto 28px; }
            .hist-demo-support-summary .hist-demo-support-meta:nth-of-type(3) { grid-column:2/4; }
            .hist-demo-support-detail { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .restr-form { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .restr-checks { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .restr-alert-row { grid-template-columns:1fr; }
            .restr-dashboard-actions { align-items:flex-start; flex-direction:column; }
            .restr-dashboard-buttons { justify-content:flex-start; }
            .restr-dashboard-watermark { right:-8px; }
            .restr-record-summary { grid-template-columns:38px minmax(180px,1fr) minmax(135px,.7fr) auto 28px; }
            .restr-record-summary .restr-record-meta:nth-of-type(3) { grid-column:2/4; }
            .restr-record-detail { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .socio-toolbar { align-items:stretch; flex-direction:column; }
            .socio-actions { justify-content:flex-start; }
        }
        @media (max-width: 768px) {
            .main-wrapper { margin-left:0; width:100%; }
            .content-area { padding:18px 14px 45px; }
            .page-head { align-items:flex-start; flex-direction:column; }
            .med-card-head { align-items:flex-start; flex-direction:column; }
            .med-personnel-top { align-items:flex-start; flex-direction:column; }
            .med-personnel-actions { justify-content:flex-start; flex-wrap:wrap; }
            .med-personnel-body { grid-template-columns:1fr; }
            .med-alert-empty { grid-template-columns:44px minmax(0,1fr); align-items:start; }
            .med-alert-empty-stats,.med-alert-empty-link { grid-column:1/3; }
            .med-alert-empty-link { justify-content:center; }
            .status-pill { align-self:flex-start; }
            .rep-e5-grid { grid-template-columns:1fr; }
            .rep-e5-wide { grid-column:auto; }
            .rep-e5-bar { grid-template-columns:1fr; gap:5px; }
            .socio-filters { flex-direction:column; align-items:stretch; }
            .socio-field { width:100%; min-width:0; }
            .socio-btn { flex:1; justify-content:center; }
            .socio-question-toggle { grid-template-columns:minmax(0,1fr) auto; grid-template-areas:"title actions" "insight insight" "progress progress"; align-items:center; gap:11px; }
            .socio-question-insight { padding:9px 0 0; border-left:0; border-top:1px solid #eef2f7; }
            .socio-dominant-answer small { width:100%; margin-left:0; }
            .socio-detail-head { flex-direction:column; }
            .socio-detail-head span { text-align:left; }
            .socio-detail-grid { grid-template-columns:1fr; }
            .socio-detail-stats { grid-template-columns:repeat(3,minmax(0,1fr)); }
            .answer-row { grid-template-columns:1fr; gap:5px; }
            .answer-percent { text-align:left; }
            .pyp-demo-note { grid-template-columns:auto minmax(0,1fr); }
            .pyp-demo-label { grid-column:2/3; justify-self:start; }
            .pyp-demo-activities-head { align-items:flex-start; flex-direction:column; }
            .pyp-demo-activity { grid-template-columns:auto minmax(0,1fr); align-items:start; }
            .pyp-demo-activity-status,.pyp-demo-activity-progress,.pyp-demo-activity-evidence,.pyp-demo-activity-action { grid-column:2/3; }
        }
        @media (max-width: 560px) {
            .module-tabs, .state-row { grid-template-columns:1fr; }
            .e5-home-hero { padding:17px; }
            .e5-home-badges { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); }
            .e5-home-badge { min-width:0; }
            .e5-module-grid, .socio-grid, .socio-detail-grid { grid-template-columns:1fr; }
            .head-copy { align-items:flex-start; }
            .socio-summary { grid-template-columns:1fr; }
            .socio-question-actions { width:auto; }
            .socio-dominant { text-align:left; }
            .pyp-summary, .pyp-card-metrics, .pyp-card-foot { grid-template-columns:1fr; }
            .cargo-grid-2, .cargo-summary { grid-template-columns:1fr; }
            .tools-check-grid, .tools-groups, .risk-check-grid { grid-template-columns:1fr; }
            .med-summary { grid-template-columns:1fr; }
            .med-demo-summary { grid-template-columns:1fr; }
            .med-demo-note { align-items:flex-start; }
            .med-demo-label { margin-left:0; }
            .med-demo-case { grid-template-columns:40px minmax(0,1fr); gap:9px; }
            .med-demo-meta,.med-demo-progress-box,.med-demo-evidence,.med-demo-action { grid-column:2/3; }
            .med-demo-footer { align-items:flex-start; flex-direction:column; }
            .med-control-access { align-items:flex-start; flex-direction:column; }
            .med-control-access-meta { justify-content:flex-start; }
            .med-control-access-watermark { right:-10px; }
            .med-program-config { grid-template-columns:1fr; }
            .med-program-config .wide { grid-column:auto; }
            .med-worker-search { min-width:0; max-width:none; width:100%; }
            .med-program-toolbar { align-items:stretch; flex-direction:column; }
            .med-worker-item { grid-template-columns:22px minmax(0,1fr); }
            .med-worker-secondary,.med-worker-status { grid-column:2/3; }
            .med-program-footer { align-items:stretch; flex-direction:column; }
            .med-pagination { justify-content:center; }
            .med-page-size { justify-content:center; }
            .med-bulk-submit { justify-content:center; }
            .med-personnel-overview { grid-template-columns:1fr; }
            .med-alert-empty-stats { grid-template-columns:1fr; }
            .med-request-detail { grid-template-columns:1fr; }
            .med-request-detail-box.wide { grid-column:auto; }
            .med-upload-grid { grid-template-columns:1fr; }
            .med-upload-grid .wide { grid-column:auto; }
            .history-upload-form { grid-template-columns:1fr; }
            .hist-demo-center-body { grid-template-columns:1fr; }
            .hist-demo-center-top,.hist-demo-center-footer { align-items:flex-start; flex-direction:column; }
            .hist-demo-support-summary { grid-template-columns:34px minmax(0,1fr) 28px; align-items:start; }
            .hist-demo-support-summary > .hist-demo-support-icon { grid-column:1/2; grid-row:1; }
            .hist-demo-support-summary > .hist-demo-support-copy { grid-column:2/3; grid-row:1; }
            .hist-demo-support-summary > .hist-demo-toggle { grid-column:3/4; grid-row:1; }
            .hist-demo-support-summary > .hist-demo-support-meta,.hist-demo-support-summary > .med-badge { grid-column:2/4; }
            .hist-demo-support-detail { grid-template-columns:1fr; }
            .restr-form, .restr-checks { grid-template-columns:1fr; }
            .restr-form .wide { grid-column:auto; }
            .restr-overview { grid-template-columns:1fr; }
            .restr-record-summary { grid-template-columns:34px minmax(0,1fr) 28px; align-items:start; }
            .restr-record-summary > .restr-record-icon { grid-column:1/2; grid-row:1; }
            .restr-record-summary > .restr-record-copy { grid-column:2/3; grid-row:1; }
            .restr-record-summary > .restr-record-toggle { grid-column:3/4; grid-row:1; }
            .restr-record-summary > .restr-record-meta,.restr-record-summary > .med-badge { grid-column:2/4; }
            .restr-record-detail { grid-template-columns:1fr; }
            .restr-record-data.wide { grid-column:auto; }
            .restr-dashboard-buttons { width:100%; }
            .restr-dashboard-link { flex:1; justify-content:center; }
            .cargo-panel-head, .cargo-item-head { flex-direction:column; }
        }
    </style>
</head>
<body>
<?php include 'components/sidebar.php'; ?>
<main class="main-wrapper">
    <?php include 'components/header.php'; ?>
    <div class="content-area">
        <div class="page-head">
            <div class="head-copy">
                <div class="head-icon"><i class="fa-solid <?php echo $modulo ? htmlspecialchars($modulo['icono']) : 'fa-briefcase-medical'; ?>"></i></div>
                <div>
                    <?php if ($modulo): ?>
                        <h1><?php echo htmlspecialchars($modulo['codigo'] . ' ' . $modulo['titulo']); ?></h1>
                        <p>Vista independiente del submódulo seleccionado.</p>
                    <?php else: ?>
                        <h1>5. Evaluaciones médicas ocupacionales</h1>
                        <p>Selecciona el submódulo que deseas consultar o gestionar.</p>
                    <?php endif; ?>
                </div>
            </div>
            <span class="status-pill"><i class="fa-solid <?php echo $modulo ? 'fa-window-maximize' : 'fa-layer-group'; ?>"></i> <?php echo $modulo ? 'Submódulo' : 'Vista modular'; ?></span>
        </div>

        <?php if (!$modulo): ?>
            <?php
                $total_trabajadores_socio = count($socio_rows);
                $socio_con_datos = 0;
                foreach ($socio_stats as $stat_resumen) {
                    if (!empty($stat_resumen['items'])) {
                        $socio_con_datos++;
                    }
                }
                $pyp_total = (int)($pyp_resumen['total'] ?? 0);
                $pyp_ejecutadas = (int)($pyp_resumen['ejecutadas'] ?? 0);
                $pyp_abiertas = (int)(($pyp_resumen['programadas'] ?? 0) + ($pyp_resumen['en_proceso'] ?? 0));
                $pyp_pct = $pyp_total > 0 ? round(($pyp_ejecutadas / $pyp_total) * 100) : 0;
                $mes_actual_e5 = (int)date('m');
                $anio_actual_e5 = (int)date('Y');
                $eval_mes = 0;
                $eval_realizadas_anio = 0;
                foreach ($eval_solicitudes as $solicitud_e5) {
                    $fecha_sol = !empty($solicitud_e5['creado_en']) ? strtotime((string)$solicitud_e5['creado_en']) : false;
                    if ($fecha_sol && (int)date('m', $fecha_sol) === $mes_actual_e5 && (int)date('Y', $fecha_sol) === $anio_actual_e5) {
                        $eval_mes++;
                    }
                    if (($solicitud_e5['estado'] ?? '') === 'realizada' && $fecha_sol && (int)date('Y', $fecha_sol) === $anio_actual_e5) {
                        $eval_realizadas_anio++;
                    }
                }
                $centros_con_soporte_rep = [];
                $custodias_alerta_rep = 0;
                foreach ($hist_custodias as $custodia_rep) {
                    $centros_con_soporte_rep[(int)$custodia_rep['centro_medico_id']] = true;
                    $status_rep = estandar5_custodia_status($custodia_rep['fecha_emision'] ?? null, $custodia_rep['fecha_renovacion'] ?? null);
                    if (in_array($status_rep['estado'], ['vencido', 'critico', 'sin-fecha'], true)) {
                        $custodias_alerta_rep++;
                    }
                }
                $restr_pendientes = 0;
                $restr_cerradas = 0;
                $cartas_pendientes = 0;
                foreach ($restriccion_rows as $restr_rep) {
                    if (($restr_rep['sst_estado'] ?? '') === 'Cerrado') {
                        $restr_cerradas++;
                    } else {
                        $restr_pendientes++;
                    }
                    if (($restr_rep['carta_firmada'] ?? 'No') !== 'Si') {
                        $cartas_pendientes++;
                    }
                }
                $restr_pct = count($restriccion_rows) > 0 ? round(($restr_cerradas / count($restriccion_rows)) * 100) : 0;
                $socio_pct = count($preguntas_socio) > 0 ? round(($socio_con_datos / count($preguntas_socio)) * 100) : 0;
                $eval_soportes_pct = count($eval_solicitudes) > 0 ? min(100, round((count($eval_soportes) / count($eval_solicitudes)) * 100)) : 0;
                $custodia_pct = count($hist_centros_medicos) > 0 ? min(100, round((count($centros_con_soporte_rep) / count($hist_centros_medicos)) * 100)) : 0;
            ?>
            <section class="e5-home-hero">
                <i class="fa-solid fa-briefcase-medical e5-home-watermark" aria-hidden="true"></i>
                <div class="e5-home-copy">
                    <span class="e5-home-kicker"><i class="fa-solid fa-layer-group"></i> Panel general · Estándar 5</span>
                    <h2>Accesos directos y estado de las evaluaciones médicas ocupacionales</h2>
                    <p>Consulta el resumen de cada submódulo y entra directamente al proceso que necesitas revisar o gestionar.</p>
                </div>
                <div class="e5-home-badges" aria-label="Resumen general">
                    <div class="e5-home-badge"><span>Submódulos</span><strong><?php echo count($submodulos); ?></strong></div>
                    <div class="e5-home-badge"><span>Rol actual</span><strong><?php echo $usuario_rol === 'sst' ? 'Gestión SST' : 'Representante'; ?></strong></div>
                    <div class="e5-home-badge"><span>Actualizado</span><strong><?php echo date('d/m/Y'); ?></strong></div>
                </div>
            </section>

            <section class="e5-module-grid" aria-label="Accesos directos y resumen de submódulos">
                <a class="e5-module-card" style="--module-color:#ea580c;--module-soft:#fff3e8" href="estandar5?modulo=sociodemografica">
                    <i class="fa-solid fa-users-viewfinder e5-module-watermark" aria-hidden="true"></i>
                    <div class="e5-module-head"><span class="e5-module-code">3.1.1</span><span class="e5-module-icon"><i class="fa-solid fa-users-viewfinder"></i></span></div>
                    <h3>Descripción sociodemográfica</h3><p>Consolidado poblacional y diagnóstico general de condiciones de salud.</p>
                    <div class="e5-module-metrics"><div class="e5-module-metric"><span>Encuestados</span><strong><?php echo $total_trabajadores_socio; ?></strong></div><div class="e5-module-metric"><span>Preguntas con datos</span><strong><?php echo $socio_con_datos; ?></strong></div></div>
                    <div class="e5-module-progress"><div class="e5-module-progress-copy"><span>Cobertura del perfil</span><strong><?php echo $socio_pct; ?>%</strong></div><div class="e5-module-track"><div class="e5-module-fill" style="width:<?php echo $socio_pct; ?>%"></div></div></div>
                    <span class="e5-module-link">Abrir módulo <i class="fa-solid fa-arrow-right"></i></span>
                </a>
                <a class="e5-module-card" style="--module-color:#dc2626;--module-soft:#fef2f2" href="estandar5?modulo=promocion-prevencion">
                    <i class="fa-solid fa-heart-pulse e5-module-watermark" aria-hidden="true"></i>
                    <div class="e5-module-head"><span class="e5-module-code">3.1.2</span><span class="e5-module-icon"><i class="fa-solid fa-heart-pulse"></i></span></div>
                    <h3>Promoción y prevención en salud</h3><p>Seguimiento de campañas preventivas conectadas con las actividades SST.</p>
                    <div class="e5-module-metrics"><div class="e5-module-metric"><span>Ejecutadas</span><strong><?php echo $pyp_ejecutadas; ?></strong></div><div class="e5-module-metric"><span>Abiertas</span><strong><?php echo $pyp_abiertas; ?></strong></div></div>
                    <div class="e5-module-progress"><div class="e5-module-progress-copy"><span>Avance PyP</span><strong><?php echo $pyp_pct; ?>%</strong></div><div class="e5-module-track"><div class="e5-module-fill" style="width:<?php echo $pyp_pct; ?>%"></div></div></div>
                    <span class="e5-module-link">Abrir módulo <i class="fa-solid fa-arrow-right"></i></span>
                </a>
                <a class="e5-module-card" style="--module-color:#2563eb;--module-soft:#eff6ff" href="estandar5?modulo=perfiles-cargo">
                    <i class="fa-solid fa-user-doctor e5-module-watermark" aria-hidden="true"></i>
                    <div class="e5-module-head"><span class="e5-module-code">3.1.3</span><span class="e5-module-icon"><i class="fa-solid fa-user-doctor"></i></span></div>
                    <h3>Perfiles de cargo para el médico</h3><p>Funciones, peligros e insumos entregados para orientar los exámenes médicos.</p>
                    <div class="e5-module-metrics"><div class="e5-module-metric"><span>Perfiles</span><strong><?php echo count($perfiles_cargo); ?></strong></div><div class="e5-module-metric"><span>Centros médicos</span><strong><?php echo count($centros_medicos); ?></strong></div></div>
                    <span class="e5-module-link">Abrir módulo <i class="fa-solid fa-arrow-right"></i></span>
                </a>
                <a class="e5-module-card" style="--module-color:#059669;--module-soft:#ecfdf5" href="estandar5?modulo=evaluaciones-medicas">
                    <i class="fa-solid fa-notes-medical e5-module-watermark" aria-hidden="true"></i>
                    <div class="e5-module-head"><span class="e5-module-code">3.1.4</span><span class="e5-module-icon"><i class="fa-solid fa-notes-medical"></i></span></div>
                    <h3>Evaluaciones médicas ocupacionales</h3><p>Programaciones, realización, soportes y alertas de los exámenes ocupacionales.</p>
                    <div class="e5-module-metrics"><div class="e5-module-metric"><span>Solicitudes</span><strong><?php echo count($eval_solicitudes); ?></strong></div><div class="e5-module-metric"><span>Alertas</span><strong><?php echo count($eval_alertas); ?></strong></div></div>
                    <div class="e5-module-progress"><div class="e5-module-progress-copy"><span>Solicitudes con soporte</span><strong><?php echo $eval_soportes_pct; ?>%</strong></div><div class="e5-module-track"><div class="e5-module-fill" style="width:<?php echo $eval_soportes_pct; ?>%"></div></div></div>
                    <span class="e5-module-link">Abrir módulo <i class="fa-solid fa-arrow-right"></i></span>
                </a>
                <a class="e5-module-card" style="--module-color:#7c3aed;--module-soft:#f5f3ff" href="estandar5?modulo=historias-clinicas">
                    <i class="fa-solid fa-folder-open e5-module-watermark" aria-hidden="true"></i>
                    <div class="e5-module-head"><span class="e5-module-code">3.1.5</span><span class="e5-module-icon"><i class="fa-solid fa-folder-open"></i></span></div>
                    <h3>Custodia de historias clínicas</h3><p>Control documental de la custodia realizada por entidades competentes.</p>
                    <div class="e5-module-metrics"><div class="e5-module-metric"><span>Centros con soporte</span><strong><?php echo count($centros_con_soporte_rep); ?></strong></div><div class="e5-module-metric"><span>Alertas</span><strong><?php echo $custodias_alerta_rep; ?></strong></div></div>
                    <div class="e5-module-progress"><div class="e5-module-progress-copy"><span>Cobertura de custodia</span><strong><?php echo $custodia_pct; ?>%</strong></div><div class="e5-module-track"><div class="e5-module-fill" style="width:<?php echo $custodia_pct; ?>%"></div></div></div>
                    <span class="e5-module-link">Abrir módulo <i class="fa-solid fa-arrow-right"></i></span>
                </a>
                <a class="e5-module-card" style="--module-color:#d97706;--module-soft:#fffbeb" href="estandar5?modulo=restricciones">
                    <i class="fa-solid fa-clipboard-list e5-module-watermark" aria-hidden="true"></i>
                    <div class="e5-module-head"><span class="e5-module-code">3.1.6</span><span class="e5-module-icon"><i class="fa-solid fa-clipboard-list"></i></span></div>
                    <h3>Restricciones y recomendaciones</h3><p>Seguimiento médico-laboral, cartas firmadas, acciones pendientes y cierres.</p>
                    <div class="e5-module-metrics"><div class="e5-module-metric"><span>Trabajadores</span><strong><?php echo count($restriccion_rows); ?></strong></div><div class="e5-module-metric"><span>Pendientes</span><strong><?php echo $restr_pendientes; ?></strong></div></div>
                    <div class="e5-module-progress"><div class="e5-module-progress-copy"><span>Seguimiento cerrado</span><strong><?php echo $restr_pct; ?>%</strong></div><div class="e5-module-track"><div class="e5-module-fill" style="width:<?php echo $restr_pct; ?>%"></div></div></div>
                    <span class="e5-module-link">Abrir módulo <i class="fa-solid fa-arrow-right"></i></span>
                </a>
            </section>
        <?php endif; ?>

        <?php if ($modulo_actual === 'sociodemografica'): ?>
            <?php
                $total_trabajadores = count($socio_rows);
                $preguntas_con_datos = 0;
                foreach ($socio_stats as $stat) {
                    if (!empty($stat['items'])) {
                        $preguntas_con_datos++;
                    }
                }
                $query_export = http_build_query([
                    'modulo' => 'sociodemografica',
                    'pregunta' => $pregunta_filtro,
                    'buscar' => $buscar_filtro,
                ]);
                $socio_stats_filtradas = [];
                foreach ($socio_stats as $campo => $stat) {
                    if (empty($stat['items'])) {
                        continue;
                    }
                    if ($pregunta_filtro && $pregunta_filtro !== $campo) {
                        continue;
                    }
                    if ($buscar_filtro !== '') {
                        $hay_busqueda = stripos($stat['label'], $buscar_filtro) !== false;
                        foreach ($stat['items'] as $item_busqueda) {
                            if (stripos($item_busqueda['respuesta'], $buscar_filtro) !== false) {
                                $hay_busqueda = true;
                                break;
                            }
                        }
                        if (!$hay_busqueda) {
                            continue;
                        }
                    }
                    $socio_stats_filtradas[$campo] = $stat;
                }
                $abrir_resultados_socio = $pregunta_filtro !== '' || $buscar_filtro !== '';
            ?>

            <?php if ($usuario_rol === 'representante'): ?>
                <section class="socio-role-note" aria-label="Vista ejecutiva del representante legal">
                    <i class="fa-solid fa-chart-line"></i>
                    <div>
                        <strong>Resumen ejecutivo para Representante Legal</strong>
                        <span>Consulta la composición de la población y despliega solo los porcentajes que necesites revisar. La información es de solo lectura y se consolida con los registros de los trabajadores.</span>
                    </div>
                </section>
            <?php endif; ?>

            <form class="socio-toolbar" method="GET" action="estandar5">
                <input type="hidden" name="modulo" value="sociodemografica">
                <div class="socio-filters">
                    <select class="socio-field" name="pregunta" aria-label="Filtrar por pregunta">
                        <option value="">Todas las preguntas</option>
                        <?php foreach ($preguntas_socio as $campo => $meta): ?>
                            <option value="<?php echo htmlspecialchars($campo); ?>" <?php echo $pregunta_filtro === $campo ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($meta['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input class="socio-field socio-search" type="search" name="buscar" value="<?php echo htmlspecialchars($buscar_filtro); ?>" placeholder="Buscar pregunta o respuesta...">
                    <button class="socio-btn primary" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
                </div>
                <div class="socio-actions">
                    <span class="socio-results-badge"><i class="fa-solid fa-layer-group"></i> <?php echo count($socio_stats_filtradas); ?> resultado(s)</span>
                    <a class="socio-btn" href="estandar5?modulo=sociodemografica"><i class="fa-solid fa-rotate-left"></i> Limpiar</a>
                    <a class="socio-btn" href="estandar5?<?php echo htmlspecialchars($query_export . '&export=excel'); ?>"><i class="fa-solid fa-file-excel"></i> Excel</a>
                    <a class="socio-btn" href="estandar5?<?php echo htmlspecialchars($query_export . '&export=pdf'); ?>"><i class="fa-solid fa-file-pdf"></i> PDF</a>
                </div>
            </form>

            <section class="socio-summary">
                <article class="socio-kpi">
                    <i class="fa-solid fa-users socio-kpi-watermark" aria-hidden="true"></i>
                    <div class="socio-kpi-top">
                        <span class="socio-kpi-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
                        <strong><?php echo $total_trabajadores; ?></strong>
                    </div>
                    <span class="socio-kpi-label">Trabajadores encuestados</span>
                    <small>Registros vinculados a la empresa actual.</small>
                </article>
                <article class="socio-kpi kpi-blue">
                    <i class="fa-solid fa-list-check socio-kpi-watermark" aria-hidden="true"></i>
                    <div class="socio-kpi-top">
                        <span class="socio-kpi-icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span>
                        <strong><?php echo count($preguntas_socio); ?></strong>
                    </div>
                    <span class="socio-kpi-label">Preguntas del perfil</span>
                    <small>Campos del formulario de registro del trabajador.</small>
                </article>
                <article class="socio-kpi kpi-green">
                    <i class="fa-solid fa-chart-column socio-kpi-watermark" aria-hidden="true"></i>
                    <div class="socio-kpi-top">
                        <span class="socio-kpi-icon"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span>
                        <strong><?php echo $preguntas_con_datos; ?></strong>
                    </div>
                    <span class="socio-kpi-label">Preguntas con datos</span>
                    <small>Se calculan porcentajes sobre trabajadores encuestados.</small>
                </article>
                <article class="socio-kpi kpi-violet">
                    <i class="fa-solid fa-eye socio-kpi-watermark" aria-hidden="true"></i>
                    <div class="socio-kpi-top">
                        <span class="socio-kpi-icon"><i class="fa-solid fa-eye" aria-hidden="true"></i></span>
                        <strong class="socio-kpi-value-text"><?php echo $usuario_rol === 'sst' ? 'Gestión' : 'Resumen'; ?></strong>
                    </div>
                    <span class="socio-kpi-label">Vista del rol</span>
                    <small>Información lista para análisis e informe.</small>
                </article>
            </section>

            <?php if ($total_trabajadores === 0): ?>
                <div class="empty-socio">
                    <i class="fa-solid fa-chart-pie"></i>
                    <strong>No hay encuestas sociodemográficas registradas.</strong>
                    <p>Cuando los trabajadores completen el registro, aquí aparecerán los porcentajes por pregunta.</p>
                </div>
            <?php elseif (empty($socio_stats_filtradas)): ?>
                <div class="empty-socio">
                    <i class="fa-solid fa-filter-circle-xmark"></i>
                    <strong>No encontramos resultados con esos filtros.</strong>
                    <p>Prueba otra pregunta o limpia el texto de búsqueda para volver a ver el consolidado.</p>
                </div>
            <?php else: ?>
                <section class="socio-grid" aria-label="Resumen desplegable del perfil sociodemográfico">
                    <?php foreach ($socio_stats_filtradas as $campo => $stat): ?>
                        <?php
                            $items_grafica = array_slice($stat['items'], 0, 5);
                            $items_detalle = $stat['items'];
                            if ($buscar_filtro !== '' && stripos($stat['label'], $buscar_filtro) === false) {
                                $items_detalle = array_values(array_filter($items_detalle, function ($item) use ($buscar_filtro) {
                                    return stripos($item['respuesta'], $buscar_filtro) !== false;
                                }));
                            }
                            $ranking_respuestas = array_column($stat['items'], 'respuesta');
                            $porcentaje_principal_card = (float)$stat['principal']['porcentaje'];
                            $otras_respuestas_card = max(0, (int)$stat['total'] - (int)$stat['principal']['cantidad']);
                            if ($porcentaje_principal_card >= 50) {
                                $nivel_principal_card = 'Mayoritaria';
                            } elseif ($porcentaje_principal_card >= 30) {
                                $nivel_principal_card = 'Alta';
                            } elseif ($porcentaje_principal_card >= 15) {
                                $nivel_principal_card = 'Media';
                            } else {
                                $nivel_principal_card = 'Baja';
                            }
                            $panel_grafica_id = 'socio-distribution-' . preg_replace('/[^a-z0-9_-]/i', '-', $campo);
                        ?>
                        <article class="socio-question <?php echo $abrir_resultados_socio ? 'is-open' : ''; ?>" data-disclosure data-question="<?php echo htmlspecialchars($campo); ?>">
                            <i class="fa-solid <?php echo htmlspecialchars($stat['icon']); ?> socio-question-watermark" aria-hidden="true"></i>
                            <button class="socio-question-toggle" type="button" data-disclosure-toggle aria-expanded="<?php echo $abrir_resultados_socio ? 'true' : 'false'; ?>" aria-controls="<?php echo htmlspecialchars($panel_grafica_id); ?>">
                                <span class="socio-question-title">
                                    <i class="fa-solid <?php echo htmlspecialchars($stat['icon']); ?>"></i>
                                    <span class="socio-question-copy">
                                        <strong><?php echo htmlspecialchars($stat['label']); ?></strong>
                                        <small><?php echo $stat['total']; ?> respuesta(s) analizadas</small>
                                    </span>
                                </span>
                                <span class="socio-question-insight">
                                    <small>Respuesta principal</small>
                                    <strong title="<?php echo htmlspecialchars($stat['principal']['respuesta']); ?>"><?php echo htmlspecialchars($stat['principal']['respuesta']); ?></strong>
                                    <span class="socio-question-insight-meta"><i class="fa-solid fa-user-group" aria-hidden="true"></i> <?php echo (int)$stat['principal']['cantidad']; ?> trabajador(es)</span>
                                </span>
                                <span class="socio-question-progress">
                                    <span class="socio-question-progress-head">
                                        <span>Participación <?php echo htmlspecialchars(strtolower($nivel_principal_card)); ?></span>
                                        <strong><?php echo htmlspecialchars((string)$stat['principal']['porcentaje']); ?>%</strong>
                                    </span>
                                    <span class="socio-question-preview-track" aria-hidden="true"><span class="socio-question-preview-fill" style="width:<?php echo htmlspecialchars((string)$stat['principal']['porcentaje']); ?>%;"></span></span>
                                    <span class="socio-question-preview-meta">
                                        <span><i class="fa-solid fa-list" aria-hidden="true"></i> <?php echo count($stat['items']); ?> opción(es)</span>
                                        <span><i class="fa-solid fa-users" aria-hidden="true"></i> <?php echo $otras_respuestas_card; ?> en otras respuestas</span>
                                    </span>
                                </span>
                                <span class="socio-question-actions">
                                    <span class="socio-expand-label"><span class="when-closed">Ver análisis</span><span class="when-open">Ocultar</span></span>
                                    <i class="fa-solid fa-chevron-down socio-chevron" aria-hidden="true"></i>
                                </span>
                            </button>
                            <div class="socio-distribution" id="<?php echo htmlspecialchars($panel_grafica_id); ?>" aria-hidden="<?php echo $abrir_resultados_socio ? 'false' : 'true'; ?>">
                                <div class="socio-dominant-answer">
                                    <span>Respuesta predominante</span>
                                    <strong><?php echo htmlspecialchars($stat['principal']['respuesta']); ?></strong>
                                    <small><?php echo (int)$stat['principal']['cantidad']; ?> de <?php echo (int)$stat['total']; ?> trabajador(es)</small>
                                </div>
                                <div class="answer-bars">
                                    <?php foreach ($items_grafica as $item): ?>
                                        <div class="answer-row">
                                            <div class="answer-name" title="<?php echo htmlspecialchars($item['respuesta']); ?>"><?php echo htmlspecialchars($item['respuesta']); ?></div>
                                            <div class="answer-track"><div class="answer-fill" style="width: <?php echo htmlspecialchars((string)$item['porcentaje']); ?>%;"></div></div>
                                            <div class="answer-percent"><?php echo htmlspecialchars((string)$item['porcentaje']); ?>%</div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="socio-detail">
                                    <div class="socio-detail-head">
                                        <strong><i class="fa-solid fa-chart-simple" aria-hidden="true"></i> Detalle consolidado</strong>
                                        <span><?php echo count($items_detalle); ?> opción(es) · Ranking, participación y comparación sobre trabajadores encuestados</span>
                                    </div>
                                    <div class="socio-detail-grid">
                                        <?php foreach ($items_detalle as $item): ?>
                                            <?php
                                                $posicion_en_ranking = array_search($item['respuesta'], $ranking_respuestas, true);
                                                $posicion_en_ranking = $posicion_en_ranking === false ? 0 : $posicion_en_ranking + 1;
                                                $porcentaje_item = (float)$item['porcentaje'];
                                                $porcentaje_principal = (float)$stat['principal']['porcentaje'];
                                                $diferencia_principal = round(max(0, $porcentaje_principal - $porcentaje_item), 1);
                                                $restantes_item = max(0, (int)$stat['total'] - (int)$item['cantidad']);
                                                $es_principal = $item['respuesta'] === $stat['principal']['respuesta'];
                                                if ($porcentaje_item >= 50) {
                                                    $nivel_participacion = 'Mayoritaria';
                                                } elseif ($porcentaje_item >= 30) {
                                                    $nivel_participacion = 'Alta';
                                                } elseif ($porcentaje_item >= 15) {
                                                    $nivel_participacion = 'Media';
                                                } else {
                                                    $nivel_participacion = 'Baja';
                                                }
                                            ?>
                                            <article class="socio-detail-item <?php echo $es_principal ? 'is-principal' : ''; ?>">
                                                <div class="socio-detail-badges">
                                                    <span class="socio-detail-badge">#<?php echo $posicion_en_ranking; ?> en respuestas</span>
                                                    <span class="socio-detail-badge level">Participación <?php echo htmlspecialchars(strtolower($nivel_participacion)); ?></span>
                                                    <?php if ($es_principal): ?><span class="socio-detail-badge principal"><i class="fa-solid fa-crown"></i>&nbsp; Principal</span><?php endif; ?>
                                                </div>
                                                <div class="socio-detail-item-top">
                                                    <span class="socio-detail-item-name"><?php echo htmlspecialchars($item['respuesta']); ?></span>
                                                    <strong class="socio-detail-item-percent"><?php echo htmlspecialchars((string)$item['porcentaje']); ?>%</strong>
                                                </div>
                                                <div class="socio-detail-stats">
                                                    <div class="socio-detail-stat"><span>Participan</span><strong><?php echo (int)$item['cantidad']; ?> trabajador(es)</strong></div>
                                                    <div class="socio-detail-stat"><span>Otras respuestas</span><strong><?php echo $restantes_item; ?> trabajador(es)</strong></div>
                                                    <div class="socio-detail-stat"><span>Brecha con líder</span><strong><?php echo $es_principal ? 'Es líder' : htmlspecialchars((string)$diferencia_principal) . ' pp'; ?></strong></div>
                                                </div>
                                                <div class="socio-detail-mini-track" aria-hidden="true">
                                                    <div class="socio-detail-mini-fill" style="width:<?php echo htmlspecialchars((string)$item['porcentaje']); ?>%;"></div>
                                                </div>
                                                <div class="socio-detail-reading">
                                                    <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
                                                    <span><?php echo $es_principal
                                                        ? 'Es la respuesta con mayor participación dentro de esta pregunta.'
                                                        : ($diferencia_principal > 0
                                                            ? 'Se encuentra a ' . htmlspecialchars((string)$diferencia_principal) . ' puntos porcentuales de la respuesta principal.'
                                                            : 'Empata porcentualmente con la respuesta principal.'); ?></span>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        <?php elseif ($modulo_actual === 'promocion-prevencion'): ?>
            <?php
                $pyp_modo_demo = false;
                $pyp_demo_categorias = [
                    [
                        'titulo' => 'Estilos de vida saludable',
                        'icon' => 'fa-person-running',
                        'descripcion' => 'Hábitos protectores, pausas activas, alimentación y ejercicio.',
                        'total' => 2, 'abiertas' => 1, 'ejecutadas' => 1, 'alcance' => 54,
                        'avance' => 78, 'ultima' => 'Jornada de hábitos saludables', 'proxima' => 'Reto de pausas activas',
                    ],
                    [
                        'titulo' => 'Bienestar emocional y mental',
                        'icon' => 'fa-brain',
                        'descripcion' => 'Manejo del estrés, equilibrio emocional y rutas de apoyo.',
                        'total' => 2, 'abiertas' => 1, 'ejecutadas' => 1, 'alcance' => 38,
                        'avance' => 64, 'ultima' => 'Taller de manejo del estrés', 'proxima' => 'Cápsula de salud mental',
                    ],
                    [
                        'titulo' => 'Prevención consumo de sustancias',
                        'icon' => 'fa-ban-smoking',
                        'descripcion' => 'Prevención de alcohol, tabaco, drogas y automedicación.',
                        'total' => 1, 'abiertas' => 1, 'ejecutadas' => 0, 'alcance' => 24,
                        'avance' => 25, 'ultima' => 'Sin actividad ejecutada', 'proxima' => 'Campaña de prevención integral',
                    ],
                    [
                        'titulo' => 'Controles médicos periódicos',
                        'icon' => 'fa-stethoscope',
                        'descripcion' => 'Autocuidado y seguimiento preventivo de condiciones de salud.',
                        'total' => 1, 'abiertas' => 0, 'ejecutadas' => 1, 'alcance' => 31,
                        'avance' => 100, 'ultima' => 'Jornada de tamizaje preventivo', 'proxima' => 'Sin próxima actividad',
                    ],
                ];
                $pyp_demo_actividades = [
                    [
                        'nombre' => 'Jornada de hábitos saludables', 'categoria' => 'Estilos de vida saludable',
                        'icon' => 'fa-apple-whole', 'fecha' => date('d/m/Y', strtotime('-12 days')), 'modalidad' => 'Presencial',
                        'estado_key' => 'ejecutada', 'estado' => 'Ejecutada', 'alcance' => 42, 'completaron' => 37,
                        'avance' => 88, 'actas' => 3, 'soporte' => 'Evidencias completas',
                    ],
                    [
                        'nombre' => 'Taller de manejo del estrés', 'categoria' => 'Bienestar emocional y mental',
                        'icon' => 'fa-brain', 'fecha' => date('d/m/Y', strtotime('+3 days')), 'modalidad' => 'Virtual',
                        'estado_key' => 'en_proceso', 'estado' => 'En proceso', 'alcance' => 28, 'completaron' => 18,
                        'avance' => 64, 'actas' => 1, 'soporte' => 'Evaluación activa',
                    ],
                    [
                        'nombre' => 'Campaña de prevención integral', 'categoria' => 'Prevención consumo de sustancias',
                        'icon' => 'fa-shield-heart', 'fecha' => date('d/m/Y', strtotime('+14 days')), 'modalidad' => 'Mixta',
                        'estado_key' => 'programada', 'estado' => 'Programada', 'alcance' => 54, 'completaron' => 0,
                        'avance' => 0, 'actas' => 0, 'soporte' => 'Pendiente de ejecución',
                    ],
                ];
                $pyp_total_mostrar = (int)($pyp_resumen['total'] ?? 0);
                $pyp_abiertas_mostrar = (int)(($pyp_resumen['programadas'] ?? 0) + ($pyp_resumen['en_proceso'] ?? 0));
                $pyp_ejecutadas_mostrar = (int)($pyp_resumen['ejecutadas'] ?? 0);
                $pyp_alcance_mostrar = (int)($pyp_resumen['personas_alcance'] ?? 0);
            ?>
            <section class="pyp-hero">
                <div>
                    <h2>Resumen conectado de campañas PyP en salud</h2>
                    <p>Esta vista lee las actividades creadas en Capacitación SST como <strong>Campaña PyP en Salud</strong> y consolida estado, alcance, evaluación y evidencias por categoría.</p>
                </div>
                <div class="pyp-hero-actions">
                    <a class="socio-btn" href="estandar3.php"><i class="fa-solid fa-list-check"></i> Ver capacitación</a>
                    <?php if ($usuario_rol === 'sst'): ?>
                        <a class="socio-btn primary" href="nueva_actividad.php"><i class="fa-solid fa-plus"></i> Nueva campaña</a>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($pyp_modo_demo): ?>
                <div class="pyp-demo-note">
                    <span class="pyp-demo-note-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                    <div>
                        <strong>Vista demostrativa activa</strong>
                        <p>Estos datos son ejemplos visuales y no se guardaron en la base de datos. Al registrar tu primera campaña PyP serán reemplazados automáticamente por información real.</p>
                    </div>
                    <span class="pyp-demo-label">Datos de muestra</span>
                </div>
            <?php endif; ?>

            <section class="pyp-summary">
                <article class="pyp-kpi">
                    <i class="fa-solid fa-bullhorn pyp-kpi-watermark" aria-hidden="true"></i>
                    <span>Campañas PyP</span>
                    <strong><?php echo $pyp_total_mostrar; ?></strong>
                    <small>Registros sincronizados desde actividades de capacitación.</small>
                </article>
                <article class="pyp-kpi blue">
                    <i class="fa-solid fa-calendar-days pyp-kpi-watermark" aria-hidden="true"></i>
                    <span>Programadas / proceso</span>
                    <strong><?php echo $pyp_abiertas_mostrar; ?></strong>
                    <small>Acciones abiertas para seguimiento del Responsable SST.</small>
                </article>
                <article class="pyp-kpi green">
                    <i class="fa-solid fa-circle-check pyp-kpi-watermark" aria-hidden="true"></i>
                    <span>Ejecutadas</span>
                    <strong><?php echo $pyp_ejecutadas_mostrar; ?></strong>
                    <small>Actividades cerradas o marcadas como completadas.</small>
                </article>
                <article class="pyp-kpi violet">
                    <i class="fa-solid fa-users pyp-kpi-watermark" aria-hidden="true"></i>
                    <span>Alcance estimado</span>
                    <strong><?php echo $pyp_alcance_mostrar; ?></strong>
                    <small>Personas asignadas o trabajadores activos si aplica a toda la empresa.</small>
                </article>
            </section>

            <?php if (empty($pyp_rows)): ?>
                <section class="pyp-empty-state">
                    <span class="pyp-empty-icon"><i class="fa-solid fa-bullhorn"></i></span>
                    <h3>Aún no hay campañas PyP registradas</h3>
                    <p>Cuando crees una actividad de tipo Campaña PyP en Salud aparecerán aquí sus indicadores, categorías, participación y evidencias reales.</p>
                    <div class="pyp-empty-actions">
                        <a class="socio-btn" href="estandar3"><i class="fa-solid fa-list-check"></i> Ver capacitación</a>
                        <?php if ($usuario_rol === 'sst'): ?><a class="socio-btn primary" href="nueva_actividad"><i class="fa-solid fa-plus"></i> Crear primera campaña</a><?php endif; ?>
                    </div>
                </section>
                <?php if (false): ?>
                <section class="pyp-category-grid pyp-demo-category-grid" aria-label="Ejemplo de resumen por categoría PyP">
                    <?php foreach ($pyp_demo_categorias as $categoria_demo): ?>
                        <article class="pyp-category-card pyp-demo-category-card">
                            <span class="pyp-demo-corner">Ejemplo</span>
                            <div class="pyp-category-head">
                                <div class="pyp-icon"><i class="fa-solid <?php echo htmlspecialchars($categoria_demo['icon']); ?>"></i></div>
                                <div>
                                    <h3><?php echo htmlspecialchars($categoria_demo['titulo']); ?></h3>
                                    <p><?php echo htmlspecialchars($categoria_demo['descripcion']); ?></p>
                                </div>
                            </div>
                            <div class="pyp-card-metrics">
                                <div><span>Total</span><strong><?php echo (int)$categoria_demo['total']; ?></strong></div>
                                <div><span>Abiertas</span><strong><?php echo (int)$categoria_demo['abiertas']; ?></strong></div>
                                <div><span>Ejecutadas</span><strong><?php echo (int)$categoria_demo['ejecutadas']; ?></strong></div>
                                <div><span>Alcance</span><strong><?php echo (int)$categoria_demo['alcance']; ?></strong></div>
                            </div>
                            <div class="pyp-demo-category-progress">
                                <div><span>Avance de participación</span><strong><?php echo (int)$categoria_demo['avance']; ?>%</strong></div>
                                <div class="pyp-progress-track"><div class="pyp-progress-fill" style="width:<?php echo (int)$categoria_demo['avance']; ?>%"></div></div>
                            </div>
                            <div class="pyp-card-foot">
                                <div><span>Última actividad</span><b><?php echo htmlspecialchars($categoria_demo['ultima']); ?></b></div>
                                <div><span>Próxima actividad</span><b><?php echo htmlspecialchars($categoria_demo['proxima']); ?></b></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>

                <section class="pyp-demo-activities">
                    <div class="pyp-demo-activities-head">
                        <div>
                            <span class="pyp-demo-eyebrow">Vista operativa de ejemplo</span>
                            <h2>Seguimiento de campañas</h2>
                            <p>Así podrías revisar rápidamente ejecución, alcance, avance y evidencias sin entrar a cada actividad.</p>
                        </div>
                        <span class="pyp-demo-count"><i class="fa-solid fa-bullhorn"></i> <?php echo count($pyp_demo_actividades); ?> actividades</span>
                    </div>
                    <div class="pyp-demo-activity-list">
                        <?php foreach ($pyp_demo_actividades as $actividad_demo): ?>
                            <article class="pyp-demo-activity">
                                <div class="pyp-demo-activity-icon"><i class="fa-solid <?php echo htmlspecialchars($actividad_demo['icon']); ?>"></i></div>
                                <div class="pyp-demo-activity-copy">
                                    <span><?php echo htmlspecialchars($actividad_demo['categoria']); ?></span>
                                    <strong><?php echo htmlspecialchars($actividad_demo['nombre']); ?></strong>
                                    <small><i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($actividad_demo['fecha']); ?> · <?php echo htmlspecialchars($actividad_demo['modalidad']); ?></small>
                                </div>
                                <div class="pyp-demo-activity-status">
                                    <span class="pyp-badge <?php echo htmlspecialchars($actividad_demo['estado_key']); ?>"><i class="fa-solid fa-circle"></i> <?php echo htmlspecialchars($actividad_demo['estado']); ?></span>
                                    <small><?php echo (int)$actividad_demo['alcance']; ?> personas de alcance</small>
                                </div>
                                <div class="pyp-demo-activity-progress">
                                    <div><span>Participación</span><strong><?php echo (int)$actividad_demo['avance']; ?>%</strong></div>
                                    <div class="pyp-progress-track"><div class="pyp-progress-fill" style="width:<?php echo (int)$actividad_demo['avance']; ?>%"></div></div>
                                    <small><?php echo (int)$actividad_demo['completaron']; ?> de <?php echo (int)$actividad_demo['alcance']; ?> completaron</small>
                                </div>
                                <div class="pyp-demo-activity-evidence">
                                    <span><i class="fa-solid fa-paperclip"></i> <?php echo htmlspecialchars($actividad_demo['soporte']); ?></span>
                                    <small><?php echo (int)$actividad_demo['actas']; ?> acta(s) registrada(s)</small>
                                </div>
                                <div class="pyp-demo-activity-action">
                                    <a class="pyp-campaign-link" href="estandar3" title="Abrir el panel de capacitaciones"><i class="fa-solid fa-arrow-up-right-from-square"></i> Ver detalle</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
            <?php else: ?>
                <section class="pyp-category-grid" aria-label="Resumen por categoría PyP">
                    <?php foreach (($pyp_resumen['categorias'] ?? []) as $categoria => $info): ?>
                        <?php
                            $meta = $info['meta'];
                            $ultima = $info['ultima'];
                            $proxima = $info['proxima'];
                        ?>
                        <article class="pyp-category-card">
                            <div class="pyp-category-head">
                                <div class="pyp-icon"><i class="fa-solid <?php echo htmlspecialchars($meta['icon']); ?>"></i></div>
                                <div>
                                    <h3><?php echo htmlspecialchars($meta['titulo']); ?></h3>
                                    <p><?php echo htmlspecialchars($meta['descripcion']); ?></p>
                                </div>
                            </div>
                            <div class="pyp-card-metrics">
                                <div><span>Total</span><strong><?php echo (int)$info['total']; ?></strong></div>
                                <div><span>Abiertas</span><strong><?php echo (int)($info['programadas'] + $info['en_proceso']); ?></strong></div>
                                <div><span>Ejecutadas</span><strong><?php echo (int)$info['ejecutadas']; ?></strong></div>
                                <div><span>Alcance</span><strong><?php echo (int)$info['personas_alcance']; ?></strong></div>
                            </div>
                            <div class="pyp-card-foot">
                                <div>
                                    <b><?php echo $ultima ? htmlspecialchars($ultima['nombre_actividad']) : 'Sin actividad'; ?></b>
                                    Última: <?php echo $ultima ? htmlspecialchars(estandar5_pyp_fecha($ultima['fecha_inicio'])) : 'Sin fecha'; ?>
                                </div>
                                <div>
                                    <b><?php echo $proxima ? htmlspecialchars($proxima['nombre_actividad']) : 'Sin próxima'; ?></b>
                                    Próxima: <?php echo $proxima ? htmlspecialchars(estandar5_pyp_fecha($proxima['fecha_inicio'])) : 'Sin fecha'; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>

                <section class="pyp-table-card">
                    <div class="pyp-table-head">
                        <div>
                            <h2>Actividades PyP registradas</h2>
                            <p>Detalle operativo tomado de la misma base usada por el módulo de capacitación.</p>
                        </div>
                        <i class="fa-solid fa-table-list" style="color:var(--primary2);"></i>
                    </div>
                    <div class="pyp-table-wrap">
                        <table class="pyp-table">
                            <thead>
                                <tr>
                                    <th>Actividad</th>
                                    <th>Categoría</th>
                                    <th>Fecha</th>
                                    <th>Modalidad</th>
                                    <th>Dirigido a</th>
                                    <th>Estado</th>
                                    <th>Seguimiento</th>
                                    <th>Soporte</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pyp_rows as $row_original): ?>
                                    <?php
                                        $estado = estandar5_pyp_estado($row_original);
                                        $alcance = estandar5_pyp_alcance($row_original, $pyp_total_trabajadores);
                                        $completaron = (int)($row_original['trabajadores_completaron'] ?? 0);
                                        $avance = $alcance > 0 ? round(($completaron / $alcance) * 100) : 0;
                                        $tiene_evaluacion = !empty($row_original['curso_id']);
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="pyp-activity-name"><?php echo htmlspecialchars($row_original['nombre_actividad']); ?></span>
                                            <?php if (!empty($row_original['descripcion'])): ?>
                                                <span class="pyp-activity-desc"><?php echo htmlspecialchars($row_original['descripcion']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row_original['categoria']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars(estandar5_pyp_fecha($row_original['fecha_inicio'])); ?><br>
                                            <small>Fin: <?php echo htmlspecialchars(estandar5_pyp_fecha($row_original['fecha_fin'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($row_original['modalidad'] ?: 'Sin dato'); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row_original['dirigido_a'] ?: 'Sin dato'); ?><br>
                                            <small><?php echo (int)$alcance; ?> persona(s) de alcance</small>
                                        </td>
                                        <td>
                                            <span class="pyp-badge <?php echo htmlspecialchars($estado['key']); ?>">
                                                <i class="fa-solid fa-circle"></i><?php echo htmlspecialchars($estado['label']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="pyp-progress">
                                                <?php echo $tiene_evaluacion ? 'Curso/evaluación activa' : 'Sin evaluación'; ?><br>
                                                <small><?php echo $completaron; ?> completado(s) / <?php echo (int)$alcance; ?></small>
                                                <div class="pyp-progress-track"><div class="pyp-progress-fill" style="width: <?php echo (int)min(100, $avance); ?>%;"></div></div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo (int)($row_original['actas_firmadas'] ?? 0); ?> acta(s)<br>
                                            <small><?php echo !empty($row_original['enlace_reunion']) ? 'Enlace registrado' : 'Sin enlace'; ?></small>
                                        </td>
                                        <td>
                                            <a class="pyp-campaign-link" href="estandar3?mes=<?php echo urlencode(date('m', strtotime((string)$row_original['fecha_inicio']))); ?>&amp;anio=<?php echo urlencode(date('Y', strtotime((string)$row_original['fecha_inicio']))); ?>&amp;actividad=<?php echo (int)$row_original['id']; ?>#actividad-<?php echo (int)$row_original['id']; ?>"><i class="fa-solid fa-arrow-up-right-from-square"></i> Ver detalle</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
        <?php elseif ($modulo_actual === 'perfiles-cargo'): ?>
            <?php
                $msg = trim((string)($_GET['msg'] ?? ''));
                $tipo_msg = ($_GET['tipo'] ?? 'ok') === 'error' ? 'error' : 'ok';
            ?>
            <?php if ($msg !== ''): ?>
                <div class="cargo-alert <?php echo htmlspecialchars($tipo_msg); ?>">
                    <i class="fa-solid <?php echo $tipo_msg === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
                    <span><?php echo htmlspecialchars($msg); ?></span>
                </div>
            <?php endif; ?>

            <?php
                $centros_con_licencia = 0;
                foreach ($centros_medicos as $centro_panel) {
                    if (!empty($centro_panel['licencia_sst_archivo']) || !empty($centro_panel['licencia_funcionamiento_archivo'])) {
                        $centros_con_licencia++;
                    }
                }
                $perfiles_con_centro = 0;
                $total_tareas_perfil = 0;
                $total_riesgos_perfil = 0;
                $total_herramientas_perfil = 0;
                foreach ($perfiles_cargo as $perfil_panel) {
                    if (!empty($perfil_panel['centro_medico_id'])) $perfiles_con_centro++;
                    $total_tareas_perfil += count(estandar5_decode_list($perfil_panel['tareas_json'] ?? ''));
                    $total_riesgos_perfil += count(estandar5_decode_list($perfil_panel['tareas_alto_riesgo_json'] ?? ''));
                    $total_herramientas_perfil += count(estandar5_decode_list($perfil_panel['herramientas_json'] ?? ''));
                }
                $cobertura_centros = count($perfiles_cargo) > 0 ? (int)round(($perfiles_con_centro / count($perfiles_cargo)) * 100) : 0;
            ?>

            <section class="pc-summary" aria-label="Resumen de perfiles de cargo">
                <article class="pc-kpi"><i class="fa-solid fa-hospital pc-kpi-watermark"></i><div class="pc-kpi-top"><div class="pc-kpi-icon"><i class="fa-solid fa-hospital"></i></div><strong><?php echo count($centros_medicos); ?></strong></div><span>Centros médicos</span><small><?php echo $centros_con_licencia; ?> con soporte de licencia cargado.</small></article>
                <article class="pc-kpi blue"><i class="fa-solid fa-diagram-project pc-kpi-watermark"></i><div class="pc-kpi-top"><div class="pc-kpi-icon"><i class="fa-solid fa-diagram-project"></i></div><strong><?php echo count($procesos_perfil); ?></strong></div><span>Procesos disponibles</span><small>Clasificaciones reutilizables para nuevos cargos.</small></article>
                <article class="pc-kpi green"><i class="fa-solid fa-user-gear pc-kpi-watermark"></i><div class="pc-kpi-top"><div class="pc-kpi-icon"><i class="fa-solid fa-user-gear"></i></div><strong><?php echo count($perfiles_cargo); ?></strong></div><span>Perfiles creados</span><small><?php echo $total_tareas_perfil; ?> tareas y <?php echo $total_riesgos_perfil; ?> exposiciones de alto riesgo.</small></article>
                <article class="pc-kpi violet"><i class="fa-solid fa-link pc-kpi-watermark"></i><div class="pc-kpi-top"><div class="pc-kpi-icon"><i class="fa-solid fa-link"></i></div><strong><?php echo $cobertura_centros; ?>%</strong></div><span>Cobertura de asignación</span><small><?php echo $perfiles_con_centro; ?> de <?php echo count($perfiles_cargo); ?> perfiles tienen centro asociado.</small></article>
            </section>

            <?php if ($usuario_rol === 'sst'): ?>
                <section class="pc-actions" aria-label="Acciones del flujo">
                    <a class="pc-action-card" href="nuevo_centro_medico"><i class="fa-solid fa-hospital-user pc-action-watermark"></i><div class="pc-action-top"><div class="pc-action-icon"><i class="fa-solid fa-hospital-user"></i></div><span class="pc-action-count">Paso 1</span></div><h3>Registrar centro médico</h3><p>Guarda datos de contacto, sedes y licencias del prestador autorizado.</p><span class="pc-action-link">Abrir formulario <i class="fa-solid fa-arrow-right"></i></span></a>
                    <a class="pc-action-card blue" href="nuevo_proceso_perfil"><i class="fa-solid fa-sitemap pc-action-watermark"></i><div class="pc-action-top"><div class="pc-action-icon"><i class="fa-solid fa-sitemap"></i></div><span class="pc-action-count">Paso 2</span></div><h3>Crear proceso</h3><p>Organiza los cargos con procesos que podrás reutilizar en futuras creaciones.</p><span class="pc-action-link">Abrir formulario <i class="fa-solid fa-arrow-right"></i></span></a>
                    <a class="pc-action-card green" href="nuevo_perfil_cargo"><i class="fa-solid fa-user-plus pc-action-watermark"></i><div class="pc-action-top"><div class="pc-action-icon"><i class="fa-solid fa-user-plus"></i></div><span class="pc-action-count">Paso 3</span></div><h3>Crear perfil de cargo</h3><p>Define funciones, herramientas, riesgos y el centro que recibirá la información.</p><span class="pc-action-link">Abrir formulario <i class="fa-solid fa-arrow-right"></i></span></a>
                </section>
            <?php endif; ?>

            <section class="pc-section">
                <div class="pc-section-head"><div><h2>Centros médicos autorizados</h2><p>Prestadores disponibles para asociar a los perfiles.</p></div><?php if ($usuario_rol === 'sst'): ?><a href="nuevo_centro_medico"><i class="fa-solid fa-plus"></i> Agregar centro</a><?php endif; ?></div>
                <?php if (empty($centros_medicos)): ?><div class="pc-empty"><i class="fa-solid fa-hospital-user"></i><strong>No hay centros registrados.</strong><p>Crea el primero desde la card de acceso.</p></div><?php else: ?>
                    <div class="pc-card-list"><?php foreach ($centros_medicos as $centro): ?><?php $sedes_panel = estandar5_decode_list($centro['sedes_json'] ?? ''); ?><article class="pc-record"><div class="pc-record-head"><div class="pc-record-icon"><i class="fa-solid fa-hospital"></i></div><div class="pc-record-title"><h3><?php echo htmlspecialchars($centro['nombre']); ?></h3><p>NIT <?php echo htmlspecialchars($centro['nit']); ?> · <?php echo htmlspecialchars($centro['direccion_principal']); ?></p><p><?php echo htmlspecialchars($centro['telefono']); ?> · <?php echo htmlspecialchars($centro['correo']); ?></p></div></div><div class="pc-record-tags"><span class="pc-tag"><i class="fa-solid fa-location-dot"></i> <?php echo count($sedes_panel) + 1; ?> ubicación(es)</span><?php if (!empty($centro['licencia_funcionamiento_archivo'])): ?><a class="pc-tag ok" href="<?php echo htmlspecialchars($centro['licencia_funcionamiento_archivo']); ?>" target="_blank"><i class="fa-solid fa-file-shield"></i> Funcionamiento</a><?php endif; ?><?php if (!empty($centro['licencia_sst_archivo'])): ?><a class="pc-tag ok" href="<?php echo htmlspecialchars($centro['licencia_sst_archivo']); ?>" target="_blank"><i class="fa-solid fa-id-card-clip"></i> Licencia SST</a><?php endif; ?></div></article><?php endforeach; ?></div>
                <?php endif; ?>
            </section>

            <section class="pc-section">
                <div class="pc-section-head"><div><h2>Procesos de los perfiles</h2><p>Estructura disponible para clasificar los cargos.</p></div><?php if ($usuario_rol === 'sst'): ?><a href="nuevo_proceso_perfil"><i class="fa-solid fa-plus"></i> Agregar proceso</a><?php endif; ?></div>
                <?php if (empty($procesos_perfil)): ?><div class="pc-empty"><i class="fa-solid fa-diagram-project"></i><strong>No hay procesos guardados.</strong><p>Registra uno para acelerar la creación de perfiles.</p></div><?php else: ?><div class="pc-card-list"><?php foreach ($procesos_perfil as $proceso_panel): ?><article class="pc-record"><div class="pc-record-head"><div class="pc-record-icon"><i class="fa-solid fa-sitemap"></i></div><div class="pc-record-title"><h3><?php echo htmlspecialchars($proceso_panel); ?></h3><p>Proceso reutilizable disponible para los perfiles de esta empresa.</p></div></div><div class="pc-record-tags"><span class="pc-tag ok"><i class="fa-solid fa-rotate"></i> Reutilizable</span></div></article><?php endforeach; ?></div><?php endif; ?>
            </section>

            <section class="pc-section">
                <div class="pc-section-head"><div><h2>Perfiles de cargo consolidados</h2><p>Información lista para reutilizar o entregar al médico ocupacional.</p></div><?php if ($usuario_rol === 'sst'): ?><a href="nuevo_perfil_cargo"><i class="fa-solid fa-plus"></i> Crear perfil</a><?php endif; ?></div>
                <?php if (empty($perfiles_cargo)): ?><div class="pc-empty"><i class="fa-solid fa-user-doctor"></i><strong>No hay perfiles creados.</strong><p>Completa el flujo para construir el primero.</p></div><?php else: ?><div class="pc-card-list"><?php foreach ($perfiles_cargo as $perfil): ?><?php $tareas_panel = estandar5_decode_list($perfil['tareas_json'] ?? ''); $riesgos_panel = estandar5_decode_list($perfil['tareas_alto_riesgo_json'] ?? ''); $herramientas_panel = estandar5_decode_list($perfil['herramientas_json'] ?? ''); ?><article class="pc-record"><div class="pc-record-head"><div class="pc-record-icon"><i class="fa-solid fa-user-gear"></i></div><div class="pc-record-title"><h3><?php echo htmlspecialchars($perfil['nombre_cargo']); ?></h3><p><?php echo htmlspecialchars($perfil['tipo_proceso']); ?> · <?php echo htmlspecialchars($perfil['tipo_operacion'] ?? 'Mixto'); ?> · Jefe: <?php echo htmlspecialchars($perfil['jefe_inmediato']); ?></p><p>Centro: <?php echo htmlspecialchars($perfil['centro_nombre'] ?: 'Sin asignar'); ?></p></div><a class="pc-record-action" href="estandar5?modulo=perfiles-cargo&amp;export_perfil=<?php echo (int)$perfil['id']; ?>"><i class="fa-solid fa-file-pdf"></i> PDF</a></div><div class="pc-record-tags"><span class="pc-tag"><i class="fa-solid fa-list-check"></i> <?php echo count($tareas_panel); ?> tareas</span><span class="pc-tag warn"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo count($riesgos_panel); ?> alto riesgo</span><span class="pc-tag"><i class="fa-solid fa-screwdriver-wrench"></i> <?php echo count($herramientas_panel); ?> herramientas</span></div></article><?php endforeach; ?></div><?php endif; ?>
            </section>

            <?php if (false && $usuario_rol === 'representante'): ?>
                <?php
                    $centros_funcionamiento = 0;
                    $centros_licencia_sst = 0;
                    foreach ($centros_medicos as $centro_rep) {
                        if (!empty($centro_rep['licencia_funcionamiento_archivo'])) $centros_funcionamiento++;
                        if (!empty($centro_rep['licencia_sst_archivo'])) $centros_licencia_sst++;
                    }
                    $perfiles_admin = 0;
                    $perfiles_operativos = 0;
                    $perfiles_mixtos = 0;
                    foreach ($perfiles_cargo as $perfil_rep) {
                        $operacion_rep = strtolower((string)($perfil_rep['tipo_operacion'] ?? ''));
                        if ($operacion_rep === 'administrativo') $perfiles_admin++;
                        elseif ($operacion_rep === 'operativo') $perfiles_operativos++;
                        else $perfiles_mixtos++;
                    }
                ?>
                <section class="rep-e5-hero">
                    <h2>Informacion al medico de perfiles de cargo</h2>
                    <p>Resumen de centros medicos autorizados y perfiles creados por el responsable SST para orientar la programacion de examenes.</p>
                </section>
                <section class="rep-e5-grid" aria-label="Resumen informativo de perfiles de cargo">
                    <article class="rep-e5-card">
                        <div class="rep-e5-card-head">
                            <div><h3>Centros medicos</h3><p>Proveedores autorizados por la empresa.</p></div>
                            <div class="rep-e5-icon"><i class="fa-solid fa-hospital"></i></div>
                        </div>
                        <div class="rep-e5-metrics">
                            <div class="rep-e5-metric"><span>Registrados</span><strong><?php echo count($centros_medicos); ?></strong></div>
                            <div class="rep-e5-metric"><span>Con licencia SST</span><strong><?php echo $centros_licencia_sst; ?></strong></div>
                        </div>
                    </article>
                    <article class="rep-e5-card">
                        <div class="rep-e5-card-head">
                            <div><h3>Perfiles creados</h3><p>Distribucion por tipo de operacion.</p></div>
                            <div class="rep-e5-icon"><i class="fa-solid fa-user-doctor"></i></div>
                        </div>
                        <div class="rep-e5-metrics">
                            <div class="rep-e5-metric"><span>Total</span><strong><?php echo count($perfiles_cargo); ?></strong></div>
                            <div class="rep-e5-metric"><span>Mixtos</span><strong><?php echo $perfiles_mixtos; ?></strong></div>
                        </div>
                        <div class="rep-e5-bars">
                            <div class="rep-e5-bar"><span>Administrativo</span><div class="rep-e5-track"><div class="rep-e5-fill" style="--bar:#2563eb;width:<?php echo count($perfiles_cargo) ? round(($perfiles_admin / count($perfiles_cargo)) * 100) : 0; ?>%"></div></div><strong><?php echo $perfiles_admin; ?></strong></div>
                            <div class="rep-e5-bar"><span>Operativo</span><div class="rep-e5-track"><div class="rep-e5-fill" style="--bar:#f97316;width:<?php echo count($perfiles_cargo) ? round(($perfiles_operativos / count($perfiles_cargo)) * 100) : 0; ?>%"></div></div><strong><?php echo $perfiles_operativos; ?></strong></div>
                        </div>
                    </article>
                    <article class="rep-e5-card rep-e5-wide">
                        <div class="rep-e5-card-head">
                            <div><h3>Listado informativo</h3><p>Perfiles disponibles para examenes medicos ocupacionales.</p></div>
                            <div class="rep-e5-icon"><i class="fa-solid fa-list-check"></i></div>
                        </div>
                        <?php if (empty($perfiles_cargo)): ?>
                            <div class="med-empty"><i class="fa-solid fa-user-doctor"></i><strong>No hay perfiles de cargo creados.</strong></div>
                        <?php else: ?>
                            <div class="rep-e5-list">
                                <?php foreach (array_slice($perfiles_cargo, 0, 8) as $perfil_rep): ?>
                                    <?php
                                        $tareas_rep = estandar5_decode_list($perfil_rep['tareas_json'] ?? '');
                                        $riesgos_rep = estandar5_decode_list($perfil_rep['tareas_alto_riesgo_json'] ?? '');
                                        $herramientas_rep = estandar5_decode_list($perfil_rep['herramientas_json'] ?? '');
                                    ?>
                                    <div class="rep-e5-row">
                                        <strong><?php echo htmlspecialchars($perfil_rep['nombre_cargo']); ?></strong>
                                        <span><?php echo htmlspecialchars($perfil_rep['tipo_proceso']); ?> &middot; <?php echo htmlspecialchars($perfil_rep['tipo_operacion'] ?? 'Mixto'); ?> &middot; Jefe: <?php echo htmlspecialchars($perfil_rep['jefe_inmediato']); ?></span>
                                        <span><?php echo count($tareas_rep); ?> tarea(s), <?php echo count($riesgos_rep); ?> alto riesgo, <?php echo count($herramientas_rep); ?> herramienta(s)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                </section>
            <?php endif; ?>

            <section class="cargo-summary compact rep-hide">
                <article class="cargo-kpi">
                    <span>Centros autorizados</span>
                    <strong><?php echo count($centros_medicos); ?></strong>
                </article>
                <article class="cargo-kpi">
                    <span>Perfiles creados</span>
                    <strong><?php echo count($perfiles_cargo); ?></strong>
                </article>
            </section>

            <section class="cargo-layout rep-hide">
                <article class="cargo-panel">
                    <div class="cargo-panel-head">
                        <div class="cargo-panel-title">
                            <div class="cargo-panel-icon"><i class="fa-solid fa-hospital"></i></div>
                            <div>
                                <h2>Centros médicos autorizados</h2>
                                <p>Registra los proveedores donde se realizarán los exámenes médicos ocupacionales.</p>
                            </div>
                        </div>
                    </div>

                    <?php if ($usuario_rol === 'sst'): ?>
                        <form class="cargo-form" action="procesar_estandar5.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="accion" value="guardar_centro_medico">
                            <div class="cargo-grid-2">
                                <div class="cargo-field">
                                    <label>Nombre del centro médico *</label>
                                    <input name="nombre" required placeholder="Ej. IPS Salud Ocupacional">
                                </div>
                                <div class="cargo-field">
                                    <label>NIT *</label>
                                    <input name="nit" required placeholder="900123456-7">
                                </div>
                                <div class="cargo-field full">
                                    <label>Dirección principal *</label>
                                    <input name="direccion_principal" required placeholder="Dirección principal del centro">
                                </div>
                                <div class="cargo-field">
                                    <label>Teléfono de contacto *</label>
                                    <input name="telefono" required placeholder="Teléfono o celular">
                                </div>
                                <div class="cargo-field">
                                    <label>Correo *</label>
                                    <input type="email" name="correo" required placeholder="contacto@centro.com">
                                </div>
                                <div class="cargo-field full">
                                    <label>Direcciones de sedes adicionales</label>
                                    <div class="cargo-dynamic" data-dynamic-list="sedes">
                                        <div class="cargo-dynamic-row">
                                            <input name="sedes[]" placeholder="Sede opcional">
                                            <button class="cargo-icon-btn" type="button" data-remove-row aria-label="Quitar sede"><i class="fa-solid fa-xmark"></i></button>
                                        </div>
                                    </div>
                                    <button class="cargo-add-btn" type="button" data-add-row="sedes"><i class="fa-solid fa-plus"></i> Agregar sede</button>
                                </div>
                                <div class="cargo-field">
                                    <label>Licencia de funcionamiento</label>
                                    <div class="cargo-file"><input type="file" name="licencia_funcionamiento" accept=".pdf,.png,.jpg,.jpeg,.webp"></div>
                                </div>
                                <div class="cargo-field">
                                    <label>Licencia SST médico o centro</label>
                                    <div class="cargo-file"><input type="file" name="licencia_sst" accept=".pdf,.png,.jpg,.jpeg,.webp"></div>
                                </div>
                            </div>
                            <button class="cargo-submit" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar centro médico</button>
                        </form>
                    <?php endif; ?>

                    <?php if (empty($centros_medicos)): ?>
                        <div class="cargo-empty">
                            <i class="fa-solid fa-hospital-user"></i>
                            <strong>No hay centros médicos registrados.</strong>
                            <p>Cuando agregues el primero, quedará disponible para asociarlo a perfiles de cargo.</p>
                        </div>
                    <?php else: ?>
                        <div class="cargo-list">
                            <?php foreach ($centros_medicos as $centro): ?>
                                <?php $sedes = estandar5_decode_list($centro['sedes_json'] ?? ''); ?>
                                <article class="cargo-item">
                                    <div class="cargo-item-head">
                                        <div>
                                            <h3><?php echo htmlspecialchars($centro['nombre']); ?></h3>
                                            <p>NIT <?php echo htmlspecialchars($centro['nit']); ?> · <?php echo htmlspecialchars($centro['direccion_principal']); ?></p>
                                            <p><?php echo htmlspecialchars($centro['telefono']); ?> · <?php echo htmlspecialchars($centro['correo']); ?></p>
                                        </div>
                                    </div>
                                    <div class="cargo-tags">
                                        <span class="cargo-tag"><i class="fa-solid fa-location-dot"></i> <?php echo count($sedes); ?> sede(s) adicional(es)</span>
                                        <?php if (!empty($centro['licencia_funcionamiento_archivo'])): ?>
                                            <a class="cargo-tag" href="<?php echo htmlspecialchars($centro['licencia_funcionamiento_archivo']); ?>" target="_blank"><i class="fa-solid fa-file-shield"></i> Funcionamiento</a>
                                        <?php endif; ?>
                                        <?php if (!empty($centro['licencia_sst_archivo'])): ?>
                                            <a class="cargo-tag" href="<?php echo htmlspecialchars($centro['licencia_sst_archivo']); ?>" target="_blank"><i class="fa-solid fa-id-card-clip"></i> Licencia SST</a>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>

                <article class="cargo-panel">
                    <div class="cargo-panel-head">
                        <div class="cargo-panel-title">
                            <div class="cargo-panel-icon"><i class="fa-solid fa-user-gear"></i></div>
                            <div>
                                <h2>Crear perfil de cargo</h2>
                                <p>Define tareas y herramientas autorizadas para reutilizar el perfil y entregarlo al médico.</p>
                            </div>
                        </div>
                    </div>

                    <?php if ($usuario_rol === 'sst'): ?>
                        <form class="cargo-form" action="procesar_estandar5.php" method="POST">
                            <input type="hidden" name="accion" value="guardar_perfil_cargo">
                            <div class="cargo-grid-2">
                                <div class="cargo-field">
                                    <label>Nombre del cargo *</label>
                                    <input name="nombre_cargo" required placeholder="Ej. Operario de producción">
                                </div>
                                <div class="cargo-field">
                                    <label>Tipo de operación *</label>
                                    <select name="tipo_operacion" required>
                                        <option value="">Selecciona...</option>
                                        <option value="Administrativo">Administrativo</option>
                                        <option value="Operativo">Operativo</option>
                                        <option value="Mixto">Mixto</option>
                                    </select>
                                </div>
                                <div class="cargo-field full">
                                    <label>Proceso *</label>
                                    <select name="tipo_proceso_select" id="tipoProcesoSelect" required>
                                        <option value="">Selecciona...</option>
                                        <?php foreach ($procesos_perfil as $proceso_guardado): ?>
                                            <option value="<?php echo htmlspecialchars($proceso_guardado); ?>"><?php echo htmlspecialchars($proceso_guardado); ?></option>
                                        <?php endforeach; ?>
                                        <option value="__nuevo_proceso__" <?php echo empty($procesos_perfil) ? 'selected' : ''; ?>>Crear otro proceso...</option>
                                    </select>
                                    <div class="process-new-panel" id="newProcessPanel">
                                        <input name="tipo_proceso_nuevo" id="tipoProcesoNuevo" placeholder="Escribe el nuevo proceso">
                                        <div class="process-save-options">
                                            <span>¿Conservar para futuras creaciones?</span>
                                            <label><input type="radio" name="guardar_proceso" value="1" checked> Sí, dejar fijo</label>
                                            <label><input type="radio" name="guardar_proceso" value="0"> No, solo este perfil</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="cargo-field full">
                                    <label>Responde a / jefe inmediato *</label>
                                    <input name="jefe_inmediato" required placeholder="Ej. Coordinador de planta">
                                </div>
                                <div class="cargo-field full">
                                    <label>Tareas del cargo *</label>
                                    <div class="cargo-dynamic" data-dynamic-list="tareas">
                                        <div class="cargo-dynamic-row">
                                            <input name="tareas[]" required placeholder="Tarea 1">
                                            <button class="cargo-icon-btn" type="button" data-remove-row aria-label="Quitar tarea"><i class="fa-solid fa-xmark"></i></button>
                                        </div>
                                    </div>
                                    <button class="cargo-add-btn" type="button" data-add-row="tareas"><i class="fa-solid fa-plus"></i> Agregar tarea</button>
                                </div>
                                <div class="cargo-field full">
                                    <label>Herramientas autorizadas según operación</label>
                                    <div class="tools-groups" id="toolsGroups">
                                        <?php foreach ($herramientas_grupos as $grupo_key => $grupo): ?>
                                            <section class="tool-group" data-tool-group="<?php echo htmlspecialchars($grupo_key); ?>">
                                                <h3><i class="fa-solid <?php echo htmlspecialchars($grupo['icon']); ?>"></i> <?php echo htmlspecialchars($grupo['titulo']); ?></h3>
                                                <div class="tools-check-grid">
                                                    <?php foreach ($grupo['items'] as $herramienta): ?>
                                                        <label class="tool-check">
                                                            <input type="checkbox" name="herramientas_<?php echo htmlspecialchars($grupo_key); ?>[]" value="<?php echo htmlspecialchars($herramienta); ?>">
                                                            <span><?php echo htmlspecialchars($herramienta); ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                                <input name="herramienta_otra_<?php echo htmlspecialchars($grupo_key); ?>" placeholder="Agregar otra herramienta">
                                            </section>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="cargo-field full">
                                    <label>Tareas de alto riesgo a las que se expone</label>
                                    <div class="risk-check-grid">
                                        <?php foreach ($tareas_alto_riesgo_base as $riesgo): ?>
                                            <label class="risk-check">
                                                <input type="checkbox" name="tareas_alto_riesgo[]" value="<?php echo htmlspecialchars($riesgo); ?>">
                                                <span><?php echo htmlspecialchars($riesgo); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="field-hint">Marca todas las tareas de alto riesgo que apliquen para el cargo.</div>
                                </div>
                            </div>
                            <button class="cargo-submit" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar perfil predeterminado</button>
                        </form>
                    <?php endif; ?>

                    <?php if (empty($perfiles_cargo)): ?>
                        <div class="cargo-empty">
                            <i class="fa-solid fa-user-doctor"></i>
                            <strong>No hay perfiles de cargo creados.</strong>
                            <p>Los perfiles guardados quedarán disponibles para reutilizar y descargar.</p>
                        </div>
                    <?php else: ?>
                        <div class="cargo-list">
                            <?php foreach ($perfiles_cargo as $perfil): ?>
                                <?php
                                    $tareas = estandar5_decode_list($perfil['tareas_json'] ?? '');
                                    $tareas_alto_riesgo = estandar5_decode_list($perfil['tareas_alto_riesgo_json'] ?? '');
                                    $herramientas = estandar5_decode_list($perfil['herramientas_json'] ?? '');
                                ?>
                                <article class="cargo-item">
                                    <div class="cargo-item-head">
                                        <div>
                                            <h3><?php echo htmlspecialchars($perfil['nombre_cargo']); ?></h3>
                                            <p><?php echo htmlspecialchars($perfil['tipo_proceso']); ?> · <?php echo htmlspecialchars($perfil['tipo_operacion'] ?? 'Mixto'); ?> · Jefe: <?php echo htmlspecialchars($perfil['jefe_inmediato']); ?></p>
                                            <p>Centro: <?php echo htmlspecialchars($perfil['centro_nombre'] ?: 'Sin asignar'); ?></p>
                                        </div>
                                        <a class="cargo-link" href="estandar5.php?modulo=perfiles-cargo&export_perfil=<?php echo (int)$perfil['id']; ?>"><i class="fa-solid fa-file-pdf"></i> PDF</a>
                                    </div>
                                    <div class="cargo-tags">
                                        <span class="cargo-tag"><i class="fa-solid fa-list-check"></i> <?php echo count($tareas); ?> tarea(s)</span>
                                        <span class="cargo-tag"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo count($tareas_alto_riesgo); ?> alto riesgo</span>
                                        <span class="cargo-tag"><i class="fa-solid fa-screwdriver-wrench"></i> <?php echo count($herramientas); ?> herramienta(s)</span>
                                        <span class="cargo-tag"><i class="fa-solid fa-rotate"></i> Reutilizable</span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            </section>
        <?php elseif ($modulo_actual === 'evaluaciones-medicas'): ?>
            <?php
                $msg = trim((string)($_GET['msg'] ?? ''));
                $tipo_msg = ($_GET['tipo'] ?? 'ok') === 'error' ? 'error' : 'ok';
                $solicitadas = 0;
                foreach ($eval_solicitudes as $sol) {
                    if (($sol['estado'] ?? '') === 'solicitada') {
                        $solicitadas++;
                    }
                }
                $eval_mes_actual = date('Y-m');
                $eval_mes = 0;
                foreach ($eval_solicitudes as $sol) {
                    if (!empty($sol['creado_en']) && date('Y-m', strtotime($sol['creado_en'])) === $eval_mes_actual) {
                        $eval_mes++;
                    }
                }
                $eval_soportes_vigentes = 0;
                foreach ($eval_soportes as $soporte_rep) {
                    $status_rep = estandar5_eval_alert_status($soporte_rep['fecha_vencimiento'] ?? null);
                    if ($status_rep && ($status_rep['estado'] ?? '') === 'vigente') {
                        $eval_soportes_vigentes++;
                    }
                }
                $eval_personal_total = count($eval_trabajadores);
                $eval_personal_con_soporte = min($eval_personal_total, count($eval_soportes_por_trabajador));
                $eval_personal_pendiente = max(0, $eval_personal_total - $eval_personal_con_soporte);
                $eval_personal_al_dia = 0;
                $eval_proxima_vigencia = null;
                foreach ($eval_soportes_por_trabajador as $soporte_personal) {
                    $status_personal = estandar5_eval_alert_status($soporte_personal['fecha_vencimiento'] ?? null);
                    if ($status_personal && ($status_personal['estado'] ?? '') === 'vigente') {
                        $eval_personal_al_dia++;
                    }
                    if (!empty($soporte_personal['fecha_vencimiento'])) {
                        $fecha_personal = strtotime((string)$soporte_personal['fecha_vencimiento']);
                        if ($fecha_personal && $fecha_personal >= strtotime('today') && ($eval_proxima_vigencia === null || $fecha_personal < $eval_proxima_vigencia)) {
                            $eval_proxima_vigencia = $fecha_personal;
                        }
                    }
                }
                $eval_cobertura_soportes = $eval_personal_total > 0 ? (int)round(($eval_personal_con_soporte / $eval_personal_total) * 100) : 0;
                $can_program = $usuario_rol === 'sst' && !empty($eval_centros_medicos) && !empty($eval_perfiles_cargo);
            ?>
            <?php if ($msg !== ''): ?>
                <div class="med-alert <?php echo htmlspecialchars($tipo_msg); ?>">
                    <i class="fa-solid <?php echo $tipo_msg === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
                    <span><?php echo htmlspecialchars($msg); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($usuario_rol === 'representante'): ?>
                <section class="rep-e5-hero">
                    <h2>Realizacion de evaluaciones medicas ocupacionales</h2>
                    <p>Vista informativa de programaciones, examenes recibidos y vencimientos reportados por el responsable SST.</p>
                </section>
                <section class="rep-e5-grid" aria-label="Resumen informativo de evaluaciones medicas">
                    <article class="rep-e5-card">
                        <div class="rep-e5-card-head">
                            <div><h3>Programacion</h3><p>Solicitudes enviadas a centros medicos.</p></div>
                            <div class="rep-e5-icon"><i class="fa-solid fa-calendar-check"></i></div>
                        </div>
                        <div class="rep-e5-metrics">
                            <div class="rep-e5-metric"><span>Este mes</span><strong><?php echo $eval_mes; ?></strong></div>
                            <div class="rep-e5-metric"><span>Solicitudes ano</span><strong><?php echo count($eval_solicitudes); ?></strong></div>
                        </div>
                    </article>
                    <article class="rep-e5-card">
                        <div class="rep-e5-card-head">
                            <div><h3>Soportes y alertas</h3><p>Control de examenes cargados.</p></div>
                            <div class="rep-e5-icon"><i class="fa-solid fa-file-medical"></i></div>
                        </div>
                        <div class="rep-e5-metrics">
                            <div class="rep-e5-metric"><span>Soportes</span><strong><?php echo count($eval_soportes); ?></strong></div>
                            <div class="rep-e5-metric"><span>Alertas</span><strong><?php echo count($eval_alertas); ?></strong></div>
                        </div>
                    </article>
                    <article class="rep-e5-card rep-e5-wide">
                        <div class="rep-e5-card-head">
                            <div><h3>Seguimiento del personal</h3><p>Estado general de los soportes medicos recibidos.</p></div>
                            <div class="rep-e5-icon"><i class="fa-solid fa-users"></i></div>
                        </div>
                        <div class="rep-e5-metrics">
                            <div class="rep-e5-metric"><span>Trabajadores</span><strong><?php echo count($eval_trabajadores); ?></strong></div>
                            <div class="rep-e5-metric"><span>Vigentes</span><strong><?php echo $eval_soportes_vigentes; ?></strong></div>
                        </div>
                    </article>
                </section>
            <?php endif; ?>

            <section class="med-summary <?php echo $usuario_rol === 'representante' ? 'rep-hide' : ''; ?>">
                <article class="med-kpi"><i class="fa-solid fa-users med-kpi-watermark" aria-hidden="true"></i><span>Trabajadores</span><strong><?php echo count($eval_trabajadores); ?></strong><small>Personal disponible para programación médica.</small></article>
                <article class="med-kpi blue"><i class="fa-solid fa-hospital med-kpi-watermark" aria-hidden="true"></i><span>Centros médicos</span><strong><?php echo count($eval_centros_medicos); ?></strong><small>Prestadores autorizados y activos.</small></article>
                <article class="med-kpi green"><i class="fa-solid fa-user-gear med-kpi-watermark" aria-hidden="true"></i><span>Perfiles de cargo</span><strong><?php echo count($eval_perfiles_cargo); ?></strong><small>Perfiles listos para enviar al centro.</small></article>
                <article class="med-kpi violet"><i class="fa-solid fa-paper-plane med-kpi-watermark" aria-hidden="true"></i><span>Solicitudes recientes</span><strong><?php echo count($eval_solicitudes); ?></strong><small>Programaciones registradas recientemente.</small></article>
            </section>

            <?php if ($usuario_rol === 'sst' && (empty($eval_centros_medicos) || empty($eval_perfiles_cargo))): ?>
                <div class="notice" style="margin-bottom:14px;">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Para programar exámenes debes tener al menos un centro médico autorizado y un perfil de cargo creado en el subestándar 3.1.3.</span>
                </div>
            <?php endif; ?>

            <?php if ($usuario_rol === 'sst' && !empty($eval_alertas)): ?>
                <div class="med-notification">
                    <span><i class="fa-solid fa-bell"></i> <strong>Notificación SST:</strong> tienes vencimientos médicos o certificaciones que requieren seguimiento.</span>
                    <span class="med-badge critico"><?php echo count($eval_alertas); ?> alerta(s)</span>
                </div>
            <?php endif; ?>

            <?php if (false): // Formulario trasladado a control_examenes_medicos.php. ?>
            <section class="med-card upload-exam <?php echo $usuario_rol === 'representante' ? 'rep-hide' : ''; ?>">
                <div class="med-card-head">
                    <div>
                        <h2>Control de exámenes médicos realizados</h2>
                        <p>Sube el PDF entregado por el centro médico. El sistema intenta leer el texto y deja los datos listos para confirmación antes de guardar vencimientos.</p>
                    </div>
                    <i class="fa-solid fa-file-medical" style="color:var(--primary2);"></i>
                </div>
                <div class="med-filter-row">
                    <div class="med-filter-field">
                        <label class="med-label" for="medDocumentoFilter">Filtrar por No. identificación</label>
                        <input class="med-field" id="medDocumentoFilter" type="search" placeholder="Escribe la cédula o documento del trabajador">
                    </div>
                    <div class="med-filter-actions">
                        <button class="med-ghost-btn" type="button" id="medDocumentoClear"><i class="fa-solid fa-rotate-left"></i> Limpiar</button>
                        <span class="med-note" id="medDocumentoFilterStatus">Filtra el listado de trabajadores antes de cargar el soporte.</span>
                    </div>
                </div>
                <form class="med-upload-grid" action="procesar_estandar5.php" method="POST" enctype="multipart/form-data" id="medicalSupportForm">
                    <input type="hidden" name="accion" value="guardar_soporte_evaluacion_medica">
                    <input type="hidden" name="texto_extraido" id="textoExtraidoInput">
                    <div>
                        <label class="med-label" for="supportTrabajador">Trabajador</label>
                        <select class="med-field" name="trabajador_id" id="supportTrabajador" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($eval_trabajadores as $trabajador): ?>
                                <?php $cargo_trabajador = estandar5_eval_cargo_trabajador($trabajador); ?>
                                <option value="<?php echo (int)$trabajador['id']; ?>" data-nombre="<?php echo htmlspecialchars(trim($trabajador['nombre'] . ' ' . $trabajador['apellido'])); ?>" data-cedula="<?php echo htmlspecialchars($trabajador['cedula']); ?>" data-cargo="<?php echo htmlspecialchars($cargo_trabajador); ?>">
                                    <?php echo htmlspecialchars(trim($trabajador['nombre'] . ' ' . $trabajador['apellido']) . ' · ' . $trabajador['cedula']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="med-label" for="supportSolicitud">Solicitud previa</label>
                        <select class="med-field" name="evaluacion_id" id="supportSolicitud">
                            <option value="">Sin solicitud vinculada</option>
                            <?php foreach ($eval_solicitudes as $sol): ?>
                                <option value="<?php echo (int)$sol['id']; ?>" data-trabajador="<?php echo (int)$sol['trabajador_id']; ?>" data-perfil="<?php echo (int)$sol['perfil_cargo_id']; ?>" data-centro="<?php echo (int)$sol['centro_medico_id']; ?>">
                                    <?php echo htmlspecialchars(date('d/m/Y', strtotime($sol['creado_en'])) . ' · ' . $sol['cedula'] . ' · ' . $sol['tipo_examen']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="med-label" for="supportPerfil">Perfil de cargo</label>
                        <select class="med-field" name="perfil_cargo_id" id="supportPerfil">
                            <option value="">Sin perfil</option>
                            <?php foreach ($eval_perfiles_cargo as $perfil): ?>
                                <option value="<?php echo (int)$perfil['id']; ?>" data-operacion="<?php echo htmlspecialchars($perfil['tipo_operacion'] ?? 'Mixto'); ?>" data-cargo="<?php echo htmlspecialchars($perfil['nombre_cargo']); ?>">
                                    <?php echo htmlspecialchars($perfil['nombre_cargo'] . ' · ' . ($perfil['tipo_operacion'] ?? 'Mixto')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="med-label" for="supportCentro">Centro medico</label>
                        <select class="med-field" name="centro_medico_id" id="supportCentro">
                            <option value="">Sin centro vinculado</option>
                            <?php foreach ($eval_centros_medicos as $centro): ?>
                                <option value="<?php echo (int)$centro['id']; ?>" data-nombre="<?php echo htmlspecialchars($centro['nombre']); ?>">
                                    <?php echo htmlspecialchars($centro['nombre'] . ' · ' . $centro['direccion_principal']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="wide">
                        <label class="med-label" for="archivoPdfMedico">PDF del examen o certificado</label>
                        <div class="med-file">
                            <input type="file" name="archivo_pdf" id="archivoPdfMedico" accept="application/pdf,.pdf" required>
                            <div class="med-note" id="extractStatus">Al cargar el PDF se intentará extraer texto para completar campos.</div>
                        </div>
                    </div>
                    <div class="med-section-title"><i class="fa-solid fa-stethoscope"></i> Seguimiento a vencimiento de exámenes médicos</div>
                    <div class="wide">
                        <label class="med-label" for="nombreTrabajadorMedico">Nombres y apellidos</label>
                        <input class="med-field" name="nombre_trabajador" id="nombreTrabajadorMedico" required>
                    </div>
                    <div>
                        <label class="med-label" for="cedulaTrabajadorMedico">No. identificacion</label>
                        <input class="med-field" name="cedula" id="cedulaTrabajadorMedico" required>
                    </div>
                    <div>
                        <label class="med-label" for="cargoTrabajadorMedico">Cargo</label>
                        <input class="med-field" name="cargo" id="cargoTrabajadorMedico">
                    </div>
                    <div>
                        <label class="med-label" for="tipoExamenMedico">Tipo de examen</label>
                        <select class="med-field" name="tipo_examen" id="tipoExamenMedico">
                            <option value="">Selecciona...</option>
                            <option value="Ingreso">Ingreso</option>
                            <option value="Periodico">Periodico</option>
                            <option value="Levantamiento Restricciones">Levantamiento Restricciones</option>
                            <option value="Retiro">Retiro</option>
                            <option value="Post-Incapacidad">Post-Incapacidad</option>
                        </select>
                    </div>
                    <div>
                        <label class="med-label" for="resultadoMedico">Resultado</label>
                        <select class="med-field" name="resultado" id="resultadoMedico">
                            <option value="">Selecciona...</option>
                            <option value="Restriccion">Restriccion</option>
                            <option value="Apto">Apto</option>
                            <option value="No Apto">No Apto</option>
                        </select>
                    </div>
                    <div>
                        <label class="med-label" for="aptitudMedico">Tipo de aptitud</label>
                        <select class="med-field" name="tipo_aptitud" id="aptitudMedico">
                            <option value="">Selecciona...</option>
                            <option value="Sin restricciones">Sin restricciones</option>
                            <option value="Con recomendaciones">Con recomendaciones</option>
                            <option value="Con restricciones">Con restricciones</option>
                        </select>
                    </div>
                    <div><label class="med-label" for="centroMedicoTexto">Centro medico</label><input class="med-field" name="centro_medico" id="centroMedicoTexto"></div>
                    <div><label class="med-label" for="fechaExpedicionMedico">Fecha de expedicion</label><input class="med-field" type="date" name="fecha_expedicion" id="fechaExpedicionMedico"></div>
                    <div><label class="med-label" for="fechaVencimientoMedico">Fecha de vencimiento</label><input class="med-field" type="date" name="fecha_vencimiento" id="fechaVencimientoMedico"></div>
                    <div><label class="med-label" for="tiempoProgramarMedico">Tiempo para programar</label><input class="med-field" name="tiempo_para_programar" id="tiempoProgramarMedico" placeholder="30, 60, 90 dias"></div>
                    <div><label class="med-label" for="diasAccionMedico">Dias de accion</label><input class="med-field" type="number" name="dias_accion" id="diasAccionMedico"></div>
                    <div class="full"><label class="med-label" for="observacionesSoporte">Observaciones internas</label><textarea class="med-field med-extract-box" name="observaciones" id="observacionesSoporte" placeholder="Notas del responsable SST, restricciones o recomendaciones visibles en el soporte."></textarea></div>
                    <div class="full med-actions-row">
                        <span class="med-note">Periodicidad sugerida: operativo/mixto 18 meses, administrativo 36 meses.</span>
                        <button class="med-btn" type="submit" <?php echo $usuario_rol === 'sst' ? '' : 'disabled'; ?>><i class="fa-solid fa-cloud-arrow-up"></i> Guardar examen</button>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <section class="med-control-access <?php echo $usuario_rol === 'representante' ? 'rep-hide' : ''; ?>">
                <i class="fa-solid fa-file-medical med-control-access-watermark" aria-hidden="true"></i>
                <div class="med-control-access-copy">
                    <i class="fa-solid fa-file-waveform"></i>
                    <div><h2>Control de exámenes médicos realizados</h2><p>El cargue de soportes, resultados, aptitud y vencimientos ahora tiene su propio espacio de trabajo.</p></div>
                </div>
                <div class="med-control-access-meta">
                    <div class="med-control-access-stat"><span>Soportes</span><strong><?php echo count($eval_soportes); ?></strong></div>
                    <div class="med-control-access-stat"><span>Alertas</span><strong><?php echo count($eval_alertas); ?></strong></div>
                    <a class="med-control-access-link" href="control_examenes_medicos"><i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir control</a>
                </div>
            </section>

            <section class="med-card alert-panel" id="alertasVencimiento">
                <div class="med-card-head">
                    <div>
                        <h2>Alertas de vencimiento</h2>
                        <p>Prioriza certificados vencidos y próximos a vencer sin revisar una tabla extensa.</p>
                    </div>
                    <span class="med-demo-count"><i class="fa-solid fa-bell"></i> <?php echo count($eval_alertas); ?> alertas</span>
                </div>
                <?php if (empty($eval_alertas)): ?>
                    <div class="med-alert-empty">
                        <i class="fa-solid fa-shield-heart med-alert-empty-watermark" aria-hidden="true"></i>
                        <span class="med-alert-empty-icon"><i class="fa-solid fa-circle-check"></i></span>
                        <span class="med-alert-empty-copy"><strong>Control médico al día</strong><span>No hay soportes vencidos ni fechas dentro de los próximos 90 días. Mantén actualizadas las vigencias para conservar este estado.</span></span>
                        <span class="med-alert-empty-stats"><span class="med-alert-empty-stat"><span>Vencidas</span><strong>0</strong></span><span class="med-alert-empty-stat"><span>Próximas 90 días</span><strong>0</strong></span><span class="med-alert-empty-stat"><span>Próxima vigencia</span><strong><?php echo $eval_proxima_vigencia ? htmlspecialchars(date('d/m/Y', $eval_proxima_vigencia)) : 'Sin fecha'; ?></strong></span></span>
                        <a class="med-alert-empty-link" href="control_examenes_medicos"><i class="fa-solid fa-arrow-up-right-from-square"></i> Revisar control</a>
                    </div>
                <?php else: ?>
                    <div class="med-alert-grid">
                        <?php foreach (array_slice($eval_alertas, 0, 9) as $alerta): ?>
                            <article class="med-alert-item <?php echo htmlspecialchars($alerta['estado']); ?>">
                                <i class="fa-solid fa-bell med-alert-watermark" aria-hidden="true"></i>
                                <i class="fa-solid <?php echo $alerta['estado'] === 'vencido' ? 'fa-triangle-exclamation' : 'fa-clock'; ?>"></i>
                                <div>
                                    <strong><?php echo htmlspecialchars($alerta['tipo']); ?></strong>
                                    <span><?php echo htmlspecialchars($alerta['trabajador']); ?> · C.C. <?php echo htmlspecialchars($alerta['cedula']); ?></span>
                                    <span><?php echo htmlspecialchars($alerta['texto']); ?></span>
                                    <span class="med-alert-date"><i class="fa-solid fa-calendar-day"></i> <?php echo htmlspecialchars(date('d/m/Y', strtotime($alerta['fecha']))); ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="med-card personnel-matrix" id="controlPersonal">
                <div class="med-card-head">
                    <div>
                        <h2>Control médico del personal</h2>
                        <p>Consulta cargo, último resultado, vigencia y soporte de cada trabajador en una sola card.</p>
                    </div>
                    <span class="med-demo-count"><i class="fa-solid fa-users"></i> <?php echo count($eval_trabajadores); ?> trabajadores</span>
                </div>
                <div class="med-personnel-overview">
                    <article class="med-personnel-metric"><i class="fa-solid fa-chart-pie"></i><span>Cobertura documental</span><strong><?php echo $eval_cobertura_soportes; ?>%</strong><small><?php echo $eval_personal_con_soporte; ?> de <?php echo $eval_personal_total; ?> trabajadores con soporte.</small></article>
                    <article class="med-personnel-metric"><i class="fa-solid fa-file-circle-check"></i><span>Con soporte</span><strong><?php echo $eval_personal_con_soporte; ?></strong><small>Conceptos médicos cargados.</small></article>
                    <article class="med-personnel-metric"><i class="fa-solid fa-shield-heart"></i><span>Al día</span><strong><?php echo $eval_personal_al_dia; ?></strong><small>Vigencias sin alertas activas.</small></article>
                    <article class="med-personnel-metric"><i class="fa-solid fa-clock"></i><span>Pendientes</span><strong><?php echo $eval_personal_pendiente; ?></strong><small>Trabajadores sin soporte médico.</small></article>
                </div>
                <?php if (empty($eval_trabajadores)): ?>
                    <div class="med-empty"><i class="fa-solid fa-users"></i><strong>No hay trabajadores registrados.</strong></div>
                <?php else: ?>
                    <div class="med-personnel-list">
                        <?php foreach ($eval_trabajadores as $trabajador): ?>
                            <?php
                                $trabajador_id_matriz = (int)$trabajador['id'];
                                $soporte = $eval_soportes_por_trabajador[$trabajador_id_matriz] ?? null;
                                $estado_med = $soporte ? estandar5_eval_alert_status($soporte['fecha_vencimiento'] ?? null) : null;
                                $alertas_fila = array_filter([$estado_med], fn($estado) => $estado && $estado['estado'] !== 'vigente');
                            ?>
                            <article class="med-personnel-item">
                                <i class="fa-solid fa-user-doctor med-personnel-watermark" aria-hidden="true"></i>
                                <div class="med-personnel-top">
                                    <div class="med-personnel-person"><span class="med-personnel-icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span><span class="med-personnel-copy"><strong><?php echo htmlspecialchars(trim($trabajador['nombre'] . ' ' . $trabajador['apellido'])); ?></strong><span>C.C. <?php echo htmlspecialchars($trabajador['cedula']); ?> · <?php echo htmlspecialchars($trabajador['email']); ?></span></span></div>
                                    <div class="med-personnel-actions"><?php if (!empty($alertas_fila)): ?><span class="med-badge critico"><?php echo count($alertas_fila); ?> alerta(s)</span><?php elseif ($soporte): ?><span class="med-badge vigente">Al día</span><?php else: ?><span class="med-badge solicitada">Pendiente</span><?php endif; ?><?php if ($soporte): ?><a class="med-personnel-action-link" href="<?php echo htmlspecialchars($soporte['archivo_pdf']); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i> Ver soporte</a><?php else: ?><a class="med-personnel-action-link" href="control_examenes_medicos"><i class="fa-solid fa-cloud-arrow-up"></i> Cargar resultado</a><?php endif; ?></div>
                                </div>
                                <div class="med-personnel-body">
                                    <div class="med-personnel-data"><small>Cargo o perfil</small><strong><?php echo htmlspecialchars($soporte['cargo'] ?? estandar5_eval_cargo_trabajador($trabajador)); ?></strong><span>Información reportada para la evaluación.</span></div>
                                    <div class="med-personnel-data"><small>Último examen</small><strong><?php echo $soporte ? htmlspecialchars($soporte['tipo_examen'] ?: 'Sin tipo') : 'Sin examen registrado'; ?></strong><span><?php echo $soporte ? htmlspecialchars(date('d/m/Y', strtotime($soporte['creado_en']))) : 'Pendiente de cargar resultado.'; ?></span></div>
                                    <div class="med-personnel-data"><small>Resultado y aptitud</small><strong><?php echo $soporte ? htmlspecialchars($soporte['resultado'] ?: 'Sin resultado') : 'Sin concepto médico'; ?></strong><span><?php echo $soporte ? htmlspecialchars($soporte['tipo_aptitud'] ?: 'Sin aptitud confirmada') : 'La aptitud aparecerá al cargar el soporte.'; ?></span></div>
                                    <div class="med-personnel-data"><small>Vigencia</small><strong><?php echo $soporte && $soporte['fecha_vencimiento'] ? htmlspecialchars(date('d/m/Y', strtotime($soporte['fecha_vencimiento']))) : 'Sin fecha definida'; ?></strong><span><?php echo $estado_med ? htmlspecialchars($estado_med['texto']) : 'Sin cálculo de vencimiento.'; ?></span></div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="med-card program-list <?php echo $usuario_rol === 'representante' ? 'rep-hide' : ''; ?>" id="programacionExamenes">
                <div class="med-card-head">
                    <div>
                        <h2>Trabajadores para programación de exámenes médicos</h2>
                        <p>Busca, selecciona uno o varios trabajadores y programa la misma evaluación de forma masiva.</p>
                    </div>
                    <span class="med-demo-count"><i class="fa-solid fa-users"></i> <?php echo count($eval_trabajadores); ?> disponibles</span>
                </div>
                <?php if (empty($eval_trabajadores)): ?>
                    <div class="med-empty">
                        <i class="fa-solid fa-users"></i>
                        <strong>No hay trabajadores registrados.</strong>
                        <p>Cuando los trabajadores se registren, aparecerán aquí para programar sus exámenes médicos.</p>
                    </div>
                <?php else: ?>
                    <form class="med-program-body" action="procesar_estandar5.php" method="POST" id="medBulkProgramForm" data-can-program="<?php echo $can_program ? '1' : '0'; ?>">
                        <input type="hidden" name="accion" value="programar_evaluacion_medica">
                        <div class="med-program-config">
                            <div><label class="med-label" for="bulkTipoExamen">Tipo de examen</label><select class="med-field" name="tipo_examen" id="bulkTipoExamen" required <?php echo $can_program ? '' : 'disabled'; ?>><option value="Periodico">Periódico</option><option value="Ingreso">Ingreso</option><option value="Egreso">Egreso</option><option value="Post incapacidad">Post incapacidad</option><option value="Reubicacion">Reubicación</option></select></div>
                            <div><label class="med-label" for="bulkPerfilCargo">Perfil de cargo</label><select class="med-field" name="perfil_cargo_id" id="bulkPerfilCargo" required <?php echo $can_program ? '' : 'disabled'; ?>><option value="">Selecciona un perfil...</option><?php foreach ($eval_perfiles_cargo as $perfil): ?><option value="<?php echo (int)$perfil['id']; ?>"><?php echo htmlspecialchars($perfil['nombre_cargo'] . ' · ' . $perfil['tipo_operacion']); ?></option><?php endforeach; ?></select></div>
                            <div><label class="med-label" for="bulkCentroMedico">Centro médico</label><select class="med-field" name="centro_medico_id" id="bulkCentroMedico" required <?php echo $can_program ? '' : 'disabled'; ?>><option value="">Selecciona un centro...</option><?php foreach ($eval_centros_medicos as $centro): ?><option value="<?php echo (int)$centro['id']; ?>"><?php echo htmlspecialchars($centro['nombre'] . ' · ' . $centro['correo']); ?></option><?php endforeach; ?></select></div>
                            <div><label class="med-label" for="bulkObservaciones">Observaciones comunes</label><input class="med-field" name="observaciones" id="bulkObservaciones" placeholder="Indicaciones para el centro médico"></div>
                        </div>
                        <div class="med-program-toolbar">
                            <div class="med-worker-search"><label class="med-label" for="medWorkerSearch">Buscar trabajador</label><div class="med-worker-search-box"><i class="fa-solid fa-magnifying-glass"></i><input class="med-field" type="search" id="medWorkerSearch" placeholder="Nombre, cédula, correo o cargo"></div></div>
                            <div class="med-program-selection"><label class="med-select-visible"><input type="checkbox" id="medSelectVisible"> Seleccionar visibles</label><span class="med-selected-count" id="medSelectedCount">0 seleccionados</span></div>
                        </div>
                        <div class="med-worker-list" id="medWorkerList">
                            <?php foreach ($eval_trabajadores as $trabajador): ?>
                                <?php $cargo_trabajador = estandar5_eval_cargo_trabajador($trabajador); $busqueda_trabajador = strtolower(trim($trabajador['nombre'] . ' ' . $trabajador['apellido'] . ' ' . $trabajador['cedula'] . ' ' . $trabajador['email'] . ' ' . $cargo_trabajador)); ?>
                                <label class="med-worker-item" data-worker-row data-search="<?php echo htmlspecialchars($busqueda_trabajador); ?>">
                                    <input class="med-worker-check" type="checkbox" name="trabajador_ids[]" value="<?php echo (int)$trabajador['id']; ?>">
                                    <span class="med-worker-primary"><strong><?php echo htmlspecialchars(trim($trabajador['nombre'] . ' ' . $trabajador['apellido'])); ?></strong><span>C.C. <?php echo htmlspecialchars($trabajador['cedula']); ?> · <?php echo htmlspecialchars($trabajador['email']); ?></span></span>
                                    <span class="med-worker-secondary"><strong><?php echo htmlspecialchars($cargo_trabajador); ?></strong><span>Cargo o dato de perfil disponible</span></span>
                                    <span class="med-worker-status"><?php if (!empty($trabajador['ultima_eval_estado'])): ?><span class="med-badge <?php echo htmlspecialchars($trabajador['ultima_eval_estado']); ?>"><?php echo htmlspecialchars($trabajador['ultima_eval_estado']); ?></span><span class="med-muted"><?php echo htmlspecialchars($trabajador['ultimo_tipo_examen'] ?? ''); ?> · <?php echo htmlspecialchars(date('d/m/Y', strtotime($trabajador['ultima_eval_fecha']))); ?></span><?php else: ?><span class="med-badge sin-fecha">Sin solicitud</span><?php endif; ?></span>
                                </label>
                            <?php endforeach; ?>
                            <div class="med-worker-empty" id="medWorkerEmpty" hidden><i class="fa-solid fa-user-slash"></i> No encontramos trabajadores con esa búsqueda.</div>
                        </div>
                        <div class="med-program-footer">
                            <div class="med-pagination"><button class="med-page-btn" type="button" id="medPagePrev" aria-label="Página anterior"><i class="fa-solid fa-chevron-left"></i></button><span class="med-page-status" id="medPageStatus">Página 1 de 1</span><button class="med-page-btn" type="button" id="medPageNext" aria-label="Página siguiente"><i class="fa-solid fa-chevron-right"></i></button></div>
                            <label class="med-page-size" for="medPageSize">Mostrar <select id="medPageSize"><option value="10">10 trabajadores</option><option value="20">20 trabajadores</option><option value="50">50 trabajadores</option><option value="100">100 trabajadores</option><option value="all">Todos</option></select></label>
                            <button class="med-bulk-submit" type="submit" id="medBulkSubmit" disabled><i class="fa-solid fa-paper-plane"></i> Programar seleccionados</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <section class="med-card request-history" id="solicitudesEnviadas">
                <div class="med-card-head">
                    <div>
                        <h2>Solicitudes enviadas recientemente</h2>
                        <p>Seguimiento rápido del trabajador, perfil enviado, centro médico y estado de cada solicitud.</p>
                    </div>
                    <span class="med-demo-count"><i class="fa-solid fa-paper-plane"></i> <?php echo count($eval_solicitudes); ?> solicitudes</span>
                </div>
                <?php if (empty($eval_solicitudes)): ?>
                    <div class="med-empty"><i class="fa-solid fa-paper-plane"></i><strong>No hay solicitudes enviadas.</strong><p>Las programaciones reales aparecerán aquí cuando selecciones trabajadores y las envíes a un centro médico.</p></div>
                <?php else: ?>
                    <div class="med-request-list">
                        <?php foreach ($eval_solicitudes as $sol): ?>
                            <?php $sol_siguiente = ($sol['estado'] ?? '') === 'realizada' ? 'Revisar aptitud, vigencia y soporte cargado.' : 'Confirmar fecha de atención y recepción del concepto médico.'; ?>
                            <details class="med-request-item"><summary class="med-request-summary"><i class="fa-solid fa-paper-plane med-request-watermark" aria-hidden="true"></i><span class="med-request-icon"><i class="fa-solid fa-user-doctor"></i></span><span class="med-request-copy"><strong><?php echo htmlspecialchars(trim($sol['nombre'] . ' ' . $sol['apellido'])); ?></strong><span>C.C. <?php echo htmlspecialchars($sol['cedula']); ?> · <?php echo htmlspecialchars($sol['nombre_cargo']); ?></span></span><span class="med-request-meta"><strong><?php echo htmlspecialchars($sol['tipo_examen']); ?></strong><span>Perfil: <?php echo htmlspecialchars($sol['nombre_cargo']); ?></span></span><span class="med-request-meta"><strong><?php echo htmlspecialchars($sol['centro_nombre']); ?></strong><span><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($sol['creado_en']))); ?> · <?php echo htmlspecialchars($sol['correo_destino']); ?></span></span><span class="med-request-status"><span class="med-badge <?php echo htmlspecialchars($sol['estado']); ?>"><?php echo htmlspecialchars($sol['estado']); ?></span><span class="med-muted">Mostrar detalles</span></span><span class="med-request-toggle"><i class="fa-solid fa-chevron-down"></i></span></summary><div class="med-request-detail"><div class="med-request-detail-box"><span>Contacto del trabajador</span><strong><?php echo htmlspecialchars($sol['email'] ?: 'Sin correo registrado'); ?></strong></div><div class="med-request-detail-box"><span>Documento</span><strong>C.C. <?php echo htmlspecialchars($sol['cedula']); ?></strong></div><div class="med-request-detail-box"><span>Correo del centro</span><strong><?php echo htmlspecialchars($sol['correo_destino']); ?></strong></div><div class="med-request-detail-box"><span>Perfil enviado</span><strong><?php echo htmlspecialchars($sol['nombre_cargo']); ?> · <?php echo htmlspecialchars($sol['tipo_examen']); ?></strong></div><div class="med-request-detail-box wide"><span>Observaciones enviadas</span><strong><?php echo htmlspecialchars(trim((string)($sol['observaciones'] ?? '')) ?: 'Sin observaciones adicionales.'); ?></strong></div><div class="med-request-detail-box wide"><span>Siguiente acción</span><strong class="med-request-next"><?php echo htmlspecialchars($sol_siguiente); ?></strong></div></div></details>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php elseif ($modulo_actual === 'historias-clinicas'): ?>
            <?php
                $msg = trim((string)($_GET['msg'] ?? ''));
                $tipo_msg = ($_GET['tipo'] ?? 'ok') === 'error' ? 'error' : 'ok';
                $centros_con_soporte = [];
                foreach ($hist_custodias as $custodia) {
                    $centros_con_soporte[(int)$custodia['centro_medico_id']] = true;
                }
                $ultima_custodia = $hist_custodias[0] ?? null;
                $hist_alertas = estandar5_custodia_alertas($hist_custodias);
                $proxima_renovacion = null;
                foreach ($hist_custodias as $custodia) {
                    $status = estandar5_custodia_status($custodia['fecha_emision'] ?? null, $custodia['fecha_renovacion'] ?? null);
                    if (!empty($status['fecha']) && ($proxima_renovacion === null || $status['fecha'] < $proxima_renovacion)) {
                        $proxima_renovacion = $status['fecha'];
                    }
                }
                $hist_ultima_por_centro = [];
                foreach ($hist_custodias as $custodia) {
                    $centro_id_custodia = (int)($custodia['centro_medico_id'] ?? 0);
                    if ($centro_id_custodia > 0 && !isset($hist_ultima_por_centro[$centro_id_custodia])) {
                        $hist_ultima_por_centro[$centro_id_custodia] = $custodia;
                    }
                }
            ?>
            <?php if ($msg !== ''): ?>
                <div class="med-alert <?php echo htmlspecialchars($tipo_msg); ?>">
                    <i class="fa-solid <?php echo $tipo_msg === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
                    <span><?php echo htmlspecialchars($msg); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($usuario_rol === 'representante'): ?>
                <?php $hist_pendientes_rep = max(0, count($hist_centros_medicos) - count($centros_con_soporte)); ?>
                <section class="rep-e5-hero">
                    <h2>Custodia de historias clinicas</h2>
                    <p>Resumen de certificados de custodia cargados por centro medico y alertas de renovacion anual.</p>
                </section>
                <section class="rep-e5-grid" aria-label="Resumen informativo de custodia de historias clinicas">
                    <article class="rep-e5-card">
                        <div class="rep-e5-card-head">
                            <div><h3>Cobertura de soporte</h3><p>Centros con certificado vigente o registrado.</p></div>
                            <div class="rep-e5-icon"><i class="fa-solid fa-folder-open"></i></div>
                        </div>
                        <div class="rep-e5-metrics">
                            <div class="rep-e5-metric"><span>Centros</span><strong><?php echo count($hist_centros_medicos); ?></strong></div>
                            <div class="rep-e5-metric"><span>Con soporte</span><strong><?php echo count($centros_con_soporte); ?></strong></div>
                        </div>
                    </article>
                    <article class="rep-e5-card">
                        <div class="rep-e5-card-head">
                            <div><h3>Renovacion</h3><p>Alertas 15 dias antes del cumplimiento anual.</p></div>
                            <div class="rep-e5-icon"><i class="fa-solid fa-bell"></i></div>
                        </div>
                        <div class="rep-e5-metrics">
                            <div class="rep-e5-metric"><span>Alertas</span><strong><?php echo count($hist_alertas); ?></strong></div>
                            <div class="rep-e5-metric"><span>Pendientes</span><strong><?php echo $hist_pendientes_rep; ?></strong></div>
                        </div>
                    </article>
                    <article class="rep-e5-card rep-e5-wide">
                        <div class="rep-e5-card-head">
                            <div><h3>Centros con soporte</h3><p>Ultimos certificados registrados.</p></div>
                            <div class="rep-e5-icon"><i class="fa-solid fa-hospital-user"></i></div>
                        </div>
                        <?php if (empty($hist_custodias)): ?>
                            <div class="med-empty"><i class="fa-solid fa-file-circle-plus"></i><strong>No hay certificados cargados.</strong></div>
                        <?php else: ?>
                            <div class="rep-e5-list">
                                <?php foreach (array_slice($hist_custodias, 0, 8) as $custodia_rep): ?>
                                    <?php $custodia_status_rep = estandar5_custodia_status($custodia_rep['fecha_emision'] ?? null, $custodia_rep['fecha_renovacion'] ?? null); ?>
                                    <div class="rep-e5-row">
                                        <strong><?php echo htmlspecialchars($custodia_rep['centro_nombre']); ?></strong>
                                        <span>NIT <?php echo htmlspecialchars($custodia_rep['centro_nit']); ?> &middot; Renovacion: <?php echo $custodia_status_rep['fecha'] ? htmlspecialchars(date('d/m/Y', strtotime($custodia_status_rep['fecha']))) : 'Sin fecha'; ?></span>
                                        <span><?php echo htmlspecialchars($custodia_status_rep['texto']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                </section>
            <?php endif; ?>

            <section class="med-summary <?php echo $usuario_rol === 'representante' ? 'rep-hide' : ''; ?>">
                <article class="med-kpi"><i class="fa-solid fa-hospital med-kpi-watermark" aria-hidden="true"></i><span>Centros registrados</span><strong><?php echo count($hist_centros_medicos); ?></strong><small>Prestadores que custodian historias clínicas.</small></article>
                <article class="med-kpi blue"><i class="fa-solid fa-folder-closed med-kpi-watermark" aria-hidden="true"></i><span>Con soporte</span><strong><?php echo count($centros_con_soporte); ?></strong><small>Centros con certificado documental.</small></article>
                <article class="med-kpi green"><i class="fa-solid fa-file-circle-check med-kpi-watermark" aria-hidden="true"></i><span>Soportes cargados</span><strong><?php echo count($hist_custodias); ?></strong><small>Certificados disponibles para consulta.</small></article>
                <article class="med-kpi violet"><i class="fa-solid fa-bell med-kpi-watermark" aria-hidden="true"></i><span>Alertas renovación</span><strong><?php echo count($hist_alertas); ?></strong><small>Documentos que requieren seguimiento.</small></article>
            </section>

            <?php if (!empty($hist_alertas)): ?>
                <div class="med-notification">
                    <span><i class="fa-solid fa-bell"></i> <strong>Alerta SST:</strong> hay certificados de custodia por renovar o sin fecha base confirmada.</span>
                    <span class="med-badge critico"><?php echo count($hist_alertas); ?> alerta(s)</span>
                </div>
            <?php endif; ?>

            <section class="med-card <?php echo $usuario_rol === 'representante' ? 'rep-hide' : ''; ?>">
                <div class="med-card-head">
                    <div>
                        <h2>Centros médicos registrados</h2>
                        <p>Selecciona el centro que emite el certificado de custodia de historias clínicas y carga el soporte en PDF.</p>
                    </div>
                    <i class="fa-solid fa-hospital-user" style="color:var(--primary2);"></i>
                </div>
                <?php if (empty($hist_centros_medicos)): ?>
                    <div class="med-empty">
                        <i class="fa-solid fa-hospital"></i>
                        <strong>No hay centros médicos registrados.</strong>
                        <p>Primero registra los centros autorizados en el subestándar 3.1.3 Información al médico de los perfiles de cargo.</p>
                    </div>
                <?php else: ?>
                    <div class="hist-demo-center-list">
                        <?php foreach ($hist_centros_medicos as $centro_index => $centro): ?>
                            <?php
                                $centro_id_hist = (int)$centro['id'];
                                $centro_custodia = $hist_ultima_por_centro[$centro_id_hist] ?? null;
                                $centro_status = $centro_custodia
                                    ? estandar5_custodia_status($centro_custodia['fecha_emision'] ?? null, $centro_custodia['fecha_renovacion'] ?? null)
                                    : ['estado' => 'sin-fecha', 'texto' => 'Pendiente de soporte', 'fecha' => null];
                                $centro_es_ultimo_impar = count($hist_centros_medicos) % 2 === 1 && $centro_index === count($hist_centros_medicos) - 1;
                            ?>
                            <article class="hist-demo-center <?php echo $centro_es_ultimo_impar ? 'wide' : ''; ?>">
                                <i class="fa-solid fa-hospital-user hist-demo-center-watermark" aria-hidden="true"></i>
                                <div class="hist-demo-center-top">
                                    <div class="hist-demo-center-title">
                                        <i class="fa-solid fa-hospital-user hist-demo-center-icon" aria-hidden="true"></i>
                                        <div><strong><?php echo htmlspecialchars($centro['nombre']); ?></strong><span>NIT <?php echo htmlspecialchars($centro['nit']); ?> · Centro médico autorizado</span></div>
                                    </div>
                                    <span class="med-badge <?php echo htmlspecialchars($centro_status['estado']); ?>"><?php echo htmlspecialchars($centro_status['texto']); ?></span>
                                </div>
                                <div class="hist-demo-center-body">
                                    <div class="hist-demo-data"><small>Sede autorizada</small><strong><?php echo htmlspecialchars($centro['direccion_principal']); ?></strong></div>
                                    <div class="hist-demo-data"><small>Contacto documental</small><strong><?php echo htmlspecialchars($centro['correo']); ?> · <?php echo htmlspecialchars($centro['telefono']); ?></strong></div>
                                    <div class="hist-demo-data"><small>Estado del certificado</small><strong><?php echo $centro_status['fecha'] ? 'Renovación ' . htmlspecialchars(date('d/m/Y', strtotime($centro_status['fecha']))) : 'Aún no tiene fecha de renovación'; ?></strong></div>
                                </div>
                                <details class="hist-upload-details">
                                    <summary><span><i class="fa-solid fa-circle-info"></i> Gestiona el certificado de custodia de este centro.</span><span class="hist-demo-button"><i class="fa-solid fa-cloud-arrow-up"></i> <?php echo $centro_custodia ? 'Actualizar certificado' : 'Cargar certificado'; ?></span></summary>
                                    <form class="history-upload-form" action="procesar_estandar5.php" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="accion" value="guardar_custodia_historia_clinica">
                                        <input type="hidden" name="centro_medico_id" value="<?php echo $centro_id_hist; ?>">
                                        <input type="hidden" name="texto_extraido" data-custody-text="<?php echo $centro_id_hist; ?>">
                                        <div><label class="med-label" for="custodiaPdf<?php echo $centro_id_hist; ?>">Certificado PDF</label><div class="med-file"><input id="custodiaPdf<?php echo $centro_id_hist; ?>" type="file" name="archivo_pdf" accept="application/pdf,.pdf" required data-custody-file="<?php echo $centro_id_hist; ?>"><div class="med-note" data-custody-status="<?php echo $centro_id_hist; ?>">Se intentará extraer la fecha del certificado.</div></div></div>
                                        <div><label class="med-label" for="custodiaFecha<?php echo $centro_id_hist; ?>">Fecha de emisión</label><input class="med-field" id="custodiaFecha<?php echo $centro_id_hist; ?>" type="date" name="fecha_emision" required data-custody-date="<?php echo $centro_id_hist; ?>"></div>
                                        <div><label class="med-label" for="custodiaObs<?php echo $centro_id_hist; ?>">Observaciones</label><input class="med-field" id="custodiaObs<?php echo $centro_id_hist; ?>" name="observaciones" placeholder="Ej. certificado anual"></div>
                                        <button class="med-btn" type="submit"><i class="fa-solid fa-cloud-arrow-up"></i> Guardar certificado</button>
                                    </form>
                                </details>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="med-card">
                <div class="med-card-head">
                    <div>
                        <h2>Soportes de custodia cargados</h2>
                        <p>Histórico de certificados emitidos por los centros médicos para custodia de historias clínicas.</p>
                    </div>
                    <i class="fa-solid fa-folder-open" style="color:var(--primary2);"></i>
                </div>
                <?php if (empty($hist_custodias)): ?>
                    <div class="med-empty">
                        <i class="fa-solid fa-file-circle-plus"></i>
                        <strong>No hay certificados cargados.</strong>
                        <p>Cuando cargues un PDF desde un centro registrado aparecerá en este consolidado.</p>
                    </div>
                <?php else: ?>
                    <div class="hist-demo-support-list">
                        <?php foreach ($hist_custodias as $custodia_index => $custodia): ?>
                            <?php
                                $custodia_status = estandar5_custodia_status($custodia['fecha_emision'] ?? null, $custodia['fecha_renovacion'] ?? null);
                                $custodia_archivo = basename((string)($custodia['archivo_pdf'] ?? 'certificado.pdf'));
                            ?>
                            <details class="hist-demo-support" <?php echo $custodia_index === 0 ? 'open' : ''; ?>>
                                <i class="fa-solid fa-file-medical hist-demo-support-watermark" aria-hidden="true"></i>
                                <summary class="hist-demo-support-summary">
                                    <span class="hist-demo-support-icon"><i class="fa-solid fa-file-pdf"></i></span>
                                    <span class="hist-demo-support-copy"><strong><?php echo htmlspecialchars($custodia['centro_nombre']); ?></strong><span>NIT <?php echo htmlspecialchars($custodia['centro_nit']); ?> · <?php echo htmlspecialchars($custodia_archivo); ?></span></span>
                                    <span class="hist-demo-support-meta"><strong><?php echo $custodia['fecha_emision'] ? htmlspecialchars(date('d/m/Y', strtotime($custodia['fecha_emision']))) : 'Sin fecha'; ?></strong><span>Fecha de emisión</span></span>
                                    <span class="hist-demo-support-meta"><strong><?php echo $custodia_status['fecha'] ? htmlspecialchars(date('d/m/Y', strtotime($custodia_status['fecha']))) : 'Sin fecha'; ?></strong><span>Renovación anual</span></span>
                                    <span class="med-badge <?php echo htmlspecialchars($custodia_status['estado']); ?>"><?php echo htmlspecialchars($custodia_status['texto']); ?></span>
                                    <span class="hist-demo-toggle"><i class="fa-solid fa-chevron-down"></i></span>
                                </summary>
                                <div class="hist-demo-support-detail">
                                    <div class="hist-demo-data"><small>Registro de carga</small><strong><?php echo htmlspecialchars(date('d/m/Y · H:i', strtotime($custodia['creado_en']))); ?></strong></div>
                                    <div class="hist-demo-data"><small>Contacto del centro</small><strong><?php echo htmlspecialchars($custodia['correo']); ?> · <?php echo htmlspecialchars($custodia['telefono']); ?></strong></div>
                                    <div class="hist-demo-data"><small>Observaciones</small><strong><?php echo trim((string)$custodia['observaciones']) !== '' ? nl2br(htmlspecialchars($custodia['observaciones'])) : 'Sin observaciones registradas.'; ?></strong></div>
                                    <div class="hist-demo-data"><small>Soporte documental</small><strong><a class="med-link" href="<?php echo htmlspecialchars($custodia['archivo_pdf']); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i> Abrir certificado PDF</a></strong></div>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php elseif ($modulo_actual === 'restricciones'): ?>
            <?php
                $msg = trim((string)($_GET['msg'] ?? ''));
                $tipo_msg = ($_GET['tipo'] ?? 'ok') === 'error' ? 'error' : 'ok';
                $pendientes_sst = 0;
                $sin_carta = 0;
                $cerrados_sst = 0;
                $cartas_firmadas = 0;
                $seguimientos_programados = 0;
                $en_gestion_sst = 0;
                foreach ($restriccion_rows as $row) {
                    if (($row['carta_firmada'] ?? 'No') !== 'Si') {
                        $sin_carta++;
                    } else {
                        $cartas_firmadas++;
                    }
                    if (trim((string)($row['sst_estado'] ?? '')) === 'Cerrado') {
                        $cerrados_sst++;
                    } elseif (trim((string)($row['sst_estado'] ?? '')) !== '') {
                        $en_gestion_sst++;
                    }
                    if (trim((string)($row['sst_fecha_real'] ?? '')) === '' && trim((string)($row['sst_estado'] ?? '')) !== 'Cerrado') {
                        $pendientes_sst++;
                    }
                    if (!empty($row['sst_fecha_programada'])) {
                        $seguimientos_programados++;
                    }
                }
                $restr_pct_cerrado = count($restriccion_rows) ? (int)round(($cerrados_sst / count($restriccion_rows)) * 100) : 0;
            ?>
            <?php if ($msg !== ''): ?>
                <div class="med-alert <?php echo htmlspecialchars($tipo_msg); ?>">
                    <i class="fa-solid <?php echo $tipo_msg === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
                    <span><?php echo htmlspecialchars($msg); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($usuario_rol === 'representante'): ?>
                <section class="rep-e5-hero">
                    <h2>Restricciones y recomendaciones medico laborales</h2>
                    <p>Vista informativa de trabajadores con restricciones, cartas generadas y seguimiento realizado por SST.</p>
                </section>
                <section class="rep-e5-grid" aria-label="Resumen informativo de restricciones y recomendaciones">
                    <article class="rep-e5-card">
                        <div class="rep-e5-card-head">
                            <div><h3>Trabajadores</h3><p>Registros con recomendaciones o restricciones.</p></div>
                            <div class="rep-e5-icon"><i class="fa-solid fa-notes-medical"></i></div>
                        </div>
                        <div class="rep-e5-metrics">
                            <div class="rep-e5-metric"><span>Registros</span><strong><?php echo count($restriccion_rows); ?></strong></div>
                            <div class="rep-e5-metric"><span>Pendientes SST</span><strong><?php echo $pendientes_sst; ?></strong></div>
                        </div>
                    </article>
                    <article class="rep-e5-card">
                        <div class="rep-e5-card-head">
                            <div><h3>Cartas y seguimiento</h3><p>Control de firma y cierre.</p></div>
                            <div class="rep-e5-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                        </div>
                        <div class="rep-e5-metrics">
                            <div class="rep-e5-metric"><span>Cartas pendientes</span><strong><?php echo $sin_carta; ?></strong></div>
                            <div class="rep-e5-metric"><span>Cerrados</span><strong><?php echo $cerrados_sst; ?></strong></div>
                        </div>
                        <div class="rep-e5-bars"><div class="rep-e5-bar"><span>Seguimiento cerrado</span><div class="rep-e5-track"><div class="rep-e5-fill" style="--bar:#16a34a;width:<?php echo $restr_pct_cerrado; ?>%"></div></div><strong><?php echo $restr_pct_cerrado; ?>%</strong></div></div>
                    </article>
                </section>
            <?php endif; ?>

            <section class="med-summary <?php echo $usuario_rol === 'representante' ? 'rep-hide' : ''; ?>">
                <article class="med-kpi"><i class="fa-solid fa-notes-medical med-kpi-watermark" aria-hidden="true"></i><span>Registros médicos</span><strong><?php echo count($restriccion_rows); ?></strong><small>Trabajadores con carta o recomendación registrada.</small></article>
                <article class="med-kpi blue"><i class="fa-solid fa-signature med-kpi-watermark" aria-hidden="true"></i><span>Cartas firmadas</span><strong><?php echo $cartas_firmadas; ?></strong><small>Comunicaciones entregadas y confirmadas.</small></article>
                <article class="med-kpi green"><i class="fa-solid fa-circle-check med-kpi-watermark" aria-hidden="true"></i><span>Seguimientos cerrados</span><strong><?php echo $cerrados_sst; ?></strong><small>Casos que completaron la gestión SST.</small></article>
                <article class="med-kpi violet"><i class="fa-solid fa-clock med-kpi-watermark" aria-hidden="true"></i><span>Pendientes SST</span><strong><?php echo $pendientes_sst; ?></strong><small>Registros que requieren programación o cierre.</small></article>
            </section>

            <section class="restr-dashboard-actions <?php echo $usuario_rol === 'representante' ? 'rep-hide' : ''; ?>">
                <i class="fa-solid fa-clipboard-list restr-dashboard-watermark" aria-hidden="true"></i>
                <div class="restr-dashboard-copy"><i class="fa-solid fa-clipboard-check"></i><div><h2>Gestión de restricciones y recomendaciones</h2><p>Crea nuevas cartas o actualiza el seguimiento desde un espacio separado, sin recargar este tablero principal.</p></div></div>
                <div class="restr-dashboard-buttons">
                    <a class="restr-dashboard-link primary" href="gestion_restricciones_medicas?vista=nueva"><i class="fa-solid fa-plus"></i> Crear nueva carta</a>
                    <a class="restr-dashboard-link secondary" href="gestion_restricciones_medicas?vista=seguimiento"><i class="fa-solid fa-route"></i> Actualizar seguimiento</a>
                </div>
            </section>

            <section class="restr-overview <?php echo $usuario_rol === 'representante' ? 'rep-hide' : ''; ?>" aria-label="Estado operativo de restricciones médicas">
                <article class="restr-overview-card"><i class="fa-solid fa-file-signature"></i><small>Gestión documental</small><strong><?php echo $sin_carta; ?> carta(s) por firmar</strong><span><?php echo $cartas_firmadas; ?> comunicaciones ya fueron confirmadas.</span></article>
                <article class="restr-overview-card"><i class="fa-solid fa-calendar-check"></i><small>Programación SST</small><strong><?php echo $seguimientos_programados; ?> seguimiento(s) programado(s)</strong><span><?php echo $pendientes_sst; ?> casos aún requieren una fecha real o cierre.</span></article>
                <article class="restr-overview-card"><i class="fa-solid fa-chart-line"></i><small>Avance general</small><strong><?php echo $restr_pct_cerrado; ?>% completado</strong><span><?php echo $en_gestion_sst; ?> caso(s) continúan en gestión activa.</span><div class="restr-progress"><span style="width:<?php echo $restr_pct_cerrado; ?>%"></span></div></article>
            </section>

            <section class="med-card">
                <div class="med-card-head">
                    <div><h2>Seguimiento de recomendaciones médicas</h2><p>Consulta trabajador, carta, restricción y avance SST en cards desplegables.</p></div>
                    <span class="med-demo-count"><i class="fa-solid fa-notes-medical"></i> <?php echo count($restriccion_rows); ?> registros</span>
                </div>
                <?php if (empty($restriccion_rows)): ?>
                    <div class="med-empty"><i class="fa-solid fa-notes-medical"></i><strong>No hay restricciones o recomendaciones registradas.</strong><p>Usa “Crear nueva carta” para iniciar el primer seguimiento médico laboral.</p></div>
                <?php else: ?>
                    <div class="restr-record-list">
                        <?php foreach ($restriccion_rows as $restr_index => $row): ?>
                            <?php $pves = estandar5_decode_list($row['pve_json'] ?? ''); ?>
                            <details class="restr-record" <?php echo $restr_index === 0 ? 'open' : ''; ?>>
                                <i class="fa-solid fa-notes-medical restr-record-watermark" aria-hidden="true"></i>
                                <summary class="restr-record-summary">
                                    <span class="restr-record-icon"><i class="fa-solid fa-user-doctor"></i></span>
                                    <span class="restr-record-copy"><strong><?php echo htmlspecialchars(trim($row['nombre'] . ' ' . $row['apellido'])); ?></strong><span>C.C. <?php echo htmlspecialchars($row['cedula']); ?> · <?php echo htmlspecialchars($row['cargo'] ?: 'Sin cargo'); ?></span></span>
                                    <span class="restr-record-meta"><strong><?php echo htmlspecialchars($row['tipo_restriccion'] ?: 'Sin tipo'); ?></strong><span><?php echo $row['carta_fecha'] ? 'Carta del ' . htmlspecialchars(date('d/m/Y', strtotime($row['carta_fecha']))) : 'Carta sin fecha'; ?></span></span>
                                    <span class="restr-record-meta"><strong><?php echo htmlspecialchars($row['sst_estado'] ?: 'Sin estado SST'); ?></strong><span><?php echo $row['sst_fecha_programada'] ? 'Programado: ' . htmlspecialchars(date('d/m/Y', strtotime($row['sst_fecha_programada']))) : 'Sin programación SST'; ?></span></span>
                                    <span class="med-badge <?php echo ($row['sst_estado'] ?? '') === 'Cerrado' ? 'vigente' : (($row['carta_firmada'] ?? 'No') === 'Si' ? 'programada' : 'critico'); ?>"><?php echo ($row['sst_estado'] ?? '') === 'Cerrado' ? 'Cerrado' : (($row['carta_firmada'] ?? 'No') === 'Si' ? 'En gestión' : 'Carta pendiente'); ?></span>
                                    <span class="restr-record-toggle"><i class="fa-solid fa-chevron-down"></i></span>
                                </summary>
                                <div class="restr-record-detail">
                                    <div class="restr-record-data"><small>Carta y entrega</small><strong><?php echo ($row['carta_firmada'] ?? 'No') === 'Si' ? 'Firmada' : 'Pendiente de firma'; ?> · <?php echo $row['fecha_entrega_carta'] ? htmlspecialchars(date('d/m/Y', strtotime($row['fecha_entrega_carta']))) : 'Sin fecha de entrega'; ?></strong></div>
                                    <div class="restr-record-data"><small>Concepto médico</small><strong><?php echo htmlspecialchars($row['concepto_medico'] ?: 'Sin concepto registrado'); ?></strong></div>
                                    <div class="restr-record-data"><small>IPS / proyecto</small><strong><?php echo htmlspecialchars($row['ips_nombre'] ?: 'Sin IPS'); ?> · <?php echo htmlspecialchars($row['proyecto'] ?: 'Sin proyecto'); ?></strong></div>
                                    <div class="restr-record-data"><small>Responsable SST</small><strong><?php echo htmlspecialchars($row['sst_responsable'] ?: 'Sin asignar'); ?> · <?php echo $row['sst_fecha_real'] ? htmlspecialchars(date('d/m/Y', strtotime($row['sst_fecha_real']))) : 'Sin fecha real'; ?></strong></div>
                                    <div class="restr-record-data wide"><small>Restricción y recomendaciones laborales</small><strong><?php echo nl2br(htmlspecialchars($row['restriccion'] ?: $row['recomendaciones_laborales'] ?: 'Sin detalle registrado.')); ?></strong></div>
                                    <div class="restr-record-data wide"><small>Programas de vigilancia epidemiológica</small><div class="restr-record-tags"><?php if (empty($pves)): ?><span class="med-muted">Sin programas asociados.</span><?php else: ?><?php foreach ($pves as $pve): ?><span class="restr-pill"><?php echo htmlspecialchars($pve); ?></span><?php endforeach; ?><?php endif; ?></div></div>
                                    <div class="restr-record-data wide"><small>Historial SST / ARL</small><strong><?php echo nl2br(htmlspecialchars($row['sst_historial'] ?: $row['arl_historial'] ?: 'Sin historial registrado.')); ?></strong></div>
                                    <div class="restr-record-data wide"><small>Acciones</small><strong><?php if (!empty($row['carta_pdf'])): ?><a class="med-link" href="<?php echo htmlspecialchars($row['carta_pdf']); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i> Ver carta</a> · <?php endif; ?><a class="med-link" href="gestion_restricciones_medicas?vista=seguimiento&registro_id=<?php echo (int)$row['id']; ?>"><i class="fa-solid fa-route"></i> Actualizar seguimiento</a></strong></div>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if (false): // Formularios trasladados a gestion_restricciones_medicas. ?>

            <section class="med-card <?php echo $usuario_rol === 'representante' ? 'rep-hide' : ''; ?>">
                <div class="med-card-head">
                    <div>
                        <h2>Crear carta de recomendaciones médicas</h2>
                        <p>Primer paso del flujo: esta carta alimenta automáticamente la matriz y evita volver a registrar trabajador, recomendaciones y restricciones.</p>
                    </div>
                    <i class="fa-solid fa-clipboard-list" style="color:var(--primary2);"></i>
                </div>
                <form class="restr-form" action="procesar_estandar5.php" method="POST" id="restrictionForm">
                    <input type="hidden" name="accion" value="crear_carta_recomendacion_medica">
                    <div class="wide">
                        <label class="med-label" for="restrTrabajador">Trabajador</label>
                        <select class="med-field" name="trabajador_id" id="restrTrabajador" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($restriccion_trabajadores as $trabajador): ?>
                                <?php $cargo_trabajador = estandar5_eval_cargo_trabajador($trabajador); ?>
                                <option value="<?php echo (int)$trabajador['id']; ?>" data-cargo="<?php echo htmlspecialchars($cargo_trabajador); ?>">
                                    <?php echo htmlspecialchars(trim($trabajador['nombre'] . ' ' . $trabajador['apellido']) . ' · C.C. ' . $trabajador['cedula']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="med-label" for="restrCargo">Cargo</label>
                        <input class="med-field" id="restrCargo" name="cargo" placeholder="Cargo">
                    </div>
                    <div>
                        <label class="med-label" for="restrFechaIngreso">Fecha de ingreso</label>
                        <input class="med-field" id="restrFechaIngreso" type="date" name="fecha_ingreso">
                    </div>
                    <div>
                        <label class="med-label" for="restrCartaFecha">Fecha de carta</label>
                        <input class="med-field" id="restrCartaFecha" type="date" name="carta_fecha" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div>
                        <label class="med-label" for="restrFechaExamen">Fecha examen</label>
                        <input class="med-field" id="restrFechaExamen" type="date" name="fecha_examen">
                    </div>
                    <div>
                        <label class="med-label" for="restrIps">IPS / centro médico</label>
                        <input class="med-field" id="restrIps" name="ips_nombre" placeholder="IPS que emite concepto">
                    </div>
                    <div>
                        <label class="med-label" for="restrConcepto">Concepto médico</label>
                        <input class="med-field" id="restrConcepto" name="concepto_medico" placeholder="Ej. Apto con restricciones">
                    </div>
                    <div>
                        <label class="med-label" for="restrProyecto">Proyecto</label>
                        <input class="med-field" id="restrProyecto" name="proyecto" placeholder="Proyecto / sede">
                    </div>
                    <div class="wide">
                        <label class="med-label" for="restrTipo">Tipo de restricciones</label>
                        <select class="med-field" id="restrTipo" name="tipo_restriccion" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($restriccion_tipos as $tipo): ?>
                                <option value="<?php echo htmlspecialchars($tipo); ?>"><?php echo htmlspecialchars($tipo); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="wide">
                        <label class="med-label" for="restrRestriccion">Restricción</label>
                        <input class="med-field" id="restrRestriccion" name="restriccion" placeholder="Detalle específico de la restricción">
                    </div>
                    <div class="wide">
                        <label class="med-label" for="restrLaborales">Recomendaciones médico laborales</label>
                        <textarea class="med-field med-extract-box" id="restrLaborales" name="recomendaciones_laborales"></textarea>
                    </div>
                    <div class="wide">
                        <label class="med-label" for="restrGenerales">Recomendaciones médico generales</label>
                        <textarea class="med-field med-extract-box" id="restrGenerales" name="recomendaciones_generales"></textarea>
                    </div>
                    <div class="full">
                        <label class="med-label">PVE / programa de gestión</label>
                        <div class="restr-checks">
                            <?php foreach ($restriccion_pve_programas as $programa): ?>
                                <label class="restr-check">
                                    <input type="checkbox" name="pve[]" value="<?php echo htmlspecialchars($programa); ?>">
                                    <span><?php echo htmlspecialchars($programa); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="full med-actions-row">
                        <span class="med-note">Al crearla se genera PDF de carta y se abre el registro en la matriz para seguimiento posterior.</span>
                        <button class="med-btn" type="submit" <?php echo $usuario_rol === 'sst' ? '' : 'disabled'; ?>><i class="fa-solid fa-file-pdf"></i> Crear carta</button>
                    </div>
                </form>
            </section>

            <section class="med-card <?php echo $usuario_rol === 'representante' ? 'rep-hide' : ''; ?>">
                <div class="med-card-head">
                    <div>
                        <h2>Actualizar seguimiento de carta creada</h2>
                        <p>Segundo paso del flujo: selecciona una carta existente y registra entrega, firma, seguimiento SST y seguimiento ARL sin duplicar el proceso.</p>
                    </div>
                    <i class="fa-solid fa-route" style="color:var(--primary2);"></i>
                </div>
                <form class="restr-form" action="procesar_estandar5.php" method="POST">
                    <input type="hidden" name="accion" value="actualizar_seguimiento_restriccion">
                    <div class="wide">
                        <label class="med-label" for="restrRegistro">Carta / trabajador</label>
                        <select class="med-field" id="restrRegistro" name="registro_id" required <?php echo empty($restriccion_rows) ? 'disabled' : ''; ?>>
                            <option value="">Selecciona carta creada...</option>
                            <?php foreach ($restriccion_rows as $row): ?>
                                <option value="<?php echo (int)$row['id']; ?>">
                                    <?php echo htmlspecialchars(trim($row['nombre'] . ' ' . $row['apellido']) . ' · C.C. ' . $row['cedula'] . ' · ' . ($row['carta_fecha'] ? date('d/m/Y', strtotime($row['carta_fecha'])) : 'Sin fecha')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="med-label" for="restrCarta">Carta firmada</label>
                        <select class="med-field" id="restrCarta" name="carta_firmada" <?php echo empty($restriccion_rows) ? 'disabled' : ''; ?>>
                            <option value="No">No</option>
                            <option value="Si">Sí</option>
                        </select>
                    </div>
                    <div>
                        <label class="med-label" for="restrFechaCarta">Fecha entrega carta</label>
                        <input class="med-field" id="restrFechaCarta" type="date" name="fecha_entrega_carta" <?php echo empty($restriccion_rows) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label class="med-label" for="restrSstProg">Fecha programada SST</label>
                        <input class="med-field" id="restrSstProg" type="date" name="sst_fecha_programada" <?php echo empty($restriccion_rows) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label class="med-label" for="restrSstReal">Fecha real SST</label>
                        <input class="med-field" id="restrSstReal" type="date" name="sst_fecha_real" <?php echo empty($restriccion_rows) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label class="med-label" for="restrSstResp">Responsable SST</label>
                        <input class="med-field" id="restrSstResp" name="sst_responsable" placeholder="Responsable" <?php echo empty($restriccion_rows) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label class="med-label" for="restrSstEstado">Estado SST</label>
                        <select class="med-field" id="restrSstEstado" name="sst_estado" <?php echo empty($restriccion_rows) ? 'disabled' : ''; ?>>
                            <option value="">Selecciona...</option>
                            <?php foreach ($restriccion_estados as $estado): ?>
                                <option value="<?php echo htmlspecialchars($estado); ?>"><?php echo htmlspecialchars($estado); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="wide">
                        <label class="med-label" for="restrSstHist">Historial seguimiento SST</label>
                        <textarea class="med-field med-extract-box" id="restrSstHist" name="sst_historial" <?php echo empty($restriccion_rows) ? 'disabled' : ''; ?>></textarea>
                    </div>
                    <div>
                        <label class="med-label" for="restrArlReal">Fecha real ARL</label>
                        <input class="med-field" id="restrArlReal" type="date" name="arl_fecha_real" <?php echo empty($restriccion_rows) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label class="med-label" for="restrArlResp">Responsable ARL</label>
                        <input class="med-field" id="restrArlResp" name="arl_responsable" <?php echo empty($restriccion_rows) ? 'disabled' : ''; ?>>
                    </div>
                    <div class="wide">
                        <label class="med-label" for="restrArlHist">Historial seguimiento ARL</label>
                        <textarea class="med-field med-extract-box" id="restrArlHist" name="arl_historial" <?php echo empty($restriccion_rows) ? 'disabled' : ''; ?>></textarea>
                    </div>
                    <div class="full med-actions-row">
                        <span class="med-note">El seguimiento se actualiza sobre la carta seleccionada.</span>
                        <button class="med-btn" type="submit" <?php echo $usuario_rol === 'sst' && !empty($restriccion_rows) ? '' : 'disabled'; ?>><i class="fa-solid fa-rotate"></i> Actualizar seguimiento</button>
                    </div>
                </form>
            </section>

            <section class="med-card restr-matrix">
                <div class="med-card-head">
                    <div>
                        <h2>Matriz de seguimiento a recomendaciones médicas</h2>
                        <p>Control horizontal de recomendaciones laborales, generales, PVE, restricciones y seguimiento por área SST/ARL.</p>
                    </div>
                    <i class="fa-solid fa-table" style="color:var(--primary2);"></i>
                </div>
                <?php if (empty($restriccion_rows)): ?>
                    <div class="med-empty">
                        <i class="fa-solid fa-notes-medical"></i>
                        <strong>No hay restricciones o recomendaciones registradas.</strong>
                        <p>Cuando guardes el primer seguimiento aparecerá en la matriz.</p>
                    </div>
                <?php else: ?>
                    <div class="med-table-wrap">
                        <table class="med-table">
                            <thead>
                                <tr class="group-head">
                                    <th colspan="6">Datos del trabajador / carta</th>
                                    <th colspan="2">Recomendaciones</th>
                                    <th colspan="5">PVE / programa de gestión</th>
                                    <th colspan="2">Restricción</th>
                                    <th colspan="5">Seguimiento por área SST</th>
                                    <th colspan="3">Seguimiento #1 por ARL</th>
                                </tr>
                                <tr>
                                    <th>No. identificación</th><th>Trabajador</th><th>Cargo</th><th>Fecha ingreso</th><th>Carta generada</th><th>Carta recomendaciones</th>
                                    <th>Médico laborales</th><th>Médico generales</th>
                                    <?php foreach ($restriccion_pve_programas as $programa): ?><th><?php echo htmlspecialchars($programa); ?></th><?php endforeach; ?>
                                    <th>Tipo de restricciones</th><th>Restricción</th>
                                    <th>Fecha programada</th><th>Fecha real</th><th>Responsable</th><th>Estado</th><th>Historial seguimiento</th>
                                    <th>Fecha real</th><th>Responsable</th><th>Historial seguimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($restriccion_rows as $row): ?>
                                    <?php $pves = estandar5_decode_list($row['pve_json'] ?? ''); ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['cedula']); ?></td>
                                        <td><span class="med-worker-name"><?php echo htmlspecialchars(trim($row['nombre'] . ' ' . $row['apellido'])); ?></span><span class="restr-mini"><?php echo htmlspecialchars($row['email']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['cargo'] ?: 'Sin cargo'); ?></td>
                                        <td><?php echo $row['fecha_ingreso'] ? htmlspecialchars(date('d/m/Y', strtotime($row['fecha_ingreso']))) : '<span class="med-muted">Sin fecha</span>'; ?></td>
                                        <td>
                                            <?php if (!empty($row['carta_pdf'])): ?>
                                                <a class="med-link" href="<?php echo htmlspecialchars($row['carta_pdf']); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i> Ver carta</a>
                                                <span class="restr-mini"><?php echo $row['carta_fecha'] ? htmlspecialchars(date('d/m/Y', strtotime($row['carta_fecha']))) : 'Sin fecha'; ?></span>
                                            <?php else: ?>
                                                <span class="med-muted">Sin PDF</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="med-badge <?php echo $row['carta_firmada'] === 'Si' ? 'vigente' : 'critico'; ?>"><?php echo htmlspecialchars($row['carta_firmada']); ?></span><span class="restr-mini"><?php echo $row['fecha_entrega_carta'] ? htmlspecialchars(date('d/m/Y', strtotime($row['fecha_entrega_carta']))) : 'Sin entrega'; ?></span></td>
                                        <td><?php echo nl2br(htmlspecialchars($row['recomendaciones_laborales'] ?: '')); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($row['recomendaciones_generales'] ?: '')); ?></td>
                                        <?php foreach ($restriccion_pve_programas as $programa): ?>
                                            <td><?php echo in_array($programa, $pves, true) ? '<span class="med-badge vigente">Sí</span>' : '<span class="med-muted">No</span>'; ?></td>
                                        <?php endforeach; ?>
                                        <td><?php echo htmlspecialchars($row['tipo_restriccion'] ?: 'Sin tipo'); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($row['restriccion'] ?: '')); ?></td>
                                        <td><?php echo $row['sst_fecha_programada'] ? htmlspecialchars(date('d/m/Y', strtotime($row['sst_fecha_programada']))) : '<span class="med-muted">Sin fecha</span>'; ?></td>
                                        <td><?php echo $row['sst_fecha_real'] ? htmlspecialchars(date('d/m/Y', strtotime($row['sst_fecha_real']))) : '<span class="med-muted">Pendiente</span>'; ?></td>
                                        <td><?php echo htmlspecialchars($row['sst_responsable'] ?: 'Sin asignar'); ?></td>
                                        <td><span class="med-badge <?php echo ($row['sst_estado'] ?? '') === 'Cerrado' ? 'vigente' : 'alerta'; ?>"><?php echo htmlspecialchars($row['sst_estado'] ?: 'Sin estado'); ?></span></td>
                                        <td><?php echo nl2br(htmlspecialchars($row['sst_historial'] ?: '')); ?></td>
                                        <td><?php echo $row['arl_fecha_real'] ? htmlspecialchars(date('d/m/Y', strtotime($row['arl_fecha_real']))) : '<span class="med-muted">Sin fecha</span>'; ?></td>
                                        <td><?php echo htmlspecialchars($row['arl_responsable'] ?: 'Sin asignar'); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($row['arl_historial'] ?: '')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        <?php elseif ($modulo): ?>
            <section class="workspace-grid">
                <article class="module-card">
                    <div class="module-card-head">
                        <span class="module-code"><i class="fa-solid <?php echo htmlspecialchars($modulo['icono']); ?>"></i> <?php echo htmlspecialchars($modulo['codigo']); ?></span>
                        <h2><?php echo htmlspecialchars($modulo['titulo']); ?></h2>
                    </div>
                    <div class="module-card-body">
                        <p><?php echo htmlspecialchars($modulo['descripcion']); ?></p>
                        <div class="state-row">
                            <div class="state-box"><span>Estado</span><strong>Diseño</strong></div>
                            <div class="state-box"><span>Datos</span><strong>Pendiente</strong></div>
                            <div class="state-box"><span>Rol</span><strong><?php echo $usuario_rol === 'sst' ? 'Gestión SST' : 'Resumen'; ?></strong></div>
                        </div>
                    </div>
                </article>

                <article class="detail-card">
                    <h3>Base funcional del submódulo</h3>
                    <p>Por ahora dejamos el contenedor visual listo para conectar datos y flujos cuando definas cómo debe operar cada sección.</p>
                    <ul class="detail-list">
                        <li><i class="fa-solid fa-check"></i><span>Acceso desde el menú lateral y desde los botones internos del estándar 5.</span></li>
                        <li><i class="fa-solid fa-check"></i><span>Selección del submódulo actual sin perder la estructura general de la página.</span></li>
                        <li><i class="fa-solid fa-check"></i><span>Diseño responsive para escritorio, portátil y celular.</span></li>
                        <li><i class="fa-solid fa-check"></i><span>Preparado para separar vista de gestión del Responsable SST y vista resumen del Representante Legal.</span></li>
                    </ul>
                    <div class="notice">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>No se creó ni modificó ninguna tabla todavía. Cuando conectemos datos, dejaré el SQL correspondiente en un archivo de migración.</span>
                    </div>
                </article>
            </section>
        <?php endif; ?>
    </div>
</main>
<?php if ($modulo_actual === 'sociodemografica'): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    function setDisclosureState(container, open) {
        const toggle = container.querySelector('[data-disclosure-toggle]');
        if (!toggle) return;
        const panelId = toggle.getAttribute('aria-controls');
        const panel = panelId ? document.getElementById(panelId) : null;
        container.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel?.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    document.querySelectorAll('[data-disclosure-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const container = toggle.closest('[data-disclosure]');
            if (!container) return;
            setDisclosureState(container, !container.classList.contains('is-open'));
        });
    });

});
</script>
<?php endif; ?>
<?php if ($modulo_actual === 'perfiles-cargo' && $usuario_rol === 'sst'): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tipoProcesoSelect = document.getElementById('tipoProcesoSelect');
    const newProcessPanel = document.getElementById('newProcessPanel');
    const tipoProcesoNuevo = document.getElementById('tipoProcesoNuevo');
    const tipoOperacionSelect = document.querySelector('select[name="tipo_operacion"]');
    const toolGroups = document.querySelectorAll('[data-tool-group]');

    function toggleNewProcessPanel() {
        const active = tipoProcesoSelect?.value === '__nuevo_proceso__';
        newProcessPanel?.classList.toggle('active', active);
        if (tipoProcesoNuevo) {
            tipoProcesoNuevo.required = active;
            if (!active) tipoProcesoNuevo.value = '';
        }
    }

    tipoProcesoSelect?.addEventListener('change', toggleNewProcessPanel);
    toggleNewProcessPanel();

    function updateToolGroups() {
        const type = tipoOperacionSelect?.value || '';
        const visibleByType = {
            'Administrativo': ['administrativo'],
            'Operativo': ['menores', 'electricas'],
            'Mixto': ['administrativo', 'menores', 'electricas']
        };
        const visibleGroups = visibleByType[type] || [];
        toolGroups.forEach((group) => {
            const active = visibleGroups.includes(group.dataset.toolGroup);
            group.classList.toggle('active', active);
            group.querySelectorAll('input').forEach((input) => {
                input.disabled = !active;
                if (!active && input.type === 'checkbox') input.checked = false;
                if (!active && input.type !== 'checkbox') input.value = '';
            });
        });
    }

    tipoOperacionSelect?.addEventListener('change', updateToolGroups);
    updateToolGroups();

    function refreshRemoveButtons(list) {
        const rows = list.querySelectorAll('.cargo-dynamic-row');
        rows.forEach((row) => {
            const button = row.querySelector('[data-remove-row]');
            if (button) button.disabled = rows.length === 1;
        });
    }

    document.querySelectorAll('[data-dynamic-list]').forEach(refreshRemoveButtons);

    document.querySelectorAll('[data-add-row]').forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.addRow;
            const list = document.querySelector(`[data-dynamic-list="${key}"]`);
            if (!list) return;
            const first = list.querySelector('.cargo-dynamic-row');
            const clone = first.cloneNode(true);
            const input = clone.querySelector('input');
            if (input) {
                input.value = '';
                input.required = key === 'tareas';
                input.placeholder = key === 'tareas' ? `Tarea ${list.children.length + 1}` : 'Sede opcional';
            }
            list.appendChild(clone);
            refreshRemoveButtons(list);
            input?.focus();
        });
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-row]');
        if (!button) return;
        const list = button.closest('[data-dynamic-list]');
        const row = button.closest('.cargo-dynamic-row');
        if (!list || !row || list.querySelectorAll('.cargo-dynamic-row').length === 1) return;
        row.remove();
        refreshRemoveButtons(list);
    });
});
</script>
<?php endif; ?>
<?php if (in_array($modulo_actual, ['evaluaciones-medicas', 'historias-clinicas'], true) && $usuario_rol === 'sst'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<?php endif; ?>
<?php if ($modulo_actual === 'evaluaciones-medicas' && $usuario_rol === 'sst'): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const bulkForm = document.getElementById('medBulkProgramForm');
    const workerSearch = document.getElementById('medWorkerSearch');
    const workerRows = [...document.querySelectorAll('[data-worker-row]')];
    const selectVisible = document.getElementById('medSelectVisible');
    const selectedCount = document.getElementById('medSelectedCount');
    const bulkSubmit = document.getElementById('medBulkSubmit');
    const pagePrev = document.getElementById('medPagePrev');
    const pageNext = document.getElementById('medPageNext');
    const pageStatus = document.getElementById('medPageStatus');
    const pageSize = document.getElementById('medPageSize');
    const workerEmpty = document.getElementById('medWorkerEmpty');
    const canBulkProgram = bulkForm?.dataset.canProgram === '1';
    let workersPerPage = Number(pageSize?.value || 10);
    let workerPage = 1;
    let visibleWorkerRows = [];

    const normalizeWorkerText = (value) => String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const workerCheckbox = (row) => row.querySelector('input[type="checkbox"]');
    const currentWorkerPageRows = () => visibleWorkerRows.slice((workerPage - 1) * workersPerPage, workerPage * workersPerPage);

    const refreshWorkerSelection = () => {
        const enabledChecks = workerRows.map(workerCheckbox).filter((check) => check && !check.disabled);
        const selected = enabledChecks.filter((check) => check.checked).length;
        if (selectedCount) selectedCount.textContent = `${selected} seleccionado${selected === 1 ? '' : 's'}`;
        if (bulkSubmit) bulkSubmit.disabled = selected === 0 || !canBulkProgram;
        const pageChecks = currentWorkerPageRows().map(workerCheckbox).filter((check) => check && !check.disabled);
        if (selectVisible) {
            selectVisible.checked = pageChecks.length > 0 && pageChecks.every((check) => check.checked);
            selectVisible.indeterminate = pageChecks.some((check) => check.checked) && !pageChecks.every((check) => check.checked);
        }
    };

    const renderWorkerPage = () => {
        const query = normalizeWorkerText(workerSearch?.value);
        visibleWorkerRows = workerRows.filter((row) => normalizeWorkerText(row.dataset.search).includes(query));
        const pages = Math.max(1, Math.ceil(visibleWorkerRows.length / workersPerPage));
        workerPage = Math.min(Math.max(1, workerPage), pages);
        workerRows.forEach((row) => { row.hidden = true; });
        currentWorkerPageRows().forEach((row) => { row.hidden = false; });
        if (workerEmpty) workerEmpty.hidden = visibleWorkerRows.length > 0;
        if (pageStatus) pageStatus.textContent = visibleWorkerRows.length ? `Página ${workerPage} de ${pages} · ${visibleWorkerRows.length} trabajador(es)` : 'Sin resultados';
        if (pagePrev) pagePrev.disabled = workerPage <= 1;
        if (pageNext) pageNext.disabled = workerPage >= pages;
        refreshWorkerSelection();
    };

    workerSearch?.addEventListener('input', () => { workerPage = 1; renderWorkerPage(); });
    pagePrev?.addEventListener('click', () => { if (workerPage > 1) { workerPage--; renderWorkerPage(); } });
    pageNext?.addEventListener('click', () => { const pages = Math.max(1, Math.ceil(visibleWorkerRows.length / workersPerPage)); if (workerPage < pages) { workerPage++; renderWorkerPage(); } });
    pageSize?.addEventListener('change', () => { workersPerPage = pageSize.value === 'all' ? Number.MAX_SAFE_INTEGER : Number(pageSize.value || 10); workerPage = 1; renderWorkerPage(); });
    selectVisible?.addEventListener('change', () => { currentWorkerPageRows().map(workerCheckbox).filter((check) => check && !check.disabled).forEach((check) => { check.checked = selectVisible.checked; }); refreshWorkerSelection(); });
    workerRows.forEach((row) => workerCheckbox(row)?.addEventListener('change', refreshWorkerSelection));
    bulkForm?.addEventListener('submit', (event) => { if (!workerRows.some((row) => workerCheckbox(row)?.checked)) event.preventDefault(); });
    renderWorkerPage();

    const trabajadorSelect = document.getElementById('supportTrabajador');
    const solicitudSelect = document.getElementById('supportSolicitud');
    const perfilSelect = document.getElementById('supportPerfil');
    const centroSelect = document.getElementById('supportCentro');
    const archivoInput = document.getElementById('archivoPdfMedico');
    const extractStatus = document.getElementById('extractStatus');
    const textoExtraidoInput = document.getElementById('textoExtraidoInput');
    const documentoFilter = document.getElementById('medDocumentoFilter');
    const documentoClear = document.getElementById('medDocumentoClear');
    const documentoFilterStatus = document.getElementById('medDocumentoFilterStatus');

    const fields = {
        nombre: document.getElementById('nombreTrabajadorMedico'),
        cedula: document.getElementById('cedulaTrabajadorMedico'),
        cargo: document.getElementById('cargoTrabajadorMedico'),
        tipoExamen: document.getElementById('tipoExamenMedico'),
        resultado: document.getElementById('resultadoMedico'),
        aptitud: document.getElementById('aptitudMedico'),
        centroTexto: document.getElementById('centroMedicoTexto'),
        fechaExp: document.getElementById('fechaExpedicionMedico'),
        fechaVen: document.getElementById('fechaVencimientoMedico'),
        diasAccion: document.getElementById('diasAccionMedico'),
        tiempoProgramar: document.getElementById('tiempoProgramarMedico')
    };

    const trabajadorOptions = [...(trabajadorSelect?.options || [])].map((option) => ({
        option,
        label: option.textContent.toLowerCase(),
        cedula: (option.dataset.cedula || '').toLowerCase()
    }));

    function filterTrabajadores() {
        const query = (documentoFilter?.value || '').trim().toLowerCase();
        let visible = 0;
        trabajadorOptions.forEach(({ option, label, cedula }) => {
            const isPlaceholder = !option.value;
            const match = isPlaceholder || query === '' || cedula.includes(query) || label.includes(query);
            option.hidden = !match;
            if (!isPlaceholder && match) visible++;
        });
        if (trabajadorSelect && trabajadorSelect.selectedOptions[0]?.hidden) {
            trabajadorSelect.value = '';
            trabajadorSelect.dispatchEvent(new Event('change'));
        }
        if (documentoFilterStatus) {
            documentoFilterStatus.textContent = query === '' ? 'Filtra el listado de trabajadores antes de cargar el soporte.' : `${visible} trabajador(es) encontrado(s).`;
        }
    }

    documentoFilter?.addEventListener('input', filterTrabajadores);
    documentoClear?.addEventListener('click', () => {
        if (documentoFilter) documentoFilter.value = '';
        filterTrabajadores();
        documentoFilter?.focus();
    });

    function addMonths(value, months) {
        if (!value) return '';
        const date = new Date(value + 'T00:00:00');
        if (Number.isNaN(date.getTime())) return '';
        date.setMonth(date.getMonth() + months);
        return date.toISOString().slice(0, 10);
    }

    function daysUntil(value) {
        if (!value) return '';
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const target = new Date(value + 'T00:00:00');
        return Math.ceil((target - today) / 86400000);
    }

    function refreshExamExpiry() {
        const selectedPerfil = perfilSelect?.selectedOptions?.[0];
        const operation = selectedPerfil?.dataset?.operacion || 'Mixto';
        const months = operation === 'Administrativo' ? 36 : 18;
        if (fields.fechaExp?.value && !fields.fechaVen?.value) {
            fields.fechaVen.value = addMonths(fields.fechaExp.value, months);
        }
        if (fields.fechaVen?.value) {
            fields.diasAccion.value = daysUntil(fields.fechaVen.value);
            fields.tiempoProgramar.value = daysUntil(fields.fechaVen.value) <= 90 ? 'Programar renovacion' : 'Seguimiento';
        }
    }

    trabajadorSelect?.addEventListener('change', () => {
        const opt = trabajadorSelect.selectedOptions[0];
        fields.nombre.value = opt?.dataset?.nombre || '';
        fields.cedula.value = opt?.dataset?.cedula || '';
        fields.cargo.value = opt?.dataset?.cargo || '';
    });

    solicitudSelect?.addEventListener('change', () => {
        const opt = solicitudSelect.selectedOptions[0];
        if (!opt || !opt.value) return;
        if (opt.dataset.trabajador) {
            trabajadorSelect.value = opt.dataset.trabajador;
            trabajadorSelect.dispatchEvent(new Event('change'));
        }
        if (opt.dataset.perfil) {
            perfilSelect.value = opt.dataset.perfil;
            perfilSelect.dispatchEvent(new Event('change'));
        }
        if (opt.dataset.centro) {
            centroSelect.value = opt.dataset.centro;
            centroSelect.dispatchEvent(new Event('change'));
        }
    });

    perfilSelect?.addEventListener('change', () => {
        const opt = perfilSelect.selectedOptions[0];
        if (opt?.dataset?.cargo && (!fields.cargo.value || fields.cargo.value === 'Sin cargo registrado')) {
            fields.cargo.value = opt.dataset.cargo;
        }
        refreshExamExpiry();
    });

    centroSelect?.addEventListener('change', () => {
        const opt = centroSelect.selectedOptions[0];
        if (opt?.dataset?.nombre) fields.centroTexto.value = opt.dataset.nombre;
    });

    [fields.fechaExp, fields.fechaVen].forEach((el) => el?.addEventListener('change', refreshExamExpiry));
    function toIsoDate(value) {
        const clean = String(value || '').trim();
        let m = clean.match(/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/);
        if (m) return `${m[3]}-${m[2].padStart(2, '0')}-${m[1].padStart(2, '0')}`;
        m = clean.match(/\b(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})\b/);
        if (m) return `${m[1]}-${m[2].padStart(2, '0')}-${m[3].padStart(2, '0')}`;
        return '';
    }

    function valueNear(text, labels) {
        const escaped = labels.map((label) => label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|');
        const regex = new RegExp(`(?:${escaped})\\s*[:\\-]?\\s*([^\\n\\r]{2,90})`, 'i');
        const found = text.match(regex);
        return found ? found[1].trim().replace(/\s{2,}/g, ' ') : '';
    }

    function fillIfEmpty(input, value) {
        if (input && !input.value && value) input.value = value;
    }

    function setSelectByKeywords(select, text, options) {
        if (!select || select.value) return;
        const haystack = String(text || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        for (const item of options) {
            if (item.keywords.some((keyword) => haystack.includes(keyword))) {
                select.value = item.value;
                break;
            }
        }
    }

    function applyExtractedText(text) {
        textoExtraidoInput.value = text.slice(0, 60000);
        fillIfEmpty(fields.nombre, valueNear(text, ['Nombre del Trabajador', 'Trabajador', 'Nombre']));
        fillIfEmpty(fields.cedula, valueNear(text, ['No. Identificacion', 'Identificacion', 'Cedula', 'Documento']));
        fillIfEmpty(fields.cargo, valueNear(text, ['Cargo']));
        setSelectByKeywords(fields.tipoExamen, text, [
            { value: 'Levantamiento Restricciones', keywords: ['levantamiento restricciones', 'levantamiento de restricciones'] },
            { value: 'Post-Incapacidad', keywords: ['post-incapacidad', 'post incapacidad', 'pos incapacidad'] },
            { value: 'Ingreso', keywords: ['ingreso'] },
            { value: 'Periodico', keywords: ['periodico', 'periodica'] },
            { value: 'Retiro', keywords: ['retiro', 'egreso'] }
        ]);
        setSelectByKeywords(fields.resultado, text, [
            { value: 'No Apto', keywords: ['no apto', 'noapto'] },
            { value: 'Restriccion', keywords: ['restriccion', 'restricciones'] },
            { value: 'Apto', keywords: ['apto'] }
        ]);
        setSelectByKeywords(fields.aptitud, text, [
            { value: 'Con restricciones', keywords: ['con restricciones', 'restriccion'] },
            { value: 'Con recomendaciones', keywords: ['con recomendaciones', 'recomendaciones'] },
            { value: 'Sin restricciones', keywords: ['sin restricciones'] }
        ]);
        fillIfEmpty(fields.centroTexto, valueNear(text, ['Centro Medico', 'IPS', 'Entidad']));

        const exp = toIsoDate(valueNear(text, ['Fecha de Expedicion', 'Fecha Expedicion', 'Expedicion']));
        const ven = toIsoDate(valueNear(text, ['Fecha de Vencimiento', 'Fecha Vencimiento', 'Vencimiento']));
        if (exp && !fields.fechaExp.value) fields.fechaExp.value = exp;
        if (ven && !fields.fechaVen.value) fields.fechaVen.value = ven;
        refreshExamExpiry();

        extractStatus.textContent = 'Texto extraido. Revisa y confirma los datos antes de guardar.';
    }

    archivoInput?.addEventListener('change', async () => {
        const file = archivoInput.files?.[0];
        if (!file) return;
        if (!window.pdfjsLib) {
            extractStatus.textContent = 'PDF cargado. La lectura automatica no esta disponible; confirma los campos manualmente.';
            return;
        }
        try {
            extractStatus.textContent = 'Leyendo PDF...';
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            const buffer = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({ data: buffer }).promise;
            const pages = [];
            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                const page = await pdf.getPage(pageNum);
                const content = await page.getTextContent();
                pages.push(content.items.map((item) => item.str).join(' '));
            }
            applyExtractedText(pages.join('\n'));
        } catch (error) {
            extractStatus.textContent = 'No se pudo leer el PDF automaticamente. Puedes confirmar los datos manualmente.';
        }
    });
});
</script>
<?php endif; ?>
<?php if ($modulo_actual === 'historias-clinicas' && $usuario_rol === 'sst'): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const monthMap = {
        enero: '01', febrero: '02', marzo: '03', abril: '04', mayo: '05', junio: '06',
        julio: '07', agosto: '08', septiembre: '09', setiembre: '09', octubre: '10',
        noviembre: '11', diciembre: '12'
    };

    function validIsoDate(year, month, day) {
        const y = Number(year);
        const m = Number(month);
        const d = Number(day);
        if (y < 2000 || y > 2100 || m < 1 || m > 12 || d < 1 || d > 31) return '';
        const dt = new Date(Date.UTC(y, m - 1, d));
        if (dt.getUTCFullYear() !== y || dt.getUTCMonth() + 1 !== m || dt.getUTCDate() !== d) return '';
        return `${String(y).padStart(4, '0')}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    }

    function normalize(text) {
        return String(text || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function extractBestDate(text) {
        const source = String(text || '');
        const normalized = normalize(source);
        const candidates = [];
        const keywordRegex = /(fecha|emision|expedicion|expedido|suscrito|firmado|certifica|certificado|constancia|carta)/g;
        const keywordPositions = [];
        let keyMatch;
        while ((keyMatch = keywordRegex.exec(normalized)) !== null) {
            keywordPositions.push(keyMatch.index);
        }

        function score(index) {
            if (!keywordPositions.length) return 0;
            const distances = keywordPositions.map((pos) => Math.abs(pos - index));
            const min = Math.min(...distances);
            return Math.max(0, 1200 - min);
        }

        const numericRegex = /\b(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})\b/g;
        let match;
        while ((match = numericRegex.exec(normalized)) !== null) {
            const iso = validIsoDate(match[3], match[2], match[1]);
            if (iso) candidates.push({ iso, score: score(match.index), index: match.index });
        }

        const isoRegex = /\b(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})\b/g;
        while ((match = isoRegex.exec(normalized)) !== null) {
            const iso = validIsoDate(match[1], match[2], match[3]);
            if (iso) candidates.push({ iso, score: score(match.index), index: match.index });
        }

        const spanishRegex = /\b(\d{1,2})\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\s+de\s+(\d{4})\b/g;
        while ((match = spanishRegex.exec(normalized)) !== null) {
            const iso = validIsoDate(match[3], monthMap[match[2]], match[1]);
            if (iso) candidates.push({ iso, score: score(match.index) + 120, index: match.index });
        }

        if (!candidates.length) return '';
        candidates.sort((a, b) => b.score - a.score || a.index - b.index);
        return candidates[0].iso;
    }

    async function readPdfText(file) {
        if (!window.pdfjsLib) return '';
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        const buffer = await file.arrayBuffer();
        const pdf = await pdfjsLib.getDocument({ data: buffer }).promise;
        const pages = [];
        for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
            const page = await pdf.getPage(pageNum);
            const content = await page.getTextContent();
            pages.push(content.items.map((item) => item.str).join(' '));
        }
        return pages.join('\n');
    }

    document.querySelectorAll('[data-custody-file]').forEach((input) => {
        input.addEventListener('change', async () => {
            const id = input.dataset.custodyFile;
            const dateInput = document.querySelector(`[data-custody-date="${id}"]`);
            const textInput = document.querySelector(`[data-custody-text="${id}"]`);
            const status = document.querySelector(`[data-custody-status="${id}"]`);
            const file = input.files?.[0];
            if (!file) return;
            if (status) status.textContent = 'Leyendo PDF para identificar la fecha...';

            try {
                const text = await readPdfText(file);
                if (textInput) textInput.value = text.slice(0, 60000);
                const date = extractBestDate(text);
                if (date && dateInput) {
                    dateInput.value = date;
                    if (status) status.textContent = 'Fecha extraída del PDF. Revísala antes de cargar el certificado.';
                } else if (status) {
                    status.textContent = 'No se identificó una fecha clara. Confirma la fecha manualmente antes de cargar.';
                }
            } catch (error) {
                if (status) status.textContent = 'No se pudo leer el PDF automáticamente. Confirma la fecha manualmente antes de cargar.';
            }
        });
    });
});
</script>
<?php endif; ?>
</body>
</html>
