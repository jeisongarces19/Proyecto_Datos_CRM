<?php
header('Content-Type: application/json');

// Configuración de GeoNames
$username = "juanma"; // Reemplaza con tu usuario de GeoNames

// URL de la API para obtener países
$url = "http://api.geonames.org/countryInfoJSON?username={$username}";

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
    $paises = array_map(function($pais) {
        return [
            'id' => $pais['geonameId'],
            'name' => $pais['countryName'],
            'code' => $pais['countryCode']
        ];
    }, $data['geonames']);

    echo json_encode([
        'error' => false,
        'paises' => $paises
    ]);
} else {
    echo json_encode([
        'error' => true,
        'mensaje' => 'Formato de respuesta inválido'
    ]);
}