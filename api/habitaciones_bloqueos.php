<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once '../config/Database.php';
require_once '../config/auth.php';

requerir_login_api(['admin_hotel', 'admin']);

function responder($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function entrada(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw ?: '{}', true);
        return is_array($json) ? $json : [];
    }
    return $_POST;
}

function estado_calculado(array $row): string {
    if (($row['estado'] ?? '') === 'CANCELADO') {
        return 'CANCELADO';
    }

    $hoy = date('Y-m-d');
    $inicio = (string)($row['fecha_inicio'] ?? '');
    $fin = (string)($row['fecha_fin'] ?? '');

    if ($inicio > $hoy) return 'PROGRAMADO';
    if ($inicio <= $hoy && $fin >= $hoy) return 'BLOQUEADO';
    return 'FINALIZADO';
}

try {
    $db = (new Database())->connect();
    if (!$db) responder(['error' => 'No se pudo conectar con la base de datos.'], 500);

    $idHotel = (int) id_hotel_actual();
    if ($idHotel <= 0) responder(['error' => 'El usuario no tiene hotel asignado.'], 403);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT b.id_bloqueo, b.id_habitacion, b.fecha_inicio, b.fecha_fin,
                       b.motivo, b.estado, b.fecha_registro,
                       h.numero_habitacion, c.id_categoria, c.nombre AS categoria
                FROM habitaciones_bloqueos b
                INNER JOIN habitaciones h ON h.id_habitacion = b.id_habitacion AND h.id_hotel = b.id_hotel
                INNER JOIN categorias c ON c.id_categoria = h.id_categoria AND c.id_hotel = b.id_hotel
                WHERE b.id_hotel = ?
                ORDER BY b.fecha_inicio DESC, b.id_bloqueo DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idHotel]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['id_bloqueo'] = (int)$row['id_bloqueo'];
            $row['id_habitacion'] = (int)$row['id_habitacion'];
            $row['id_categoria'] = (int)$row['id_categoria'];
            $row['estado_calculado'] = estado_calculado($row);
        }
        unset($row);

        responder(['bloqueos' => $rows]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responder(['error' => 'Método no permitido.'], 405);
    }

    $data = entrada();
    $accion = strtolower(trim((string)($data['accion'] ?? 'crear')));

    if ($accion === 'cancelar') {
        $idBloqueo = (int)($data['id_bloqueo'] ?? 0);
        if ($idBloqueo <= 0) responder(['error' => 'Bloqueo inválido.'], 422);

        $stmt = $db->prepare("UPDATE habitaciones_bloqueos
                              SET estado = 'CANCELADO'
                              WHERE id_bloqueo = ? AND id_hotel = ? AND estado = 'ACTIVO'");
        $stmt->execute([$idBloqueo, $idHotel]);

        if ($stmt->rowCount() === 0) {
            responder(['error' => 'El bloqueo no existe, ya fue cancelado o no pertenece al hotel.'], 404);
        }

        responder(['ok' => true, 'mensaje' => 'Bloqueo cancelado correctamente.']);
    }

    $idHabitacion = (int)($data['id_habitacion'] ?? 0);
    $fechaInicio = trim((string)($data['fecha_inicio'] ?? ''));
    $fechaFin = trim((string)($data['fecha_fin'] ?? ''));
    $motivo = trim((string)($data['motivo'] ?? ''));

    if ($idHabitacion <= 0 || !$fechaInicio || !$fechaFin) {
        responder(['error' => 'Habitación, fecha inicial y fecha final son obligatorias.'], 422);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
        responder(['error' => 'Formato de fechas inválido.'], 422);
    }
    if ($fechaFin < $fechaInicio) {
        responder(['error' => 'La fecha final no puede ser anterior a la fecha inicial.'], 422);
    }

    $stmt = $db->prepare("SELECT id_habitacion FROM habitaciones WHERE id_habitacion = ? AND id_hotel = ? LIMIT 1");
    $stmt->execute([$idHabitacion, $idHotel]);
    if (!$stmt->fetchColumn()) responder(['error' => 'La habitación no pertenece al hotel.'], 404);

    $stmt = $db->prepare("SELECT COUNT(*)
                          FROM habitaciones_bloqueos
                          WHERE id_hotel = ? AND id_habitacion = ? AND estado = 'ACTIVO'
                            AND fecha_inicio <= ? AND fecha_fin >= ?");
    $stmt->execute([$idHotel, $idHabitacion, $fechaFin, $fechaInicio]);
    if ((int)$stmt->fetchColumn() > 0) {
        responder(['error' => 'La habitación ya tiene un bloqueo que se cruza con esas fechas.'], 409);
    }

    $stmt = $db->prepare("INSERT INTO habitaciones_bloqueos
                          (id_hotel, id_habitacion, fecha_inicio, fecha_fin, motivo, estado)
                          VALUES (?, ?, ?, ?, ?, 'ACTIVO')");
    $stmt->execute([$idHotel, $idHabitacion, $fechaInicio, $fechaFin, $motivo !== '' ? $motivo : null]);

    responder([
        'ok' => true,
        'id_bloqueo' => (int)$db->lastInsertId(),
        'mensaje' => 'Bloqueo programado correctamente.'
    ], 201);
} catch (Throwable $e) {
    responder(['error' => 'Error interno al gestionar bloqueos.'], 500);
}
