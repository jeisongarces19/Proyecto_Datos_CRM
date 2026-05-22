<?php
// Include database connection
require_once '../../conexion.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $id = isset($_POST['id_tipologia']) ? intval($_POST['id_tipologia']) : 0;
    $codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $categoria_costos = isset($_POST['categoria_costos']) ? trim($_POST['categoria_costos']) : null;
    $categoria_producto = isset($_POST['categoria_producto']) ? trim($_POST['categoria_producto']) : null;
    $medida_primaria = isset($_POST['medida_primaria']) ? trim($_POST['medida_primaria']) : null;
    $tipo_articulo = isset($_POST['tipo_articulo']) ? trim($_POST['tipo_articulo']) : null;
    $imagen = isset($_POST['imagen']) ? trim($_POST['imagen']) : null;
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'activo';
    
    // Validate required fields
    if ($id <= 0 || empty($codigo) || empty($descripcion)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'ID, código y descripción son obligatorios'
        ]);
        exit;
    }
    
    try {
        // Check if code already exists for another record
        $sql_check = "SELECT id FROM tipologias WHERE codigo = :codigo AND id != :id";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':codigo', $codigo);
        $stmt_check->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Ya existe otra tipología con el código especificado'
            ]);
            exit;
        }
        
        // Update tipologia
        $sql = "UPDATE tipologias SET 
                codigo = :codigo, 
                descripcion = :descripcion, 
                categoria_costos = :categoria_costos, 
                categoria_producto = :categoria_producto, 
                medida_primaria = :medida_primaria, 
                tipo_articulo = :tipo_articulo, 
                imagen = :imagen, 
                estado = :estado 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':categoria_costos', $categoria_costos);
        $stmt->bindParam(':categoria_producto', $categoria_producto);
        $stmt->bindParam(':medida_primaria', $medida_primaria);
        $stmt->bindParam(':tipo_articulo', $tipo_articulo);
        $stmt->bindParam(':imagen', $imagen);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => 'Tipología actualizada correctamente'
            ]);
        } else {
            throw new Exception("Error al actualizar la tipología");
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