-- CRM Hoteles V4.6.7
-- Limpia operaciones y asignaciones de limpieza huérfanas de reservas ya eliminadas.
-- No modifica la estructura de la base de datos.

DELETE ho
FROM habitacion_operaciones ho
LEFT JOIN reservas r ON r.id_reserva = ho.id_reserva
WHERE ho.id_reserva IS NOT NULL
  AND r.id_reserva IS NULL;

DELETE hl
FROM habitacion_limpieza hl
LEFT JOIN reservas r ON r.id_reserva = hl.id_reserva
WHERE hl.id_reserva IS NOT NULL
  AND r.id_reserva IS NULL;
