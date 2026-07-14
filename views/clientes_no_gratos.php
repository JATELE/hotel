<?php
require_once '../config/Database.php';
require_once '../config/auth.php';
requerir_login(['admin_hotel','admin','recepcionista','recepcion']);

$db = (new Database())->connect();
$idHotel = (int)id_hotel_actual();
$usuario = $_SESSION['usuario'] ?? [];
$error = '';
$esRecepcionista = (normalizar_rol($_SESSION['usuario']['rol'] ?? '') === 'recepcionista');

function limpiar($v) { return trim((string)($v ?? '')); }
function volver() { header('Location: clientes_no_gratos.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'crear') {
            $dni = preg_replace('/\D+/', '', limpiar($_POST['dni'] ?? ''));
            $nombres = limpiar($_POST['nombres'] ?? '');
            $apellidos = limpiar($_POST['apellidos'] ?? '');
            $motivo = limpiar($_POST['motivo'] ?? '');
            if ($dni === '' || strlen($dni) < 8 || $nombres === '' || $motivo === '') {
                throw new Exception('DNI, nombres y motivo son obligatorios.');
            }
            $stmt = $db->prepare("INSERT INTO clientes_no_gratos (id_hotel, dni, nombres, apellidos, motivo, estado) VALUES (?, ?, ?, ?, ?, 'ACTIVO')
                                  ON DUPLICATE KEY UPDATE nombres=VALUES(nombres), apellidos=VALUES(apellidos), motivo=VALUES(motivo), estado='ACTIVO'");
            $stmt->execute([$idHotel, $dni, $nombres, $apellidos, $motivo]);
            volver();
        }
        if ($accion === 'estado') {
            $id = (int)($_POST['id_no_grato'] ?? 0);
            $estado = ($_POST['estado'] ?? '') === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO';
            $stmt = $db->prepare("UPDATE clientes_no_gratos SET estado=? WHERE id_no_grato=? AND id_hotel=?");
            $stmt->execute([$estado, $id, $idHotel]);
            volver();
        }
        if ($accion === 'eliminar') {
            if ($esRecepcionista) throw new Exception('Solo el administrador puede eliminar registros.');
            $id = (int)($_POST['id_no_grato'] ?? 0);
            $stmt = $db->prepare("DELETE FROM clientes_no_gratos WHERE id_no_grato=? AND id_hotel=?");
            $stmt->execute([$id, $idHotel]);
            volver();
        }
    } catch (Throwable $e) { $error = $e->getMessage(); }
}

$stmtHotel = $db->prepare("SELECT nombre_comercial FROM hoteles WHERE id_hotel=? LIMIT 1");
$stmtHotel->execute([$idHotel]);
$hotel = $stmtHotel->fetch(PDO::FETCH_ASSOC) ?: [];

$stmt = $db->prepare("SELECT * FROM clientes_no_gratos WHERE id_hotel=? ORDER BY estado ASC, fecha_registro DESC");
$stmt->execute([$idHotel]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Clientes no gratos | CRM Hoteles</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--nav:#162330;--gold:#d4af37;--bg:#f4f7fb;--text:#13202b;--muted:#64748b;--line:#e6edf4;--green:#16a06a;--red:#ef4444;--blue:#2563eb}*{box-sizing:border-box}body{font-family:Poppins,Arial;background:linear-gradient(180deg,#eef4fa 0,#f7f8fb 45%,#f8fafc 100%);margin:0;color:var(--text)}.header{background:linear-gradient(135deg,#162330,#24384c);color:white;padding:18px 26px;display:flex;justify-content:space-between;align-items:center;gap:18px;box-shadow:0 12px 30px #0f172a26;position:sticky;top:0;z-index:10}.brand{font-weight:800;display:flex;align-items:center;gap:12px}.brand i{color:var(--gold);font-size:24px}.brand small{display:block;color:#b8c6d3;font-size:12px;margin-top:3px}.nav{display:flex;gap:10px;flex-wrap:wrap}.nav a{background:#fff;color:#162330;text-decoration:none;font-weight:800;border-radius:12px;padding:10px 14px}.nav a.gold{background:var(--gold)}.wrap{max-width:1180px;margin:28px auto;padding:0 18px}.hero h1{margin:0;font-size:30px}.hero p{margin:6px 0 18px;color:var(--muted);font-weight:500}.grid{display:grid;grid-template-columns:.8fr 1.2fr;gap:18px}.card{background:white;border:1px solid var(--line);border-radius:22px;padding:22px;box-shadow:0 18px 50px #0f172a12;margin-bottom:20px}.field{margin-bottom:12px}.field label{font-size:12px;color:#475569;font-weight:800;text-transform:uppercase}.field input,.field textarea,.field select{width:100%;padding:12px;border:1px solid #dbe4ef;border-radius:12px;font-family:Poppins;background:#fbfdff;outline:none}.field textarea{min-height:92px;resize:vertical}.btn{border:0;border-radius:13px;padding:11px 14px;font-family:Poppins;font-weight:900;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px}.btn.green{background:var(--green);color:white}.btn.red{background:#fee2e2;color:#991b1b}.btn.gray{background:#f1f5f9;color:#334155}.alert{padding:13px 15px;border-radius:14px;margin-bottom:14px;font-weight:700;background:#fee2e2;color:#991b1b}.hint{display:none;margin-top:8px;padding:10px 12px;border-radius:12px;font-size:12px;font-weight:800}.hint.ok{display:block;background:#ecfdf5;color:#047857;border:1px solid #bbf7d0}.hint.info{display:block;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}.hint.bad{display:block;background:#fef2f2;color:#991b1b;border:1px solid #fecaca}.table-wrap{overflow:auto}table{width:100%;border-collapse:separate;border-spacing:0 10px}th{font-size:11px;text-transform:uppercase;color:#64748b;text-align:left;padding:0 12px}td{background:#fbfdff;border-top:1px solid var(--line);border-bottom:1px solid var(--line);padding:13px 12px;vertical-align:top}td:first-child{border-left:1px solid var(--line);border-radius:14px 0 0 14px}td:last-child{border-right:1px solid var(--line);border-radius:0 14px 14px 0}.badge{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border-radius:999px;font-weight:800;font-size:12px;background:#fee2e2;color:#991b1b}.badge.off{background:#f1f5f9;color:#475569}.actions{display:flex;gap:8px;flex-wrap:wrap}@media(max-width:900px){.header,.grid{grid-template-columns:1fr;flex-direction:column;align-items:flex-start}.nav{width:100%}.nav a{flex:1;text-align:center}}
</style></head><body>
<header class="header"><div class="brand"><i class="fa-solid fa-user-slash"></i><div>Clientes no gratos<small><?=htmlspecialchars($hotel['nombre_comercial'] ?? 'Hotel')?></small></div></div><nav class="nav"><a href="recepcion.php"><i class="fa-solid fa-calendar-check"></i> Reservas</a><?php if(!$esRecepcionista): ?><a href="habitaciones.php"><i class="fa-solid fa-bed"></i> Habitaciones</a><?php endif; ?><a class="gold" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a></nav></header>
<main class="wrap"><section class="hero"><h1>Control de clientes no gratos</h1><p>Registra DNI y nombres para alertar o bloquear atención cuando aparezca una consulta del mismo cliente.</p></section>
<?php if($error): ?><div class="alert"><i class="fa-solid fa-triangle-exclamation"></i> <?=htmlspecialchars($error)?></div><?php endif; ?>
<div class="grid"><section class="card"><h2><i class="fa-solid fa-plus"></i> Nuevo registro</h2><form method="post" autocomplete="off"><input type="hidden" name="accion" value="crear"><div class="field"><label>DNI</label><input id="dni-no-grato" name="dni" maxlength="12" required autocomplete="off" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'')"><div id="dni-hint" class="hint"></div></div><div class="field"><label>Nombres</label><input id="nombres-no-grato" name="nombres" required autocomplete="off"></div><div class="field"><label>Apellidos</label><input id="apellidos-no-grato" name="apellidos" autocomplete="off"></div><div class="field"><label>Motivo</label><textarea name="motivo" required placeholder="Ej: ocasionó daños, no pagó consumo, conducta inapropiada..."></textarea></div><button class="btn green" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar</button></form></section>
<section class="card"><h2><i class="fa-solid fa-list"></i> Lista registrada</h2><div class="table-wrap"><table><thead><tr><th>DNI</th><th>Cliente</th><th>Motivo</th><th>Estado</th><th>Acción</th></tr></thead><tbody><?php if(!$rows): ?><tr><td colspan="5">No hay clientes no gratos registrados.</td></tr><?php endif; ?><?php foreach($rows as $r): ?><tr><td><b><?=htmlspecialchars($r['dni'])?></b></td><td><?=htmlspecialchars(trim($r['nombres'].' '.$r['apellidos']))?></td><td><?=htmlspecialchars($r['motivo'])?></td><td><span class="badge <?=($r['estado']==='ACTIVO'?'':'off')?>"><?=htmlspecialchars($r['estado'])?></span></td><td class="actions"><form method="post" autocomplete="off"><input type="hidden" name="accion" value="estado"><input type="hidden" name="id_no_grato" value="<?=(int)$r['id_no_grato']?>"><input type="hidden" name="estado" value="<?=$r['estado']==='ACTIVO'?'INACTIVO':'ACTIVO'?>"><button class="btn gray" type="submit"><?=$r['estado']==='ACTIVO'?'Desactivar':'Activar'?></button></form><?php if(!$esRecepcionista): ?><form method="post" onsubmit="return confirm('¿Eliminar este registro?')"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id_no_grato" value="<?=(int)$r['id_no_grato']?>"><button class="btn red" type="submit"><i class="fa-solid fa-trash"></i></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section></div></main>
<script>
const dniNoGrato = document.getElementById('dni-no-grato');
const nombresNoGrato = document.getElementById('nombres-no-grato');
const apellidosNoGrato = document.getElementById('apellidos-no-grato');
const dniHint = document.getElementById('dni-hint');
let timerDniNoGrato = null;

function setDniHint(tipo, texto) {
    if (!dniHint) return;
    dniHint.className = 'hint ' + (tipo || '');
    dniHint.innerHTML = texto || '';
}

async function autocompletarClienteNoGrato() {
    const dni = (dniNoGrato?.value || '').replace(/[^0-9]/g, '');
    if (dni.length < 8) {
        setDniHint('', '');
        return;
    }
    setDniHint('info', '<i class="fa-solid fa-spinner fa-spin"></i> Buscando cliente registrado...');
    try {
        const res = await fetch('../api/clientes_buscar.php?dni=' + encodeURIComponent(dni), {cache:'no-store'});
        const data = await res.json();
        if (res.ok && data.encontrado && data.cliente) {
            nombresNoGrato.value = data.cliente.nombres || nombresNoGrato.value;
            apellidosNoGrato.value = data.cliente.apellidos || apellidosNoGrato.value;
            setDniHint('ok', '<i class="fa-solid fa-circle-check"></i> Cliente encontrado. Datos completados automáticamente.');
        } else {
            setDniHint('info', '<i class="fa-solid fa-circle-info"></i> No existe en clientes. Puedes registrarlo manualmente.');
        }
    } catch (e) {
        setDniHint('bad', '<i class="fa-solid fa-triangle-exclamation"></i> No se pudo consultar el cliente.');
    }
}

if (dniNoGrato) {
    dniNoGrato.addEventListener('input', () => {
        clearTimeout(timerDniNoGrato);
        timerDniNoGrato = setTimeout(autocompletarClienteNoGrato, 450);
    });
}
</script>
</body></html>
