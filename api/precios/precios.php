<?php
// Incluir archivo de conexión
require_once '../conexion.php';

// Configurar cabeceras
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Función para limpiar datos de entrada
function limpiarDato($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato);
    return $dato;
}

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Procesar solicitud según método
switch ($method) {
    case 'GET':
        // Obtener precios
        obtenerPrecios();
        break;
    case 'POST':
        // Crear nuevo precio
        crearPrecio();
        break;
    case 'PUT':
        // Actualizar precio existente
        actualizarPrecio();
        break;
    case 'DELETE':
        // Eliminar precio
        eliminarPrecio();
        break;
    default:
        // Método no permitido
        http_response_code(405);
        echo json_encode(['error' => true, 'mensaje' => 'Método no permitido']);
        break;
}

// Función para obtener precios
function obtenerPrecios() {
    global $conexion;
    
    // Verificar si existe la tabla
    $sql_verificar_tabla = "CREATE TABLE IF NOT EXISTS precios_tipologias (
        id INT(11) NOT NULL AUTO_INCREMENT,
        lista_precios VARCHAR(100) NOT NULL,
        articulo VARCHAR(50) NOT NULL,
        precio DECIMAL(12,2) NOT NULL,
        descuento DECIMAL(5,2) DEFAULT 0.00,
        fecha_inicio DATE DEFAULT NULL,
        fecha_fin DATE DEFAULT NULL,
        moneda VARCHAR(10) DEFAULT 'COP',
        estado ENUM('activo', 'inactivo') DEFAULT 'activo',
        created_by INT(11) DEFAULT NULL,
        fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_articulo_lista (articulo, lista_precios)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conexion->query($sql_verificar_tabla);
    
    try {
        // Parámetros de consulta
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        $lista_precios = isset($_GET['lista']) ? limpiarDato($_GET['lista']) : null;
        $articulo = isset($_GET['articulo']) ? limpiarDato($_GET['articulo']) : null;
        $estado = isset($_GET['estado']) ? limpiarDato($_GET['estado']) : null;
        $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 100;
        $pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
        $offset = ($pagina - 1) * $limite;
        
        // Construir consulta según parámetros
        $where_clauses = [];
        $params = [];
        $types = "";
        
        if ($id !== null) {
            $where_clauses[] = "p.id = ?";
            $params[] = $id;
            $types .= "i";
        }
        
        if ($lista_precios !== null) {
            $where_clauses[] = "p.lista_precios = ?";
            $params[] = $lista_precios;
            $types .= "s";
        }
        
        if ($articulo !== null) {
            $where_clauses[] = "p.articulo = ?";
            $params[] = $articulo;
            $types .= "s";
        }
        
        if ($estado !== null) {
            $where_clauses[] = "p.estado = ?";
            $params[] = $estado;
            $types .= "s";
        }
        
        $where_sql = "";
        if (!empty($where_clauses)) {
            $where_sql = "WHERE " . implode(" AND ", $where_clauses);
        }
        
        // Consulta para contar total
        $sql_count = "SELECT COUNT(*) as total FROM precios_tipologias p $where_sql";
        $stmt_count = $conexion->prepare($sql_count);
        
        if (!empty($params)) {
            $stmt_count->bind_param($types, ...$params);
        }
        
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        $row_count = $result_count->fetch_assoc();
        $total = $row_count['total'];
        
        // Consulta para obtener datos
        $sql = "SELECT p.*, t.descripcion as articulo_descripcion 
                FROM precios_tipologias p 
                LEFT JOIN tipologias t ON p.articulo = t.codigo 
                $where_sql 
                ORDER BY p.lista_precios, p.articulo 
                LIMIT ?, ?";
        
        $stmt = $conexion->prepare($sql);
        
        // Añadir parámetros de paginación
        $params[] = $offset;
        $params[] = $limite;
        $types .= "ii";
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $precios = [];
        while ($row = $result->fetch_assoc()) {
            // Formatear fechas y valores numéricos
            $row['precio'] = floatval($row['precio']);
            $row['descuento'] = floatval($row['descuento']);
            $row['fecha_inicio'] = $row['fecha_inicio'] ? $row['fecha_inicio'] : null;
            $row['fecha_fin'] = $row['fecha_fin'] ? $row['fecha_fin'] : null;
            
            $precios[] = $row;
        }
        
        // Preparar respuesta con paginación
        $response = [
            'error' => false,
            'total' => $total,
            'pagina' => $pagina,
            'limite' => $limite,
            'total_paginas' => ceil($total / $limite),
            'precios' => $precios
        ];
        
        http_response_code(200);
        echo json_encode($response);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => true, 'mensaje' => 'Error al obtener precios: ' . $e->getMessage()]);
    }
}

// Función para crear nuevo precio
function crearPrecio() {
    global $conexion;
    
    // Verificar si existe la tabla
    $sql_verificar_tabla = "CREATE TABLE IF NOT EXISTS precios_tipologias (
        id INT(11) NOT NULL AUTO_INCREMENT,
        lista_precios VARCHAR(100) NOT NULL,
        articulo VARCHAR(50) NOT NULL,
        precio DECIMAL(12,2) NOT NULL,
        descuento DECIMAL(5,2) DEFAULT 0.00,
        fecha_inicio DATE DEFAULT NULL,
        fecha_fin DATE DEFAULT NULL,
        moneda VARCHAR(10) DEFAULT 'COP',
        estado ENUM('activo', 'inactivo') DEFAULT 'activo',
        created_by INT(11) DEFAULT NULL,
        fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_articulo_lista (articulo, lista_precios)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conexion->query($sql_verificar_tabla);
    
    try {
        // Obtener datos del cuerpo de la solicitud
        $data = json_decode(file_get_contents("php://input"));
        
        if (!$data || !isset($data->lista_precios) || !isset($data->articulo) || !isset($data->precio)) {
            http_response_code(400);
            echo json_encode(['error' => true, 'mensaje' => 'Datos incompletos. Se requieren lista_precios, articulo y precio']);
            return;
        }
        
        $lista_precios = limpiarDato($data->lista_precios);
        $articulo = limpiarDato($data->articulo);
        $precio = floatval($data->precio);
        $descuento = isset($data->descuento) ? floatval($data->descuento) : 0.00;
        $fecha_inicio = isset($data->fecha_inicio) ? limpiarDato($data->fecha_inicio) : null;
        $fecha_fin = isset($data->fecha_fin) ? limpiarDato($data->fecha_fin) : null;
        $moneda = isset($data->moneda) ? limpiarDato($data->moneda) : 'COP';
        $estado = isset($data->estado) ? limpiarDato($data->estado) : 'activo';
        $created_by = isset($data->created_by) ? intval($data->created_by) : null;
        
        // Verificar si ya existe la combinación articulo-lista
        $sql_verificar = "SELECT id FROM precios_tipologias WHERE articulo = ? AND lista_precios = ?";
        $stmt_verificar = $conexion->prepare($sql_verificar);
        $stmt_verificar->bind_param("ss", $articulo, $lista_precios);
        $stmt_verificar->execute();
        $resultado = $stmt_verificar->get_result();
        
        if ($resultado->num_rows > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => true, 'mensaje' => 'Ya existe un precio para este artículo en esta lista']);
            return;
        }
        
        // Verificar si existe el artículo en tipologías
        $sql_verificar_articulo = "SELECT codigo FROM tipologias WHERE codigo = ?";
        $stmt_verificar_articulo = $conexion->prepare($sql_verificar_articulo);
        $stmt_verificar_articulo->bind_param("s", $articulo);
        $stmt_verificar_articulo->execute();
        $resultado_articulo = $stmt_verificar_articulo->get_result();
        
        if ($resultado_articulo->num_rows == 0) {
            // El artículo no existe, enviar advertencia pero continuar
            $warning = "Advertencia: El artículo '$articulo' no existe en la tabla de tipologías.";
        } else {
            $warning = null;
        }
        
        // Insertar el nuevo precio
        $sql = "INSERT INTO precios_tipologias (
                lista_precios, articulo, precio, descuento, fecha_inicio, 
                fecha_fin, moneda, estado, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssddsssi", $lista_precios, $articulo, $precio, $descuento, 
                        $fecha_inicio, $fecha_fin, $moneda, $estado, $created_by);
        
        if ($stmt->execute()) {
            $id = $conexion->insert_id;
            
            $response = [
                'error' => false,
                'mensaje' => 'Precio creado correctamente',
                'id' => $id,
                'warning' => $warning
            ];
            
            http_response_code(201); // Created
            echo json_encode($response);
        } else {
            http_response_code(500);
            echo json_encode(['error' => true, 'mensaje' => 'Error al crear precio: ' . $stmt->error]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => true, 'mensaje' => 'Error al crear precio: ' . $e->getMessage()]);
    }
}

// Función para actualizar precio existente
function actualizarPrecio() {
    global $conexion;
    
    try {
        // Obtener datos del cuerpo de la solicitud
        $data = json_decode(file_get_contents("php://input"));
        
        if (!$data || !isset($data->id)) {
            http_response_code(400);
            echo json_encode(['error' => true, 'mensaje' => 'ID del precio requerido para actualización']);
            return;
        }
        
        $id = intval($data->id);
        
        // Verificar si el precio existe
        $sql_verificar = "SELECT id FROM precios_tipologias WHERE id = ?";
        $stmt_verificar = $conexion->prepare($sql_verificar);
        $stmt_verificar->bind_param("i", $id);
        $stmt_verificar->execute();
        $resultado = $stmt_verificar->get_result();
        
        if ($resultado->num_rows == 0) {
            http_response_code(404);
            echo json_encode(['error' => true, 'mensaje' => 'Precio no encontrado']);
            return;
        }
        
        // Preparar los campos a actualizar
        $campos = [];
        $valores = [];
        $tipos = "";
        
        // Solo actualizar campos que vienen en la solicitud
        if (isset($data->precio)) {
            $campos[] = "precio = ?";
            $valores[] = floatval($data->precio);
            $tipos .= "d";
        }
        
        if (isset($data->descuento)) {
            $campos[] = "descuento = ?";
            $valores[] = floatval($data->descuento);
            $tipos .= "d";
        }
        
        if (isset($data->fecha_inicio)) {
            $campos[] = "fecha_inicio = ?";
            $valores[] = limpiarDato($data->fecha_inicio);
            $tipos .= "s";
        }
        
        if (isset($data->fecha_fin)) {
            $campos[] = "fecha_fin = ?";
            $valores[] = limpiarDato($data->fecha_fin);
            $tipos .= "s";
        }
        
        if (isset($data->moneda)) {
            $campos[] = "moneda = ?";
            $valores[] = limpiarDato($data->moneda);
            $tipos .= "s";
        }
        
        if (isset($data->estado)) {
            $campos[] = "estado = ?";
            $valores[] = limpiarDato($data->estado);
            $tipos .= "s";
        }
        
        // Verificar si hay campos para actualizar
        if (empty($campos)) {
            http_response_code(400);
            echo json_encode(['error' => true, 'mensaje' => 'No se proporcionaron campos para actualizar']);
            return;
        }
        
        // Actualizar el precio
        $sql = "UPDATE precios_tipologias SET " . implode(", ", $campos) . ", fecha_actualizacion = NOW() WHERE id = ?";
        
        // Añadir el ID al final de los parámetros
        $valores[] = $id;
        $tipos .= "i";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($tipos, ...$valores);
        
        if ($stmt->execute()) {
            $response = [
                'error' => false,
                'mensaje' => 'Precio actualizado correctamente',
                'id' => $id
            ];
            
            http_response_code(200);
            echo json_encode($response);
        } else {
            http_response_code(500);
            echo json_encode(['error' => true, 'mensaje' => 'Error al actualizar precio: ' . $stmt->error]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => true, 'mensaje' => 'Error al actualizar precio: ' . $e->getMessage()]);
    }
}

// Función para eliminar precio
function eliminarPrecio() {
    global $conexion;
    
    try {
        // Obtener ID de la solicitud
        $data = json_decode(file_get_contents("php://input"));
        
        if (!$data || !isset($data->id)) {
            http_response_code(400);
            echo json_encode(['error' => true, 'mensaje' => 'ID del precio requerido para eliminación']);
            return;
        }
        
        $id = intval($data->id);
        
        // Verificar si el precio existe
        $sql_verificar = "SELECT id FROM precios_tipologias WHERE id = ?";
        $stmt_verificar = $conexion->prepare($sql_verificar);
        $stmt_verificar->bind_param("i", $id);
        $stmt_verificar->execute();
        $resultado = $stmt_verificar->get_result();
        
        if ($resultado->num_rows == 0) {
            http_response_code(404);
            echo json_encode(['error' => true, 'mensaje' => 'Precio no encontrado']);
            return;
        }
        
        // Eliminar el precio
        $sql = "DELETE FROM precios_tipologias WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $response = [
                'error' => false,
                'mensaje' => 'Precio eliminado correctamente',
                'id' => $id
            ];
            
            http_response_code(200);
            echo json_encode($response);
        } else {
            http_response_code(500);
            echo json_encode(['error' => true, 'mensaje' => 'Error al eliminar precio: ' . $stmt->error]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => true, 'mensaje' => 'Error al eliminar precio: ' . $e->getMessage()]);
    }
}

// Función para crear una respuesta de error
function responderError($codigo, $mensaje) {
    http_response_code($codigo);
    echo json_encode(['error' => true, 'mensaje' => $mensaje]);
    exit;
}

// Función para registrar actividad
function registrarActividad($usuario_id, $accion, $detalle) {
    global $conexion;
    
    try {
        // Verificar si existe la tabla de logs
        $sql_verificar_tabla = "CREATE TABLE IF NOT EXISTS logs (
            id INT(11) NOT NULL AUTO_INCREMENT,
            usuario_id INT(11),
            accion VARCHAR(100) NOT NULL,
            detalle TEXT,
            ip VARCHAR(45),
            fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $conexion->query($sql_verificar_tabla);
        
        // Obtener IP
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Insertar log
        $sql = "INSERT INTO logs (usuario_id, accion, detalle, ip) VALUES (?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("isss", $usuario_id, $accion, $detalle, $ip);
        $stmt->execute();
        
    } catch (Exception $e) {
        // Ignorar errores al registrar actividad
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}