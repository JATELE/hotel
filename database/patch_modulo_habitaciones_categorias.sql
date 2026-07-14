-- PATCH MÓDULO HABITACIONES/CATEGORÍAS - CRM HOTELES V3
-- Ejecutar una sola vez en phpMyAdmin / MySQL Workbench sobre la BD del CRM.

ALTER TABLE categorias
  ADD COLUMN IF NOT EXISTS precio_anterior DECIMAL(10,2) NULL AFTER precio_base,
  ADD COLUMN IF NOT EXISTS etiqueta VARCHAR(80) NULL AFTER capacidad_pax,
  ADD COLUMN IF NOT EXISTS tipo_cama VARCHAR(80) NULL AFTER etiqueta,
  ADD COLUMN IF NOT EXISTS servicios TEXT NULL AFTER tipo_cama,
  ADD COLUMN IF NOT EXISTS incluye TEXT NULL AFTER servicios,
  ADD COLUMN IF NOT EXISTS imagen_1 VARCHAR(500) NULL AFTER incluye,
  ADD COLUMN IF NOT EXISTS imagen_2 VARCHAR(500) NULL AFTER imagen_1,
  ADD COLUMN IF NOT EXISTS imagen_3 VARCHAR(500) NULL AFTER imagen_2,
  ADD COLUMN IF NOT EXISTS galeria_url VARCHAR(500) NULL AFTER imagen_3;

-- Valores visuales iniciales para categorías existentes.
UPDATE categorias SET
  precio_anterior = IFNULL(precio_anterior, precio_base + 50),
  etiqueta = IFNULL(etiqueta, CASE
    WHEN nombre LIKE '%Suite%' THEN 'Vista a selva / Promo'
    WHEN nombre LIKE '%Matrimonial%' THEN 'Oferta especial'
    WHEN nombre LIKE '%Triple%' THEN 'Ideal familias'
    WHEN nombre LIKE '%Doble%' THEN 'Disponibilidad limitada'
    ELSE 'Económica'
  END),
  tipo_cama = IFNULL(tipo_cama, CASE
    WHEN nombre LIKE '%Triple%' THEN 'Triple'
    WHEN nombre LIKE '%Doble%' THEN '2 Camas'
    WHEN nombre LIKE '%Simple%' THEN 'Simple'
    ELSE 'King'
  END),
  servicios = IFNULL(servicios, CASE
    WHEN nombre LIKE '%Suite%' OR nombre LIKE '%Matrimonial%' THEN 'A/C, WiFi, Balcón, Baño privado'
    ELSE 'A/C, WiFi, Baño privado'
  END),
  incluye = IFNULL(incluye, 'Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina'),
  imagen_1 = IFNULL(imagen_1, CASE
    WHEN nombre LIKE '%Matrimonial%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/1.webp'
    WHEN nombre LIKE '%Triple%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/10.webp'
    WHEN nombre LIKE '%Doble%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/13.webp'
    WHEN nombre LIKE '%Simple%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/16.webp'
    ELSE 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/7.webp'
  END),
  imagen_2 = IFNULL(imagen_2, CASE
    WHEN nombre LIKE '%Matrimonial%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/2.webp'
    WHEN nombre LIKE '%Triple%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/11.webp'
    WHEN nombre LIKE '%Doble%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/14.webp'
    WHEN nombre LIKE '%Simple%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/17.webp'
    ELSE 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/8.webp'
  END),
  imagen_3 = IFNULL(imagen_3, CASE
    WHEN nombre LIKE '%Matrimonial%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/3.webp'
    WHEN nombre LIKE '%Triple%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/12.webp'
    WHEN nombre LIKE '%Doble%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/15.webp'
    WHEN nombre LIKE '%Simple%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/18.webp'
    ELSE 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/9.webp'
  END),
  galeria_url = IFNULL(galeria_url, '#');
