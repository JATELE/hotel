<?php
require_once '../config/Database.php';
require_once '../config/auth.php';

requerir_login(['admin_hotel','admin']);

$db = (new Database())->connect();
$idHotel = id_hotel_actual();

/*
  Reporte corregido:
  - Evita duplicar reservas por el LEFT JOIN con sesiones usando subconsultas.
  - Agrega botón "Ver sesiones" por cada recepcionista.
  - Agrega botón "Ver reservas" por cada recepcionista.
  - Muestra historial de sesiones en modal sin IP ni navegador para mantenerlo limpio.
  - Muestra historial de reservas confirmadas en modal.
*/

$sql = "
SELECT 
    u.id_usuario,
    TRIM(CONCAT(u.nombres,' ',IFNULL(u.apellidos,''))) AS recepcionista,
    u.usuario,
    u.estado,
    IFNULL(res.reservas_confirmadas, 0) AS reservas_confirmadas,
    IFNULL(res.importe_confirmado, 0) AS importe_confirmado,
    ses.ultimo_login,
    ses.ultimo_logout,
    IFNULL(ses.total_sesiones, 0) AS total_sesiones
FROM usuarios u
LEFT JOIN (
    SELECT 
        id_usuario_confirmacion,
        COUNT(id_reserva) AS reservas_confirmadas,
        SUM(precio_final) AS importe_confirmado
    FROM reservas
    WHERE id_hotel = ?
      AND estado_reserva = 'Confirmada'
      AND id_usuario_confirmacion IS NOT NULL
    GROUP BY id_usuario_confirmacion
) res ON res.id_usuario_confirmacion = u.id_usuario
LEFT JOIN (
    SELECT 
        id_usuario,
        MAX(fecha_login) AS ultimo_login,
        MAX(fecha_logout) AS ultimo_logout,
        COUNT(id_sesion) AS total_sesiones
    FROM usuario_sesiones
    WHERE id_hotel = ?
    GROUP BY id_usuario
) ses ON ses.id_usuario = u.id_usuario
WHERE u.id_hotel = ?
  AND u.id_rol = 3
GROUP BY u.id_usuario
ORDER BY reservas_confirmadas DESC, u.id_usuario DESC
";

$stmt = $db->prepare($sql);
$stmt->execute([$idHotel, $idHotel, $idHotel]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sqlSesiones = "
SELECT 
    us.id_sesion,
    us.id_usuario,
    us.fecha_login,
    us.fecha_logout,
    CASE
        WHEN us.fecha_logout IS NULL THEN 'Activa o sin cerrar'
        ELSE 'Cerrada'
    END AS estado_sesion
FROM usuario_sesiones us
INNER JOIN usuarios u ON u.id_usuario = us.id_usuario
WHERE us.id_hotel = ?
  AND u.id_rol = 3
ORDER BY us.fecha_login DESC
";
$stmtSesiones = $db->prepare($sqlSesiones);
$stmtSesiones->execute([$idHotel]);
$sesiones = $stmtSesiones->fetchAll(PDO::FETCH_ASSOC);

$sqlReservas = "
SELECT
    r.id_reserva,
    r.id_usuario_confirmacion,
    r.fecha_registro,
    r.fecha_checkin,
    r.fecha_checkout,
    r.precio_final,
    r.estado_reserva,
    r.canal_reserva,
    c.nombre AS categoria,
    TRIM(CONCAT(IFNULL(cl.nombres,''),' ',IFNULL(cl.apellidos,''))) AS cliente,
    cl.documento_identidad AS dni
FROM reservas r
LEFT JOIN categorias c ON c.id_categoria = r.id_categoria
LEFT JOIN clientes cl ON cl.id_cliente = r.id_cliente
INNER JOIN usuarios u ON u.id_usuario = r.id_usuario_confirmacion
WHERE r.id_hotel = ?
  AND r.estado_reserva = 'Confirmada'
  AND u.id_rol = 3
ORDER BY r.fecha_registro DESC
";
$stmtReservas = $db->prepare($sqlReservas);
$stmtReservas->execute([$idHotel]);
$reservas = $stmtReservas->fetchAll(PDO::FETCH_ASSOC);

$totalConfirmadas = array_sum(array_map(fn($r) => (int)$r['reservas_confirmadas'], $rows));
$totalImporte = array_sum(array_map(fn($r) => (float)$r['importe_confirmado'], $rows));
$totalSesiones = array_sum(array_map(fn($r) => (int)$r['total_sesiones'], $rows));

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function formatearFecha($valor) {
    if (!$valor) return '-';
    return date('Y-m-d H:i:s', strtotime($valor));
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reportes recepción | CRM Hoteles</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{
    --nav:#162330;
    --nav2:#24384c;
    --gold:#d4af37;
    --gold-soft:#fff6dd;
    --bg:#f4f7fb;
    --text:#13202b;
    --muted:#64748b;
    --line:#e6edf4;
    --green:#16a06a;
    --green-soft:#ecfdf5;
    --blue:#2563eb;
    --blue-soft:#eff6ff;
    --red:#ef4444;
    --red-soft:#fef2f2;
    --shadow:0 18px 50px rgba(15,23,42,.08);
}
*{box-sizing:border-box}
body{
    font-family:Poppins,Arial,sans-serif;
    background:linear-gradient(180deg,#eef4fa 0,#f7f8fb 45%,#f8fafc 100%);
    margin:0;
    color:var(--text);
}
.header{
    background:linear-gradient(135deg,var(--nav),var(--nav2));
    color:white;
    padding:18px 26px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:18px;
    box-shadow:0 12px 30px rgba(15,23,42,.15);
    position:sticky;
    top:0;
    z-index:10;
}
.brand{
    font-weight:800;
    display:flex;
    align-items:center;
    gap:12px;
}
.brand i{
    color:var(--gold);
    font-size:24px;
}
.brand small{
    display:block;
    color:#b8c6d3;
    font-size:12px;
    margin-top:3px;
    font-weight:600;
}
.nav{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.nav a{
    background:#fff;
    color:#162330;
    text-decoration:none;
    font-weight:800;
    border-radius:12px;
    padding:10px 14px;
    transition:.2s ease;
}
.nav a:hover{
    transform:translateY(-1px);
    box-shadow:0 10px 22px rgba(0,0,0,.16);
}
.nav a.gold{
    background:var(--gold);
}
.wrap{
    max-width:1220px;
    margin:28px auto;
    padding:0 18px;
}
.hero{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:20px;
    margin-bottom:18px;
}
.hero h1{
    margin:0;
    font-size:30px;
}
.hero p{
    margin:6px 0 0;
    color:var(--muted);
    font-weight:500;
}
.stats{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
    margin:20px 0;
}
.stat{
    background:white;
    border:1px solid var(--line);
    border-radius:20px;
    padding:20px;
    box-shadow:0 12px 30px rgba(15,23,42,.05);
    position:relative;
    overflow:hidden;
}
.stat:after{
    content:"";
    position:absolute;
    right:-35px;
    top:-35px;
    width:90px;
    height:90px;
    border-radius:50%;
    background:rgba(212,175,55,.15);
}
.stat span{
    font-size:12px;
    color:var(--muted);
    font-weight:800;
    text-transform:uppercase;
}
.stat strong{
    display:block;
    font-size:31px;
    margin-top:5px;
}
.card{
    background:white;
    border:1px solid var(--line);
    border-radius:22px;
    padding:22px;
    box-shadow:var(--shadow);
    margin-bottom:20px;
}
.card-title{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    margin-bottom:18px;
}
.card-title h2{
    margin:0;
    font-size:22px;
}
.card-title p{
    margin:4px 0 0;
    color:var(--muted);
    font-size:13px;
}
.table-wrap{
    overflow:auto;
}
table{
    width:100%;
    border-collapse:separate;
    border-spacing:0 10px;
}
th{
    font-size:11px;
    text-transform:uppercase;
    color:#64748b;
    text-align:left;
    padding:0 12px;
    white-space:nowrap;
}
td{
    background:#fbfdff;
    border-top:1px solid var(--line);
    border-bottom:1px solid var(--line);
    padding:14px 12px;
    vertical-align:middle;
}
td:first-child{
    border-left:1px solid var(--line);
    border-radius:14px 0 0 14px;
}
td:last-child{
    border-right:1px solid var(--line);
    border-radius:0 14px 14px 0;
}
.money{
    font-weight:900;
    color:#087344;
}
.badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:7px 10px;
    border-radius:999px;
    font-weight:800;
    font-size:12px;
    background:var(--green-soft);
    color:#166534;
}
.badge.inactivo{
    background:var(--red-soft);
    color:#991b1b;
}
.badge i{
    font-size:9px;
}
.actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.btn{
    border:0;
    border-radius:11px;
    padding:9px 12px;
    font-family:Poppins,Arial;
    font-weight:800;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    gap:7px;
    transition:.2s ease;
    text-decoration:none;
    white-space:nowrap;
}
.btn:hover{
    transform:translateY(-1px);
}
.btn.blue{
    background:var(--blue-soft);
    color:var(--blue);
    border:1px solid #dbeafe;
}
.btn.green{
    background:var(--green-soft);
    color:#047857;
    border:1px solid #bbf7d0;
}
.btn.gray{
    background:#f1f5f9;
    color:#334155;
    border:1px solid #e2e8f0;
}
.empty{
    text-align:center;
    color:var(--muted);
    padding:30px!important;
}
.modal-bg{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(5px);
    z-index:100;
    padding:20px;
}
.modal{
    display:none;
    position:fixed;
    left:50%;
    top:50%;
    transform:translate(-50%,-50%);
    width:min(980px,calc(100% - 32px));
    max-height:84vh;
    overflow:auto;
    background:white;
    border-radius:24px;
    box-shadow:0 28px 70px rgba(15,23,42,.35);
    z-index:101;
    padding:22px;
}
.modal-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:14px;
    border-bottom:1px solid var(--line);
    padding-bottom:14px;
    margin-bottom:14px;
}
.modal-head h3{
    margin:0;
    font-size:22px;
}
.modal-head p{
    margin:5px 0 0;
    color:var(--muted);
    font-size:13px;
}
.close{
    width:38px;
    height:38px;
    border-radius:50%;
    border:0;
    background:#f1f5f9;
    color:#334155;
    cursor:pointer;
    font-size:18px;
}
.close:hover{
    background:#fee2e2;
    color:#dc2626;
}
.mini{
    font-size:12px;
    color:var(--muted);
    max-width:260px;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.session-active{
    color:#047857;
    font-weight:800;
}
.channel{
    display:inline-flex;
    align-items:center;
    gap:6px;
    background:#f8fafc;
    border:1px solid var(--line);
    color:#334155;
    padding:6px 9px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
}
@media(max-width:900px){
    .header,.hero{flex-direction:column;align-items:flex-start}
    .stats{grid-template-columns:1fr}
    .nav{width:100%}
    .nav a{flex:1;text-align:center}
    .card-title{flex-direction:column;align-items:flex-start}
}
</style>
</head>
<body>

<header class="header">
    <div class="brand">
        <i class="fa-solid fa-chart-column"></i>
        <div>
            Reportes de recepción
            <small><?= h($_SESSION['usuario']['hotel'] ?? 'Hotel') ?></small>
        </div>
    </div>
    <nav class="nav">
        <a href="recepcion.php"><i class="fa-solid fa-calendar-check"></i> Reservas</a>
        <a href="recepcionistas.php"><i class="fa-solid fa-users-gear"></i> Recepcionistas</a>
        <a class="gold" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
    </nav>
</header>

<main class="wrap">
    <section class="hero">
        <div>
            <h1>Rendimiento por recepcionista</h1>
            <p>Controla reservas confirmadas, importes y el historial completo de ingreso/salida del equipo.</p>
        </div>
    </section>

    <section class="stats">
        <div class="stat">
            <span>Confirmadas</span>
            <strong><?= (int)$totalConfirmadas ?></strong>
        </div>
        <div class="stat">
            <span>Importe confirmado</span>
            <strong>S/ <?= number_format($totalImporte,2) ?></strong>
        </div>
        <div class="stat">
            <span>Sesiones registradas</span>
            <strong><?= (int)$totalSesiones ?></strong>
        </div>
    </section>

    <section class="card">
        <div class="card-title">
            <div>
                <h2><i class="fa-solid fa-ranking-star"></i> Detalle operativo</h2>
                <p>Usa los botones para ver todas las sesiones y reservas confirmadas de cada recepcionista.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Recepcionista</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>Confirmadas</th>
                        <th>Importe</th>
                        <th>Último ingreso</th>
                        <th>Última salida</th>
                        <th>Sesiones</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                    <tr>
                        <td class="empty" colspan="9">Todavía no hay datos de recepción.</td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach($rows as $r): ?>
                    <tr>
                        <td><b><?= h($r['recepcionista']) ?></b></td>
                        <td><?= h($r['usuario']) ?></td>
                        <td>
                            <span class="badge <?= strtoupper((string)$r['estado']) === 'INACTIVO' ? 'inactivo' : '' ?>">
                                <i class="fa-solid fa-circle"></i><?= h($r['estado']) ?>
                            </span>
                        </td>
                        <td><b><?= (int)$r['reservas_confirmadas'] ?></b></td>
                        <td class="money">S/ <?= number_format((float)$r['importe_confirmado'],2) ?></td>
                        <td><?= h(formatearFecha($r['ultimo_login'])) ?></td>
                        <td><?= h(formatearFecha($r['ultimo_logout'])) ?></td>
                        <td><b><?= (int)$r['total_sesiones'] ?></b></td>
                        <td>
                            <div class="actions">
                                <button class="btn blue" onclick="verSesiones(<?= (int)$r['id_usuario'] ?>, '<?= h($r['recepcionista']) ?>')">
                                    <i class="fa-solid fa-clock-rotate-left"></i> Ver sesiones
                                </button>
                                <button class="btn green" onclick="verReservas(<?= (int)$r['id_usuario'] ?>, '<?= h($r['recepcionista']) ?>')">
                                    <i class="fa-solid fa-bed"></i> Ver reservas
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<div id="modal-bg" class="modal-bg" onclick="cerrarModal()"></div>

<section id="modal-sesiones" class="modal">
    <div class="modal-head">
        <div>
            <h3><i class="fa-solid fa-clock-rotate-left"></i> Historial de sesiones</h3>
            <p id="modal-sesiones-subtitle">Recepcionista</p>
        </div>
        <button class="close" onclick="cerrarModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Ingreso</th>
                    <th>Salida</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="tbody-sesiones"></tbody>
        </table>
    </div>
</section>

<section id="modal-reservas" class="modal">
    <div class="modal-head">
        <div>
            <h3><i class="fa-solid fa-bed"></i> Reservas confirmadas</h3>
            <p id="modal-reservas-subtitle">Recepcionista</p>
        </div>
        <button class="close" onclick="cerrarModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reserva</th>
                    <th>Cliente</th>
                    <th>DNI</th>
                    <th>Categoría</th>
                    <th>Canal</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Importe</th>
                    <th>Registro</th>
                </tr>
            </thead>
            <tbody id="tbody-reservas"></tbody>
        </table>
    </div>
</section>

<script>
const sesiones = <?= json_encode($sesiones, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const reservas = <?= json_encode($reservas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(match) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[match];
    });
}

function abrirModal(id) {
    document.getElementById('modal-bg').style.display = 'block';
    document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
    document.getElementById(id).style.display = 'block';
}

function cerrarModal() {
    document.getElementById('modal-bg').style.display = 'none';
    document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
}

function verSesiones(idUsuario, nombre) {
    const tbody = document.getElementById('tbody-sesiones');
    const lista = sesiones.filter(s => Number(s.id_usuario) === Number(idUsuario));

    document.getElementById('modal-sesiones-subtitle').textContent = nombre;

    if (!lista.length) {
        tbody.innerHTML = `<tr><td class="empty" colspan="3">Este recepcionista todavía no tiene sesiones registradas.</td></tr>`;
        abrirModal('modal-sesiones');
        return;
    }

    tbody.innerHTML = lista.map(s => {
        const salida = s.fecha_logout
            ? escapeHtml(s.fecha_logout)
            : '<span class="session-active">Sesión activa / no cerrada</span>';

        return `
            <tr>
                <td><b>${escapeHtml(s.fecha_login || '-')}</b></td>
                <td>${salida}</td>
                <td><span class="badge ${s.fecha_logout ? '' : 'inactivo'}"><i class="fa-solid fa-circle"></i>${escapeHtml(s.estado_sesion || '-')}</span></td>
            </tr>
        `;
    }).join('');

    abrirModal('modal-sesiones');
}

function verReservas(idUsuario, nombre) {
    const tbody = document.getElementById('tbody-reservas');
    const lista = reservas.filter(r => Number(r.id_usuario_confirmacion) === Number(idUsuario));

    document.getElementById('modal-reservas-subtitle').textContent = nombre;

    if (!lista.length) {
        tbody.innerHTML = `<tr><td class="empty" colspan="9">Este recepcionista todavía no tiene reservas confirmadas.</td></tr>`;
        abrirModal('modal-reservas');
        return;
    }

    tbody.innerHTML = lista.map(r => `
        <tr>
            <td><b>#ADS-${escapeHtml(r.id_reserva)}</b></td>
            <td>${escapeHtml(r.cliente || '-')}</td>
            <td>${escapeHtml(r.dni || '-')}</td>
            <td>${escapeHtml(r.categoria || '-')}</td>
            <td><span class="channel"><i class="fa-solid fa-route"></i>${escapeHtml(r.canal_reserva || 'Sin canal')}</span></td>
            <td>${escapeHtml(r.fecha_checkin || '-')}</td>
            <td>${escapeHtml(r.fecha_checkout || '-')}</td>
            <td class="money">S/ ${Number(r.precio_final || 0).toFixed(2)}</td>
            <td>${escapeHtml(r.fecha_registro || '-')}</td>
        </tr>
    `).join('');

    abrirModal('modal-reservas');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModal();
});
</script>

</body>
</html>
