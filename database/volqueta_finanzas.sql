CREATE TABLE IF NOT EXISTS volqueta_config (
  id INT PRIMARY KEY DEFAULT 1,
  anio INT NOT NULL DEFAULT 2026,
  mes TINYINT NOT NULL DEFAULT 6,
  meta_diaria_lj INT NOT NULL DEFAULT 20,
  porcentaje_mio DECIMAL(6,4) NOT NULL DEFAULT 0.2200,
  juancho_porcentaje DECIMAL(6,4) NOT NULL DEFAULT 0.5000,
  beto_porcentaje DECIMAL(6,4) NOT NULL DEFAULT 0.5000,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO volqueta_config (id)
VALUES (1)
ON DUPLICATE KEY UPDATE id = id;

CREATE TABLE IF NOT EXISTS volqueta_rutas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  tarifa DECIMAL(12,2) NOT NULL DEFAULT 0,
  orden INT NOT NULL DEFAULT 0,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO volqueta_rutas (nombre, tarifa, orden)
SELECT * FROM (
  SELECT 'Rio a trituradora', 32000, 1 UNION ALL
  SELECT 'Rio a stock', 30000, 2 UNION ALL
  SELECT 'Stock a trituradora', 16000, 3 UNION ALL
  SELECT 'Trituradora a stock', 12000, 4 UNION ALL
  SELECT 'Stock a stock', 12000, 5
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM volqueta_rutas);

CREATE TABLE IF NOT EXISTS volqueta_catalogos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('categoria_gasto','pagado_por','medio_pago','tio') NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  orden INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_volqueta_catalogo (tipo, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO volqueta_catalogos (tipo, nombre, orden) VALUES
('categoria_gasto','ACPM',1),
('categoria_gasto','Mantenimiento',2),
('categoria_gasto','Llantas',3),
('categoria_gasto','Aceite',4),
('categoria_gasto','Lavadero',5),
('categoria_gasto','Peajes',6),
('categoria_gasto','Parqueadero',7),
('categoria_gasto','Reparacion',8),
('categoria_gasto','Alimentacion',9),
('categoria_gasto','Documentos',10),
('categoria_gasto','Impuestos',11),
('categoria_gasto','Seguro',12),
('categoria_gasto','Multas',13),
('categoria_gasto','Grua',14),
('categoria_gasto','Lavada',15),
('categoria_gasto','Repuestos',16),
('categoria_gasto','Mano de obra',17),
('categoria_gasto','Prestamo',18),
('categoria_gasto','Otro',19),
('pagado_por','Mi bolsillo',1),
('pagado_por','Juancho',2),
('pagado_por','Beto',3),
('pagado_por','Caja',4),
('pagado_por','Otro',5),
('medio_pago','Nequi',1),
('medio_pago','Daviplata',2),
('medio_pago','Davivienda',3),
('medio_pago','Bancolombia',4),
('medio_pago','Efectivo',5),
('medio_pago','Transferencia',6),
('medio_pago','Banco',7),
('medio_pago','Otro',8),
('tio','Juancho',1),
('tio','Beto',2);

CREATE TABLE IF NOT EXISTS volqueta_viajes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  ruta_id INT NOT NULL,
  cantidad INT NOT NULL DEFAULT 0,
  observaciones VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_volqueta_viaje_fecha_ruta (fecha, ruta_id),
  CONSTRAINT fk_volqueta_viajes_ruta FOREIGN KEY (ruta_id) REFERENCES volqueta_rutas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS volqueta_gastos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  categoria VARCHAR(120) NOT NULL,
  detalle VARCHAR(255) NULL,
  valor DECIMAL(12,2) NOT NULL DEFAULT 0,
  pagado_por VARCHAR(120) NULL,
  medio_pago VARCHAR(120) NULL,
  observaciones VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS volqueta_aportes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  tio VARCHAR(120) NOT NULL,
  concepto VARCHAR(255) NULL,
  valor DECIMAL(12,2) NOT NULL DEFAULT 0,
  medio_envio VARCHAR(120) NULL,
  observaciones VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
