<?php
// api/busqueda_dinamica.php
// API para realizar búsquedas dinámicas de clientes

// Iniciar sesión para mantener la seguridad existente
session_start();

// Comprobar si el usuario está autenticado (ajustar según tu sistema de autenticación)
// if (!isset($_SESSION['usuario_id'])) {
//     header('Content-Type: application/json');
//     echo json_encode(['error' => 'No autorizado']);
//     exit;
// }

// Conexión a la base de datos (importar conexion.php o definir aquí)
require_once '../conexion.php';

// Función para sanitizar entradas
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Parámetros de búsqueda
$searchTerm = isset($_GET['term']) ? sanitizeInput($_GET['term']) : '';
$searchType = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'nombre';

// Validación de parámetros
if (empty($searchTerm) || strlen($searchTerm) < 2) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'El término de búsqueda debe tener al menos 2 caracteres']);
    exit;
}

// Determinar campo de búsqueda según tipo
$searchField = $searchType === 'id' ? 'c.Numero_De_Organizacion' : 'c.Nombre';

try {
    // Consulta para buscar clientes - Corregido Estado_Cliente a Estado
    $sql = "SELECT 
        c.Numero_Cliente as id,
        c.Nombre as nombre,
        c.Numero_De_Organizacion as identificacion,
        c.Digito_Verificacion as dv,
        c.Tipo_Cliente as tipo_cliente,
        c.Estado as estado,
        c.Ciudad as ciudad
    FROM 
        clientes1 c
    WHERE 
        $searchField LIKE :searchTerm
    ORDER BY 
        $searchField ASC
    LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':searchTerm', "%$searchTerm%", PDO::PARAM_STR);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear resultados si es necesario
    foreach ($results as &$result) {
        // Formatear NIT con dígito de verificación si está disponible
        if (!empty($result['dv'])) {
            $result['identificacion'] = $result['identificacion'] . '-' . $result['dv'];
        }
        
        // Convertir estado a formato más legible
        if ($result['estado'] === 'inactivo_documentacion') {
            $result['estado'] = 'Inactivo Doc.';
        } else if (!empty($result['estado'])) {
            $result['estado'] = ucfirst($result['estado']);
        }
        
        // Convertir tipo_cliente a formato más legible
        if ($result['tipo_cliente'] === 'arquitectos') {
            $result['tipo_cliente'] = 'Arquitecto';
        } else if ($result['tipo_cliente'] === 'tipo_a') {
            $result['tipo_cliente'] = 'Cliente A';
        } else if ($result['tipo_cliente'] === 'tipo_b') {
            $result['tipo_cliente'] = 'Cliente B';
        } else if ($result['tipo_cliente'] === 'tipo_c') {
            $result['tipo_cliente'] = 'Cliente C';
        }
    }
    
    // Devolver resultados en formato JSON
    header('Content-Type: application/json');
    echo json_encode(['results' => $results]);
    
} catch (PDOException $e) {
    // Manejar errores
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>