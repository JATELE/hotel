-- CRM HOTELES V4.5
-- Catálogos editables, habitación con varias categorías, cantidad de habitaciones e iframe por categoría.
SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE categorias
  ADD COLUMN IF NOT EXISTS slug VARCHAR(100) NULL AFTER nombre;

UPDATE categorias
SET slug = LOWER(TRIM(BOTH '-' FROM REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nombre,'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u'),'ñ','n'),' ','-')))
WHERE slug IS NULL OR slug='';

CREATE TABLE IF NOT EXISTS servicios_catalogo (
  id_servicio INT NOT NULL AUTO_INCREMENT,
  id_hotel INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  icono VARCHAR(80) NULL,
  estado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  PRIMARY KEY(id_servicio),
  UNIQUE KEY uk_servicio_hotel_nombre(id_hotel,nombre),
  CONSTRAINT fk_servicio_hotel FOREIGN KEY(id_hotel) REFERENCES hoteles(id_hotel) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS beneficios_catalogo (
  id_beneficio INT NOT NULL AUTO_INCREMENT,
  id_hotel INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  estado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  PRIMARY KEY(id_beneficio),
  UNIQUE KEY uk_beneficio_hotel_nombre(id_hotel,nombre),
  CONSTRAINT fk_beneficio_hotel FOREIGN KEY(id_hotel) REFERENCES hoteles(id_hotel) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categoria_servicios (
  id_categoria INT NOT NULL,
  id_servicio INT NOT NULL,
  PRIMARY KEY(id_categoria,id_servicio),
  CONSTRAINT fk_cs_categoria FOREIGN KEY(id_categoria) REFERENCES categorias(id_categoria) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cs_servicio FOREIGN KEY(id_servicio) REFERENCES servicios_catalogo(id_servicio) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categoria_beneficios (
  id_categoria INT NOT NULL,
  id_beneficio INT NOT NULL,
  PRIMARY KEY(id_categoria,id_beneficio),
  CONSTRAINT fk_cb_categoria FOREIGN KEY(id_categoria) REFERENCES categorias(id_categoria) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cb_beneficio FOREIGN KEY(id_beneficio) REFERENCES beneficios_catalogo(id_beneficio) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS habitacion_categorias (
  id_habitacion INT NOT NULL,
  id_categoria INT NOT NULL,
  precio_especial DECIMAL(10,2) NULL,
  estado ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  PRIMARY KEY(id_habitacion,id_categoria),
  CONSTRAINT fk_hc_habitacion FOREIGN KEY(id_habitacion) REFERENCES habitaciones(id_habitacion) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_hc_categoria FOREIGN KEY(id_categoria) REFERENCES categorias(id_categoria) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO habitacion_categorias(id_habitacion,id_categoria)
SELECT id_habitacion,id_categoria FROM habitaciones;

ALTER TABLE reservas
  ADD COLUMN IF NOT EXISTS cantidad_habitaciones INT NOT NULL DEFAULT 1 AFTER id_habitacion;

CREATE TABLE IF NOT EXISTS reserva_detalle (
  id_detalle INT NOT NULL AUTO_INCREMENT,
  id_reserva INT NOT NULL,
  id_categoria INT NOT NULL,
  id_habitacion INT NULL,
  precio_noche DECIMAL(10,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  PRIMARY KEY(id_detalle),
  KEY idx_rd_reserva(id_reserva),
  CONSTRAINT fk_rd_reserva FOREIGN KEY(id_reserva) REFERENCES reservas(id_reserva) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_rd_categoria FOREIGN KEY(id_categoria) REFERENCES categorias(id_categoria) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_rd_habitacion FOREIGN KEY(id_habitacion) REFERENCES habitaciones(id_habitacion) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO servicios_catalogo(id_hotel,nombre)
SELECT h.id_hotel, x.nombre FROM hoteles h JOIN (
 SELECT 'WiFi' nombre UNION ALL SELECT 'A/C' UNION ALL SELECT 'Baño privado' UNION ALL SELECT 'Balcón' UNION ALL SELECT 'Jacuzzi' UNION ALL SELECT 'TV' UNION ALL SELECT 'Agua caliente' UNION ALL SELECT 'Estacionamiento' UNION ALL SELECT 'Piscina'
) x;
INSERT IGNORE INTO beneficios_catalogo(id_hotel,nombre)
SELECT h.id_hotel, x.nombre FROM hoteles h JOIN (
 SELECT 'Desayuno' nombre UNION ALL SELECT 'Acceso a piscina' UNION ALL SELECT 'Limpieza diaria' UNION ALL SELECT 'Toallas' UNION ALL SELECT 'Amenities' UNION ALL SELECT 'Recepción 24 horas' UNION ALL SELECT 'Estacionamiento' UNION ALL SELECT 'Traslado'
) x;

SET FOREIGN_KEY_CHECKS=1;
