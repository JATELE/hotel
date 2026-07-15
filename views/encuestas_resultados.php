<?php
require_once '../config/Database.php';
require_once '../config/auth.php';
requerir_login(['admin_hotel','admin']);
$db=(new Database())->connect();
$idHotel=(int)id_hotel_actual();

$statsStmt=$db->prepare("SELECT COUNT(*) total, COALESCE(AVG(r.promedio),0) promedio, COALESCE(SUM(r.mostro_redes),0) positivas FROM encuesta_respuestas r INNER JOIN encuestas e ON e.id_encuesta=r.id_encuesta WHERE e.id_hotel=?");
$statsStmt->execute([$idHotel]);
$stats=$statsStmt->fetch(PDO::FETCH_ASSOC)?:['total'=>0,'promedio'=>0,'positivas'=>0];
$total=(int)$stats['total'];
$positivas=(int)$stats['positivas'];
$tasa=$total>0?($positivas/$total)*100:0;

$areasStmt=$db->prepare("SELECT LOWER(TRIM(a.nombre)) clave, MIN(a.nombre) nombre, COUNT(d.id_detalle) respuestas, COALESCE(AVG(d.calificacion),0) promedio, SUM(d.calificacion=5) cinco, SUM(d.calificacion=4) cuatro, SUM(d.calificacion=3) tres, SUM(d.calificacion=2) dos, SUM(d.calificacion=1) uno FROM encuesta_areas a INNER JOIN encuestas e ON e.id_encuesta=a.id_encuesta LEFT JOIN encuesta_respuesta_detalle d ON d.id_area=a.id_area WHERE e.id_hotel=? GROUP BY LOWER(TRIM(a.nombre)) HAVING COUNT(d.id_detalle)>0 ORDER BY promedio DESC, nombre ASC");
$areasStmt->execute([$idHotel]);
$areas=$areasStmt->fetchAll(PDO::FETCH_ASSOC);

$distStmt=$db->prepare("SELECT d.calificacion,COUNT(*) cantidad FROM encuesta_respuesta_detalle d INNER JOIN encuesta_areas a ON a.id_area=d.id_area INNER JOIN encuestas e ON e.id_encuesta=a.id_encuesta WHERE e.id_hotel=? GROUP BY d.calificacion ORDER BY d.calificacion DESC");
$distStmt->execute([$idHotel]);
$dist=[5=>0,4=>0,3=>0,2=>0,1=>0];
foreach($distStmt->fetchAll(PDO::FETCH_ASSOC) as $row){$dist[(int)$row['calificacion']]=(int)$row['cantidad'];}
$distTotal=array_sum($dist);

$respuestasStmt=$db->prepare("SELECT r.*,e.titulo FROM encuesta_respuestas r INNER JOIN encuestas e ON e.id_encuesta=r.id_encuesta WHERE e.id_hotel=? ORDER BY r.fecha_respuesta DESC LIMIT 100");
$respuestasStmt->execute([$idHotel]);
$respuestas=$respuestasStmt->fetchAll(PDO::FETCH_ASSOC);
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function estrellas($v){$n=max(0,min(5,(int)round((float)$v)));return str_repeat('★',$n).str_repeat('☆',5-$n);}
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Resultados de encuestas</title><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><style>
*{box-sizing:border-box}body{margin:0;background:#f3f6fa;color:#132238;font-family:Poppins,sans-serif}.top{height:64px;background:#172635;color:#fff;padding:0 28px;display:flex;align-items:center;justify-content:space-between}.top a{background:#fff;color:#172635;text-decoration:none;padding:10px 15px;border-radius:11px;font-weight:800}.wrap{max-width:1240px;margin:30px auto;padding:0 22px}.head{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;margin-bottom:22px}.head h1{font-size:32px;margin:0 0 5px}.head p{margin:0;color:#64748b}.tabs{display:flex;gap:10px}.tabs a{padding:11px 16px;border-radius:12px;background:#e8eef5;color:#334155;text-decoration:none;font-weight:800}.tabs .active{background:#14a36d;color:#fff}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}.stat,.panel{background:#fff;border:1px solid #dde6f0;border-radius:18px;box-shadow:0 14px 38px rgba(15,23,42,.045)}.stat{padding:20px}.stat .ico{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:#ecfdf5;color:#059669;margin-bottom:12px}.stat span{font-size:12px;color:#64748b;font-weight:800}.stat strong{display:block;font-size:29px;margin-top:4px}.grid{display:grid;grid-template-columns:1.45fr .75fr;gap:18px}.panel{padding:22px;margin-bottom:18px}.panel h2{font-size:21px;margin:0 0 4px}.sub{color:#64748b;font-size:13px;margin-bottom:17px}.areas{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.area{border:1px solid #e2e8f0;border-radius:15px;padding:16px;background:#fbfdff}.area-top{display:flex;justify-content:space-between;align-items:start;gap:12px}.area h3{margin:0;text-transform:capitalize;font-size:16px}.score{font-size:20px;font-weight:900;color:#f59e0b;white-space:nowrap}.stars{font-size:14px;color:#f59e0b;letter-spacing:1px}.meta{font-size:12px;color:#64748b;margin-top:4px}.bar{height:10px;border-radius:99px;background:#e4eaf1;overflow:hidden;margin:13px 0 8px}.bar i{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,#10a36c,#34d399)}.mini-dist{display:grid;gap:7px;margin-top:10px}.mini-dist .dist-item{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 10px;border:1px solid #e2e8f0;border-radius:10px;background:#fff}.mini-dist .dist-label{font-size:12px;font-weight:700;color:#334155}.mini-dist .dist-count{font-size:12px;font-weight:900;color:#0f172a;white-space:nowrap}.majority{margin-top:10px;padding:9px 11px;border-radius:10px;background:#ecfdf5;color:#047857;font-size:12px;font-weight:800}.distribution .row{display:grid;grid-template-columns:82px 1fr 42px;align-items:center;gap:10px;margin:13px 0}.distribution .track{height:10px;background:#e7edf4;border-radius:99px;overflow:hidden}.distribution .track i{display:block;height:100%;background:#f59e0b;border-radius:99px}.distribution b{font-size:12px}.table{overflow:auto}table{width:100%;border-collapse:collapse;min-width:850px}th,td{text-align:left;padding:13px 12px;border-bottom:1px solid #e8edf3;font-size:12px;vertical-align:top}th{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#64748b;background:#f8fafc}.badge{display:inline-flex;align-items:center;border-radius:99px;padding:6px 9px;font-weight:800}.positive{background:#dcfce7;color:#15803d}.private{background:#fff7ed;color:#c2410c}.comment{max-width:330px;white-space:normal}.empty{padding:30px;text-align:center;color:#94a3b8}@media(max-width:950px){.stats{grid-template-columns:repeat(2,1fr)}.grid{grid-template-columns:1fr}.areas{grid-template-columns:1fr}}@media(max-width:620px){.head{align-items:flex-start;flex-direction:column}.stats{grid-template-columns:1fr}.tabs{width:100%}.tabs a{flex:1;text-align:center}}
</style></head><body><header class="top"><b><i class="fa-solid fa-chart-line"></i> Resultados de encuestas</b><a href="recepcion.php">Volver</a></header><main class="wrap"><div class="head"><div><h1>Dashboard de satisfacción</h1><p>Resultados consolidados y agrupados por área.</p></div><div class="tabs"><a href="encuestas.php">Configurar</a><a class="active" href="encuestas_resultados.php">Ver resultados</a></div></div>
<section class="stats"><div class="stat"><div class="ico"><i class="fa-solid fa-clipboard-check"></i></div><span>Respuestas</span><strong><?=$total?></strong></div><div class="stat"><div class="ico"><i class="fa-solid fa-star"></i></div><span>Promedio general</span><strong><?=number_format((float)$stats['promedio'],2)?> ★</strong></div><div class="stat"><div class="ico"><i class="fa-solid fa-face-smile"></i></div><span>Calificaciones positivas</span><strong><?=$positivas?></strong></div><div class="stat"><div class="ico"><i class="fa-solid fa-chart-pie"></i></div><span>Tasa de satisfacción</span><strong><?=number_format($tasa,1)?>%</strong></div></section>
<div class="grid"><section class="panel"><h2>Resultados por área</h2><div class="sub">Cada área aparece una sola vez con su promedio general.</div><?php if(!$areas):?><div class="empty">Todavía no hay calificaciones por área.</div><?php else:?><div class="areas"><?php foreach($areas as $a):$p=(float)$a['promedio'];?><article class="area"><div class="area-top"><div><h3><?=e($a['nombre'])?></h3><div class="stars"><?=estrellas($p)?></div><div class="meta"><?=(int)$a['respuestas']?> respuesta<?=((int)$a['respuestas']===1?'':'s')?> · <?=number_format($p*20,0)?>% de satisfacción</div></div><div class="score"><?=number_format($p,2)?></div></div><div class="bar"><i style="width:<?=min(100,$p*20)?>%"></i></div><?php
$conteos=[
    5=>(int)$a['cinco'],
    4=>(int)$a['cuatro'],
    3=>(int)$a['tres'],
    2=>(int)$a['dos'],
    1=>(int)$a['uno'],
];
$maxConteo=max($conteos);
$mayoria=$maxConteo>0?(int)array_search($maxConteo,$conteos,true):0;
$etiquetas=[5=>'Excelente',4=>'Muy bueno',3=>'Regular',2=>'Malo',1=>'Muy malo'];
?>
<div class="majority"><?= $mayoria>0 ? 'La mayoría calificó con '.$mayoria.' estrella'.($mayoria===1?'':'s').'.' : 'Sin calificaciones registradas.' ?></div>
<div class="mini-dist">
<?php foreach([5,4,3,2,1] as $estrella): $cantidad=$conteos[$estrella]; ?>
<div class="dist-item">
    <span class="dist-label"><?=e($etiquetas[$estrella])?> — <?=$estrella?> estrella<?=($estrella===1?'':'s')?></span>
    <span class="dist-count"><?=$cantidad?> respuesta<?=($cantidad===1?'':'s')?></span>
</div>
<?php endforeach; ?>
</div></article><?php endforeach;?></div><?php endif;?></section>
<aside><section class="panel distribution"><h2>Distribución de estrellas</h2><div class="sub">Todas las calificaciones registradas.</div><?php foreach([5,4,3,2,1] as $star):$pct=$distTotal?($dist[$star]/$distTotal*100):0;?><div class="row"><b><?=$star?> estrellas</b><div class="track"><i style="width:<?=$pct?>%"></i></div><strong><?=$dist[$star]?></strong></div><?php endforeach;?></section></aside></div>
<section class="panel"><h2>Respuestas recientes</h2><div class="sub">Comentarios y puntuaciones individuales.</div><div class="table"><table><thead><tr><th>Fecha</th><th>Encuesta</th><th>Cliente</th><th>Puntuación</th><th>Comentario</th><th>Resultado</th></tr></thead><tbody><?php if(!$respuestas):?><tr><td colspan="6" class="empty">No hay respuestas registradas.</td></tr><?php endif;?><?php foreach($respuestas as $r):?><tr><td><?=e($r['fecha_respuesta'])?></td><td><b><?=e($r['titulo'])?></b></td><td><?=e($r['nombre_cliente']?:'Anónimo')?></td><td><span class="stars"><?=estrellas($r['promedio'])?></span><br><b><?=number_format((float)$r['promedio'],2)?></b></td><td class="comment"><?=e($r['comentario']?:'Sin comentario')?></td><td><span class="badge <?=$r['mostro_redes']?'positive':'private'?>"><?=$r['mostro_redes']?'Positiva':'Seguimiento interno'?></span></td></tr><?php endforeach;?></tbody></table></div></section></main></body></html>