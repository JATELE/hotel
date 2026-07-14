<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function usuario_actual() {
    return $_SESSION['usuario'] ?? null;
}

function esta_logueado() {
    return isset($_SESSION['usuario']);
}

function normalizar_rol($rol) {
    $rol = strtolower(trim((string)$rol));
    $aliases = [
        'recepcion' => 'recepcionista',
        'recepción' => 'recepcionista',
        'admin' => 'admin_hotel'
    ];
    return $aliases[$rol] ?? $rol;
}

function tiene_rol($rolesPermitidos) {
    if (!esta_logueado()) return false;
    if (is_string($rolesPermitidos)) $rolesPermitidos = [$rolesPermitidos];
    $rolActual = normalizar_rol($_SESSION['usuario']['rol'] ?? '');
    $permitidos = array_map('normalizar_rol', $rolesPermitidos);
    return in_array($rolActual, $permitidos, true);
}

function id_hotel_actual() {
    return $_SESSION['usuario']['id_hotel'] ?? null;
}

function es_superadmin() {
    return esta_logueado() && ($_SESSION['usuario']['rol'] ?? '') === 'superadmin';
}

function requerir_login($rolesPermitidos = []) {
    if (!esta_logueado()) {
        header('Location: login.php');
        exit;
    }

    if (!empty($rolesPermitidos) && !tiene_rol($rolesPermitidos)) {
        http_response_code(403);
        echo 'Acceso denegado. No tienes permisos para ver esta sección.';
        exit;
    }
}

function requerir_login_api($rolesPermitidos = []) {
    header('Content-Type: application/json; charset=UTF-8');
    if (!esta_logueado()) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado. Inicia sesión nuevamente.']);
        exit;
    }

    if (!empty($rolesPermitidos) && !tiene_rol($rolesPermitidos)) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para realizar esta acción.']);
        exit;
    }
}

function cerrar_sesion_db($db) {
    if (!empty($_SESSION['id_sesion'])) {
        $stmt = $db->prepare("UPDATE usuario_sesiones SET fecha_logout = NOW(), estado = 'CERRADA' WHERE id_sesion = ? AND estado = 'ABIERTA'");
        $stmt->execute([$_SESSION['id_sesion']]);
    }
}
?>
