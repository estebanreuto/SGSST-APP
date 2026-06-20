<?php

function ensure_estandar4_schema(PDO $conn): void
{
    $conn->exec("
        CREATE TABLE IF NOT EXISTS estandar4_planes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT NOT NULL,
            anio SMALLINT UNSIGNED NOT NULL,
            meta_cumplimiento TINYINT UNSIGNED NOT NULL DEFAULT 85,
            estado ENUM('borrador','pendiente_firma','firmado') NOT NULL DEFAULT 'borrador',
            sst_id INT DEFAULT NULL,
            representante_id INT DEFAULT NULL,
            firma_sst LONGTEXT DEFAULT NULL,
            firma_representante LONGTEXT DEFAULT NULL,
            fecha_envio DATETIME DEFAULT NULL,
            fecha_firma DATETIME DEFAULT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_estandar4_empresa_anio (empresa_id, anio),
            KEY idx_estandar4_estado (estado),
            CONSTRAINT fk_estandar4_sst FOREIGN KEY (sst_id) REFERENCES usuarios(id) ON DELETE SET NULL,
            CONSTRAINT fk_estandar4_representante FOREIGN KEY (representante_id) REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS estandar4_actividades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plan_id INT NOT NULL,
            actividad_capacitacion_id INT DEFAULT NULL,
            tema VARCHAR(180) NOT NULL,
            actividad VARCHAR(255) NOT NULL,
            responsable VARCHAR(180) NOT NULL,
            programacion_json LONGTEXT NOT NULL,
            observaciones TEXT DEFAULT NULL,
            orden INT NOT NULL DEFAULT 0,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_estandar4_actividad_capacitacion (plan_id, actividad_capacitacion_id),
            KEY idx_estandar4_actividad_plan (plan_id),
            CONSTRAINT fk_estandar4_actividad_plan FOREIGN KEY (plan_id)
                REFERENCES estandar4_planes(id) ON DELETE CASCADE,
            CONSTRAINT fk_estandar4_actividad_capacitacion FOREIGN KEY (actividad_capacitacion_id)
                REFERENCES actividades_capacitacion(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS estandar4_seguimientos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plan_id INT NOT NULL,
            periodo VARCHAR(120) NOT NULL,
            analisis_resultado TEXT NOT NULL,
            accion_propuesta TEXT NOT NULL,
            responsable VARCHAR(180) NOT NULL,
            fecha_max_ejecucion DATE DEFAULT NULL,
            fecha_seguimiento DATE DEFAULT NULL,
            responsable_seguimiento VARCHAR(180) DEFAULT NULL,
            resultado_seguimiento TEXT DEFAULT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_estandar4_seguimiento_plan (plan_id),
            CONSTRAINT fk_estandar4_seguimiento_plan FOREIGN KEY (plan_id)
                REFERENCES estandar4_planes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function estandar4_get_or_create_plan(PDO $conn, int $empresa_id, int $anio): array
{
    $stmt = $conn->prepare("SELECT * FROM estandar4_planes WHERE empresa_id = ? AND anio = ? LIMIT 1");
    $stmt->execute([$empresa_id, $anio]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        $stmt = $conn->prepare("INSERT INTO estandar4_planes (empresa_id, anio) VALUES (?, ?)");
        $stmt->execute([$empresa_id, $anio]);
        $stmt = $conn->prepare("SELECT * FROM estandar4_planes WHERE id = ?");
        $stmt->execute([(int)$conn->lastInsertId()]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    return $plan;
}

function estandar4_estado_desde_capacitacion(array $capacitacion, ?string $estado_actual = null): string
{
    $estado_capacitacion = strtolower(trim((string)($capacitacion['estado'] ?? '')));

    if (in_array($estado_capacitacion, ['ejecutada', 'completada', 'cumplida', 'finalizada', 'realizada'], true)) {
        return 'E';
    }

    if ($estado_capacitacion === 'reprogramada') {
        return 'R';
    }

    if (in_array($estado_capacitacion, ['programada', 'en_proceso', 'no_ejecutada', 'cancelada'], true)) {
        return 'P';
    }

    return in_array($estado_actual, ['P', 'E', 'R'], true) ? $estado_actual : 'P';
}

function estandar4_programacion_desde_capacitacion(array $capacitacion, array $programacion_actual = []): array
{
    $fecha_inicio = $capacitacion['fecha_inicio'] ?? null;
    $timestamp = $fecha_inicio ? strtotime((string)$fecha_inicio) : false;

    if (!$timestamp) {
        return $programacion_actual ?: ['1' => ['estado' => 'P', 'fecha' => null]];
    }

    $mes = (string)(int)date('n', $timestamp);
    $estado_actual = null;
    if (isset($programacion_actual[$mes])) {
        $dato_actual = $programacion_actual[$mes];
        $estado_actual = is_array($dato_actual) ? ($dato_actual['estado'] ?? null) : $dato_actual;
    }

    return [
        $mes => [
            'estado' => estandar4_estado_desde_capacitacion($capacitacion, $estado_actual),
            'fecha' => date('Y-m-d', $timestamp),
        ],
    ];
}

function estandar4_importar_capacitaciones(PDO $conn, int $plan_id, int $empresa_id, int $anio): int
{
    $stmt = $conn->prepare("
        SELECT a.* FROM actividades_capacitacion a
        WHERE a.empresa_id=? AND YEAR(a.fecha_inicio)=?
        ORDER BY a.fecha_inicio
    ");
    $stmt->execute([$empresa_id, $anio]);
    $capacitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$capacitaciones) {
        return 0;
    }

    $existentes_stmt = $conn->prepare("
        SELECT * FROM estandar4_actividades
        WHERE plan_id=? AND actividad_capacitacion_id IS NOT NULL
    ");
    $existentes_stmt->execute([$plan_id]);
    $existentes = [];
    foreach ($existentes_stmt->fetchAll(PDO::FETCH_ASSOC) as $actividad) {
        $existentes[(int)$actividad['actividad_capacitacion_id']] = $actividad;
    }

    $orden_stmt = $conn->prepare("SELECT COALESCE(MAX(orden), 0) FROM estandar4_actividades WHERE plan_id = ?");
    $orden_stmt->execute([$plan_id]);
    $orden = (int)$orden_stmt->fetchColumn();

    $insert = $conn->prepare("
        INSERT INTO estandar4_actividades
            (plan_id, actividad_capacitacion_id, tema, actividad, responsable, programacion_json, observaciones, orden)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $update = $conn->prepare("
        UPDATE estandar4_actividades
        SET tema=?, actividad=?, responsable=?, programacion_json=?
        WHERE id=? AND plan_id=?
    ");

    $sincronizadas = 0;

    foreach ($capacitaciones as $capacitacion) {
        $capacitacion_id = (int)$capacitacion['id'];
        $tema = $capacitacion['categoria'] ?: 'Capacitación SST';
        $actividad = $capacitacion['nombre_actividad'];
        $responsable = $capacitacion['dirigido_a'] ?: 'Responsable SST';
        $existente = $existentes[$capacitacion_id] ?? null;
        $programacion_actual = $existente
            ? estandar4_decode_programacion($existente['programacion_json'] ?? '')
            : [];
        $programacion = estandar4_programacion_desde_capacitacion($capacitacion, $programacion_actual);
        $programacion_json = json_encode($programacion, JSON_UNESCAPED_UNICODE);

        if ($existente) {
            if (
                ($existente['tema'] ?? '') === $tema
                && ($existente['actividad'] ?? '') === $actividad
                && ($existente['responsable'] ?? '') === $responsable
                && ($existente['programacion_json'] ?? '') === $programacion_json
            ) {
                continue;
            }

            $update->execute([
                $tema,
                $actividad,
                $responsable,
                $programacion_json,
                (int)$existente['id'],
                $plan_id,
            ]);
            $sincronizadas++;
            continue;
        }

        $insert->execute([
            $plan_id,
            $capacitacion_id,
            $tema,
            $actividad,
            $responsable,
            $programacion_json,
            'Importada automáticamente desde el Estándar 3.',
            ++$orden,
        ]);
        $sincronizadas++;
    }

    return $sincronizadas;
}

function estandar4_decode_programacion(?string $json): array
{
    $programacion = json_decode((string)$json, true);
    return is_array($programacion) ? $programacion : [];
}

function estandar4_metricas(array $actividades): array
{
    $programadas = 0;
    $ejecutadas = 0;
    $reprogramadas = 0;
    $por_mes = array_fill(1, 12, ['P' => 0, 'E' => 0, 'R' => 0]);

    foreach ($actividades as $actividad) {
        foreach (estandar4_decode_programacion($actividad['programacion_json'] ?? '') as $mes => $dato) {
            $estado = is_array($dato) ? ($dato['estado'] ?? '') : $dato;
            $mes = (int)$mes;
            if ($mes < 1 || $mes > 12 || !in_array($estado, ['P', 'E', 'R'], true)) {
                continue;
            }
            $por_mes[$mes][$estado]++;
            if ($estado === 'P') $programadas++;
            if ($estado === 'E') $ejecutadas++;
            if ($estado === 'R') $reprogramadas++;
        }
    }

    $base = $programadas + $ejecutadas + $reprogramadas;
    $cumplimiento = $base > 0 ? round(($ejecutadas / $base) * 100) : 0;

    return compact('programadas', 'ejecutadas', 'reprogramadas', 'cumplimiento', 'por_mes');
}
