<?php
require_once '../config/Database.php';
require_once '../config/auth.php';
requerir_login(['admin_hotel','admin']);
$db = (new Database())->connect();
$idHotel = id_hotel_actual();
$msg = '';
$tipoMsg = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        try {
            $hash = password_hash($_POST['password'] ?: 'recepcion123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO usuarios (id_hotel,id_rol,usuario,email,password_hash,nombres,apellidos,telefono,estado) VALUES (?,3,?,?,?,?,?,?,'ACTIVO')");
            $stmt->execute([$idHotel, trim($_POST['usuario']), trim($_POST['email']), $hash, trim($_POST['nombres']), trim($_POST['apellidos']), trim($_POST['telefono'])]);
            $msg = 'Recepcionista creado correctamente.';
        } catch(Exception $e) { $tipoMsg='error'; $msg = 'Error: '.$e->getMessage(); }
    }
    if ($accion === 'password') {
        $idUsuario=(int)($_POST['id_usuario']??0);
        $nueva=(string)($_POST['password_nueva']??'');
        $confirmar=(string)($_POST['password_confirmar']??'');
        if(strlen($nueva)<6){$tipoMsg='error';$msg='La contraseña debe tener al menos 6 caracteres.';}
        elseif($nueva!==$confirmar){$tipoMsg='error';$msg='Las contraseñas no coinciden.';}
        else{$stmt=$db->prepare("UPDATE usuarios SET password_hash=? WHERE id_usuario=? AND id_hotel=? AND id_rol=3");$stmt->execute([password_hash($nueva,PASSWORD_DEFAULT),$idUsuario,$idHotel]);$msg='Contraseña del recepcionista actualizada.';}
    }
    if ($accion === 'estado') {
        $stmt = $db->prepare("UPDATE usuarios SET estado=? WHERE id_usuario=? AND id_hotel=? AND id_rol=3");
        $stmt->execute([$_POST['estado'], (int)$_POST['id_usuario'], $idHotel]);
        $msg = 'Estado actualizado.';
    }
}
$stmt = $db->prepare("SELECT u.*, COUNT(r.id_reserva) total_reservas, SUM(r.estado_reserva='Confirmada') confirmadas,
    MAX(us.fecha_login) ultimo_login, MAX(us.fecha_logout) ultimo_logout
    FROM usuarios u
    LEFT JOIN reservas r ON r.id_usuario_confirmacion=u.id_usuario
    LEFT JOIN usuario_sesiones us ON us.id_usuario=u.id_usuario
    WHERE u.id_hotel=? AND u.id_rol=3
    GROUP BY u.id_usuario ORDER BY u.id_usuario DESC");
$stmt->execute([$idHotel]);
$rows = $stmt->fetchAll();
$total = count($rows);
$activos = count(array_filter($rows, fn($r)=>$r['estado']==='ACTIVO'));
$confirmadasTotal = array_sum(array_map(fn($r)=>(int)$r['confirmadas'], $rows));
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Recepcionistas | CRM Hoteles</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--nav:#162330;--gold:#d4af37;--bg:#f4f7fb;--text:#13202b;--muted:#64748b;--line:#e6edf4;--green:#16a06a;--red:#ef4444}
*{box-sizing:border-box}body{font-family:Poppins,Arial,sans-serif;background:radial-gradient(circle at top left,#eef7ff 0,#f4f7fb 32%,#f7f8fa 100%);margin:0;color:var(--text)}
.header{background:linear-gradient(135deg,#162330,#24384c);color:white;padding:18px 26px;display:flex;justify-content:space-between;align-items:center;gap:18px;box-shadow:0 12px 30px #0f172a26;position:sticky;top:0;z-index:10}.brand{font-weight:800;display:flex;align-items:center;gap:12px}.brand i{color:var(--gold);font-size:24px}.brand small{display:block;color:#b8c6d3;font-size:12px;margin-top:3px}.nav{display:flex;gap:10px;flex-wrap:wrap}.nav a{background:#fff;color:#162330;text-decoration:none;font-weight:800;border-radius:12px;padding:10px 14px}.nav a.gold{background:var(--gold)}
.wrap{max-width:1180px;margin:28px auto;padding:0 18px}.hero{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:18px}.hero h1{margin:0;font-size:30px}.hero p{margin:6px 0 0;color:var(--muted);font-weight:500}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:20px 0}.stat{background:white;border:1px solid var(--line);border-radius:20px;padding:18px;box-shadow:0 12px 30px #0f172a0d}.stat span{font-size:12px;color:var(--muted);font-weight:800;text-transform:uppercase}.stat strong{display:block;font-size:30px;margin-top:4px}.card{background:white;border:1px solid var(--line);border-radius:22px;padding:22px;box-shadow:0 18px 50px #0f172a12;margin-bottom:20px}.card h2{margin:0 0 16px}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.field label{display:block;font-size:12px;font-weight:800;color:#334155;margin-bottom:7px}.field input,.field select{width:100%;padding:12px 13px;border:1px solid #d7e0ea;border-radius:13px;background:#fbfdff;font-family:Poppins}.btn{border:0;border-radius:13px;padding:12px 16px;font-weight:800;cursor:pointer;font-family:Poppins}.btn.primary{background:var(--gold);color:#162330}.btn.reset{background:#e0f2fe;color:#075985;padding:11px 13px;margin-top:7px}.modal{display:none;position:fixed;inset:0;background:#0f172a99;z-index:9999;align-items:center;justify-content:center;padding:20px}.modal.show{display:flex}.modal-box{background:#fff;border-radius:20px;padding:22px;width:min(460px,100%);box-shadow:0 30px 70px #0005}.modal-head{display:flex;justify-content:space-between;align-items:center}.close{border:0;background:#f1f5f9;width:36px;height:36px;border-radius:50%;cursor:pointer}.msg{padding:13px 15px;border-radius:14px;margin-bottom:16px;font-weight:700}.msg.ok{background:#e9fbf2;color:#087344;border:1px solid #b8ebce}.msg.error{background:#fff1f2;color:#be123c;border:1px solid #fecdd3}.table-wrap{overflow:auto}table{width:100%;border-collapse:separate;border-spacing:0 10px}th{font-size:11px;text-transform:uppercase;color:#64748b;text-align:left;padding:0 12px}td{background:#fbfdff;border-top:1px solid var(--line);border-bottom:1px solid var(--line);padding:13px 12px}td:first-child{border-left:1px solid var(--line);border-radius:14px 0 0 14px}td:last-child{border-right:1px solid var(--line);border-radius:0 14px 14px 0}.badge{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border-radius:999px;font-weight:800;font-size:12px}.badge.ok{background:#dcfce7;color:#166534}.badge.off{background:#fee2e2;color:#991b1b}.action-form{display:flex;gap:8px;align-items:center}.action-form select{padding:9px;border-radius:10px;border:1px solid #d7e0ea}.empty{text-align:center;color:var(--muted);padding:30px!important}@media(max-width:900px){.header,.hero{flex-direction:column;align-items:flex-start}.stats,.grid{grid-template-columns:1fr}.nav{width:100%}.nav a{flex:1;text-align:center}}
</style>
</head>
<body>
<header class="header"><div class="brand"><i class="fa-solid fa-users-gear"></i><div>Recepcionistas<small><?= htmlspecialchars($_SESSION['usuario']['hotel'] ?? 'Hotel') ?></small></div></div><nav class="nav"><a href="recepcion.php"><i class="fa-solid fa-calendar-check"></i> Reservas</a><a href="reportes_recepcionistas.php"><i class="fa-solid fa-chart-column"></i> Reportes</a><a class="gold" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a></nav></header>
<main class="wrap">
<section class="hero"><div><h1>Gestión del equipo de recepción</h1><p>Registra, activa o desactiva recepcionistas del hotel y revisa su producción.</p></div></section>
<?php if($msg):?><div class="msg <?= $tipoMsg ?>"><?=htmlspecialchars($msg)?></div><?php endif;?>
<section class="stats"><div class="stat"><span>Total recepcionistas</span><strong><?= $total ?></strong></div><div class="stat"><span>Activos</span><strong><?= $activos ?></strong></div><div class="stat"><span>Reservas confirmadas</span><strong><?= $confirmadasTotal ?></strong></div></section>
<section class="card"><h2><i class="fa-solid fa-user-plus"></i> Registrar recepcionista</h2><form method="post"><input type="hidden" name="accion" value="crear"><div class="grid"><div class="field"><label>Usuario</label><input name="usuario" placeholder="ej. recepcion_turno1" required></div><div class="field"><label>Email</label><input name="email" type="email" placeholder="correo@hotel.com" required></div><div class="field"><label>Nombres</label><input name="nombres" required></div><div class="field"><label>Apellidos</label><input name="apellidos"></div><div class="field"><label>Teléfono</label><input name="telefono" placeholder="+51 999 999 999"></div><div class="field"><label>Contraseña inicial</label><input name="password" placeholder="recepcion123 si se deja vacío"></div></div><br><button class="btn primary"><i class="fa-solid fa-floppy-disk"></i> Guardar recepcionista</button></form></section>
<section class="card"><h2><i class="fa-solid fa-list-check"></i> Lista de recepcionistas</h2><div class="table-wrap"><table><thead><tr><th>Usuario</th><th>Nombre</th><th>Email</th><th>Estado</th><th>Confirmadas</th><th>Último ingreso</th><th>Acción</th></tr></thead><tbody><?php if(!$rows):?><tr><td class="empty" colspan="7">Todavía no hay recepcionistas registrados.</td></tr><?php endif;?><?php foreach($rows as $r):?><tr><td><b><?=htmlspecialchars($r['usuario'])?></b></td><td><?=htmlspecialchars($r['nombres'].' '.$r['apellidos'])?></td><td><?=htmlspecialchars($r['email'])?></td><td><span class="badge <?= $r['estado']==='ACTIVO'?'ok':'off' ?>"><i class="fa-solid fa-circle"></i><?=$r['estado']?></span></td><td><b><?= (int)$r['confirmadas'] ?></b></td><td><?= htmlspecialchars($r['ultimo_login'] ?: '-') ?></td><td><form method="post" class="action-form"><input type="hidden" name="accion" value="estado"><input type="hidden" name="id_usuario" value="<?=$r['id_usuario']?>"><select name="estado"><option <?= $r['estado']==='ACTIVO'?'selected':'' ?>>ACTIVO</option><option <?= $r['estado']==='INACTIVO'?'selected':'' ?>>INACTIVO</option></select><button class="btn primary">Actualizar</button></form><button type="button" class="btn reset" onclick="abrirPassword(<?= (int)$r['id_usuario'] ?>, <?= htmlspecialchars(json_encode($r['nombres'].' '.$r['apellidos']), ENT_QUOTES) ?>)"><i class="fa-solid fa-key"></i></button></td></tr><?php endforeach;?></tbody></table></div></section>
</main>
<div class="modal" id="modalPassword"><div class="modal-box"><div class="modal-head"><h2>Restablecer contraseña</h2><button class="close" type="button" onclick="cerrarPassword()"><i class="fa-solid fa-xmark"></i></button></div><p id="passwordNombre" style="color:#64748b"></p><form method="post"><input type="hidden" name="accion" value="password"><input type="hidden" name="id_usuario" id="passwordUsuario"><div class="field"><label>Nueva contraseña</label><input type="password" name="password_nueva" minlength="6" required></div><div class="field"><label>Confirmar contraseña</label><input type="password" name="password_confirmar" minlength="6" required></div><button class="btn primary"><i class="fa-solid fa-key"></i> Guardar contraseña</button></form></div></div><script>function abrirPassword(id,nombre){document.getElementById('passwordUsuario').value=id;document.getElementById('passwordNombre').textContent='Recepcionista: '+nombre;document.getElementById('modalPassword').classList.add('show')}function cerrarPassword(){document.getElementById('modalPassword').classList.remove('show')}document.getElementById('modalPassword').addEventListener('click',e=>{if(e.target.id==='modalPassword')cerrarPassword()})</script></body></html>
