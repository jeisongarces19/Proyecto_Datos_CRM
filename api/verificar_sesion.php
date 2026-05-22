<?php
// Iniciar sesión
session_start();

// Comprobar si el usuario está logueado
$logueado = isset($_SESSION['usuario_id']);

// Responder con el estado de la sesión
header('Content-Type: application/json');
echo json_encode([
    'logueado' => $logueado,
    'tiempo_restante' => isset($_SESSION['LAST_ACTIVITY']) ? 
        (ini_get('session.gc_maxlifetime') - (time() - $_SESSION['LAST_ACTIVITY'])) : 0
]);

// Actualizar tiempo de actividad
if ($logueado) {
    $_SESSION['LAST_ACTIVITY'] = time();
}
?>