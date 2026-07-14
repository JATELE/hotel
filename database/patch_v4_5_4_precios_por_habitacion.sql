SET FOREIGN_KEY_CHECKS = 0;

SET @existe_precio_original = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reserva_detalle' AND COLUMN_NAME = 'precio_original'
);
SET @sql = IF(@existe_precio_original = 0,
    'ALTER TABLE reserva_detalle ADD COLUMN precio_original DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER id_habitacion',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_descuento = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reserva_detalle' AND COLUMN_NAME = 'descuento'
);
SET @sql = IF(@existe_descuento = 0,
    'ALTER TABLE reserva_detalle ADD COLUMN descuento DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER precio_noche',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_motivo = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reserva_detalle' AND COLUMN_NAME = 'motivo_ajuste'
);
SET @sql = IF(@existe_motivo = 0,
    'ALTER TABLE reserva_detalle ADD COLUMN motivo_ajuste VARCHAR(255) NULL AFTER descuento',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe_usuario = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reserva_detalle' AND COLUMN_NAME = 'id_usuario_ajuste'
);
SET @sql = IF(@existe_usuario = 0,
    'ALTER TABLE reserva_detalle ADD COLUMN id_usuario_ajuste INT NULL AFTER motivo_ajuste, ADD KEY idx_rd_usuario_ajuste (id_usuario_ajuste)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE reserva_detalle
SET precio_original = precio_noche
WHERE id_detalle > 0 AND precio_original = 0;

SET FOREIGN_KEY_CHECKS = 1;
