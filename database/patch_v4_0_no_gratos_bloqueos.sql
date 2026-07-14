-- PATCH CRM HOTELES V4.0
-- Clientes no gratos y programación de inactividad de habitaciones.
-- Ejecutar sobre la base actual. Hacer backup antes de importar.

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS clientes_no_gratos (
  id_no_grato INT NOT NULL AUTO_INCREMENT,
  id_hotel INT NOT NULL,
  dni VARCHAR(20) NOT NULL,
  nombres VARCHAR(100) NOT NULL,
  apellidos VARCHAR(100) DEFAULT NULL,
  motivo TEXT NOT NULL,
  estado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  fecha_registro TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_no_grato),
  UNIQUE KEY uk_no_grato_hotel_dni (id_hotel, dni),
  CONSTRAINT fk_no_gratos_hotel FOREIGN KEY (id_hotel) REFERENCES hoteles(id_hotel)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS habitaciones_bloqueos (
  id_bloqueo INT NOT NULL AUTO_INCREMENT,
  id_hotel INT NOT NULL,
  id_habitacion INT NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  motivo VARCHAR(250) DEFAULT NULL,
  estado ENUM('ACTIVO','CANCELADO') NOT NULL DEFAULT 'ACTIVO',
  fecha_registro TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_bloqueo),
  KEY idx_bloqueos_hotel (id_hotel),
  KEY idx_bloqueos_habitacion (id_habitacion),
  KEY idx_bloqueos_fechas (fecha_inicio, fecha_fin),
  CONSTRAINT fk_bloqueos_hotel FOREIGN KEY (id_hotel) REFERENCES hoteles(id_hotel)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_bloqueos_habitacion FOREIGN KEY (id_habitacion) REFERENCES habitaciones(id_habitacion)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
