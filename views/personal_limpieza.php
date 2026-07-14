<?php
require_once '../config/Database.php';
require_once '../config/auth.php';
requerir_login(['admin_hotel','admin']);
$db=(new Database())->connect();
$idHotel=(int)id_hotel_actual();
$error='';
function t($v){return trim((string)($v??''));}
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  $accion=t($_POST['accion']??'');
  if($accion==='crear'){
   $n=t($_POST['nombres']??'');$a=t($_POST['apellidos']??'');$dni=t($_POST['dni']??'');$tel=t($_POST['telefono']??'');$obs=t($_POST['observacion']??'');
   if($n==='') throw new Exception('Ingresa el nombre del trabajador.');
   $q=$db->prepare("INSERT INTO personal_limpieza(id_hotel,nombres,apellidos,dni,telefono,observacion) VALUES(?,?,?,?,?,?)");
   $q->execute([$idHotel,$n,$a?:null,$dni?:null,$tel?:null,$obs?:null]);
  }elseif($accion==='estado'){
   $q=$db->prepare("UPDATE personal_limpieza SET estado=IF(estado='ACTIVO','INACTIVO','ACTIVO') WHERE id_personal=? AND id_hotel=?");$q->execute([(int)$_POST['id_personal'],$idHotel]);
  }elseif($accion==='eliminar'){
   $q=$db->prepare("DELETE FROM personal_limpieza WHERE id_personal=? AND id_hotel=?");$q->execute([(int)$_POST['id_personal'],$idHotel]);
  }
  header('Location: personal_limpieza.php');exit;
 }catch(Throwable $e){$error=$e->getMessage();}
}
$q=$db->prepare("SELECT * FROM personal_limpieza WHERE id_hotel=? ORDER BY estado,nombres,apellidos");$q->execute([$idHotel]);$items=$q->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Personal de limpieza</title><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet"><style>body{font-family:Poppins;margin:0;background:#f4f7fb;color:#172235}.top{background:#172736;color:#fff;padding:16px 24px;display:flex;justify-content:space-between}.top a{background:#fff;color:#172736;padding:9px 13px;border-radius:10px;text-decoration:none;font-weight:800}.wrap{max-width:1100px;margin:24px auto;padding:0 16px}.grid{display:grid;grid-template-columns:360px 1fr;gap:18px}.card{background:#fff;border:1px solid #dfe7ef;border-radius:18px;padding:20px;box-shadow:0 10px 30px #10203012}.field{margin-bottom:12px}.field label{display:block;font-size:12px;font-weight:800;margin-bottom:5px}.field input,.field textarea{width:100%;box-sizing:border-box;padding:11px;border:1px solid #d7e0ea;border-radius:10px;font-family:Poppins}.btn{border:0;border-radius:10px;padding:10px 13px;font-weight:800;cursor:pointer}.green{background:#16a06a;color:#fff}.gray{background:#e9eef4}.red{background:#fee2e2;color:#991b1b}table{width:100%;border-collapse:collapse}th,td{padding:11px;border-bottom:1px solid #e5edf4;text-align:left;font-size:13px}.badge{padding:5px 8px;border-radius:999px;background:#dcfce7;color:#166534;font-weight:800;font-size:11px}.off{background:#e2e8f0;color:#475569}@media(max-width:800px){.grid{grid-template-columns:1fr}}</style></head><body><header class="top"><b>Personal de limpieza</b><a href="operaciones_habitaciones.php">Volver</a></header><main class="wrap"><?php if($error):?><div class="card" style="background:#fee2e2;color:#991b1b"><?=htmlspecialchars($error)?></div><?php endif;?><div class="grid"><section class="card"><h2>Nuevo trabajador</h2><form method="post"><input type="hidden" name="accion" value="crear"><div class="field"><label>Nombres</label><input name="nombres" required></div><div class="field"><label>Apellidos</label><input name="apellidos"></div><div class="field"><label>DNI</label><input name="dni"></div><div class="field"><label>Teléfono</label><input name="telefono"></div><div class="field"><label>Observación</label><textarea name="observacion"></textarea></div><button class="btn green">Guardar trabajador</button></form></section><section class="card"><h2>Trabajadores registrados</h2><table><thead><tr><th>Trabajador</th><th>DNI</th><th>Teléfono</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><?php foreach($items as $i):?><tr><td><b><?=htmlspecialchars(trim($i['nombres'].' '.$i['apellidos']))?></b></td><td><?=htmlspecialchars($i['dni']?:'-')?></td><td><?=htmlspecialchars($i['telefono']?:'-')?></td><td><span class="badge <?=$i['estado']==='ACTIVO'?'':'off'?>"><?=htmlspecialchars($i['estado'])?></span></td><td><form method="post" style="display:inline"><input type="hidden" name="accion" value="estado"><input type="hidden" name="id_personal" value="<?=$i['id_personal']?>"><button class="btn gray">Cambiar estado</button></form> <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar trabajador?')"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id_personal" value="<?=$i['id_personal']?>"><button class="btn red">Eliminar</button></form></td></tr><?php endforeach;?></tbody></table></section></div></main></body></html>
