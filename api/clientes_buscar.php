<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once '../config/Database.php';
require_once '../config/auth.php';

function out_json($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!esta_logueado()) {
    out_json(['error' => 'No autorizado'], 401);
}

try {
    $dni = preg_replace('/\D+/', '', (string)($_GET['dni'] ?? ''));
    if ($dni === '' || strlen($dni) < 8) {
        out_json(['encontrado' => false, 'cliente' => null]);
    }

    $idHotel = id_hotel_actual();
    if ($idHotel <= 0) {
        out_json(['error' => 'Hotel no identificado'], 400);
    }

    $db = (new Database())->connect();
    $stmt = $db->prepare("SELECT id_cliente, nombres, apellidos, documento_identidad AS dni, telefono, email
                          FROM clientes
                          WHERE id_hotel = ? AND documento_identidad = ?
                          LIMIT 1");
    $stmt->execute([$idHotel, $dni]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        out_json(['encontrado' => false, 'cliente' => null]);
    }

    out_json([
        'encontrado' => true,
        'cliente' => [
            'id_cliente' => (int)$cliente['id_cliente'],
            'dni' => $cliente['dni'],
            'nombres' => $cliente['nombres'],
            'apellidos' => $cliente['apellidos'],
            'telefono' => $cliente['telefono'],
            'email' => $cliente['email']
        ]
    ]);
} catch (Throwable $e) {
    out_json(['error' => 'Error interno al buscar cliente.'], 500);
}
