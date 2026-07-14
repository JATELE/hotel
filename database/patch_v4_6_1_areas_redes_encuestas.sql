SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS encuesta_areas_catalogo (
  id_area_catalogo INT NOT NULL AUTO_INCREMENT,
  id_hotel INT NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255) NULL,
  estado ENUM('ACTIVA','INACTIVA') NOT NULL DEFAULT 'ACTIVA',
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_area_catalogo),
  UNIQUE KEY uk_area_catalogo_hotel_nombre (id_hotel,nombre),
  KEY idx_area_catalogo_hotel_estado (id_hotel,estado),
  CONSTRAINT fk_area_catalogo_hotel FOREIGN KEY (id_hotel)
    REFERENCES hoteles(id_hotel) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @existe_area_catalogo = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='encuesta_areas' AND COLUMN_NAME='id_area_catalogo'
);
SET @sql = IF(@existe_area_catalogo=0,
  'ALTER TABLE encuesta_areas ADD COLUMN id_area_catalogo INT NULL AFTER id_encuesta, ADD KEY idx_encuesta_area_catalogo (id_area_catalogo)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS encuesta_redes (
  id_encuesta INT NOT NULL,
  red VARCHAR(40) NOT NULL,
  PRIMARY KEY (id_encuesta,red),
  CONSTRAINT fk_encuesta_redes_encuesta FOREIGN KEY (id_encuesta)
    REFERENCES encuestas(id_encuesta) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO encuesta_areas_catalogo (id_hotel,nombre)
SELECT DISTINCT e.id_hotel,a.nombre
FROM encuesta_areas a
INNER JOIN encuestas e ON e.id_encuesta=a.id_encuesta;

UPDATE encuesta_areas a
INNER JOIN encuestas e ON e.id_encuesta=a.id_encuesta
INNER JOIN encuesta_areas_catalogo c
  ON c.id_hotel=e.id_hotel AND c.nombre=a.nombre
SET a.id_area_catalogo=c.id_area_catalogo
WHERE a.id_area_catalogo IS NULL;

INSERT IGNORE INTO encuesta_redes (id_encuesta,red)
SELECT id_encuesta,'google_reviews' FROM encuestas;
INSERT IGNORE INTO encuesta_redes (id_encuesta,red)
SELECT id_encuesta,'facebook' FROM encuestas;
INSERT IGNORE INTO encuesta_redes (id_encuesta,red)
SELECT id_encuesta,'tripadvisor' FROM encuestas;

SET FOREIGN_KEY_CHECKS=1;
