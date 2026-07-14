<?php

function ensure_whatsapp_crm_schema(PDO $conn): void
{
    $conn->exec("
        CREATE TABLE IF NOT EXISTS whatsapp_prospectos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_nombre VARCHAR(180) DEFAULT NULL,
            contacto_nombre VARCHAR(140) NOT NULL,
            cargo VARCHAR(120) DEFAULT NULL,
            telefono VARCHAR(40) NOT NULL,
            email VARCHAR(160) DEFAULT NULL,
            ciudad VARCHAR(120) DEFAULT NULL,
            nivel_riesgo VARCHAR(30) DEFAULT NULL,
            trabajadores INT DEFAULT NULL,
            interes VARCHAR(80) NOT NULL DEFAULT 'diagnostico',
            etapa VARCHAR(40) NOT NULL DEFAULT 'nuevo',
            prioridad VARCHAR(20) NOT NULL DEFAULT 'media',
            origen VARCHAR(80) NOT NULL DEFAULT 'WhatsApp Business',
            mensaje_inicial TEXT DEFAULT NULL,
            notas TEXT DEFAULT NULL,
            ultimo_contacto DATETIME DEFAULT NULL,
            proximo_seguimiento DATE DEFAULT NULL,
            creado_por_admin_id INT DEFAULT NULL,
            fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_whatsapp_prospectos_etapa (etapa),
            INDEX idx_whatsapp_prospectos_prioridad (prioridad),
            INDEX idx_whatsapp_prospectos_telefono (telefono),
            INDEX idx_whatsapp_prospectos_seguimiento (proximo_seguimiento)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

