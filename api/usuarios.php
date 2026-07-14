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

try {
    $db = (new Database())->connect();
    if (!$db) responder(['error' => 'No se pudo conectar con la base de datos.'], 500);

    $usuarioSesion = usuario_actual();
    $idHotel = (int) id_hotel_actual();
    $idUsuarioActual = (int)($usuarioSesion['id_usuario'] ?? 0);

    if ($idHotel <= 0 || $idUsuarioActual <= 0) {
        responder(['error' => 'Sesión o hotel inválido.'], 403);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->prepare("SELECT id_usuario, usuario, email, nombres, apellidos, telefono, estado
                              FROM usuarios
                              WHERE id_hotel = ? AND id_rol = 3
                              ORDER BY nombres, apellidos, id_usuario");
        $stmt->execute([$idHotel]);
        responder(['usuarios' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responder(['error' => 'Método no permitido.'], 405);
    }

    $data = entrada();
    $accion = strtolower(trim((string)($data['accion'] ?? '')));

    if ($accion === 'cambiar_password_propia') {
        $actual = (string)($data['password_actual'] ?? '');
        $nueva = (string)($data['password_nueva'] ?? '');
        $confirmar = (string)($data['password_confirmar'] ?? '');

        if ($actual === '' || $nueva === '' || $confirmar === '') {
            responder(['error' => 'Completa todos los campos de contraseña.'], 422);
        }
        if (strlen($nueva) < 6) responder(['error' => 'La nueva contraseña debe tener al menos 6 caracteres.'], 422);
        if ($nueva !== $confirmar) responder(['error' => 'Las contraseñas nuevas no coinciden.'], 422);

        $stmt = $db->prepare("SELECT password_hash FROM usuarios WHERE id_usuario = ? AND id_hotel = ? LIMIT 1");
        $stmt->execute([$idUsuarioActual, $idHotel]);
        $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($actual, $hash)) {
            responder(['error' => 'La contraseña actual no es correcta.'], 422);
        }

        $stmt = $db->prepare("UPDATE usuarios SET password_hash = ? WHERE id_usuario = ? AND id_hotel = ?");
        $stmt->execute([password_hash($nueva, PASSWORD_DEFAULT), $idUsuarioActual, $idHotel]);
        responder(['ok' => true, 'mensaje' => 'Contraseña actualizada correctamente.']);
    }

    if ($accion === 'restablecer_password') {
        $idUsuario = (int)($data['id_usuario'] ?? 0);
        $nueva = (string)($data['password_nueva'] ?? '');
        $confirmar = (string)($data['password_confirmar'] ?? '');

        if ($idUsuario <= 0) responder(['error' => 'Recepcionista inválido.'], 422);
        if (strlen($nueva) < 6) responder(['error' => 'La contraseña debe tener al menos 6 caracteres.'], 422);
        if ($nueva !== $confirmar) responder(['error' => 'Las contraseñas no coinciden.'], 422);

        $stmt = $db->prepare("UPDATE usuarios
                              SET password_hash = ?
                              WHERE id_usuario = ? AND id_hotel = ? AND id_rol = 3");
        $stmt->execute([password_hash($nueva, PASSWORD_DEFAULT), $idUsuario, $idHotel]);
        if ($stmt->rowCount() === 0) {
            responder(['error' => 'El recepcionista no existe o no pertenece al hotel.'], 404);
        }
        responder(['ok' => true, 'mensaje' => 'Contraseña del recepcionista actualizada.']);
    }

    if ($accion === 'cambiar_estado') {
        $idUsuario = (int)($data['id_usuario'] ?? 0);
        $estado = strtoupper(trim((string)($data['estado'] ?? '')));
        if ($idUsuario <= 0 || !in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
            responder(['error' => 'Datos inválidos para actualizar el estado.'], 422);
        }

        $stmt = $db->prepare("UPDATE usuarios SET estado = ?
                              WHERE id_usuario = ? AND id_hotel = ? AND id_rol = 3");
        $stmt->execute([$estado, $idUsuario, $idHotel]);
        if ($stmt->rowCount() === 0) responder(['error' => 'No se pudo actualizar el recepcionista.'], 404);
        responder(['ok' => true, 'mensaje' => 'Estado actualizado correctamente.']);
    }

    responder(['error' => 'Acción no reconocida.'], 400);
} catch (Throwable $e) {
    responder(['error' => 'Error interno al gestionar usuarios.'], 500);
}
