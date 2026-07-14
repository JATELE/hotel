USE `eboiskqx_cmrhoteles_bd`;

-- =========================================================
-- PRUEBA: cambiar la habitación 106 para que su categoría
-- principal sea Habitación Triple y pueda venderse también
-- como Habitación Doble y Habitación Simple.
-- Cambia 106 y los nombres si necesitas probar otro cuarto.
-- =========================================================

SET @id_hotel := 1;
SET @numero_habitacion := '106';

SET @id_habitacion := (
    SELECT id_habitacion
    FROM habitaciones
    WHERE id_hotel = @id_hotel
      AND numero_habitacion = @numero_habitacion
    LIMIT 1
);

SET @id_triple := (
    SELECT id_categoria
    FROM categorias
    WHERE id_hotel = @id_hotel
      AND nombre = 'Habitación Triple'
    LIMIT 1
);

SET @id_doble := (
    SELECT id_categoria
    FROM categorias
    WHERE id_hotel = @id_hotel
      AND nombre = 'Habitación Doble'
    LIMIT 1
);

SET @id_simple := (
    SELECT id_categoria
    FROM categorias
    WHERE id_hotel = @id_hotel
      AND nombre = 'Habitación Simple'
    LIMIT 1
);

START TRANSACTION;

-- La categoría principal representa la capacidad real del cuarto.
UPDATE habitaciones
SET id_categoria = @id_triple
WHERE id_habitacion = @id_habitacion
  AND id_hotel = @id_hotel;

-- Reemplazar las categorías comerciales permitidas.
DELETE FROM habitacion_categorias
WHERE id_habitacion = @id_habitacion;

INSERT INTO habitacion_categorias (id_habitacion, id_categoria, estado)
SELECT @id_habitacion, @id_triple, 'ACTIVO'
WHERE @id_habitacion IS NOT NULL AND @id_triple IS NOT NULL
UNION ALL
SELECT @id_habitacion, @id_doble, 'ACTIVO'
WHERE @id_habitacion IS NOT NULL AND @id_doble IS NOT NULL
UNION ALL
SELECT @id_habitacion, @id_simple, 'ACTIVO'
WHERE @id_habitacion IS NOT NULL AND @id_simple IS NOT NULL;

COMMIT;

-- Verificación
SELECT
    h.numero_habitacion,
    cp.nombre AS categoria_principal,
    GROUP_CONCAT(c.nombre ORDER BY c.capacidad_pax DESC SEPARATOR ', ') AS puede_venderse_como
FROM habitaciones h
INNER JOIN categorias cp ON cp.id_categoria = h.id_categoria
LEFT JOIN habitacion_categorias hc ON hc.id_habitacion = h.id_habitacion AND hc.estado = 'ACTIVO'
LEFT JOIN categorias c ON c.id_categoria = hc.id_categoria
WHERE h.id_hotel = @id_hotel
  AND h.numero_habitacion = @numero_habitacion
GROUP BY h.id_habitacion, h.numero_habitacion, cp.nombre;
