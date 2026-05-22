<?php
// Include database connection
require_once '../../conexion.php';

// Get tipologia ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'ID de tipología no válido'
    ]);
    exit;
}

try {
    // Query to get tipologia details
    $sql = "SELECT * FROM tipologias WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($tipologia = $stmt->fetch()) {
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'tipologia' => $tipologia
        ]);
    } else {
        // Return error if not found
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Tipología no encontrada'
        ]);
    }
    
} catch (Exception $e) {
    // Return error as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>