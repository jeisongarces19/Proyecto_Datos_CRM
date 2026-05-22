<?php
// Habilitar reporte de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuración básica
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Incluir el archivo de conexión - MODIFICAR ESTA RUTA para que apunte a la ubicación correcta
    require_once '../conexion.php';
    
    // Establecer el tipo de categoría a obtener (opcional, desde la consulta)
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todas';
    
    // Inicializar el array para la respuesta
    $categorias = [];
    
    // Consultas según el tipo de categoría solicitada
    switch ($tipo) {
        case 'costos':
            $sql = "SELECT DISTINCT categoria_costos as categoria FROM tipologias WHERE categoria_costos IS NOT NULL AND categoria_costos != '' ORDER BY categoria_costos";
            break;
            
        case 'productos':
            $sql = "SELECT DISTINCT categoria_producto as categoria FROM tipologias WHERE categoria_producto IS NOT NULL AND categoria_producto != '' ORDER BY categoria_producto";
            break;
            
        case 'tipos_articulo':
            $sql = "SELECT DISTINCT tipo_articulo as categoria FROM tipologias WHERE tipo_articulo IS NOT NULL AND tipo_articulo != '' ORDER BY tipo_articulo";
            break;
            
        default:
            // Obtener todas las categorías
            $sqlCostos = "SELECT DISTINCT categoria_costos as categoria, 'costos' as tipo FROM tipologias WHERE categoria_costos IS NOT NULL AND categoria_costos != ''";
            $sqlProductos = "SELECT DISTINCT categoria_producto as categoria, 'productos' as tipo FROM tipologias WHERE categoria_producto IS NOT NULL AND categoria_producto != ''";
            $sqlTipos = "SELECT DISTINCT tipo_articulo as categoria, 'tipos_articulo' as tipo FROM tipologias WHERE tipo_articulo IS NOT NULL AND tipo_articulo != ''";
            
            $sql = "$sqlCostos UNION $sqlProductos UNION $sqlTipos ORDER BY tipo, categoria";
            break;
    }
    
    // Ejecutar la consulta
    $resultado = $conexion->query($sql);
    
    if (!$resultado) {
        throw new Exception("Error al ejecutar la consulta: " . $conexion->error);
    }
    
    // Procesar los resultados
    while ($fila = $resultado->fetch_assoc()) {
        $categorias[] = $fila;
    }
    
    // Devolver los resultados como JSON
    echo json_encode([
        'error' => false,
        'categorias' => $categorias
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'mensaje' => "Error: " . $e->getMessage()
    ]);
}
?>