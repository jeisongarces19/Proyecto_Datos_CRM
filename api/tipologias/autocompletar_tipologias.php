<?php
// Habilitar reporte de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuración básica
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Incluir el archivo de conexión
    require_once '../conexion.php';
    
    // Obtener término de búsqueda
    $termino = isset($_GET['term']) ? trim($_GET['term']) : '';
    $campo = isset($_GET['campo']) ? trim($_GET['campo']) : 'descripcion';
    
    // Validar campo para evitar inyección SQL
    $campos_permitidos = ['codigo', 'descripcion', 'categoria_costos', 'categoria_producto', 'tipo_articulo'];
    if (!in_array($campo, $campos_permitidos)) {
        $campo = 'descripcion'; // Valor por defecto si el campo no es válido
    }
    
    // Limitar resultados
    $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
    
    // Consulta SQL con LIKE para autocompletado
    $sql = "SELECT DISTINCT $campo as valor FROM tipologias WHERE $campo LIKE ? ORDER BY $campo LIMIT ?";
    
    // Preparar y ejecutar consulta
    $stmt = $conexion->prepare($sql);
    $termino_like = "%$termino%";
    $stmt->bind_param("si", $termino_like, $limite);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    // Procesar resultados
    $sugerencias = [];
    while ($fila = $resultado->fetch_assoc()) {
        $sugerencias[] = $fila['valor'];
    }
    
    // Devolver resultados como JSON
    echo json_encode($sugerencias);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'mensaje' => "Error: " . $e->getMessage()
    ]);
} finally {
    // Cerrar la declaración
    if (isset($stmt)) {
        $stmt->close();
    }
}
?>