<?php
require_once '../config/auth.php';
require_once '../config/Database.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (!esta_logueado()) {
    header('Location: login.php');
    exit;
}

$usuarioActual = $_SESSION['usuario'];
$idHotelActual = (int)($usuarioActual['id_hotel'] ?? 0);
$rolActual = $usuarioActual['rol'] ?? '';
$hotelSlugActual = $usuarioActual['hotel_slug'] ?? '';
$webReservasUrl = 'webreservas.html' . ($hotelSlugActual !== '' ? '?hotel=' . urlencode($hotelSlugActual) : ($idHotelActual > 0 ? '?id_hotel=' . $idHotelActual : ''));
$categoriasHotel = [];
$gestionPorHabitacion = false;
try {
    if ($idHotelActual > 0) {
        $dbCategorias = (new Database())->connect();
        $stmtGestion = $dbCategorias->prepare("SELECT gestion_por_habitacion FROM hoteles WHERE id_hotel = ? LIMIT 1");
        $stmtGestion->execute([$idHotelActual]);
        $gestionPorHabitacion = ((int)$stmtGestion->fetchColumn() === 1);
        $stmtCategorias = $dbCategorias->prepare("SELECT id_categoria, nombre, slug FROM categorias WHERE id_hotel = ? AND estado = 'ACTIVO' ORDER BY id_categoria ASC");
        $stmtCategorias->execute([$idHotelActual]);
        $categoriasHotel = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $categoriasHotel = [];
    $gestionPorHabitacion = false;
}

if (!in_array(($usuarioActual['rol'] ?? ''), ['admin_hotel', 'admin', 'recepcionista'], true)) {
    if (($usuarioActual['rol'] ?? '') === 'superadmin') {
        header('Location: superadmin.php');
    } else {
        http_response_code(403);
        echo 'Acceso denegado. No tienes permisos para ver recepción.';
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recepción | CRM Hoteles</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #1a252f;
            --dorado: #d4af37;
            --dorado-hover: #b5952f;
            --secondary: #34495e;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0 0 90px 0;
            color: #333;
            overflow-x: hidden;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes epicSlideIn {
            0% {
                opacity: 0;
                transform: translateY(15px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .navbar {
            background-color: var(--primary);
            color: white;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: var(--dorado);
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
        }

        .hero {
            background: linear-gradient(135deg, #2c3e50, var(--primary));
            color: white;
            padding: 45px 20px 65px 20px;
            text-align: center;
            animation: fadeInDown 0.6s ease-out;
        }

        .hero h1 {
            margin: 0;
            font-size: 28px;
        }

        .container {
            max-width: 1450px;
            margin: -40px auto 40px auto;
            padding: 0 20px;
            animation: fadeInUp 0.8s ease-out;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background-color: #ffffff;
            padding: 28px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            border-left: 5px solid var(--dorado);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
            transition: transform 0.3s, border-color 0.3s, background 0.3s;
            cursor: pointer;
            border: 1px solid rgba(209, 213, 219, 0.7);
            text-align: center;
        }

        .stat-card-button {
            width: 100%;
            display: block;
            border: none;
            background-color: #ffffff;
            color: var(--primary);
            padding: 20px;
            text-align: center;
        }

        .stat-top-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .stat-label {
            margin: 0;
            font-size: 12px;
            color: #777;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            background-color: #fff4d8;
        }

        .stat-card.active {
            background: #fff7df;
            border-color: rgba(212, 175, 55, 0.35);
            box-shadow: 0 12px 20px rgba(212, 175, 55, 0.18);
            transform: translateY(-2px);
        }

        .stat-icon {
            font-size: 32px;
            color: var(--dorado);
        }

        .stat-info h3 {
            margin: 0;
            font-size: 26px;
            color: var(--primary);
        }

        .stat-info p {
            margin: 0;
            font-size: 12px;
            color: #777;
            font-weight: 600;
            text-transform: uppercase;
        }

        .main-panel {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .controls-top {
            padding: 20px 25px;
            background-color: #fcfcfc;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .toggle-btn {
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-family: 'Poppins';
            cursor: pointer;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .mode-pendientes {
            background: linear-gradient(135deg, #f1c40f, #f39c12);
            color: white;
        }

        .mode-pendientes:hover {
            box-shadow: 0 6px 20px rgba(243, 156, 18, 0.4);
            transform: translateY(-2px);
        }

        .mode-confirmadas {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }

        .mode-confirmadas:hover {
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
            transform: translateY(-2px);
        }

        .search-bar {
            display: flex;
            gap: 15px;
            flex-grow: 1;
            justify-content: flex-end;
        }

        .search-bar select,
        .search-bar input {
            padding: 12px 18px;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            outline: none;
            transition: 0.3s;
        }

        .search-bar input {
            width: 320px;
        }

        .search-bar select:focus,
        .search-bar input:focus {
            border-color: var(--dorado);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
        }

        .date-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: flex-end;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 200px;
        }

        .filter-group label {
            font-size: 12px;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group select {
            padding: 12px 16px;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            outline: none;
            font-family: 'Poppins', sans-serif;
            background: #fff;
            transition: border-color 0.3s;
        }

        .filter-group select:focus {
            border-color: var(--dorado);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.12);
        }

        .table-container {
            min-height: 400px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 16px 15px;
            text-align: left;
            border-bottom: 1px solid #f5f5f5;
            font-size: 13px;
        }

        th {
            background-color: #fafbfc;
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        tr {
            transition: all 0.2s;
        }

        tr:hover {
            background-color: #fdfaf0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transform: scale(1.005);
            z-index: 10;
            position: relative;
            border-radius: 6px;
        }


        .canal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
        }

        .canal-web { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
        .canal-whatsapp { background:#ecfdf5; color:#047857; border-color:#bbf7d0; }
        .canal-redes { background:#f5f3ff; color:#6d28d9; border-color:#ddd6fe; }
        .canal-llamada { background:#fff7ed; color:#c2410c; border-color:#fed7aa; }
        .canal-presencial { background:#fefce8; color:#a16207; border-color:#fde68a; }

        .col-notas {
            max-width: 130px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #7f8c8d;
            font-style: italic;
        }

        .btn-atender {
            background-color: var(--dorado);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-family: 'Poppins';
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 10px rgba(212, 175, 55, 0.2);
        }

        .btn-atender:hover {
            background-color: var(--dorado-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(212, 175, 55, 0.4);
        }

        .btn-icon-edit {
            background: #eff6ff;
            color: #3b82f6;
            border: 1px solid #dbeafe;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .btn-icon-edit:hover {
            transform: scale(1.15) rotate(10deg);
            background: #3b82f6;
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-icon-delete {
            background: #fef2f2;
            color: #ef4444;
            border: 1px solid #fecaca;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .btn-icon-delete:hover {
            transform: scale(1.15) rotate(-10deg);
            background: #ef4444;
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        .paginacion-container {
            padding: 20px;
            background: #fafbfc;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .paginacion-info {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        .paginacion-controles {
            display: flex;
            gap: 10px;
        }

        .btn-pagina {
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: white;
            color: var(--primary);
            font-family: 'Poppins';
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-pagina:hover:not(:disabled) {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .btn-pagina:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.5);
            z-index: 99998;
            backdrop-filter: blur(4px);
            transition: opacity 0.2s ease;
        }

        .modal-content {
            display: none;
            position: fixed;
            top: 22px;
            left: 50%;
            transform: translateX(-50%) scale(0.98);
            background: white;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            z-index: 99999;
            width: min(820px, calc(100vw - 28px));
            max-height: calc(100vh - 44px);
            overflow-y: auto;
            opacity: 0;
            transition: opacity 0.2s ease-out, transform 0.2s ease-out;
        }

        .modal-content.show {
            transform: translateX(-50%) scale(1);
            opacity: 1;
        }

        .modal-content h2 {
            margin-top: 0;
            color: var(--primary);
            font-weight: 600;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 12px;
            font-size: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-close-icon {
            color: #94a3b8;
            cursor: pointer;
            transition: 0.3s;
        }

        .modal-close-icon:hover {
            color: var(--danger);
            transform: rotate(90deg);
        }

        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .input-group {
            margin-bottom: 10px;
        }

        .input-group.full-width {
            grid-column: span 2;
        }

        .input-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #475569;
            font-size: 12px;
        }

        .input-group input,
        .input-group select,
        .input-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-family: 'Poppins';
            font-size: 13px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.3s;
            background: #f8fafc;
        }

        .input-group input:focus,
        .input-group select:focus,
        .input-group textarea:focus {
            border-color: var(--dorado);
            background: #fff;
        }

        .input-group input:disabled,
        .input-group select:disabled {
            background: #e2e8f0;
            cursor: not-allowed;
            color: #64748b;
        }

        .input-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .input-group textarea:focus {
            border-color: var(--dorado) !important;
        }

        .error-msg {
            color: var(--danger);
            font-size: 11px;
            margin-top: 5px;
            display: none;
            font-weight: 500;
        }

        .cliente-no-grato-alerta {
            display: none;
            margin-top: 8px;
            padding: 9px 11px;
            border-radius: 12px;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            font-size: 11.5px;
            font-weight: 700;
            line-height: 1.3;
            max-height: 74px;
            overflow-y: auto;
        }

        .habitaciones-alerta{display:none;margin:0 0 10px;padding:10px 12px;border-radius:10px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:12px;font-weight:700;line-height:1.45}
        .habitaciones-check-list{display:grid;gap:10px;max-height:310px;overflow-y:auto;padding:4px 2px}
        .habitacion-cargando{padding:14px;border:1px dashed #cbd5e1;border-radius:10px;color:#64748b;text-align:center;font-size:12px}
        .habitacion-check-card{display:grid;grid-template-columns:auto 1fr 150px;gap:12px;align-items:center;padding:12px;border:1px solid #dbe4ee;border-radius:12px;background:#f8fafc;transition:.2s}
        .habitacion-check-card:has(input[type=checkbox]:checked){border-color:#16a06a;background:#ecfdf5;box-shadow:0 0 0 2px rgba(22,160,106,.08)}
        .habitacion-check-card input[type=checkbox]{width:18px;height:18px;accent-color:#16a06a}
        .habitacion-info strong{display:block;color:#162330;font-size:13px}.habitacion-info small{display:block;color:#64748b;margin-top:3px;line-height:1.35}
        .habitacion-precio label{display:block;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:4px}
        .habitacion-precio input{padding:9px 10px!important;text-align:right;font-weight:800;background:#fff!important}
        .habitacion-motivo{grid-column:2/4;display:none}.habitacion-motivo.visible{display:block}.habitacion-motivo input{background:#fff!important}
        .resumen-precios-habitaciones{display:none;margin-top:12px;padding:12px;border-radius:12px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;font-size:12px;line-height:1.55}
        .resumen-precios-habitaciones strong{font-size:14px}
        @media(max-width:700px){.habitacion-check-card{grid-template-columns:auto 1fr}.habitacion-precio{grid-column:2}.habitacion-motivo{grid-column:1/3}}

        @media(max-width: 700px){
            .modal-content{top: 10px; width: calc(100vw - 16px); max-height: calc(100vh - 20px); padding: 22px;}
            .modal-grid{grid-template-columns: 1fr;}
            .input-group.full-width{grid-column: span 1;}
        }

        .btn-modal {
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            border: none;
            width: 48%;
            font-family: 'Poppins';
            font-size: 14px;
            transition: 0.3s;
        }

        .btn-guardar {
            background: var(--success);
            color: white;
        }

        .btn-guardar:hover {
            background: #219653;
            transform: translateY(-2px);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .btn-desbloquear {
            background: #1e40af;
            color: white;
            border: none;
            padding: 10px 14px;
            border-radius: 8px;
            width: auto;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }

        .btn-desbloquear:hover {
            background: #1d4ed8;
        }

        /* ESTILOS NUEVOS: Notificaciones Animadas (Toast) */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 100500;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background: white;
            color: #333;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            border-left: 5px solid;
            transform: translateX(120%);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast.success {
            border-left-color: var(--success);
        }

        .toast.error {
            border-left-color: var(--danger);
        }

        .toast.success i {
            color: var(--success);
            font-size: 20px;
        }

        .toast.error i {
            color: var(--danger);
            font-size: 20px;
        }



        .btn-whatsapp {
            background: #25d366;
            color: white;
            border: none;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 15px;
        }

        .btn-whatsapp:hover {
            transform: scale(1.12);
            background: #1ebe5d;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.35);
        }


        /* Tabla equilibrada: mantiene columnas legibles y evita botones cortados */
        .table-container {
            overflow-x: auto;
            overflow-y: visible;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f8fafc;
        }

        .table-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 999px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .table-container table {
            min-width: 1320px;
        }

        th,
        td {
            padding: 14px 12px;
            vertical-align: middle;
        }

        .btn-atender {
            white-space: nowrap;
            min-width: 110px;
            justify-content: center;
            padding: 9px 14px;
        }

        .acciones-cell,
        table th:last-child {
            min-width: 135px;
            width: 135px;
            white-space: nowrap;
            overflow: visible;
            position: sticky;
            right: 0;
            z-index: 5;
            background: #fff;
            box-shadow: -8px 0 16px rgba(15, 23, 42, 0.05);
        }

        table th:last-child {
            background: #fafbfc;
            z-index: 6;
        }

        tr:hover .acciones-cell {
            background: #fdfaf0;
        }

        .acciones-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: nowrap;
            min-width: max-content;
        }

        .acciones-wrap .btn-whatsapp,
        .acciones-wrap .btn-icon-edit,
        .acciones-wrap .btn-icon-delete {
            flex: 0 0 34px;
        }

        @media (max-width: 1200px) {
            .table-container table {
                min-width: 1280px;
            }
        }

        .report-panel {
            display: none;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-bottom: 25px;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid #edf2f7;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .report-header h2 {
            margin: 0;
            color: var(--primary);
            font-size: 22px;
        }

        .report-header p {
            margin: 4px 0 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .report-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 5px solid var(--dorado);
            border-radius: 12px;
            padding: 18px;
        }

        .report-card span {
            display: block;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .report-card strong {
            color: var(--primary);
            font-size: 24px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            align-items: stretch;
        }

        .chart-box {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            overflow: hidden;
        }

        .chart-box h3 {
            margin: 0 0 14px 0;
            color: var(--primary);
            font-size: 16px;
        }

        .chart-canvas-wrap {
            position: relative;
            width: 100%;
            height: 300px;
            max-height: 300px;
        }

        .chart-canvas-wrap canvas {
            display: block;
            width: 100% !important;
            height: 300px !important;
            max-height: 300px !important;
        }

        @media (max-width: 900px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }

            .chart-canvas-wrap {
                height: 260px;
                max-height: 260px;
            }

            .chart-canvas-wrap canvas {
                height: 260px !important;
                max-height: 260px !important;
            }
        }


        .whatsapp-modal textarea{width:100%;min-height:210px;border:1px solid #cbd5e1;border-radius:12px;padding:14px;font-family:'Poppins';font-size:14px;resize:vertical;background:#f8fafc;outline:none}.whatsapp-modal textarea:focus{border-color:#25d366;background:#fff}.quick-msg{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 14px}.quick-msg button{border:1px solid #bbf7d0;background:#ecfdf5;color:#047857;border-radius:999px;padding:8px 12px;font-weight:800;cursor:pointer;font-family:'Poppins';font-size:12px}.quick-msg button:hover{background:#dcfce7}.btn-whatsapp-send{background:#25d366;color:white;border:none;border-radius:10px;padding:12px 16px;font-family:'Poppins';font-weight:900;cursor:pointer}.btn-whatsapp-send:hover{background:#1ebe5d}

        footer.feed-footer {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            background: var(--primary);
            color: #f4f7f6;
            text-align: center;
            padding: 14px 20px;
            font-size: 14px;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.12);
            z-index: 100;
        }

        .app-header{
            position:sticky;top:0;z-index:1000;background:rgba(22,35,48,.96);backdrop-filter:blur(12px);
            color:white;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;gap:18px;
            box-shadow:0 12px 30px rgba(2,8,23,.18);border-bottom:1px solid rgba(255,255,255,.08)
        }
        .app-brand{display:flex;align-items:center;gap:12px;font-weight:800;letter-spacing:.2px}
        .app-brand i{color:var(--dorado);font-size:22px}.app-brand small{display:block;color:#b9c6d2;font-size:12px;font-weight:600;margin-top:2px}
        .app-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap}.app-pill{background:#223244;color:#dbe7f2;text-decoration:none;border:1px solid rgba(255,255,255,.08);padding:9px 12px;border-radius:999px;font-size:12px;font-weight:700}.app-pill-static{display:inline-flex;align-items:center;gap:5px;cursor:default;user-select:none}
        .app-btn{display:inline-flex;align-items:center;gap:8px;border-radius:12px;text-decoration:none;font-weight:800;font-size:13px;padding:10px 14px;transition:.2s ease;box-shadow:0 6px 18px rgba(0,0,0,.12)}
        .app-btn:hover{transform:translateY(-1px)}.app-btn.light{background:#fff;color:#162330}.app-btn.green{background:#16a06a;color:#fff}.app-btn.gold{background:var(--dorado);color:#162330}.app-btn.outline{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.22)}
        @media(max-width:900px){.app-header{align-items:flex-start;flex-direction:column}.app-actions{justify-content:flex-start}.hero{padding-top:28px}}


        .modal-secundario{width:min(900px,calc(100vw - 28px));}
        .detalle-lista,.enlaces-lista{display:grid;gap:12px;}
        .detalle-item,.enlace-item{border:1px solid #e2e8f0;border-radius:14px;padding:14px;background:#f8fafc;}
        .detalle-top,.enlace-top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;}
        .detalle-titulo,.enlace-titulo{font-weight:800;color:#1e293b;}
        .detalle-meta{font-size:12px;color:#64748b;margin-top:4px;}
        .precio-original-tachado{text-decoration:line-through;color:#94a3b8;margin-right:8px;}
        .precio-aplicado{font-weight:800;color:#059669;}
        .ajuste-badge{display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:6px 9px;border-radius:999px;background:#fff7ed;color:#c2410c;font-size:11px;font-weight:800;}
        .detalle-resumen{margin-top:14px;padding:14px;border-radius:14px;background:#ecfdf5;border:1px solid #bbf7d0;display:grid;grid-template-columns:repeat(3,1fr);gap:10px;}
        .detalle-resumen div{font-size:12px;color:#475569}.detalle-resumen strong{display:block;font-size:18px;color:#065f46;margin-top:4px;}
        .btn-detalle{border:1px solid #dbeafe;background:#eff6ff;color:#2563eb;border-radius:999px;padding:6px 9px;font-size:11px;font-weight:800;cursor:pointer;margin-top:5px;}
        .enlace-url{font-size:12px;color:#475569;word-break:break-all;margin:8px 0;}
        .enlace-acciones{display:flex;gap:8px;flex-wrap:wrap;}
        .mini-btn{border:0;border-radius:9px;padding:8px 11px;font-family:Poppins;font-weight:800;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:12px;}
        .mini-btn.abrir{background:#2563eb;color:white}.mini-btn.copiar{background:#e2e8f0;color:#1e293b;}
        @media(max-width:700px){.detalle-resumen{grid-template-columns:1fr}.modal-secundario{width:calc(100vw - 16px)}}

    </style>
</head>

<body>
    <header class="app-header">
        <div class="app-brand">
            <i class="fa-solid fa-hotel"></i>
            <div>
                CRM Hoteles
                <small><?= htmlspecialchars($usuarioActual['hotel'] ?? 'Hotel sin asignar') ?> · <?= htmlspecialchars($rolActual) ?></small>
            </div>
        </div>
        <div class="app-actions">
            <?php if(in_array($rolActual, ['admin_hotel','admin'], true)): ?>
                <a class="app-pill" href="perfil.php" title="Ver mi perfil"><i class="fa-solid fa-user-circle"></i> <?= htmlspecialchars($usuarioActual['nombres'] ?? 'Usuario') ?></a>
            <?php else: ?>
                <span class="app-pill app-pill-static"><i class="fa-solid fa-user-circle"></i> Recepción</span>
            <?php endif; ?>
            <?php if(in_array($rolActual, ['admin_hotel','admin'], true)): ?>
                <a class="app-btn light" href="habitaciones.php"><i class="fa-solid fa-bed"></i> Habitaciones</a>
                <a class="app-btn light" href="recepcionistas.php"><i class="fa-solid fa-users-gear"></i> Recepcionistas</a>
                <a class="app-btn light" href="reportes_recepcionistas.php"><i class="fa-solid fa-chart-column"></i> Reportes recepción</a>
                <a class="app-btn light" href="encuestas.php"><i class="fa-solid fa-square-poll-horizontal"></i> Crear encuestas</a>
                <a class="app-btn light" href="encuestas_resultados.php"><i class="fa-solid fa-chart-line"></i> Resultados encuestas</a>
            <?php endif; ?>
            <?php if(in_array($rolActual, ['admin_hotel','admin','recepcionista','recepcion'], true)): ?>
                <a class="app-btn light" href="clientes_no_gratos.php"><i class="fa-solid fa-user-slash"></i> Clientes no gratos</a>
                <a class="app-btn light" href="encuestas_enlaces.php"><i class="fa-solid fa-link"></i> Enlaces de encuestas</a>
            <?php endif; ?>
            <a class="app-btn light" href="operaciones_habitaciones.php"><i class="fa-solid fa-bell-concierge"></i> Operación hotelera</a>
            <button type="button" class="app-btn outline" onclick="abrirModalEnlacesCategorias()" style="border:none;cursor:pointer;font-family:Poppins;"><i class="fa-solid fa-link"></i> Enlaces de habitaciones</button>
            <a class="app-btn green" href="<?= htmlspecialchars($webReservasUrl) ?>" target="_blank"><i class="fa-solid fa-calendar-plus"></i> Registrar reserva visual</a>
            <a class="app-btn gold" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
        </div>
    </header>

    <div class="hero">
        <h1>Centro de Gestión de Reservas</h1>
        <p>Versión 2: gestión por roles, contacto por WhatsApp y reportes administrativos.</p>
    </div>

    <div class="container">
        <div class="stats-grid">
            <button class="stat-card stat-card-button active" data-vista="Pendiente" onclick="cambiarVista('Pendiente')"
                style="border-left-color: var(--warning);">
                <div class="stat-top-row">
                    <div class="stat-icon"><i class="fa-solid fa-bell" style="color: var(--warning);"></i></div>
                    <div class="stat-info">
                        <h3 id="stat-pendientes">0</h3>
                    </div>
                </div>
                <p class="stat-label">Por Atender</p>
            </button>
            <button class="stat-card stat-card-button" data-vista="Atendida" onclick="cambiarVista('Atendida')"
                style="border-left-color: #3b82f6;">
                <div class="stat-top-row">
                    <div class="stat-icon"><i class="fa-solid fa-check" style="color: #3b82f6;"></i></div>
                    <div class="stat-info">
                        <h3 id="stat-atendidas">0</h3>
                    </div>
                </div>
                <p class="stat-label">Atendido</p>
            </button>
            <button class="stat-card stat-card-button" data-vista="Confirmada" onclick="cambiarVista('Confirmada')"
                style="border-left-color: var(--success);">
                <div class="stat-top-row">
                    <div class="stat-icon"><i class="fa-solid fa-check-double" style="color: var(--success);"></i></div>
                    <div class="stat-info">
                        <h3 id="stat-confirmadas">0</h3>
                    </div>
                </div>
                <p class="stat-label">Confirmado</p>
            </button>
            <button class="stat-card stat-card-button" data-vista="Culminada" onclick="cambiarVista('Culminada')"
                style="border-left-color:#0f766e;">
                <div class="stat-top-row">
                    <div class="stat-icon"><i class="fa-solid fa-flag-checkered" style="color:#0f766e;"></i></div>
                    <div class="stat-info"><h3 id="stat-culminadas">0</h3></div>
                </div>
                <p class="stat-label">Check Out</p>
            </button>
            <?php if (in_array(($usuarioActual['rol'] ?? ''), ['admin_hotel','admin'], true)): ?>
            <button class="stat-card stat-card-button" data-vista="Cancelada" onclick="cambiarVista('Cancelada')"
                style="border-left-color: var(--danger);">
                <div class="stat-top-row">
                    <div class="stat-icon"><i class="fa-solid fa-ban" style="color: var(--danger);"></i></div>
                    <div class="stat-info">
                        <h3 id="stat-canceladas">0</h3>
                    </div>
                </div>
                <p class="stat-label">Cancelado</p>
            </button>
            <button class="stat-card stat-card-button" data-vista="Todas" onclick="cambiarVista('Todas')"
                style="border-left-color: var(--secondary);">
                <div class="stat-top-row">
                    <div class="stat-icon"><i class="fa-solid fa-earth-americas"></i></div>
                    <div class="stat-info">
                        <h3 id="stat-total">0</h3>
                    </div>
                </div>
                <p class="stat-label">Total</p>
            </button>
            <button class="stat-card stat-card-button" data-vista="Reportes" onclick="cambiarVista('Reportes')"
                style="border-left-color: #8b5cf6;">
                <div class="stat-top-row">
                    <div class="stat-icon"><i class="fa-solid fa-chart-line" style="color:#8b5cf6;"></i></div>
                    <div class="stat-info">
                        <h3 id="stat-reportes">S/ 0.00</h3>
                    </div>
                </div>
                <p class="stat-label">Reportes</p>
            </button>
            <?php endif; ?>
        </div>

        <div class="date-filter-bar">
            <div class="filter-group">
                <label>Filtrar por fecha</label>
                <select id="filtro-fecha-tipo" onchange="aplicarFiltrosYPaginar()">
                    <option value="fecha_registro">Fecha de registro</option>
                    <option value="fecha_checkin">Fecha de Checkin</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Rango</label>
                <select id="filtro-fecha-rango" onchange="actualizarControlesFecha(); aplicarFiltrosYPaginar()">
                    <option value="">Todos</option>
                    <option value="hoy">Hoy</option>
                    <option value="ultimos7dias">Últimos 7 días</option>
                    <option value="mes">Mes específico</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Mes</label>
                <select id="filtro-mes" onchange="aplicarFiltrosYPaginar()"></select>
            </div>
            <div class="filter-group">
                <label>Año</label>
                <select id="filtro-ano" onchange="aplicarFiltrosYPaginar()"></select>
            </div>
        </div>

        <?php if (in_array(($usuarioActual['rol'] ?? ''), ['admin_hotel','admin'], true)): ?>
        <div id="panel-reportes" class="report-panel">
            <div class="report-header">
                <div>
                    <h2><i class="fa-solid fa-chart-pie"></i> Reportes y Gráficas</h2>
                    <p>Los ingresos se calculan con reservas Confirmadas y Check Out, usando los mismos filtros de fecha y búsqueda.</p>
                </div>
                <button class="btn-pagina" onclick="actualizarReportes(reservasFiltradasActuales)"><i class="fa-solid fa-rotate"></i> Actualizar</button>
            </div>
            <div class="report-grid">
                <div class="report-card">
                    <span>Ganancia filtrada</span>
                    <strong id="reporte-ingresos">S/ 0.00</strong>
                </div>
                <div class="report-card">
                    <span>Ventas realizadas</span>
                    <strong id="reporte-confirmadas">0</strong>
                </div>
                <div class="report-card">
                    <span>Promedio por reserva</span>
                    <strong id="reporte-promedio">S/ 0.00</strong>
                </div>
                <div class="report-card">
                    <span>Categoría más solicitada</span>
                    <strong id="reporte-categoria-top">-</strong>
                </div>
                <div class="report-card">
                    <span>Canal con más reservas</span>
                    <strong id="reporte-canal-top">-</strong>
                </div>
            </div>
            <div class="charts-grid">
                <div class="chart-box">
                    <h3>Ingresos confirmados por día</h3>
                    <div class="chart-canvas-wrap">
                        <canvas id="chartIngresos"></canvas>
                    </div>
                </div>
                <div class="chart-box">
                    <h3>Reservas por categoría</h3>
                    <div class="chart-canvas-wrap">
                        <canvas id="chartCategorias"></canvas>
                    </div>
                </div>
                <div class="chart-box">
                    <h3>Reservas por canal</h3>
                    <div class="chart-canvas-wrap">
                        <canvas id="chartCanales"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="main-panel">
            <div class="controls-top">
                <div class="search-bar">
                    <select id="filtro-tipo" onchange="aplicarFiltrosYPaginar()">
                        <option value="cliente">Cliente</option>
                        <option value="dni">DNI</option>
                        <option value="fecha_registro">Fecha Consulta</option>
                        <option value="fecha_checkin">Check-in</option>
                        <option value="canal_reserva">Canal de reserva</option>
                    </select>
                    <input type="text" id="filtro-input" placeholder="Buscar registros..."
                        onkeyup="aplicarFiltrosYPaginar()">
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Registro</th>
                            <th>Reserva</th>
                            <th>Categoría</th>
                            <th>Habitación</th>
                            <th>Cliente</th>
                            <th>DNI</th>
                            <th>Número móvil</th>
                            <th>Canal</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th style="text-align: center;">Noches</th>
                            <th>Importe Total</th>
                            <th>Notas</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-reservas"></tbody>
                </table>
            </div>

            <div class="paginacion-container">
                <div class="paginacion-info" id="info-paginacion">Mostrando 0 al 0 de 0 reservas</div>
                <div class="paginacion-controles">
                    <button class="btn-pagina" id="btn-prev" onclick="cambiarPagina(-1)"><i
                            class="fa-solid fa-chevron-left"></i> Anterior</button>
                    <button class="btn-pagina" id="btn-next" onclick="cambiarPagina(1)">Siguiente <i
                            class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-overlay" class="modal-overlay"></div>
    <div id="modal-atencion" class="modal-content" tabindex="0">
        <div class="modal-header">
            <h2 id="modal-titulo">Atender <i class="fa-solid fa-xmark modal-close-icon" onclick="cerrarModal()"></i>
            </h2>
            <button id="btn-desbloquear-modal" type="button" class="btn-modal btn-desbloquear"
                onclick="solicitarDesbloqueoModal()">
                <i class="fa-solid fa-pen-to-square"></i> Editar campos
            </button>
        </div>

        <div class="modal-grid">
            <div class="input-group">
                <label>Estado de la Reserva</label>
                <select id="cli-estado-reserva">
                    <option value="Confirmada">Confirmada</option>
                    <option value="Atendida">Atendida</option>
                    <option value="Cancelada">Cancelada</option>
                </select>
                <div id="err-estado" class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> Selecciona un
                    estado</div>
            </div>
            <div class="input-group">
                <label>Canal de atención</label>
                <select id="cli-canal-reserva">
                    <option value="Web">Web</option>
                    <option value="Consulta WhatsApp">Consulta WhatsApp</option>
                    <option value="Redes sociales">Redes sociales</option>
                    <option value="Llamada">Llamada</option>
                    <option value="Presencial">Presencial</option>
                </select>
            </div>
            <div class="input-group">
                <label>Nombres</label>
                <input type="text" id="cli-nombres" autocomplete="off">
                <div id="err-nombres" class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> Obligatorio</div>
            </div>
            <div class="input-group">
                <label>Apellidos</label>
                <input type="text" id="cli-apellidos" autocomplete="off">
                <div id="err-apellidos" class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> Obligatorio
                </div>
            </div>
            <div class="input-group">
                <label>DNI (8 dígitos)</label>
                <input type="text" id="cli-dni" maxlength="8" autocomplete="off" oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                    autocomplete="off">
                <div id="err-dni" class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> 8 números req.</div>
                <div id="alerta-no-grato" class="cliente-no-grato-alerta"></div>
            </div>
            <div class="input-group">
                <label>Código país</label>
                <select id="cli-codigo-pais">
                    <option value="+93">🇦🇫 +93 Afganistán</option>
                    <option value="+358">🇦🇽 +358 Åland</option>
                    <option value="+355">🇦🇱 +355 Albania</option>
                    <option value="+213">🇩🇿 +213 Argelia</option>
                    <option value="+1-684">🇦🇸 +1-684 Samoa Americana</option>
                    <option value="+376">🇦🇩 +376 Andorra</option>
                    <option value="+244">🇦🇴 +244 Angola</option>
                    <option value="+1-264">🇦🇮 +1-264 Anguila</option>
                    <option value="+672">🇦🇶 +672 Antártida</option>
                    <option value="+1-268">🇦🇬 +1-268 Antigua y Barbuda</option>
                    <option value="+54">🇦🇷 +54 Argentina</option>
                    <option value="+374">🇦🇲 +374 Armenia</option>
                    <option value="+297">🇦🇼 +297 Aruba</option>
                    <option value="+61">🇦🇺 +61 Australia</option>
                    <option value="+43">🇦🇹 +43 Austria</option>
                    <option value="+994">🇦🇿 +994 Azerbaiyán</option>
                    <option value="+1-242">🇧🇸 +1-242 Bahamas</option>
                    <option value="+973">🇧🇭 +973 Baréin</option>
                    <option value="+880">🇧🇩 +880 Bangladés</option>
                    <option value="+1-246">🇧🇧 +1-246 Barbados</option>
                    <option value="+375">🇧🇾 +375 Bielorrusia</option>
                    <option value="+32">🇧🇪 +32 Bélgica</option>
                    <option value="+501">🇧🇿 +501 Belice</option>
                    <option value="+229">🇧🇯 +229 Benín</option>
                    <option value="+1-441">🇧🇲 +1-441 Bermudas</option>
                    <option value="+975">🇧🇹 +975 Bután</option>
                    <option value="+591">🇧🇴 +591 Bolivia</option>
                    <option value="+387">🇧🇦 +387 Bosnia y Herzegovina</option>
                    <option value="+267">🇧🇼 +267 Botsuana</option>
                    <option value="+55">🇧🇷 +55 Brasil</option>
                    <option value="+246">🇧🇳 +246 Territorios Británicos del Océano Índico</option>
                    <option value="+1-284">🇻🇬 +1-284 Islas Vírgenes Británicas</option>
                    <option value="+673">🇧🇳 +673 Brunéi</option>
                    <option value="+359">🇧🇬 +359 Bulgaria</option>
                    <option value="+226">🇧🇫 +226 Burkina Faso</option>
                    <option value="+257">🇧🇮 +257 Burundi</option>
                    <option value="+855">🇰🇭 +855 Camboya</option>
                    <option value="+237">🇨🇲 +237 Camerún</option>
                    <option value="+1">🇨🇦 +1 Canadá</option>
                    <option value="+238">🇨🇻 +238 Cabo Verde</option>
                    <option value="+1-345">🇰🇾 +1-345 Islas Caimán</option>
                    <option value="+236">🇨🇫 +236 República Centroafricana</option>
                    <option value="+235">🇹🇩 +235 Chad</option>
                    <option value="+56">🇨🇱 +56 Chile</option>
                    <option value="+86">🇨🇳 +86 China</option>
                    <option value="+61">🇨🇽 +61 Isla de Navidad</option>
                    <option value="+61">🇨🇨 +61 Islas Cocos</option>
                    <option value="+57">🇨🇴 +57 Colombia</option>
                    <option value="+269">🇰🇲 +269 Comoras</option>
                    <option value="+243">🇨🇩 +243 Congo (RDC)</option>
                    <option value="+242">🇨🇬 +242 Congo (República)</option>
                    <option value="+682">🇨🇰 +682 Islas Cook</option>
                    <option value="+506">🇨🇷 +506 Costa Rica</option>
                    <option value="+385">🇭🇷 +385 Croacia</option>
                    <option value="+53">🇨🇺 +53 Cuba</option>
                    <option value="+599">🇨🇼 +599 Curazao</option>
                    <option value="+357">🇨🇾 +357 Chipre</option>
                    <option value="+420">🇨🇿 +420 República Checa</option>
                    <option value="+45">🇩🇰 +45 Dinamarca</option>
                    <option value="+253">🇩🇯 +253 Yibuti</option>
                    <option value="+1-767">🇩🇲 +1-767 Dominica</option>
                    <option value="+1-809">🇩🇴 +1-809 República Dominicana</option>
                    <option value="+593">🇪🇨 +593 Ecuador</option>
                    <option value="+20">🇪🇬 +20 Egipto</option>
                    <option value="+503">🇸🇻 +503 El Salvador</option>
                    <option value="+240">🇬🇶 +240 Guinea Ecuatorial</option>
                    <option value="+291">🇪🇷 +291 Eritrea</option>
                    <option value="+372">🇪🇪 +372 Estonia</option>
                    <option value="+251">🇪🇹 +251 Etiopía</option>
                    <option value="+500">🇫🇰 +500 Islas Malvinas</option>
                    <option value="+298">🇫🇴 +298 Islas Feroe</option>
                    <option value="+679">🇫🇯 +679 Fiyi</option>
                    <option value="+358">🇫🇮 +358 Finlandia</option>
                    <option value="+33">🇫🇷 +33 Francia</option>
                    <option value="+596">🇬🇫 +596 Guayana Francesa</option>
                    <option value="+689">🇵🇫 +689 Polinesia Francesa</option>
                    <option value="+241">🇬🇦 +241 Gabón</option>
                    <option value="+220">🇬🇲 +220 Gambia</option>
                    <option value="+995">🇬🇪 +995 Georgia</option>
                    <option value="+49">🇩🇪 +49 Alemania</option>
                    <option value="+233">🇬🇭 +233 Ghana</option>
                    <option value="+350">🇬🇮 +350 Gibraltar</option>
                    <option value="+30">🇬🇷 +30 Grecia</option>
                    <option value="+299">🇬🇱 +299 Groenlandia</option>
                    <option value="+1-473">🇬🇩 +1-473 Granada</option>
                    <option value="+1-671">🇬🇺 +1-671 Guam</option>
                    <option value="+502">🇬🇹 +502 Guatemala</option>
                    <option value="+44-1481">🇬🇬 +44-1481 Guernsey</option>
                    <option value="+224">🇬🇳 +224 Guinea</option>
                    <option value="+245">🇬🇼 +245 Guinea-Bisáu</option>
                    <option value="+592">🇬🇾 +592 Guyana</option>
                    <option value="+509">🇭🇹 +509 Haití</option>
                    <option value="+504">🇭🇳 +504 Honduras</option>
                    <option value="+36">🇭🇺 +36 Hungría</option>
                    <option value="+354">🇮🇸 +354 Islandia</option>
                    <option value="+91">🇮🇳 +91 India</option>
                    <option value="+62">🇮🇩 +62 Indonesia</option>
                    <option value="+98">🇮🇷 +98 Irán</option>
                    <option value="+964">🇮🇶 +964 Irak</option>
                    <option value="+353">🇮🇪 +353 Irlanda</option>
                    <option value="+44-1624">🇮🇲 +44-1624 Isla de Man</option>
                    <option value="+39">🇮🇹 +39 Italia</option>
                    <option value="+1-876">🇯🇲 +1-876 Jamaica</option>
                    <option value="+81">🇯🇵 +81 Japón</option>
                    <option value="+44-1534">🇯🇪 +44-1534 Jersey</option>
                    <option value="+962">🇯🇴 +962 Jordania</option>
                    <option value="+7">🇰🇿 +7 Kazajistán</option>
                    <option value="+254">🇰🇪 +254 Kenia</option>
                    <option value="+686">🇰🇮 +686 Kiribati</option>
                    <option value="+965">🇰🇼 +965 Kuwait</option>
                    <option value="+996">🇰🇬 +996 Kirguistán</option>
                    <option value="+856">🇱🇦 +856 Laos</option>
                    <option value="+371">🇱🇻 +371 Letonia</option>
                    <option value="+961">🇱🇧 +961 Líbano</option>
                    <option value="+266">🇱🇸 +266 Lesoto</option>
                    <option value="+231">🇱🇷 +231 Liberia</option>
                    <option value="+218">🇱🇾 +218 Libia</option>
                    <option value="+423">🇱🇮 +423 Liechtenstein</option>
                    <option value="+370">🇱🇹 +370 Lituania</option>
                    <option value="+352">🇱🇺 +352 Luxemburgo</option>
                    <option value="+853">🇲🇴 +853 Macao</option>
                    <option value="+389">🇲🇰 +389 Macedonia del Norte</option>
                    <option value="+261">🇲🇬 +261 Madagascar</option>
                    <option value="+265">🇲🇼 +265 Malaui</option>
                    <option value="+60">🇲🇾 +60 Malasia</option>
                    <option value="+960">🇲🇻 +960 Maldivas</option>
                    <option value="+223">🇲🇱 +223 Malí</option>
                    <option value="+356">🇲🇹 +356 Malta</option>
                    <option value="+692">🇲🇭 +692 Islas Marshall</option>
                    <option value="+596">🇲🇶 +596 Martinica</option>
                    <option value="+222">🇲🇷 +222 Mauritania</option>
                    <option value="+230">🇲🇺 +230 Mauricio</option>
                    <option value="+262">🇾🇹 +262 Mayotte</option>
                    <option value="+52">🇲🇽 +52 México</option>
                    <option value="+691">🇫🇲 +691 Estados Federados de Micronesia</option>
                    <option value="+373">🇲🇩 +373 Moldavia</option>
                    <option value="+377">🇲🇨 +377 Mónaco</option>
                    <option value="+976">🇲🇳 +976 Mongolia</option>
                    <option value="+382">🇲🇪 +382 Montenegro</option>
                    <option value="+1664">🇲🇸 +1664 Montserrat</option>
                    <option value="+212">🇲🇦 +212 Marruecos</option>
                    <option value="+258">🇲🇿 +258 Mozambique</option>
                    <option value="+95">🇲🇲 +95 Birmania</option>
                    <option value="+264">🇳🇦 +264 Namibia</option>
                    <option value="+674">🇳🇷 +674 Nauru</option>
                    <option value="+977">🇳🇵 +977 Nepal</option>
                    <option value="+31">🇳🇱 +31 Países Bajos</option>
                    <option value="+687">🇳🇨 +687 Nueva Caledonia</option>
                    <option value="+64">🇳🇿 +64 Nueva Zelanda</option>
                    <option value="+505">🇳🇮 +505 Nicaragua</option>
                    <option value="+227">🇳🇪 +227 Níger</option>
                    <option value="+234">🇳🇬 +234 Nigeria</option>
                    <option value="+683">🇳🇺 +683 Niue</option>
                    <option value="+672">🇳🇫 +672 Isla Norfolk</option>
                    <option value="+850">🇰🇵 +850 Corea del Norte</option>
                    <option value="+47">🇳🇴 +47 Noruega</option>
                    <option value="+968">🇴🇲 +968 Omán</option>
                    <option value="+92">🇵🇰 +92 Pakistán</option>
                    <option value="+680">🇵🇼 +680 Palaos</option>
                    <option value="+970">🇵🇸 +970 Palestina</option>
                    <option value="+507">🇵🇦 +507 Panamá</option>
                    <option value="+675">🇵🇬 +675 Papúa Nueva Guinea</option>
                    <option value="+595">🇵🇾 +595 Paraguay</option>
                    <option value="+51" selected>🇵🇪 +51 Perú</option>
                    <option value="+63">🇵🇭 +63 Filipinas</option>
                    <option value="+48">🇵🇱 +48 Polonia</option>
                    <option value="+351">🇵🇹 +351 Portugal</option>
                    <option value="+1-787">🇵🇷 +1-787 Puerto Rico</option>
                    <option value="+974">🇶🇦 +974 Catar</option>
                    <option value="+262">🇷🇪 +262 Reunión</option>
                    <option value="+40">🇷🇴 +40 Rumania</option>
                    <option value="+7">🇷🇺 +7 Rusia</option>
                    <option value="+250">🇷🇼 +250 Ruanda</option>
                    <option value="+590">🇧🇱 +590 San Bartolomé</option>
                    <option value="+1-869">🇰🇳 +1-869 San Cristóbal y Nieves</option>
                    <option value="+590">🇸🇧 +590 Santa Lucía</option>
                    <option value="+1-758">🇻🇨 +1-758 San Vicente y las Granadinas</option>
                    <option value="+239">🇸🇹 +239 Santo Tomé y Príncipe</option>
                    <option value="+966">🇸🇦 +966 Arabia Saudita</option>
                    <option value="+221">🇸🇳 +221 Senegal</option>
                    <option value="+381">🇷🇸 +381 Serbia</option>
                    <option value="+248">🇸🇨 +248 Seychelles</option>
                    <option value="+232">🇸🇱 +232 Sierra Leona</option>
                    <option value="+65">🇸🇬 +65 Singapur</option>
                    <option value="+1-721">🇸🇽 +1-721 Sint Maarten</option>
                    <option value="+421">🇸🇰 +421 Eslovaquia</option>
                    <option value="+386">🇸🇮 +386 Eslovenia</option>
                    <option value="+677">🇸🇧 +677 Islas Salomón</option>
                    <option value="+252">🇸🇴 +252 Somalia</option>
                    <option value="+27">🇿🇦 +27 Sudáfrica</option>
                    <option value="+211">🇸🇸 +211 Sudán del Sur</option>
                    <option value="+34">🇪🇸 +34 España</option>
                    <option value="+94">🇱🇰 +94 Sri Lanka</option>
                    <option value="+249">🇸🇩 +249 Sudán</option>
                    <option value="+597">🇸🇷 +597 Surinam</option>
                    <option value="+47">🇸🇯 +47 Svalbard y Jan Mayen</option>
                    <option value="+268">🇸🇿 +268 Suazilandia</option>
                    <option value="+46">🇸🇪 +46 Suecia</option>
                    <option value="+41">🇨🇭 +41 Suiza</option>
                    <option value="+992">🇹🇯 +992 Tayikistán</option>
                    <option value="+66">🇹🇭 +66 Tailandia</option>
                    <option value="+676">🇹🇴 +676 Tonga</option>
                    <option value="+216">🇹🇳 +216 Túnez</option>
                    <option value="+90">🇹🇷 +90 Turquía</option>
                    <option value="+1-868">🇹🇹 +1-868 Trinidad y Tobago</option>
                    <option value="+688">🇹🇻 +688 Tuvalu</option>
                    <option value="+256">🇺🇬 +256 Uganda</option>
                    <option value="+380">🇺🇦 +380 Ucrania</option>
                    <option value="+971">🇦🇪 +971 Emiratos Árabes Unidos</option>
                    <option value="+44">🇬🇧 +44 Reino Unido</option>
                    <option value="+1">🇺🇸 +1 Estados Unidos</option>
                    <option value="+598">🇺🇾 +598 Uruguay</option>
                    <option value="+998">🇺🇿 +998 Uzbekistán</option>
                    <option value="+678">🇻🇺 +678 Vanuatu</option>
                    <option value="+379">🇻🇦 +379 Ciudad del Vaticano</option>
                    <option value="+58">🇻🇪 +58 Venezuela</option>
                    <option value="+84">🇻🇳 +84 Vietnam</option>
                    <option value="+681">🇼🇫 +681 Wallis y Futuna</option>
                    <option value="+212">🇪🇭 +212 Sahara Occidental</option>
                    <option value="+967">🇾🇪 +967 Yemen</option>
                    <option value="+260">🇿🇲 +260 Zambia</option>
                    <option value="+263">🇿🇼 +263 Zimbabue</option>
                </select>
            </div>
            <div class="input-group">
                <label>Número móvil</label>
                <input type="tel" id="cli-telefono" maxlength="15" placeholder="999 999 999" autocomplete="off">
                <div id="err-telefono" class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> Número
                    obligatorio</div>
            </div>
            <div class="input-group">
                <label>Email <span style="font-weight:500;color:#94a3b8;">(opcional)</span></label>
                <input type="email" id="cli-email" placeholder="correo@ejemplo.com" autocomplete="off">
                <div id="err-email" class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> Email inválido
                </div>
            </div>
            <div class="input-group">
                <label>Categoría Asignada</label>
                <select id="cli-categoria">
                    <?php foreach($categoriasHotel as $cat): ?>
                        <option value="<?= (int)$cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group" id="grupo-habitacion-asignada" style="<?= $gestionPorHabitacion ? '' : 'display:none;' ?>">
                <label>Habitaciones asignadas <span id="cli-cantidad-ayuda" style="color:#64748b;font-weight:600"></span></label>
                <div id="alerta-categorias-superiores" class="habitaciones-alerta"></div>
                <div id="cli-habitaciones-lista" class="habitaciones-check-list">
                    <div class="habitacion-cargando">Seleccione una categoría para cargar habitaciones.</div>
                </div>
                <div id="resumen-precios-habitaciones" class="resumen-precios-habitaciones"></div>
                <div id="err-habitacion" class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> Selecciona exactamente la cantidad de habitaciones solicitada.</div>
            </div>
            <div class="input-group full-width">
                <label>Notas / Observaciones</label>
                <textarea id="cli-notas" placeholder="Añade notas para recepción..."></textarea>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; margin-top: 10px;">
            <button onclick="cerrarModal()" class="btn-modal"
                style="background:#f1f5f9; color:#64748b;">Cancelar</button>
            <button onclick="guardarAtencion()" class="btn-modal btn-guardar"><i class="fa-solid fa-floppy-disk"></i>
                Guardar Cambios</button>
        </div>
    </div>


    <div id="modal-whatsapp" class="modal-content whatsapp-modal" tabindex="0">
        <div class="modal-header">
            <h2><i class="fa-brands fa-whatsapp" style="color:#25d366;"></i> Mensaje WhatsApp <i class="fa-solid fa-xmark modal-close-icon" onclick="cerrarModalWhatsApp()"></i></h2>
        </div>
        <p style="margin-top:0;color:#64748b;font-size:13px;">Edita el texto antes de abrir WhatsApp. Se cargan los datos de la consulta automáticamente.</p>
        <div class="quick-msg">
            <button type="button" onclick="usarPlantillaWhatsApp('recordatorio')">Recordatorio</button>
            <button type="button" onclick="usarPlantillaWhatsApp('precio')">Bajó de precio</button>
            <button type="button" onclick="usarPlantillaWhatsApp('ultimas')">Últimas disponibles</button>
            <button type="button" onclick="usarPlantillaWhatsApp('personalizado')">Personalizado</button>
        </div>
        <textarea id="wa-mensaje"></textarea>
        <div style="display:flex;justify-content:space-between;gap:12px;margin-top:14px;">
            <button onclick="cerrarModalWhatsApp()" class="btn-modal" style="background:#f1f5f9;color:#64748b;">Cancelar</button>
            <button onclick="enviarWhatsAppPersonalizado()" class="btn-whatsapp-send"><i class="fa-brands fa-whatsapp"></i> Enviar por WhatsApp</button>
        </div>
    </div>

    <div id="modal-detalle-reserva" class="modal-content modal-secundario" tabindex="0">
        <div class="modal-header"><h2><span><i class="fa-solid fa-bed"></i> Detalle de habitaciones</span><i class="fa-solid fa-xmark modal-close-icon" onclick="cerrarModalDetalleReserva()"></i></h2></div>
        <div id="detalle-reserva-contenido"></div>
    </div>

    <div id="modal-enlaces-categorias" class="modal-content modal-secundario" tabindex="0">
        <div class="modal-header"><h2><span><i class="fa-solid fa-link"></i> Enlaces por categoría</span><i class="fa-solid fa-xmark modal-close-icon" onclick="cerrarModalEnlacesCategorias()"></i></h2></div>
        <p style="margin-top:0;color:#64748b;font-size:13px;">Abre o copia el enlace directo de la categoría para enviarlo al cliente.</p>
        <div class="enlaces-lista">
            <?php foreach($categoriasHotel as $cat):
                $catSlug = trim((string)($cat['slug'] ?? ''));
                if ($catSlug === '') { $catSlug = strtolower(preg_replace('/[^a-z0-9]+/i','-', $cat['nombre'])); $catSlug = trim($catSlug,'-'); }
                $urlCategoria = $webReservasUrl . (strpos($webReservasUrl, '?') !== false ? '&' : '?') . 'categoria=' . rawurlencode($catSlug);
            ?>
            <div class="enlace-item">
                <div class="enlace-top"><div><div class="enlace-titulo"><?= htmlspecialchars($cat['nombre']) ?></div><div class="enlace-url"><?= htmlspecialchars($urlCategoria) ?></div></div></div>
                <div class="enlace-acciones"><a class="mini-btn abrir" href="<?= htmlspecialchars($urlCategoria) ?>" target="_blank"><i class="fa-solid fa-up-right-from-square"></i> Abrir</a><button class="mini-btn copiar" type="button" onclick='copiarTexto(<?= json_encode($urlCategoria, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>)'><i class="fa-solid fa-copy"></i> Copiar enlace</button></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="toast-container" class="toast-container"></div>

    <footer class="feed-footer">
        Desarrollado por Inkarian Lab y Maxisoft Net
    </footer>

    <script>
        const ROL_USUARIO = <?= json_encode($usuarioActual['rol'] ?? '') ?>;
        const ID_USUARIO_ACTUAL = <?= json_encode((int)($usuarioActual['id_usuario'] ?? 0)) ?>;
        const ES_ADMIN = ['admin_hotel','admin'].includes(ROL_USUARIO);
        const ES_RECEPCIONISTA = ROL_USUARIO === 'recepcionista';
        const ES_RECEPCION = ES_ADMIN || ES_RECEPCIONISTA;
        const GESTION_POR_HABITACION = <?= $gestionPorHabitacion ? 'true' : 'false' ?>;

        let reservaActualId = null;
        let todasLasReservas = [];
        let modoModal = 'atender';
        let vistaActual = 'Pendiente';
        let modalCamposDesbloqueados = false;

        // Variables de Paginación
        let paginaActual = 1;
        const filasPorPagina = 15;
        let reservasFiltradasActuales = [];
        let chartIngresos = null;
        let chartCategorias = null;
        let reservaWhatsAppActual = null;
        let chartCanales = null;

        // --- NUEVA FUNCIÓN: Notificaciones Animadas ---
        function mostrarNotificacion(mensaje, tipo = 'success') {
            const container = document.getElementById('toast-container');
            container.querySelectorAll('.toast').forEach(item => item.remove());
            const toast = document.createElement('div');
            toast.className = `toast ${tipo}`;

            const icono = tipo === 'success' ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-triangle-exclamation"></i>';
            toast.innerHTML = `${icono} <span>${mensaje}</span>`;

            container.appendChild(toast);

            // Animar entrada
            setTimeout(() => toast.classList.add('show'), 10);

            // Desaparecer después de 3 segundos
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400); // Espera a que termine la animación css
            }, 3000);
        }

        async function cargarReservas(mantenerPagina = false) {
            const apiUrl = '../api/reservas.php';

            try {
                const res = await fetch(apiUrl, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });

                const texto = await res.text();
                let data;

                try {
                    data = JSON.parse(texto);
                } catch (e) {
                    console.error('Respuesta no JSON del servidor:', texto);
                    mostrarNotificacion('El API no devolvió JSON válido. Revisa la consola.', 'error');
                    return;
                }

                if (!res.ok) {
                    console.error('Error HTTP del API:', res.status, data);
                    mostrarNotificacion('Error del API: ' + res.status, 'error');
                    return;
                }

                if (!Array.isArray(data)) {
                    console.error('Formato inesperado del API:', data);
                    mostrarNotificacion('El API respondió, pero el formato no es una lista.', 'error');
                    return;
                }

                todasLasReservas = data;

                if (!mantenerPagina) {
                    paginaActual = 1;
                }

                aplicarFiltrosYPaginar();
            } catch (err) {
                console.error('Error real al llamar al API:', err);
                mostrarNotificacion('No se pudo llamar al API de reservas.', 'error');
            }
        }

        function cambiarVista(nuevaVista) {
            const vistasRecepcionista = ['Pendiente', 'Atendida', 'Confirmada'];
            if (!ES_ADMIN && !vistasRecepcionista.includes(nuevaVista)) {
                mostrarNotificacion('Tu rol solo permite ver Por atender, Atendidos y Confirmados.', 'error');
                return;
            }
            if (nuevaVista === 'Reportes' && !ES_ADMIN) {
                mostrarNotificacion('Solo el administrador puede ver reportes.', 'error');
                return;
            }
            vistaActual = nuevaVista;
            paginaActual = 1;
            actualizarBotonesVista();
            aplicarFiltrosYPaginar();
        }

        function actualizarBotonesVista() {
            document.querySelectorAll('.stat-card-button').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.vista === vistaActual);
            });
        }

        function inicializarFiltroMesAnio() {
            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            const mesSelect = document.getElementById('filtro-mes');
            const anoSelect = document.getElementById('filtro-ano');

            mesSelect.innerHTML = meses.map((mes, index) => `<option value="${index}">${mes}</option>`).join('');

            const añoInicio = 2026;
            const añoFin = 2046;
            let años = '';
            for (let año = añoInicio; año <= añoFin; año++) {
                años += `<option value="${año}">${año}</option>`;
            }
            anoSelect.innerHTML = años;

            const hoy = new Date();
            const mesActual = hoy.getMonth();
            const anoActual = hoy.getFullYear();
            if (anoActual >= añoInicio && anoActual <= añoFin) {
                anoSelect.value = anoActual;
            } else {
                anoSelect.value = añoInicio;
            }
            mesSelect.value = mesActual;
            actualizarControlesFecha();
        }

        function actualizarControlesFecha() {
            const rango = document.getElementById('filtro-fecha-rango').value;
            const mesSelect = document.getElementById('filtro-mes');
            const anoSelect = document.getElementById('filtro-ano');
            const habilitado = rango === 'mes';
            mesSelect.disabled = !habilitado;
            anoSelect.disabled = !habilitado;
        }

        function obtenerIntervaloFecha(rango) {
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            let inicio = new Date(hoy);
            let fin = new Date(hoy);

            if (rango === 'hoy') {
                fin.setDate(fin.getDate() + 1);
            } else if (rango === 'ultimos7dias') {
                inicio.setDate(hoy.getDate() - 6);
                fin.setDate(fin.getDate() + 1);
            } else if (rango === 'mes') {
                const mes = parseInt(document.getElementById('filtro-mes').value, 10);
                const ano = parseInt(document.getElementById('filtro-ano').value, 10);
                inicio = new Date(ano, mes, 1);
                fin = new Date(ano, mes + 1, 1);
            }

            return { inicio, fin };
        }

        function parseFechaLocal(fechaStr) {
            if (!fechaStr) return null;
            const partes = fechaStr.split('-');
            if (partes.length !== 3) return null;
            const ano = parseInt(partes[0], 10);
            const mes = parseInt(partes[1], 10) - 1;
            const dia = parseInt(partes[2], 10);
            const fecha = new Date(ano, mes, dia);
            fecha.setHours(0, 0, 0, 0);
            return fecha;
        }

        function aplicarFiltrosYPaginar() {
            const tipoFiltro = document.getElementById('filtro-tipo')?.value || 'cliente';
            const textoBuscado = (document.getElementById('filtro-input')?.value || '').toLowerCase();
            const tipoFecha = document.getElementById('filtro-fecha-tipo')?.value || 'fecha_registro';
            const rangoFecha = document.getElementById('filtro-fecha-rango')?.value || '';

            let datosFiltrados = Array.isArray(todasLasReservas) ? [...todasLasReservas] : [];

            if (rangoFecha) {
                const { inicio, fin } = obtenerIntervaloFecha(rangoFecha);
                datosFiltrados = datosFiltrados.filter(reserva => {
                    const fechaRaw = tipoFecha === 'fecha_registro'
                        ? String(reserva.fecha_consulta || '').split(' ')[0]
                        : String(reserva.fecha_checkin || '');
                    if (!fechaRaw) return false;
                    const fecha = parseFechaLocal(fechaRaw);
                    if (!fecha) return false;
                    return fecha >= inicio && fecha < fin;
                });
            }

            actualizarDashboard(datosFiltrados);

            const panelReportes = document.getElementById('panel-reportes');
            const mainPanel = document.querySelector('.main-panel');
            if (panelReportes) panelReportes.style.display = vistaActual === 'Reportes' ? 'block' : 'none';
            if (mainPanel) mainPanel.style.display = vistaActual === 'Reportes' ? 'none' : 'flex';

            if (vistaActual === 'Reportes') {
                if (!ES_ADMIN) {
                    vistaActual = 'Pendiente';
                    mostrarNotificacion('Solo el administrador puede ver reportes.', 'error');
                } else {
                    reservasFiltradasActuales = datosFiltrados;
                    setTimeout(() => actualizarReportes(datosFiltrados), 80);
                    return;
                }
            }

            if (vistaActual !== 'Todas') {
                datosFiltrados = datosFiltrados.filter(r => String(r.estado_reserva || '') === vistaActual);
            }

            if (textoBuscado !== '') {
                datosFiltrados = datosFiltrados.filter(reserva => {
                    const cliente = String(reserva.cliente || '').toLowerCase();
                    const dni = String(reserva.dni || '').toLowerCase();
                    const checkin = String(reserva.fecha_checkin || '').toLowerCase();
                    const registro = String(reserva.fecha_consulta || '').toLowerCase();
                    const canal = obtenerCanalReserva(reserva).toLowerCase();

                    if (tipoFiltro === 'cliente') return cliente.includes(textoBuscado);
                    if (tipoFiltro === 'dni') return dni.includes(textoBuscado);
                    if (tipoFiltro === 'fecha_checkin') return checkin.includes(textoBuscado);
                    if (tipoFiltro === 'fecha_registro') return registro.includes(textoBuscado);
                    if (tipoFiltro === 'canal_reserva') return canal.includes(textoBuscado);
                    return true;
                });
                paginaActual = 1;
            }

            reservasFiltradasActuales = datosFiltrados;
            renderizarPaginacionYTabla();
        }

        function renderizarPaginacionYTabla() {
            const totalItems = reservasFiltradasActuales.length;
            const totalPaginas = Math.ceil(totalItems / filasPorPagina) || 1;

            if (paginaActual > totalPaginas) paginaActual = totalPaginas;

            const inicio = (paginaActual - 1) * filasPorPagina;
            const fin = inicio + filasPorPagina;
            const filasAMostrar = reservasFiltradasActuales.slice(inicio, fin);

            const infoPaginacion = document.getElementById('info-paginacion');
            const btnPrev = document.getElementById('btn-prev');
            const btnNext = document.getElementById('btn-next');

            if (infoPaginacion) {
                infoPaginacion.innerText = `Mostrando ${totalItems === 0 ? 0 : inicio + 1} al ${Math.min(fin, totalItems)} de ${totalItems} reservas`;
            }
            if (btnPrev) btnPrev.disabled = paginaActual === 1;
            if (btnNext) btnNext.disabled = paginaActual === totalPaginas;

            renderizarTablaDOM(filasAMostrar);
        }

        function cambiarPagina(direccion) {
            paginaActual += direccion;
            renderizarPaginacionYTabla();
        }


        function obtenerCanalReserva(reserva) {
            const canal = String(reserva.canal_reserva || reserva.canal || 'Web').trim() || 'Web';
            return canal;
        }

        function renderizarCanalBadge(canal) {
            const texto = String(canal || 'Web').trim() || 'Web';
            const normalizado = texto.toLowerCase();

            let clase = 'canal-web';
            let icono = 'fa-globe';

            if (normalizado.includes('whatsapp')) {
                clase = 'canal-whatsapp';
                icono = 'fa-brands fa-whatsapp';
            } else if (normalizado.includes('red')) {
                clase = 'canal-redes';
                icono = 'fa-share-nodes';
            } else if (normalizado.includes('llamada')) {
                clase = 'canal-llamada';
                icono = 'fa-phone';
            } else if (normalizado.includes('presencial')) {
                clase = 'canal-presencial';
                icono = 'fa-user-check';
            }

            const claseIcono = icono.includes('fa-brands') ? icono : `fa-solid ${icono}`;
            return `<span class="canal-badge ${clase}"><i class="${claseIcono}"></i>${texto}</span>`;
        }

        function renderizarTablaDOM(datos) {
            const tbody = document.getElementById('tabla-reservas');
            if (!tbody) {
                console.error('No existe el tbody con id tabla-reservas');
                return;
            }
            datos = Array.isArray(datos) ? datos : [];
            tbody.innerHTML = '';

            if (datos.length === 0) {
                tbody.innerHTML = `<tr><td colspan="14" style="text-align:center; padding: 40px; color:#a0aec0; font-weight: 500;"><i class="fa-solid fa-box-open" style="font-size: 24px; display:block; margin-bottom:10px;"></i> No hay registros en esta vista</td></tr>`;
                return;
            }

            datos.forEach((reserva, index) => {
                const esPendiente = reserva.estado_reserva === 'Pendiente';
                const esAtendida = reserva.estado_reserva === 'Atendida';
                const bloqueoActivo = String(reserva.bloqueo_activo || '0') === '1' && Number(reserva.id_usuario_tomada || 0) !== Number(ID_USUARIO_ACTUAL || 0);
                const bloqueoTexto = bloqueoActivo
                    ? `<span style="display:inline-flex;align-items:center;gap:6px;color:#b45309;background:#fffbeb;border:1px solid #fde68a;padding:7px 10px;border-radius:999px;font-size:11px;font-weight:800;" title="Bloqueada hasta ${reserva.bloqueo_hasta || ''}"><i class="fa-solid fa-lock"></i> ${reserva.usuario_tomada_nombre || 'En atención'}</span>`
                    : '';

                let botonAccion = '<span style="color:#94a3b8; font-size:12px; font-weight:700;">Solo lectura</span>';
                if (ES_RECEPCION) {
                    const botonEliminar = ES_ADMIN ? `<button class="btn-icon-delete" onclick="eliminarReserva(${reserva.id_reserva})" title="Eliminar"><i class="fa-solid fa-trash-can"></i></button>` : '';
                    const botonWhatsApp = esAtendida ? `<button class="btn-whatsapp" onclick="abrirWhatsApp(${reserva.id_reserva})" title="Preparar mensaje WhatsApp"><i class="fa-brands fa-whatsapp"></i></button>` : '';
                    const puedeEditar = esAtendida;
                    const botonVerDetalleAccion = (Array.isArray(reserva.detalle_habitaciones) && reserva.detalle_habitaciones.length) || reserva.id_habitacion
                        ? `<button class="btn-icon-edit" onclick="abrirDetalleReserva(${reserva.id_reserva})" title="Ver detalle"><i class="fa-solid fa-eye"></i></button>`
                        : '';
                    botonAccion = bloqueoActivo
                        ? `<div class="acciones-wrap">${bloqueoTexto}</div>`
                        : (esPendiente
                            ? `<div class="acciones-wrap">
                                   <button class="btn-atender" onclick="abrirModalAtender(${reserva.id_reserva})"><i class="fa-solid fa-clipboard-user"></i> Atender</button>
                                   ${botonEliminar}
                               </div>`
                            : `<div class="acciones-wrap">
                                   ${botonVerDetalleAccion}
                                   ${botonWhatsApp}
                                   ${puedeEditar ? `<button class="btn-icon-edit" onclick="abrirModalEditar(${reserva.id_reserva})" title="Editar"><i class="fa-solid fa-pencil"></i></button>` : ''}
                                   ${botonEliminar}
                               </div>`);
                }

                let notasIcono = reserva.notas ? `<span class="col-notas" title="${reserva.notas}">${reserva.notas}</span>` : '<span style="color:#cbd5e1;">-</span>';
                const detalles = Array.isArray(reserva.detalle_habitaciones) ? reserva.detalle_habitaciones : [];
                const habitacionesResumen = detalles.length
                    ? `${detalles.slice(0,2).map(d => d.numero_habitacion || '-').join(', ')}${detalles.length > 2 ? ` +${detalles.length-2}` : ''}`
                    : (reserva.nro_cuarto || 'Por asignar');
                const botonDetalle = (detalles.length || reserva.id_habitacion)
                    ? `<button class="btn-detalle" type="button" onclick="abrirDetalleReserva(${reserva.id_reserva})"><i class="fa-solid fa-eye"></i> Ver detalle</button>`
                    : '';
                const canalReserva = obtenerCanalReserva(reserva);
                const canalBadge = renderizarCanalBadge(canalReserva);

                let diffDays = 1;
                if (reserva.fecha_checkin && reserva.fecha_checkout) {
                    const diffTime = Math.abs(new Date(reserva.fecha_checkout) - new Date(reserva.fecha_checkin));
                    diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                }

                tbody.innerHTML += `
                    <tr style="animation: epicSlideIn 0.3s ease forwards; opacity: 0; animation-delay: ${index * 0.03}s;">
                        <td style="font-family: monospace; color:#64748b;">${reserva.fecha_consulta || ''}</td>
                        <td style="font-weight:700; color:var(--primary);">#ADS-${reserva.id_reserva}</td>
                        <td style="color:#475569; font-weight: 500;">${reserva.categoria_solicitada}</td>
                        <td style="font-weight:700; color:#0f766e;"><div>${habitacionesResumen}</div>${botonDetalle}</td>
                        <td style="font-weight: 500;">${reserva.cliente}</td>
                        <td style="font-family: monospace; color:#64748b;">${reserva.dni}</td>
                        <td style="font-family: monospace; color:#64748b;">${reserva.telefono || ''}</td>
                        <td>${canalBadge}</td>
                        <td style="font-family: monospace; font-weight: 500;">${reserva.fecha_checkin}</td>
                        <td style="font-family: monospace; font-weight: 500;">${reserva.fecha_checkout}</td>
                        <td style="font-weight: 700; color: #34495e; text-align: center;">${diffDays} <i class="fa-solid fa-moon" style="font-size:10px; color:#95a5a6;"></i></td>
                        <td style="font-weight:700; color:#27ae60;">S/ ${reserva.precio_final}</td>
                        <td>${notasIcono}</td>
                        <td class="acciones-cell">${botonAccion}</td>
                    </tr>
                `;
            });
        }

        function moneda(valor) {
            return `S/ ${Number(valor || 0).toFixed(2)}`;
        }

        function abrirDetalleReserva(id) {
            const reserva = todasLasReservas.find(r => Number(r.id_reserva) === Number(id));
            if (!reserva) return mostrarNotificacion('No se encontró la reserva.', 'error');
            let detalles = Array.isArray(reserva.detalle_habitaciones) ? reserva.detalle_habitaciones : [];
            const cont = document.getElementById('detalle-reserva-contenido');
            if (!detalles.length && reserva.id_habitacion) {
                const nochesFallback = Math.max(1, Math.ceil((new Date(reserva.fecha_checkout) - new Date(reserva.fecha_checkin)) / 86400000));
                const aplicadoFallback = Number(reserva.precio_final || 0) / nochesFallback;
                detalles = [{
                    id_habitacion: reserva.id_habitacion,
                    numero_habitacion: reserva.nro_cuarto || '-',
                    categoria_principal: reserva.categoria_solicitada || 'Sin categoría',
                    categoria_vendida: reserva.categoria_solicitada || '-',
                    precio_original: Number(reserva.tarifa_inicial || aplicadoFallback),
                    precio_aplicado: aplicadoFallback,
                    subtotal: Number(reserva.precio_final || 0),
                    motivo_ajuste: '',
                    usuario_ajuste: ''
                }];
            }
            if (!detalles.length) {
                cont.innerHTML = '<div class="detalle-item">Esta reserva todavía no tiene habitaciones asignadas.</div>';
            } else {
                const noches = Math.max(1, Math.ceil((new Date(reserva.fecha_checkout) - new Date(reserva.fecha_checkin)) / 86400000));
                const cantidadSolicitada = Math.max(1, Number(reserva.cantidad_habitaciones || detalles.length || 1));
                let totalOriginal = 0, totalAplicado = 0;
                const items = detalles.map((d, indice) => {
                    const original = Number(d.precio_original || 0);
                    const aplicado = Number(d.precio_aplicado || d.precio_noche || 0);
                    const subtotal = Number(d.subtotal || (aplicado * noches));
                    totalOriginal += original * noches;
                    totalAplicado += subtotal;
                    const ajustado = Math.abs(original - aplicado) > 0.009;
                    const precioHtml = ajustado
                        ? `<span class="precio-original-tachado">${moneda(original)}</span><span class="precio-aplicado">${moneda(aplicado)} / noche</span>`
                        : `<span class="precio-aplicado">${moneda(aplicado)} / noche</span>`;
                    const ajuste = ajustado ? `<div class="ajuste-badge"><i class="fa-solid fa-tag"></i> Ajuste: ${moneda(original-aplicado)}${d.motivo_ajuste ? ' · '+d.motivo_ajuste : ''}</div>` : '';
                    return `<div class="detalle-item"><div class="detalle-top"><div><div class="detalle-titulo">${indice + 1}. Hab. ${d.numero_habitacion || '-'} — ${d.categoria_principal || 'Sin categoría'}</div><div class="detalle-meta">Vendida como: ${d.categoria_vendida || reserva.categoria_solicitada || '-'}</div></div><div>${precioHtml}</div></div>${ajuste}<div class="detalle-meta" style="margin-top:8px;">Precio individual por noche: <strong>${moneda(aplicado)}</strong></div><div class="detalle-meta" style="margin-top:4px;">Subtotal por ${noches} noche(s): <strong>${moneda(subtotal)}</strong>${d.usuario_ajuste ? ' · Ajustado por: '+d.usuario_ajuste : ''}</div></div>`;
                }).join('');
                const diferencia = totalOriginal-totalAplicado;
                const faltantes = Math.max(0, cantidadSolicitada - detalles.length);
                const avisoFaltantes = faltantes > 0
                    ? `<div style="margin-bottom:12px;padding:12px 14px;border-radius:12px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-weight:700;"><i class="fa-solid fa-triangle-exclamation"></i> La reserva indica ${cantidadSolicitada} habitación(es), pero la base de datos solo tiene ${detalles.length} detalle(s) guardado(s). Faltan ${faltantes} por registrar.</div>`
                    : '';
                cont.innerHTML = `<div style="margin-bottom:12px;font-weight:800;color:#1e293b;">Habitaciones asignadas: ${detalles.length} de ${cantidadSolicitada}</div>${avisoFaltantes}<div class="detalle-lista">${items}</div><div class="detalle-resumen"><div>Habitaciones<strong>${detalles.length}</strong></div><div>Noches<strong>${noches}</strong></div><div>Total original<strong>${moneda(totalOriginal)}</strong></div><div>Total aplicado<strong>${moneda(totalAplicado)}</strong>${Math.abs(diferencia)>0.009 ? `<small style="display:block;color:#c2410c;margin-top:4px;">Diferencia: ${moneda(diferencia)}</small>` : ''}</div></div>`;
            }
            document.getElementById('modal-overlay').style.display = 'block';
            const modal = document.getElementById('modal-detalle-reserva');
            modal.style.display = 'block';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function cerrarModalDetalleReserva() {
            const modal = document.getElementById('modal-detalle-reserva');
            modal.classList.remove('show');
            setTimeout(() => { modal.style.display='none'; document.getElementById('modal-overlay').style.display='none'; }, 150);
        }

        function abrirModalEnlacesCategorias() {
            document.getElementById('modal-overlay').style.display = 'block';
            const modal = document.getElementById('modal-enlaces-categorias');
            modal.style.display = 'block';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function cerrarModalEnlacesCategorias() {
            const modal = document.getElementById('modal-enlaces-categorias');
            modal.classList.remove('show');
            setTimeout(() => { modal.style.display='none'; document.getElementById('modal-overlay').style.display='none'; }, 150);
        }

        async function copiarTexto(texto) {
            try { await navigator.clipboard.writeText(texto); mostrarNotificacion('Enlace copiado.'); }
            catch(e) { mostrarNotificacion('No se pudo copiar el enlace.', 'error'); }
        }

        function normalizarTelefonoWhatsApp(telefono) {
            let limpio = String(telefono || '').replace(/[^0-9]/g, '');
            if (!limpio) return '';
            if (limpio.startsWith('00')) limpio = limpio.substring(2);
            if (limpio.length === 9) limpio = '51' + limpio;
            return limpio;
        }

        function abrirWhatsApp(id) {
            const r = todasLasReservas.find(x => x.id_reserva == id);
            if (!r) {
                mostrarNotificacion('No se encontró la reserva.', 'error');
                return;
            }
            const numero = normalizarTelefonoWhatsApp(r.telefono);
            if (!numero) {
                mostrarNotificacion('Esta reserva no tiene número de WhatsApp guardado.', 'error');
                return;
            }
            reservaWhatsAppActual = r;
            document.getElementById('wa-mensaje').value = construirMensajeWhatsApp(r, 'recordatorio');
            document.getElementById('modal-overlay').style.display = 'block';
            const modal = document.getElementById('modal-whatsapp');
            modal.style.display = 'block';
            setTimeout(() => modal.classList.add('show'), 10);
        }

        function cerrarModalWhatsApp() {
            const modal = document.getElementById('modal-whatsapp');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                if (document.getElementById('modal-atencion').style.display !== 'block') {
                    document.getElementById('modal-overlay').style.display = 'none';
                }
            }, 150);
        }

        function construirMensajeWhatsApp(r, tipo = 'recordatorio') {
            const hotel = <?= json_encode($usuarioActual['hotel'] ?? 'nuestro hotel') ?>;
            const cliente = (r.cli_nombres || r.cliente || '').trim();
            const categoria = r.categoria_solicitada || 'la habitación consultada';
            const checkin = r.fecha_checkin || '';
            const checkout = r.fecha_checkout || '';
            const importe = r.precio_final || '0.00';
            const noches = (() => { try { return Math.ceil(Math.abs(new Date(checkout) - new Date(checkin)) / (1000*60*60*24)); } catch(e) { return ''; } })();
            if (tipo === 'precio') {
                return `Hola ${cliente}, le saluda ${hotel}.\n\nLe escribimos por su consulta de reserva en ${categoria}, del ${checkin} al ${checkout}.\n\nTenemos una promoción / ajuste de precio para esas fechas. El importe consultado fue S/ ${importe}. Podemos revisar una mejor tarifa para ayudarle a confirmar su reserva.\n\n¿Desea que le enviemos la nueva propuesta?`;
            }
            if (tipo === 'ultimas') {
                return `Hola ${cliente}, le saluda ${hotel}.\n\nLe recordamos su consulta por ${categoria}, del ${checkin} al ${checkout}.\n\nEstamos revisando disponibilidad y quedan pocas opciones para esas fechas. ¿Desea que le ayudemos a confirmar antes de que se ocupen?`;
            }
            if (tipo === 'personalizado') {
                return `Hola ${cliente}, le saluda ${hotel}.\n\nLe escribimos por su consulta de reserva:\nCategoría: ${categoria}\nCheck-in: ${checkin}\nCheck-out: ${checkout}\nNoches: ${noches}\nImporte consultado: S/ ${importe}\n\n`;
            }
            return `Hola ${cliente}, le saluda ${hotel}.\n\nLe escribimos para recordarle su consulta de reserva:\n\nCategoría: ${categoria}\nCheck-in: ${checkin}\nCheck-out: ${checkout}\nNoches: ${noches}\nImporte consultado: S/ ${importe}\n\nActualmente podemos ayudarle a revisar disponibilidad y continuar con la confirmación. ¿Desea que le ayudemos con su reserva?`;
        }

        function usarPlantillaWhatsApp(tipo) {
            if (!reservaWhatsAppActual) return;
            document.getElementById('wa-mensaje').value = construirMensajeWhatsApp(reservaWhatsAppActual, tipo);
        }

        function enviarWhatsAppPersonalizado() {
            if (!reservaWhatsAppActual) return;
            const numero = normalizarTelefonoWhatsApp(reservaWhatsAppActual.telefono);
            const mensaje = document.getElementById('wa-mensaje').value.trim();
            if (!mensaje) {
                mostrarNotificacion('Escribe un mensaje antes de enviarlo.', 'error');
                return;
            }
            window.open(`https://wa.me/${numero}?text=${encodeURIComponent(mensaje)}`, '_blank');
            cerrarModalWhatsApp();
        }

        function actualizarReportes(datosBase) {
            if (!ES_ADMIN) return;
            datosBase = Array.isArray(datosBase) ? datosBase : [];

            const ventasRealizadas = datosBase.filter(r => ['Confirmada', 'Culminada'].includes(String(r.estado_reserva || '')));
            const ingresos = ventasRealizadas.reduce((sum, r) => sum + (parseFloat(r.precio_final) || 0), 0);
            const promedio = ventasRealizadas.length ? ingresos / ventasRealizadas.length : 0;

            const porCategoria = {};
            const porCanal = {};
            const ingresosPorDia = {};

            ventasRealizadas.forEach(r => {
                const cat = r.categoria_solicitada || 'Sin categoría';
                porCategoria[cat] = (porCategoria[cat] || 0) + 1;

                const fecha = String(r.fecha_checkin || r.fecha_consulta || '').split(' ')[0] || 'Sin fecha';
                ingresosPorDia[fecha] = (ingresosPorDia[fecha] || 0) + (parseFloat(r.precio_final) || 0);
            });

            datosBase.forEach(r => {
                const canal = obtenerCanalReserva(r);
                porCanal[canal] = (porCanal[canal] || 0) + 1;
            });

            const categoriaTop = Object.entries(porCategoria).sort((a, b) => b[1] - a[1])[0];
            const canalTop = Object.entries(porCanal).sort((a, b) => b[1] - a[1])[0];

            const setText = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.innerText = value;
            };

            setText('reporte-ingresos', 'S/ ' + ingresos.toFixed(2));
            setText('reporte-confirmadas', ventasRealizadas.length);
            setText('reporte-promedio', 'S/ ' + promedio.toFixed(2));
            setText('reporte-categoria-top', categoriaTop ? categoriaTop[0] : '-');
            setText('reporte-canal-top', canalTop ? `${canalTop[0]} (${canalTop[1]})` : '-');
            setText('stat-reportes', 'S/ ' + ingresos.toFixed(2));

            if (typeof Chart === 'undefined') {
                console.error('Chart.js no cargó correctamente.');
                return;
            }

            const canvasIngresos = document.getElementById('chartIngresos');
            const canvasCategorias = document.getElementById('chartCategorias');
            const canvasCanales = document.getElementById('chartCanales');

            if (!canvasIngresos || !canvasCategorias || !canvasCanales) {
                console.error('No se encontraron los canvas de reportes.');
                return;
            }

            const diasOrdenados = Object.keys(ingresosPorDia).sort();
            const ingresosOrdenados = diasOrdenados.map(dia => ingresosPorDia[dia]);
            const categorias = Object.keys(porCategoria);
            const cantidadesCategoria = categorias.map(cat => porCategoria[cat]);
            const canales = Object.keys(porCanal);
            const cantidadesCanal = canales.map(canal => porCanal[canal]);

            if (chartIngresos) {
                chartIngresos.destroy();
                chartIngresos = null;
            }

            if (chartCategorias) {
                chartCategorias.destroy();
                chartCategorias = null;
            }

            if (chartCanales) {
                chartCanales.destroy();
                chartCanales = null;
            }

            chartIngresos = new Chart(canvasIngresos, {
                type: 'bar',
                data: {
                    labels: diasOrdenados.length ? diasOrdenados : ['Sin datos'],
                    datasets: [{
                        label: 'Ingresos S/',
                        data: ingresosOrdenados.length ? ingresosOrdenados : [0],
                        backgroundColor: 'rgba(212, 175, 55, 0.65)',
                        borderColor: 'rgba(212, 175, 55, 1)',
                        borderWidth: 1,
                        maxBarThickness: 80
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    resizeDelay: 120,
                    plugins: {
                        legend: { display: true }
                    },
                    scales: {
                        x: {
                            ticks: { maxRotation: 0, autoSkip: true }
                        },
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            chartCategorias = new Chart(canvasCategorias, {
                type: 'doughnut',
                data: {
                    labels: categorias.length ? categorias : ['Sin datos'],
                    datasets: [{
                        label: 'Reservas',
                        data: cantidadesCategoria.length ? cantidadesCategoria : [1]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    resizeDelay: 120,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { boxWidth: 16 }
                        }
                    }
                }
            });

            chartCanales = new Chart(canvasCanales, {
                type: 'bar',
                data: {
                    labels: canales.length ? canales : ['Sin datos'],
                    datasets: [{
                        label: 'Reservas por canal',
                        data: cantidadesCanal.length ? cantidadesCanal : [0],
                        backgroundColor: 'rgba(22, 160, 106, 0.65)',
                        borderColor: 'rgba(22, 160, 106, 1)',
                        borderWidth: 1,
                        maxBarThickness: 80
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    resizeDelay: 120,
                    plugins: {
                        legend: { display: true }
                    },
                    scales: {
                        x: {
                            ticks: { maxRotation: 0, autoSkip: false }
                        },
                        y: {
                            beginAtZero: true,
                            precision: 0
                        }
                    }
                }
            });
        }

        // --- Eliminar Reserva Modificado ---
        function eliminarReserva(id) {
            if (!ES_ADMIN) {
                mostrarNotificacion('Solo el administrador puede eliminar reservas.', 'error');
                return;
            }
            const password = prompt('Ingrese la contraseña para eliminar esta reserva:');
            if (password !== 'admin123') {
                mostrarNotificacion('Contraseña incorrecta', 'error');
                return;
            }

            if (confirm(`¿Estás completamente seguro de ELIMINAR la reserva #ADS-${id}? Esta acción no se puede deshacer.`)) {
                fetch(`../api/reservas.php?id=${id}`, { method: 'DELETE' })
                    .then(res => res.json())
                    .then(data => {
                        cargarReservas(true);
                        mostrarNotificacion('¡Registro eliminado correctamente!', 'success');
                    })
                    .catch(err => {
                        mostrarNotificacion('Error al intentar eliminar', 'error');
                    });
            }
        }

        function actualizarDashboard(datos) {
            datos = Array.isArray(datos) ? datos : [];
            const setText = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.innerText = value;
            };

            setText('stat-pendientes', datos.filter(r => String(r.estado_reserva || '') === 'Pendiente').length);
            setText('stat-atendidas', datos.filter(r => String(r.estado_reserva || '') === 'Atendida').length);
            setText('stat-confirmadas', datos.filter(r => String(r.estado_reserva || '') === 'Confirmada').length);
            setText('stat-culminadas', datos.filter(r => String(r.estado_reserva || '') === 'Culminada').length);
            setText('stat-canceladas', datos.filter(r => String(r.estado_reserva || '') === 'Cancelada').length);
            setText('stat-total', datos.length);
            const ingresosRealizados = datos
                .filter(r => ['Confirmada', 'Culminada'].includes(String(r.estado_reserva || '')))
                .reduce((sum, r) => sum + (parseFloat(r.precio_final) || 0), 0);
            setText('stat-reportes', 'S/ ' + ingresosRealizados.toFixed(2));
        }

        function toggleCampoHabitacion() {
            const estado = document.getElementById('cli-estado-reserva').value;
            const grupo = document.getElementById('grupo-habitacion-asignada');
            const mostrar = GESTION_POR_HABITACION && estado === 'Confirmada';
            if (grupo) grupo.style.display = mostrar ? 'block' : 'none';
            document.querySelectorAll('#cli-habitaciones-lista input').forEach(el => el.disabled = !mostrar);
            if (!mostrar) {
                document.querySelectorAll('.hab-check').forEach(ch => ch.checked = false);
                actualizarResumenHabitaciones();
            }
        }


        function actualizarNotasPorEstado() {
            const estado = document.getElementById('cli-estado-reserva')?.value || '';
            const notas = document.getElementById('cli-notas');
            if (!notas) return;
            if (estado === 'Cancelada') {
                notas.placeholder = 'Motivo de cancelación: cliente no respondió, cambió de fecha, encontró otra opción...';
            } else if (estado === 'Atendida') {
                notas.placeholder = 'Añade observaciones de seguimiento: se contactó por WhatsApp, espera respuesta, solicita promoción...';
            } else {
                notas.placeholder = 'Añade notas para recepción...';
            }
        }

        function actualizarResumenHabitaciones() {
            const cards = Array.from(document.querySelectorAll('.habitacion-check-card'));
            const seleccionadas = cards.filter(card => card.querySelector('.hab-check')?.checked);
            const registro = todasLasReservas.find(r => r.id_reserva == reservaActualId) || {};
            const noches = Math.max(1, Math.round((new Date(registro.fecha_checkout) - new Date(registro.fecha_checkin)) / 86400000));
            let totalNoche = 0;
            let hayAjuste = false;
            seleccionadas.forEach(card => {
                const original = Number(card.dataset.precioOriginal || 0);
                const aplicado = Number(card.querySelector('.hab-precio')?.value || original);
                totalNoche += aplicado;
                if (Math.abs(original - aplicado) > 0.009) hayAjuste = true;
                const motivo = card.querySelector('.habitacion-motivo');
                if (motivo) motivo.classList.toggle('visible', Math.abs(original - aplicado) > 0.009);
            });
            const box = document.getElementById('resumen-precios-habitaciones');
            if (!box) return;
            if (!seleccionadas.length) { box.style.display='none'; box.innerHTML=''; return; }
            const total = totalNoche * noches;
            box.style.display='block';
            box.innerHTML = '<strong>Seleccionadas: '+seleccionadas.length+'</strong><br>Tarifa por noche: S/ '+totalNoche.toFixed(2)+'<br>Noches: '+noches+'<br>Total calculado: <strong>S/ '+total.toFixed(2)+'</strong>'+(hayAjuste?'<br><span style="color:#b45309">Hay precios modificados; debes indicar el motivo.</span>':'');
        }

        async function cargarHabitacionesLibresReserva(idHabitacionActual = null) {
            if (!GESTION_POR_HABITACION) return;
            const registro = todasLasReservas.find(r => r.id_reserva == reservaActualId) || {};
            const cantidad = Math.max(1, Number(registro.cantidad_habitaciones || 1));
            const ayuda = document.getElementById('cli-cantidad-ayuda');
            if (ayuda) ayuda.textContent = '(selecciona ' + cantidad + ')';
            const idCategoria = document.getElementById('cli-categoria').disabled ? registro.id_categoria : document.getElementById('cli-categoria').value;
            const lista = document.getElementById('cli-habitaciones-lista');
            const alerta = document.getElementById('alerta-categorias-superiores');
            if (!lista || !idCategoria || !registro.fecha_checkin || !registro.fecha_checkout) return;
            lista.innerHTML = '<div class="habitacion-cargando">Cargando habitaciones...</div>';
            if (alerta) { alerta.style.display='none'; alerta.innerHTML=''; }
            try {
                const params = new URLSearchParams({
                    id_hotel: String(registro.id_hotel || ''), id_categoria: String(idCategoria),
                    fecha_checkin: registro.fecha_checkin, fecha_checkout: registro.fecha_checkout,
                    id_reserva: String(reservaActualId || ''), cantidad_habitaciones: String(cantidad)
                });
                const res = await fetch('../api/disponibilidad.php?' + params.toString(), { cache: 'no-store' });
                const data = await res.json();
                const habitaciones = Array.isArray(data.habitaciones) ? data.habitaciones : [];
                lista.innerHTML = '';
                const superiores = habitaciones.filter(h => Number(h.id_categoria_principal) !== Number(idCategoria));
                if (alerta && superiores.length) {
                    alerta.style.display='block';
                    alerta.innerHTML='<i class="fa-solid fa-triangle-exclamation"></i> Solo algunas habitaciones corresponden a la categoría solicitada. Las categorías superiores se cobrarán inicialmente a su tarifa real; recepción puede negociar y modificar el precio dejando un motivo.';
                }
                habitaciones.forEach(h => {
                    const categoria = String(h.categoria_principal || '').replace(/^Habitación\s+/i,'').trim();
                    const precio = Number(h.precio_principal || 0);
                    const superior = Number(h.id_categoria_principal) !== Number(idCategoria);
                    const card = document.createElement('label');
                    card.className='habitacion-check-card';
                    card.dataset.id=String(h.id_habitacion);
                    card.dataset.precioOriginal=String(precio);
                    card.innerHTML=`<input class="hab-check" type="checkbox" value="${h.id_habitacion}">
                        <div class="habitacion-info"><strong>Hab. ${h.numero_habitacion} — ${categoria || 'Sin categoría'}</strong><small>${superior ? 'Categoría superior; se usará para cubrir la solicitud.' : 'Coincide con la categoría solicitada.'}<br>Precio normal: S/ ${precio.toFixed(2)} por noche</small></div>
                        <div class="habitacion-precio"><label>Precio aplicado</label><input class="hab-precio" type="number" min="0" step="0.01" value="${precio.toFixed(2)}" disabled></div>
                        <div class="habitacion-motivo"><input class="hab-motivo" type="text" maxlength="255" placeholder="Motivo del descuento o ajuste de precio"></div>`;
                    const check=card.querySelector('.hab-check'), input=card.querySelector('.hab-precio');
                    check.addEventListener('change',()=>{ input.disabled=!check.checked; if(document.querySelectorAll('.hab-check:checked').length>cantidad){check.checked=false;input.disabled=true;mostrarNotificacion('Solo debes seleccionar '+cantidad+' habitación(es).','error');} actualizarResumenHabitaciones(); });
                    input.addEventListener('input',actualizarResumenHabitaciones);
                    lista.appendChild(card);
                });
                if (!habitaciones.length) lista.innerHTML='<div class="habitacion-cargando">No hay habitaciones libres.</div>';
                if (idHabitacionActual) {
                    const chk=lista.querySelector('.hab-check[value="'+idHabitacionActual+'"]');
                    if(chk){chk.checked=true;chk.closest('.habitacion-check-card').querySelector('.hab-precio').disabled=false;}
                }
                actualizarResumenHabitaciones();
            } catch (e) {
                lista.innerHTML='<div class="habitacion-cargando">Error al cargar habitaciones.</div>';
                mostrarNotificacion('No se pudo cargar habitaciones libres.','error');
            }
        }

        async function tomarReservaParaEdicion(id) {
            try {
                const res = await fetch(`../api/reservas.php?id=${id}&action=tomar`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({})
                });
                const data = await res.json();
                if (!res.ok) {
                    mostrarNotificacion(data.error || 'Esta reserva ya está siendo atendida por otro usuario.', 'error');
                    cargarReservas(true);
                    return false;
                }
                return true;
            } catch (e) {
                console.error('Error al tomar reserva:', e);
                mostrarNotificacion('No se pudo bloquear la reserva para edición.', 'error');
                return false;
            }
        }

        async function liberarReservaTomada(id = null) {
            const reservaId = id || reservaActualId;
            if (!reservaId) return;
            try {
                await fetch(`../api/reservas.php?id=${reservaId}&action=liberar`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({})
                });
            } catch (e) {
                console.warn('No se pudo liberar la reserva tomada:', e);
            }
        }

        async function abrirModalAtender(id) {
            const r = todasLasReservas.find(x => x.id_reserva == id);
            if (!r) { mostrarNotificacion('Registro no encontrado', 'error'); return; }
            const tomada = await tomarReservaParaEdicion(id);
            if (!tomada) return;
            reservaActualId = id; modoModal = 'atender';
            document.getElementById('modal-titulo').innerHTML = `Atender Reserva #${id} <i class="fa-solid fa-xmark modal-close-icon" onclick="cerrarModal()"></i>`;
            // Prefill con datos existentes, pero limpiar nombres/apellidos para el flujo Por Atender
            document.getElementById('cli-nombres').value = '';
            document.getElementById('cli-apellidos').value = '';
            document.getElementById('cli-dni').value = r.dni !== '---' ? r.dni : '';
            document.getElementById('cli-codigo-pais').value = r.telefono ? r.telefono.match(/^\+\d+/) ? r.telefono.match(/^\+\d+/)[0] : '+51' : '+51';
            document.getElementById('cli-telefono').value = r.telefono ? r.telefono.replace(/^\+\d+\s*/, '') : '';
            document.getElementById('cli-email').value = r.email || '';
            document.getElementById('cli-estado-reserva').value = 'Atendida';
            configurarOpcionesEstado(r);
            document.getElementById('cli-canal-reserva').value = r.canal_reserva || 'Web';
            document.getElementById('cli-categoria').value = r.id_categoria;
            document.getElementById('cli-habitaciones-lista').innerHTML = '<div class="habitacion-cargando">Seleccione una categoría para cargar habitaciones.</div>';
            // Habilitar SOLO los campos permitidos para "Por Atender"
            document.getElementById('cli-nombres').disabled = false;
            document.getElementById('cli-apellidos').disabled = false;
            document.getElementById('cli-dni').disabled = false;
            document.getElementById('cli-codigo-pais').disabled = false;
            document.getElementById('cli-telefono').disabled = false;
            document.getElementById('cli-email').disabled = false;
            document.getElementById('cli-estado-reserva').disabled = false;
            document.getElementById('cli-canal-reserva').disabled = false;
            document.getElementById('cli-notas').disabled = false;
            // Mantener categoría bloqueada en este flujo
            document.getElementById('cli-categoria').disabled = true;
            document.querySelectorAll('#cli-habitaciones-lista input').forEach(el=>el.disabled=true);
            document.getElementById('cli-notas').value = r.notas || '';
            modalCamposDesbloqueados = false;
            toggleCampoHabitacion();
            actualizarNotasPorEstado();
            cargarHabitacionesLibresReserva(r.id_habitacion || null);
            verificarClienteNoGrato();
            mostrarModal();
        }

        async function abrirModalEditar(id) {
            const r = todasLasReservas.find(x => x.id_reserva == id);
            if (!r) { mostrarNotificacion('Registro no encontrado', 'error'); return; }
            const tomada = await tomarReservaParaEdicion(id);
            if (!tomada) return;
            reservaActualId = id; modoModal = 'editar';
            const soloEdicionMinima = r.estado_reserva === 'Atendida';
            document.getElementById('modal-titulo').innerHTML = `Editar Reserva #${id} <i class="fa-solid fa-xmark modal-close-icon" onclick="cerrarModal()"></i>`;
            document.getElementById('cli-nombres').value = r.cli_nombres || r.cliente.split(' ')[0] || '';
            document.getElementById('cli-apellidos').value = r.cli_apellidos || r.cliente.split(' ').slice(1).join(' ') || '';
            document.getElementById('cli-dni').value = r.dni !== '---' ? r.dni : '';
            document.getElementById('cli-codigo-pais').value = r.telefono ? r.telefono.match(/^\+\d+/) ? r.telefono.match(/^\+\d+/)[0] : '+51' : '+51';
            document.getElementById('cli-telefono').value = r.telefono ? r.telefono.replace(/^\+\d+\s*/, '') : '';
            document.getElementById('cli-email').value = r.email || '';
            document.getElementById('cli-estado-reserva').value = r.estado_reserva || 'Confirmada';
            configurarOpcionesEstado(r);
            document.getElementById('cli-canal-reserva').value = r.canal_reserva || 'Web';
            document.getElementById('cli-categoria').value = r.id_categoria;
            
            document.getElementById('cli-notas').value = r.notas || '';

            document.getElementById('cli-nombres').disabled = soloEdicionMinima;
            document.getElementById('cli-apellidos').disabled = soloEdicionMinima;
            document.getElementById('cli-dni').disabled = soloEdicionMinima;
            document.getElementById('cli-codigo-pais').disabled = soloEdicionMinima;
            document.getElementById('cli-telefono').disabled = soloEdicionMinima;
            document.getElementById('cli-email').disabled = soloEdicionMinima;
            document.getElementById('cli-categoria').disabled = soloEdicionMinima;
            document.querySelectorAll('#cli-habitaciones-lista input').forEach(el=>el.disabled=soloEdicionMinima);
            document.getElementById('cli-estado-reserva').disabled = false;
            document.getElementById('cli-canal-reserva').disabled = false;
            document.getElementById('cli-notas').disabled = false;
            modalCamposDesbloqueados = true;
            toggleCampoHabitacion();
            actualizarNotasPorEstado();
            cargarHabitacionesLibresReserva(r.id_habitacion || null);
            verificarClienteNoGrato();
            mostrarModal();
        }

        function configurarOpcionesEstado(registroActual = {}) {
            const selectEstado = document.getElementById('cli-estado-reserva');
            const opcionCancelada = selectEstado?.querySelector('option[value="Cancelada"]');
            if (!selectEstado || !opcionCancelada) return;

            // Regla solicitada: Cancelada solo debe aparecer cuando la reserva ya está en Atendida.
            // En Por Atender no se muestra para evitar cancelar consultas nuevas desde ese flujo.
            const mostrarCancelada = modoModal === 'editar' && String(registroActual.estado_reserva || '') === 'Atendida';
            opcionCancelada.hidden = !mostrarCancelada;
            opcionCancelada.disabled = !mostrarCancelada;

            if (!mostrarCancelada && selectEstado.value === 'Cancelada') {
                selectEstado.value = 'Atendida';
            }
        }

        function mostrarModal() {
            const botonDesbloquear = document.getElementById('btn-desbloquear-modal');
            const registroActual = todasLasReservas.find(r => r.id_reserva == reservaActualId) || {};
            configurarOpcionesEstado(registroActual);
            botonDesbloquear.style.display = (ES_ADMIN && modoModal === 'editar' && registroActual.estado_reserva === 'Atendida') ? 'inline-flex' : 'none';
            botonDesbloquear.disabled = false;
            botonDesbloquear.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Editar campos';
            document.getElementById('modal-overlay').style.display = 'block';
            const modal = document.getElementById('modal-atencion');
            modal.style.display = 'block';
            setTimeout(() => modal.classList.add('show'), 10);
            setTimeout(() => document.getElementById('cli-nombres').focus(), 150);
            ocultarErrores();
        }

        function cerrarModal(liberar = true) {
            const reservaParaLiberar = reservaActualId;
            if (liberar && reservaParaLiberar) liberarReservaTomada(reservaParaLiberar);
            document.getElementById('modal-overlay').style.display = 'none';
            const modal = document.getElementById('modal-atencion');
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 200);
            document.querySelectorAll('#modal-atencion input, #modal-atencion textarea, #modal-atencion select').forEach(el => el.value = '');
            document.getElementById('cli-notas').style.borderColor = '';
            reservaActualId = null;
        }

        function ocultarErrores() { document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none'); }

        let clienteNoGratoActual = null;
        let timerNoGrato = null;
        function rellenarClienteDesdeBD(cliente) {
            if (!cliente) return;
            const nombres = document.getElementById('cli-nombres');
            const apellidos = document.getElementById('cli-apellidos');
            const telefono = document.getElementById('cli-telefono');
            const email = document.getElementById('cli-email');

            if (nombres && !nombres.disabled) nombres.value = cliente.nombres || nombres.value || '';
            if (apellidos && !apellidos.disabled) apellidos.value = cliente.apellidos || apellidos.value || '';

            if (telefono && !telefono.disabled && cliente.telefono) {
                const limpio = String(cliente.telefono).replace(/^\+?51\s*/,'').trim();
                telefono.value = limpio || telefono.value;
            }
            if (email && !email.disabled && cliente.email) email.value = cliente.email;
        }

        async function buscarClientePorDni(dni) {
            try {
                const res = await fetch('../api/clientes_buscar.php?dni=' + encodeURIComponent(dni), { cache: 'no-store' });
                const data = await res.json();
                if (res.ok && data.encontrado && data.cliente) {
                    rellenarClienteDesdeBD(data.cliente);
                    mostrarNotificacion('Cliente encontrado. Datos cargados automáticamente.', 'success');
                    return data.cliente;
                }
            } catch (e) {}
            return null;
        }

        async function verificarClienteNoGrato() {
            const dniInput = document.getElementById('cli-dni');
            const alerta = document.getElementById('alerta-no-grato');
            if (!dniInput || !alerta) return null;
            const dni = dniInput.value.trim();
            clienteNoGratoActual = null;
            alerta.style.display = 'none';
            alerta.innerHTML = '';
            if (dni.length !== 8) return null;

            buscarClientePorDni(dni);

            try {
                const res = await fetch('../api/clientes_no_gratos.php?dni=' + encodeURIComponent(dni), { cache: 'no-store' });
                const data = await res.json();
                if (res.ok && data.encontrado && data.cliente) {
                    clienteNoGratoActual = data.cliente;
                    alerta.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Cliente no grato: ' +
                        (data.cliente.nombres || '') + ' ' + (data.cliente.apellidos || '') +
                        '<br>Motivo: ' + (data.cliente.motivo || 'Sin motivo registrado');
                    alerta.style.display = 'block';
                    mostrarNotificacion('Cliente no grato detectado. Revisa el motivo antes de continuar.', 'error');
                }
                return clienteNoGratoActual;
            } catch (e) {
                return null;
            }
        }

        // --- Guardar Atención Modificado ---
        function guardarAtencion() {
            ocultarErrores(); let hayError = false;
            const registroActual = todasLasReservas.find(r => r.id_reserva == reservaActualId) || {};
            const nombres = document.getElementById('cli-nombres').value.trim();
            const apellidos = document.getElementById('cli-apellidos').value.trim();
            const dni = document.getElementById('cli-dni').value.trim();
            const codigoPais = document.getElementById('cli-codigo-pais').value;
            const telefono = document.getElementById('cli-telefono').value.trim();
            const email = document.getElementById('cli-email').value.trim();
            const estadoReserva = document.getElementById('cli-estado-reserva').value;
            const canal_reserva = document.getElementById('cli-canal-reserva').value;
            const id_categoria = document.getElementById('cli-categoria').disabled
                ? registroActual.id_categoria
                : document.getElementById('cli-categoria').value;
            const notas = document.getElementById('cli-notas').value.trim();
            const cardsSeleccionadas=Array.from(document.querySelectorAll('.habitacion-check-card')).filter(card=>card.querySelector('.hab-check')?.checked);
            const detalles_habitaciones=cardsSeleccionadas.map(card=>({
                id_habitacion:Number(card.dataset.id),
                precio_original:Number(card.dataset.precioOriginal||0),
                precio_aplicado:Number(card.querySelector('.hab-precio')?.value||0),
                motivo_ajuste:String(card.querySelector('.hab-motivo')?.value||'').trim()
            }));
            const id_habitaciones=detalles_habitaciones.map(d=>d.id_habitacion);
            const id_habitacion=id_habitaciones[0]||'';
            const ajusteSinMotivo=detalles_habitaciones.some(d=>Math.abs(d.precio_original-d.precio_aplicado)>0.009 && !d.motivo_ajuste);
            if(ajusteSinMotivo){mostrarNotificacion('Escribe el motivo del descuento o ajuste de precio.','error');return;}

            if (modoModal === 'atender' && estadoReserva === 'Cancelada') {
                document.getElementById('err-estado').style.display = 'flex';
                mostrarNotificacion('Solo puedes cancelar una reserva cuando ya está en Atendida.', 'error');
                hayError = true;
            }

            const cantidadRequerida=Math.max(1,Number(registroActual.cantidad_habitaciones||1));
            if (GESTION_POR_HABITACION && estadoReserva === 'Confirmada' && id_habitaciones.length!==cantidadRequerida) {
                document.getElementById('err-habitacion').style.display = 'flex';
                mostrarNotificacion('Debes seleccionar exactamente '+cantidadRequerida+' habitación(es).', 'error');
                hayError = true;
            }

            if (modoModal === 'atender' && !modalCamposDesbloqueados) {
                if (estadoReserva !== 'Atendida') {
                    document.getElementById('err-estado').style.display = 'flex';
                    hayError = true;
                }
                if (!nombres) { document.getElementById('err-nombres').style.display = 'flex'; hayError = true; }
                if (!apellidos) { document.getElementById('err-apellidos').style.display = 'flex'; hayError = true; }
                if (dni.length !== 8) { document.getElementById('err-dni').style.display = 'flex'; hayError = true; }
                if (!telefono) { document.getElementById('err-telefono').style.display = 'flex'; hayError = true; }
                if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { document.getElementById('err-email').style.display = 'flex'; hayError = true; }
                if (estadoReserva === 'Cancelada' && !notas) {
                    document.getElementById('cli-notas').style.borderColor = 'var(--danger)';
                    mostrarNotificacion('Las notas son obligatorias al cancelar una reserva', 'error');
                    hayError = true;
                }
            } else {
                if (!nombres) { document.getElementById('err-nombres').style.display = 'flex'; hayError = true; }
                if (!apellidos) { document.getElementById('err-apellidos').style.display = 'flex'; hayError = true; }
                if (dni.length !== 8) { document.getElementById('err-dni').style.display = 'flex'; hayError = true; }
                if (!telefono) { document.getElementById('err-telefono').style.display = 'flex'; hayError = true; }
                if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { document.getElementById('err-email').style.display = 'flex'; hayError = true; }
                if (estadoReserva === 'Cancelada' && !notas) {
                    document.getElementById('cli-notas').style.borderColor = 'var(--danger)';
                    mostrarNotificacion('Las notas son obligatorias al cancelar una reserva', 'error');
                    hayError = true;
                }
            }


            if (clienteNoGratoActual && clienteNoGratoActual.dni === dni) {
                mostrarNotificacion('No se puede guardar: el DNI figura como cliente no grato.', 'error');
                return;
            }

            if (hayError) return;

            const telefonoCompleto = `${codigoPais} ${telefono}`;

            const accion = modoModal === 'atender' ? 'atender' : 'editar';
            const url_php = `../api/reservas.php?id=${reservaActualId}&action=${accion}`;

            fetch(url_php, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nombres, apellidos, dni, telefono: telefonoCompleto, email, id_categoria, id_habitacion, id_habitaciones, detalles_habitaciones, notas, estado_reserva: estadoReserva, canal_reserva })
            })
                .then(async res => {
                    const texto = await res.text();
                    let data = {};
                    try {
                        data = texto ? JSON.parse(texto) : {};
                    } catch (e) {
                        console.error('Respuesta no JSON al guardar:', texto);
                        data = { error: texto.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() || 'El servidor devolvió una respuesta inválida.' };
                    }
                    if (!res.ok) {
                        mostrarNotificacion(data.error || 'Error al intentar guardar los datos', 'error');
                        return null;
                    }
                    return data;
                })
                .then(result => {
                    if (!result) return;
                    const mantenerSeguimiento = estadoReserva === 'Atendida';
                    cerrarModal(!mantenerSeguimiento);
                    cargarReservas(true);

                    let mensajeExito = '';
                    if (estadoReserva === 'Confirmada') {
                        mensajeExito = '¡Reserva confirmada correctamente!';
                    } else if (estadoReserva === 'Cancelada') {
                        mensajeExito = '¡Reserva cancelada!';
                    } else if (estadoReserva === 'Atendida') {
                        mensajeExito = '¡Reserva atendida!';
                    }
                    mostrarNotificacion(mensajeExito, 'success');
                })
                .catch(error => {
                    console.error('Error guardarAtencion:', error);
                    mostrarNotificacion('Error al intentar guardar los datos', 'error');
                });
        }

        function solicitarDesbloqueoModal() {
            if (!ES_ADMIN) {
                mostrarNotificacion('Solo el administrador puede desbloquear todos los campos.', 'error');
                return;
            }
            const password = prompt('Ingrese la contraseña para desbloquear todos los campos:');
            if (password !== 'admin123') {
                mostrarNotificacion('Contraseña incorrecta', 'error');
                return;
            }
            ['cli-nombres', 'cli-apellidos', 'cli-dni', 'cli-codigo-pais', 'cli-telefono', 'cli-email', 'cli-canal-reserva', 'cli-notas'].forEach(id => {
                document.getElementById(id).disabled = false;
            });
            // La categoría y las habitaciones asignadas quedan bloqueadas después de Atendida.
            // Cambiarlas podría romper la disponibilidad y el historial de la reserva.
            document.getElementById('cli-categoria').disabled = true;
            document.querySelectorAll('#cli-habitaciones-lista input').forEach(el=>el.disabled=true);
            modalCamposDesbloqueados = true;
            const boton = document.getElementById('btn-desbloquear-modal');
            boton.disabled = true;
            boton.innerHTML = '<i class="fa-solid fa-lock-open"></i> Desbloqueado';
            mostrarNotificacion('Datos del cliente desbloqueados. La categoría y la habitación permanecen fijas.', 'success');
        }

        window.addEventListener('beforeunload', () => {
            if (reservaActualId) {
                try {
                    fetch(`../api/reservas.php?id=${reservaActualId}&action=liberar`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: '{}',
                        keepalive: true
                    });
                } catch (e) {}
            }
        });

        document.getElementById('cli-estado-reserva').addEventListener('change', () => { toggleCampoHabitacion(); actualizarNotasPorEstado(); cargarHabitacionesLibresReserva(null); });
        document.getElementById('cli-categoria').addEventListener('change', () => { cargarHabitacionesLibresReserva(null); });
        document.getElementById('cli-dni').addEventListener('input', () => {
            clearTimeout(timerNoGrato);
            timerNoGrato = setTimeout(verificarClienteNoGrato, 450);
        });

        document.getElementById('modal-atencion').addEventListener('keydown', e => {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') { e.preventDefault(); guardarAtencion(); }
        });

        inicializarFiltroMesAnio();
        cargarReservas();
    </script>
</body>

</html>
<style>#toast-container,.toast-container{z-index:2147483647!important;position:fixed!important}</style>
