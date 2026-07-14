<?php
header('Content-Type: application/json; charset=UTF-8');
require_once '../config/Database.php';
require_once '../config/auth.php';

function json_out($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    if (!esta_logueado()) json_out(['error' => 'No autorizado'], 401);
    $usuario = $_SESSION['usuario'] ?? [];
    $idHotel = (int)($usuario['id_hotel'] ?? 0);
    $rol = $usuario['rol'] ?? '';
    if (!$idHotel && $rol !== 'superadmin') json_out(['error' => 'Usuario sin hotel asignado'], 403);

    $db = (new Database())->connect();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $dni = trim((string)($_GET['dni'] ?? ''));
        if ($dni === '') json_out(['error' => 'DNI requerido'], 400);
        $stmt = $db->prepare("SELECT id_no_grato, nombres, apellidos, dni, motivo, estado, fecha_registro
                              FROM clientes_no_gratos
                              WHERE id_hotel = ? AND dni = ? AND estado = 'ACTIVO'
                              LIMIT 1");
        $stmt->execute([$idHotel, $dni]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        json_out(['encontrado' => (bool)$row, 'cliente' => $row ?: null]);
    }

    json_out(['error' => 'Método no permitido'], 405);
} catch (Throwable $e) {
    if (isset($_GET['debug']) && $_GET['debug'] == '1') json_out(['error' => $e->getMessage(), 'line' => $e->getLine()], 500);
    json_out(['error' => 'Error interno al consultar clientes no gratos.'], 500);
}
