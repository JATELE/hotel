<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/Database.php';

function json_out($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function normalizar_slug(string $texto): string {
    $texto = trim(mb_strtolower($texto, 'UTF-8'));
    $texto = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $texto);
    $texto = preg_replace('/[^a-z0-9]+/u', '-', $texto);
    return trim($texto, '-');
}

function columnas_tabla(PDO $db, string $tabla): array {
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$tabla]);
    return array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
}

function resolver_hotel(PDO $db): array {
    $colsHoteles = columnas_tabla($db, 'hoteles');
    $idHotel = isset($_GET['id_hotel']) ? (int)$_GET['id_hotel'] : 0;
    $hotelParam = trim((string)($_GET['hotel'] ?? ''));

    if ($idHotel > 0) {
        $stmt = $db->prepare("SELECT id_hotel, nombre_comercial, razon_social, whatsapp, estado" . (isset($colsHoteles['slug']) ? ", slug" : ", LOWER(REPLACE(nombre_comercial,' ','-')) AS slug") . " FROM hoteles WHERE id_hotel = ? LIMIT 1");
        $stmt->execute($paramsCategorias);
        $hotel = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($hotel) return $hotel;
    }

    if ($hotelParam !== '') {
        $slug = normalizar_slug($hotelParam);
        if (isset($colsHoteles['slug'])) {
            $stmt = $db->prepare("SELECT id_hotel, nombre_comercial, razon_social, whatsapp, estado, slug FROM hoteles WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
        } else {
            $stmt = $db->prepare("SELECT id_hotel, nombre_comercial, razon_social, whatsapp, estado, LOWER(REPLACE(nombre_comercial,' ','-')) AS slug FROM hoteles WHERE LOWER(REPLACE(nombre_comercial,' ','-')) = ? LIMIT 1");
            $stmt->execute([$slug]);
        }
        $hotel = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($hotel) return $hotel;
    }

    return [];
}

function normalizar_imagen_publica(?string $ruta): ?string {
    $ruta = trim((string)$ruta);
    if ($ruta === '') return null;
    if (preg_match('/^https?:\/\//i', $ruta) || str_starts_with($ruta, '/')) return $ruta;
    return '../' . ltrim($ruta, '/');
}

function campo_categoria(array $cols, string $nombre, string $fallbackSql): string {
    if (isset($cols[$nombre])) {
        return "c.`{$nombre}` AS `{$nombre}`";
    }
    return "{$fallbackSql} AS `{$nombre}`";
}

try {
    $db = (new Database())->connect();
    if (!$db) json_out(['error' => 'No se pudo conectar a la base de datos.'], 500);

    $hotel = resolver_hotel($db);
    if (!$hotel) json_out(['error' => 'Hotel no válido o no encontrado. Usa ?hotel=slug-del-hotel o ?id_hotel=ID.'], 400);
    if (($hotel['estado'] ?? '') !== 'ACTIVO') json_out(['error' => 'El hotel no está activo en el CRM.'], 403);

    $idHotel = (int)$hotel['id_hotel'];
    $colsHotelesActual = columnas_tabla($db, 'hoteles');
    $gestionPorHabitacion = false;
    if (isset($colsHotelesActual['gestion_por_habitacion'])) {
        $stmtGestion = $db->prepare("SELECT gestion_por_habitacion FROM hoteles WHERE id_hotel = ? LIMIT 1");
        $stmtGestion->execute([$idHotel]);
        $gestionPorHabitacion = ((int)$stmtGestion->fetchColumn() === 1);
    }
    $cols = columnas_tabla($db, 'categorias');

    $visualSelect = [
        campo_categoria($cols, 'precio_anterior', 'NULL'),
        campo_categoria($cols, 'etiqueta', "CASE WHEN c.nombre LIKE '%Suite%' THEN 'Vista a selva / Promo' WHEN c.nombre LIKE '%Matrimonial%' THEN 'Oferta especial' WHEN c.nombre LIKE '%Triple%' THEN 'Ideal familias' WHEN c.nombre LIKE '%Doble%' THEN 'Disponibilidad limitada' ELSE 'Económica' END"),
        campo_categoria($cols, 'tipo_cama', "CASE WHEN c.nombre LIKE '%Triple%' THEN 'Triple' WHEN c.nombre LIKE '%Doble%' THEN '2 Camas' WHEN c.nombre LIKE '%Simple%' THEN 'Simple' ELSE 'King' END"),
        campo_categoria($cols, 'servicios', "'A/C, WiFi, Baño privado'"),
        campo_categoria($cols, 'incluye', "'Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina'"),
        campo_categoria($cols, 'imagen_1', "CASE WHEN c.nombre LIKE '%Matrimonial%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/1.webp' WHEN c.nombre LIKE '%Triple%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/10.webp' WHEN c.nombre LIKE '%Doble%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/13.webp' WHEN c.nombre LIKE '%Simple%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/16.webp' ELSE 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/7.webp' END"),
        campo_categoria($cols, 'imagen_2', "CASE WHEN c.nombre LIKE '%Matrimonial%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/2.webp' WHEN c.nombre LIKE '%Triple%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/11.webp' WHEN c.nombre LIKE '%Doble%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/14.webp' WHEN c.nombre LIKE '%Simple%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/17.webp' ELSE 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/8.webp' END"),
        campo_categoria($cols, 'imagen_3', "CASE WHEN c.nombre LIKE '%Matrimonial%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/3.webp' WHEN c.nombre LIKE '%Triple%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/12.webp' WHEN c.nombre LIKE '%Doble%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/15.webp' WHEN c.nombre LIKE '%Simple%' THEN 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/18.webp' ELSE 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/9.webp' END"),
        campo_categoria($cols, 'galeria_url', "'#'"),
    ];

    $categoriaFiltro = trim((string)($_GET['categoria'] ?? ''));
    $whereCategoria = '';
    $paramsCategorias = [$idHotel];
    if ($categoriaFiltro !== '') {
        if (ctype_digit($categoriaFiltro)) {
            $whereCategoria = ' AND c.id_categoria = ?';
            $paramsCategorias[] = (int)$categoriaFiltro;
        } else {
            $whereCategoria = ' AND c.slug = ?';
            $paramsCategorias[] = normalizar_slug($categoriaFiltro);
        }
    }

    $slugCategoriaSql = isset($cols['slug']) ? "c.slug" : "LOWER(REPLACE(c.nombre,' ','-')) AS slug";
    $sql = "SELECT c.id_categoria, c.nombre, {$slugCategoriaSql}, c.precio_base, c.capacidad_pax,
                " . implode(",\n                ", $visualSelect) . ",
                c.estado,
                COUNT(h.id_habitacion) AS total_habitaciones,
                COALESCE(SUM(CASE WHEN h.estado = 'Disponible' THEN 1 ELSE 0 END), 0) AS habitaciones_disponibles
            FROM categorias c
            LEFT JOIN habitacion_categorias hc ON hc.id_categoria = c.id_categoria AND hc.estado='ACTIVO'
            LEFT JOIN habitaciones h ON h.id_hotel=c.id_hotel AND h.estado='Disponible' AND (h.id_categoria=c.id_categoria OR h.id_habitacion=hc.id_habitacion)
            WHERE c.id_hotel = ? AND c.estado = 'ACTIVO' {$whereCategoria}
            GROUP BY c.id_categoria, c.nombre, c.precio_base, c.capacidad_pax, c.estado
            " . ($gestionPorHabitacion ? "HAVING habitaciones_disponibles > 0" : "") . "
            ORDER BY c.id_categoria ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($paramsCategorias);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categorias as &$cat) {
        $cat['id_categoria'] = (int)$cat['id_categoria'];
        $cat['precio_base'] = (float)$cat['precio_base'];
        $cat['precio_anterior'] = $cat['precio_anterior'] !== null && $cat['precio_anterior'] !== '' ? (float)$cat['precio_anterior'] : ((float)$cat['precio_base'] + 50);
        $cat['capacidad_pax'] = (int)$cat['capacidad_pax'];
        $cat['total_habitaciones'] = (int)$cat['total_habitaciones'];
        $cat['habitaciones_disponibles'] = (int)$cat['habitaciones_disponibles'];
        $cat['servicios_lista'] = array_values(array_filter(array_map('trim', explode(',', (string)($cat['servicios'] ?? ''))))) ?: ['WiFi', 'A/C'];
        $cat['incluye_lista'] = array_values(array_filter(array_map('trim', explode(',', (string)($cat['incluye'] ?? ''))))) ?: ['Desayuno americano'];
        $cat['imagen_1'] = normalizar_imagen_publica($cat['imagen_1'] ?? null);
        $cat['imagen_2'] = normalizar_imagen_publica($cat['imagen_2'] ?? null);
        $cat['imagen_3'] = normalizar_imagen_publica($cat['imagen_3'] ?? null);
        $cat['imagenes'] = array_values(array_filter([$cat['imagen_1'] ?: null, $cat['imagen_2'] ?: null, $cat['imagen_3'] ?: null]));
    }
    unset($cat);

    json_out([
        'hotel' => [
            'id_hotel' => $idHotel,
            'slug' => $hotel['slug'] ?? '',
            'nombre_comercial' => $hotel['nombre_comercial'],
            'whatsapp' => preg_replace('/\D+/', '', (string)$hotel['whatsapp']),
            'gestion_por_habitacion' => $gestionPorHabitacion,
        ],
        'categorias' => $categorias,
    ]);
} catch (Throwable $e) {
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        json_out(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], 500);
    }
    json_out(['error' => 'Error interno al cargar categorías del hotel. Usa ?debug=1 para ver el detalle técnico.'], 500);
}
