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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
