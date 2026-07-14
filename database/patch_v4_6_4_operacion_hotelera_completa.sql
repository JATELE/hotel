SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS personal_limpieza (
  id_personal INT NOT NULL AUTO_INCREMENT,
  id_hotel INT NOT NULL,
  nombres VARCHAR(120) NOT NULL,
  apellidos VARCHAR(120) NULL,
  dni VARCHAR(20) NULL,
  telefono VARCHAR(30) NULL,
  estado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  observacion VARCHAR(255) NULL,
  PRIMARY KEY(id_personal),
  UNIQUE KEY uk_personal_hotel_dni(id_hotel,dni),
  KEY idx_personal_hotel_estado(id_hotel,estado),
  CONSTRAINT fk_personal_limpieza_hotel FOREIGN KEY(id_hotel) REFERENCES hoteles(id_hotel) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @x=(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='reservas' AND COLUMN_NAME='fecha_hora_checkin_real');
SET @sql=IF(@x=0,'ALTER TABLE reservas ADD COLUMN fecha_hora_checkin_real DATETIME NULL AFTER fecha_checkout','SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @x=(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='reservas' AND COLUMN_NAME='fecha_hora_checkout_real');
SET @sql=IF(@x=0,'ALTER TABLE reservas ADD COLUMN fecha_hora_checkout_real DATETIME NULL AFTER fecha_hora_checkin_real','SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @x=(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='reservas' AND COLUMN_NAME='id_usuario_checkin');
SET @sql=IF(@x=0,'ALTER TABLE reservas ADD COLUMN id_usuario_checkin INT NULL AFTER fecha_hora_checkout_real','SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @x=(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='reservas' AND COLUMN_NAME='id_usuario_checkout');
SET @sql=IF(@x=0,'ALTER TABLE reservas ADD COLUMN id_usuario_checkout INT NULL AFTER id_usuario_checkin','SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

ALTER TABLE reservas MODIFY estado_reserva ENUM('Pendiente','Confirmada','Atendida','Cancelada','Culminada') NOT NULL DEFAULT 'Pendiente';

SET @x=(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='habitacion_limpieza' AND COLUMN_NAME='id_personal');
SET @sql=IF(@x=0,'ALTER TABLE habitacion_limpieza ADD COLUMN id_personal INT NULL AFTER id_reserva, ADD KEY idx_hl_personal(id_personal)','SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @x=(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='habitacion_limpieza' AND COLUMN_NAME='fecha_programada');
SET @sql=IF(@x=0,'ALTER TABLE habitacion_limpieza ADD COLUMN fecha_programada DATE NULL AFTER fecha','SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @x=(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='habitacion_limpieza' AND COLUMN_NAME='hora_programada');
SET @sql=IF(@x=0,'ALTER TABLE habitacion_limpieza ADD COLUMN hora_programada TIME NULL AFTER fecha_programada','SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

ALTER TABLE habitacion_limpieza MODIFY estado ENUM('PENDIENTE','EN_LIMPIEZA','FINALIZADA','INSPECCIONADA','LISTA_PARA_VENDER') NOT NULL DEFAULT 'PENDIENTE';
ALTER TABLE habitacion_operaciones MODIFY estado_resultante ENUM('Disponible','Ocupada','Reservada','Check In','Check Out','Limpieza','Inspeccionada','Fuera de Servicio','Bloqueada','Lista para vender') NOT NULL;

SET FOREIGN_KEY_CHECKS=1;
