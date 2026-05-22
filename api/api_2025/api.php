<?php
/**
 * ============================================================================
 * API.PHP - Catálogo de Productos MEPAL
 * ============================================================================
 *
 * Enrutamiento de tipologías según lista de precio:
 * ┌─────────────────────────────┬─────────────────────────────────────────────┐
 * │ Lista de Precio             │ JSON de Tipologías                          │
 * ├─────────────────────────────┼─────────────────────────────────────────────┤
 * │ MEPAL_CO_Nacionales (COP)   │ tipologias-detalle_Colombia.json            │
 * │ MEPAL_CO_Nacionales USD     │ tipologias-detalle_Colombia.json            │
 * │ MEPAL_CO_Sur America        │ tipologias-detalle_Distribuidores.json      │
 * │ MEPAL_EC_Nacionales         │ tipologias-detalle_Ecuador.json             │
 * │ (Sin lista / Todas)         │ tipologias-detalle_Colombia.json (default)  │
 * └─────────────────────────────┴─────────────────────────────────────────────┘
 *
 * Acciones disponibles:
 *  - listar               → Categorías principales (sin padre)
 *  - listar_hijos         → Subcategorías por padre_id
 *  - listar_productos     → Productos por categoria_id
 *  - listar_tipologias    → Tipologías por categoria_id
 *  - detalle_tipologia    → Detalle de una tipología por código
 *  - detalle              → Detalle de una categoría por ID
 *  - obtener_categorias   → Mapa código→categoría para el frontend
 *  - buscar_productos     → Búsqueda libre de productos y tipologías
 * ============================================================================
 */

header('Content-Type: application/json');

// ============================================================================
// AUTENTICACIÓN HTTP BÁSICA
// ============================================================================
$API_USER = 'root';
$API_PASS = '12345678';

if (
    !isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== $API_USER ||
    $_SERVER['PHP_AUTH_PW'] !== $API_PASS
) {
    header('WWW-Authenticate: Basic realm="API"');
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado.']);
    exit;
}

// ============================================================================
// CONFIGURACIÓN DE RUTAS
// ============================================================================
define('APIJSON_PATH', realpath(__DIR__ . '/../../api_json/'));

/**
 * Resuelve qué archivo JSON de tipologías-detalle usar según la lista de precio.
 *
 * @param  string $listaPrecio  Nombre de la lista de precio recibida del cliente.
 * @return string               Nombre del archivo JSON correspondiente.
 */
function resolverArchivoTipologias(string $listaPrecio): string
{
    // Mapa: fragmento de nombre de lista → archivo JSON
    $mapa = [
        // Ecuador → JSON de Ecuador
        'MEPAL_EC_Nacionales'  => 'tipologias-detalle_Ecuador.json',

        // Distribuidores / Sur América → JSON de Distribuidores
        'MEPAL_CO_Sur America' => 'tipologias-detalle_Distribuidores.json',

        // Colombia (COP o USD) → JSON de Colombia
        'MEPAL_CO_Nacionales'  => 'tipologias-detalle_Colombia.json',
    ];

    // Recorrer el mapa en orden; el primero que coincida exactamente gana.
    foreach ($mapa as $clave => $archivo) {
        if (trim($listaPrecio) === $clave) {
            return $archivo;
        }
    }

    // Valor por defecto: Colombia
    return 'tipologias-detalle_Colombia.json';
}

// ============================================================================
// FUNCIONES AUXILIARES
// ============================================================================

/**
 * Lee y decodifica un archivo JSON del directorio api_json.
 *
 * @param  string $filename  Nombre del archivo (ej: 'categorias.json').
 * @return array             Array decodificado; [] si no existe o hay error.
 */
function getJsonData(string $filename): array
{
    if (!APIJSON_PATH) return [];
    $ruta = APIJSON_PATH . '/' . $filename;
    if (!file_exists($ruta)) return [];
    $contenido = @file_get_contents($ruta);
    return json_decode($contenido, true) ?: [];
}

/**
 * Corrige URLs de imágenes que apuntan al servidor antiguo.
 * Actúa de forma recursiva sobre arrays anidados.
 *
 * @param array $data  Array (pasado por referencia) sobre el que operar.
 */
function fixImagePaths(array &$data): void
{
    if (empty($data)) return;

    $oldBaseUrl = 'https://espacios.carvajal.com/media/';
    $newBaseUrl = 'https://www.mepal.com.co/crm/api_uploads/';

    foreach ($data as $key => &$value) {
        if (is_array($value)) {
            fixImagePaths($value);
        } elseif (in_array($key, ['ubicacion', 'urlubicacion', 'url'], true) && is_string($value)) {
            // Reemplazar base URL antigua
            $value = str_replace($oldBaseUrl, $newBaseUrl, $value);
            // Si no tiene esquema, agregar la base nueva
            if (!preg_match('/^https?:\/\//', $value)) {
                $value = $newBaseUrl . ltrim($value, '/');
            }
        }
    }
    unset($value);
}

/**
 * Devuelve la primera URL de imagen encontrada para un código de producto.
 *
 * @param  string|int $codigo  Código del producto.
 * @return string|null         URL de imagen o null si no existe.
 */
function getProductImage($codigo): ?string
{
    $imagenes = getJsonData('imagenes.json');
    foreach ($imagenes as $img) {
        if (isset($img['nombre']) && $img['nombre'] == $codigo) {
            return $img['url'] ?? $img['ubicacion'] ?? null;
        }
    }
    return null;
}

/**
 * Normaliza un texto para comparaciones: minúsculas, sin acentos,
 * sin espacios múltiples.
 *
 * @param  string $texto
 * @return string
 */
function normalizarTexto(string $texto): string
{
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = preg_replace('/\s+/', ' ', $texto);
    return trim($texto);
}

/**
 * Comprueba si $haystack contiene $needle (búsqueda insensible a mayúsculas).
 *
 * @param  string $haystack  Texto donde buscar.
 * @param  string $needle    Texto a buscar.
 * @return bool
 */
function contieneTexto(string $haystack, string $needle): bool
{
    if ($needle === '') return true;
    return strpos(normalizarTexto($haystack), normalizarTexto($needle)) !== false;
}

// ============================================================================
// PARÁMETROS DE ENTRADA
// ============================================================================
$accion         = $_GET['accion'] ?? 'listar';
$padreId        = isset($_GET['padre_id']) ? intval($_GET['padre_id'])
                : (isset($_GET['padre'])   ? intval($_GET['padre'])   : null);
$categoriaId    = isset($_GET['categoria_id']) ? intval($_GET['categoria_id'])  : null;
$codigoTipologia = isset($_GET['codigo'])      ? trim($_GET['codigo'])          : null;
$idCategoria    = isset($_GET['id'])           ? intval($_GET['id'])            : null;

// ============================================================================
// ACCIÓN: obtener_categorias
// Devuelve el mapa código→categoría usado por el frontend para badges.
// ============================================================================
if ($accion === 'obtener_categorias') {
    $data = getJsonData('categorias_intranet.json');

    if (empty($data)) {
        echo json_encode([
            'error'        => true,
            'mensaje'      => 'categorias_intranet.json vacío o no encontrado',
            'ruta_buscada' => APIJSON_PATH . '/categorias_intranet.json',
        ]);
        exit;
    }

    echo json_encode($data);
    exit;
}

// ============================================================================
// ACCIÓN: listar
// Devuelve las categorías raíz (sin padre_id) ordenadas alfabéticamente.
// ============================================================================
if ($accion === 'listar') {
    $data = getJsonData('categorias.json');

    if (empty($data)) {
        echo json_encode(['results' => [], 'error' => 'categorias.json vacío o mal formateado']);
        exit;
    }

    fixImagePaths($data);

    $results = array_values(array_filter($data, function ($cat) {
        return empty($cat['padre_id']);   // sin padre o padre_id nulo/0/""
    }));

    usort($results, fn($a, $b) => strcasecmp($a['nombre'] ?? '', $b['nombre'] ?? ''));

    echo json_encode(['results' => $results]);
    exit;
}

// ============================================================================
// ACCIÓN: listar_hijos
// Devuelve las subcategorías de una categoría padre, ordenadas alfabéticamente.
// ============================================================================
if ($accion === 'listar_hijos' && $padreId) {
    $data = getJsonData('categorias.json');

    if (empty($data)) {
        echo json_encode(['results' => [], 'error' => 'categorias.json vacío o mal formateado']);
        exit;
    }

    fixImagePaths($data);

    $results = array_values(array_filter($data, function ($cat) use ($padreId) {
        return isset($cat['padre_id']) && intval($cat['padre_id']) === $padreId;
    }));

    usort($results, fn($a, $b) => strcasecmp($a['nombre'] ?? '', $b['nombre'] ?? ''));

    echo json_encode(['results' => $results]);
    exit;
}

// ============================================================================
// ACCIÓN: listar_productos
// Devuelve los productos de una categoría con su imagen y nombre de categoría.
// ============================================================================
if ($accion === 'listar_productos' && $categoriaId) {
    $data = getJsonData('precios-detalle.json');

    if (empty($data)) {
        echo json_encode(['results' => [], 'error' => 'precios-detalle.json vacío o mal formateado']);
        exit;
    }

    fixImagePaths($data);

    // Mapa id→nombre de categoría para mostrar en frontend
    $categoriasMap = [];
    foreach (getJsonData('categorias_intranet.json') as $cat) {
        if (isset($cat['id'], $cat['nombre'])) {
            $categoriasMap[$cat['id']] = $cat['nombre'];
        }
    }

    // Filtrar por categoría y eliminar duplicados de código
    $final        = [];
    $codigosUnicos = [];

    foreach ($data as $prod) {
        if (!isset($prod['categoria']['id']) || intval($prod['categoria']['id']) !== $categoriaId) {
            continue;
        }

        $codigo = $prod['producto_codigo'] ?? '';
        if (isset($codigosUnicos[$codigo])) continue;

        // Imagen: primero del registro, luego desde imagenes.json
        $imgUrl = (isset($prod['imagen']) && is_string($prod['imagen']) && $prod['imagen'])
            ? $prod['imagen']
            : getProductImage($codigo);

        $prodFinal                   = $prod;
        $prodFinal['img_url']        = $imgUrl;
        $prodFinal['categoria_nombre'] = $categoriasMap[$prod['categoria']['id']] ?? 'Sin categoría';

        $final[]             = $prodFinal;
        $codigosUnicos[$codigo] = true;
    }

    usort($final, fn($a, $b) => strcasecmp($a['producto_descripcion'] ?? '', $b['producto_descripcion'] ?? ''));

    echo json_encode(['results' => array_values($final)]);
    exit;
}

// ============================================================================
// ACCIÓN: listar_tipologias
// Devuelve las tipologías de una categoría ordenadas por descripción.
// ============================================================================
if ($accion === 'listar_tipologias' && $categoriaId) {
    $data = getJsonData('tipologias-lista.json');

    if (empty($data)) {
        echo json_encode(['results' => [], 'error' => 'tipologias-lista.json vacío o mal formateado']);
        exit;
    }

    fixImagePaths($data);

    $results = array_values(array_filter($data, function ($tipo) use ($categoriaId) {
        return isset($tipo['categoria_tipologia_id'])
            && intval($tipo['categoria_tipologia_id']) === $categoriaId;
    }));

    usort($results, fn($a, $b) => strcasecmp($a['descripcion'] ?? '', $b['descripcion'] ?? ''));

    echo json_encode(['results' => $results]);
    exit;
}

// ============================================================================
// ACCIÓN: detalle_tipologia
// Devuelve el detalle de una tipología por código.
//
// ROUTING SEGÚN LISTA DE PRECIO:
//   ?lista=MEPAL_EC_Nacionales  → tipologias-detalle_Ecuador.json
//   ?lista=MEPAL_CO_Sur America → tipologias-detalle_Distribuidores.json
//   ?lista=MEPAL_CO_Nacionales* → tipologias-detalle_Colombia.json  (default)
// ============================================================================
if ($accion === 'detalle_tipologia' && $codigoTipologia) {

    // Leer lista de precio desde query string (opcional, default = Colombia)
    $listaPrecio = isset($_GET['lista']) ? trim($_GET['lista']) : '';

    // Resolver qué JSON cargar
    $archivoTipologias = resolverArchivoTipologias($listaPrecio);

    $data = getJsonData($archivoTipologias);

    if (empty($data)) {
        echo json_encode([
            'results' => [],
            'error'   => "$archivoTipologias vacío o no encontrado",
            'archivo' => $archivoTipologias,   // útil para debugging
        ]);
        exit;
    }

    fixImagePaths($data);

    $results = array_values(array_filter($data, function ($tipo) use ($codigoTipologia) {
        return isset($tipo['codigo']) && $tipo['codigo'] == $codigoTipologia;
    }));

    echo json_encode([
        'results' => $results,
        'archivo' => $archivoTipologias,   // incluido para transparencia / debug
    ]);
    exit;
}

// ============================================================================
// ACCIÓN: detalle
// Devuelve el detalle de una categoría por ID.
// ============================================================================
if ($accion === 'detalle' && $idCategoria) {
    $data = getJsonData('categorias.json');

    if (empty($data)) {
        echo json_encode(['success' => false, 'data' => null, 'error' => 'categorias.json vacío o mal formateado']);
        exit;
    }

    fixImagePaths($data);

    $results = array_values(array_filter($data, function ($cat) use ($idCategoria) {
        return isset($cat['id']) && intval($cat['id']) === $idCategoria;
    }));

    $finalData = $results[0] ?? null;

    echo json_encode(['success' => $finalData !== null, 'data' => $finalData]);
    exit;
}

// ============================================================================
// ACCIÓN: buscar_productos
// Búsqueda libre por código, descripción y/o lista de precio.
//
// ROUTING IGUAL QUE detalle_tipologia:
//   Cuando se filtra por lista, las tipologías se buscan en el JSON correcto.
// ============================================================================
if ($accion === 'buscar_productos') {

    // --- Leer cuerpo JSON ---
    $inputRaw = file_get_contents('php://input');

    if (empty($inputRaw)) {
        http_response_code(400);
        echo json_encode(['error' => 'No se recibieron datos de búsqueda']);
        exit;
    }

    $input = json_decode($inputRaw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido: ' . json_last_error_msg()]);
        exit;
    }

    $codigo      = isset($input['codigo'])      ? trim($input['codigo'])      : '';
    $descripcion = isset($input['descripcion']) ? trim($input['descripcion']) : '';
    $listaPrecio = isset($input['listaPrecio']) ? trim($input['listaPrecio']) : '';

    // ------------------------------------------------------------------
    // Resolver qué JSON de tipologías usar según la lista de precio
    // ------------------------------------------------------------------
    $archivoTipologias = resolverArchivoTipologias($listaPrecio);

    // ------------------------------------------------------------------
    // PARTE 1: Búsqueda en PRODUCTOS (precios-detalle.json)
    // ------------------------------------------------------------------
    $preciosDetalle = getJsonData('precios-detalle.json');
    fixImagePaths($preciosDetalle);

    // Mapa id→nombre de categoría
    $categoriasMap = [];
    foreach (getJsonData('categorias_intranet.json') as $cat) {
        if (isset($cat['id'], $cat['nombre'])) {
            $categoriasMap[$cat['id']] = $cat['nombre'];
        }
    }

    $resultado    = [];
    $codigosUnicos = [];

    foreach ($preciosDetalle as $precio) {
        $cumpleCodigo      = empty($codigo)      || contieneTexto($precio['producto_codigo'] ?? '',      $codigo);
        $cumpleDescripcion = empty($descripcion) || contieneTexto($precio['producto_descripcion'] ?? '', $descripcion);
        $cumpleLista       = empty($listaPrecio) || ($precio['lista'] ?? '') === $listaPrecio;

        if (!($cumpleCodigo && $cumpleDescripcion && $cumpleLista)) continue;

        $codigoProd = $precio['producto_codigo'];

        if (isset($codigosUnicos[$codigoProd . '_producto'])) continue;

        // Nombre de categoría
        $categoriaNombre = 'Sin categoría';
        if (isset($precio['categoria']['id'])) {
            $categoriaNombre = $categoriasMap[$precio['categoria']['id']] ?? 'Sin categoría';
        }

        $resultado[] = [
            'codigo'              => $codigoProd,
            'producto_codigo'     => $codigoProd,
            'descripcion'         => $precio['producto_descripcion'] ?? '',
            'producto_descripcion' => $precio['producto_descripcion'] ?? '',
            'precio'              => $precio['precio'] ?? 0,
            'lista'               => $precio['lista'] ?? '',
            'img_url'             => getProductImage($codigoProd),
            'categoria_nombre'    => $categoriaNombre,
            'tipo'                => 'producto',
        ];

        $codigosUnicos[$codigoProd . '_producto'] = true;
    }

    // ------------------------------------------------------------------
    // PARTE 2: Búsqueda en TIPOLOGÍAS
    //   - Lista  → tipologias-lista.json     (siempre)
    //   - Detalle → $archivoTipologias       (según lista de precio)
    // ------------------------------------------------------------------
    $tipologiasLista  = getJsonData('tipologias-lista.json');
    $tipologiasDetalle = getJsonData($archivoTipologias);       // ← JSON correcto
    $productosHijos   = getJsonData('productos-hijos-tipologia.json');

    fixImagePaths($tipologiasLista);
    fixImagePaths($tipologiasDetalle);

    // Índice de hijos por tipología para cálculo de precio total
    $hijosIndex = [];
    foreach ($productosHijos as $hijo) {
        $tipId = $hijo['tipologia_id'];
        $hijosIndex[$tipId][] = $hijo;
    }

    foreach ($tipologiasLista as $tip) {
        $codigoTip      = $tip['codigo']      ?? '';
        $descripcionTip = $tip['descripcion'] ?? '';

        $cumpleCodigo      = empty($codigo)      || contieneTexto($codigoTip,      $codigo);
        $cumpleDescripcion = empty($descripcion) || contieneTexto($descripcionTip, $descripcion);

        // Verificar lista de precio: la tipología debe tener al menos un hijo
        // cuyo producto exista en precios-detalle con esa lista.
        $cumpleLista = true;
        if (!empty($listaPrecio)) {
            $cumpleLista = false;
            if (isset($hijosIndex[$codigoTip])) {
                foreach ($hijosIndex[$codigoTip] as $hijo) {
                    $codigoHijo = $hijo['producto']['codigo'] ?? null;
                    if (!$codigoHijo) continue;

                    foreach ($preciosDetalle as $precioHijo) {
                        if ($precioHijo['producto_codigo'] === $codigoHijo
                            && ($precioHijo['lista'] ?? '') === $listaPrecio
                        ) {
                            $cumpleLista = true;
                            break 2;
                        }
                    }
                }
            }
        }

        if (!($cumpleCodigo && $cumpleDescripcion && $cumpleLista)) continue;

        // Precio total = suma de precios  de los hijos
        $precioTotal = 0;
        if (isset($hijosIndex[$codigoTip])) {
            foreach ($hijosIndex[$codigoTip] as $hijo) {
                $precioTotal += $hijo['precio'] ?? 0;
            }
        }

        // Imagen de la tipología
        $img = $tip['imagen']['urlubicacion']
            ?? $tip['imagen']['url']
            ?? $tip['imagen']['ubicacion']
            ?? null;

        $resultado[] = [
            'codigo'              => $codigoTip,
            'producto_codigo'     => $codigoTip,
            'descripcion'         => $descripcionTip,
            'producto_descripcion' => $descripcionTip,
            'precio'              => $precioTotal,
            'lista'               => $listaPrecio ?: 'Múltiples',
            'img_url'             => $img,
            'tipo'                => 'tipologia',
            'archivo_tipologias'  => $archivoTipologias,  // debug: qué JSON se usó
        ];
    }

    // Ordenar alfabéticamente por descripción
    usort($resultado, fn($a, $b) => strcasecmp($a['descripcion'], $b['descripcion']));

    echo json_encode([
        'results'            => $resultado,
        'archivo_tipologias' => $archivoTipologias,   // debug
    ]);
    exit;
}

// ============================================================================
// ACCIÓN INVÁLIDA
// ============================================================================
http_response_code(400);
echo json_encode([
    'error' => 'Acción no válida.',
    'debug' => [
        'accion' => $accion,
        'params' => $_GET,
    ],
]);
exit;