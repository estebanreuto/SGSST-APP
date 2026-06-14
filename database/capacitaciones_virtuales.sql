CREATE TABLE IF NOT EXISTS capacitaciones_cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actividad_id INT NOT NULL UNIQUE,
    tipo_contenido ENUM('video','enlace') NOT NULL DEFAULT 'enlace',
    contenido_url VARCHAR(500) DEFAULT NULL,
    video_archivo VARCHAR(500) DEFAULT NULL,
    imagen_portada VARCHAR(500) DEFAULT NULL,
    instrucciones TEXT DEFAULT NULL,
    escala_calificacion ENUM('5','10','100') NOT NULL DEFAULT '100',
    puntaje_aprobacion DECIMAL(8,2) NOT NULL DEFAULT 70,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_curso_actividad FOREIGN KEY (actividad_id)
        REFERENCES actividades_capacitacion(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS capacitaciones_preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    enunciado TEXT NOT NULL,
    tipo ENUM('unica','multiple','verdadero_falso') NOT NULL DEFAULT 'unica',
    puntos DECIMAL(8,2) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_pregunta_curso FOREIGN KEY (curso_id)
        REFERENCES capacitaciones_cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS capacitaciones_opciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_id INT NOT NULL,
    texto VARCHAR(500) NOT NULL,
    es_correcta TINYINT(1) NOT NULL DEFAULT 0,
    orden INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_opcion_pregunta FOREIGN KEY (pregunta_id)
        REFERENCES capacitaciones_preguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS capacitaciones_materiales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    tipo ENUM('texto','video','enlace','documento','imagen') NOT NULL DEFAULT 'texto',
    contenido LONGTEXT DEFAULT NULL,
    archivo VARCHAR(500) DEFAULT NULL,
    orden INT NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_material_curso FOREIGN KEY (curso_id)
        REFERENCES capacitaciones_cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS capacitaciones_intentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    usuario_id INT NOT NULL,
    puntaje_obtenido DECIMAL(8,2) NOT NULL DEFAULT 0,
    puntaje_escala DECIMAL(8,2) NOT NULL DEFAULT 0,
    aprobado TINYINT(1) NOT NULL DEFAULT 0,
    iniciado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finalizado_en DATETIME DEFAULT NULL,
    UNIQUE KEY uq_intento_curso_usuario (curso_id, usuario_id),
    CONSTRAINT fk_intento_curso FOREIGN KEY (curso_id)
        REFERENCES capacitaciones_cursos(id) ON DELETE CASCADE,
    CONSTRAINT fk_intento_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS capacitaciones_respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intento_id INT NOT NULL,
    pregunta_id INT NOT NULL,
    opciones_json LONGTEXT NOT NULL,
    correcta TINYINT(1) NOT NULL DEFAULT 0,
    puntos_obtenidos DECIMAL(8,2) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_respuesta_intento_pregunta (intento_id, pregunta_id),
    CONSTRAINT fk_respuesta_intento FOREIGN KEY (intento_id)
        REFERENCES capacitaciones_intentos(id) ON DELETE CASCADE,
    CONSTRAINT fk_respuesta_pregunta FOREIGN KEY (pregunta_id)
        REFERENCES capacitaciones_preguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS capacitaciones_actas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    usuario_id INT NOT NULL,
    intento_id INT NOT NULL,
    firma LONGTEXT NOT NULL,
    aceptacion TEXT NOT NULL,
    enviada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_acta_curso_usuario (curso_id, usuario_id),
    CONSTRAINT fk_acta_curso FOREIGN KEY (curso_id)
        REFERENCES capacitaciones_cursos(id) ON DELETE CASCADE,
    CONSTRAINT fk_acta_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_acta_intento FOREIGN KEY (intento_id)
        REFERENCES capacitaciones_intentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS capacitaciones_progreso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    usuario_id INT NOT NULL,
    porcentaje TINYINT UNSIGNED NOT NULL DEFAULT 0,
    seccion_actual INT NOT NULL DEFAULT 0,
    completado_en DATETIME DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_progreso_curso_usuario (curso_id, usuario_id),
    CONSTRAINT fk_progreso_curso FOREIGN KEY (curso_id)
        REFERENCES capacitaciones_cursos(id) ON DELETE CASCADE,
    CONSTRAINT fk_progreso_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
