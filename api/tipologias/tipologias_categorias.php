<?php
// Include database connection
require_once '../../conexion.php';

try {
    // Query to get unique categories
    $sql = "SELECT DISTINCT categoria_costos as nombre, categoria_costos as id 
            FROM tipologias 
            WHERE categoria_costos IS NOT NULL AND categoria_costos != '' 
            ORDER BY categoria_costos ASC";
    
    $stmt = $pdo->query($sql);
    $categorias = $stmt->fetchAll();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'categorias_costos' => $categorias
    ]);
    
} catch (Exception $e) {
    // Return error as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>