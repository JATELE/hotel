<?php
require_once '../config/Database.php';
require_once '../config/auth.php';
requerir_login(['admin_hotel','admin']);
$db=(new Database())->connect();
$idHotel=(int)id_hotel_actual();
$msg='';$err='';
function t($v){return trim((string)($v??''));}
function volver_encuestas(){header('Location: encuestas.php');exit;}
$redesDisponibles=[
 'google_reviews'=>'Google Reviews','facebook'=>'Facebook','tripadvisor'=>'Tripadvisor',
 'instagram'=>'Instagram','tiktok'=>'TikTok','sitio_web'=>'Sitio web'
];
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  $accion=$_POST['accion']??'';
  if($accion==='guardar_redes'){
   $sql="INSERT INTO hotel_redes_sociales(id_hotel,google_reviews,facebook,tripadvisor,instagram,tiktok,sitio_web) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE google_reviews=VALUES(google_reviews),facebook=VALUES(facebook),tripadvisor=VALUES(tripadvisor),instagram=VALUES(instagram),tiktok=VALUES(tiktok),sitio_web=VALUES(sitio_web)";
   $db->prepare($sql)->execute([$idHotel,t($_POST['google_reviews']),t($_POST['facebook']),t($_POST['tripadvisor']),t($_POST['instagram']),t($_POST['tiktok']),t($_POST['sitio_web'])]);
   $msg='Enlaces sociales guardados.';
  }
  if($accion==='crear_area'){
   $nombre=t($_POST['nombre_area']);$descripcion=t($_POST['descripcion_area']);
   if($nombre==='')throw new Exception('Escribe el nombre del área.');
   try{$db->prepare("INSERT INTO encuesta_areas_catalogo(id_hotel,nombre,descripcion) VALUES(?,?,?)")->execute([$idHotel,$nombre,$descripcion]);}
   catch(PDOException $e){if(($e->errorInfo[1]??0)==1062)throw new Exception('Esa área ya existe para este hotel.');throw $e;}
   $msg='Área creada correctamente.';
  }
  if($accion==='eliminar_area'){
   $idArea=(int)($_POST['id_area_catalogo']??0);
   $st=$db->prepare("SELECT COUNT(*) FROM encuesta_areas WHERE id_area_catalogo=?");$st->execute([$idArea]);
   if((int)$st->fetchColumn()>0)throw new Exception('No se puede eliminar el área porque ya fue utilizada en una encuesta.');
   $db->prepare("DELETE FROM encuesta_areas_catalogo WHERE id_area_catalogo=? AND id_hotel=?")->execute([$idArea,$idHotel]);
   $msg='Área eliminada.';
  }
  if($accion==='crear_encuesta'){
   $titulo=t($_POST['titulo']);$descripcion=t($_POST['descripcion']);
   $umbral=max(1,min(5,(float)($_POST['umbral_publicar']??4)));
   $areas=array_values(array_unique(array_map('intval',$_POST['areas']??[])));
   $redes=array_values(array_intersect(array_keys($redesDisponibles),$_POST['redes']??[]));
   if($titulo==='')throw new Exception('Escribe el título de la encuesta.');
   if(!$areas)throw new Exception('Selecciona al menos un área para evaluar.');
   if(!$redes)throw new Exception('Selecciona al menos una red o enlace para mostrar en calificaciones positivas.');
   $marks=implode(',',array_fill(0,count($areas),'?'));
   $st=$db->prepare("SELECT id_area_catalogo,nombre FROM encuesta_areas_catalogo WHERE id_hotel=? AND estado='ACTIVA' AND id_area_catalogo IN ($marks) ORDER BY nombre");
   $st->execute(array_merge([$idHotel],$areas));$areasValidas=$st->fetchAll(PDO::FETCH_ASSOC);
   if(count($areasValidas)!==count($areas))throw new Exception('Una de las áreas seleccionadas no es válida.');
   $db->beginTransaction();
   $db->prepare("INSERT INTO encuestas(id_hotel,titulo,descripcion,umbral_publicar) VALUES(?,?,?,?)")->execute([$idHotel,$titulo,$descripcion,$umbral]);
   $id=(int)$db->lastInsertId();
   $stA=$db->prepare("INSERT INTO encuesta_areas(id_encuesta,id_area_catalogo,nombre,orden) VALUES(?,?,?,?)");
   foreach($areasValidas as $i=>$a)$stA->execute([$id,(int)$a['id_area_catalogo'],$a['nombre'],$i+1]);
   $stR=$db->prepare("INSERT INTO encuesta_redes(id_encuesta,red) VALUES(?,?)");
   foreach($redes as $red)$stR->execute([$id,$red]);
   $db->commit();$msg='Encuesta creada correctamente.';
  }
  if($accion==='eliminar_encuesta'){
   $id=(int)($_POST['id_encuesta']??0);
   $db->prepare("DELETE FROM encuestas WHERE id_encuesta=? AND id_hotel=?")->execute([$id,$idHotel]);
   $msg='Encuesta eliminada.';
  }
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();$err=$e->getMessage();}
}
$st=$db->prepare("SELECT * FROM hotel_redes_sociales WHERE id_hotel=?");$st->execute([$idHotel]);$redes=$st->fetch(PDO::FETCH_ASSOC)?:[];
$st=$db->prepare("SELECT * FROM encuesta_areas_catalogo WHERE id_hotel=? AND estado='ACTIVA' ORDER BY nombre");$st->execute([$idHotel]);$catalogoAreas=$st->fetchAll(PDO::FETCH_ASSOC);
$st=$db->prepare("SELECT e.*,COUNT(DISTINCT a.id_area) total_areas,COUNT(DISTINCT r.id_respuesta) respuestas,GROUP_CONCAT(DISTINCT er.red ORDER BY er.red) redes FROM encuestas e LEFT JOIN encuesta_areas a ON a.id_encuesta=e.id_encuesta LEFT JOIN encuesta_respuestas r ON r.id_encuesta=e.id_encuesta LEFT JOIN encuesta_redes er ON er.id_encuesta=e.id_encuesta WHERE e.id_hotel=? GROUP BY e.id_encuesta ORDER BY e.id_encuesta DESC");$st->execute([$idHotel]);$encuestas=$st->fetchAll(PDO::FETCH_ASSOC);
$st=$db->prepare("SELECT nombre_comercial,slug FROM hoteles WHERE id_hotel=?");$st->execute([$idHotel]);$hotel=$st->fetch(PDO::FETCH_ASSOC)?:[];
$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$host=$_SERVER['HTTP_HOST']??'localhost';$base=rtrim(dirname($_SERVER['SCRIPT_NAME']??''),'/');
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Configurar encuestas</title><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><style>
*{box-sizing:border-box}body{margin:0;background:#f4f7fb;color:#152231;font-family:Poppins}.top{background:#162330;color:#fff;padding:16px 24px;display:flex;justify-content:space-between;align-items:center}.top a{background:#fff;color:#162330;padding:10px 14px;border-radius:12px;text-decoration:none;font-weight:800}.wrap{max-width:1240px;margin:26px auto;padding:0 18px}.head{display:flex;justify-content:space-between;gap:14px;align-items:center}.tabs{display:flex;gap:10px}.tabs a{padding:11px 15px;border-radius:12px;background:#e8eef5;color:#334155;text-decoration:none;font-weight:800}.tabs .active{background:#2563eb;color:#fff}.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.card{background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:22px;box-shadow:0 15px 40px #0f172a0d;margin-bottom:18px}.field{margin-bottom:12px}.field label{display:block;font-size:12px;font-weight:800;color:#475569;margin-bottom:6px}.field input,.field textarea,.field select{width:100%;padding:12px;border:1px solid #cfd9e5;border-radius:11px;font-family:Poppins}.field textarea{min-height:90px}.btn{border:0;border-radius:11px;padding:11px 15px;font-weight:800;cursor:pointer;font-family:Poppins}.blue{background:#2563eb;color:#fff}.green{background:#16a06a;color:#fff}.red{background:#fee2e2;color:#991b1b}.alert{padding:12px 14px;border-radius:12px;margin:12px 0;font-weight:700}.ok{background:#dcfce7;color:#166534}.bad{background:#fee2e2;color:#991b1b}.item{border:1px solid #e1e8f0;border-radius:15px;padding:15px;margin:10px 0;background:#f9fbfd}.row{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}.small{font-size:12px;color:#64748b}.actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.actions a{padding:8px 10px;border-radius:9px;background:#eef2ff;color:#1d4ed8;text-decoration:none;font-weight:800;font-size:12px}.checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.check{display:flex;align-items:center;gap:8px;padding:10px;border:1px solid #dbe4ee;border-radius:11px;background:#f8fafc;font-size:12px;font-weight:700}.check input{width:auto}.area-line{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px;border:1px solid #e2e8f0;border-radius:11px;margin-top:8px}@media(max-width:850px){.grid{grid-template-columns:1fr}.head{align-items:flex-start;flex-direction:column}.checks{grid-template-columns:1fr}}
</style></head><body><header class="top"><b><i class="fa-solid fa-square-poll-horizontal"></i> Encuestas · <?=htmlspecialchars($hotel['nombre_comercial']??'Hotel')?></b><a href="recepcion.php">Volver</a></header><main class="wrap"><div class="head"><div><h1>Crear y configurar encuestas</h1><p class="small">Primero crea las áreas. Después arma encuestas pequeñas o grandes seleccionando las que necesites.</p></div><div class="tabs"><a class="active" href="encuestas.php">Configurar</a><a href="encuestas_resultados.php">Ver resultados</a></div></div><?php if($msg):?><div class="alert ok"><?=htmlspecialchars($msg)?></div><?php endif;?><?php if($err):?><div class="alert bad"><?=htmlspecialchars($err)?></div><?php endif;?>
<div class="grid"><section class="card"><h2>1. Áreas de evaluación</h2><form method="post"><input type="hidden" name="accion" value="crear_area"><div class="field"><label>Nombre del área</label><input name="nombre_area" required placeholder="Ej: Recepción, Limpieza, Desayuno"></div><div class="field"><label>Descripción (opcional)</label><input name="descripcion_area" placeholder="Qué aspecto se evaluará"></div><button class="btn green">Agregar área</button></form><?php if(!$catalogoAreas):?><p class="small">Aún no hay áreas. Crea al menos una antes de crear encuestas.</p><?php endif;?><?php foreach($catalogoAreas as $a):?><div class="area-line"><div><b><?=htmlspecialchars($a['nombre'])?></b><?php if($a['descripcion']):?><div class="small"><?=htmlspecialchars($a['descripcion'])?></div><?php endif;?></div><form method="post" onsubmit="return confirm('¿Eliminar esta área? Solo se permitirá si no fue usada en encuestas.')"><input type="hidden" name="accion" value="eliminar_area"><input type="hidden" name="id_area_catalogo" value="<?=(int)$a['id_area_catalogo']?>"><button class="btn red" type="submit"><i class="fa-solid fa-trash"></i></button></form></div><?php endforeach;?></section>
<section class="card"><h2>Redes sociales y reseñas</h2><p class="small">Guarda los enlaces del hotel. En cada encuesta podrás elegir cuáles mostrar.</p><form method="post"><input type="hidden" name="accion" value="guardar_redes"><?php foreach($redesDisponibles as $k=>$l):?><div class="field"><label><?=$l?></label><input type="url" name="<?=$k?>" value="<?=htmlspecialchars($redes[$k]??'')?>" placeholder="https://..."></div><?php endforeach;?><button class="btn green">Guardar enlaces</button></form></section></div>
<section class="card"><h2>2. Nueva encuesta</h2><form method="post"><input type="hidden" name="accion" value="crear_encuesta"><div class="grid"><div><div class="field"><label>Título</label><input name="titulo" required placeholder="Experiencia de estadía"></div><div class="field"><label>Descripción</label><textarea name="descripcion" placeholder="Ayúdanos a mejorar tu experiencia..."></textarea></div><div class="field"><label>Mostrar enlaces públicos desde</label><select name="umbral_publicar"><option value="4">4 estrellas</option><option value="5">5 estrellas</option></select></div></div><div><div class="field"><label>Áreas que tendrá la encuesta</label><div class="checks"><?php foreach($catalogoAreas as $a):?><label class="check"><input type="checkbox" name="areas[]" value="<?=(int)$a['id_area_catalogo']?>"> <?=htmlspecialchars($a['nombre'])?></label><?php endforeach;?></div></div><div class="field"><label>Enlaces que aparecerán al final de una calificación positiva</label><div class="checks"><?php foreach($redesDisponibles as $k=>$l):?><label class="check"><input type="checkbox" name="redes[]" value="<?=$k?>" <?=empty($redes[$k])?'disabled':''?>> <?=$l?><?=empty($redes[$k])?' (sin enlace)':''?></label><?php endforeach;?></div></div></div></div><button class="btn blue" <?=$catalogoAreas?'':'disabled'?>>Crear encuesta</button></form></section>
<section class="card"><h2>Encuestas creadas</h2><?php if(!$encuestas):?><p class="small">Aún no hay encuestas.</p><?php endif;?><?php foreach($encuestas as $e):$url=$scheme.'://'.$host.$base.'/encuesta_publica.php?hotel='.rawurlencode($hotel['slug']??'').'&encuesta='.(int)$e['id_encuesta'];?><div class="item"><div class="row"><div><b><?=htmlspecialchars($e['titulo'])?></b><div class="small"><?=(int)$e['total_areas']?> áreas · <?=(int)$e['respuestas']?> respuestas · Umbral <?=number_format((float)$e['umbral_publicar'],1)?>★</div><div class="small">Redes: <?=htmlspecialchars($e['redes']?:'Ninguna')?></div></div><span class="small"><b><?=$e['estado']?></b></span></div><div class="actions" style="margin-top:12px"><a href="<?=htmlspecialchars($url)?>" target="_blank">Abrir encuesta</a><a href="#" onclick='navigator.clipboard.writeText(<?=json_encode($url)?>);return false'>Copiar enlace</a><form method="post" onsubmit="return confirm('¿Eliminar esta encuesta? También se eliminarán sus respuestas y resultados.')"><input type="hidden" name="accion" value="eliminar_encuesta"><input type="hidden" name="id_encuesta" value="<?=(int)$e['id_encuesta']?>"><button class="btn red" type="submit"><i class="fa-solid fa-trash"></i> Eliminar</button></form></div></div><?php endforeach;?></section></main></body></html>
