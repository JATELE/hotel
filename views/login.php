<?php
require_once '../config/Database.php';
require_once '../config/auth.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (esta_logueado()) {
    $rol = $_SESSION['usuario']['rol'] ?? '';
    if ($rol === 'superadmin') {
        header('Location: superadmin.php');
    } else {
        header('Location: recepcion.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $error = 'Ingresa tu usuario y contraseña.';
    } else {
        $database = new Database();
        $db = $database->connect();

        if ($db) {
            $sql = "SELECT u.id_usuario, u.id_hotel, u.usuario, u.password_hash, u.nombres, u.apellidos, u.estado,
                           r.nombre_rol, h.nombre_comercial, h.slug, h.gestion_por_habitacion, h.estado AS estado_hotel, h.fecha_fin_plan
                    FROM usuarios u
                    INNER JOIN roles r ON r.id_rol = u.id_rol
                    LEFT JOIN hoteles h ON h.id_hotel = u.id_hotel
                    WHERE u.usuario = :usuario OR u.email = :usuario
                    LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['estado'] === 'ACTIVO' && password_verify($password, $row['password_hash'])) {
                if ($row['nombre_rol'] !== 'superadmin') {
                    if ($row['estado_hotel'] !== 'ACTIVO' || strtotime($row['fecha_fin_plan']) < strtotime(date('Y-m-d'))) {
                        $error = 'La cuenta del hotel está vencida o suspendida. Contacta al superadmin.';
                    } else {
                        $_SESSION['usuario'] = [
                            'id_usuario' => (int)$row['id_usuario'],
                            'id_hotel' => (int)$row['id_hotel'],
                            'hotel' => $row['nombre_comercial'],
                            'hotel_slug' => $row['slug'] ?? '',
                            'gestion_por_habitacion' => (int)($row['gestion_por_habitacion'] ?? 0),
                            'usuario' => $row['usuario'],
                            'nombres' => $row['nombres'],
                            'apellidos' => $row['apellidos'],
                            'rol' => $row['nombre_rol']
                        ];
                        $stmtSesion = $db->prepare("INSERT INTO usuario_sesiones (id_usuario, id_hotel, ip, user_agent) VALUES (?, ?, ?, ?)");
                        $stmtSesion->execute([(int)$row['id_usuario'], (int)$row['id_hotel'], $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
                        $_SESSION['id_sesion'] = $db->lastInsertId();
                        header('Location: recepcion.php');
                        exit;
                    }
                } else {
                    $_SESSION['usuario'] = [
                        'id_usuario' => (int)$row['id_usuario'],
                        'id_hotel' => null,
                        'hotel' => 'Sistema CRM Hoteles',
                        'usuario' => $row['usuario'],
                        'nombres' => $row['nombres'],
                        'apellidos' => $row['apellidos'],
                        'rol' => $row['nombre_rol']
                    ];
                    $stmtSesion = $db->prepare("INSERT INTO usuario_sesiones (id_usuario, id_hotel, ip, user_agent) VALUES (?, NULL, ?, ?)");
                    $stmtSesion->execute([(int)$row['id_usuario'], $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
                    $_SESSION['id_sesion'] = $db->lastInsertId();
                    header('Location: superadmin.php');
                    exit;
                }
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } else {
            $error = 'No se pudo conectar a la base de datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CRM Hoteles</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a252f, #34495e 55%, #d4af37);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(0,0,0,.28);
            overflow: hidden;
            animation: show .35s ease;
        }
        @keyframes show { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .login-header {
            background: #1a252f;
            color: #fff;
            padding: 32px 28px;
            text-align: center;
        }
        .login-header i { color: #d4af37; font-size: 42px; margin-bottom: 12px; }
        .login-header h1 { margin: 0; font-size: 24px; }
        .login-header p { margin: 8px 0 0; opacity: .8; font-size: 14px; }
        form { padding: 30px 28px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #1a252f; font-size: 14px; }
        .input-group { margin-bottom: 18px; }
        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #8a94a3; }
        input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            border: 1px solid #dfe4ea;
            border-radius: 14px;
            outline: none;
            font: inherit;
            transition: .2s;
        }
        input:focus { border-color: #d4af37; box-shadow: 0 0 0 4px rgba(212,175,55,.15); }
        .error {
            background: #fdecec;
            color: #b42318;
            border: 1px solid #f8c8c8;
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        button {
            width: 100%;
            border: none;
            border-radius: 14px;
            background: #d4af37;
            color: #1a252f;
            padding: 14px 18px;
            font-weight: 800;
            cursor: pointer;
            font-size: 15px;
            transition: .2s;
        }
        button:hover { background: #b5952f; transform: translateY(-1px); }
        .demo {
            margin-top: 18px;
            padding: 14px;
            border-radius: 14px;
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            line-height: 1.55;
        }
        .back { display:block; text-align:center; margin-top: 18px; color:#64748b; text-decoration:none; font-size:14px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fa-solid fa-hotel"></i>
            <h1>CRM Hoteles</h1>
            <p>Acceso al módulo de recepción</p>
        </div>
        <form method="POST" autocomplete="off">
            <?php if ($error): ?>
                <div class="error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <div class="input-group">
                <label for="usuario">Usuario o correo</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-user"></i>
                    <input id="usuario" name="usuario" type="text" placeholder="admin" required>
                </div>
            </div>
            <div class="input-group">
                <label for="password">Contraseña</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input id="password" name="password" type="password" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit"><i class="fa-solid fa-right-to-bracket"></i> Ingresar</button>
            <a class="back" href="landing.html">Volver al landing</a>
        </form>
    </div>
</body>
</html>
