<?php

function ensure_estandar7_schema(PDO $conn): void
{
    $conn->exec("
        CREATE TABLE IF NOT EXISTS estandar7_recursos_presupuesto (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT NOT NULL,
            anio SMALLINT NOT NULL,
            categoria_slug VARCHAR(80) NOT NULL,
            categoria_nombre VARCHAR(180) NOT NULL,
            item_slug VARCHAR(100) NOT NULL,
            item_nombre VARCHAR(220) NOT NULL,
            periodo TINYINT NOT NULL,
            presupuestado DECIMAL(14,2) NOT NULL DEFAULT 0,
            ejecutado DECIMAL(14,2) NOT NULL DEFAULT 0,
            creado_por INT DEFAULT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_e7_recursos_periodo (empresa_id, anio, item_slug, periodo),
            KEY idx_e7_recursos_empresa_anio (empresa_id, anio),
            KEY idx_e7_recursos_categoria (categoria_slug),
            KEY idx_e7_recursos_item (item_slug),
            KEY idx_e7_recursos_creador (creado_por),
            CONSTRAINT fk_e7_recursos_creador FOREIGN KEY (creado_por)
                REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS estandar7_recursos_analisis_consumo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT NOT NULL,
            anio SMALLINT NOT NULL,
            trimestre TINYINT NOT NULL,
            seguimiento TEXT DEFAULT NULL,
            accion TEXT DEFAULT NULL,
            creado_por INT DEFAULT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_e7_analisis_trimestre (empresa_id, anio, trimestre),
            KEY idx_e7_analisis_empresa_anio (empresa_id, anio),
            KEY idx_e7_analisis_creador (creado_por),
            CONSTRAINT fk_e7_analisis_creador FOREIGN KEY (creado_por)
                REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS estandar7_epp_entregas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT NOT NULL,
            trabajador_id INT NOT NULL,
            nombre_trabajador VARCHAR(180) NOT NULL,
            cedula VARCHAR(40) NOT NULL,
            cargo VARCHAR(180) DEFAULT NULL,
            fecha_entrega DATE NOT NULL,
            items_json LONGTEXT NOT NULL,
            tipo_entrega ENUM('Ordinaria','Desgaste','Perdida') NOT NULL DEFAULT 'Ordinaria',
            entregado_por_tipo VARCHAR(60) NOT NULL,
            entregado_por_usuario_id INT DEFAULT NULL,
            entregado_por_nombre VARCHAR(180) NOT NULL,
            estado ENUM('pendiente_firma','firmado') NOT NULL DEFAULT 'pendiente_firma',
            firma_trabajador LONGTEXT DEFAULT NULL,
            firma_codigo_hash VARCHAR(255) DEFAULT NULL,
            firma_codigo_expira DATETIME DEFAULT NULL,
            firma_codigo_validado_at DATETIME DEFAULT NULL,
            fecha_firma DATETIME DEFAULT NULL,
            observaciones TEXT DEFAULT NULL,
            creado_por INT DEFAULT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_e7_epp_empresa (empresa_id),
            KEY idx_e7_epp_trabajador (trabajador_id),
            KEY idx_e7_epp_estado (estado),
            KEY idx_e7_epp_fecha (fecha_entrega),
            KEY idx_e7_epp_creador (creado_por),
            CONSTRAINT fk_e7_epp_trabajador FOREIGN KEY (trabajador_id)
                REFERENCES usuarios(id) ON DELETE CASCADE,
            CONSTRAINT fk_e7_epp_entregado_usuario FOREIGN KEY (entregado_por_usuario_id)
                REFERENCES usuarios(id) ON DELETE SET NULL,
            CONSTRAINT fk_e7_epp_creador FOREIGN KEY (creado_por)
                REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS estandar7_programas_documentales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT NOT NULL,
            programa_slug VARCHAR(80) NOT NULL,
            programa_nombre VARCHAR(220) NOT NULL,
            contenido_json LONGTEXT NOT NULL,
            creado_por INT DEFAULT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_e7_programa_empresa (empresa_id, programa_slug),
            KEY idx_e7_programa_empresa (empresa_id),
            KEY idx_e7_programa_slug (programa_slug),
            KEY idx_e7_programa_creador (creado_por),
            CONSTRAINT fk_e7_programa_creador FOREIGN KEY (creado_por)
                REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS estandar7_mantenimiento_equipos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT NOT NULL,
            codigo_interno VARCHAR(20) NOT NULL,
            tipo_elemento ENUM('Maquina','Equipo','Herramienta') NOT NULL DEFAULT 'Equipo',
            nombre_elemento VARCHAR(180) NOT NULL,
            marca VARCHAR(120) DEFAULT NULL,
            serie VARCHAR(120) DEFAULT NULL,
            modelo VARCHAR(120) DEFAULT NULL,
            tipo_energia_json LONGTEXT DEFAULT NULL,
            ubicacion VARCHAR(180) DEFAULT NULL,
            seccion VARCHAR(180) DEFAULT NULL,
            tipo_combustible VARCHAR(120) DEFAULT NULL,
            fabricante VARCHAR(180) DEFAULT NULL,
            direccion VARCHAR(220) DEFAULT NULL,
            telefono VARCHAR(80) DEFAULT NULL,
            creado_por INT DEFAULT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_e7_mant_codigo_empresa (empresa_id, codigo_interno),
            KEY idx_e7_mant_empresa (empresa_id),
            KEY idx_e7_mant_tipo (tipo_elemento),
            KEY idx_e7_mant_creador (creado_por),
            CONSTRAINT fk_e7_mant_creador FOREIGN KEY (creado_por)
                REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function estandar7_meses(): array
{
    return [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];
}

function estandar7_recursos_catalogo(): array
{
    return [
        'seguridad-industrial' => [
            'nombre' => 'Seguridad industrial',
            'items' => [
                'dotaciones-epp' => 'Dotaciones y elementos de proteccion personal',
                'senalizacion-emergencia' => 'Senalizacion de emergencia, cintas antideslizantes',
                'mantenimiento-emergencia' => 'Mantenimiento de equipos de emergencia (extintores y botiquines)',
            ],
        ],
        'medicina-preventiva-trabajo' => [
            'nombre' => 'Medicina preventiva y del trabajo',
            'items' => [
                'examenes-medicos' => 'Examenes medicos (ingresos, periodicos y retiros)',
                'vigilancia-epidemiologica' => 'Programas de vigilancia epidemiologica',
                'riesgo-psicosocial' => 'Evaluacion riesgo psicosocial',
            ],
        ],
        'capacitacion' => [
            'nombre' => 'Capacitacion',
            'items' => [
                'capacitacion-entrenamiento' => 'Capacitacion y entrenamiento',
            ],
        ],
        'higiene-industrial' => [
            'nombre' => 'Higiene industrial',
            'items' => [
                'mediciones-ambientales' => 'Mediciones ambientales',
                'estudio-puestos-trabajo' => 'Estudio puestos de trabajo',
            ],
        ],
        'comites' => [
            'nombre' => 'Comites',
            'items' => [
                'copasst' => 'COPASST',
                'cocola' => 'COCOLA',
                'brigadas' => 'Brigadas',
            ],
        ],
    ];
}

function estandar7_recursos_flat_items(): array
{
    $items = [];
    foreach (estandar7_recursos_catalogo() as $categoriaSlug => $categoria) {
        foreach ($categoria['items'] as $itemSlug => $itemNombre) {
            $items[$itemSlug] = [
                'categoria_slug' => $categoriaSlug,
                'categoria_nombre' => $categoria['nombre'],
                'item_slug' => $itemSlug,
                'item_nombre' => $itemNombre,
            ];
        }
    }
    return $items;
}

function estandar7_programas_catalogo(): array
{
    return [
        'trabajo-seguro-alturas' => [
            'nombre' => 'Programa de trabajo seguro en alturas',
            'resumen' => 'Lineamientos, responsabilidades, controles y seguimiento para trabajos en alturas.',
            'icono' => 'fa-person-arrow-up-from-line',
            'items' => [
                'objetivo-general' => 'Objetivo general que establezca los lineamientos basicos para trabajo en alturas',
                'alcance-programa' => 'Alcance del programa',
                'marco-conceptual-legal' => 'Marco conceptual y marco legal',
                'roles-responsabilidades' => 'Roles y responsabilidades',
                'capacitacion-entrenamiento' => 'Requisitos de capacitacion y entrenamiento para los roles definidos',
                'cronograma-cumplimiento' => 'Cronograma de cumplimiento de las actividades',
                'identificacion-peligros' => 'Identificacion de peligros',
                'evaluacion-riesgos' => 'Evaluacion y valoracion de riesgos',
                'inventario-actividades' => 'Inventario de actividades de trabajos en alturas, tareas rutinarias y no rutinarias',
                'procedimientos-anexos' => 'Procedimientos de trabajo documentados y anexos definidos por el empleador',
                'medidas-prevencion' => 'Medidas de prevencion',
                'sistemas-acceso' => 'Sistemas de acceso para trabajos en alturas',
                'medidas-proteccion' => 'Medidas de proteccion',
                'emergencias' => 'Procedimientos en caso de emergencias',
                'indicadores-gestion' => 'Indicadores de gestion especificos alineados al Decreto 1072 de 2015',
            ],
        ],
        'espacios-confinados' => [
            'nombre' => 'Programa de gestion para el trabajo en espacios confinados',
            'resumen' => 'Gestion documental y de control para actividades en espacios confinados.',
            'icono' => 'fa-vault',
            'items' => [
                'objetivo-general' => 'Objetivo general',
                'alcance-programa' => 'Alcance del programa',
                'marco-conceptual-legal' => 'Marco conceptual y legal',
                'roles-responsabilidades' => 'Roles y responsabilidades',
                'analisis-peligros-controles' => 'Analisis de peligros, evaluacion y valoracion de riesgos y establecimiento de controles',
                'inventario-clasificacion-ubicacion' => 'Inventario, clasificacion y ubicacion de los espacios confinados',
                'procedimiento-anexos' => 'Procedimiento documentado y anexos definidos por el empleador y/o contratante',
                'medidas-prevencion' => 'Medidas de prevencion',
                'medidas-proteccion' => 'Medidas de proteccion',
                'emergencias' => 'Procedimientos en caso de emergencias',
                'indicadores-gestion' => 'Indicadores de gestion alineados con Decreto 1072 de 2015 y Resolucion 0312 de 2019',
            ],
        ],
    ];
}

function estandar7_programa_contenido($json): array
{
    $contenido = json_decode((string)$json, true);
    return is_array($contenido) ? $contenido : [];
}

function estandar7_tipos_energia_mantenimiento(): array
{
    return [
        'Electrica' => 'Electrica',
        'Mecanica' => 'Mecanica',
        'Hidraulica' => 'Hidraulica',
        'Neumatica' => 'Neumatica',
        'Termica' => 'Termica',
        'Quimica' => 'Quimica',
        'Gravitacional' => 'Gravitacional',
        'Combustion' => 'Combustion / combustible',
        'Energia almacenada' => 'Energia almacenada o residual',
        'Otra' => 'Otra',
    ];
}

function estandar7_epp_catalogo(): array
{
    return [
        'botas-seguridad' => [
            'nombre' => 'Botas de seguridad',
            'items' => [
                'botas_material' => 'Material',
                'botas_caucho_pvc' => 'Caucho o PVC',
            ],
        ],
        'proteccion-ocular' => [
            'nombre' => 'Proteccion ocular',
            'items' => [
                'lente_claro' => 'Lente claro',
                'lente_oscuro' => 'Lente oscuro',
                'careta' => 'Careta',
            ],
        ],
        'proteccion-respiratoria' => [
            'nombre' => 'Proteccion respiratoria',
            'items' => [
                'material_particulado' => 'Material particulado',
                'tapabocas_tela' => 'Tapabocas en tela',
                'mascarilla_desechable' => 'Mascarilla desechable',
            ],
        ],
        'proteccion-auditiva' => [
            'nombre' => 'Proteccion auditiva',
            'items' => [
                'tipo_copa' => 'Tipo copa',
                'insercion' => 'De insercion',
            ],
        ],
        'guantes' => [
            'nombre' => 'Guantes',
            'items' => [
                'carnaza' => 'Carnaza',
                'vaqueta' => 'Vaqueta',
                'nitrilo' => 'Nitrilo',
                'caucho_cal_35' => 'Caucho cal. 35',
            ],
        ],
        'proteccion-corporal-obra' => [
            'nombre' => 'Proteccion corporal para obra',
            'items' => [
                'camisa_obra' => 'Camisa',
                'pantalon_obra' => 'Pantalon',
                'overol' => 'Overol',
                'impermeable' => 'Impermeable',
                'chaqueta_obra' => 'Chaqueta',
            ],
        ],
        'distintivo-obra' => [
            'nombre' => 'Distintivo obra',
            'items' => [
                'chaleco' => 'Chaleco',
            ],
        ],
        'dotacion-oficina' => [
            'nombre' => 'Dotacion para oficina',
            'items' => [
                'camisa_oficina' => 'Camisa',
                'pantalon_oficina' => 'Pantalon',
                'falda' => 'Falda',
                'chaqueta_oficina' => 'Chaqueta',
                'zapatos' => 'Zapatos',
            ],
        ],
        'alturas' => [
            'nombre' => 'Alturas',
            'items' => [
                'arnes' => 'Arnes',
                'eslinga_posicionamiento' => 'Eslinga de posicionamiento',
                'eslinga_absorbedor' => 'Eslinga con absorbedor',
            ],
        ],
        'proteccion-cabeza' => [
            'nombre' => 'Proteccion para la cabeza',
            'items' => [
                'casco' => 'Casco',
                'barbuquejo' => 'Barbuquejo',
            ],
        ],
        'otros-epp' => [
            'nombre' => 'Otros EPP',
            'items' => [],
        ],
    ];
}

function estandar7_decode_items_json($json): array
{
    $items = json_decode((string)$json, true);
    return is_array($items) ? $items : [];
}

function estandar7_clean_money($value): float
{
    $value = trim((string)$value);
    if ($value === '') {
        return 0.0;
    }
    $value = str_replace(['$', ' ', ','], ['', '', ''], $value);
    return max(0, round((float)$value, 2));
}
