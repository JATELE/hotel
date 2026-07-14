<?php
require_once '../config/Database.php';
require_once '../config/auth.php';
requerir_login(['admin_hotel','admin','recepcionista','recepcion']);

$db = (new Database())->connect();
$idHotel = (int) id_hotel_actual();

$puedeGestionar = tiene_rol(['admin_hotel','admin']);
$mensajeEstado = '';
$errorEstado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_estado'])) {
    if (!$puedeGestionar) {
        $errorEstado = 'Solo el administrador puede modificar estados desde Resume.';
    } else {
        try {
            $accionEstado = trim((string)($_POST['accion_estado'] ?? ''));
            $idHabitacionEstado = (int)($_POST['id_habitacion'] ?? 0);
            $fechaInicioEstado = trim((string)($_POST['fecha_inicio'] ?? ''));
            $fechaFinEstado = trim((string)($_POST['fecha_fin'] ?? ''));
            $motivoEstado = trim((string)($_POST['motivo'] ?? ''));
            $idPersonal = (int)($_POST['id_personal'] ?? 0);
            $horaProgramada = trim((string)($_POST['hora_programada'] ?? ''));
            $accionesPermitidas = ['Bloqueada','Fuera de Servicio','Limpieza','Reactivar'];

            if (!in_array($accionEstado, $accionesPermitidas, true)) {
                throw new RuntimeException('Solo puedes bloquear, poner fuera de servicio, programar limpieza o reactivar desde Resume.');
            }
            if ($idHabitacionEstado <= 0 || $fechaInicioEstado === '' || $fechaFinEstado === '') {
                throw new RuntimeException('Selecciona una habitación y un rango de fechas.');
            }
            if ($fechaFinEstado < $fechaInicioEstado) {
                throw new RuntimeException('La fecha final no puede ser anterior a la inicial.');
            }

            $ver = $db->prepare("SELECT id_habitacion FROM habitaciones WHERE id_habitacion=? AND id_hotel=? LIMIT 1");
            $ver->execute([$idHabitacionEstado, $idHotel]);
            if (!$ver->fetchColumn()) {
                throw new RuntimeException('La habitación no pertenece a este hotel.');
            }

            if ($accionEstado === 'Reactivar') {
                // Cancela bloqueos o periodos fuera de servicio que se crucen con el rango elegido.
                $cancelar = $db->prepare("UPDATE habitaciones_bloqueos
                    SET estado='CANCELADO'
                    WHERE id_hotel=?
                      AND id_habitacion=?
                      AND estado='ACTIVO'
                      AND fecha_inicio <= ?
                      AND fecha_fin >= ?");
                $cancelar->execute([$idHotel, $idHabitacionEstado, $fechaFinEstado, $fechaInicioEstado]);

                // Si la habitación estaba marcada manualmente en mantenimiento, vuelve a estar disponible.
                $activar = $db->prepare("UPDATE habitaciones
                    SET estado='Disponible'
                    WHERE id_hotel=?
                      AND id_habitacion=?
                      AND estado='Mantenimiento'");
                $activar->execute([$idHotel, $idHabitacionEstado]);

                if ($cancelar->rowCount() === 0 && $activar->rowCount() === 0) {
                    throw new RuntimeException('No existe un bloqueo o mantenimiento activo en el rango seleccionado.');
                }
                $mensajeEstado = 'Habitación reactivada para la venta correctamente.';
            } elseif ($accionEstado === 'Limpieza') {
                if (!table_exists($db, 'habitacion_limpieza')) {
                    throw new RuntimeException('El módulo de limpieza todavía no está instalado en la base de datos.');
                }
                if ($idPersonal <= 0) {
                    throw new RuntimeException('Selecciona un trabajador de limpieza.');
                }
                if ($horaProgramada === '') {
                    throw new RuntimeException('Selecciona la hora programada de limpieza.');
                }
                $personal = $db->prepare("SELECT CONCAT(nombres,' ',COALESCE(apellidos,'')) nombre FROM personal_limpieza WHERE id_personal=? AND id_hotel=? AND estado='ACTIVO' LIMIT 1");
                $personal->execute([$idPersonal, $idHotel]);
                $nombrePersonal = trim((string)$personal->fetchColumn());
                if ($nombrePersonal === '') {
                    throw new RuntimeException('El trabajador de limpieza seleccionado no está disponible.');
                }

                $desde = new DateTime($fechaInicioEstado);
                $hasta = new DateTime($fechaFinEstado);
                $hasta->modify('+1 day');
                $db->beginTransaction();
                try {
                    for ($d = clone $desde; $d < $hasta; $d->modify('+1 day')) {
                        $fecha = $d->format('Y-m-d');
                        $existe = $db->prepare("SELECT id_limpieza FROM habitacion_limpieza WHERE id_hotel=? AND id_habitacion=? AND COALESCE(fecha_programada,fecha)=? AND estado IN('PENDIENTE','EN_LIMPIEZA') LIMIT 1");
                        $existe->execute([$idHotel, $idHabitacionEstado, $fecha]);
                        if ($existe->fetchColumn()) continue;
                        $crear = $db->prepare("INSERT INTO habitacion_limpieza (id_hotel,id_habitacion,id_reserva,id_personal,fecha,fecha_programada,hora_programada,responsable,estado,observacion) VALUES (?,?,NULL,?,?,?,?,?,'PENDIENTE',?)");
                        $crear->execute([$idHotel,$idHabitacionEstado,$idPersonal,$fecha,$fecha,$horaProgramada,$nombrePersonal,$motivoEstado !== '' ? $motivoEstado : 'Limpieza programada desde Resume']);
                    }
                    $db->commit();
                } catch (Throwable $e) {
                    if ($db->inTransaction()) $db->rollBack();
                    throw $e;
                }
                $mensajeEstado = 'Limpieza programada correctamente. Continúa con Iniciar limpieza y Finalizar limpieza desde Operación hotelera.';
            } else {
                $prefijo = $accionEstado === 'Fuera de Servicio' ? '[FUERA_SERVICIO]' : '[BLOQUEADA]';
                $motivoFinal = $prefijo . ' ' . ($motivoEstado !== '' ? $motivoEstado : $accionEstado . ' desde Resume');
                $cruce = $db->prepare("SELECT id_bloqueo FROM habitaciones_bloqueos WHERE id_hotel=? AND id_habitacion=? AND estado='ACTIVO' AND fecha_inicio<=? AND fecha_fin>=? LIMIT 1");
                $cruce->execute([$idHotel, $idHabitacionEstado, $fechaFinEstado, $fechaInicioEstado]);
                if ($cruce->fetchColumn()) {
                    throw new RuntimeException('La habitación ya tiene un bloqueo o mantenimiento que se cruza con esas fechas.');
                }
                $crear = $db->prepare("INSERT INTO habitaciones_bloqueos (id_hotel,id_habitacion,fecha_inicio,fecha_fin,motivo,estado) VALUES (?,?,?,?,?,'ACTIVO')");
                $crear->execute([$idHotel, $idHabitacionEstado, $fechaInicioEstado, $fechaFinEstado, $motivoFinal]);
                $mensajeEstado = 'Estado "' . $accionEstado . '" aplicado correctamente.';
            }
        } catch (Throwable $e) {
            $errorEstado = $e->getMessage();
        }
    }
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function table_exists(PDO $db, string $table): bool {
    $q = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
    $q->execute([$table]);
    return (int)$q->fetchColumn() > 0;
}
function fmt_date_es(string $date): string {
    $meses=['Jan'=>'Ene','Feb'=>'Feb','Mar'=>'Mar','Apr'=>'Abr','May'=>'May','Jun'=>'Jun','Jul'=>'Jul','Aug'=>'Ago','Sep'=>'Sep','Oct'=>'Oct','Nov'=>'Nov','Dec'=>'Dic'];
    $ts=strtotime($date); return date('d',$ts).' '.($meses[date('M',$ts)]??date('M',$ts));
}
function estado_meta(string $estado): array {
    $map=[
        'Disponible'=>['#9fbe62','Habitación libre, sin reservas ni bloqueos. Lista para ser asignada y vendida.'],
        'Ocupada'=>['#08264d','Habitación actualmente ocupada por huéspedes registrados.'],
        'Reservada'=>['#7650a5','Habitación reservada para futuras fechas. Aún no hay check in.'],
        'Check In'=>['#078b85','Llegada del huésped el día de hoy. Se está realizando el ingreso a la habitación.'],
        'Check Out'=>['#0874cb','Salida del huésped el día de hoy. La habitación debe liberarse.'],
        'Limpieza'=>['#ff7a00','Habitación en proceso de limpieza y preparación para quedar lista para el próximo huésped.'],
        'Fuera de Servicio'=>['#f02029','Habitación no disponible por avería, reparación o mantenimiento mayor.'],
        'Bloqueada'=>['#55585d','Habitación bloqueada para uso interno del hotel (administración, entrenamiento, grupos, etc.).'],
        'Lista para vender'=>['#71ae2f','Habitación limpia, inspeccionada y liberada. Disponible para ser asignada y vendida.'],
    ];
    return $map[$estado] ?? ['#cbd5e1',''];
}

$stmt=$db->prepare("SELECT nombre_comercial,slug,gestion_por_habitacion FROM hoteles WHERE id_hotel=? LIMIT 1");
$stmt->execute([$idHotel]);
$hotel=$stmt->fetch(PDO::FETCH_ASSOC)?:[];

$stmt=$db->prepare("SELECT h.*, c.nombre categoria_principal, c.precio_base,
    GROUP_CONCAT(DISTINCT c2.nombre ORDER BY c2.capacidad_pax SEPARATOR ', ') categorias_venta
    FROM habitaciones h
    JOIN categorias c ON c.id_categoria=h.id_categoria
    LEFT JOIN habitacion_categorias hc ON hc.id_habitacion=h.id_habitacion AND hc.estado='ACTIVO'
    LEFT JOIN categorias c2 ON c2.id_categoria=hc.id_categoria
    WHERE h.id_hotel=?
    GROUP BY h.id_habitacion
    ORDER BY c.nombre, h.numero_habitacion");
$stmt->execute([$idHotel]);
$habitaciones=$stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt=$db->prepare("SELECT b.* FROM habitaciones_bloqueos b WHERE b.id_hotel=? AND b.estado='ACTIVO'");
$stmt->execute([$idHotel]);
$bloqueos=$stmt->fetchAll(PDO::FETCH_ASSOC);
$bloqueosPorHab=[];
foreach($bloqueos as $b){$bloqueosPorHab[(int)$b['id_habitacion']][]=$b;}

$detalleExiste=table_exists($db,'reserva_detalle');
$sql="SELECT r.*, c.nombre categoria_reserva, cl.nombres, cl.apellidos".
    ($detalleExiste ? ", rd.id_habitacion AS detalle_habitacion" : "") .
    " FROM reservas r
      JOIN categorias c ON c.id_categoria=r.id_categoria
      LEFT JOIN clientes cl ON cl.id_cliente=r.id_cliente".
    ($detalleExiste ? " LEFT JOIN reserva_detalle rd ON rd.id_reserva=r.id_reserva" : "") .
    " WHERE r.id_hotel=? AND r.estado_reserva IN ('Confirmada','Culminada')
      ORDER BY r.fecha_checkin, r.id_reserva";
$stmt=$db->prepare($sql);$stmt->execute([$idHotel]);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
$reservas=[];$reservasPorHab=[];
foreach($rows as $r){
    $rid=(int)$r['id_reserva'];
    if(!isset($reservas[$rid])){$reservas[$rid]=$r;$reservas[$rid]['habitaciones']=[];}
    $hid=(int)($r['detalle_habitacion']??$r['id_habitacion']??0);
    if($hid>0 && !in_array($hid,$reservas[$rid]['habitaciones'],true)){$reservas[$rid]['habitaciones'][]=$hid;$reservasPorHab[$hid][]=$r;}
}

$fechaBase = trim((string)($_GET['semana'] ?? $_GET['fecha'] ?? date('Y-m-d')));
$fechaObj = DateTime::createFromFormat('Y-m-d', $fechaBase);
if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fechaBase) {
    $fechaObj = new DateTime('today');
}
$diaSemana = (int)$fechaObj->format('N');
$fechaObj->modify('-' . ($diaSemana - 1) . ' days');
$inicio = $fechaObj->format('Y-m-d');
$finSemana = (clone $fechaObj)->modify('+6 days')->format('Y-m-d');
$semanaAnterior = (clone $fechaObj)->modify('-7 days')->format('Y-m-d');
$semanaSiguiente = (clone $fechaObj)->modify('+7 days')->format('Y-m-d');
$semanaHoy = (new DateTime('today'))->modify('-' . ((int)date('N') - 1) . ' days')->format('Y-m-d');
$dias=[];
for($i=0;$i<7;$i++){$dias[]=(clone $fechaObj)->modify("+$i days")->format('Y-m-d');}

function estado_habitacion_dia(array $h,string $dia,array $bloqueosPorHab,array $reservasPorHab,array $limpiezaPorHabDia): string {
    $hid=(int)$h['id_habitacion'];

    // Estados configurados desde Conecting.
    if(($h['estado']??'')==='Mantenimiento') return 'Fuera de Servicio';

    foreach($bloqueosPorHab[$hid]??[] as $b){
        if($dia>=$b['fecha_inicio'] && $dia<=$b['fecha_fin']) {
            $motivo = strtoupper((string)($b['motivo'] ?? ''));
            return str_contains($motivo, '[FUERA_SERVICIO]') ? 'Fuera de Servicio' : 'Bloqueada';
        }
    }

    // La limpieza solo afecta la fecha en la que fue programada o iniciada.
    if(!empty($limpiezaPorHabDia[$hid][$dia])) return 'Limpieza';

    $tieneReservas = !empty($reservasPorHab[$hid]);
    foreach($reservasPorHab[$hid]??[] as $r){
        $in=$r['fecha_checkin'];
        $out=$r['fecha_checkout'];
        if($dia<$in || $dia>$out) continue;

        $hoy=date('Y-m-d');
        $ci=trim((string)($r['fecha_hora_checkin_real']??''));

        // Mientras la llegada siga siendo futura, todo el rango se visualiza como reservado.
        if($ci==='' && $in>$hoy) return 'Reservada';
        if($dia===$in) return 'Check In';
        if($dia===$out) return 'Check Out';
        if($dia>$in && $dia<$out) return 'Ocupada';
    }

    // El estado manual Ocupada se usa solo cuando no hay una reserva asociada.
    if(!$tieneReservas && ($h['estado']??'')==='Ocupada') return 'Ocupada';
    return 'Disponible';
}

$operacionesActuales=[];$limpiezaActual=[];$limpiezaPorHabDia=[];
if(table_exists($db,'habitacion_operaciones')){
 $q=$db->prepare("SELECT ho.* FROM habitacion_operaciones ho INNER JOIN (SELECT ho2.id_habitacion,MAX(ho2.id_operacion) idop FROM habitacion_operaciones ho2 LEFT JOIN reservas rr ON rr.id_reserva=ho2.id_reserva WHERE ho2.id_hotel=? AND (ho2.id_reserva IS NULL OR rr.id_reserva IS NOT NULL) GROUP BY ho2.id_habitacion) x ON x.idop=ho.id_operacion");$q->execute([$idHotel]);
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $o)$operacionesActuales[(int)$o['id_habitacion']]=$o;
}
if(table_exists($db,'habitacion_limpieza')){
 $q=$db->prepare("SELECT hl.* FROM habitacion_limpieza hl LEFT JOIN reservas rr ON rr.id_reserva=hl.id_reserva WHERE hl.id_hotel=? AND (hl.id_reserva IS NULL OR rr.id_reserva IS NOT NULL) ORDER BY hl.id_limpieza");$q->execute([$idHotel]);
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $o){
   $hid=(int)$o['id_habitacion'];
   $limpiezaActual[$hid]=$o;
   $estadoL=(string)($o['estado']??'');
   if(in_array($estadoL,['PENDIENTE','EN_LIMPIEZA'],true)){
      $fechaL=(string)($o['fecha_programada']??$o['fecha']??'');
      if($fechaL!=='')$limpiezaPorHabDia[$hid][$fechaL]=true;
   }
 }
}
$planning=[];
foreach($habitaciones as $h){
 foreach($dias as $d){
  $st=estado_habitacion_dia($h,$d,$bloqueosPorHab,$reservasPorHab,$limpiezaPorHabDia);
  $planning[(int)$h['id_habitacion']][$d]=$st;
 }
}

$hoy=date('Y-m-d');$operativo=[];$housekeeping=[];
foreach($habitaciones as $h){
    $hid=(int)$h['id_habitacion'];$resHoy=null;
    foreach($reservasPorHab[$hid]??[] as $r){if($hoy>=$r['fecha_checkin']&&$hoy<=$r['fecha_checkout']){$resHoy=$r;break;}}
    $estadoHoy=$planning[$hid][$hoy]??'Lista para vender';
    $operativo[]=['h'=>$h,'r'=>$resHoy,'estado'=>$estadoHoy];
    $hkEstado=$estadoHoy;
    if(!empty($limpiezaActual[$hid]) && in_array(($limpiezaActual[$hid]['estado']??''),['PENDIENTE','EN_LIMPIEZA'],true))$hkEstado='Limpieza';
    elseif($estadoHoy==='Lista para vender')$hkEstado='Lista';
    elseif($estadoHoy==='Ocupada')$hkEstado='Stay Over';
    $housekeeping[]=['h'=>$h,'r'=>$resHoy,'estado'=>$hkEstado,'limpieza'=>$limpiezaActual[$hid]??null];
}

$porCategoria=[];$ingresoTotal=0;$ocupadasTotal=0;$disponiblesTotal=0;
foreach($habitaciones as $h){
    $cat=$h['categoria_principal'];
    if(!isset($porCategoria[$cat]))$porCategoria[$cat]=['total'=>0,'ocupadas'=>0,'disponibles'=>0,'precio'=>(float)$h['precio_base'],'ingresos'=>0];
    $porCategoria[$cat]['total']++;
    $st=$planning[(int)$h['id_habitacion']][$hoy]??'Disponible';
    if(in_array($st,['Ocupada','Check In','Check Out'],true)){$porCategoria[$cat]['ocupadas']++;$ocupadasTotal++;}
    else {$porCategoria[$cat]['disponibles']++;$disponiblesTotal++;}
}
$q=$db->prepare("SELECT c.nombre categoria,SUM(r.precio_final) total FROM reservas r JOIN categorias c ON c.id_categoria=r.id_categoria WHERE r.id_hotel=? AND r.estado_reserva IN('Confirmada','Culminada') AND r.fecha_checkin<=? AND r.fecha_checkout>=? GROUP BY c.id_categoria,c.nombre");
$q->execute([$idHotel,$finSemana,$inicio]);
foreach($q->fetchAll(PDO::FETCH_ASSOC) as $rv){
    $cat=$rv['categoria'];$m=(float)$rv['total'];$ingresoTotal+=$m;
    if(!isset($porCategoria[$cat]))$porCategoria[$cat]=['total'=>0,'ocupadas'=>0,'disponibles'=>0,'precio'=>0,'ingresos'=>0];
    $porCategoria[$cat]['ingresos']=$m;
}
$total=count($habitaciones);$ocupacion=$total?round(($ocupadasTotal/$total)*100):0;
$categoriasAgrupadas=[];foreach($habitaciones as $h){$categoriasAgrupadas[$h['categoria_principal']][]=$h;}
$personalLimpieza=[];
if(table_exists($db,'personal_limpieza')){
    $qp=$db->prepare("SELECT id_personal,nombres,apellidos,dni FROM personal_limpieza WHERE id_hotel=? AND estado='ACTIVO' ORDER BY nombres,apellidos");
    $qp->execute([$idHotel]);
    $personalLimpieza=$qp->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Resume · Planning Hotelero</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--nav:#062654;--gold:#b78c2f;--line:#d9e1ea;--bg:#f7f8fa;--text:#11233d}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Poppins,Arial}.top{background:#162330;color:#fff;padding:15px 24px;display:flex;justify-content:space-between;align-items:center;gap:14px}.top a{background:#fff;color:#162330;text-decoration:none;padding:10px 14px;border-radius:11px;font-weight:800}.wrap{max-width:1500px;margin:22px auto;padding:0 16px}.hero{text-align:center;margin-bottom:20px}.hero h1{font-size:42px;letter-spacing:1px;margin:0;color:#092755}.hero p{font-size:20px;color:var(--gold);font-weight:700;margin:4px 0}.tabs{display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px}.tabs a{padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:800;background:#e8eef5;color:#334155}.tabs .active{background:#0c8f58;color:#fff}.planning-grid{display:grid;grid-template-columns:.9fr 1.1fr;gap:18px}.card{background:white;border:1px solid #d8dee7;border-radius:8px;overflow:hidden;box-shadow:0 8px 25px rgba(15,23,42,.05)}.card-title{margin:0;background:var(--nav);color:white;padding:10px 14px;text-align:center;font-size:18px}.section{padding:12px}.legend-table,.data-table,.calendar{width:100%;border-collapse:collapse}.legend-table th,.data-table th,.calendar th{background:#082954;color:#fff;font-size:11px;text-transform:uppercase;padding:8px;border:1px solid #d9e0e8}.legend-table td,.data-table td,.calendar td{padding:8px;border:1px solid #d9e0e8;font-size:12px;vertical-align:middle}.legend-color,.status-box{display:inline-block;width:32px;height:22px;border-radius:1px}.calendar th.date{background:#fff;color:#26364a}.calendar td{text-align:center}.calendar td:first-child{font-weight:800;background:#fff}.category-head{color:#fff;font-weight:800;padding:5px 10px;text-transform:uppercase;font-size:13px}.category-block{margin-bottom:10px;border-radius:7px;overflow:hidden;border:1px solid #d9e0e8}.category-block .calendar{border:0}.category-block:nth-child(4n+1) .category-head{background:#082954}.category-block:nth-child(4n+2) .category-head{background:#08723f}.category-block:nth-child(4n+3) .category-head{background:#e85b08}.category-block:nth-child(4n+4) .category-head{background:#0868c4}.bottom-grid{display:grid;grid-template-columns:1fr 1.15fr;gap:18px;margin-top:18px}.full{grid-column:1/-1}.commercial-wrap{display:grid;grid-template-columns:1fr 1.15fr;gap:18px;margin-top:18px}.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:10px}.kpi{border:1px solid #d9e0e8;border-radius:8px;padding:12px;text-align:center;background:#fff}.kpi i{font-size:28px;color:#b78c2f}.kpi strong{font-size:24px;display:block}.master-cell{min-width:54px;height:26px;padding:3px!important}.bar{height:16px;border-radius:3px}.muted{color:#64748b}.badge{display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:800}.dot{width:12px;height:12px;border-radius:2px}.scroll{overflow:auto}.empty{padding:24px;text-align:center;color:#64748b}.week-nav{display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap;margin:0 0 18px}.week-nav a,.week-nav button{border:0;text-decoration:none;background:#e8eef5;color:#16324f;padding:10px 14px;border-radius:10px;font-family:Poppins;font-weight:800;cursor:pointer}.week-nav a.primary{background:#0c8f58;color:#fff}.week-label{background:#fff;border:1px solid #d8dee7;border-radius:10px;padding:10px 16px;font-weight:800;color:#092755}.week-date-form{display:flex;align-items:center;gap:8px}.week-date-form input{padding:9px 11px;border:1px solid #cbd5e1;border-radius:9px;font-family:Poppins}.today-col{background:#fff7d6!important;box-shadow:inset 0 0 0 2px #e5b93b}.calendar th.today-col{color:#7a5600}.calendar td.today-col{background:#fffdf4!important}@media(max-width:1050px){.planning-grid,.bottom-grid,.commercial-wrap{grid-template-columns:1fr}.hero h1{font-size:32px}}@media(max-width:700px){.kpis{grid-template-columns:1fr 1fr}.hero h1{font-size:27px}.hero p{font-size:15px}}

.legend-horizontal{display:grid;grid-template-columns:repeat(9,minmax(115px,1fr));gap:10px;padding:14px}.legend-action{border:1px solid #d8e0ea;background:#fff;border-radius:12px;padding:10px;display:flex;align-items:center;gap:9px;text-align:left;font-family:Poppins;font-weight:800;color:#17283d;cursor:pointer;transition:.18s}.legend-action:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(15,42,76,.12)}.legend-action.disabled{opacity:.7}.legend-action.active{box-shadow:inset 0 0 0 3px #e5b93b,0 8px 20px rgba(15,42,76,.12)}.legend-action .legend-color{width:28px;height:28px;flex:0 0 28px;border-radius:7px}.legend-action small{display:block;font-size:9px;font-weight:600;color:#6b7788}.master-cell.editable{cursor:pointer}.master-cell.editable:hover{box-shadow:inset 0 0 0 3px #e5b93b}.master-cell.filtered-out .bar{background:transparent!important}.master-cell.filtered-out{background:#fff!important}.state-modal{display:none;position:fixed;inset:0;background:rgba(11,28,48,.68);z-index:99999;align-items:center;justify-content:center;padding:18px}.state-modal.show{display:flex}.state-modal-box{width:min(620px,100%);background:#fff;border-radius:18px;padding:24px;box-shadow:0 24px 70px rgba(0,0,0,.28)}.state-modal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}.state-modal-head h3{margin:0}.state-close{border:0;background:#e8eef5;border-radius:9px;padding:8px 11px;cursor:pointer}.state-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.state-form-grid .full{grid-column:1/-1}.state-form-grid label{display:block;font-weight:800;font-size:12px;margin-bottom:6px}.state-form-grid input,.state-form-grid select,.state-form-grid textarea{width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:10px;font-family:Poppins}.state-form-grid textarea{min-height:90px;resize:vertical}.state-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}.state-actions button{border:0;border-radius:10px;padding:11px 16px;font-family:Poppins;font-weight:800;cursor:pointer}.state-save{background:#0c8f58;color:#fff}.state-cancel{background:#e8eef5;color:#18324c}.flash{margin:0 0 16px;padding:12px 15px;border-radius:11px;font-weight:700}.flash.ok{background:#e8fbf1;color:#087347;border:1px solid #9ee4bd}.flash.err{background:#fff0ed;color:#ae351f;border:1px solid #ffc2b5}.action-picker{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.action-option{border:1px solid #d5deea;background:#f8fafc;border-radius:13px;padding:12px;cursor:pointer;display:flex;align-items:center;gap:10px;text-align:left;font-family:Poppins;font-weight:800;color:#17283d;transition:.18s}.action-option:hover{transform:translateY(-1px);box-shadow:0 7px 18px rgba(15,42,76,.10)}.action-option.active{border-color:#0c8f58;box-shadow:inset 0 0 0 2px #0c8f58;background:#edf9f3}.action-option i{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;color:#fff}.action-option[data-value="Bloqueada"] i{background:#55585d}.action-option[data-value="Fuera de Servicio"] i{background:#f02029}.action-option[data-value="Limpieza"] i{background:#ff7a00}.action-option[data-value="Reactivar"] i{background:#71ae2f}.action-help{font-size:12px;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:11px;padding:10px 12px}.filter-summary{padding:0 16px 14px;color:#64748b;font-size:12px;font-weight:700}.filter-empty{display:none;margin:0 16px 16px;padding:13px;border:1px dashed #cbd5e1;border-radius:11px;text-align:center;color:#64748b;background:#f8fafc}.filter-empty.show{display:block}@media(max-width:1100px){.legend-horizontal{grid-template-columns:repeat(3,1fr)}}@media(max-width:700px){.legend-horizontal{grid-template-columns:1fr 1fr}.state-form-grid{grid-template-columns:1fr}}
</style></head><body>
<header class="top"><b><i class="fa-solid fa-hotel"></i> Resume · <?=e($hotel['nombre_comercial']??'Hotel')?></b><a href="recepcion.php">Volver a recepción</a><a href="operaciones_habitaciones.php" style="background:#16a06a;color:white;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:800">Operación hotelera</a></header>
<main class="wrap"><div class="tabs"><a href="conecting.php">Conecting</a><a class="active" href="resume.php">Resume</a></div><div class="hero"><h1>PLANNING HOTELERO</h1><p>GUÍA VISUAL DE ESTADOS Y PLANIFICACIÓN</p></div>
<div class="week-nav">
  <a href="resume.php?semana=<?=e($semanaAnterior)?>"><i class="fa-solid fa-chevron-left"></i> Semana anterior</a>
  <a class="primary" href="resume.php?semana=<?=e($semanaHoy)?>"><i class="fa-solid fa-calendar-day"></i> Hoy</a>
  <div class="week-label"><?=e(fmt_date_es($inicio))?> al <?=e(fmt_date_es($finSemana))?></div>
  <a href="resume.php?semana=<?=e($semanaSiguiente)?>">Semana siguiente <i class="fa-solid fa-chevron-right"></i></a>
  <form class="week-date-form" method="get"><input type="date" name="fecha" value="<?=e($fechaBase)?>"><button type="submit"><i class="fa-solid fa-arrow-right"></i> Ir a fecha</button></form>
</div>

<?php if($mensajeEstado!==''):?><div class="flash ok"><?=e($mensajeEstado)?></div><?php endif;?>
<?php if($errorEstado!==''):?><div class="flash err"><?=e($errorEstado)?></div><?php endif;?>
<section class="card">
  <h2 class="card-title">TABLA DE ESTADOS DE HABITACIONES</h2>
  <div class="legend-horizontal">
  <button type="button" class="legend-action active" data-estado="Todos" onclick="filtrarEstado('Todos',this)"><span class="legend-color" style="background:linear-gradient(135deg,#9fbe62 0 33%,#7650a5 33% 66%,#55585d 66%)"></span><span>Todos<small>Mostrar todos</small></span></button>
  <?php foreach(['Disponible','Ocupada','Reservada','Check In','Check Out','Limpieza','Fuera de Servicio','Bloqueada','Lista para vender'] as $st):[$color,$def]=estado_meta($st);?>
    <button type="button" class="legend-action" data-estado="<?=e($st)?>" onclick="filtrarEstado('<?=e($st)?>',this)">
      <span class="legend-color" style="background:<?=$color?>"></span>
      <span><?=e($st)?><small>Filtrar planning</small></span>
    </button>
  <?php endforeach;?>
  </div>
</section>

<section class="card" style="margin-top:18px">
  <h2 class="card-title">PLANNING MAESTRO (UTILIZADO EN PMS)</h2>
  <div class="section scroll">
    <table class="calendar">
      <thead><tr><th>Piso</th><th>Habitación</th><?php foreach($dias as $d):?><th class="date <?=$d===$hoy?'today-col':''?>"><?=e(fmt_date_es($d))?></th><?php endforeach;?></tr></thead>
      <tbody><?php foreach($habitaciones as $h):$num=(string)$h['numero_habitacion'];$piso=strlen($num)>=3?substr($num,0,1):'-';?>
        <tr><td><?=$piso?></td><td><b><?=e($num)?> (<?=e($h['categoria_principal'])?>)</b></td>
        <?php foreach($dias as $d):$st=$planning[(int)$h['id_habitacion']][$d];[$color]=estado_meta($st);?>
          <td class="master-cell <?=$d===$hoy?'today-col':''?> <?=$puedeGestionar?'editable':''?>" data-estado="<?=e($st)?>" title="<?=e($st)?>" <?php if($puedeGestionar):?>onclick="abrirEstadoCelda(<?=(int)$h['id_habitacion']?>,'<?=e($num)?>','<?=e($d)?>','<?=e($st)?>')"<?php endif;?>><div class="bar" style="background:<?=$color?>"></div></td>
        <?php endforeach;?></tr>
      <?php endforeach;?></tbody>
    </table>
  </div>
  <div class="filter-summary" id="filterSummary">Mostrando todos los estados.</div>
  <div class="filter-empty" id="filterEmpty">No hay celdas con este estado en la semana seleccionada.</div>
  <?php if(!$puedeGestionar):?><p class="muted" style="padding:0 16px 16px">Vista de consulta. Solo el administrador puede aplicar estados manuales.</p><?php endif;?>
</section>

<div class="state-modal" id="stateModal">
  <div class="state-modal-box">
    <div class="state-modal-head"><h3 id="stateModalTitle">Aplicar estado</h3><button type="button" class="state-close" onclick="cerrarStateModal()"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="post" id="stateForm">
      <div class="full"><label>Acción a realizar</label>
        <input type="hidden" name="accion_estado" id="accionEstado" required>
        <div class="action-picker">
          <button type="button" class="action-option" data-value="Bloqueada" onclick="seleccionarAccion(this)"><i class="fa-solid fa-ban"></i><span>Bloquear habitación</span></button>
          <button type="button" class="action-option" data-value="Fuera de Servicio" onclick="seleccionarAccion(this)"><i class="fa-solid fa-screwdriver-wrench"></i><span>Fuera de servicio / reparación</span></button>
          <button type="button" class="action-option" data-value="Limpieza" onclick="seleccionarAccion(this)"><i class="fa-solid fa-broom"></i><span>Programar limpieza</span></button>
          <button type="button" class="action-option" data-value="Reactivar" onclick="seleccionarAccion(this)"><i class="fa-solid fa-circle-check"></i><span>Reactivar para la venta</span></button>
        </div>
      </div>
      <div class="state-form-grid">
        <div class="full"><label>Habitación</label><select name="id_habitacion" id="estadoHabitacion" required><option value="">Seleccionar habitación</option><?php foreach($habitaciones as $h):?><option value="<?=(int)$h['id_habitacion']?>">Hab. <?=e($h['numero_habitacion'])?> — <?=e($h['categoria_principal'])?></option><?php endforeach;?></select></div>
        <div><label>Desde</label><input type="date" name="fecha_inicio" id="estadoDesde" required value="<?=e($fechaBase)?>"></div>
        <div><label>Hasta</label><input type="date" name="fecha_fin" id="estadoHasta" required value="<?=e($fechaBase)?>"></div><div class="full" id="campoPersonal" style="display:none"><label>Trabajador de limpieza</label><select name="id_personal" id="estadoPersonal"><option value="">Seleccionar trabajador</option><?php foreach($personalLimpieza as $p):?><option value="<?=(int)$p['id_personal']?>"><?=e(trim(($p['nombres']??'').' '.($p['apellidos']??'')))?><?=!empty($p['dni'])?' · DNI '.e($p['dni']):''?></option><?php endforeach;?></select></div>
        <div id="campoHoraLimpieza" style="display:none"><label>Hora programada</label><input type="time" name="hora_programada" id="horaProgramada"></div>
        <div class="full action-help" id="ayudaLimpieza" style="display:none"><b>Flujo de limpieza:</b> aquí solo se programa. Luego se debe usar <b>Iniciar limpieza</b> y <b>Finalizar limpieza</b> desde <a href="operaciones_habitaciones.php">Operación hotelera</a>.</div>
        <div class="full"><label>Motivo / observación</label><textarea name="motivo" id="estadoMotivo" placeholder="Ej.: reparación de aire acondicionado, bloqueo interno, limpieza profunda..."></textarea></div>
      </div>
      <div class="state-actions"><button type="button" class="state-cancel" onclick="cerrarStateModal()">Cancelar</button><button type="submit" class="state-save">Aplicar cambio</button></div>
    </form>
  </div>
</div>
<script>
const puedeGestionar = <?= $puedeGestionar ? 'true' : 'false' ?>;
function filtrarEstado(estado,boton){
  document.querySelectorAll('.legend-action').forEach(b=>b.classList.remove('active'));
  boton.classList.add('active');
  let coincidencias=0;
  document.querySelectorAll('.master-cell').forEach(c=>{
    const coincide=estado==='Todos' || c.dataset.estado===estado;
    c.classList.toggle('filtered-out',!coincide);
    if(estado!=='Todos' && coincide) coincidencias++;
  });
  document.getElementById('filterSummary').textContent=estado==='Todos'
    ? 'Mostrando todos los estados.'
    : `Filtro activo: ${estado}. Coincidencias: ${coincidencias}.`;
  document.getElementById('filterEmpty').classList.toggle('show',estado!=='Todos' && coincidencias===0);
}
function abrirEstadoCelda(idHabitacion,numero,fecha,estadoActual){
  if(!puedeGestionar) return;
  document.getElementById('estadoHabitacion').value=String(idHabitacion);
  document.getElementById('estadoDesde').value=fecha;
  document.getElementById('estadoHasta').value=fecha;
  document.getElementById('accionEstado').value='';
  document.querySelectorAll('.action-option').forEach(b=>b.classList.remove('active'));
  document.getElementById('estadoMotivo').value='';
  document.getElementById('stateModalTitle').textContent='Hab. '+numero+' · '+fecha+' · Actual: '+estadoActual;
  actualizarCamposAccion();
  document.getElementById('stateModal').classList.add('show');
}
function seleccionarAccion(boton){
  document.querySelectorAll('.action-option').forEach(b=>b.classList.remove('active'));
  boton.classList.add('active');
  document.getElementById('accionEstado').value=boton.dataset.value;
  actualizarCamposAccion();
}
function actualizarCamposAccion(){
  const accion=document.getElementById('accionEstado').value;
  const esLimpieza=accion==='Limpieza';
  const campo=document.getElementById('campoPersonal');
  const select=document.getElementById('estadoPersonal');
  const campoHora=document.getElementById('campoHoraLimpieza');
  const hora=document.getElementById('horaProgramada');
  const ayuda=document.getElementById('ayudaLimpieza');
  campo.style.display=esLimpieza?'block':'none';
  campoHora.style.display=esLimpieza?'block':'none';
  ayuda.style.display=esLimpieza?'block':'none';
  select.required=esLimpieza;
  hora.required=esLimpieza;
  if(esLimpieza && !hora.value){
    const now=new Date();
    hora.value=String(now.getHours()).padStart(2,'0')+':'+String(now.getMinutes()).padStart(2,'0');
  }
}
function cerrarStateModal(){document.getElementById('stateModal').classList.remove('show');}
document.getElementById('stateModal').addEventListener('click',e=>{if(e.target.id==='stateModal')cerrarStateModal();});
document.getElementById('stateForm').addEventListener('submit',e=>{
  if(!document.getElementById('accionEstado').value){
    e.preventDefault();
    alert('Selecciona una acción a realizar.');
  }
});
</script>
</main></body></html>
