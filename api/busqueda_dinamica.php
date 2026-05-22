<?php
// api/busqueda_dinamica.php

// Incluir conexión a la base de datos
require_once '../conexion.php';

// Inicializar respuesta
$response = [
    'success' => false,
    'results' => [],
    'error' => null
];

// Validar parámetros
if (!isset($_GET['term']) || empty($_GET['term'])) {
    $response['error'] = 'Término de búsqueda no proporcionado';
    echo json_encode($response);
    exit;
}

// Obtener parámetros
$searchTerm = $_GET['term'];
$searchType = isset($_GET['type']) ? $_GET['type'] : 'nombre';

try {
    // Crear la consulta según el tipo de búsqueda
    if ($searchType === 'nombre') {
        $sql = "SELECT 
                c.Numero_Cliente as id, 
                c.Nombre as nombre, 
                c.Numero_De_Organizacion as identificacion,
                c.Digito_Verificacion as digito_verificacion,
                c.Estado as estado,
                c.Tipo_Cliente as tipo_cliente,
                c.Ciudad as ciudad
            FROM clientes1 c
            WHERE c.Nombre LIKE :searchTerm
            ORDER BY c.Nombre ASC
            LIMIT 10";
        $searchParam = '%' . $searchTerm . '%';
    } else {
        $sql = "SELECT 
                c.Numero_Cliente as id, 
                c.Nombre as nombre, 
                c.Numero_De_Organizacion as identificacion,
                c.Digito_Verificacion as digito_verificacion,
                c.Estado as estado,
                c.Tipo_Cliente as tipo_cliente,
                c.Ciudad as ciudad
            FROM clientes1 c
            WHERE c.Numero_De_Organizacion LIKE :searchTerm
            ORDER BY c.Numero_De_Organizacion ASC
            LIMIT 10";
        $searchParam = '%' . $searchTerm . '%';
    }

    // Ejecutar consulta
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':searchTerm', $searchParam, PDO::PARAM_STR);
    $stmt->execute();
    
    // Obtener resultados
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construir respuesta
    $response['success'] = true;
    $response['results'] = $results;
    
} catch (PDOException $e) {
    $response['error'] = 'Error en la búsqueda: ' . $e->getMessage();
}

// Enviar respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;