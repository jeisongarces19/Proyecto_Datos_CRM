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
    
    // Procesar parámetros de paginación
    $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
    $registros_por_pagina = isset($_POST['registros_por_pagina']) ? (int)$_POST['registros_por_pagina'] : 10;
    $offset = ($pagina - 1) * $registros_por_pagina;
    
    // Procesar filtros
    $parametros = [];
    $condiciones = [];
    
    // Obtener filtros desde POST
    $filtro_codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
    $filtro_descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $filtro_categoria = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';
    $filtro_estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
    
    // Añadir condiciones según los filtros
    if (!empty($filtro_codigo)) {
        $condiciones[] = "codigo LIKE ?";
        $parametros[] = "%$filtro_codigo%";
    }
    
    if (!empty($filtro_descripcion)) {
        $condiciones[] = "descripcion LIKE ?";
        $parametros[] = "%$filtro_descripcion%";
    }
    
    if (!empty($filtro_categoria)) {
        $condiciones[] = "categoria_costos LIKE ?";
        $parametros[] = "%$filtro_categoria%";
    }
    
    if (!empty($filtro_estado)) {
        $condiciones[] = "estado = ?";
        $parametros[] = $filtro_estado;
    }
    
    // Consulta para contar el total de registros
    $sql_total = "SELECT COUNT(*) AS total FROM tipologias";
    if (count($condiciones) > 0) {
        $sql_total .= " WHERE " . implode(" AND ", $condiciones);
    }
    
    // Preparar la consulta para contar
    $stmt_total = $conexion->prepare($sql_total);
    
    // Vincular parámetros si hay condiciones
    if (count($parametros) > 0) {
        $tipos = str_repeat('s', count($parametros)); // Todos los parámetros son strings
        $stmt_total->bind_param($tipos, ...$parametros);
    }
    
    // Ejecutar consulta para contar
    $stmt_total->execute();
    $resultado_total = $stmt_total->get_result();
    $fila_total = $resultado_total->fetch_assoc();
    $total_registros = $fila_total['total'];
    
    // Consulta para obtener los datos paginados
    $sql = "SELECT * FROM tipologias";
    if (count($condiciones) > 0) {
        $sql .= " WHERE " . implode(" AND ", $condiciones);
    }
    $sql .= " ORDER BY codigo ASC LIMIT ?, ?";
    
    // Preparar la consulta para los datos
    $stmt = $conexion->prepare($sql);
    
    // Crear array de parámetros para la consulta paginada
    $parametros_paginados = $parametros;
    $parametros_paginados[] = $offset;
    $parametros_paginados[] = $registros_por_pagina;
    
    // Crear string de tipos para bind_param
    if (count($parametros) > 0) {
        $tipos = str_repeat('s', count($parametros)) . 'ii'; // Offset y limit son integers
    } else {
        $tipos = 'ii'; // Solo offset y limit si no hay otros parámetros
    }
    
    // Vincular parámetros
    $stmt->bind_param($tipos, ...$parametros_paginados);
    
    // Ejecutar consulta para datos
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if (!$resultado) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }
    
    // Procesar resultados
    $tipologias = [];
    while ($fila = $resultado->fetch_assoc()) {
        // Formatear cada tipología según la estructura actual de la tabla
        $tipologia = [
            'id' => $fila['id'],
            'codigo' => $fila['codigo'],
            'descripcion' => $fila['descripcion'],
            'categoria_costos' => $fila['categoria_costos'] ?? '',
            'categoria_producto' => $fila['categoria_producto'] ?? '',
            'medida_primaria' => $fila['medida_primaria'] ?? '',
            'tipo_articulo' => $fila['tipo_articulo'] ?? '',
            'imagen' => $fila['imagen'] ?? '',
            'estado' => $fila['estado'] ?? 'activo',
        ];
        
        $tipologias[] = $tipologia;
    }
    
    // Devolver resultados en el formato que espera el frontend
    echo json_encode([
        'datos' => $tipologias,
        'total_registros' => $total_registros
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'mensaje' => "Error: " . $e->getMessage()
    ]);
} finally {
    // Cerrar las declaraciones y conexiones
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($stmt_total)) {
        $stmt_total->close();
    }
}
?>