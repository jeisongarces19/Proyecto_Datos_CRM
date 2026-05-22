<?php
// Include database connection
require_once '../../conexion.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get tipologia ID
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'ID de tipología no válido'
        ]);
        exit;
    }
    
    try {
        // Delete tipologia
        $sql = "DELETE FROM tipologias WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => 'Tipología eliminada correctamente'
            ]);
        } else {
            throw new Exception("Error al eliminar la tipología");
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