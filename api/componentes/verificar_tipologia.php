<?php
// Incluir archivo de conexión
require_once '../../conexion.php';

// Validar método de la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que se haya recibido el código de tipología
    $codigo = isset($_GET['codigo']) ? $_GET['codigo'] : (isset($_POST['codigo']) ? $_POST['codigo'] : null);
    
    if ($codigo !== null) {
        // Sanitizar el código para evitar inyección SQL
        $codigo = htmlspecialchars($codigo);
        
        // Preparar la consulta para verificar si existe la tipología
        $sql = "SELECT codigo, descripcion FROM tipologias WHERE codigo = ? LIMIT 1";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $respuesta = array();
        
        if ($resultado && $resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            $respuesta = array(
                'existe' => true,
                'codigo' => $fila['codigo'],
                'descripcion' => $fila['descripcion']
            );
        } else {
            $respuesta = array(
                'existe' => false,
                'mensaje' => 'No se encontró ninguna tipología con el código especificado'
            );
        }
        
        // Devolver la respuesta en formato JSON
        header('Content-Type: application/json');
        echo json_encode($respuesta);
    } else {
        // Si no se recibió código, devolver error
        header('Content-Type: application/json');
        echo json_encode(array(
            'existe' => false,
            'mensaje' => 'No se proporcionó el código de tipología'
        ));
    }
} else {
    // Si el método no es GET o POST, devolver error 405
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: GET, POST');
    header('Content-Type: application/json');
    echo json_encode(array(
        'error' => 'Método no permitido'
    ));
}
?>