<?php
require_once '../config/Database.php';
require_once '../config/auth.php';
requerir_login(['admin_hotel','admin']);

$db = (new Database())->connect();
$idHotel = (int)id_hotel_actual();
$usuario = $_SESSION['usuario'] ?? [];
$error = '';

function limpiar($v) { return trim((string)($v ?? '')); }
function lista_post(string $campo): string {
    $valor = $_POST[$campo] ?? [];
    if (!is_array($valor)) return limpiar($valor);
    $items = array_values(array_filter(array_map(fn($v) => limpiar($v), $valor), fn($v) => $v !== ''));
    return implode(', ', $items);
}
function volver() { header('Location: conecting.php'); exit; }
function slug_simple($texto) {
    $texto = strtolower(trim((string)$texto));
    $texto = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    return trim($texto, '-') ?: 'hotel';
}
function guardar_imagen_categoria($campo, $hotelSlug, $actual = '') {
    if (empty($_FILES[$campo]) || ($_FILES[$campo]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $actual;
    }
    if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se pudo subir la imagen ' . $campo . '.');
    }
    $permitidas = ['jpg','jpeg','png','webp'];
    $nombreOriginal = $_FILES[$campo]['name'] ?? '';
    $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
    if (!in_array($ext, $permitidas, true)) {
        throw new Exception('Formato no permitido en ' . $campo . '. Usa JPG, PNG o WEBP.');
    }
    if (($_FILES[$campo]['size'] ?? 0) > 4 * 1024 * 1024) {
        throw new Exception('La imagen ' . $campo . ' supera 4 MB.');
    }
    $dirRel = 'uploads/hoteles/' . slug_simple($hotelSlug) . '/categorias';
    $dirAbs = __DIR__ . '/../' . $dirRel;
    if (!is_dir($dirAbs)) mkdir($dirAbs, 0775, true);
    $nombre = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destino = $dirAbs . '/' . $nombre;
    if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
        throw new Exception('No se pudo guardar la imagen ' . $campo . '.');
    }
    return $dirRel . '/' . $nombre;
}

$stmtHotel = $db->prepare("SELECT nombre_comercial, slug, gestion_por_habitacion FROM hoteles WHERE id_hotel=? LIMIT 1");
$stmtHotel->execute([$idHotel]);
$hotel = $stmtHotel->fetch(PDO::FETCH_ASSOC) ?: [];
$hotelSlug = $hotel['slug'] ?? ('hotel-'.$idHotel);
$gestionPorHabitacion = ((int)($hotel['gestion_por_habitacion'] ?? 0) === 1);
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseViews = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/crmhoteles/api/views/habitaciones.php'), '/');
$webReservasPublica = $scheme . '://' . $host . $baseViews . '/webreservas.html?hotel=' . rawurlencode($hotelSlug);

// Catálogos editables del hotel (V4.5)
try {
    $q=$db->prepare("SELECT nombre FROM servicios_catalogo WHERE id_hotel=? AND estado='ACTIVO' ORDER BY nombre"); $q->execute([$idHotel]); $catalogoServicios=$q->fetchAll(PDO::FETCH_COLUMN);
    $q=$db->prepare("SELECT nombre FROM beneficios_catalogo WHERE id_hotel=? AND estado='ACTIVO' ORDER BY nombre"); $q->execute([$idHotel]); $catalogoBeneficios=$q->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $catalogoServicios=['WiFi','A/C','Baño privado','Balcón','Jacuzzi','TV','Agua caliente','Estacionamiento','Piscina'];
    $catalogoBeneficios=['Desayuno','Acceso a piscina','Limpieza diaria','Toallas','Amenities','Recepción 24 horas','Estacionamiento','Traslado'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'actualizar_gestion') {
            $gestion = isset($_POST['gestion_por_habitacion']) ? 1 : 0;
            $stmt = $db->prepare("UPDATE hoteles SET gestion_por_habitacion=? WHERE id_hotel=?");
            $stmt->execute([$gestion, $idHotel]);
            $_SESSION['usuario']['gestion_por_habitacion'] = $gestion;
            volver();
        }

        if ($accion === 'crear_categoria') {
            $nombre = limpiar($_POST['nombre']);
            $precio = (float)($_POST['precio_base'] ?? 0);
            $precioAnterior = $_POST['precio_anterior'] !== '' ? (float)$_POST['precio_anterior'] : null;
            $capacidad = max(1, (int)($_POST['capacidad_pax'] ?? 1));
            $etiqueta = limpiar($_POST['etiqueta']);
            $tipoCama = limpiar($_POST['tipo_cama']);
            $servicios = lista_post('servicios');
            $incluye = lista_post('incluye');
            $imagen1 = guardar_imagen_categoria('imagen_1', $hotelSlug);
            $imagen2 = guardar_imagen_categoria('imagen_2', $hotelSlug);
            $imagen3 = guardar_imagen_categoria('imagen_3', $hotelSlug);
            $galeria = limpiar($_POST['galeria_url']);
            if ($nombre === '' || $precio <= 0) throw new Exception('El nombre y el precio son obligatorios.');
            $stmt = $db->prepare("INSERT INTO categorias
                (id_hotel, nombre, slug, precio_base, precio_anterior, capacidad_pax, etiqueta, tipo_cama, servicios, incluye, imagen_1, imagen_2, imagen_3, galeria_url, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVO')");
            $stmt->execute([$idHotel, $nombre, slug_simple($nombre), $precio, $precioAnterior, $capacidad, $etiqueta, $tipoCama, $servicios, $incluye, $imagen1, $imagen2, $imagen3, $galeria]);
            volver();
        }

        if ($accion === 'actualizar_categoria') {
            $idCategoria = (int)($_POST['id_categoria'] ?? 0);
            $nombre = limpiar($_POST['nombre']);
            $precio = (float)($_POST['precio_base'] ?? 0);
            $precioAnterior = $_POST['precio_anterior'] !== '' ? (float)$_POST['precio_anterior'] : null;
            $capacidad = max(1, (int)($_POST['capacidad_pax'] ?? 1));
            $etiqueta = limpiar($_POST['etiqueta']);
            $tipoCama = limpiar($_POST['tipo_cama']);
            $servicios = lista_post('servicios');
            $incluye = lista_post('incluye');
            $imagen1 = guardar_imagen_categoria('imagen_1', $hotelSlug, limpiar($_POST['imagen_1_actual'] ?? ''));
            $imagen2 = guardar_imagen_categoria('imagen_2', $hotelSlug, limpiar($_POST['imagen_2_actual'] ?? ''));
            $imagen3 = guardar_imagen_categoria('imagen_3', $hotelSlug, limpiar($_POST['imagen_3_actual'] ?? ''));
            $galeria = limpiar($_POST['galeria_url']);
            $estado = in_array($_POST['estado'] ?? 'ACTIVO', ['ACTIVO','INACTIVO'], true) ? $_POST['estado'] : 'ACTIVO';
            if ($idCategoria <= 0 || $nombre === '' || $precio <= 0) throw new Exception('Datos incompletos para actualizar la categoría.');
            $stmt = $db->prepare("UPDATE categorias SET nombre=?, slug=?, precio_base=?, precio_anterior=?, capacidad_pax=?, etiqueta=?, tipo_cama=?, servicios=?, incluye=?, imagen_1=?, imagen_2=?, imagen_3=?, galeria_url=?, estado=? WHERE id_categoria=? AND id_hotel=?");
            $stmt->execute([$nombre, slug_simple($nombre), $precio, $precioAnterior, $capacidad, $etiqueta, $tipoCama, $servicios, $incluye, $imagen1, $imagen2, $imagen3, $galeria, $estado, $idCategoria, $idHotel]);
            volver();
        }

        if ($accion === 'crear_habitacion') {
            if (!$gestionPorHabitacion) throw new Exception('Activa la gestión por habitación para registrar cuartos reales.');

            $numero = limpiar($_POST['numero_habitacion']);
            $idCategoriaPrincipal = (int)($_POST['id_categoria_principal'] ?? 0);
            $idsCategorias = array_values(array_unique(array_map('intval', (array)($_POST['id_categorias'] ?? []))));
            $idsCategorias = array_values(array_filter($idsCategorias, fn($v) => $v > 0));
            $estado = in_array($_POST['estado'] ?? 'Disponible', ['Disponible','Ocupada','Mantenimiento'], true)
                ? $_POST['estado'] : 'Disponible';

            if ($numero === '' || $idCategoriaPrincipal <= 0) {
                throw new Exception('Ingresa el número y selecciona la categoría principal de la habitación.');
            }

            if (!in_array($idCategoriaPrincipal, $idsCategorias, true)) {
                $idsCategorias[] = $idCategoriaPrincipal;
            }

            $duplicada = $db->prepare("SELECT id_habitacion FROM habitaciones WHERE id_hotel=? AND numero_habitacion=? LIMIT 1");
            $duplicada->execute([$idHotel, $numero]);
            if ($duplicada->fetchColumn()) {
                throw new Exception('La habitación ' . $numero . ' ya está registrada en este hotel. Usa otro número o edita la existente.');
            }

            $marks = implode(',', array_fill(0, count($idsCategorias), '?'));
            $ver = $db->prepare("SELECT id_categoria, capacidad_pax FROM categorias WHERE id_hotel=? AND id_categoria IN ($marks)");
            $ver->execute(array_merge([$idHotel], $idsCategorias));
            $categoriasValidas = $ver->fetchAll(PDO::FETCH_KEY_PAIR);

            if (count($categoriasValidas) !== count($idsCategorias) || !isset($categoriasValidas[$idCategoriaPrincipal])) {
                throw new Exception('Una de las categorías seleccionadas no pertenece a este hotel.');
            }

            $capacidadPrincipal = (int)$categoriasValidas[$idCategoriaPrincipal];
            foreach ($idsCategorias as $idc) {
                if ((int)$categoriasValidas[$idc] > $capacidadPrincipal) {
                    throw new Exception('Una habitación no puede venderse en una categoría con mayor capacidad que su categoría principal.');
                }
            }

            try {
                $db->beginTransaction();
                $stmt = $db->prepare("INSERT INTO habitaciones (id_hotel, numero_habitacion, id_categoria, estado) VALUES (?, ?, ?, ?)");
                $stmt->execute([$idHotel, $numero, $idCategoriaPrincipal, $estado]);
                $idNueva = (int)$db->lastInsertId();

                $rel = $db->prepare("INSERT INTO habitacion_categorias(id_habitacion,id_categoria,estado) VALUES(?,?,'ACTIVO')");
                foreach ($idsCategorias as $idc) {
                    $rel->execute([$idNueva, $idc]);
                }
                $db->commit();
            } catch (PDOException $e) {
                if ($db->inTransaction()) $db->rollBack();
                if ((string)$e->getCode() === '23000') {
                    throw new Exception('Ese número de habitación ya está registrado en este hotel.');
                }
                throw $e;
            }
            volver();
        }

        if ($accion === 'actualizar_habitacion') {
            if (!$gestionPorHabitacion) throw new Exception('La gestión por habitación está desactivada.');

            $idHabitacion = (int)($_POST['id_habitacion'] ?? 0);
            $numero = limpiar($_POST['numero_habitacion']);
            $idCategoriaPrincipal = (int)($_POST['id_categoria_principal'] ?? 0);
            $idsCategorias = array_values(array_unique(array_map('intval', (array)($_POST['id_categorias'] ?? []))));
            $idsCategorias = array_values(array_filter($idsCategorias, fn($v) => $v > 0));
            $estado = in_array($_POST['estado'] ?? 'Disponible', ['Disponible','Ocupada','Mantenimiento'], true)
                ? $_POST['estado'] : 'Disponible';

            if ($idHabitacion <= 0 || $numero === '' || $idCategoriaPrincipal <= 0) {
                throw new Exception('Completa el número y selecciona la categoría principal.');
            }
            if (!in_array($idCategoriaPrincipal, $idsCategorias, true)) $idsCategorias[] = $idCategoriaPrincipal;

            $propia = $db->prepare("SELECT id_habitacion FROM habitaciones WHERE id_habitacion=? AND id_hotel=? LIMIT 1");
            $propia->execute([$idHabitacion, $idHotel]);
            if (!$propia->fetchColumn()) throw new Exception('La habitación no pertenece a este hotel.');

            $duplicada = $db->prepare("SELECT id_habitacion FROM habitaciones WHERE id_hotel=? AND numero_habitacion=? AND id_habitacion<>? LIMIT 1");
            $duplicada->execute([$idHotel, $numero, $idHabitacion]);
            if ($duplicada->fetchColumn()) {
                throw new Exception('La habitación ' . $numero . ' ya está registrada. Usa otro número.');
            }

            $marks = implode(',', array_fill(0, count($idsCategorias), '?'));
            $ver = $db->prepare("SELECT id_categoria, capacidad_pax FROM categorias WHERE id_hotel=? AND id_categoria IN ($marks)");
            $ver->execute(array_merge([$idHotel], $idsCategorias));
            $categoriasValidas = $ver->fetchAll(PDO::FETCH_KEY_PAIR);
            if (count($categoriasValidas) !== count($idsCategorias) || !isset($categoriasValidas[$idCategoriaPrincipal])) {
                throw new Exception('Una de las categorías seleccionadas no pertenece a este hotel.');
            }

            $capacidadPrincipal = (int)$categoriasValidas[$idCategoriaPrincipal];
            foreach ($idsCategorias as $idc) {
                if ((int)$categoriasValidas[$idc] > $capacidadPrincipal) {
                    throw new Exception('La habitación no puede venderse en una categoría con mayor capacidad que su categoría principal.');
                }
            }

            try {
                $db->beginTransaction();
                $stmt = $db->prepare("UPDATE habitaciones SET numero_habitacion=?, id_categoria=?, estado=? WHERE id_habitacion=? AND id_hotel=?");
                $stmt->execute([$numero, $idCategoriaPrincipal, $estado, $idHabitacion, $idHotel]);
                $db->prepare("DELETE FROM habitacion_categorias WHERE id_habitacion=?")->execute([$idHabitacion]);
                $rel = $db->prepare("INSERT INTO habitacion_categorias(id_habitacion,id_categoria,estado) VALUES(?,?,'ACTIVO')");
                foreach ($idsCategorias as $idc) $rel->execute([$idHabitacion, $idc]);
                $db->commit();
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                throw $e;
            }
            volver();
        }

        if ($accion === 'eliminar_habitacion') {
            if (!$gestionPorHabitacion) throw new Exception('La gestión por habitación está desactivada.');
            $idHabitacion = (int)($_POST['id_habitacion'] ?? 0);
            $stmt = $db->prepare("DELETE FROM habitaciones WHERE id_habitacion=? AND id_hotel=?");
            $stmt->execute([$idHabitacion, $idHotel]);
            volver();
        }


        if ($accion === 'crear_bloqueo') {
            if (!$gestionPorHabitacion) throw new Exception('Activa la gestión por habitación para programar inactividad de cuartos.');
            $idHabitacion = (int)($_POST['id_habitacion'] ?? 0);
            $fechaInicio = limpiar($_POST['fecha_inicio'] ?? '');
            $fechaFin = limpiar($_POST['fecha_fin'] ?? '');
            $motivo = limpiar($_POST['motivo'] ?? '');
            if ($idHabitacion <= 0 || $fechaInicio === '' || $fechaFin === '' || strtotime($fechaFin) <= strtotime($fechaInicio)) {
                throw new Exception('Selecciona habitación y un rango de fechas válido.');
            }
            $ver = $db->prepare("SELECT id_habitacion FROM habitaciones WHERE id_habitacion=? AND id_hotel=? LIMIT 1");
            $ver->execute([$idHabitacion, $idHotel]);
            if (!$ver->fetch()) throw new Exception('La habitación no pertenece a este hotel.');
            $cruce = $db->prepare("SELECT id_bloqueo, fecha_inicio, fecha_fin FROM habitaciones_bloqueos WHERE id_hotel=? AND id_habitacion=? AND estado<>'CANCELADO' AND fecha_inicio <= ? AND fecha_fin >= ? LIMIT 1");
            $cruce->execute([$idHotel,$idHabitacion,$fechaFin,$fechaInicio]);
            if($existente=$cruce->fetch(PDO::FETCH_ASSOC)) throw new Exception('La habitación ya está bloqueada del '.date('d/m/Y',strtotime($existente['fecha_inicio'])).' al '.date('d/m/Y',strtotime($existente['fecha_fin'])).'.');
            $stmt = $db->prepare("INSERT INTO habitaciones_bloqueos (id_hotel, id_habitacion, fecha_inicio, fecha_fin, motivo, estado) VALUES (?, ?, ?, ?, ?, 'ACTIVO')");
            $stmt->execute([$idHotel, $idHabitacion, $fechaInicio, $fechaFin, $motivo]);
            volver();
        }

        if ($accion === 'cancelar_bloqueo') {
            $idBloqueo = (int)($_POST['id_bloqueo'] ?? 0);
            $stmt = $db->prepare("UPDATE habitaciones_bloqueos SET estado='CANCELADO' WHERE id_bloqueo=? AND id_hotel=?");
            $stmt->execute([$idBloqueo, $idHotel]);
            volver();
        }
    } catch (Throwable $e) { $error = $e->getMessage(); }
}

$stmtCat = $db->prepare("SELECT c.*, COUNT(h.id_habitacion) total_habitaciones, SUM(CASE WHEN h.estado='Disponible' THEN 1 ELSE 0 END) disponibles FROM categorias c LEFT JOIN habitaciones h ON h.id_categoria=c.id_categoria AND h.id_hotel=c.id_hotel WHERE c.id_hotel=? GROUP BY c.id_categoria ORDER BY c.estado ASC, c.id_categoria DESC");
$stmtCat->execute([$idHotel]);
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

$stmtHab = $db->prepare("SELECT h.*, c.nombre categoria, c.capacidad_pax capacidad_principal, GROUP_CONCAT(DISTINCT hc.id_categoria ORDER BY hc.id_categoria) categorias_ids FROM habitaciones h INNER JOIN categorias c ON c.id_categoria=h.id_categoria LEFT JOIN habitacion_categorias hc ON hc.id_habitacion=h.id_habitacion AND hc.estado='ACTIVO' WHERE h.id_hotel=? GROUP BY h.id_habitacion ORDER BY h.numero_habitacion ASC");
$stmtHab->execute([$idHotel]);
$habitaciones = $stmtHab->fetchAll(PDO::FETCH_ASSOC);

$bloqueos = [];
if ($gestionPorHabitacion) {
    $stmtBloq = $db->prepare("SELECT b.*, h.numero_habitacion, c.nombre categoria
        FROM habitaciones_bloqueos b
        INNER JOIN habitaciones h ON h.id_habitacion=b.id_habitacion
        INNER JOIN categorias c ON c.id_categoria=h.id_categoria
        WHERE b.id_hotel=?
        ORDER BY b.estado ASC, b.fecha_inicio DESC, b.id_bloqueo DESC");
    $stmtBloq->execute([$idHotel]);
    $bloqueos = $stmtBloq->fetchAll(PDO::FETCH_ASSOC);
    $hoyBloqueos = date('Y-m-d');
    foreach ($bloqueos as &$bloqueo) {
        if (($bloqueo['estado'] ?? '') === 'CANCELADO') {
            $bloqueo['estado_visual'] = 'CANCELADO';
        } elseif ($hoyBloqueos < $bloqueo['fecha_inicio']) {
            $bloqueo['estado_visual'] = 'PROGRAMADO';
        } elseif ($hoyBloqueos > $bloqueo['fecha_fin']) {
            $bloqueo['estado_visual'] = 'FINALIZADO';
        } else {
            $bloqueo['estado_visual'] = 'BLOQUEADO';
        }
    }
    unset($bloqueo);
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Categorías y habitaciones | CRM Hoteles</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--nav:#162330;--gold:#d4af37;--bg:#f4f7fb;--text:#13202b;--muted:#64748b;--line:#e6edf4;--green:#16a06a;--red:#ef4444;--blue:#2563eb}*{box-sizing:border-box}body{font-family:Poppins,Arial;background:linear-gradient(180deg,#eef4fa 0,#f7f8fb 45%,#f8fafc 100%);margin:0;color:var(--text)}.header{background:linear-gradient(135deg,#162330,#24384c);color:white;padding:18px 26px;display:flex;justify-content:space-between;align-items:center;gap:18px;box-shadow:0 12px 30px #0f172a26;position:sticky;top:0;z-index:10}.brand{font-weight:800;display:flex;align-items:center;gap:12px}.brand i{color:var(--gold);font-size:24px}.brand small{display:block;color:#b8c6d3;font-size:12px;margin-top:3px}.nav{display:flex;gap:10px;flex-wrap:wrap}.nav a{background:#fff;color:#162330;text-decoration:none;font-weight:800;border-radius:12px;padding:10px 14px}.nav a.gold{background:var(--gold)}.wrap{max-width:1240px;margin:28px auto;padding:0 18px}.hero{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:18px}.hero h1{margin:0;font-size:30px}.hero p{margin:6px 0 0;color:var(--muted);font-weight:500}.grid{display:grid;grid-template-columns:1.1fr .9fr;gap:18px}.card{background:white;border:1px solid var(--line);border-radius:22px;padding:22px;box-shadow:0 18px 50px #0f172a12;margin-bottom:20px}.card h2{margin:0 0 16px}.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.full{grid-column:1/-1}.field label{font-size:12px;color:#475569;font-weight:800;text-transform:uppercase}.field input,.field select,.field textarea{width:100%;padding:12px;border:1px solid #dbe4ef;border-radius:12px;font-family:Poppins;background:#fbfdff;outline:none}.field textarea{min-height:82px;resize:vertical}.field small{display:block;color:#64748b;margin-top:6px}.btn{border:0;border-radius:13px;padding:12px 15px;font-family:Poppins;font-weight:900;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px}.btn.green{background:var(--green);color:white}.btn.blue{background:var(--blue);color:white}.btn.red{background:#fee2e2;color:#991b1b}.btn.gray{background:#f1f5f9;color:#334155}.table-wrap{overflow:auto}table{width:100%;border-collapse:separate;border-spacing:0 10px}th{font-size:11px;text-transform:uppercase;color:#64748b;text-align:left;padding:0 12px}td{background:#fbfdff;border-top:1px solid var(--line);border-bottom:1px solid var(--line);padding:13px 12px;vertical-align:top}td:first-child{border-left:1px solid var(--line);border-radius:14px 0 0 14px}td:last-child{border-right:1px solid var(--line);border-radius:0 14px 14px 0}.money{font-weight:900;color:#087344}.badge{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border-radius:999px;font-weight:800;font-size:12px;background:#ecfdf5;color:#166534}.badge.off{background:#f1f5f9;color:#475569}.badge.warn{background:#fef3c7;color:#92400e}.badge.red{background:#fee2e2;color:#991b1b}.actions{display:flex;gap:8px;flex-wrap:wrap}.alert{padding:13px 15px;border-radius:14px;margin-bottom:14px;font-weight:700}.alert.err{background:#fee2e2;color:#991b1b}.setting{display:flex;justify-content:space-between;align-items:center;gap:15px;background:#fff7df;border:1px solid #f5dc91;border-radius:20px;padding:18px;margin-bottom:18px}.switch{display:flex;align-items:center;gap:10px;font-weight:900}.switch input{width:22px;height:22px}.muted{color:#64748b}.modal{display:none;position:fixed;inset:0;background:rgba(15,23,42,.58);z-index:9999;align-items:center;justify-content:center;padding:20px}.modal.show{display:flex}.modal-box{background:white;border-radius:22px;max-width:760px;width:100%;max-height:92vh;overflow:auto;padding:22px;box-shadow:0 30px 70px rgba(0,0,0,.35)}.modal-head{display:flex;justify-content:space-between;align-items:center}.close{border:0;background:#f1f5f9;border-radius:50%;width:38px;height:38px;cursor:pointer}.thumb{width:70px;height:50px;object-fit:cover;border-radius:10px;border:1px solid #e2e8f0}@media(max-width:900px){.header,.hero{flex-direction:column;align-items:flex-start}.grid{grid-template-columns:1fr}.nav{width:100%}.nav a{flex:1;text-align:center}.form-grid{grid-template-columns:1fr}}

.help-wrap{display:inline-flex;align-items:center;gap:8px;position:relative}.help-btn{width:24px;height:24px;border-radius:50%;border:0;background:#e8eef5;color:#334155;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}.help-box{display:none;position:absolute;left:0;top:32px;width:min(360px,80vw);background:#162330;color:#fff;padding:14px 16px;border-radius:14px;box-shadow:0 18px 45px rgba(15,23,42,.28);font-size:12px;line-height:1.55;z-index:40}.help-wrap.open .help-box{display:block}.check-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.check-item{display:flex;align-items:center;gap:8px;border:1px solid #dbe4ee;background:#f8fbfd;border-radius:12px;padding:10px;font-size:12px;font-weight:700;color:#334155}.check-item input{width:auto;margin:0}.iframe-box{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:16px;padding:16px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap}.iframe-box code{font-size:12px;color:#475569;word-break:break-all}.warn-cat{display:inline-flex;align-items:center;gap:6px;margin-top:5px;color:#b45309;font-size:11px;font-weight:800}.field small{display:block;margin-top:6px;color:#64748b}@media(max-width:760px){.check-grid{grid-template-columns:1fr 1fr}}
.bloqueos-filtros{display:grid;grid-template-columns:1.2fr repeat(4,minmax(150px,.7fr));gap:10px;margin-top:22px;padding:14px;background:#f8fafc;border:1px solid var(--line);border-radius:16px}.bloqueos-filtros input,.bloqueos-filtros select{width:100%;padding:11px 12px;border:1px solid #d7e0ea;border-radius:11px;background:#fff;font-family:Poppins}.badge.warn{background:#fef3c7;color:#92400e}.badge.off{background:#e2e8f0;color:#475569}@media(max-width:1000px){.bloqueos-filtros{grid-template-columns:1fr 1fr}}@media(max-width:650px){.bloqueos-filtros{grid-template-columns:1fr}}</style></head><body>
<header class="header"><div class="brand"><i class="fa-solid fa-bed"></i><div>Categorías y habitaciones<small><?=htmlspecialchars($hotel['nombre_comercial'] ?? 'Hotel')?></small></div></div><nav class="nav"><a href="recepcion.php"><i class="fa-solid fa-calendar-check"></i> Reservas</a><a href="recepcionistas.php"><i class="fa-solid fa-users-gear"></i> Recepcionistas</a><a class="gold" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a></nav></header>
<main class="wrap"><section class="hero"><div><h1>Gestión visual de reservas</h1><p>Registra categorías, precios, servicios e imágenes. La gestión por cuarto real es opcional.</p><div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap"><a class="btn gray" href="encuestas.php"><i class="fa-solid fa-square-poll-horizontal"></i> Configurar encuestas</a><a class="btn gray" href="encuestas_resultados.php"><i class="fa-solid fa-chart-line"></i> Resultados de encuestas</a></div></div></section>
<?php if($error): ?><div class="alert err"><i class="fa-solid fa-triangle-exclamation"></i> <?=htmlspecialchars($error)?></div><?php endif; ?>
<form class="setting" method="post"><input type="hidden" name="accion" value="actualizar_gestion"><div><div class="help-wrap"><b><i class="fa-solid fa-toggle-on"></i> Gestión de reservas por habitación</b><button class="help-btn" type="button" aria-label="Ayuda" onclick="toggleHelp(this)">?</button><div class="help-box"><b>¿Cómo funciona?</b><br>Si activas esta opción, registra como mínimo una habitación para cada categoría que quieras manejar con disponibilidad real. Si una categoría activa no tiene habitaciones, no podrá validar disponibilidad correctamente. Si está desactivada, el CRM seguirá funcionando como gestor de consultas por categoría y no exigirá habitaciones.</div></div><br><span class="muted">Si está desactivado, el CRM trabajará como consultas por categoría.</span></div><label class="switch"><input type="checkbox" name="gestion_por_habitacion" value="1" <?=$gestionPorHabitacion?'checked':''?>> <?=$gestionPorHabitacion?'Activado':'Desactivado'?></label><button class="btn green" type="submit">Guardar opción</button></form>
<section class="card"><h2><i class="fa-solid fa-code"></i> Iframe del hotel</h2><div class="iframe-box"><code><?=htmlspecialchars($webReservasPublica)?></code><div><a class="btn blue" href="<?=htmlspecialchars($webReservasPublica)?>" target="_blank"><i class="fa-solid fa-up-right-from-square"></i> Abrir</a> <button class="btn gray" type="button" onclick="copiarLinkHotel()"><i class="fa-solid fa-link"></i> Copiar enlace</button> <button class="btn green" type="button" onclick="copiarIframeHotel()"><i class="fa-solid fa-copy"></i> Copiar iframe</button></div></div></section>
<div class="grid"><section class="card"><h2><i class="fa-solid fa-layer-group"></i> Nueva categoría visual</h2><p><a class="btn gray" href="catalogos.php"><i class="fa-solid fa-list-check"></i> Administrar servicios e incluye</a></p><form method="post" enctype="multipart/form-data" class="form-grid"><input type="hidden" name="accion" value="crear_categoria"><div class="field full"><label>Nombre categoría</label><input name="nombre" required placeholder="Ej: Suite Ejecutiva"></div><div class="field"><label>Precio noche</label><input name="precio_base" type="number" step="0.01" min="1" required placeholder="230"></div><div class="field"><label>Precio anterior</label><input name="precio_anterior" type="number" step="0.01" min="0" placeholder="300"></div><div class="field"><label>Capacidad pax</label><input name="capacidad_pax" type="number" min="1" value="2"></div><div class="field"><label>Tipo cama</label><input name="tipo_cama" placeholder="King, Simple, 2 Camas"></div><div class="field full"><label>Etiqueta</label><input name="etiqueta" placeholder="Oferta especial / Disponibilidad limitada"></div><div class="field full"><label>Servicios</label><div class="check-grid"><?php foreach($catalogoServicios as $op): ?><label class="check-item"><input type="checkbox" name="servicios[]" value="<?=htmlspecialchars($op)?>"> <?=htmlspecialchars($op)?></label><?php endforeach; ?></div></div><div class="field full"><label>Incluye</label><div class="check-grid"><?php foreach($catalogoBeneficios as $op): ?><label class="check-item"><input type="checkbox" name="incluye[]" value="<?=htmlspecialchars($op)?>"> <?=htmlspecialchars($op)?></label><?php endforeach; ?></div></div><div class="field full"><label>Imagen principal</label><input name="imagen_1" type="file" accept="image/png,image/jpeg,image/webp"><small>Sube JPG, PNG o WEBP. Máximo 4 MB.</small></div><div class="field"><label>Imagen 2</label><input name="imagen_2" type="file" accept="image/png,image/jpeg,image/webp"></div><div class="field"><label>Imagen 3</label><input name="imagen_3" type="file" accept="image/png,image/jpeg,image/webp"></div><div class="field full"><div class="help-wrap"><label style="margin:0">URL “Ver más fotos”</label><button class="help-btn" type="button" onclick="toggleHelp(this)">?</button><div class="help-box">Pega aquí el enlace público de una página o galería donde el cliente pueda ver más fotos de esta categoría. Si dejas el campo vacío, el botón “Ver más fotos” no aparecerá en el iframe.</div></div><input name="galeria_url" placeholder="https://hotel.com/habitaciones/matrimonial"><small>Opcional. Debe ser un enlace público.</small></div><div class="full"><button class="btn green" type="submit"><i class="fa-solid fa-plus"></i> Guardar categoría</button></div></form></section>
<section class="card" <?=$gestionPorHabitacion?'':'style="opacity:.65"'?>> <h2><i class="fa-solid fa-door-open"></i> Nueva habitación real</h2><?php if(!$gestionPorHabitacion): ?><p class="muted">La gestión por habitación está desactivada. Actívala arriba si el hotel desea manejar cuartos como 101, 102, 201.</p><?php endif; ?><form method="post" class="form-grid"><input type="hidden" name="accion" value="crear_habitacion"><div class="field"><label>Número</label><input name="numero_habitacion" <?=$gestionPorHabitacion?'required':'disabled'?> placeholder="101"></div><div class="field"><label>Estado</label><select name="estado" <?=$gestionPorHabitacion?'':'disabled'?>><option>Disponible</option><option>Ocupada</option><option>Mantenimiento</option></select></div><div class="field full"><label>Categoría principal (capacidad máxima)</label><div class="check-grid category-parent-grid"><?php foreach($categorias as $c): ?><label class="check-item"><input type="radio" name="id_categoria_principal" value="<?=(int)$c['id_categoria']?>" data-capacidad="<?=(int)$c['capacidad_pax']?>" onchange="actualizarCategoriasCompatibles()" <?=$gestionPorHabitacion?'required':'disabled'?>> <?=htmlspecialchars($c['nombre'])?> (<?=(int)$c['capacidad_pax']?> pax)</label><?php endforeach; ?></div><small>Esta es la capacidad real del cuarto. Por ejemplo, una Triple puede venderse como Doble o Simple.</small></div><div class="field full"><label>También puede venderse como</label><div class="check-grid" id="categoriasCompatibles"><?php foreach($categorias as $c): ?><label class="check-item categoria-compatible" data-capacidad="<?=(int)$c['capacidad_pax']?>"><input type="checkbox" name="id_categorias[]" value="<?=(int)$c['id_categoria']?>" <?=$gestionPorHabitacion?'':'disabled'?>> <?=htmlspecialchars($c['nombre'])?></label><?php endforeach; ?></div><small>Solo se habilitarán categorías con igual o menor capacidad que la categoría principal. Si se ocupa bajo una, queda ocupada en todas.</small></div><div class="full"><button class="btn green" type="submit" <?=$gestionPorHabitacion?'':'disabled'?>> <i class="fa-solid fa-plus"></i> Guardar habitación</button></div></form></section></div>
<section class="card"><h2><i class="fa-solid fa-tags"></i> Categorías publicadas</h2><div class="table-wrap"><table><thead><tr><th>Categoría</th><th>Imagen</th><th>Precio</th><th>Pax</th><th>Etiqueta</th><th>Habitaciones</th><th>Disponibles</th><th>Estado</th><th>Acción</th></tr></thead><tbody><?php if(!$categorias): ?><tr><td colspan="9">Todavía no hay categorías.</td></tr><?php endif; ?><?php foreach($categorias as $c): ?><tr><td><b><?=htmlspecialchars($c['nombre'])?></b><br><small><?=htmlspecialchars($c['tipo_cama'] ?? '')?></small></td><td><?php if(!empty($c['imagen_1'])): ?><img class="thumb" src="../<?=htmlspecialchars($c['imagen_1'])?>"><?php else: ?>-<?php endif; ?></td><td class="money">S/ <?=number_format((float)$c['precio_base'],2)?></td><td><?= (int)$c['capacidad_pax'] ?></td><td><?=htmlspecialchars($c['etiqueta'] ?? '-')?></td><td><?= (int)$c['total_habitaciones'] ?><?php if($gestionPorHabitacion && (int)$c['total_habitaciones']===0): ?><div class="warn-cat"><i class="fa-solid fa-triangle-exclamation"></i> Sin habitaciones</div><?php endif; ?></td><td><?= (int)$c['disponibles'] ?></td><td><span class="badge <?=($c['estado']==='ACTIVO'?'':'off')?>"><?=htmlspecialchars($c['estado'])?></span></td><td class="actions"><button class="btn blue" type="button" onclick='editarCategoria(<?=json_encode($c, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>)'><i class="fa-solid fa-pen"></i> Editar</button><button class="btn gray" type="button" onclick="copiarCategoria(<?=htmlspecialchars(json_encode($c['slug'] ?? $c['id_categoria']),ENT_QUOTES)?>,false)"><i class="fa-solid fa-link"></i></button><button class="btn green" type="button" onclick="copiarCategoria(<?=htmlspecialchars(json_encode($c['slug'] ?? $c['id_categoria']),ENT_QUOTES)?>,true)"><i class="fa-solid fa-code"></i></button></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php if($gestionPorHabitacion): ?><section class="card"><h2><i class="fa-solid fa-list-check"></i> Habitaciones reales</h2><div class="table-wrap"><table><thead><tr><th>Número</th><th>Categoría</th><th>Estado</th><th>Acción</th></tr></thead><tbody><?php if(!$habitaciones): ?><tr><td colspan="4">Todavía no hay habitaciones.</td></tr><?php endif; ?><?php foreach($habitaciones as $h): ?><tr><td><b><?=htmlspecialchars($h['numero_habitacion'])?></b></td><td><?=htmlspecialchars($h['categoria'])?></td><td><span class="badge <?=$h['estado']==='Mantenimiento'?'warn':($h['estado']==='Ocupada'?'red':'')?>"><?=htmlspecialchars($h['estado'])?></span></td><td class="actions"><button class="btn blue" type="button" onclick='editarHabitacion(<?=json_encode($h, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>)'><i class="fa-solid fa-pen"></i> Editar</button><form method="post" onsubmit="return confirm('¿Eliminar esta habitación?')"><input type="hidden" name="accion" value="eliminar_habitacion"><input type="hidden" name="id_habitacion" value="<?=(int)$h['id_habitacion']?>"><button class="btn red" type="submit"><i class="fa-solid fa-trash"></i></button></form></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
<?php if($gestionPorHabitacion): ?>
<section class="card">
  <h2><i class="fa-solid fa-calendar-xmark"></i> Programar habitación bloqueada</h2>
  <p class="muted">Usa este módulo para mantenimiento, limpieza profunda o bloqueo temporal. La habitación no saldrá disponible durante esas fechas.</p>
  <form method="post" class="form-grid">
    <input type="hidden" name="accion" value="crear_bloqueo">
    <div class="field full"><label>Habitación</label><select name="id_habitacion" required><?php foreach($habitaciones as $h): ?><option value="<?=(int)$h['id_habitacion']?>"><?=htmlspecialchars($h['numero_habitacion'].' - '.$h['categoria'])?></option><?php endforeach; ?></select></div>
    <div class="field"><label>Desde</label><input type="date" name="fecha_inicio" required></div>
    <div class="field"><label>Hasta</label><input type="date" name="fecha_fin" required></div>
    <div class="field full"><label>Motivo</label><textarea name="motivo" placeholder="Ej: mantenimiento, fumigación, reparación de aire acondicionado..."></textarea></div>
    <div class="full"><button class="btn green" type="submit"><i class="fa-solid fa-calendar-plus"></i> Programar bloqueo</button></div>
  </form>
  <div class="bloqueos-filtros">
    <input id="bloqBuscar" type="search" placeholder="Buscar habitación o motivo..." oninput="filtrarBloqueos()">
    <select id="bloqPeriodo" onchange="filtrarBloqueos()"><option value="todos">Todos los periodos</option><option value="hoy">Hoy</option><option value="proximos">Próximos</option><option value="mes_actual">Este mes</option><option value="mes">Mes específico</option></select>
    <input id="bloqMes" type="month" onchange="filtrarBloqueos()" disabled>
    <select id="bloqCategoria" onchange="filtrarBloqueos()"><option value="">Todas las categorías</option><?php foreach($categorias as $c): ?><option value="<?=htmlspecialchars(mb_strtolower($c['nombre'],'UTF-8'))?>"><?=htmlspecialchars($c['nombre'])?></option><?php endforeach; ?></select>
    <select id="bloqEstado" onchange="filtrarBloqueos()"><option value="">Todos los estados</option><option>PROGRAMADO</option><option>BLOQUEADO</option><option>FINALIZADO</option><option>CANCELADO</option></select>
  </div>
  <div class="table-wrap" style="margin-top:18px"><table><thead><tr><th>Habitación</th><th>Categoría</th><th>Desde</th><th>Hasta</th><th>Motivo</th><th>Estado</th><th>Acción</th></tr></thead><tbody id="bloqueosBody">
  <?php if(!$bloqueos): ?><tr><td colspan="7">Todavía no hay bloqueos programados.</td></tr><?php endif; ?>
  <?php foreach($bloqueos as $b): $estadoVisual=$b['estado_visual'] ?? $b['estado']; ?>
  <tr class="bloqueo-row" data-buscar="<?=htmlspecialchars(mb_strtolower(($b['numero_habitacion']??'').' '.($b['motivo']??''),'UTF-8'))?>" data-categoria="<?=htmlspecialchars(mb_strtolower($b['categoria']??'','UTF-8'))?>" data-desde="<?=htmlspecialchars($b['fecha_inicio'])?>" data-hasta="<?=htmlspecialchars($b['fecha_fin'])?>" data-estado="<?=htmlspecialchars($estadoVisual)?>">
    <td><b><?=htmlspecialchars($b['numero_habitacion'])?></b></td><td><?=htmlspecialchars($b['categoria'])?></td><td><?=htmlspecialchars($b['fecha_inicio'])?></td><td><?=htmlspecialchars($b['fecha_fin'])?></td><td><?=htmlspecialchars($b['motivo'] ?: '-')?></td>
    <td><span class="badge <?=in_array($estadoVisual,['PROGRAMADO','BLOQUEADO'],true)?'warn':'off'?>"><?=htmlspecialchars($estadoVisual)?></span></td>
    <td><?php if(($b['estado']??'')==='ACTIVO' && in_array($estadoVisual,['PROGRAMADO','BLOQUEADO'],true)): ?><form method="post" onsubmit="return confirm('¿Cancelar este bloqueo?')"><input type="hidden" name="accion" value="cancelar_bloqueo"><input type="hidden" name="id_bloqueo" value="<?=(int)$b['id_bloqueo']?>"><button class="btn red" type="submit"><i class="fa-solid fa-ban"></i> Cancelar</button></form><?php else: ?>-<?php endif; ?></td>
  </tr><?php endforeach; ?></tbody></table></div>
</section><?php endif; ?>
</main>
<div class="modal" id="modalCat"><div class="modal-box"><div class="modal-head"><h2>Editar categoría</h2><button class="close" onclick="cerrar('modalCat')"><i class="fa-solid fa-xmark"></i></button></div><form method="post" enctype="multipart/form-data" class="form-grid"><input type="hidden" name="accion" value="actualizar_categoria"><input type="hidden" name="id_categoria" id="cat_id"><input type="hidden" name="imagen_1_actual" id="cat_img1_actual"><input type="hidden" name="imagen_2_actual" id="cat_img2_actual"><input type="hidden" name="imagen_3_actual" id="cat_img3_actual"><div class="field full"><label>Nombre</label><input name="nombre" id="cat_nombre" required></div><div class="field"><label>Precio noche</label><input name="precio_base" id="cat_precio" type="number" step="0.01" min="1" required></div><div class="field"><label>Precio anterior</label><input name="precio_anterior" id="cat_precio_ant" type="number" step="0.01" min="0"></div><div class="field"><label>Capacidad</label><input name="capacidad_pax" id="cat_pax" type="number" min="1"></div><div class="field"><label>Tipo cama</label><input name="tipo_cama" id="cat_cama"></div><div class="field"><label>Estado</label><select name="estado" id="cat_estado"><option>ACTIVO</option><option>INACTIVO</option></select></div><div class="field full"><label>Etiqueta</label><input name="etiqueta" id="cat_etiqueta"></div><div class="field full"><label>Servicios</label><div class="check-grid" id="cat_servicios_checks"><label class="check-item"><input type="checkbox" name="servicios[]" value="WiFi"> WiFi</label><label class="check-item"><input type="checkbox" name="servicios[]" value="A/C"> A/C</label><label class="check-item"><input type="checkbox" name="servicios[]" value="Baño privado"> Baño privado</label><label class="check-item"><input type="checkbox" name="servicios[]" value="Balcón"> Balcón</label><label class="check-item"><input type="checkbox" name="servicios[]" value="Jacuzzi"> Jacuzzi</label><label class="check-item"><input type="checkbox" name="servicios[]" value="TV"> TV</label><label class="check-item"><input type="checkbox" name="servicios[]" value="Agua caliente"> Agua caliente</label><label class="check-item"><input type="checkbox" name="servicios[]" value="Estacionamiento"> Estacionamiento</label><label class="check-item"><input type="checkbox" name="servicios[]" value="Piscina"> Piscina</label></div></div><div class="field full"><label>Incluye</label><div class="check-grid" id="cat_incluye_checks"><label class="check-item"><input type="checkbox" name="incluye[]" value="Desayuno"> Desayuno</label><label class="check-item"><input type="checkbox" name="incluye[]" value="Acceso a piscina"> Acceso a piscina</label><label class="check-item"><input type="checkbox" name="incluye[]" value="Limpieza diaria"> Limpieza diaria</label><label class="check-item"><input type="checkbox" name="incluye[]" value="Toallas"> Toallas</label><label class="check-item"><input type="checkbox" name="incluye[]" value="Amenities"> Amenities</label><label class="check-item"><input type="checkbox" name="incluye[]" value="Recepción 24 horas"> Recepción 24 horas</label><label class="check-item"><input type="checkbox" name="incluye[]" value="Estacionamiento"> Estacionamiento</label><label class="check-item"><input type="checkbox" name="incluye[]" value="Traslado"> Traslado</label></div></div><div class="field full"><label>Cambiar imagen principal</label><input name="imagen_1" type="file" accept="image/png,image/jpeg,image/webp"><small id="cat_img1_txt"></small></div><div class="field"><label>Cambiar imagen 2</label><input name="imagen_2" type="file" accept="image/png,image/jpeg,image/webp"><small id="cat_img2_txt"></small></div><div class="field"><label>Cambiar imagen 3</label><input name="imagen_3" type="file" accept="image/png,image/jpeg,image/webp"><small id="cat_img3_txt"></small></div><div class="field full"><div class="help-wrap"><label style="margin:0">URL “Ver más fotos”</label><button class="help-btn" type="button" onclick="toggleHelp(this)">?</button><div class="help-box">Pega el enlace público de la galería. Si se deja vacío, el botón no se mostrará en el iframe.</div></div><input name="galeria_url" id="cat_galeria"></div><div class="full"><button class="btn green" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button></div></form></div></div>
<div class="modal" id="modalHab"><div class="modal-box"><div class="modal-head"><h2>Editar habitación</h2><button class="close" onclick="cerrar('modalHab')"><i class="fa-solid fa-xmark"></i></button></div><form method="post" class="form-grid"><input type="hidden" name="accion" value="actualizar_habitacion"><input type="hidden" name="id_habitacion" id="hab_id"><div class="field"><label>Número</label><input name="numero_habitacion" id="hab_numero" required></div><div class="field"><label>Estado</label><select name="estado" id="hab_estado"><option>Disponible</option><option>Ocupada</option><option>Mantenimiento</option></select></div><div class="field full"><label>Categoría principal (capacidad real)</label><div class="check-grid" id="hab_principal_checks"><?php foreach($categorias as $c): ?><label class="check-item"><input type="radio" name="id_categoria_principal" value="<?=(int)$c['id_categoria']?>" data-capacidad="<?=(int)$c['capacidad_pax']?>" onchange="actualizarCategoriasCompatiblesEdicion()" required> <?=htmlspecialchars($c['nombre'])?> (<?=(int)$c['capacidad_pax']?> pax)</label><?php endforeach; ?></div></div><div class="field full"><label>También puede venderse como</label><div class="check-grid" id="hab_categorias_checks"><?php foreach($categorias as $c): ?><label class="check-item categoria-compatible-edicion" data-capacidad="<?=(int)$c['capacidad_pax']?>"><input type="checkbox" name="id_categorias[]" value="<?=(int)$c['id_categoria']?>"> <?=htmlspecialchars($c['nombre'])?></label><?php endforeach; ?></div><small>Solo se permiten categorías con capacidad igual o menor a la principal.</small></div><div class="full"><button class="btn green" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button></div></form></div></div>
<script>
function cerrar(id){document.getElementById(id).classList.remove('show')}
function marcarChecks(contenedorId, valores){const set=new Set(String(valores||'').split(',').map(v=>v.trim().toLowerCase()).filter(Boolean));document.querySelectorAll('#'+contenedorId+' input[type=checkbox]').forEach(ch=>ch.checked=set.has(String(ch.value).toLowerCase()))}
function editarCategoria(c){document.getElementById('cat_id').value=c.id_categoria||'';document.getElementById('cat_nombre').value=c.nombre||'';document.getElementById('cat_precio').value=c.precio_base||'';document.getElementById('cat_precio_ant').value=c.precio_anterior||'';document.getElementById('cat_pax').value=c.capacidad_pax||1;document.getElementById('cat_cama').value=c.tipo_cama||'';document.getElementById('cat_estado').value=c.estado||'ACTIVO';document.getElementById('cat_etiqueta').value=c.etiqueta||'';marcarChecks('cat_servicios_checks',c.servicios);marcarChecks('cat_incluye_checks',c.incluye);document.getElementById('cat_img1_actual').value=c.imagen_1||'';document.getElementById('cat_img2_actual').value=c.imagen_2||'';document.getElementById('cat_img3_actual').value=c.imagen_3||'';document.getElementById('cat_img1_txt').textContent=c.imagen_1?'Actual: '+c.imagen_1:'Sin imagen actual';document.getElementById('cat_img2_txt').textContent=c.imagen_2?'Actual: '+c.imagen_2:'Sin imagen actual';document.getElementById('cat_img3_txt').textContent=c.imagen_3?'Actual: '+c.imagen_3:'Sin imagen actual';document.getElementById('cat_galeria').value=c.galeria_url||'';document.getElementById('modalCat').classList.add('show')}
function toggleHelp(btn){const wrap=btn.closest('.help-wrap');document.querySelectorAll('.help-wrap.open').forEach(x=>{if(x!==wrap)x.classList.remove('open')});wrap.classList.toggle('open')}
function copiarIframeHotel(){const iframe=`<iframe src="<?=htmlspecialchars($webReservasPublica, ENT_QUOTES)?>" width="100%" height="900" style="border:0;border-radius:16px;overflow:hidden;" loading="lazy"></iframe>`;navigator.clipboard.writeText(iframe).then(()=>alert('Iframe copiado'))}
function copiarLinkHotel(){navigator.clipboard.writeText('<?=htmlspecialchars($webReservasPublica, ENT_QUOTES)?>').then(()=>alert('Enlace copiado'))}
function copiarCategoria(cat,iframe){const url='<?=htmlspecialchars($webReservasPublica, ENT_QUOTES)?>&categoria='+encodeURIComponent(cat);const texto=iframe?`<iframe src="${url}" width="100%" height="900" style="border:0" loading="lazy"></iframe>`:url;navigator.clipboard.writeText(texto).then(()=>alert(iframe?'Iframe de categoría copiado':'Enlace de categoría copiado'));}
function actualizarCategoriasCompatibles(){
  const principal=document.querySelector('input[name="id_categoria_principal"]:checked');
  const capacidad=principal?Number(principal.dataset.capacidad||0):0;
  const idPrincipal=principal?String(principal.value):'';
  document.querySelectorAll('.categoria-compatible').forEach(label=>{
    const check=label.querySelector('input[type="checkbox"]');
    const permitida=capacidad>0&&Number(label.dataset.capacidad||0)<=capacidad;
    label.style.opacity=permitida?'1':'.45';
    check.disabled=!permitida;
    if(!permitida)check.checked=false;
    if(String(check.value)===idPrincipal){check.checked=true;check.disabled=true;}
  });
}
function actualizarCategoriasCompatiblesEdicion(){const principal=document.querySelector('#hab_principal_checks input[name="id_categoria_principal"]:checked');if(!principal)return;const capacidad=Number(principal.dataset.capacidad||0),idPrincipal=String(principal.value);document.querySelectorAll('.categoria-compatible-edicion').forEach(label=>{const check=label.querySelector('input'),permitida=Number(label.dataset.capacidad||0)<=capacidad;check.disabled=!permitida;label.style.opacity=permitida?'1':'.45';if(!permitida)check.checked=false;if(String(check.value)===idPrincipal){check.checked=true;check.disabled=true;}})}
function editarHabitacion(h){document.getElementById('hab_id').value=h.id_habitacion||'';document.getElementById('hab_numero').value=h.numero_habitacion||'';document.getElementById('hab_estado').value=h.estado||'Disponible';const ids=String(h.categorias_ids||h.id_categoria||'').split(',').filter(Boolean);document.querySelectorAll('#hab_principal_checks input').forEach(r=>r.checked=String(r.value)===String(h.id_categoria));document.querySelectorAll('#hab_categorias_checks input').forEach(c=>c.checked=ids.includes(String(c.value)));actualizarCategoriasCompatiblesEdicion();document.getElementById('modalHab').classList.add('show')}
document.querySelectorAll('.modal').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('show')}));

function filtrarBloqueos(){const buscar=(document.getElementById('bloqBuscar')?.value||'').toLowerCase();const periodo=document.getElementById('bloqPeriodo')?.value||'todos';const mesInput=document.getElementById('bloqMes');if(mesInput)mesInput.disabled=periodo!=='mes';const mes=mesInput?.value||'';const categoria=(document.getElementById('bloqCategoria')?.value||'').toLowerCase();const estado=document.getElementById('bloqEstado')?.value||'';const hoy='<?=date('Y-m-d')?>';const mesHoy=hoy.slice(0,7);document.querySelectorAll('.bloqueo-row').forEach(row=>{const desde=row.dataset.desde||'',hasta=row.dataset.hasta||'',mesFila=desde.slice(0,7);let okPeriodo=true;if(periodo==='hoy')okPeriodo=desde<=hoy&&hasta>=hoy;else if(periodo==='proximos')okPeriodo=desde>hoy;else if(periodo==='mes_actual')okPeriodo=mesFila===mesHoy||hasta.slice(0,7)===mesHoy;else if(periodo==='mes')okPeriodo=!mes||mesFila===mes||hasta.slice(0,7)===mes;const visible=(!buscar||(row.dataset.buscar||'').includes(buscar))&&(!categoria||(row.dataset.categoria||'')===categoria)&&(!estado||(row.dataset.estado||'')===estado)&&okPeriodo;row.style.display=visible?'':'none'})}
</script></body></html>
