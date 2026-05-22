<?php
// Include database connection
require_once '../../conexion.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $categoria_costos = isset($_POST['categoria_costos']) ? trim($_POST['categoria_costos']) : null;
    $categoria_producto = isset($_POST['categoria_producto']) ? trim($_POST['categoria_producto']) : null;
    $medida_primaria = isset($_POST['medida_primaria']) ? trim($_POST['medida_primaria']) : null;
    $tipo_articulo = isset($_POST['tipo_articulo']) ? trim($_POST['tipo_articulo']) : null;
    $imagen = isset($_POST['imagen']) ? trim($_POST['imagen']) : null;
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'activo';
    
    // Validate required fields
    if (empty($codigo) || empty($descripcion)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'El código y la descripción son obligatorios'
        ]);
        exit;
    }
    
    try {
        // Check if code already exists
        $sql_check = "SELECT id FROM tipologias WHERE codigo = :codigo";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':codigo', $codigo);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Ya existe una tipología con el código especificado'
            ]);
            exit;
        }
        
        // Insert new tipologia
        $sql = "INSERT INTO tipologias (codigo, descripcion, categoria_costos, categoria_producto, 
                medida_primaria, tipo_articulo, imagen, estado) 
                VALUES (:codigo, :descripcion, :categoria_costos, :categoria_producto, 
                :medida_primaria, :tipo_articulo, :imagen, :estado)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':categoria_costos', $categoria_costos);
        $stmt->bindParam(':categoria_producto', $categoria_producto);
        $stmt->bindParam(':medida_primaria', $medida_primaria);
        $stmt->bindParam(':tipo_articulo', $tipo_articulo);
        $stmt->bindParam(':imagen', $imagen);
        $stmt->bindParam(':estado', $estado);
        
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => 'Tipología creada correctamente',
                'id' => $pdo->lastInsertId()
            ]);
        } else {
            throw new Exception("Error al guardar la tipología");
        }
        
    } catch (Exception $e) {
        // Return error as JSON
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    // Return error if not POST request
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido'
    ]);
}
?>