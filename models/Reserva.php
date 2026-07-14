<?php
class Reserva {
    private $conn;
    private $table = 'reservas';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear($id_hotel, $id_categoria, $fecha_checkin, $fecha_checkout, $precio_final, $canal_reserva = 'Web', $id_usuario_registro = null, $cantidad_habitaciones = 1) {
        $query = "INSERT INTO {$this->table}
            (id_hotel, id_categoria, id_cliente, id_habitacion, cantidad_habitaciones, id_usuario_registro, fecha_checkin, fecha_checkout, precio_final, estado_reserva, canal_reserva)
            VALUES (?, ?, NULL, NULL, ?, ?, ?, ?, ?, 'Pendiente', ?)";
        $stmt = $this->conn->prepare($query);
        try {
            if($stmt->execute([$id_hotel, $id_categoria, max(1,(int)$cantidad_habitaciones), $id_usuario_registro, $fecha_checkin, $fecha_checkout, $precio_final, $canal_reserva])) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $e) {
            return false;
        }
        return false;
    }

    public function categoriaPerteneceHotel($id_categoria, $id_hotel) {
        $stmt = $this->conn->prepare("SELECT id_categoria FROM categorias WHERE id_categoria = ? AND id_hotel = ? AND estado = 'ACTIVO'");
        $stmt->execute([$id_categoria, $id_hotel]);
        return (bool)$stmt->fetch();
    }


    public function clienteNoGrato($id_hotel, $dni) {
        $dni = trim((string)$dni);
        if ($dni === '') return null;
        try {
            $stmtCols = $this->conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientes_no_gratos'");
            $stmtCols->execute();
            if ((int)$stmtCols->fetchColumn() === 0) return null;
            $stmt = $this->conn->prepare("SELECT id_no_grato, nombres, apellidos, dni, motivo FROM clientes_no_gratos WHERE id_hotel = ? AND dni = ? AND estado = 'ACTIVO' LIMIT 1");
            $stmt->execute([(int)$id_hotel, $dni]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }


    public function gestionPorHabitacion($id_hotel) {
        try {
            $stmtCols = $this->conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hoteles' AND COLUMN_NAME = 'gestion_por_habitacion'");
            $stmtCols->execute();
            if ((int)$stmtCols->fetchColumn() === 0) return false;
            $stmt = $this->conn->prepare("SELECT gestion_por_habitacion FROM hoteles WHERE id_hotel = ? LIMIT 1");
            $stmt->execute([(int)$id_hotel]);
            return (int)($stmt->fetchColumn() ?: 0) === 1;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function habitacionesLibres($id_hotel, $id_categoria, $fecha_checkin, $fecha_checkout, $excluir_id_reserva = null) {
        $stmtHabitaciones = $this->conn->prepare("SELECT DISTINCT h.id_habitacion, h.numero_habitacion
            FROM habitaciones h
            LEFT JOIN habitacion_categorias hc
                ON hc.id_habitacion = h.id_habitacion
               AND hc.estado = 'ACTIVO'
            WHERE h.id_hotel = ?
              AND (h.id_categoria = ? OR hc.id_categoria = ?)
              AND h.estado = 'Disponible'
            ORDER BY h.numero_habitacion ASC");
        $stmtHabitaciones->execute([(int)$id_hotel, (int)$id_categoria, (int)$id_categoria]);
        $habitaciones = $stmtHabitaciones->fetchAll(PDO::FETCH_ASSOC);
        if (!$habitaciones) return [];

        $ocupadas = [];
        $sql = "
            SELECT DISTINCT ocupacion.id_habitacion
            FROM (
                SELECT rd.id_habitacion
                FROM reserva_detalle rd
                INNER JOIN reservas r ON r.id_reserva = rd.id_reserva
                WHERE r.id_hotel = ?
                  AND r.estado_reserva NOT IN ('Cancelada','Rechazada')
                  AND rd.id_habitacion IS NOT NULL
                  AND r.fecha_checkin < ?
                  AND r.fecha_checkout > ?";
        $params = [(int)$id_hotel, $fecha_checkout, $fecha_checkin];
        if ($excluir_id_reserva) {
            $sql .= " AND r.id_reserva <> ?";
            $params[] = (int)$excluir_id_reserva;
        }
        $sql .= "
                UNION
                SELECT r.id_habitacion
                FROM reservas r
                WHERE r.id_hotel = ?
                  AND r.estado_reserva NOT IN ('Cancelada','Rechazada')
                  AND r.id_habitacion IS NOT NULL
                  AND r.fecha_checkin < ?
                  AND r.fecha_checkout > ?";
        $params[] = (int)$id_hotel;
        $params[] = $fecha_checkout;
        $params[] = $fecha_checkin;
        if ($excluir_id_reserva) {
            $sql .= " AND r.id_reserva <> ?";
            $params[] = (int)$excluir_id_reserva;
        }
        $sql .= ") ocupacion WHERE ocupacion.id_habitacion IS NOT NULL";

        $stmtOcupadas = $this->conn->prepare($sql);
        $stmtOcupadas->execute($params);
        $ocupadas = array_flip(array_map('intval', $stmtOcupadas->fetchAll(PDO::FETCH_COLUMN)));

        // Habitaciones bloqueadas por mantenimiento/inactividad programada.
        try {
            $stmtTablaBloqueos = $this->conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitaciones_bloqueos'");
            $stmtTablaBloqueos->execute();
            if ((int)$stmtTablaBloqueos->fetchColumn() > 0) {
                $stmtBloqueos = $this->conn->prepare("SELECT DISTINCT id_habitacion
                    FROM habitaciones_bloqueos
                    WHERE id_hotel = ?
                      AND estado = 'ACTIVO'
                      AND fecha_inicio < ?
                      AND fecha_fin >= ?");
                $stmtBloqueos->execute([(int)$id_hotel, $fecha_checkout, $fecha_checkin]);
                foreach (array_map('intval', $stmtBloqueos->fetchAll(PDO::FETCH_COLUMN)) as $idBloq) {
                    $ocupadas[$idBloq] = true;
                }
            }
        } catch (Throwable $e) {}

        $libres = [];
        foreach ($habitaciones as $h) {
            if (!isset($ocupadas[(int)$h['id_habitacion']])) $libres[] = $h;
        }
        return $libres;
    }

    public function obtenerBloqueoHabitacion($id_hotel, $id_habitacion, $fecha_checkin, $fecha_checkout) {
        try {
            $sql = "SELECT id_bloqueo, fecha_inicio, fecha_fin, motivo
                    FROM habitaciones_bloqueos
                    WHERE id_hotel = ?
                      AND id_habitacion = ?
                      AND estado = 'ACTIVO'
                      AND fecha_inicio < ?
                      AND fecha_fin >= ?
                    ORDER BY fecha_inicio ASC, id_bloqueo ASC
                    LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([(int)$id_hotel, (int)$id_habitacion, $fecha_checkout, $fecha_checkin]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function obtenerConflictoHabitacion($id_hotel, $id_habitacion, $fecha_checkin, $fecha_checkout, $excluir_id_reserva = null) {
        $sql = "SELECT r.id_reserva, CONCAT('#ADS-', r.id_reserva) AS codigo_reserva, r.fecha_checkin, r.fecha_checkout, r.estado_reserva
                FROM reservas r
                WHERE r.id_hotel = ?
                  AND r.estado_reserva NOT IN ('Cancelada','Rechazada')
                  AND r.fecha_checkin < ?
                  AND r.fecha_checkout > ?
                  AND (
                        r.id_habitacion = ?
                        OR EXISTS (
                            SELECT 1 FROM reserva_detalle rd
                            WHERE rd.id_reserva = r.id_reserva
                              AND rd.id_habitacion = ?
                        )
                  )";
        $params = [(int)$id_hotel, $fecha_checkout, $fecha_checkin, (int)$id_habitacion, (int)$id_habitacion];
        if ($excluir_id_reserva) {
            $sql .= " AND r.id_reserva <> ?";
            $params[] = (int)$excluir_id_reserva;
        }
        $sql .= " ORDER BY r.fecha_checkin ASC, r.id_reserva ASC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function hayDisponibilidadCategoria($id_hotel, $id_categoria, $fecha_checkin, $fecha_checkout, $excluir_id_reserva = null, $cantidad = 1) {
        if (!$this->gestionPorHabitacion($id_hotel)) {
            return $this->categoriaPerteneceHotel($id_categoria, $id_hotel);
        }
        return count($this->habitacionesLibres($id_hotel, $id_categoria, $fecha_checkin, $fecha_checkout, $excluir_id_reserva)) >= max(1,(int)$cantidad);
    }

    public function habitacionCompatibleConCategoria($id_hotel, $id_habitacion, $id_categoria) {
        /*
         * Una habitación puede cubrir una categoría inferior cuando:
         * 1) existe relación activa en habitacion_categorias, o
         * 2) su categoría principal tiene igual o mayor capacidad.
         *
         * La disponibilidad por fechas se valida por separado; aquí solo se
         * comprueba hotel, categoría y compatibilidad comercial.
         */
        $sql = "SELECT 1
                FROM habitaciones h
                INNER JOIN categorias cprincipal
                    ON cprincipal.id_categoria = h.id_categoria
                   AND cprincipal.id_hotel = h.id_hotel
                INNER JOIN categorias csol
                    ON csol.id_categoria = ?
                   AND csol.id_hotel = h.id_hotel
                LEFT JOIN habitacion_categorias hc
                    ON hc.id_habitacion = h.id_habitacion
                   AND hc.id_categoria = csol.id_categoria
                   AND UPPER(hc.estado) = 'ACTIVO'
                WHERE h.id_hotel = ?
                  AND h.id_habitacion = ?
                  AND UPPER(csol.estado) = 'ACTIVO'
                  AND UPPER(h.estado) NOT IN ('MANTENIMIENTO','FUERA DE SERVICIO','BLOQUEADA')
                  AND (
                        h.id_categoria = csol.id_categoria
                        OR hc.id_categoria IS NOT NULL
                        OR COALESCE(cprincipal.capacidad_pax, 0) >= COALESCE(csol.capacidad_pax, 0)
                  )
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([(int)$id_categoria, (int)$id_hotel, (int)$id_habitacion]);
        return (bool)$stmt->fetchColumn();
    }

    public function habitacionLibre($id_hotel, $id_categoria, $id_habitacion, $fecha_checkin, $fecha_checkout, $excluir_id_reserva = null) {
        if (!$this->gestionPorHabitacion($id_hotel)) return true;
        $libres = $this->habitacionesLibres($id_hotel, $id_categoria, $fecha_checkin, $fecha_checkout, $excluir_id_reserva);
        foreach ($libres as $h) {
            if ((int)$h['id_habitacion'] === (int)$id_habitacion) return true;
        }
        return false;
    }


    public function guardarDetalleHabitaciones($id_reserva, $id_categoria, array $detalles_habitaciones, $fecha_checkin, $fecha_checkout, $id_usuario_ajuste = null) {
        $this->conn->beginTransaction();
        try {
            $this->conn->prepare("DELETE FROM reserva_detalle WHERE id_reserva=?")->execute([(int)$id_reserva]);
            if (!$detalles_habitaciones) {
                $this->conn->commit();
                return true;
            }

            $noches = max(1, (int)round((strtotime($fecha_checkout) - strtotime($fecha_checkin)) / 86400));
            $stmt = $this->conn->prepare("INSERT INTO reserva_detalle
                (id_reserva,id_categoria,id_habitacion,precio_original,precio_noche,descuento,motivo_ajuste,id_usuario_ajuste,subtotal)
                VALUES(?,?,?,?,?,?,?,?,?)");
            $total = 0;
            $insertados = 0;
            foreach ($detalles_habitaciones as $detalle) {
                $idHab = (int)($detalle['id_habitacion'] ?? 0);
                if ($idHab <= 0) continue;
                $original = max(0, (float)($detalle['precio_original'] ?? 0));
                $aplicado = max(0, (float)($detalle['precio_aplicado'] ?? $original));
                $descuento = max(0, $original - $aplicado);
                $motivo = trim((string)($detalle['motivo_ajuste'] ?? '')) ?: null;
                $subtotal = $aplicado * $noches;
                $stmt->execute([(int)$id_reserva,(int)$id_categoria,$idHab,$original,$aplicado,$descuento,$motivo,$id_usuario_ajuste,$subtotal]);
                $total += $subtotal;
                $insertados++;
            }

            if ($insertados !== count($detalles_habitaciones)) {
                throw new RuntimeException('No se guardaron todos los detalles de habitaciones.');
            }

            $this->conn->prepare("UPDATE reservas SET precio_final=?, cantidad_habitaciones=? WHERE id_reserva=?")
                ->execute([$total,$insertados,(int)$id_reserva]);
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            throw $e;
        }
    }


    public function obtenerDetallesHabitaciones($id_reserva) {
        $sql = "SELECT
                    rd.id_detalle,
                    rd.id_habitacion,
                    h.numero_habitacion,
                    cp.nombre AS categoria_principal,
                    cv.nombre AS categoria_vendida,
                    rd.precio_original,
                    rd.precio_noche AS precio_aplicado,
                    rd.descuento,
                    rd.motivo_ajuste,
                    rd.subtotal,
                    IFNULL(CONCAT(u.nombres, ' ', u.apellidos), '') AS usuario_ajuste
                FROM reserva_detalle rd
                LEFT JOIN habitaciones h ON h.id_habitacion = rd.id_habitacion
                LEFT JOIN categorias cp ON cp.id_categoria = h.id_categoria
                LEFT JOIN categorias cv ON cv.id_categoria = rd.id_categoria
                LEFT JOIN usuarios u ON u.id_usuario = rd.id_usuario_ajuste
                WHERE rd.id_reserva = ?
                ORDER BY h.numero_habitacion ASC, rd.id_detalle ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([(int)$id_reserva]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function leerTodas($id_hotel = null, $esSuperadmin = false) {
        $where = '';
        $params = [];
        if (!$esSuperadmin) {
            $where = 'WHERE r.id_hotel = ?';
            $params[] = $id_hotel;
        }

        $query = "SELECT
                    r.id_reserva, r.id_hotel, h.nombre_comercial AS hotel,
                    r.id_categoria, r.id_habitacion, r.cantidad_habitaciones, cat.nombre AS categoria_solicitada, cat.precio_base AS tarifa_inicial,
                    IFNULL(CONCAT(cli.nombres, ' ', cli.apellidos), 'NUEVA CONSULTA (WEB)') AS cliente,
                    IFNULL(cli.nombres, '') AS cli_nombres, IFNULL(cli.apellidos, '') AS cli_apellidos,
                    IFNULL(cli.documento_identidad, '---') AS dni,
                    IFNULL(cli.telefono, '') AS telefono,
                    IFNULL(cli.email, '') AS email,
                    CASE WHEN cng.id_no_grato IS NULL THEN 0 ELSE 1 END AS cliente_no_grato,
                    IFNULL(cng.motivo, '') AS cliente_no_grato_motivo,
                    IFNULL(hab.numero_habitacion, 'Por Asignar') AS nro_cuarto,
                    DATE_FORMAT(r.fecha_checkin, '%Y-%m-%d') AS fecha_checkin,
                    DATE_FORMAT(r.fecha_checkout, '%Y-%m-%d') AS fecha_checkout,
                    r.precio_final, r.estado_reserva, r.canal_reserva, IFNULL(r.notas, '') AS notas,
                    DATE_FORMAT(r.fecha_registro, '%Y-%m-%d %H:%i') AS fecha_consulta,
                    IFNULL(CONCAT(ureg.nombres, ' ', ureg.apellidos), 'Web/Iframe') AS recepcionista_registro,
                    IFNULL(CONCAT(uconf.nombres, ' ', uconf.apellidos), '') AS recepcionista_confirmacion,
                    DATE_FORMAT(r.fecha_confirmacion, '%Y-%m-%d %H:%i') AS fecha_confirmacion,
                    r.id_usuario_tomada, DATE_FORMAT(r.fecha_tomada, '%Y-%m-%d %H:%i') AS fecha_tomada,
                    DATE_FORMAT(r.bloqueo_hasta, '%Y-%m-%d %H:%i') AS bloqueo_hasta,
                    CASE WHEN r.bloqueo_hasta IS NOT NULL AND r.bloqueo_hasta > NOW() THEN 1 ELSE 0 END AS bloqueo_activo,
                    IFNULL(CONCAT(utom.nombres, ' ', utom.apellidos), '') AS usuario_tomada_nombre
                FROM {$this->table} r
                INNER JOIN hoteles h ON r.id_hotel = h.id_hotel
                INNER JOIN categorias cat ON r.id_categoria = cat.id_categoria
                LEFT JOIN clientes cli ON r.id_cliente = cli.id_cliente
                LEFT JOIN clientes_no_gratos cng ON cng.id_hotel = r.id_hotel AND cng.dni = cli.documento_identidad AND cng.estado = 'ACTIVO'
                LEFT JOIN habitaciones hab ON r.id_habitacion = hab.id_habitacion
                LEFT JOIN usuarios ureg ON r.id_usuario_registro = ureg.id_usuario
                LEFT JOIN usuarios uconf ON r.id_usuario_confirmacion = uconf.id_usuario
                LEFT JOIN usuarios utom ON r.id_usuario_tomada = utom.id_usuario
                {$where}
                ORDER BY r.id_reserva DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    public function eliminar($id_reserva, $id_hotel = null, $esSuperadmin = false) {
        $id_reserva = (int)$id_reserva;
        $this->conn->beginTransaction();
        try {
            $where = "id_reserva = ?";
            $params = [$id_reserva];
            if (!$esSuperadmin) {
                $where .= " AND id_hotel = ?";
                $params[] = (int)$id_hotel;
            }

            $ver = $this->conn->prepare("SELECT id_reserva FROM {$this->table} WHERE {$where} LIMIT 1 FOR UPDATE");
            $ver->execute($params);
            if (!$ver->fetchColumn()) {
                $this->conn->rollBack();
                return false;
            }

            $this->conn->prepare("DELETE FROM habitacion_operaciones WHERE id_reserva=?")->execute([$id_reserva]);
            $this->conn->prepare("DELETE FROM habitacion_limpieza WHERE id_reserva=?")->execute([$id_reserva]);
            $this->conn->prepare("DELETE FROM reserva_detalle WHERE id_reserva=?")->execute([$id_reserva]);

            $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE {$where}");
            $ok = $stmt->execute($params);
            $this->conn->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            return false;
        }
    }

    public function buscarClientePorDni($dni, $id_hotel) {
        $query = "SELECT id_cliente FROM clientes WHERE documento_identidad = ? AND id_hotel = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$dni, $id_hotel]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crearCliente($id_hotel, $nombres, $apellidos, $dni, $telefono, $email) {
        $query = "INSERT INTO clientes (id_hotel, nombres, apellidos, documento_identidad, telefono, email) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute([$id_hotel, $nombres, $apellidos, $dni, $telefono, $email])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function actualizarCliente($id_cliente, $id_hotel, $nombres, $apellidos, $dni, $telefono, $email) {
        $query = "UPDATE clientes SET nombres = ?, apellidos = ?, documento_identidad = ?, telefono = ?, email = ? WHERE id_cliente = ? AND id_hotel = ?";
        $stmt = $this->conn->prepare($query);
        try {
            return $stmt->execute([$nombres, $apellidos, $dni, $telefono, $email, $id_cliente, $id_hotel]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function confirmarReserva($id_reserva, $id_hotel, $id_cliente, $notas, $id_categoria, $estado_reserva = 'Confirmada', $id_usuario_confirmacion = null, $canal_reserva = null, $id_habitacion = null) {
        $campos = "id_cliente = ?, estado_reserva = ?, notas = ?, id_categoria = ?, id_usuario_confirmacion = ?";
        $params = [$id_cliente, $estado_reserva, $notas, $id_categoria, $id_usuario_confirmacion];

        if ($estado_reserva === 'Confirmada') {
            // Confirmada: aquí recién bloquea habitación real. La reserva ya no queda tomada temporalmente.
            $campos .= ", fecha_confirmacion = NOW(), id_habitacion = ?, id_usuario_tomada = NULL, fecha_tomada = NULL, bloqueo_hasta = NULL";
            $params[] = $id_habitacion;
        } elseif ($estado_reserva === 'Atendida') {
            // Atendida: no bloquea habitación, pero queda en seguimiento por el recepcionista durante 2 horas.
            $campos .= ", id_habitacion = NULL, id_usuario_tomada = ?, fecha_tomada = NOW(), bloqueo_hasta = DATE_ADD(NOW(), INTERVAL 120 MINUTE)";
            $params[] = $id_usuario_confirmacion;
        } elseif (in_array($estado_reserva, ['Cancelada','Pendiente'], true)) {
            // Cancelada/Pendiente: no bloquea habitación ni edición.
            $campos .= ", id_habitacion = NULL, id_usuario_tomada = NULL, fecha_tomada = NULL, bloqueo_hasta = NULL";
        }

        if ($canal_reserva) {
            $campos .= ", canal_reserva = ?";
            $params[] = $canal_reserva;
        }
        $params[] = $id_reserva;
        $params[] = $id_hotel;
        $query = "UPDATE reservas SET {$campos} WHERE id_reserva = ? AND id_hotel = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    public function actualizarReservaYCliente($id_reserva, $id_hotel, $nombres, $apellidos, $dni, $telefono, $email, $notas, $id_categoria, $estado_reserva = null, $canal_reserva = null, $id_usuario_confirmacion = null, $id_habitacion = null) {
        $sets = ["notas = ?", "id_categoria = ?"];
        $params = [$notas, $id_categoria];
        if($estado_reserva) {
            $sets[] = "estado_reserva = ?";
            $params[] = $estado_reserva;
            if ($estado_reserva === 'Confirmada') {
                $sets[] = "fecha_confirmacion = NOW()";
                $sets[] = "id_usuario_confirmacion = ?";
                $params[] = $id_usuario_confirmacion;
                $sets[] = "id_habitacion = ?";
                $params[] = $id_habitacion;
                $sets[] = "id_usuario_tomada = NULL";
                $sets[] = "fecha_tomada = NULL";
                $sets[] = "bloqueo_hasta = NULL";
            } elseif ($estado_reserva === 'Atendida') {
                // Seguimiento temporal: 2 horas para que el recepcionista termine el trabajo.
                $sets[] = "id_habitacion = NULL";
                $sets[] = "id_usuario_tomada = ?";
                $params[] = $id_usuario_confirmacion;
                $sets[] = "fecha_tomada = NOW()";
                $sets[] = "bloqueo_hasta = DATE_ADD(NOW(), INTERVAL 120 MINUTE)";
            } elseif (in_array($estado_reserva, ['Cancelada','Pendiente'], true)) {
                $sets[] = "id_habitacion = NULL";
                $sets[] = "id_usuario_tomada = NULL";
                $sets[] = "fecha_tomada = NULL";
                $sets[] = "bloqueo_hasta = NULL";
            }
        }
        if ($canal_reserva) {
            $sets[] = "canal_reserva = ?";
            $params[] = $canal_reserva;
        }
        $params[] = $id_reserva;
        $params[] = $id_hotel;
        $queryRes = "UPDATE reservas SET " . implode(', ', $sets) . " WHERE id_reserva = ? AND id_hotel = ?";
        $stmtRes = $this->conn->prepare($queryRes);
        $stmtRes->execute($params);

        $queryBusqueda = "SELECT id_cliente FROM reservas WHERE id_reserva = ? AND id_hotel = ?";
        $stmtBusqueda = $this->conn->prepare($queryBusqueda);
        $stmtBusqueda->execute([$id_reserva, $id_hotel]);
        $row = $stmtBusqueda->fetch(PDO::FETCH_ASSOC);

        if($row && $row['id_cliente']) {
            return $this->actualizarCliente($row['id_cliente'], $id_hotel, $nombres, $apellidos, $dni, $telefono, $email);
        }
        return true;
    }

    public function obtenerReserva($id_reserva, $id_hotel = null, $esSuperadmin = false) {
        $sql = "SELECT r.*, CONCAT(IFNULL(u.nombres,''),' ',IFNULL(u.apellidos,'')) AS usuario_tomada_nombre
                FROM reservas r
                LEFT JOIN usuarios u ON r.id_usuario_tomada = u.id_usuario
                WHERE r.id_reserva = ?";
        $params = [(int)$id_reserva];
        if (!$esSuperadmin) {
            $sql .= " AND r.id_hotel = ?";
            $params[] = (int)$id_hotel;
        }
        $sql .= " LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function reservaBloqueadaPorOtro($id_reserva, $id_hotel, $id_usuario, $esSuperadmin = false) {
        $reserva = $this->obtenerReserva($id_reserva, $id_hotel, $esSuperadmin);
        if (!$reserva) {
            return ['bloqueada' => false, 'no_encontrada' => true];
        }
        $activa = !empty($reserva['bloqueo_hasta']) && strtotime($reserva['bloqueo_hasta']) > time();
        $otroUsuario = !empty($reserva['id_usuario_tomada']) && (int)$reserva['id_usuario_tomada'] !== (int)$id_usuario;
        if ($activa && $otroUsuario) {
            return [
                'bloqueada' => true,
                'usuario' => trim($reserva['usuario_tomada_nombre']) ?: 'otro usuario',
                'bloqueo_hasta' => $reserva['bloqueo_hasta']
            ];
        }
        return ['bloqueada' => false];
    }

    public function tomarReserva($id_reserva, $id_hotel, $id_usuario, $minutos = 30, $esSuperadmin = false) {
        $bloqueo = $this->reservaBloqueadaPorOtro($id_reserva, $id_hotel, $id_usuario, $esSuperadmin);
        if (!empty($bloqueo['no_encontrada'])) {
            return ['ok' => false, 'error' => 'Reserva no encontrada para este hotel.'];
        }
        if (!empty($bloqueo['bloqueada'])) {
            return [
                'ok' => false,
                'bloqueada' => true,
                'error' => 'Esta reserva está siendo atendida por ' . $bloqueo['usuario'] . '. Se liberará automáticamente a las ' . date('H:i', strtotime($bloqueo['bloqueo_hasta'])) . '.',
                'usuario' => $bloqueo['usuario'],
                'bloqueo_hasta' => $bloqueo['bloqueo_hasta']
            ];
        }

        $sql = "UPDATE reservas
                SET id_usuario_tomada = ?, fecha_tomada = IF(id_usuario_tomada = ?, IFNULL(fecha_tomada, NOW()), NOW()), bloqueo_hasta = DATE_ADD(NOW(), INTERVAL ? MINUTE)
                WHERE id_reserva = ?";
        $params = [(int)$id_usuario, (int)$id_usuario, (int)$minutos, (int)$id_reserva];
        if (!$esSuperadmin) {
            $sql .= " AND id_hotel = ?";
            $params[] = (int)$id_hotel;
        }
        $stmt = $this->conn->prepare($sql);
        if ($stmt->execute($params)) {
            return ['ok' => true, 'mensaje' => 'Reserva tomada para edición.', 'minutos' => (int)$minutos];
        }
        return ['ok' => false, 'error' => 'No se pudo tomar la reserva.'];
    }

    public function liberarReserva($id_reserva, $id_hotel, $id_usuario, $esSuperadmin = false) {
        $sql = "UPDATE reservas SET id_usuario_tomada = NULL, fecha_tomada = NULL, bloqueo_hasta = NULL
                WHERE id_reserva = ? AND (id_usuario_tomada = ? OR id_usuario_tomada IS NULL OR bloqueo_hasta <= NOW())";
        $params = [(int)$id_reserva, (int)$id_usuario];
        if (!$esSuperadmin) {
            $sql .= " AND id_hotel = ?";
            $params[] = (int)$id_hotel;
        }
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

}
?>
