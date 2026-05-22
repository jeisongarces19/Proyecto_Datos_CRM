<?php
// api/tipologias/tipologias_buscar_descripcion.php
// Include database connection
require_once '../../conexion.php';

// Recibir término de búsqueda
$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (empty($term)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

try {
    // Consultar descripciones que coincidan con el término
    $sql = "SELECT id, descripcion FROM tipologias 
            WHERE descripcion LIKE :term 
            ORDER BY descripcion ASC 
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':term', '%' . $term . '%');
    $stmt->execute();
    
    $results = $stmt->fetchAll();
    
    // Formatear resultados para autocomplete
    $suggestions = [];
    foreach ($results as $row) {
        $suggestions[] = [
            'id' => $row['id'],
            'value' => $row['descripcion'],
            'label' => $row['descripcion']
        ];
    }
    
    // Devolver resultados en formato JSON
    header('Content-Type: application/json');
    echo json_encode($suggestions);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>