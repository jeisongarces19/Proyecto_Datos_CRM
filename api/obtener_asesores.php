<?php
// Incluir archivo de conexión
require_once '../conexion.php';
header('Content-Type: application/json');

try {
    // Crear conexión a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Inicializar array de asesores con "Sin Asesor" como primera opción
    $asesores = [
        [
            'id' => 'Sin Asesor',
            'nombre' => 'Sin Asesor'
        ]
    ];
    
    // Consultar usuarios de la tabla usuarios (mostrar TODOS los asesores)
    $sql = "SELECT id, nombre FROM usuarios ORDER BY nombre ASC";
    $stmt = $pdo->query($sql);
    
    // Obtener todos los usuarios como un array asociativo y agregarlos al array
    $usuariosAsesores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $asesores = array_merge($asesores, $usuariosAsesores);
    
    // Devolver los asesores en formato JSON
    echo json_encode($asesores);
    
} catch(PDOException $e) {
    // En caso de error, devolver un mensaje de error
    echo json_encode([
        'error' => true,
        'mensaje' => 'Error al obtener asesores: ' . $e->getMessage()
    ]);
}
?>