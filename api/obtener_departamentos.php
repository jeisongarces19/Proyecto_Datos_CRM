<?php
header('Content-Type: application/json');

// Verificar que se recibió el parámetro id_pais
if (!isset($_GET['id_pais']) || empty($_GET['id_pais'])) {
    echo json_encode([
        'error' => true,
        'mensaje' => 'Se requiere el ID del país'
    ]);
    exit;
}

$id_pais = $_GET['id_pais']; 
$username = "juanma"; // Reemplaza con tu usuario de GeoNames

// URL de la API para obtener departamentos (divisiones administrativas) de un país
$url = "http://api.geonames.org/childrenJSON?geonameId={$id_pais}&username={$username}";

// Realizar petición a la API
$response = file_get_contents($url);

if ($response === false) {
    echo json_encode([
        'error' => true,
        'mensaje' => 'Error al obtener datos de GeoNames'
    ]);
    exit;
}

// Procesar respuesta
$data = json_decode($response, true);

if (isset($data['geonames']) && is_array($data['geonames'])) {
    // Transformar datos al formato esperado por el frontend
    $departamentos = array_map(function($departamento) {
        // Eliminar "Department" del nombre
        $nombre = str_replace(' Department', '', $departamento['name']);
        $nombre = str_replace('Department', '', $nombre);
        
        return [
            'id' => $departamento['geonameId'],
            'name' => trim($nombre),
            'code' => isset($departamento['adminCode1']) ? $departamento['adminCode1'] : ''
        ];
    }, $data['geonames']);

    echo json_encode([
        'error' => false,
        'departamentos' => $departamentos
    ]);
} else {
    echo json_encode([
        'error' => true,
        'mensaje' => 'Formato de respuesta inválido o no hay departamentos disponibles'
    ]);
}
?>