<?php
// Include database connection
require_once '../../conexion.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if file was uploaded
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al subir el archivo'
        ]);
        exit;
    }
    
    // Get file extension
    $file_extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
    
    // Check if it's a valid file type
    if (!in_array($file_extension, ['csv', 'xlsx', 'xls'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'El archivo debe ser un CSV o Excel'
        ]);
        exit;
    }
    
    // Process CSV file (for simplicity, we're just implementing CSV)
    if ($file_extension === 'csv') {
        $file = fopen($_FILES['archivo']['tmp_name'], 'r');
        
        // Skip first row if it contains headers
        $has_headers = isset($_POST['primera_fila_encabezados']) && $_POST['primera_fila_encabezados'] == '1';
        if ($has_headers) {
            fgetcsv($file);
        }
        
        $importados = 0;
        $errores = 0;
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Prepare insert statement
            $sql = "INSERT INTO tipologias (codigo, descripcion, categoria_costos, categoria_producto, 
                    medida_primaria, tipo_articulo, imagen, estado) 
                    VALUES (:codigo, :descripcion, :categoria_costos, :categoria_producto, 
                    :medida_primaria, :tipo_articulo, :imagen, :estado)";
            $stmt = $pdo->prepare($sql);
            
            // Read each row
            while (($row = fgetcsv($file)) !== false) {
                // Expected format: codigo,descripcion,categoria_costos,categoria_producto,medida_primaria,tipo_articulo,imagen,estado
                $codigo = isset($row[0]) ? trim($row[0]) : '';
                $descripcion = isset($row[1]) ? trim($row[1]) : '';
                $categoria_costos = isset($row[2]) ? trim($row[2]) : null;
                $categoria_producto = isset($row[3]) ? trim($row[3]) : null;
                $medida_primaria = isset($row[4]) ? trim($row[4]) : null;
                $tipo_articulo = isset($row[5]) ? trim($row[5]) : null;
                $imagen = isset($row[6]) ? trim($row[6]) : null;
                $estado = isset($row[7]) && (strtolower($row[7]) === 'true' || $row[7] === '1') ? 'activo' : 'inactivo';
                
                // Skip if required fields are missing
                if (empty($codigo) || empty($descripcion)) {
                    $errores++;
                    continue;
                }
                
                // Check if code already exists
                $sql_check = "SELECT id FROM tipologias WHERE codigo = :codigo";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->bindParam(':codigo', $codigo);
                $stmt_check->execute();
                
                if ($stmt_check->rowCount() > 0) {
                    $errores++;
                    continue; // Skip this record
                }
                
                // Insert new record
                $stmt->bindParam(':codigo', $codigo);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':categoria_costos', $categoria_costos);
                $stmt->bindParam(':categoria_producto', $categoria_producto);
                $stmt->bindParam(':medida_primaria', $medida_primaria);
                $stmt->bindParam(':tipo_articulo', $tipo_articulo);
                $stmt->bindParam(':imagen', $imagen);
                $stmt->bindParam(':estado', $estado);
                
                if ($stmt->execute()) {
                    $importados++;
                } else {
                    $errores++;
                }
            }
            
            fclose($file);
            
            // Commit transaction
            $pdo->commit();
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'message' => 'Importación completada',
                'importados' => $importados,
                'errores' => $errores
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction
            $pdo->rollBack();
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    } else {
        // For Excel files, you would need a library like PhpSpreadsheet
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'La importación de archivos Excel aún no está implementada'
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