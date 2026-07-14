-- PATCH V3.9 - Opciones de consultas, imágenes y gestión por habitación
-- Ejecutar solo si ya tienes una versión anterior importada.

ALTER TABLE hoteles
  ADD COLUMN IF NOT EXISTS gestion_por_habitacion TINYINT(1) NOT NULL DEFAULT 0 AFTER slug;

UPDATE hoteles SET gestion_por_habitacion = 0 WHERE gestion_por_habitacion IS NULL;
ALTER TABLE clientes MODIFY email VARCHAR(120) DEFAULT NULL;
