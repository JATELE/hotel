SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS habitacion_operaciones (
  id_operacion INT NOT NULL AUTO_INCREMENT,
  id_hotel INT NOT NULL,
  id_habitacion INT NOT NULL,
  id_reserva INT NULL,
  tipo_operacion ENUM('CHECK_IN','CHECK_OUT','INICIAR_LIMPIEZA','FINALIZAR_LIMPIEZA','INSPECCIONAR','LISTA_PARA_VENDER','FUERA_SERVICIO','REACTIVAR') NOT NULL,
  estado_resultante ENUM('Disponible','Ocupada','Reservada','Check In','Check Out','Limpieza','Fuera de Servicio','Bloqueada','Lista para vender') NOT NULL,
  fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_usuario INT NULL,
  responsable VARCHAR(120) NULL,
  observacion VARCHAR(255) NULL,
  PRIMARY KEY (id_operacion),
  KEY idx_ho_hotel_habitacion (id_hotel,id_habitacion),
  KEY idx_ho_reserva (id_reserva),
  KEY idx_ho_fecha (fecha_hora),
  CONSTRAINT fk_ho_hotel FOREIGN KEY (id_hotel) REFERENCES hoteles(id_hotel) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ho_habitacion FOREIGN KEY (id_habitacion) REFERENCES habitaciones(id_habitacion) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ho_reserva FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_ho_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS habitacion_limpieza (
  id_limpieza INT NOT NULL AUTO_INCREMENT,
  id_hotel INT NOT NULL,
  id_habitacion INT NOT NULL,
  id_reserva INT NULL,
  fecha DATE NOT NULL,
  hora_inicio TIME NULL,
  hora_fin TIME NULL,
  responsable VARCHAR(120) NULL,
  estado ENUM('PENDIENTE','EN_LIMPIEZA','FINALIZADA','INSPECCIONADA') NOT NULL DEFAULT 'PENDIENTE',
  inspeccionado TINYINT(1) NOT NULL DEFAULT 0,
  id_usuario_inicio INT NULL,
  id_usuario_fin INT NULL,
  observacion VARCHAR(255) NULL,
  PRIMARY KEY (id_limpieza),
  KEY idx_hl_hotel_fecha (id_hotel,fecha),
  KEY idx_hl_habitacion (id_habitacion),
  CONSTRAINT fk_hl_hotel FOREIGN KEY (id_hotel) REFERENCES hoteles(id_hotel) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_hl_habitacion FOREIGN KEY (id_habitacion) REFERENCES habitaciones(id_habitacion) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_hl_reserva FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_hl_usuario_inicio FOREIGN KEY (id_usuario_inicio) REFERENCES usuarios(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_hl_usuario_fin FOREIGN KEY (id_usuario_fin) REFERENCES usuarios(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
