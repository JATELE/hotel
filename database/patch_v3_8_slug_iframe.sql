-- PATCH V3.8 - Slug e iframe dinámico multi-hotel
-- Ejecutar solo si ya tienes una base anterior instalada.

USE `eboiskqx_cmrhoteles_bd`;

ALTER TABLE hoteles
  ADD COLUMN IF NOT EXISTS slug VARCHAR(90) NULL AFTER nombre_comercial;

UPDATE hoteles SET slug = 'aguila-dorada' WHERE id_hotel = 1 AND (slug IS NULL OR slug = '');
UPDATE hoteles SET slug = 'inkarian' WHERE id_hotel = 2 AND (slug IS NULL OR slug = '');
UPDATE hoteles SET slug = 'flora' WHERE id_hotel = 3 AND (slug IS NULL OR slug = '');

UPDATE hoteles
SET slug = LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nombre_comercial,'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u'),' ','-'))
WHERE slug IS NULL OR slug = '';

ALTER TABLE hoteles
  MODIFY slug VARCHAR(90) NOT NULL;

ALTER TABLE hoteles
  ADD UNIQUE KEY IF NOT EXISTS uk_hoteles_slug (slug);
