<?php
require_once '../config/Database.php';
require_once '../config/auth.php';
requerir_login(['superadmin']);
$db = (new Database())->connect();
$msg = '';
$tipoMsg = 'ok';

function generar_slug(string $texto): string {
    $texto = trim(mb_strtolower($texto, 'UTF-8'));
    $texto = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $texto);
    $texto = preg_replace('/[^a-z0-9]+/u', '-', $texto);
    return trim($texto, '-');
}
function slug_unico(PDO $db, string $base): string {
    $base = $base !== '' ? $base : 'hotel';
    $slug = $base;
    $i = 2;
    $stmt = $db->prepare("SELECT COUNT(*) FROM hoteles WHERE slug = ?");
    while (true) {
        $stmt->execute([$slug]);
        if ((int)$stmt->fetchColumn() === 0) return $slug;
        $slug = $base . '-' . $i;
        $i++;
    }
}
function url_webreservas_actual(string $slug): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/crmhoteles/api/views/superadmin.php'), '/');
    return $scheme . '://' . $host . $base . '/webreservas.html?hotel=' . rawurlencode($slug);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear_hotel') {
        $meses = (int)($_POST['meses'] ?? 1);
        $fechaInicio = date('Y-m-d');
        $fechaFin = date('Y-m-d', strtotime("+$meses months"));
        $db->beginTransaction();
        try {
            $slugBase = generar_slug($_POST['slug'] ?? $_POST['nombre_comercial'] ?? 'hotel');
            $slug = slug_unico($db, $slugBase);
            $stmt = $db->prepare("INSERT INTO hoteles (id_plan,ruc,razon_social,nombre_comercial,slug,direccion,email,whatsapp,fecha_inicio_plan,fecha_fin_plan,estado) VALUES (?,?,?,?,?,?,?,?,?,?,'ACTIVO')");
            $stmt->execute([$_POST['id_plan'] ?: null, trim($_POST['ruc']), trim($_POST['razon_social']), trim($_POST['nombre_comercial']), $slug, trim($_POST['direccion']), trim($_POST['email']), trim($_POST['whatsapp']), $fechaInicio, $fechaFin]);
            $idHotel = $db->lastInsertId();
            $hash = password_hash($_POST['password'] ?: 'admin123', PASSWORD_DEFAULT);
            $stmtUser = $db->prepare("INSERT INTO usuarios (id_hotel,id_rol,usuario,email,password_hash,nombres,apellidos,telefono,estado) VALUES (?,2,?,?,?,?,?,?,'ACTIVO')");
            $stmtUser->execute([$idHotel, trim($_POST['usuario_admin']), trim($_POST['email_admin']), $hash, trim($_POST['nombres_admin']), trim($_POST['apellidos_admin']), trim($_POST['whatsapp'])]);
            $db->commit();
            $msg = 'Hotel y administrador registrados correctamente. Slug generado: ' . $slug;
        } catch(Exception $e) { $db->rollBack(); $tipoMsg='error'; $msg = 'Error: ' . $e->getMessage(); }
    }
    if ($accion === 'estado_hotel') {
        $stmt = $db->prepare("UPDATE hoteles SET estado = ? WHERE id_hotel = ?");
        $stmt->execute([$_POST['estado'], (int)$_POST['id_hotel']]);
        $msg = 'Estado del hotel actualizado.';
    }
}
$planes = $db->query("SELECT * FROM planes WHERE estado='ACTIVO' ORDER BY meses")->fetchAll();
$hoteles = $db->query("SELECT h.*, p.nombre_plan,
    (SELECT COUNT(*) FROM usuarios u WHERE u.id_hotel=h.id_hotel AND u.id_rol=3) recepcionistas,
    (SELECT COUNT(*) FROM reservas r WHERE r.id_hotel=h.id_hotel) reservas
    FROM hoteles h LEFT JOIN planes p ON p.id_plan=h.id_plan ORDER BY h.id_hotel DESC")->fetchAll();
$totalHoteles=count($hoteles);
$activos=count(array_filter($hoteles, fn($h)=>$h['estado']==='ACTIVO'));
$vencenPronto=count(array_filter($hoteles, fn($h)=>strtotime($h['fecha_fin_plan']) <= strtotime('+15 days')));
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Superadmin | CRM Hoteles</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--nav:#162330;--gold:#d4af37;--bg:#f4f7fb;--text:#13202b;--muted:#64748b;--line:#e6edf4;--green:#16a06a;--red:#ef4444}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at top right,#fff7df 0,#eef4fa 28%,#f7f8fb 70%);font-family:Poppins,Arial;color:var(--text)}.top{background:linear-gradient(135deg,#111c27,#24384c);color:white;padding:18px 28px;display:flex;justify-content:space-between;align-items:center;gap:18px;box-shadow:0 14px 34px #0f172a2b;position:sticky;top:0;z-index:10}.brand{display:flex;gap:12px;align-items:center;font-weight:900}.brand i{color:var(--gold);font-size:26px}.brand small{display:block;color:#b8c6d3;font-size:12px;margin-top:3px}.top a{background:var(--gold);color:#13202b;text-decoration:none;font-weight:900;border-radius:12px;padding:10px 14px}.wrap{max-width:1240px;margin:30px auto;padding:0 18px}.hero{display:grid;grid-template-columns:1.3fr .7fr;gap:18px;align-items:stretch;margin-bottom:20px}.hero-card{background:linear-gradient(135deg,#162330,#2f465e);color:white;border-radius:26px;padding:28px;box-shadow:0 22px 60px #0f172a20;overflow:hidden;position:relative}.hero-card:after{content:"";position:absolute;right:-45px;bottom:-45px;width:180px;height:180px;border-radius:50%;background:#d4af3733}.hero-card h1{margin:0;font-size:32px}.hero-card p{color:#d8e3ef;font-weight:500;max-width:720px}.quick{background:white;border:1px solid var(--line);border-radius:26px;padding:22px;box-shadow:0 18px 50px #0f172a12}.quick b{font-size:15px}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:14px}.stat{background:#f8fafc;border:1px solid var(--line);border-radius:18px;padding:16px}.stat span{display:block;font-size:11px;color:var(--muted);font-weight:900;text-transform:uppercase}.stat strong{font-size:28px}.card{background:white;border:1px solid var(--line);border-radius:24px;padding:22px;box-shadow:0 18px 50px #0f172a12;margin-bottom:22px}h2{margin:0 0 16px}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.field label{display:block;font-size:12px;font-weight:900;color:#334155;margin-bottom:7px}.field input,.field select{width:100%;padding:12px 13px;border:1px solid #d7e0ea;border-radius:13px;background:#fbfdff;font-family:Poppins}.btn{background:var(--gold);color:#13202b;border:0;border-radius:13px;padding:12px 16px;font-weight:900;cursor:pointer;font-family:Poppins}.msg{padding:13px 15px;border-radius:14px;margin-bottom:16px;font-weight:800}.msg.ok{background:#e9fbf2;color:#087344;border:1px solid #b8ebce}.msg.error{background:#fff1f2;color:#be123c;border:1px solid #fecdd3}.table-wrap{overflow-x:auto;padding-bottom:8px;scrollbar-width:thin}.table-wrap::-webkit-scrollbar{height:8px}.table-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}table{width:100%;min-width:1320px;border-collapse:separate;border-spacing:0 10px;table-layout:auto}th{font-size:11px;text-transform:uppercase;color:#64748b;text-align:left;padding:0 12px}td{background:#fbfdff;border-top:1px solid var(--line);border-bottom:1px solid var(--line);padding:14px 12px}td:first-child{border-left:1px solid var(--line);border-radius:14px 0 0 14px}td:last-child{border-right:1px solid var(--line);border-radius:0 14px 14px 0}.status{display:inline-flex;padding:7px 10px;border-radius:999px;font-weight:900;font-size:12px}.status.ACTIVO{background:#dcfce7;color:#166534}.status.VENCIDO{background:#ffedd5;color:#9a3412}.status.SUSPENDIDO{background:#fee2e2;color:#991b1b}.actions{display:flex;gap:8px;align-items:center;white-space:nowrap}.actions select{padding:9px;border-radius:10px;border:1px solid #d7e0ea;min-width:125px}.hotel-cell{min-width:250px}.iframe-cell{min-width:230px}.action-cell{min-width:265px}.btn.compact{padding:10px 12px;white-space:nowrap}.link-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}.btn.secondary{background:#eef2f7;color:#233548}.table-wrap td,.table-wrap th{vertical-align:middle}@media(max-width:950px){.hero,.stats,.grid{grid-template-columns:1fr}.top{flex-direction:column;align-items:flex-start}}
</style></head><body><header class="top"><div class="brand"><i class="fa-solid fa-building-shield"></i><div>CRM Hoteles · Superadmin<small>Panel maestro de hoteles, planes y accesos</small></div></div><div><?= htmlspecialchars($_SESSION['usuario']['nombres']) ?> <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a></div></header>
<main class="wrap"><?php if($msg): ?><div class="msg <?= $tipoMsg ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?><section class="hero"><div class="hero-card"><h1>Administración central de hoteles</h1><p>Registra empresas hoteleras, asigna planes por meses y crea el usuario administrador de cada hotel. Cada hotel queda aislado con sus propias reservas, categorías, recepcionistas y reportes.</p></div><div class="quick"><b>Resumen general</b><div class="stats"><div class="stat"><span>Hoteles</span><strong><?= $totalHoteles ?></strong></div><div class="stat"><span>Activos</span><strong><?= $activos ?></strong></div><div class="stat"><span>Vencen pronto</span><strong><?= $vencenPronto ?></strong></div></div></div></section>
<section class="card"><h2><i class="fa-solid fa-hotel"></i> Registrar nuevo hotel</h2><form method="post"><input type="hidden" name="accion" value="crear_hotel"><div class="grid"><div class="field"><label>RUC</label><input name="ruc" required></div><div class="field"><label>Razón social</label><input name="razon_social" required></div><div class="field"><label>Nombre comercial</label><input name="nombre_comercial" id="nombre_comercial" required oninput="sugerirSlug()"></div><div class="field"><label>Slug / URL pública</label><input name="slug" id="slug_hotel" placeholder="ej: hotel-paraiso"></div><div class="field"><label>Dirección</label><input name="direccion"></div><div class="field"><label>Email hotel</label><input name="email" type="email"></div><div class="field"><label>WhatsApp hotel</label><input name="whatsapp"></div><div class="field"><label>Plan</label><select name="id_plan" id="id_plan" onchange="sincronizarPlanMeses()" required><?php foreach($planes as $p): ?><option value="<?= $p['id_plan'] ?>" data-meses="<?= (int)$p['meses'] ?>"><?= htmlspecialchars($p['nombre_plan']) ?></option><?php endforeach; ?></select></div><div class="field"><label>Meses de acceso</label><input name="meses" id="meses_acceso" type="number" min="1" value="1" required readonly title="Se completa automáticamente según el plan seleccionado"></div><div class="field"><label>Vencimiento estimado</label><input id="fecha_fin_estimada" readonly></div><div class="field"><label>Usuario admin</label><input name="usuario_admin" required></div><div class="field"><label>Email admin</label><input name="email_admin" type="email" required></div><div class="field"><label>Nombres admin</label><input name="nombres_admin" required></div><div class="field"><label>Apellidos admin</label><input name="apellidos_admin"></div><div class="field"><label>Contraseña inicial</label><input name="password" placeholder="admin123 si se deja vacío"></div></div><br><button class="btn"><i class="fa-solid fa-floppy-disk"></i> Registrar hotel y admin</button></form></section>
<section class="card"><h2><i class="fa-solid fa-list"></i> Hoteles registrados</h2><div class="table-wrap"><table><thead><tr><th>ID</th><th>Hotel</th><th>Slug / iframe</th><th>RUC</th><th>Plan</th><th>Vence</th><th>Reservas</th><th>Recep.</th><th>Estado</th><th>Acción</th></tr></thead><tbody><?php foreach($hoteles as $h): ?><tr><td><?= $h['id_hotel'] ?></td><td class="hotel-cell"><b><?= htmlspecialchars($h['nombre_comercial']) ?></b><br><small><?= htmlspecialchars($h['email']) ?> · <?= htmlspecialchars($h['whatsapp']) ?></small></td><td class="iframe-cell"><b><?= htmlspecialchars($h['slug'] ?? '') ?></b><div class="link-actions"><button type="button" class="btn compact" onclick="copiarIframe('<?= htmlspecialchars($h['slug'] ?? '', ENT_QUOTES) ?>')"><i class="fa-solid fa-code"></i> Copiar iframe</button><button type="button" class="btn compact secondary" onclick="copiarEnlace('<?= htmlspecialchars($h['slug'] ?? '', ENT_QUOTES) ?>')"><i class="fa-solid fa-link"></i> Copiar enlace</button></div></td><td><?= htmlspecialchars($h['ruc']) ?></td><td><?= htmlspecialchars($h['nombre_plan'] ?? '-') ?></td><td><?= $h['fecha_fin_plan'] ?></td><td><?= (int)$h['reservas'] ?></td><td><?= (int)$h['recepcionistas'] ?></td><td><span class="status <?= htmlspecialchars($h['estado']) ?>"><?= $h['estado'] ?></span></td><td class="action-cell"><form method="post" class="actions"><input type="hidden" name="accion" value="estado_hotel"><input type="hidden" name="id_hotel" value="<?= $h['id_hotel'] ?>"><select name="estado"><option <?= $h['estado']==='ACTIVO'?'selected':'' ?>>ACTIVO</option><option <?= $h['estado']==='VENCIDO'?'selected':'' ?>>VENCIDO</option><option <?= $h['estado']==='SUSPENDIDO'?'selected':'' ?>>SUSPENDIDO</option></select><button class="btn">Guardar</button></form></td></tr><?php endforeach; ?></tbody></table></div></section></main>
<script>
function slugify(txt){return String(txt||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'')}
function sugerirSlug(){const n=document.getElementById('nombre_comercial');const s=document.getElementById('slug_hotel');if(n&&s&&!s.dataset.editado){s.value=slugify(n.value)}}
document.addEventListener('input',e=>{if(e.target&&e.target.id==='slug_hotel')e.target.dataset.editado='1'});
function urlReserva(slug){const base=window.location.origin+window.location.pathname.replace(/superadmin\.php.*$/,'');return base+'webreservas.html?hotel='+encodeURIComponent(slug)}
function copiarEnlace(slug){navigator.clipboard.writeText(urlReserva(slug)).then(()=>alert('Enlace copiado'))}
function copiarIframe(slug){const url=urlReserva(slug);const iframe=`<iframe src="${url}" width="100%" height="900" style="border:0;border-radius:16px;overflow:hidden;" loading="lazy"></iframe>`;navigator.clipboard.writeText(iframe).then(()=>alert('Iframe copiado'))}
function sumarMeses(fecha, meses){
    const d = new Date(fecha.getFullYear(), fecha.getMonth() + meses, fecha.getDate());
    return d.toISOString().slice(0,10);
}
function sincronizarPlanMeses(){
    const select = document.getElementById('id_plan');
    const mesesInput = document.getElementById('meses_acceso');
    const fechaInput = document.getElementById('fecha_fin_estimada');
    if(!select || !mesesInput) return;
    const option = select.options[select.selectedIndex];
    const meses = option ? parseInt(option.dataset.meses || '1', 10) : 1;
    mesesInput.value = meses;
    if(fechaInput) fechaInput.value = sumarMeses(new Date(), meses);
}
document.addEventListener('DOMContentLoaded', sincronizarPlanMeses);
</script>
</body></html>
