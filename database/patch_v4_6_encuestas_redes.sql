SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS hotel_redes_sociales (
  id_hotel INT NOT NULL,
  google_reviews VARCHAR(500) NULL,
  facebook VARCHAR(500) NULL,
  tripadvisor VARCHAR(500) NULL,
  instagram VARCHAR(500) NULL,
  tiktok VARCHAR(500) NULL,
  sitio_web VARCHAR(500) NULL,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_hotel),
  CONSTRAINT fk_redes_hotel FOREIGN KEY (id_hotel) REFERENCES hoteles(id_hotel) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS encuestas (
  id_encuesta INT NOT NULL AUTO_INCREMENT,
  id_hotel INT NOT NULL,
  titulo VARCHAR(180) NOT NULL,
  descripcion TEXT NULL,
  umbral_publicar DECIMAL(3,2) NOT NULL DEFAULT 4.00,
  estado ENUM('ACTIVA','INACTIVA') NOT NULL DEFAULT 'ACTIVA',
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_encuesta),
  KEY idx_encuestas_hotel_estado (id_hotel,estado),
  CONSTRAINT fk_encuesta_hotel FOREIGN KEY (id_hotel) REFERENCES hoteles(id_hotel) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS encuesta_areas (
  id_area INT NOT NULL AUTO_INCREMENT,
  id_encuesta INT NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  orden INT NOT NULL DEFAULT 0,
  estado ENUM('ACTIVA','INACTIVA') NOT NULL DEFAULT 'ACTIVA',
  PRIMARY KEY (id_area),
  KEY idx_area_encuesta (id_encuesta,estado,orden),
  CONSTRAINT fk_area_encuesta FOREIGN KEY (id_encuesta) REFERENCES encuestas(id_encuesta) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS encuesta_respuestas (
  id_respuesta INT NOT NULL AUTO_INCREMENT,
  id_encuesta INT NOT NULL,
  id_reserva INT NULL,
  nombre_cliente VARCHAR(160) NULL,
  email_cliente VARCHAR(160) NULL,
  comentario TEXT NULL,
  promedio DECIMAL(3,2) NOT NULL DEFAULT 0,
  mostro_redes TINYINT(1) NOT NULL DEFAULT 0,
  fecha_respuesta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_respuesta),
  KEY idx_respuesta_encuesta_fecha (id_encuesta,fecha_respuesta),
  CONSTRAINT fk_respuesta_encuesta FOREIGN KEY (id_encuesta) REFERENCES encuestas(id_encuesta) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_respuesta_reserva FOREIGN KEY (id_reserva) REFERENCES reservas(id_reserva) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS encuesta_respuesta_detalle (
  id_detalle INT NOT NULL AUTO_INCREMENT,
  id_respuesta INT NOT NULL,
  id_area INT NOT NULL,
  calificacion TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (id_detalle),
  UNIQUE KEY uk_respuesta_area (id_respuesta,id_area),
  KEY idx_detalle_area (id_area),
  CONSTRAINT fk_detalle_respuesta FOREIGN KEY (id_respuesta) REFERENCES encuesta_respuestas(id_respuesta) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detalle_area FOREIGN KEY (id_area) REFERENCES encuesta_areas(id_area) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
