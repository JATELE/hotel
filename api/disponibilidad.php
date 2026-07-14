<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
require_once '../config/Database.php';

function json_out($data, int $status = 200): void { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function normalizar_slug(string $texto): string { $texto = trim(mb_strtolower($texto, 'UTF-8')); $texto = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $texto); $texto = preg_replace('/[^a-z0-9]+/u','-',$texto); return trim($texto,'-'); }
function columnas_tabla(PDO $db, string $tabla): array { $stmt=$db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?"); $stmt->execute([$tabla]); return array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true); }
function resolver_hotel(PDO $db): array {
    $cols = columnas_tabla($db,'hoteles');
    $selectGestion = isset($cols['gestion_por_habitacion']) ? ', gestion_por_habitacion' : ', 0 AS gestion_por_habitacion';
    $idHotel = isset($_GET['id_hotel']) ? (int)$_GET['id_hotel'] : 0;
    if ($idHotel > 0) { $stmt=$db->prepare("SELECT id_hotel, nombre_comercial, estado".$selectGestion." FROM hoteles WHERE id_hotel=? LIMIT 1"); $stmt->execute([$idHotel]); return $stmt->fetch(PDO::FETCH_ASSOC) ?: []; }
    $hotelParam = trim((string)($_GET['hotel'] ?? ''));
    if ($hotelParam === '') return [];
    $slug = normalizar_slug($hotelParam);
    if (isset($cols['slug'])) { $stmt=$db->prepare("SELECT id_hotel, nombre_comercial, estado".$selectGestion." FROM hoteles WHERE slug=? LIMIT 1"); $stmt->execute([$slug]); }
    else { $stmt=$db->prepare("SELECT id_hotel, nombre_comercial, estado".$selectGestion." FROM hoteles WHERE LOWER(REPLACE(nombre_comercial,' ','-'))=? LIMIT 1"); $stmt->execute([$slug]); }
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}
function fechas_validas(string $checkin, string $checkout): bool { return preg_match('/^\d{4}-\d{2}-\d{2}$/',$checkin) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$checkout) && strtotime($checkout) > strtotime($checkin); }

try {
    $db=(new Database())->connect();
    $hotel=resolver_hotel($db);
    $idHotel=(int)($hotel['id_hotel'] ?? 0);
    $gestionPorHabitacion=(int)($hotel['gestion_por_habitacion'] ?? 0) === 1;
    $idCategoria=isset($_GET['id_categoria'])?(int)$_GET['id_categoria']:0;
    $cantidad=max(1,(int)($_GET['cantidad_habitaciones']??1));
    $idReservaExcluir=isset($_GET['id_reserva'])?(int)$_GET['id_reserva']:0;
    $idHabitacion=isset($_GET['id_habitacion'])?(int)$_GET['id_habitacion']:0;
    $checkin=trim((string)($_GET['fecha_checkin'] ?? $_GET['checkin'] ?? ''));
    $checkout=trim((string)($_GET['fecha_checkout'] ?? $_GET['checkout'] ?? ''));
    if ($idHotel<=0 || $idCategoria<=0 || !fechas_validas($checkin,$checkout)) json_out(['disponible'=>false,'habitaciones_libres'=>0,'habitaciones'=>[],'mensaje'=>'Datos incompletos o fechas inválidas.'],400);
    if (($hotel['estado'] ?? '') !== 'ACTIVO') json_out(['disponible'=>false,'habitaciones_libres'=>0,'habitaciones'=>[],'mensaje'=>'El hotel no está activo.'],403);

    $stmtCat=$db->prepare("SELECT id_categoria,nombre,precio_base FROM categorias WHERE id_hotel=? AND id_categoria=? AND estado='ACTIVO' LIMIT 1");
    $stmtCat->execute([$idHotel,$idCategoria]);
    $categoria=$stmtCat->fetch(PDO::FETCH_ASSOC);
    if(!$categoria) json_out(['disponible'=>false,'habitaciones_libres'=>0,'habitaciones'=>[],'mensaje'=>'La categoría no pertenece al hotel o está inactiva.'],404);

    if (!$gestionPorHabitacion) {
        json_out([
            'disponible'=>true,
            'gestion_por_habitacion'=>false,
            'habitaciones_libres'=>null,
            'habitaciones'=>[],
            'categoria'=>['id_categoria'=>(int)$categoria['id_categoria'],'nombre'=>$categoria['nombre'],'precio_base'=>(float)$categoria['precio_base']],
            'mensaje'=>'Categoría disponible para registrar consulta. La gestión por habitación está desactivada.'
        ]);
    }

    $stmtHabitaciones=$db->prepare("SELECT DISTINCT h.id_habitacion,h.numero_habitacion,h.id_categoria AS id_categoria_principal,cp.nombre AS categoria_principal,cp.precio_base AS precio_principal FROM habitaciones h INNER JOIN categorias cp ON cp.id_categoria=h.id_categoria LEFT JOIN habitacion_categorias hc ON hc.id_habitacion=h.id_habitacion AND hc.estado='ACTIVO' WHERE h.id_hotel=? AND (h.id_categoria=? OR hc.id_categoria=?) AND h.estado='Disponible' ORDER BY h.numero_habitacion ASC");
    $stmtHabitaciones->execute([$idHotel,$idCategoria,$idCategoria]);
    $habitaciones=$stmtHabitaciones->fetchAll(PDO::FETCH_ASSOC);
    if(!$habitaciones) json_out(['disponible'=>false,'gestion_por_habitacion'=>true,'habitaciones_libres'=>0,'habitaciones'=>[],'categoria'=>$categoria,'mensaje'=>'No hay habitaciones registradas como disponibles en esta categoría.']);

    /*
     * La ocupación se valida por habitación física, no por categoría.
     * Se consideran tanto reservas múltiples (reserva_detalle) como
     * reservas antiguas (reservas.id_habitacion). Las culminadas siguen
     * ocupando históricamente su rango; solo se ignoran canceladas/rechazadas.
     */
    $paramsOcupadas = [$idHotel, $checkout, $checkin];
    $excluirDetalle = '';
    $excluirPrincipal = '';
    if ($idReservaExcluir > 0) {
        $excluirDetalle = ' AND r.id_reserva <> ?';
        $excluirPrincipal = ' AND r.id_reserva <> ?';
    }

    $sqlOcupadas = "
        SELECT DISTINCT ocupacion.id_habitacion
        FROM (
            SELECT rd.id_habitacion
            FROM reserva_detalle rd
            INNER JOIN reservas r ON r.id_reserva = rd.id_reserva
            WHERE r.id_hotel = ?
              AND r.estado_reserva NOT IN ('Cancelada','Rechazada')
              AND rd.id_habitacion IS NOT NULL
              AND r.fecha_checkin < ?
              AND r.fecha_checkout > ?
              {$excluirDetalle}

            UNION

            SELECT r.id_habitacion
            FROM reservas r
            WHERE r.id_hotel = ?
              AND r.estado_reserva NOT IN ('Cancelada','Rechazada')
              AND r.id_habitacion IS NOT NULL
              AND r.fecha_checkin < ?
              AND r.fecha_checkout > ?
              {$excluirPrincipal}
        ) ocupacion
        WHERE ocupacion.id_habitacion IS NOT NULL";

    $paramsConsulta = $paramsOcupadas;
    if ($idReservaExcluir > 0) $paramsConsulta[] = $idReservaExcluir;
    $paramsConsulta = array_merge($paramsConsulta, $paramsOcupadas);
    if ($idReservaExcluir > 0) $paramsConsulta[] = $idReservaExcluir;

    $stmtOcupadas = $db->prepare($sqlOcupadas);
    $stmtOcupadas->execute($paramsConsulta);
    $ocupadas = array_flip(array_map('intval', $stmtOcupadas->fetchAll(PDO::FETCH_COLUMN)));

    // Bloqueos programados por mantenimiento/inactividad.
    try {
        $stmtTablaBloqueos=$db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='habitaciones_bloqueos'");
        $stmtTablaBloqueos->execute();
        if((int)$stmtTablaBloqueos->fetchColumn()>0){
            $stmtBloqueos=$db->prepare("SELECT DISTINCT id_habitacion FROM habitaciones_bloqueos WHERE id_hotel=? AND estado='ACTIVO' AND fecha_inicio < ? AND fecha_fin >= ?");
            $stmtBloqueos->execute([$idHotel,$checkout,$checkin]);
            foreach(array_map('intval',$stmtBloqueos->fetchAll(PDO::FETCH_COLUMN)) as $idBloq){ $ocupadas[$idBloq]=true; }
        }
    } catch(Throwable $e) {}

    $libres=[]; foreach($habitaciones as $h){ if(!isset($ocupadas[(int)$h['id_habitacion']])) $libres[]=['id_habitacion'=>(int)$h['id_habitacion'],'numero_habitacion'=>$h['numero_habitacion'],'id_categoria_principal'=>(int)$h['id_categoria_principal'],'categoria_principal'=>$h['categoria_principal'],'precio_principal'=>(float)$h['precio_principal'],'es_categoria_superior'=>((int)$h['id_categoria_principal']!==(int)$idCategoria)]; }
    if($idHabitacion>0) $libres=array_values(array_filter($libres, fn($h)=>(int)$h['id_habitacion']===$idHabitacion));
    $disponible=count($libres)>=$cantidad;
    json_out(['disponible'=>$disponible,'gestion_por_habitacion'=>true,'habitaciones_libres'=>count($libres),'cantidad_solicitada'=>$cantidad,'habitaciones'=>$libres,'categoria'=>['id_categoria'=>(int)$categoria['id_categoria'],'nombre'=>$categoria['nombre'],'precio_base'=>(float)$categoria['precio_base']],'mensaje'=>$disponible?'Sí hay disponibilidad para la cantidad solicitada.':'No hay habitaciones disponibles para esta categoría en las fechas seleccionadas.']);
} catch(Throwable $e){ if(isset($_GET['debug'])&&$_GET['debug']=='1') json_out(['error'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()],500); json_out(['error'=>'Error interno al verificar disponibilidad.'],500); }
