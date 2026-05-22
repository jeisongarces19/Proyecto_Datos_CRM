<?php
// Include connection file
require_once '../../conexion.php';
// Receive parameters
$pagina = isset($_POST['pagina']) ? intval($_POST['pagina']) : 1;
$registros_por_pagina = isset($_POST['registros_por_pagina']) ? intval($_POST['registros_por_pagina']) : 10;
$offset = ($pagina - 1) * $registros_por_pagina;

// Base query
$sql = "SELECT * FROM tipologias";
$sql_count = "SELECT COUNT(*) as total FROM tipologias";

// Apply filters if provided
$where_conditions = [];
$params = [];

if (!empty($_POST['codigo'])) {
    $where_conditions[] = "codigo LIKE :codigo";
    $params[':codigo'] = '%' . $_POST['codigo'] . '%';
}

if (!empty($_POST['descripcion'])) {
    $where_conditions[] = "descripcion LIKE :descripcion";
    $params[':descripcion'] = '%' . $_POST['descripcion'] . '%';
}

if (!empty($_POST['categoria'])) {
    $where_conditions[] = "categoria_costos = :categoria";
    $params[':categoria'] = $_POST['categoria'];
}

if (!empty($_POST['estado'])) {
    $where_conditions[] = "estado = :estado";
    $params[':estado'] = $_POST['estado'];
}

// Add WHERE clause if conditions exist
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
    $sql_count .= " WHERE " . implode(' AND ', $where_conditions);
}

// Add pagination
$sql .= " ORDER BY id DESC LIMIT :offset, :limit";

try {
    // Use the PDO connection from conexion.php
    
    // Execute count query first
    $stmt_count = $pdo->prepare($sql_count);
    
    // Bind parameters for count query
    foreach ($params as $param_name => $param_value) {
        $stmt_count->bindValue($param_name, $param_value);
    }
    
    $stmt_count->execute();
    $total_registros = $stmt_count->fetchColumn();
    
    // Execute main query
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters for main query
    foreach ($params as $param_name => $param_value) {
        $stmt->bindValue($param_name, $param_value);
    }
    
    // Bind pagination parameters
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
    
    $stmt->execute();
    $tipologias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare response
    $response = [
        'status' => 'success',
        'datos' => $tipologias,
        'total_registros' => $total_registros,
        'pagina_actual' => $pagina,
        'registros_por_pagina' => $registros_por_pagina
    ];
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    // Return error as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}
?>