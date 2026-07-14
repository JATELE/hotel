<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/auth.php';
require_once '../controllers/ReservaController.php';

try {
    $controller = new ReservaController();
    $metodo = $_SERVER['REQUEST_METHOD'];

    switch($metodo) {
        case 'POST':
            // Público para iframe/webreservas; si hay sesión, guarda usuario recepcionista.
            $controller->crear();
            break;
        case 'GET':
            requerir_login_api(['superadmin', 'admin_hotel', 'recepcionista', 'cajero', 'admin']);
            $controller->listar();
            break;
        case 'PUT':
            requerir_login_api(['admin_hotel', 'recepcionista', 'admin']);
            if(isset($_GET['id']) && isset($_GET['action'])) {
                $controller->actualizar($_GET['id'], $_GET['action']);
            } else {
                http_response_code(400);
                echo json_encode(["error" => "ID o acción no proporcionados"]);
            }
            break;
        case 'DELETE':
            requerir_login_api(['superadmin', 'admin_hotel', 'admin']);
            if(isset($_GET['id'])) {
                $controller->eliminar($_GET['id']);
            } else {
                http_response_code(400);
                echo json_encode(["error" => "ID no proporcionado"]);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(["error" => "Método HTTP no soportado"]);
            break;
    }
} catch (Throwable $e) {
    error_log('API reservas: ' . $e->getMessage());
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
    }
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno al guardar la reserva: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
