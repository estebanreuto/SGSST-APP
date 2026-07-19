<?php

function ensure_estandar6_schema(PDO $conn): void
{
    $conn->exec("
        CREATE TABLE IF NOT EXISTS estandar6_ipvr_registros (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT NOT NULL,
            numero INT NOT NULL,
            sitio_trabajo VARCHAR(180) DEFAULT NULL,
            cuadro_basico VARCHAR(180) DEFAULT NULL,
            proceso VARCHAR(180) NOT NULL,
            actividad VARCHAR(220) NOT NULL,
            tarea TEXT DEFAULT NULL,
            zona_lugar VARCHAR(180) DEFAULT NULL,
            clase_actividad ENUM('Rutinaria','No Rutinaria') NOT NULL DEFAULT 'Rutinaria',
            origen_actividad ENUM('Interna','Externa') NOT NULL DEFAULT 'Interna',
            cargos TEXT DEFAULT NULL,
            directos INT NOT NULL DEFAULT 0,
            contratistas INT NOT NULL DEFAULT 0,
            visitantes INT NOT NULL DEFAULT 0,
            total_expuestos INT NOT NULL DEFAULT 0,
            peligro VARCHAR(80) NOT NULL,
            clasificacion_peligro VARCHAR(180) NOT NULL,
            metodologia_ref VARCHAR(180) DEFAULT NULL,
            descripcion_peligro TEXT DEFAULT NULL,
            categoria ENUM('Salud','Seguridad','Propiedad_Proceso') NOT NULL DEFAULT 'Seguridad',
            nivel_danio VARCHAR(120) NOT NULL,
            nivel_deficiencia INT NOT NULL DEFAULT 2,
            valoracion_nd VARCHAR(80) DEFAULT NULL,
            valoracion_nd_descripcion TEXT DEFAULT NULL,
            nivel_exposicion INT NOT NULL DEFAULT 1,
            nivel_probabilidad INT NOT NULL DEFAULT 0,
            interpretacion_probabilidad VARCHAR(40) DEFAULT NULL,
            nivel_consecuencia INT NOT NULL DEFAULT 10,
            nivel_riesgo INT NOT NULL DEFAULT 0,
            significado_riesgo VARCHAR(180) DEFAULT NULL,
            aceptabilidad VARCHAR(120) DEFAULT NULL,
            peor_consecuencia VARCHAR(220) DEFAULT NULL,
            requisito_legal ENUM('SI','NO') NOT NULL DEFAULT 'NO',
            control_fuente TEXT DEFAULT NULL,
            control_medio TEXT DEFAULT NULL,
            control_persona TEXT DEFAULT NULL,
            instrumento TEXT DEFAULT NULL,
            nivel_deficiencia_residual INT NOT NULL DEFAULT 2,
            nivel_exposicion_residual INT NOT NULL DEFAULT 1,
            nivel_probabilidad_residual INT NOT NULL DEFAULT 0,
            interpretacion_probabilidad_residual VARCHAR(40) DEFAULT NULL,
            nivel_consecuencia_residual INT NOT NULL DEFAULT 10,
            nivel_riesgo_residual INT NOT NULL DEFAULT 0,
            significado_riesgo_residual VARCHAR(180) DEFAULT NULL,
            aceptabilidad_residual VARCHAR(120) DEFAULT NULL,
            eliminacion TEXT DEFAULT NULL,
            sustitucion TEXT DEFAULT NULL,
            controles_ingenieria TEXT DEFAULT NULL,
            senalizacion_advertencia TEXT DEFAULT NULL,
            administrativos TEXT DEFAULT NULL,
            epp TEXT DEFAULT NULL,
            factor_reduccion DECIMAL(6,2) NOT NULL DEFAULT 0,
            accidentes_anterior INT DEFAULT NULL,
            accidentes_actual INT DEFAULT NULL,
            eficacia_controles ENUM('SI','NO','') NOT NULL DEFAULT '',
            observaciones TEXT DEFAULT NULL,
            creado_por INT DEFAULT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_e6_ipvr_empresa (empresa_id),
            KEY idx_e6_ipvr_numero (empresa_id, numero),
            KEY idx_e6_ipvr_peligro (peligro),
            KEY idx_e6_ipvr_riesgo (nivel_riesgo),
            KEY idx_e6_ipvr_riesgo_residual (nivel_riesgo_residual),
            CONSTRAINT fk_e6_ipvr_creador FOREIGN KEY (creado_por)
                REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function estandar6_catalogos(): array
{
    return [
        'peligros' => [
            'Biologico' => ['Virus', 'Bacterias', 'Hongos', 'Parasitos', 'Picaduras', 'Mordeduras', 'Fluidos o excrementos'],
            'Fisico' => ['Ruido', 'Iluminacion', 'Vibracion cuerpo entero', 'Vibracion segmentaria', 'Temperaturas extremas', 'Presion atmosferica', 'Radiaciones ionizantes', 'Radiaciones no ionizantes'],
            'Quimico' => ['Polvos organicos e inorganicos', 'Fibras', 'Liquidos', 'Gases y vapores', 'Humos metalicos', 'Humos no metalicos', 'Material particulado', 'Productos quimicos'],
            'Psicosocial' => ['Gestion organizacional', 'Caracteristicas de la organizacion', 'Caracteristicas del grupo social', 'Condiciones de la tarea', 'Jornada de trabajo', 'Interfase persona tarea'],
            'Biomecanicas' => ['Postura', 'Esfuerzo', 'Movimiento repetitivo', 'Manipulacion manual de cargas'],
            'Condiciones de seguridad' => ['Mecanico', 'Electrico', 'Locativo', 'Tecnologico', 'Accidente de transito', 'Publicos', 'Trabajo en alturas', 'Espacios confinados', 'Trabajo en caliente', 'Trabajo en excavaciones'],
            'Fenomenos naturales' => ['Sismo', 'Terremoto', 'Vendaval', 'Inundacion', 'Derrumbe', 'Precipitaciones'],
        ],
        'niveles_danio' => [
            'Salud' => ['Leve', 'Moderado', 'Grave', 'Mortal'],
            'Seguridad' => ['Leve', 'Moderado', 'Grave', 'Mortal'],
            'Propiedad_Proceso' => ['Menor', 'Moderado', 'Mayor', 'Catastrofico'],
        ],
        'nivel_deficiencia' => [
            10 => 'Muy alto',
            6 => 'Alto',
            2 => 'Medio',
            0 => 'Bajo',
        ],
        'nivel_exposicion' => [
            4 => 'Continua',
            3 => 'Frecuente',
            2 => 'Ocasional',
            1 => 'Esporadica',
        ],
    ];
}

function estandar6_metodologia_ref(string $clasificacion): string
{
    $c = mb_strtolower($clasificacion, 'UTF-8');
    $map = [
        'ruido' => 'RUIDO',
        'iluminacion' => 'ILUMINACION',
        'vibracion cuerpo' => 'VIBRACIONES_CUERPO',
        'vibracion segmentaria' => 'VIBRACIONES_SEGMENTO',
        'temperaturas' => 'TEMPERATURAS_EXTREMAS',
        'postura' => 'BIOMECANICOS_POSTURA',
        'esfuerzo' => 'BIOMECANICOS_ESFUERZO',
        'movimiento repetitivo' => 'BIOMECANICOS_MOVIMIENTO_REPETITIVO',
        'manipulacion' => 'BIOMECANICOS_MANIPULACION_CARGAS',
        'mecanico' => 'MECANICO',
        'electrico' => 'ELECTRICO',
        'locativo' => 'LOCATIVO',
        'tecnologico' => 'TECNOLOGICO',
        'transito' => 'ACCIDENTE_DE_TRANSITO',
        'publicos' => 'PUBLICOS',
        'alturas' => 'TRABAJO_EN_ALTURAS',
        'confinados' => 'ESPACIOS_CONFINADOS',
        'caliente' => 'TRABAJO_EN_CALIENTE',
        'excavaciones' => 'TRABAJO_EN_EXCAVACIONES',
    ];

    foreach ($map as $needle => $value) {
        if (strpos($c, $needle) !== false) {
            return $value;
        }
    }

    if (preg_match('/virus|bacteria|hongo|parasito|picadura|mordedura|fluido/u', $c)) {
        return 'BIOLOGICOS';
    }
    if (preg_match('/polvo|fibra|liquido|gas|vapor|humo|quimico|particulado/u', $c)) {
        return 'QUIMICOS';
    }
    if (preg_match('/gestion|organizacion|grupo|tarea|jornada|interfase/u', $c)) {
        return 'PSICOSOCIALES';
    }
    if (preg_match('/sismo|terremoto|vendaval|inundacion|derrumbe|precipitacion/u', $c)) {
        return 'FENOMENOS_NATURALES';
    }

    return 'REVISAR_CLASIFICACION';
}

function estandar6_peor_consecuencia(string $clasificacion): string
{
    $c = mb_strtolower($clasificacion, 'UTF-8');
    if (preg_match('/alturas|confinados|electrico|caliente|excavaciones|transito|mecanico/u', $c)) {
        return 'Lesion grave, fatalidad o incapacidad permanente';
    }
    if (preg_match('/quimico|gas|vapor|humo|polvo|fibra/u', $c)) {
        return 'Intoxicacion, enfermedad laboral o dano respiratorio';
    }
    if (preg_match('/postura|esfuerzo|movimiento|manipulacion/u', $c)) {
        return 'Lesion osteomuscular o restriccion medico laboral';
    }
    if (preg_match('/psico|gestion|jornada|grupo|tarea/u', $c)) {
        return 'Afectacion psicosocial, estres o ausentismo';
    }
    if (preg_match('/virus|bacteria|hongo|parasito|fluido/u', $c)) {
        return 'Enfermedad infecciosa o contagio ocupacional';
    }
    return 'Lesion o afectacion a la salud por exposicion ocupacional';
}

function estandar6_nd_descripcion(int $nd): string
{
    if ($nd >= 10) {
        return 'Deficiencia muy alta: controles inexistentes o exposicion critica.';
    }
    if ($nd >= 6) {
        return 'Deficiencia alta: controles insuficientes o parcialmente aplicados.';
    }
    if ($nd >= 2) {
        return 'Deficiencia media: existen controles, pero requieren verificacion.';
    }
    return 'Deficiencia baja: controles implementados y operando.';
}

function estandar6_nc_por_categoria_nivel(string $categoria, string $nivel): int
{
    $nivel = mb_strtolower($nivel, 'UTF-8');
    if (strpos($nivel, 'mortal') !== false || strpos($nivel, 'catastrofico') !== false) {
        return 100;
    }
    if (strpos($nivel, 'grave') !== false || strpos($nivel, 'mayor') !== false) {
        return 60;
    }
    if (strpos($nivel, 'moderado') !== false) {
        return 25;
    }
    return 10;
}

function estandar6_interpretacion_np(int $np): string
{
    if ($np >= 24) {
        return 'MUY ALTO';
    }
    if ($np >= 10) {
        return 'ALTO';
    }
    if ($np >= 6) {
        return 'MEDIO';
    }
    return 'BAJO';
}

function estandar6_significado_riesgo(int $riesgo): string
{
    if ($riesgo >= 600) {
        return 'I - Situacion critica. Suspender o intervenir de inmediato.';
    }
    if ($riesgo >= 150) {
        return 'II - Corregir y adoptar medidas de control inmediato.';
    }
    if ($riesgo >= 40) {
        return 'III - Mejorar si es posible y justificar intervencion.';
    }
    return 'IV - Mantener medidas existentes y seguimiento.';
}

function estandar6_aceptabilidad(int $riesgo): string
{
    if ($riesgo >= 600) {
        return 'NO ACEPTABLE';
    }
    if ($riesgo >= 150) {
        return 'NO ACEPTABLE O ACEPTABLE CON CONTROL ESPECIFICO';
    }
    if ($riesgo >= 40) {
        return 'MEJORABLE';
    }
    return 'ACEPTABLE';
}

function estandar6_calcular(array $data): array
{
    $directos = max(0, (int)($data['directos'] ?? 0));
    $contratistas = max(0, (int)($data['contratistas'] ?? 0));
    $visitantes = max(0, (int)($data['visitantes'] ?? 0));
    $nd = (int)($data['nivel_deficiencia'] ?? 2);
    $ne = (int)($data['nivel_exposicion'] ?? 1);
    $categoria = (string)($data['categoria'] ?? 'Seguridad');
    $nivelDanio = (string)($data['nivel_danio'] ?? 'Leve');
    $nc = estandar6_nc_por_categoria_nivel($categoria, $nivelDanio);
    $np = $nd * $ne;
    $riesgo = $np * $nc;

    $ndResidual = (int)($data['nivel_deficiencia_residual'] ?? $nd);
    $neResidual = (int)($data['nivel_exposicion_residual'] ?? $ne);
    $ncResidual = (int)($data['nivel_consecuencia_residual'] ?? $nc);
    $npResidual = $ndResidual * $neResidual;
    $riesgoResidual = $npResidual * $ncResidual;

    $accidentesAnterior = isset($data['accidentes_anterior']) && $data['accidentes_anterior'] !== '' ? max(0, (int)$data['accidentes_anterior']) : null;
    $accidentesActual = isset($data['accidentes_actual']) && $data['accidentes_actual'] !== '' ? max(0, (int)$data['accidentes_actual']) : null;
    $eficaciaSolicitada = strtoupper(trim((string)($data['eficacia_controles'] ?? '')));
    $eficacia = in_array($eficaciaSolicitada, ['SI', 'NO'], true) ? $eficaciaSolicitada : '';
    if ($eficacia === '' && $accidentesAnterior !== null && $accidentesActual !== null) {
        $eficacia = $accidentesActual <= $accidentesAnterior ? 'SI' : 'NO';
    }

    return [
        'total_expuestos' => $directos + $contratistas + $visitantes,
        'metodologia_ref' => estandar6_metodologia_ref((string)($data['clasificacion_peligro'] ?? '')),
        'valoracion_nd' => estandar6_catalogos()['nivel_deficiencia'][$nd] ?? 'Medio',
        'valoracion_nd_descripcion' => estandar6_nd_descripcion($nd),
        'nivel_probabilidad' => $np,
        'interpretacion_probabilidad' => estandar6_interpretacion_np($np),
        'nivel_consecuencia' => $nc,
        'nivel_riesgo' => $riesgo,
        'significado_riesgo' => estandar6_significado_riesgo($riesgo),
        'aceptabilidad' => estandar6_aceptabilidad($riesgo),
        'peor_consecuencia' => estandar6_peor_consecuencia((string)($data['clasificacion_peligro'] ?? '')),
        'nivel_exposicion_residual' => $neResidual,
        'nivel_probabilidad_residual' => $npResidual,
        'interpretacion_probabilidad_residual' => estandar6_interpretacion_np($npResidual),
        'nivel_consecuencia_residual' => $ncResidual,
        'nivel_riesgo_residual' => $riesgoResidual,
        'significado_riesgo_residual' => estandar6_significado_riesgo($riesgoResidual),
        'aceptabilidad_residual' => estandar6_aceptabilidad($riesgoResidual),
        'factor_reduccion' => $riesgo > 0 ? round((($riesgo - $riesgoResidual) / $riesgo) * 100, 2) : 0,
        'eficacia_controles' => $eficacia,
    ];
}
