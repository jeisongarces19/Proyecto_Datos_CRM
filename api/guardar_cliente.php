<?php
// Iniciar sesión
session_start();

// Configuración para mostrar errores como JSON en lugar de HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Manejador de errores personalizado para capturar errores PHP
function handleError($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Error PHP: $errstr en $errfile línea $errline",
        'error_code' => 'PHP_ERROR'
    ]);
    exit;
}
set_error_handler('handleError');

// Incluir archivo de conexión
require_once '../conexion.php';

// Verificar si se recibieron datos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido',
        'error_code' => 'METHOD_NOT_ALLOWED'
    ]);
    exit;
}

// Procesar los datos del formulario
try {
    // Crear conexión a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Depuración de datos recibidos
    error_log("Datos POST recibidos:");
    foreach ($_POST as $key => $value) {
        error_log("$key: " . print_r($value, true));
    }
    
    // Obtener datos del formulario
    $nombre = isset($_POST['nombre_cliente']) ? trim($_POST['nombre_cliente']) : null;
    $tipoIdentificacion = isset($_POST['tipo_identificacion']) ? $_POST['tipo_identificacion'] : null;
    $identificacion = null;
    $digitoVerificacion = null;
    
    // Validar datos obligatorios
    if (empty($nombre)) {
        throw new Exception('El nombre del cliente es obligatorio');
    }
    
    if (empty($tipoIdentificacion)) {
        throw new Exception('El tipo de identificación es obligatorio');
    }
    
    // Procesar según tipo de identificación
    if ($tipoIdentificacion === 'juridica') {
        $identificacion = isset($_POST['nit']) ? trim($_POST['nit']) : null;
        $digitoVerificacion = isset($_POST['digito_verificacion']) ? trim($_POST['digito_verificacion']) : null;
        
        if (empty($identificacion) || strlen($identificacion) !== 9) {
            throw new Exception('El NIT debe tener 9 dígitos');
        }
        
        if (empty($digitoVerificacion) || strlen($digitoVerificacion) !== 1) {
            throw new Exception('El dígito de verificación es obligatorio');
        }
    } else if ($tipoIdentificacion === 'natural') {
        $identificacion = isset($_POST['documento']) ? trim($_POST['documento']) : null;
        $digitoVerificacion = null; // No se usa para persona natural
        
        if (empty($identificacion)) {
            throw new Exception('El número de documento es obligatorio');
        }
    } else {
        throw new Exception('Tipo de identificación no válido');
    }
    
    // Obtener otros datos del formulario
    $estadoCliente = isset($_POST['estado_cliente']) ? $_POST['estado_cliente'] : 'activo';
    $tipoCliente = isset($_POST['tipo_cliente']) ? $_POST['tipo_cliente'] : null;
    $sector = isset($_POST['sector']) ? $_POST['sector'] : null;
    
    // Datos de ubicación
    $paisId = isset($_POST['pais']) ? trim($_POST['pais']) : null;
    $paisNombre = isset($_POST['pais_nombre']) ? trim($_POST['pais_nombre']) : null;
    
    $departamentoId = isset($_POST['departamento']) ? trim($_POST['departamento']) : null;
    $departamentoNombre = isset($_POST['departamento_nombre']) ? trim($_POST['departamento_nombre']) : null;
    
    $ciudadId = isset($_POST['ciudad']) ? trim($_POST['ciudad']) : null;
    $ciudadNombre = isset($_POST['ciudad_nombre']) ? trim($_POST['ciudad_nombre']) : null;
    
    // Cambiar asesor_id por Vendedor
    $asesorAsignado = isset($_POST['asesor_asignado']) ? trim($_POST['asesor_asignado']) : null;
    
    // Obtener el nombre del asesor
    $vendedor = null;
    if ($asesorAsignado) {
        try {
            // Intentar obtener el nombre del asesor
            $stmtAsesor = $pdo->prepare("SELECT nombre FROM asesores WHERE id = :id");
            $stmtAsesor->bindValue(':id', $asesorAsignado, PDO::PARAM_INT);
            $stmtAsesor->execute();
            $vendedor = $stmtAsesor->fetchColumn();
            
            // Si no se encuentra nombre, usar el ID
            if (!$vendedor) {
                $vendedor = $asesorAsignado;
            }
        } catch(PDOException $e) {
            // Si falla la consulta, usar el ID original
            $vendedor = $asesorAsignado;
            error_log("Error al obtener nombre de asesor: " . $e->getMessage());
        }
    }
    
    // Otros campos del formulario
    $tamanoEmpresa = isset($_POST['tamano_empresa']) ? $_POST['tamano_empresa'] : null;
    $numeroEmpleados = isset($_POST['numero_empleados']) ? $_POST['numero_empleados'] : null;
    $direccionPrincipal = isset($_POST['direccion_principal_completa']) ? $_POST['direccion_principal_completa'] : null;
    $mismaDireccion = isset($_POST['misma_direccion']) ? true : false;
    $direccionFacturacion = $mismaDireccion ? $direccionPrincipal : (isset($_POST['direccion_facturacion_completa']) ? $_POST['direccion_facturacion_completa'] : null);
    $paginaWeb = isset($_POST['pagina_web']) ? $_POST['pagina_web'] : null;
    $origenCliente = isset($_POST['origen_cliente']) ? $_POST['origen_cliente'] : null;
    $clienteERP = isset($_POST['cliente_erp']) ? $_POST['cliente_erp'] : 'no';
    $comoSeEntero = isset($_POST['como_se_entero']) ? $_POST['como_se_entero'] : null;
    
    // Validar campos obligatorios adicionales
    if (empty($tipoCliente)) {
        throw new Exception('El tipo de cliente es obligatorio');
    }
    
    if (empty($direccionPrincipal)) {
        throw new Exception('La dirección principal es obligatoria');
    }
    
    // Determinar estados según el estadoCliente seleccionado
    $senalCreditoSuspendido = $estadoCliente === 'inactivo' || $estadoCliente === 'inactivo_documentacion' ? 1 : 0;
    $senalArchivoNegro = $estadoCliente === 'inactivo' ? 1 : 0;
    $controlCredito = $estadoCliente === 'activo' ? 1 : 0;
    
    // Obtener datos del primer contacto para usarlo como contacto principal
    $telefonoPrimario = '';
    $mailPrimario = '';
    $contactoPrincipal = '';
    
    if (isset($_POST['contactos']) && is_array($_POST['contactos']) && count($_POST['contactos']) > 0) {
        $primerContacto = reset($_POST['contactos']);
        if (isset($primerContacto['telefono'])) {
            $telefonoPrimario = $primerContacto['telefono'];
        }
        if (isset($primerContacto['email'])) {
            $mailPrimario = $primerContacto['email'];
        }
        if (isset($primerContacto['nombre'])) {
            $contactoPrincipal = $primerContacto['nombre'];
        }
    }
    
    // Iniciar transacción para asegurar integridad de datos
    $pdo->beginTransaction();
    
    // Preparar la consulta SQL para insertar el cliente
    $sqlCliente = "INSERT INTO clientes1 (
        Nombre, 
        Numero_De_Organizacion, 
        Digito_Verificacion, 
        Tipo_Identificacion, 
        Tipo_Cliente, 
        Pais,
        Departamento,
        Ciudad,
        Dirección, 
        Direccion_Facturacion, 
        Mail_Primario, 
        Telefono_Primario, 
        Contacto, 
        Vendedor,
        Senal_Archivo_Negro, 
        Señal_Credito_Suspendido, 
        Control_Credito, 
        Estado,
        Sector,
        Tamano_Empresa,
        Numero_Empleados,
        Pagina_Web,
        Origen_Cliente,
        Cliente_ERP,
        Como_Se_Entero,
        Fecha_Creacion_Cliente,
        Formato_Creacion_Actualizacion
    ) VALUES (
        :nombre, 
        :numero_organizacion, 
        :digito_verificacion, 
        :tipo_identificacion, 
        :tipo_cliente, 
        :pais,
        :departamento,
        :ciudad,
        :direccion, 
        :direccion_facturacion, 
        :mail_primario, 
        :telefono_primario, 
        :contacto, 
        :vendedor,
        :senal_archivo_negro, 
        :senal_credito_suspendido, 
        :control_credito, 
        :estado,
        :sector,
        :tamano_empresa,
        :numero_empleados,
        :pagina_web,
        :origen_cliente,
        :cliente_erp,
        :como_se_entero,
        NOW(),
        NOW()
    )";
    
    // Preparar la consulta
    $stmtCliente = $pdo->prepare($sqlCliente);
    
    // Enlazar parámetros
    $stmtCliente->bindValue(':nombre', $nombre);
    $stmtCliente->bindValue(':numero_organizacion', $identificacion);
    $stmtCliente->bindValue(':digito_verificacion', $digitoVerificacion);
    $stmtCliente->bindValue(':tipo_identificacion', $tipoIdentificacion);
    $stmtCliente->bindValue(':tipo_cliente', $tipoCliente);
    $stmtCliente->bindValue(':pais', $paisNombre);
    $stmtCliente->bindValue(':departamento', $departamentoNombre);
    $stmtCliente->bindValue(':ciudad', $ciudadNombre);
    $stmtCliente->bindValue(':direccion', $direccionPrincipal);
    $stmtCliente->bindValue(':direccion_facturacion', $direccionFacturacion);
    $stmtCliente->bindValue(':mail_primario', $mailPrimario);
    $stmtCliente->bindValue(':telefono_primario', $telefonoPrimario);
    $stmtCliente->bindValue(':contacto', $contactoPrincipal);
    $stmtCliente->bindValue(':vendedor', $vendedor);
    $stmtCliente->bindValue(':senal_archivo_negro', $senalArchivoNegro, PDO::PARAM_INT);
    $stmtCliente->bindValue(':senal_credito_suspendido', $senalCreditoSuspendido, PDO::PARAM_INT);
    $stmtCliente->bindValue(':control_credito', $controlCredito, PDO::PARAM_INT);
    $stmtCliente->bindValue(':estado', $estadoCliente);
    $stmtCliente->bindValue(':sector', $sector);
    $stmtCliente->bindValue(':tamano_empresa', $tamanoEmpresa);
    $stmtCliente->bindValue(':numero_empleados', $numeroEmpleados, PDO::PARAM_INT);
    $stmtCliente->bindValue(':pagina_web', $paginaWeb);
    $stmtCliente->bindValue(':origen_cliente', $origenCliente);
    $stmtCliente->bindValue(':cliente_erp', $clienteERP);
    $stmtCliente->bindValue(':como_se_entero', $comoSeEntero);
    
    // Ejecutar consulta
    $stmtCliente->execute();
    
    // Obtener el ID del cliente insertado
    $idCliente = $pdo->lastInsertId();
    
    // Insertar contactos
    $contactosInsertados = 0;
    if (isset($_POST['contactos']) && is_array($_POST['contactos']) && count($_POST['contactos']) > 0) {
        $sqlContacto = "INSERT INTO contactos_cliente (
            Cliente_ID,
            Nombre,
            Cargo,
            Email,
            Telefono,
            Departamento
        ) VALUES (
            :cliente_id,
            :nombre,
            :cargo,
            :email,
            :telefono,
            :departamento
        )";
        
        $stmtContacto = $pdo->prepare($sqlContacto);
        
        foreach ($_POST['contactos'] as $contacto) {
            // Validar datos mínimos del contacto
            if (empty($contacto['nombre']) || empty($contacto['email'])) {
                continue; // Saltamos este contacto si no tiene datos mínimos
            }
            
            try {
                $stmtContacto->bindValue(':cliente_id', $idCliente, PDO::PARAM_INT);
                $stmtContacto->bindValue(':nombre', $contacto['nombre']);
                $stmtContacto->bindValue(':cargo', $contacto['cargo']);
                $stmtContacto->bindValue(':email', $contacto['email']);
                $stmtContacto->bindValue(':telefono', $contacto['telefono']);
                $stmtContacto->bindValue(':departamento', $contacto['departamento']);
                
                $stmtContacto->execute();
                $contactosInsertados++;
                
            } catch(PDOException $e) {
                // Registrar el error pero continuar con los demás contactos
                error_log("Error al insertar contacto: " . $e->getMessage());
            }
        }
    }
    
    // Si todo ha ido bien, confirmar la transacción
    $pdo->commit();
    
    // Responder con éxito
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Cliente guardado correctamente',
        'client_id' => $idCliente,
        'contactos_insertados' => $contactosInsertados
    ]);

} catch(PDOException $e) {
    // Revertir la transacción en caso de error
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    // Registro detallado del error
    error_log("Error de base de datos al guardar cliente: " . $e->getMessage());
    error_log("Código de error SQL: " . $e->getCode());
    error_log("Detalles del error: " . print_r($e->errorInfo, true));
    
    // Responder con el error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar el cliente: ' . $e->getMessage(),
        'error_code' => 'DB_ERROR',
        'sql_error_code' => $e->getCode(),
        'error_details' => $e->errorInfo
    ]);
    
} catch(Exception $e) {
    // Manejar errores de validación u otros errores
    error_log("Error al procesar cliente: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'VALIDATION_ERROR'
    ]);
    
} finally {
    // Asegurar que la conexión a la base de datos se cierre
    $pdo = null;
}
?>