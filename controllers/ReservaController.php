<?php
require_once '../config/Database.php';
require_once '../models/Reserva.php';

class ReservaController {
    private $db;
    private $reserva;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->reserva = new Reserva($this->db);
    }

    private function hotelSesion() {
        return $_SESSION['usuario']['id_hotel'] ?? null;
    }

    private function usuarioSesion() {
        return $_SESSION['usuario']['id_usuario'] ?? null;
    }

    private function esSuperadmin() {
        return isset($_SESSION['usuario']) && ($_SESSION['usuario']['rol'] ?? '') === 'superadmin';
    }

    private function fechasValidas($checkin, $checkout) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$checkin) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$checkout)) {
            return false;
        }
        return strtotime($checkout) > strtotime($checkin);
    }

    public function crear() {
        $data = json_decode(file_get_contents("php://input"));
        $idHotel = null;
        $idUsuarioRegistro = null;

        // Para los iframes/webreservas, el hotel viene desde el formulario público.
        // Si además hay sesión iniciada, solo se guarda el usuario como responsable
        // cuando pertenece al mismo hotel. Así no se cruzan hoteles al probar manualmente.
        if (!empty($data->id_hotel)) {
            $idHotel = (int)$data->id_hotel;
        } elseif (isset($_SESSION['usuario']) && !empty($_SESSION['usuario']['id_hotel'])) {
            $idHotel = (int)$_SESSION['usuario']['id_hotel'];
        }

        if (isset($_SESSION['usuario']) && !empty($_SESSION['usuario']['id_hotel']) && (int)$_SESSION['usuario']['id_hotel'] === (int)$idHotel) {
            $idUsuarioRegistro = (int)$_SESSION['usuario']['id_usuario'];
        }

        $canal = !empty($data->canal_reserva) ? $data->canal_reserva : 'Web';
        $cantidad = max(1, (int)($data->cantidad_habitaciones ?? 1));
        $canalesValidos = ['Presencial','Redes sociales','Llamada','Consulta WhatsApp','Web'];
        if (!in_array($canal, $canalesValidos, true)) $canal = 'Web';

        if($idHotel && !empty($data->id_categoria) && !empty($data->fecha_checkin) && !empty($data->fecha_checkout) && isset($data->precio_final)) {
            if (!$this->fechasValidas($data->fecha_checkin, $data->fecha_checkout)) {
                http_response_code(400);
                echo json_encode(['error' => 'Fechas inválidas. El check-out debe ser posterior al check-in.']);
                return;
            }

            if (!$this->reserva->categoriaPerteneceHotel((int)$data->id_categoria, $idHotel)) {
                http_response_code(400);
                echo json_encode(['error' => 'La categoría no pertenece al hotel indicado.']);
                return;
            }

            if (!$this->reserva->hayDisponibilidadCategoria($idHotel, (int)$data->id_categoria, $data->fecha_checkin, $data->fecha_checkout, null, $cantidad)) {
                http_response_code(409);
                echo json_encode(['error' => 'No hay habitaciones disponibles para esta categoría en las fechas seleccionadas.']);
                return;
            }

            $id_generado = $this->reserva->crear($idHotel, (int)$data->id_categoria, $data->fecha_checkin, $data->fecha_checkout, $data->precio_final, $canal, $idUsuarioRegistro, $cantidad);
            if($id_generado) {
                http_response_code(201);
                echo json_encode(['mensaje' => 'Éxito', 'id_reserva' => $id_generado]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo crear la reserva. Verifica la base de datos.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Datos incompletos. Se requiere id_hotel, id_categoria, check-in, check-out y precio.']);
        }
    }

    public function listar() {
        $idHotel = $this->hotelSesion();
        $esSuper = $this->esSuperadmin();
        if (!$esSuper && !$idHotel) {
            http_response_code(403);
            echo json_encode(['error' => 'Tu usuario no tiene hotel asignado.']);
            return;
        }

        $resultado = $this->reserva->leerTodas($idHotel, $esSuper);
        $reservas_arr = [];
        while($row = $resultado->fetch(PDO::FETCH_ASSOC)) {
            $row['detalle_habitaciones'] = $this->reserva->obtenerDetallesHabitaciones((int)$row['id_reserva']);
            $reservas_arr[] = $row;
        }
        http_response_code(200);
        echo json_encode($reservas_arr);
    }

    public function eliminar($id) {
        if($this->reserva->eliminar($id, $this->hotelSesion(), $this->esSuperadmin())) {
            http_response_code(200);
            echo json_encode(['mensaje' => 'Reserva eliminada con éxito.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error interno al intentar eliminar.']);
        }
    }

    public function actualizar($id, $accion) {
        $data = json_decode(file_get_contents("php://input"));
        $idHotel = $this->hotelSesion();

        if (!$idHotel && !$this->esSuperadmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Tu usuario no tiene hotel asignado.']);
            return;
        }
        if ($this->esSuperadmin() && !empty($data->id_hotel)) {
            $idHotel = (int)$data->id_hotel;
        }

        if ($accion === 'tomar') {
            $resultado = $this->reserva->tomarReserva((int)$id, $idHotel, (int)$this->usuarioSesion(), 30, $this->esSuperadmin());
            if (!empty($resultado['ok'])) {
                http_response_code(200);
                echo json_encode($resultado);
            } else {
                http_response_code(!empty($resultado['bloqueada']) ? 409 : 400);
                echo json_encode($resultado);
            }
            return;
        }

        if ($accion === 'liberar') {
            $this->reserva->liberarReserva((int)$id, $idHotel, (int)$this->usuarioSesion(), $this->esSuperadmin());
            http_response_code(200);
            echo json_encode(['mensaje' => 'Reserva liberada.']);
            return;
        }

        $bloqueo = $this->reserva->reservaBloqueadaPorOtro((int)$id, $idHotel, (int)$this->usuarioSesion(), $this->esSuperadmin());
        if (!empty($bloqueo['bloqueada'])) {
            http_response_code(409);
            echo json_encode(['error' => 'Esta reserva está siendo atendida por ' . $bloqueo['usuario'] . '. Se liberará automáticamente a las ' . date('H:i', strtotime($bloqueo['bloqueo_hasta'])) . '.']);
            return;
        }

        if(empty($data->nombres) || empty($data->apellidos) || empty($data->dni) || empty($data->telefono) || empty($data->id_categoria)) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan datos obligatorios']);
            return;
        }

        $noGrato = $this->reserva->clienteNoGrato($idHotel, $data->dni);
        if ($noGrato) {
            http_response_code(409);
            echo json_encode(['error' => 'Cliente no grato detectado: ' . trim(($noGrato['nombres'] ?? '') . ' ' . ($noGrato['apellidos'] ?? '')) . '. Motivo: ' . ($noGrato['motivo'] ?? 'Sin motivo registrado')]);
            return;
        }

        if (!$this->reserva->categoriaPerteneceHotel((int)$data->id_categoria, $idHotel)) {
            http_response_code(400);
            echo json_encode(['error' => 'La categoría no pertenece a este hotel.']);
            return;
        }

        $estadoReserva = isset($data->estado_reserva) ? $data->estado_reserva : ($accion === 'atender' ? 'Confirmada' : null);
        $canal = !empty($data->canal_reserva) ? $data->canal_reserva : null;
        $idUsuario = $this->usuarioSesion();
        $idsHabitaciones = array_values(array_unique(array_filter(array_map('intval', (array)($data->id_habitaciones ?? [])))));
        $detallesHabitaciones = [];
        foreach ((array)($data->detalles_habitaciones ?? []) as $detalle) {
            $idHab = (int)($detalle->id_habitacion ?? 0);
            if ($idHab <= 0) continue;
            $detallesHabitaciones[$idHab] = [
                'id_habitacion' => $idHab,
                'precio_original' => max(0, (float)($detalle->precio_original ?? 0)),
                'precio_aplicado' => max(0, (float)($detalle->precio_aplicado ?? 0)),
                'motivo_ajuste' => trim((string)($detalle->motivo_ajuste ?? ''))
            ];
        }
        $idHabitacion = $idsHabitaciones[0] ?? (!empty($data->id_habitacion) ? (int)$data->id_habitacion : null);
        if ($estadoReserva === 'Cancelada' && trim((string)($data->notas ?? '')) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Para cancelar debes escribir el motivo en notas.']);
            return;
        }
        $gestionPorHabitacion = $this->reserva->gestionPorHabitacion($idHotel);

        if ($estadoReserva === 'Confirmada' && $gestionPorHabitacion) {
            if (!$idHabitacion && !$idsHabitaciones) { http_response_code(400); echo json_encode(['error'=>'Selecciona las habitaciones requeridas.']); return; }

            $stmtReserva = $this->db->prepare("SELECT fecha_checkin, fecha_checkout, cantidad_habitaciones, precio_final FROM reservas WHERE id_reserva = ? AND id_hotel = ? LIMIT 1");
            $stmtReserva->execute([(int)$id, $idHotel]);
            $reservaActual = $stmtReserva->fetch(PDO::FETCH_ASSOC);
            if (!$reservaActual) {
                http_response_code(404);
                echo json_encode(['error' => 'Reserva no encontrada para este hotel.']);
                return;
            }
            $cantidadRequerida=max(1,(int)($reservaActual['cantidad_habitaciones']??1));
            if(!$idsHabitaciones && $idHabitacion)$idsHabitaciones=[$idHabitacion];
            if(count($idsHabitaciones)!==$cantidadRequerida){http_response_code(400);echo json_encode(['error'=>'Debes seleccionar exactamente '.$cantidadRequerida.' habitación(es).']);return;}
            foreach ($idsHabitaciones as $idHabValidar) {
                if (!$this->reserva->habitacionCompatibleConCategoria($idHotel, $idHabValidar, (int)$data->id_categoria)) {
                    $stmtNumero = $this->db->prepare("SELECT numero_habitacion FROM habitaciones WHERE id_habitacion = ? AND id_hotel = ? LIMIT 1");
                    $stmtNumero->execute([(int)$idHabValidar, (int)$idHotel]);
                    $numero = (string)($stmtNumero->fetchColumn() ?: $idHabValidar);
                    http_response_code(400);
                    echo json_encode([
                        'error' => 'La habitación ' . $numero . ' no está habilitada para venderse como la categoría solicitada. Revisa sus categorías permitidas en Conecting.'
                    ]);
                    return;
                }

                $bloqueoHabitacion = $this->reserva->obtenerBloqueoHabitacion(
                    $idHotel,
                    $idHabValidar,
                    $reservaActual['fecha_checkin'],
                    $reservaActual['fecha_checkout']
                );
                if ($bloqueoHabitacion) {
                    $stmtNumero = $this->db->prepare("SELECT numero_habitacion FROM habitaciones WHERE id_habitacion = ? AND id_hotel = ? LIMIT 1");
                    $stmtNumero->execute([(int)$idHabValidar, (int)$idHotel]);
                    $numero = (string)($stmtNumero->fetchColumn() ?: $idHabValidar);
                    http_response_code(409);
                    echo json_encode([
                        'error' => 'La habitación ' . $numero . ' está bloqueada o fuera de servicio del ' .
                            date('d/m/Y', strtotime($bloqueoHabitacion['fecha_inicio'])) . ' al ' .
                            date('d/m/Y', strtotime($bloqueoHabitacion['fecha_fin'])) .
                            '. No puede asignarse para estas fechas.'
                    ]);
                    return;
                }

                $conflicto = $this->reserva->obtenerConflictoHabitacion(
                    $idHotel,
                    $idHabValidar,
                    $reservaActual['fecha_checkin'],
                    $reservaActual['fecha_checkout'],
                    (int)$id
                );
                if ($conflicto) {
                    $stmtNumero = $this->db->prepare("SELECT numero_habitacion FROM habitaciones WHERE id_habitacion = ? AND id_hotel = ? LIMIT 1");
                    $stmtNumero->execute([(int)$idHabValidar, (int)$idHotel]);
                    $numero = (string)($stmtNumero->fetchColumn() ?: $idHabValidar);
                    $codigo = trim((string)($conflicto['codigo_reserva'] ?? ''));
                    if ($codigo === '') $codigo = '#' . (int)$conflicto['id_reserva'];
                    http_response_code(409);
                    echo json_encode([
                        'error' => 'La habitación ' . $numero . ' ya está asignada a la reserva ' . $codigo .
                            ' del ' . date('d/m/Y', strtotime($conflicto['fecha_checkin'])) .
                            ' al ' . date('d/m/Y', strtotime($conflicto['fecha_checkout'])) .
                            '. Selecciona otra habitación o cambia las fechas.'
                    ]);
                    return;
                }
            }
            /*
             * Siempre se genera un detalle por cada habitación seleccionada.
             * Si el frontend no envía precios porque el recepcionista no realizó
             * ningún ajuste, se usa automáticamente la tarifa real de la
             * categoría principal de la habitación.
             */
            $placeholders = implode(',', array_fill(0, count($idsHabitaciones), '?'));
            $paramsTarifas = array_merge([$idHotel], $idsHabitaciones);
            $stmtTarifas = $this->db->prepare("SELECT h.id_habitacion, c.precio_base
                FROM habitaciones h
                INNER JOIN categorias c ON c.id_categoria = h.id_categoria
                WHERE h.id_hotel = ?
                  AND h.id_habitacion IN ($placeholders)");
            $stmtTarifas->execute($paramsTarifas);
            $tarifasReales = [];
            foreach ($stmtTarifas->fetchAll(PDO::FETCH_ASSOC) as $filaTarifa) {
                $tarifasReales[(int)$filaTarifa['id_habitacion']] = max(0, (float)$filaTarifa['precio_base']);
            }

            $detallesOrdenados = [];
            foreach ($idsHabitaciones as $idHabPrecio) {
                $precioReal = $tarifasReales[$idHabPrecio] ?? 0;
                if ($precioReal <= 0) {
                    http_response_code(400);
                    echo json_encode(['error'=>'No se pudo obtener la tarifa real de una de las habitaciones seleccionadas.']);
                    return;
                }

                $detalleRecibido = $detallesHabitaciones[$idHabPrecio] ?? [];
                $precioOriginal = (float)($detalleRecibido['precio_original'] ?? 0);
                $precioAplicado = (float)($detalleRecibido['precio_aplicado'] ?? 0);
                $motivoAjuste = trim((string)($detalleRecibido['motivo_ajuste'] ?? ''));

                if ($precioOriginal <= 0) $precioOriginal = $precioReal;
                if ($precioAplicado <= 0) $precioAplicado = $precioOriginal;

                if (abs($precioOriginal - $precioAplicado) > 0.009 && $motivoAjuste === '') {
                    http_response_code(400);
                    echo json_encode(['error'=>'Debes indicar el motivo del descuento o ajuste de precio.']);
                    return;
                }

                $detallesOrdenados[] = [
                    'id_habitacion' => $idHabPrecio,
                    'precio_original' => $precioOriginal,
                    'precio_aplicado' => $precioAplicado,
                    'motivo_ajuste' => $motivoAjuste
                ];
            }
            $detallesHabitaciones = $detallesOrdenados;

            if (false) {
                http_response_code(409);
                echo json_encode(['error' => 'No se puede confirmar. La habitación seleccionada ya no está disponible para esas fechas.']);
                return;
            }
        }

        if($accion === 'atender') {
            $clienteExistente = $this->reserva->buscarClientePorDni($data->dni, $idHotel);
            if($clienteExistente) {
                $id_cliente = $clienteExistente['id_cliente'];
                $this->reserva->actualizarCliente($id_cliente, $idHotel, $data->nombres, $data->apellidos, $data->dni, $data->telefono, $data->email);
            } else {
                $id_cliente = $this->reserva->crearCliente($idHotel, $data->nombres, $data->apellidos, $data->dni, $data->telefono, $data->email);
            }

            if($id_cliente && $this->reserva->confirmarReserva($id, $idHotel, $id_cliente, $data->notas ?? '', (int)$data->id_categoria, $estadoReserva, $idUsuario, $canal, $idHabitacion)) {
                if ($estadoReserva === 'Confirmada' && $idsHabitaciones) {
                    try {
                        $this->reserva->guardarDetalleHabitaciones((int)$id, (int)$data->id_categoria, $detallesHabitaciones, $reservaActual['fecha_checkin'], $reservaActual['fecha_checkout'], (int)$idUsuario);
                    } catch (Throwable $e) {
                        error_log('Error guardando detalle de habitaciones: ' . $e->getMessage());
                        http_response_code(500);
                        echo json_encode(['error' => 'No se pudieron guardar las habitaciones seleccionadas. ' . $e->getMessage()]);
                        return;
                    }
                }
                http_response_code(200);
                echo json_encode(['mensaje' => 'Reserva actualizada con éxito.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al confirmar.']);
            }
        } else if ($accion === 'editar') {
            if($this->reserva->actualizarReservaYCliente($id, $idHotel, $data->nombres, $data->apellidos, $data->dni, $data->telefono, $data->email, $data->notas ?? '', (int)$data->id_categoria, $estadoReserva, $canal, $idUsuario, $idHabitacion)) {
                /*
                 * El flujo real pasa primero a Atendida y luego a Confirmada.
                 * Por eso los detalles de todas las habitaciones también deben
                 * guardarse al editar una reserva y cambiarla a Confirmada.
                 */
                if ($estadoReserva === 'Confirmada' && $gestionPorHabitacion) {
                    if (!$idsHabitaciones || count($idsHabitaciones) !== $cantidadRequerida) {
                        http_response_code(400);
                        echo json_encode(['error' => 'No se recibieron todas las habitaciones seleccionadas.']);
                        return;
                    }

                    try {
                        $this->reserva->guardarDetalleHabitaciones(
                            (int)$id,
                            (int)$data->id_categoria,
                            $detallesHabitaciones,
                            $reservaActual['fecha_checkin'],
                            $reservaActual['fecha_checkout'],
                            (int)$idUsuario
                        );
                    } catch (Throwable $e) {
                        error_log('Error guardando detalle de habitaciones: ' . $e->getMessage());
                        http_response_code(500);
                        echo json_encode(['error' => 'No se pudieron guardar las habitaciones seleccionadas. ' . $e->getMessage()]);
                        return;
                    }
                }

                http_response_code(200);
                echo json_encode(['mensaje' => 'Reserva y cliente actualizados.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al editar la reserva o cliente.']);
            }
        }
    }
}
?>
