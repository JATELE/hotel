<?php
require_once '../config/Database.php';
require_once '../config/auth.php';
requerir_login(['admin_hotel','admin']);
$db=(new Database())->connect();
$idHotel=(int)id_hotel_actual();
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    $tipo=$_POST['tipo']??''; $nombre=trim((string)($_POST['nombre']??''));
    if($nombre==='') throw new Exception('Escribe un nombre.');
    if($tipo==='servicio'){
      $st=$db->prepare("INSERT INTO servicios_catalogo(id_hotel,nombre,estado) VALUES(?,?,'ACTIVO') ON DUPLICATE KEY UPDATE estado='ACTIVO'");
    }else{
      $st=$db->prepare("INSERT INTO beneficios_catalogo(id_hotel,nombre,estado) VALUES(?,?,'ACTIVO') ON DUPLICATE KEY UPDATE estado='ACTIVO'");
    }
    $st->execute([$idHotel,$nombre]); header('Location: catalogos.php'); exit;
  }catch(Throwable $e){$error=$e->getMessage();}
}
$serv=$db->prepare("SELECT * FROM servicios_catalogo WHERE id_hotel=? ORDER BY estado,nombre");$serv->execute([$idHotel]);$servicios=$serv->fetchAll(PDO::FETCH_ASSOC);
$ben=$db->prepare("SELECT * FROM beneficios_catalogo WHERE id_hotel=? ORDER BY estado,nombre");$ben->execute([$idHotel]);$beneficios=$ben->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Catálogos | CRM Hoteles</title><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet"><style>*{box-sizing:border-box}body{margin:0;font-family:Poppins;background:#f4f7fb;color:#13202b}.wrap{max-width:1100px;margin:35px auto;padding:0 18px}.top{display:flex;justify-content:space-between;align-items:center}.top a{background:#162330;color:#fff;padding:11px 15px;border-radius:12px;text-decoration:none;font-weight:700}.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.card{background:#fff;border:1px solid #e5edf5;border-radius:20px;padding:22px;box-shadow:0 15px 40px #0f172a0e}input{width:100%;padding:12px;border:1px solid #d8e2ed;border-radius:12px;font-family:inherit}button{border:0;background:#16a06a;color:#fff;border-radius:12px;padding:12px 16px;font-weight:800;margin-top:10px}.item{display:flex;justify-content:space-between;padding:11px;border-bottom:1px solid #edf2f7}.badge{font-size:11px;font-weight:800;color:#166534;background:#dcfce7;padding:5px 9px;border-radius:999px}@media(max-width:800px){.grid{grid-template-columns:1fr}}</style></head><body><main class="wrap"><div class="top"><div><h1>Catálogos del hotel</h1><p>Agrega servicios y beneficios para marcarlos rápidamente en cada categoría.</p></div><a href="habitaciones.php">Volver</a></div><?php if($error):?><p style="color:#b91c1c"><?=$error?></p><?php endif;?><div class="grid"><section class="card"><h2>Servicios</h2><form method="post"><input type="hidden" name="tipo" value="servicio"><input name="nombre" placeholder="Ej: Sauna, Minibar" required><button>Agregar servicio</button></form><?php foreach($servicios as $x):?><div class="item"><span><?=htmlspecialchars($x['nombre'])?></span><span class="badge"><?=$x['estado']?></span></div><?php endforeach;?></section><section class="card"><h2>Incluye / beneficios</h2><form method="post"><input type="hidden" name="tipo" value="beneficio"><input name="nombre" placeholder="Ej: Cena, Traslado al aeropuerto" required><button>Agregar beneficio</button></form><?php foreach($beneficios as $x):?><div class="item"><span><?=htmlspecialchars($x['nombre'])?></span><span class="badge"><?=$x['estado']?></span></div><?php endforeach;?></section></div></main></body></html>
