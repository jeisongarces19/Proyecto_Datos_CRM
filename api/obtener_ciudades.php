<?php
header('Content-Type: application/json');

// Verificar que se recibió el parámetro id_departamento
if (!isset($_GET['id_departamento']) || empty($_GET['id_departamento'])) {
    echo json_encode([
        'error' => true,
        'mensaje' => 'Se requiere el ID del departamento'
    ]);
    exit;
}

$id_departamento = $_GET['id_departamento'];
$username = "juanma"; // Reemplaza con tu usuario de GeoNames

// URL de la API para obtener ciudades (localidades) de un departamento
$url = "http://api.geonames.org/childrenJSON?geonameId={$id_departamento}&username={$username}";

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
    $ciudades = array_map(function($ciudad) {
        return [
            'id' => $ciudad['geonameId'],
            'name' => $ciudad['name'],
            'code' => isset($ciudad['adminCode2']) ? $ciudad['adminCode2'] : ''
        ];
    }, $data['geonames']);

    echo json_encode([
        'error' => false,
        'ciudades' => $ciudades
    ]);
} else {
    echo json_encode([
        'error' => true,
        'mensaje' => 'Formato de respuesta inválido o no hay ciudades disponibles'
    ]);
}
?>