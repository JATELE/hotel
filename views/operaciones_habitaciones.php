<?php
require_once '../config/Database.php';
require_once '../config/auth.php';
requerir_login(['admin_hotel','admin','recepcionista','recepcion']);

$db = (new Database())->connect();
$idHotel = (int) id_hotel_actual();
$usuario = $_SESSION['usuario'] ?? [];
$idUsuario = (int)($usuario['id_usuario'] ?? 0);
$error = '';

function tx($v): string { return trim((string)($v ?? '')); }
function go(): void { header('Location: operaciones_habitaciones.php'); exit; }
function fecha_hora_valida(string $f, string $h): bool {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) && (bool)preg_match('/^\d{2}:\d{2}$/', $h);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $accion = tx($_POST['accion'] ?? '');
        $idHab = (int)($_POST['id_habitacion'] ?? 0);
        $idRes = (int)($_POST['id_reserva'] ?? 0);
        $obs = tx($_POST['observacion'] ?? '');
        $fecha = tx($_POST['fecha'] ?? date('Y-m-d'));
        $hora = tx($_POST['hora'] ?? date('H:i'));
        $idPersonal = (int)($_POST['id_personal'] ?? 0);

        $q = $db->prepare("SELECT id_habitacion FROM habitaciones WHERE id_habitacion=? AND id_hotel=? LIMIT 1");
        $q->execute([$idHab, $idHotel]);
        if (!$q->fetchColumn()) throw new Exception('Habitación inválida.');

        $reserva = null;
        if (in_array($accion, ['check_in','check_out'], true)) {
            if ($idRes <= 0) throw new Exception('La habitación no tiene una reserva confirmada asociada.');
            $q = $db->prepare("SELECT id_reserva,fecha_checkin,fecha_checkout,fecha_hora_checkin_real,fecha_hora_checkout_real,estado_reserva FROM reservas WHERE id_reserva=? AND id_hotel=? LIMIT 1");
            $q->execute([$idRes, $idHotel]);
            $reserva = $q->fetch(PDO::FETCH_ASSOC);
            if (!$reserva) throw new Exception('No se encontró la reserva asociada.');
            $fecha = $accion === 'check_in' ? $reserva['fecha_checkin'] : $reserva['fecha_checkout'];
        }

        if (!fecha_hora_valida($fecha, $hora)) throw new Exception('La fecha o la hora no son válidas.');
        $fechaHora = $fecha . ' ' . $hora . ':00';

        $responsable = null;
        if ($idPersonal > 0) {
            $q = $db->prepare("SELECT CONCAT(nombres,' ',COALESCE(apellidos,'')) FROM personal_limpieza WHERE id_personal=? AND id_hotel=? AND estado='ACTIVO' LIMIT 1");
            $q->execute([$idPersonal, $idHotel]);
            $responsable = trim((string)$q->fetchColumn());
            if ($responsable === '') throw new Exception('Trabajador de limpieza no válido.');
        }

        $db->beginTransaction();
        $tipo = '';
        $estadoPlanning = '';
        $estadoFisico = null;

        if ($accion === 'check_in') {
            if (($reserva['estado_reserva'] ?? '') !== 'Confirmada') throw new Exception('Solo una reserva confirmada puede registrar Check In.');
            if (!empty($reserva['fecha_hora_checkin_real'])) throw new Exception('El Check In ya fue registrado.');
            if (!empty($reserva['fecha_hora_checkout_real'])) throw new Exception('La reserva ya tiene Check Out.');
            $q = $db->prepare("UPDATE reservas SET fecha_hora_checkin_real=?, id_usuario_checkin=? WHERE id_reserva=? AND id_hotel=?");
            $q->execute([$fechaHora, $idUsuario ?: null, $idRes, $idHotel]);
            $tipo = 'CHECK_IN'; $estadoPlanning = 'Check In'; $estadoFisico = 'Ocupada';
        } elseif ($accion === 'check_out') {
            if (empty($reserva['fecha_hora_checkin_real'])) throw new Exception('Primero debes confirmar el Check In.');
            if (!empty($reserva['fecha_hora_checkout_real'])) throw new Exception('El Check Out ya fue registrado.');
            $q = $db->prepare("UPDATE reservas SET fecha_hora_checkout_real=?, id_usuario_checkout=?, estado_reserva='Culminada' WHERE id_reserva=? AND id_hotel=?");
            $q->execute([$fechaHora, $idUsuario ?: null, $idRes, $idHotel]);
            $tipo = 'CHECK_OUT'; $estadoPlanning = 'Check Out'; $estadoFisico = 'Disponible';
        } elseif ($accion === 'programar_limpieza') {
            if ($idPersonal <= 0) throw new Exception('Selecciona un trabajador de limpieza.');
            $q = $db->prepare("INSERT INTO habitacion_limpieza(id_hotel,id_habitacion,id_reserva,id_personal,fecha,fecha_programada,hora_programada,responsable,estado,observacion) VALUES(?,?,?,?,?,?,?,?, 'PENDIENTE',?)");
            $q->execute([$idHotel,$idHab,$idRes ?: null,$idPersonal,$fecha,$fecha,$hora,$responsable,$obs ?: null]);
            $tipo = 'PROGRAMAR_LIMPIEZA'; $estadoPlanning = 'Limpieza'; $estadoFisico = null;
        } elseif ($accion === 'iniciar_limpieza') {
            $q = $db->prepare("SELECT * FROM habitacion_limpieza WHERE id_hotel=? AND id_habitacion=? ORDER BY id_limpieza DESC LIMIT 1 FOR UPDATE");
            $q->execute([$idHotel,$idHab]);
            $limpieza = $q->fetch(PDO::FETCH_ASSOC);
            if (!$limpieza || ($limpieza['estado'] ?? '') !== 'PENDIENTE') throw new Exception('Primero debes programar la limpieza.');
            $personalFinal = $idPersonal ?: (int)($limpieza['id_personal'] ?? 0);
            if ($personalFinal <= 0) throw new Exception('La limpieza no tiene un trabajador asignado.');
            $q = $db->prepare("UPDATE habitacion_limpieza SET fecha=?,hora_inicio=TIME(?),estado='EN_LIMPIEZA',id_usuario_inicio=?,observacion=COALESCE(NULLIF(?,''),observacion) WHERE id_limpieza=?");
            $q->execute([$fecha,$fechaHora,$idUsuario ?: null,$obs,(int)$limpieza['id_limpieza']]);
            $tipo = 'INICIAR_LIMPIEZA'; $estadoPlanning = 'Limpieza'; $estadoFisico = null;
        } elseif ($accion === 'finalizar_limpieza') {
            $q = $db->prepare("SELECT * FROM habitacion_limpieza WHERE id_hotel=? AND id_habitacion=? ORDER BY id_limpieza DESC LIMIT 1 FOR UPDATE");
            $q->execute([$idHotel,$idHab]);
            $limpieza = $q->fetch(PDO::FETCH_ASSOC);
            if (!$limpieza || ($limpieza['estado'] ?? '') !== 'EN_LIMPIEZA') throw new Exception('No puedes finalizar una limpieza que todavía no fue iniciada.');
            $q = $db->prepare("UPDATE habitacion_limpieza SET fecha=?,hora_fin=TIME(?),estado='LISTA_PARA_VENDER',inspeccionado=1,id_usuario_fin=?,observacion=COALESCE(NULLIF(?,''),observacion) WHERE id_limpieza=?");
            $q->execute([$fecha,$fechaHora,$idUsuario ?: null,$obs,(int)$limpieza['id_limpieza']]);
            $tipo = 'FINALIZAR_LIMPIEZA'; $estadoPlanning = 'Lista para vender'; $estadoFisico = 'Disponible';
        } else {
            throw new Exception('Acción no válida.');
        }

        if ($estadoFisico !== null) {
            $q = $db->prepare("UPDATE habitaciones SET estado=? WHERE id_habitacion=? AND id_hotel=?");
            $q->execute([$estadoFisico,$idHab,$idHotel]);
        }

        $q = $db->prepare("INSERT INTO habitacion_operaciones(id_hotel,id_habitacion,id_reserva,tipo_operacion,estado_resultante,fecha_hora,id_usuario,responsable,observacion) VALUES(?,?,?,?,?,?,?,?,?)");
        $q->execute([$idHotel,$idHab,$idRes ?: null,$tipo,$estadoPlanning,$fechaHora,$idUsuario ?: null,$responsable,$obs ?: null]);
        $db->commit();
        go();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        $error = $e->getMessage();
    }
}

$q=$db->prepare("SELECT * FROM personal_limpieza WHERE id_hotel=? AND estado='ACTIVO' ORDER BY nombres,apellidos");
$q->execute([$idHotel]);
$personal=$q->fetchAll(PDO::FETCH_ASSOC);

$q=$db->prepare("SELECT h.id_habitacion,h.numero_habitacion,h.estado,c.nombre categoria,
 (SELECT ho.estado_resultante
    FROM habitacion_operaciones ho
    LEFT JOIN reservas rx ON rx.id_reserva=ho.id_reserva
   WHERE ho.id_habitacion=h.id_habitacion
     AND (ho.id_reserva IS NULL OR rx.estado_reserva='Confirmada')
   ORDER BY ho.fecha_hora DESC,ho.id_operacion DESC LIMIT 1) estado_operativo,
 (SELECT CONCAT(r.id_reserva,'|',r.fecha_checkin,'|',r.fecha_checkout,'|',COALESCE(r.fecha_hora_checkin_real,''),'|',COALESCE(r.fecha_hora_checkout_real,''))
    FROM reservas r
   WHERE r.id_hotel=h.id_hotel
     AND r.estado_reserva='Confirmada'
     AND r.fecha_hora_checkout_real IS NULL
     AND (r.id_habitacion=h.id_habitacion OR EXISTS(SELECT 1 FROM reserva_detalle rd WHERE rd.id_reserva=r.id_reserva AND rd.id_habitacion=h.id_habitacion))
   ORDER BY CASE WHEN CURDATE() BETWEEN r.fecha_checkin AND r.fecha_checkout THEN 0 ELSE 1 END,
            r.fecha_checkin ASC,r.id_reserva ASC LIMIT 1) reserva_info,
 (SELECT CONCAT(COALESCE(hl.responsable,''),'|',COALESCE(hl.fecha_programada,''),'|',COALESCE(TIME_FORMAT(hl.hora_programada,'%H:%i'),''),'|',COALESCE(TIME_FORMAT(hl.hora_inicio,'%H:%i'),''),'|',COALESCE(TIME_FORMAT(hl.hora_fin,'%H:%i'),''),'|',hl.estado)
    FROM habitacion_limpieza hl
    LEFT JOIN reservas ry ON ry.id_reserva=hl.id_reserva
   WHERE hl.id_habitacion=h.id_habitacion
     AND (hl.id_reserva IS NULL OR ry.id_reserva IS NOT NULL)
   ORDER BY CASE WHEN hl.estado IN ('PENDIENTE','PROGRAMADA','EN_LIMPIEZA','INICIADA') THEN 0 ELSE 1 END,
            hl.id_limpieza DESC LIMIT 1) limpieza_info
 FROM habitaciones h JOIN categorias c ON c.id_categoria=h.id_categoria WHERE h.id_hotel=? ORDER BY c.nombre,h.numero_habitacion");
$q->execute([$idHotel]);
$habitaciones=$q->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Operación hotelera</title><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><style>
body{font-family:Poppins;margin:0;background:#f3f6fa;color:#142235}.top{background:#172736;color:white;padding:16px 24px;display:flex;justify-content:space-between;align-items:center;gap:12px}.top a{color:#172736;background:white;text-decoration:none;padding:10px 13px;border-radius:11px;font-weight:800}.wrap{max-width:1450px;margin:24px auto;padding:0 18px}.head{display:flex;justify-content:space-between;align-items:end;gap:15px;margin-bottom:18px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px}.card{background:#fff;border:1px solid #dfe7ef;border-radius:18px;padding:18px;box-shadow:0 12px 34px #10203012}.title{display:flex;justify-content:space-between;gap:12px}.badge{padding:7px 10px;border-radius:999px;font-size:11px;font-weight:900;background:#eaf0f6}.Disponible,.Lista{background:#e7f8e8;color:#137a32}.Ocupada{background:#071d48;color:white}.Reservada{background:#6d3b9a;color:white}.Check{background:#008b85;color:white}.Limpieza{background:#ff8a00;color:white}.Fuera{background:#ef1f27;color:white}.meta{font-size:12px;color:#637083;line-height:1.65;margin:10px 0}.btn{border:0;border-radius:10px;padding:10px 12px;font-weight:800;cursor:pointer}.manage{background:#2563eb;color:white;width:100%}.modal{display:none;position:fixed;inset:0;background:#0f172a99;z-index:99;align-items:center;justify-content:center;padding:20px}.modal.show{display:flex}.box{background:#fff;border-radius:18px;padding:22px;width:min(680px,96vw);max-height:92vh;overflow:auto}.formgrid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.formgrid input,.formgrid select,.formgrid textarea{width:100%;box-sizing:border-box;padding:11px;border:1px solid #d7e0ea;border-radius:10px;font-family:Poppins}.full{grid-column:1/-1}.actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}.green{background:#16a06a;color:white}.blue{background:#2563eb;color:white}.orange{background:#f59e0b;color:white}.gray{background:#e9eef4;color:#24364b}.alert{padding:12px;border-radius:12px;background:#fee2e2;color:#991b1b;margin-bottom:15px;font-weight:700}@media(max-width:700px){.head,.top{align-items:flex-start;flex-direction:column}.formgrid{grid-template-columns:1fr}.full{grid-column:auto}}
</style></head><body><header class="top"><b><i class="fa-solid fa-bell-concierge"></i> Operación hotelera</b><div><a href="personal_limpieza.php">Personal de limpieza</a> <a href="recepcion.php">Recepción</a> <a href="resume.php">Resume</a></div></header><main class="wrap"><section class="head"><div><h1>Check-in, check-out y limpieza</h1><p>Los bloqueos, mantenimiento y reactivación se administran desde Conecting.</p></div></section><?php if($error):?><div class="alert"><?=htmlspecialchars($error)?></div><?php endif;?><div class="grid"><?php foreach($habitaciones as $h):
$r=explode('|',(string)$h['reserva_info']);
$idRes=(int)($r[0]??0);$fi=$r[1]??'';$fo=$r[2]??'';$ci=$r[3]??'';$co=$r[4]??'';
$l=explode('|',(string)$h['limpieza_info']);$limEstado=strtoupper(trim((string)($l[5]??'')));
if($idRes){
    if($ci!=='' && $co==='') $estado='Ocupada';
    elseif($fi>date('Y-m-d')) $estado='Reservada';
    else $estado='Check In';
}elseif(in_array($limEstado,['PENDIENTE','PROGRAMADA','EN_LIMPIEZA','INICIADA'],true)){
    $estado='Limpieza';
}elseif(in_array($limEstado,['FINALIZADA','LISTA_PARA_VENDER'],true)){
    $estado='Lista para vender';
}elseif($h['estado']==='Mantenimiento'){
    $estado='Fuera de Servicio';
}elseif($h['estado']==='Ocupada'){
    $estado='Ocupada';
}else{
    $estado='Disponible';
}
$cl=preg_replace('/\s+.*/','',$estado);?><section class="card"><div class="title"><div><b>Hab. <?=htmlspecialchars($h['numero_habitacion'])?></b><br><small><?=htmlspecialchars($h['categoria'])?></small></div><span class="badge <?=htmlspecialchars($cl)?>"><?=htmlspecialchars($estado)?></span></div><div class="meta"><?php if($idRes):?>Reserva #<?=$idRes?> · <?=htmlspecialchars($fi)?> a <?=htmlspecialchars($fo)?><br>Check In real: <?=htmlspecialchars($ci?:'-')?> · Check Out real: <?=htmlspecialchars($co?:'-')?><?php else:?>Sin reserva confirmada próxima<?php endif;?><?php if($limEstado):?><br>Limpieza: <?=htmlspecialchars($limEstado)?> · <?=htmlspecialchars($l[0]?:'Sin responsable')?> · programada <?=htmlspecialchars(($l[1]?:'-').' '.($l[2]?:''))?><?php endif;?></div><button class="btn manage" type="button" onclick='abrir(<?=json_encode(["id_habitacion"=>$h["id_habitacion"],"numero"=>$h["numero_habitacion"],"id_reserva"=>$idRes,"estado"=>$estado,"fecha_checkin"=>$fi,"fecha_checkout"=>$fo,"checkin_real"=>$ci,"checkout_real"=>$co,"limpieza_estado"=>$limEstado],JSON_UNESCAPED_UNICODE)?>)'>Gestionar habitación</button></section><?php endforeach;?></div></main>
<div class="modal" id="modal"><div class="box"><h2 id="mt">Gestionar habitación</h2><form method="post"><input type="hidden" name="id_habitacion" id="mh"><input type="hidden" name="id_reserva" id="mr"><div class="formgrid"><div><label id="lbl-fecha">Fecha de operación</label><input type="date" name="fecha" id="mfecha" value="<?=date('Y-m-d')?>" required></div><div><label>Hora</label><input type="time" name="hora" id="mhora" value="<?=date('H:i')?>" required></div><div class="full"><label>Trabajador de limpieza</label><select name="id_personal" id="mpersonal"><option value="0">Seleccionar trabajador</option><?php foreach($personal as $p):?><option value="<?=$p['id_personal']?>"><?=htmlspecialchars(trim($p['nombres'].' '.$p['apellidos']).($p['dni']?' · DNI '.$p['dni']:''))?></option><?php endforeach;?></select></div><div class="full"><label>Observación</label><textarea name="observacion"></textarea></div></div><div class="actions"><button class="btn green" id="btn-checkin" name="accion" value="check_in">Confirmar Check In</button><button class="btn blue" id="btn-checkout" name="accion" value="check_out">Confirmar Check Out</button><button class="btn orange" id="btn-programar" name="accion" value="programar_limpieza">Programar limpieza</button><button class="btn orange" id="btn-iniciar" name="accion" value="iniciar_limpieza">Iniciar limpieza</button><button class="btn green" id="btn-finalizar" name="accion" value="finalizar_limpieza">Finalizar limpieza</button><button class="btn gray" type="button" onclick="cerrar()">Cerrar</button></div></form></div></div>
<script>
function vis(id,show){document.getElementById(id).style.display=show?'inline-flex':'none'}
function abrir(d){
 document.getElementById('mh').value=d.id_habitacion;document.getElementById('mr').value=d.id_reserva||'';document.getElementById('mt').textContent='Gestionar Hab. '+d.numero;document.getElementById('mhora').value=new Date().toTimeString().slice(0,5);
 const fecha=document.getElementById('mfecha');fecha.readOnly=false;fecha.value=new Date().toISOString().slice(0,10);document.getElementById('lbl-fecha').textContent='Fecha de operación';
 vis('btn-checkin',!!d.id_reserva&&!d.checkin_real&&!d.checkout_real);vis('btn-checkout',!!d.id_reserva&&!!d.checkin_real&&!d.checkout_real);
 if(d.id_reserva&&!d.checkin_real){fecha.value=d.fecha_checkin||'';fecha.readOnly=true;document.getElementById('lbl-fecha').textContent='Fecha programada de Check In';}
 else if(d.id_reserva&&d.checkin_real&&!d.checkout_real){fecha.value=d.fecha_checkout||'';fecha.readOnly=true;document.getElementById('lbl-fecha').textContent='Fecha programada de Check Out';}
 const le=d.limpieza_estado||'';vis('btn-programar',!['PENDIENTE','EN_LIMPIEZA'].includes(le));vis('btn-iniciar',le==='PENDIENTE');vis('btn-finalizar',le==='EN_LIMPIEZA');
 document.getElementById('modal').classList.add('show');
}
function cerrar(){document.getElementById('modal').classList.remove('show')}
</script></body></html>
