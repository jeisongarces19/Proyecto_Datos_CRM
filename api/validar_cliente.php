<?php
// Validar sesión y permisos
require_once 'validar_seccion.php';
require_once 'conexion.php';

// Función para enviar correo de notificación
function enviarNotificacionCliente($clienteData) {
    $to = "fernando.maldonado@carvajal.com,Sindy.Moreno@carvajal.com,Catherine.Cardona@carvajal.com";
    $subject = "Nuevo Cliente Creado en CRM - " . $clienteData['nombre'];
    
    $message = "
    <html>
    <head>
        <title>Nuevo Cliente Creado</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
            .content { padding: 20px; }
            .info-row { margin-bottom: 15px; padding: 10px; background-color: #f8fafc; border-radius: 5px; }
            .label { font-weight: bold; color: #4f46e5; display: inline-block; width: 150px; }
            .value { color: #333; }
            .footer { text-align: center; margin-top: 30px; padding: 20px; background-color: #f1f5f9; border-radius: 8px; color: #64748b; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Nuevo Cliente Registrado</h1>
                <p>Se ha creado un nuevo cliente en el sistema CRM</p>
            </div>
            
            <div class='content'>
                <h2>Información del Cliente</h2>
                
                <div class='info-row'>
                    <span class='label'>Nombre:</span>
                    <span class='value'>{$clienteData['nombre']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Tipo:</span>
                    <span class='value'>{$clienteData['tipo_identificacion']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Documento/NIT:</span>
                    <span class='value'>{$clienteData['documento']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Tipo Cliente:</span>
                    <span class='value'>{$clienteData['tipo_cliente']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Sector:</span>
                    <span class='value'>{$clienteData['sector']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Ubicación:</span>
                    <span class='value'>{$clienteData['ciudad']}, {$clienteData['departamento']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Dirección:</span>
                    <span class='value'>{$clienteData['direccion']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Asesor Asignado:</span>
                    <span class='value'>{$clienteData['asesor']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Estado:</span>
                    <span class='value'>{$clienteData['estado']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Fecha Vencimiento:</span>
                    <span class='value'>{$clienteData['fecha_vencimiento']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Contacto Principal:</span>
                    <span class='value'>{$clienteData['contacto_principal']} - {$clienteData['email_principal']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Teléfono:</span>
                    <span class='value'>{$clienteData['telefono_principal']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Origen:</span>
                    <span class='value'>{$clienteData['origen_cliente']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Cliente ERP:</span>
                    <span class='value'>{$clienteData['cliente_erp']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Creado por:</span>
                    <span class='value'>{$clienteData['usuario_creador']}</span>
                </div>
                
                <div class='info-row'>
                    <span class='label'>Fecha de Creación:</span>
                    <span class='value'>" . date('d/m/Y H:i:s') . "</span>
                </div>
            </div>
            
            <div class='footer'>
                <p>Este correo fue enviado automáticamente por el sistema CRM MEPAL</p>
                <p>No responder a este correo</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: CRM MEPAL <crm@mepal.com.co>" . "\r\n";
    $headers .= "Reply-To: crm@mepal.com.co" . "\r\n";

    return mail($to, $subject, $message, $headers);
}

// Procesar el formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_client') {
    header('Content-Type: application/json');
    
    try {
        // Validar campos requeridos básicos
        $required_fields = [
            'nombre_cliente', 
            'tipo_identificacion', 
            'estado_cliente', 
            'tipo_cliente', 
            'sector', 
            'departamento', 
            'ciudad', 
            'origen_cliente', 
            'cliente_erp', 
            'asesor_asignado', 
            'fecha_vencimiento_cliente',
            'contacto_nombre',
            'contacto_email'
        ];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("El campo $field es requerido");
            }
        }

        // Preparar los datos para insertar
        $nombre_cliente = strtoupper(trim($_POST['nombre_cliente']));
        $tipo_identificacion = $_POST['tipo_identificacion'];
        $numero_organizacion = '';
        $digito_verificacion = null;
        
        // Determinar el número de organización según el tipo
        if ($tipo_identificacion === 'juridica') {
            if (empty($_POST['nit'])) {
                throw new Exception("NIT es requerido para persona jurídica");
            }
            $numero_organizacion = trim($_POST['nit']);
        } else {
            if (empty($_POST['documento'])) {
                throw new Exception("Documento es requerido para persona natural");
            }
            $numero_organizacion = trim($_POST['documento']);
        }

        // Datos básicos
        $tipo_cliente = $_POST['tipo_cliente'];
        $departamento = $_POST['departamento_texto'] ?? '';
        $id_departamento = $_POST['departamento'] ?? null;
        $ciudad = $_POST['ciudad_texto'] ?? '';
        $id_ciudad = $_POST['ciudad'] ?? null;
        
        // Dirección simplificada
        $direccion_completa = trim($_POST['direccion_completa'] ?? '');

        // Contacto principal
        $contacto_principal = strtoupper(trim($_POST['contacto_nombre']));
        $contacto_cargo = strtoupper(trim($_POST['contacto_cargo'] ?? ''));
        $email_principal = trim($_POST['contacto_email']);
        $telefono_principal = trim($_POST['contacto_telefono'] ?? '');
        $contacto_departamento = $_POST['contacto_departamento'] ?? '';

        // Otros campos
        $vendedor = $_POST['asesor_asignado'];
        $sector = $_POST['sector'];
        $origen_cliente = $_POST['origen_cliente'];
        $cliente_erp = $_POST['cliente_erp'];
        $estado = $_POST['estado_cliente'];
        $fecha_vencimiento = $_POST['fecha_vencimiento_cliente'];

        // Fechas
        $fecha_creacion = date('Y-m-d');
        $fecha_actualizacion = date('Y-m-d');

        // Obtener nombre del usuario actual para el correo
        $usuario_creador = $_SESSION['nombre'] ?? 'Sistema';

        // Verificar duplicados usando la misma lógica que la API
        $check_sql = "SELECT Numero_Cliente, Nombre FROM clientes1 WHERE ";
        $check_params = [];

        if ($tipo_identificacion === 'juridica') {
            $check_sql .= "Numero_De_Organizacion = ? AND Tipo_Identificacion = 'juridica'";
            $check_params = [$numero_organizacion];
        } else {
            $check_sql .= "Numero_De_Organizacion = ? AND Tipo_Identificacion = 'natural'";
            $check_params = [$numero_organizacion];
        }

        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute($check_params);
        
        if ($check_stmt->rowCount() > 0) {
            throw new Exception("Ya existe un cliente con este documento/NIT");
        }

        // Verificar duplicado de nombre
        $name_check = $pdo->prepare("SELECT Numero_Cliente FROM clientes1 WHERE UPPER(TRIM(Nombre)) = UPPER(TRIM(?))");
        $name_check->execute([$nombre_cliente]);
        
        if ($name_check->rowCount() > 0) {
            throw new Exception("Ya existe un cliente con este nombre");
        }

        // Insertar cliente usando las columnas exactas de la tabla
        $sql = "INSERT INTO clientes1 (
            Nombre, Numero_De_Organizacion, Digito_Verificacion, Tipo_Identificacion,
            Tipo_Cliente, Pais, id_pais, Departamento, id_departamento, Ciudad, id_ciudad,
            Dirección, Direccion_Facturacion, Mail_Primario, Telefono_Primario, Contacto,
            Vendedor, Fecha_Creacion_Cliente, Formato_Creacion_Actualizacion,
            Señal_Credito_Suspendido, Senal_Archivo_Negro, Control_Credito, Cupo,
            Sector, Tamano_Empresa, Numero_Empleados, Pagina_Web, Origen_Cliente,
            Cliente_ERP, Asesor_ID, Como_Se_Entero, Estado, Fecha_Vencimiento_Cliente
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 1, 0.00,
            ?, NULL, NULL, NULL, ?, ?, ?, NULL, ?, ?
        )";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $nombre_cliente,           // Nombre
            $numero_organizacion,      // Numero_De_Organizacion
            $digito_verificacion,      // Digito_Verificacion
            $tipo_identificacion,      // Tipo_Identificacion
            $tipo_cliente,            // Tipo_Cliente
            'Colombia',               // Pais (fijo)
            170,                      // id_pais (Colombia fijo)
            $departamento,            // Departamento
            $id_departamento,         // id_departamento
            $ciudad,                  // Ciudad
            $id_ciudad,              // id_ciudad
            $direccion_completa,      // Dirección
            $direccion_completa,      // Direccion_Facturacion (misma)
            $email_principal,         // Mail_Primario
            $telefono_principal,      // Telefono_Primario
            $contacto_principal,      // Contacto
            $vendedor,               // Vendedor
            $fecha_creacion,         // Fecha_Creacion_Cliente
            $fecha_actualizacion,    // Formato_Creacion_Actualizacion
            $sector,                 // Sector
            $origen_cliente,         // Origen_Cliente
            $cliente_erp,            // Cliente_ERP
            $vendedor,               // Asesor_ID (mismo que vendedor)
            $estado,                 // Estado
            $fecha_vencimiento       // Fecha_Vencimiento_Cliente
        ]);

        if ($result) {
            $cliente_id = $pdo->lastInsertId();
            
            // Preparar datos para el correo
            $clienteDataEmail = [
                'nombre' => $nombre_cliente,
                'tipo_identificacion' => $tipo_identificacion === 'juridica' ? 'Persona Jurídica' : 'Persona Natural',
                'documento' => $numero_organizacion,
                'tipo_cliente' => $tipo_cliente,
                'sector' => $sector,
                'ciudad' => $ciudad,
                'departamento' => $departamento,
                'direccion' => $direccion_completa ?: 'No especificada',
                'asesor' => $vendedor,
                'estado' => $estado,
                'fecha_vencimiento' => date('d/m/Y', strtotime($fecha_vencimiento)),
                'contacto_principal' => $contacto_principal,
                'email_principal' => $email_principal,
                'telefono_principal' => $telefono_principal ?: 'No especificado',
                'origen_cliente' => $origen_cliente,
                'cliente_erp' => $cliente_erp === 'si' ? 'Sí' : 'No',
                'usuario_creador' => $usuario_creador
            ];
            
            // Enviar correo de notificación
            $emailSent = enviarNotificacionCliente($clienteDataEmail);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cliente creado exitosamente' . ($emailSent ? ' y notificación enviada' : ' (error enviando notificación)'),
                'cliente_id' => $cliente_id,
                'email_sent' => $emailSent
            ]);
        } else {
            throw new Exception('Error al insertar el cliente en la base de datos');
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Cliente - CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .crear-cliente-wrapper {
            --primary-color: #4f46e5;
            --primary-light: #6366f1;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --background-light: #f8fafc;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --radius-md: 0.5rem;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: calc(100vh - 64px);
            padding-top: 15px;
        }
        .crear-cliente-container { max-width: 900px; margin: 0 auto; padding: 0 0.75rem; }
        .crear-cliente-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 0.75rem;
            box-shadow: var(--shadow-md); position: relative; overflow: hidden;
        }
        .crear-cliente-header::before {
            content: ''; position: absolute; top: -50%; right: -50%; width: 100%; height: 200%;
            background: rgba(255, 255, 255, 0.1); border-radius: 50%; transform: rotate(45deg);
        }
        .crear-cliente-header-content { position: relative; z-index: 2; }
        .crear-cliente-header h1 {
            font-size: 1.1rem; font-weight: 700; margin: 0 0 0.2rem 0; display: flex; align-items: center; gap: 0.4rem;
        }
        .crear-cliente-header h1 i {
            background: rgba(255, 255, 255, 0.2); padding: 0.25rem; border-radius: var(--radius-md); font-size: 0.9rem;
        }
        .crear-cliente-header p { font-size: 0.75rem; opacity: 0.9; margin: 0; }
        .crear-cliente-form-card {
            background: white; border-radius: var(--radius-md); box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color); overflow: hidden;
        }
        .crear-cliente-tabs-container { background: #f1f5f9; border-bottom: 1px solid var(--border-color); padding: 0.2rem; }
        .crear-cliente-nav-tabs {
            border: none; display: flex; gap: 0.1rem; margin: 0; padding: 0; list-style: none;
        }
        .crear-cliente-nav-tabs .nav-item { flex: 1; }
        .crear-cliente-nav-tabs .nav-link {
            border: none; color: var(--text-secondary); font-weight: 500; padding: 0.3rem 0.4rem;
            border-radius: var(--radius-md); background: transparent; font-size: 0.65rem; text-align: center;
            margin: 0; display: flex; flex-direction: column; justify-content: center; align-items: center;
            gap: 0.1rem; transition: all 0.3s ease; text-decoration: none; cursor: pointer; min-height: 38px;
        }
        .crear-cliente-nav-tabs .nav-link i { font-size: 0.75rem; opacity: 0.7; }
        .crear-cliente-nav-tabs .nav-link.active {
            background: white; color: var(--primary-color); box-shadow: var(--shadow-sm);
        }
        .crear-cliente-nav-tabs .nav-link.active i { opacity: 1; }
        .crear-cliente-nav-tabs .nav-link:hover:not(.active) {
            background: rgba(79, 70, 229, 0.05); color: var(--primary-color);
        }
        .crear-cliente-tab-content { padding: 0.8rem; }
        .crear-cliente-section-title {
            font-size: 0.85rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.6rem;
            padding-bottom: 0.3rem; border-bottom: 2px solid #f1f5f9; display: flex; align-items: center; gap: 0.3rem;
        }
        .crear-cliente-section-title i { color: var(--primary-color); font-size: 0.9rem; }
        .crear-cliente-form-label {
            font-weight: 500; color: var(--text-secondary); margin-bottom: 0.25rem; font-size: 0.7rem;
        }
        .crear-cliente-form-control, .crear-cliente-form-select {
            border: 2px solid var(--border-color); border-radius: var(--radius-md); padding: 0.4rem 0.6rem;
            font-size: 0.7rem; transition: all 0.3s ease; background: white; height: 32px;
        }
        .crear-cliente-form-control:focus, .crear-cliente-form-select:focus {
            border-color: var(--primary-color); box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1); outline: none;
        }
        .crear-cliente-form-control.is-valid { border-color: var(--success-color); }
        .crear-cliente-form-control.is-invalid { border-color: var(--error-color); }
        #nombre_cliente {
            font-size: 0.75rem; padding: 0.5rem; font-weight: 500; background: #f8fafc; height: 36px;
        }
        .crear-cliente-btn {
            padding: 0.5rem 1rem; font-size: 0.75rem; font-weight: 500; border-radius: var(--radius-md);
            border: none; transition: all 0.3s ease; display: inline-flex; align-items: center;
            gap: 0.3rem; cursor: pointer; text-decoration: none;
        }
        .crear-cliente-btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white; box-shadow: var(--shadow-sm);
        }
        .crear-cliente-btn-primary:hover {
            transform: translateY(-1px); box-shadow: var(--shadow-md); color: white;
        }
        .crear-cliente-btn-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            color: white; box-shadow: var(--shadow-sm);
        }
        .crear-cliente-btn-success:hover {
            transform: translateY(-1px); box-shadow: var(--shadow-md); color: white;
        }
        .crear-cliente-btn-secondary { background: #64748b; color: white; }
        .crear-cliente-btn-secondary:hover { background: #475569; transform: translateY(-1px); color: white; }
        .crear-cliente-btn-outline-secondary {
            border: 2px solid var(--border-color); color: var(--text-secondary); background: transparent;
        }
        .crear-cliente-btn-outline-secondary:hover {
            background: #f1f5f9; border-color: #64748b; color: #64748b;
        }
        .crear-cliente-radio-card {
            border: 2px solid var(--border-color); border-radius: var(--radius-md); padding: 0.5rem;
            cursor: pointer; text-align: center; background: white; transition: all 0.3s ease;
            height: 65px; display: flex; flex-direction: column; justify-content: center;
            align-items: center; gap: 0.15rem;
        }
        .crear-cliente-radio-card:hover {
            border-color: var(--primary-color); transform: translateY(-1px); box-shadow: var(--shadow-md);
        }
        .crear-cliente-radio-card.selected {
            border-color: var(--primary-color); background: rgba(79, 70, 229, 0.05); color: var(--primary-color);
        }
        .crear-cliente-radio-card input[type="radio"] { display: none; }
        .crear-cliente-radio-card i { font-size: 1rem; margin-bottom: 0.1rem; opacity: 0.7; }
        .crear-cliente-radio-card.selected i, .crear-cliente-radio-card:hover i { opacity: 1; }
        .crear-cliente-radio-card .fw-bold {
            font-size: 0.7rem; margin: 0; font-weight: 600; line-height: 1;
        }
        .crear-cliente-radio-card small { font-size: 0.6rem; opacity: 0.7; line-height: 1; }
        .crear-cliente-identification-section {
            background: var(--background-light); border: 2px solid var(--border-color);
            border-radius: var(--radius-md); padding: 0.6rem; margin-top: 0.4rem; display: none;
        }
        .crear-cliente-identification-section.active { display: block; }
        .crear-cliente-identification-section h6 {
            margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 600; color: var(--text-primary);
            display: flex; align-items: center; gap: 0.25rem;
        }
        .crear-cliente-identification-section h6 i { color: var(--primary-color); font-size: 0.8rem; }
        .crear-cliente-contact-card {
            background: white; border: 2px solid var(--border-color); border-radius: var(--radius-md);
            margin-bottom: 0.6rem; overflow: hidden;
        }
        .crear-cliente-contact-body { padding: 0.8rem; }
        .crear-cliente-validation-message {
            font-size: 0.65rem; margin-top: 0.3rem; padding: 0.3rem 0.5rem;
            border-radius: var(--radius-md); display: none; font-weight: 500;
        }
        .crear-cliente-validation-message.success {
            color: #065f46; background: #d1fae5; border: 1px solid #a7f3d0;
        }
        .crear-cliente-validation-message.error {
            color: #991b1b; background: #fee2e2; border: 1px solid #fecaca;
        }
        .crear-cliente-validation-message.warning {
            color: #92400e; background: #fef3c7; border: 1px solid #fde68a;
        }
        .crear-cliente-form-footer {
            background: var(--background-light); border-top: 1px solid var(--border-color); padding: 0.8rem 1.2rem;
        }
        .crear-cliente-uppercase-input { text-transform: uppercase; }
        .crear-cliente-required { color: var(--error-color); font-weight: 600; }
        .crear-cliente-mb-3 { margin-bottom: 0.5rem !important; }
        .crear-cliente-mt-3 { margin-top: 0.6rem !important; }
        .fecha-vencimiento-highlight {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #f59e0b;
        }
        .fecha-vencimiento-highlight:focus {
            border-color: #d97706; box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.1);
        }
        .add-option-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;
            border: none; border-radius: 50%; width: 28px; height: 28px; display: inline-flex;
            align-items: center; justify-content: center; font-size: 0.7rem; cursor: pointer;
            transition: all 0.3s ease; margin-left: 0.5rem;
        }
        .add-option-btn:hover { transform: scale(1.1); box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3); }
        .validation-spinner {
            display: inline-block; width: 12px; height: 12px; border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color); border-radius: 50%;
            animation: spin 1s linear infinite; margin-right: 5px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); }
        }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        @media (max-width: 768px) {
            .crear-cliente-wrapper { padding-top: 8px; }
            .crear-cliente-container { padding: 0 0.4rem; }
            .crear-cliente-header { padding: 0.8rem; margin-bottom: 0.8rem; }
            .crear-cliente-header h1 { font-size: 1.2rem; }
            .crear-cliente-tab-content { padding: 0.8rem; }
            .crear-cliente-nav-tabs .nav-link { padding: 0.4rem; font-size: 0.65rem; min-height: 42px; }
            .crear-cliente-form-footer { padding: 0.6rem; }
        }
        @media (max-width: 576px) {
            .crear-cliente-nav-tabs { flex-wrap: wrap; }
            .crear-cliente-nav-tabs .nav-item { flex: 0 0 50%; }
            .crear-cliente-radio-card { height: 70px; padding: 0.6rem; }
            .crear-cliente-radio-card i { font-size: 1.1rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="crear-cliente-wrapper">
        <div class="crear-cliente-container">
            <div class="crear-cliente-header">
                <div class="crear-cliente-header-content">
                    <h1>
                        <i class="fas fa-user-plus"></i>
                        Crear Nuevo Cliente
                    </h1>
                    <p>Complete la información del cliente para registrarlo en el sistema CRM</p>
                </div>
            </div>

            <div class="crear-cliente-form-card">
                <div class="crear-cliente-tabs-container">
                    <ul class="crear-cliente-nav-tabs" id="clienteTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="basica-tab" data-tab="basica" role="tab">
                                <i class="fas fa-user"></i>
                                <span>Información Básica</span>
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="ubicacion-tab" data-tab="ubicacion" role="tab">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Ubicación</span>
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="contactos-tab" data-tab="contactos" role="tab">
                                <i class="fas fa-users"></i>
                                <span>Contactos</span>
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="adicional-tab" data-tab="adicional" role="tab">
                                <i class="fas fa-info-circle"></i>
                                <span>Información Adicional</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <form id="clienteForm" method="post" action="">
                    <input type="hidden" name="action" value="add_client">
                    <input type="hidden" id="departamento_texto" name="departamento_texto">
                    <input type="hidden" id="ciudad_texto" name="ciudad_texto">

                    <div class="crear-cliente-tab-content" id="clienteTabContent">
                        <!-- Tab 1: Información Básica -->
                        <div class="tab-pane active" id="basica" role="tabpanel">
                            <div class="crear-cliente-section-title">
                                <i class="fas fa-user"></i>
                                Datos Principales
                            </div>
                            
                            <div class="row">
                                <div class="col-12 crear-cliente-mb-3">
                                    <label class="crear-cliente-form-label">Nombre del Cliente <span class="crear-cliente-required">*</span></label>
                                    <input type="text" class="crear-cliente-form-control crear-cliente-uppercase-input" id="nombre_cliente" name="nombre_cliente" required placeholder="Ingrese el nombre completo del cliente" style="width: 100%;">
                                    <div id="val-nombre" class="crear-cliente-validation-message"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8 crear-cliente-mb-3">
                                    <label class="crear-cliente-form-label">Tipo de Identificación <span class="crear-cliente-required">*</span></label>
                                    <div class="row g-1">
                                        <div class="col-md-6">
                                            <label class="crear-cliente-radio-card" for="natural">
                                                <input type="radio" name="tipo_identificacion" id="natural" value="natural" required>
                                                <i class="fas fa-user"></i>
                                                <div class="fw-bold">Persona Natural</div>
                                                <small class="text-muted">Individuales</small>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="crear-cliente-radio-card" for="juridica">
                                                <input type="radio" name="tipo_identificacion" id="juridica" value="juridica" required>
                                                <i class="fas fa-building"></i>
                                                <div class="fw-bold">Persona Jurídica</div>
                                                <small class="text-muted">Empresas</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 crear-cliente-mb-3">
                                    <label class="crear-cliente-form-label">Fecha Vencimiento Cliente <span class="crear-cliente-required">*</span></label>
                                    <input type="date" class="crear-cliente-form-control fecha-vencimiento-highlight" id="fecha_vencimiento_cliente" name="fecha_vencimiento_cliente" required>
                                    <small class="text-muted">Por defecto: 2 años desde hoy</small>
                                </div>
                            </div>

                            <!-- Documento Section -->
                            <div class="crear-cliente-identification-section" id="documento-section">
                                <h6>
                                    <i class="fas fa-id-card"></i>
                                    Documento de Identidad
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="crear-cliente-form-label">Número de Documento <span class="crear-cliente-required">*</span></label>
                                        <input type="text" class="crear-cliente-form-control" id="documento" name="documento" placeholder="Número de documento (alfanumérico)">
                                    </div>
                                </div>
                                <div id="val-documento" class="crear-cliente-validation-message"></div>
                            </div>

                            <!-- NIT Section -->
                            <div class="crear-cliente-identification-section" id="nit-section">
                                <h6>
                                    <i class="fas fa-building"></i>
                                    Información NIT
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="crear-cliente-form-label">NIT (Alfanumérico) <span class="crear-cliente-required">*</span></label>
                                        <input type="text" class="crear-cliente-form-control" id="nit" name="nit" placeholder="Ingrese el NIT completo (alfanumérico)">
                                    </div>
                                </div>
                                <div id="val-nit" class="crear-cliente-validation-message"></div>
                            </div>

                            <div class="crear-cliente-section-title crear-cliente-mt-3">
                                <i class="fas fa-tags"></i>
                                Clasificación del Cliente
                            </div>
                            
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="crear-cliente-form-label">Estado <span class="crear-cliente-required">*</span></label>
                                    <select class="crear-cliente-form-select" name="estado_cliente" required>
                                        <option value="">Seleccionar estado...</option>
                                        <option value="activo">Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                        <option value="inactivo_documentacion">Inactivo por documentación</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="crear-cliente-form-label">Tipo de Cliente <span class="crear-cliente-required">*</span></label>
                                    <select class="crear-cliente-form-select" name="tipo_cliente" required>
                                        <option value="">Seleccionar tipo...</option>
                                        <option value="arquitectos">ARQUITECTOS ESPECIFICADORES</option>
                                        <option value="tipo_a">CLIENTES TIPO A</option>
                                        <option value="tipo_b">CLIENTES TIPO B</option>
                                        <option value="tipo_c">CLIENTES TIPO C</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row g-2 crear-cliente-mt-3">
                                <div class="col-12">
                                    <label class="crear-cliente-form-label">Sector <span class="crear-cliente-required">*</span></label>
                                    <select class="crear-cliente-form-select" name="sector" required>
                                        <option value="">Seleccionar sector...</option>
                                        <option value="tecnologia">Tecnología</option>
                                        <option value="servicios">Servicios</option>
                                        <option value="comercio">Comercio</option>
                                        <option value="financieros">Financieros</option>
                                        <option value="consultoria">Consultoría</option>
                                        <option value="bpo">BPO</option>
                                        <option value="manufactura">Manufactura / Producción</option>
                                        <option value="salud">Salud</option>
                                        <option value="educacion">Educación</option>
                                        <option value="horeca">Horeca</option>
                                        <option value="arquitectura">Arquitectura</option>
                                        <option value="construccion">Construcción</option>
                                        <option value="minero">Minero</option>
                                        <option value="hidrocarburos">Hidrocarburos</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Ubicación -->
                        <div class="tab-pane" id="ubicacion" role="tabpanel">
                            <div class="crear-cliente-section-title">
                                <i class="fas fa-map-marker-alt"></i>
                                Ubicación Geográfica
                            </div>
                            
                            <div class="crear-cliente-mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="crear-cliente-form-label">Departamento <span class="crear-cliente-required">*</span></label>
                                        <div class="d-flex align-items-center">
                                            <select class="crear-cliente-form-select" id="departamento" name="departamento" required style="flex: 1;">
                                                <option value="">Seleccionar departamento...</option>
                                            </select>
                                            <button type="button" class="add-option-btn" id="addDepartamento" title="Agregar nuevo departamento">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="crear-cliente-form-label">Ciudad <span class="crear-cliente-required">*</span></label>
                                        <div class="d-flex align-items-center">
                                            <select class="crear-cliente-form-select" id="ciudad" name="ciudad" required style="flex: 1;">
                                                <option value="">Seleccione departamento primero</option>
                                            </select>
                                            <button type="button" class="add-option-btn" id="addCiudad" title="Agregar nueva ciudad">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="crear-cliente-section-title">
                                <i class="fas fa-home"></i>
                                Dirección
                            </div>
                            
                            <div class="crear-cliente-mb-3">
                                <label class="crear-cliente-form-label">Dirección Completa</label>
                                <textarea class="crear-cliente-form-control" name="direccion_completa" rows="3" placeholder="Ejemplo: Calle 15 #25-30, Piso 2, Oficina 201, Barrio Centro"></textarea>
                                <small class="text-muted">Ingrese la dirección completa de manera libre</small>
                            </div>
                        </div>

                        <!-- Tab 3: Contactos -->
                        <div class="tab-pane" id="contactos" role="tabpanel">
                            <div class="crear-cliente-section-title">
                                <i class="fas fa-users"></i>
                                Contacto Principal
                            </div>
                            
                            <div id="contactos-container">
                                <div class="crear-cliente-contact-card" data-id="1">
                                    <div class="crear-cliente-contact-body">
                                        <div class="row g-2 crear-cliente-mb-3">
                                            <div class="col-md-6">
                                                <label class="crear-cliente-form-label">Nombre Completo <span class="crear-cliente-required">*</span></label>
                                                <input type="text" class="crear-cliente-form-control crear-cliente-uppercase-input" name="contacto_nombre" required placeholder="Nombre completo del contacto">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="crear-cliente-form-label">Cargo</label>
                                                <input type="text" class="crear-cliente-form-control crear-cliente-uppercase-input" name="contacto_cargo" placeholder="Cargo o posición en la empresa">
                                            </div>
                                        </div>
                                        <div class="row g-2 crear-cliente-mb-3">
                                            <div class="col-md-6">
                                                <label class="crear-cliente-form-label">Email <span class="crear-cliente-required">*</span></label>
                                                <input type="email" class="crear-cliente-form-control" name="contacto_email" required placeholder="correo@empresa.com">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="crear-cliente-form-label">Teléfono</label>
                                                <input type="text" class="crear-cliente-form-control" name="contacto_telefono" placeholder="Número de teléfono (formato libre)">
                                            </div>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <label class="crear-cliente-form-label">Departamento</label>
                                                <select class="crear-cliente-form-select" name="contacto_departamento">
                                                    <option value="">Seleccionar departamento...</option>
                                                    <option value="compras">Compras</option>
                                                    <option value="contabilidad">Contabilidad</option>
                                                    <option value="finanzas">Finanzas</option>
                                                    <option value="administracion">Administración</option>
                                                    <option value="gerencia">Gerencia</option>
                                                    <option value="recepcion">Recepción</option>
                                                    <option value="ventas">Ventas</option>
                                                    <option value="marketing">Marketing</option>
                                                    <option value="recursos_humanos">Recursos Humanos</option>
                                                    <option value="tecnologia">Tecnología</option>
                                                    <option value="otro">Otro</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 4: Información Adicional -->
                        <div class="tab-pane" id="adicional" role="tabpanel">
                            <div class="crear-cliente-section-title">
                                <i class="fas fa-info-circle"></i>
                                Información Adicional
                            </div>
                            
                            <div class="row g-2 crear-cliente-mb-3">
                                <div class="col-md-6">
                                    <label class="crear-cliente-form-label">Origen del Cliente <span class="crear-cliente-required">*</span></label>
                                    <select class="crear-cliente-form-select" name="origen_cliente" required>
                                        <option value="">Seleccionar origen...</option>
                                        <option value="email_marketing">Email Marketing</option>
                                        <option value="google">Google</option>
                                        <option value="meta">Meta (Facebook/Instagram)</option>
                                        <option value="organico">Orgánico</option>
                                        <option value="cartera">Cartera</option>
                                        <option value="visita_showroom">Visita Showroom</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="crear-cliente-form-label">¿Cliente en ERP? <span class="crear-cliente-required">*</span></label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="crear-cliente-radio-card" for="erp_si">
                                                <input type="radio" name="cliente_erp" id="erp_si" value="si" required>
                                                <i class="fas fa-check-circle"></i>
                                                <div class="fw-bold">Sí</div>
                                            </label>
                                        </div>
                                        <div class="col-6">
                                            <label class="crear-cliente-radio-card" for="erp_no">
                                                <input type="radio" name="cliente_erp" id="erp_no" value="no" required>
                                                <i class="fas fa-times-circle"></i>
                                                <div class="fw-bold">No</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <label class="crear-cliente-form-label">Asesor Asignado <span class="crear-cliente-required">*</span></label>
                                    <select class="crear-cliente-form-select" id="asesor" name="asesor_asignado" required>
                                        <option value="">Cargando asesores...</option>
                                    </select>
                                    <small class="text-muted">Por defecto se asigna el asesor que inició sesión</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="crear-cliente-form-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="crear-cliente-btn crear-cliente-btn-outline-secondary" id="btnAnterior" disabled>
                                <i class="fas fa-arrow-left"></i>
                                Anterior
                            </button>
                            <div class="d-flex gap-3">
                                <button type="button" class="crear-cliente-btn crear-cliente-btn-primary" id="btnSiguiente">
                                    Siguiente
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                                <button type="submit" class="crear-cliente-btn crear-cliente-btn-success" id="btnGuardar" style="display: none;">
                                    <i class="fas fa-save"></i>
                                    Guardar Cliente
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed end-0 p-3" style="top: 100px; z-index: 1050;">
        <div id="toastNotification" class="toast" role="alert">
            <div class="toast-header">
                <i id="toastIcon" class="fas fa-info-circle me-2"></i>
                <strong class="me-auto" id="toastTitle">Notificación</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Mensaje
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Datos estáticos de ubicaciones para Colombia
        const ubicacionesData = {
            departamentos: [
                { id: 'valle', name: 'Valle del Cauca' },
                { id: 'cundinamarca', name: 'Cundinamarca' },
                { id: 'antioquia', name: 'Antioquia' },
                { id: 'atlantico', name: 'Atlántico' },
                { id: 'bolivar', name: 'Bolívar' },
                { id: 'caldas', name: 'Caldas' },
                { id: 'cauca', name: 'Cauca' },
                { id: 'cesar', name: 'Cesar' },
                { id: 'cordoba', name: 'Córdoba' },
                { id: 'huila', name: 'Huila' },
                { id: 'magdalena', name: 'Magdalena' },
                { id: 'meta', name: 'Meta' },
                { id: 'narino', name: 'Nariño' },
                { id: 'norte_santander', name: 'Norte de Santander' },
                { id: 'quindio', name: 'Quindío' },
                { id: 'risaralda', name: 'Risaralda' },
                { id: 'santander', name: 'Santander' },
                { id: 'tolima', name: 'Tolima' },
                { id: 'boyaca', name: 'Boyacá' },
                { id: 'casanare', name: 'Casanare' },
                { id: 'choco', name: 'Chocó' },
                { id: 'la_guajira', name: 'La Guajira' },
                { id: 'sucre', name: 'Sucre' }
            ],
            
            ciudades: {
                valle: [
                    { id: 'cali', name: 'Cali' },
                    { id: 'palmira', name: 'Palmira' },
                    { id: 'jamundi', name: 'Jamundí' },
                    { id: 'yumbo', name: 'Yumbo' },
                    { id: 'buenaventura', name: 'Buenaventura' },
                    { id: 'tulua', name: 'Tuluá' },
                    { id: 'cartago', name: 'Cartago' },
                    { id: 'buga', name: 'Buga' },
                    { id: 'candelaria', name: 'Candelaria' },
                    { id: 'pradera', name: 'Pradera' },
                    { id: 'florida', name: 'Florida' },
                    { id: 'dagua', name: 'Dagua' }
                ]
            }
        };

        // Variables globales
        let currentStep = 0;
        const totalSteps = 4;
        let validationTimeouts = {};
        let validationCache = new Map();
        let currentUser = '<?php echo $_SESSION['nombre'] ?? 'Usuario Actual'; ?>';

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            initializeForm();
            loadInitialData();
            setupEventListeners();
            setupUppercaseInputs();
            setupRealtimeValidation();
            setupFechaVencimiento();
            console.log('Formulario CRM inicializado correctamente');
            console.log('Usuario actual:', currentUser);
        });

        function setupFechaVencimiento() {
            const fechaInput = document.getElementById('fecha_vencimiento_cliente');
            if (fechaInput) {
                // Establecer fecha por defecto (2 años desde hoy)
                const twoYearsFromToday = new Date();
                twoYearsFromToday.setFullYear(twoYearsFromToday.getFullYear() + 2);
                fechaInput.value = twoYearsFromToday.toISOString().split('T')[0];
                
                fechaInput.addEventListener('change', function() {
                    const selectedDate = new Date(this.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    this.classList.remove('is-invalid', 'is-valid');
                    
                    if (this.value) {
                        if (selectedDate < today) {
                            this.classList.add('is-valid');
                            showToast('Fecha anterior a hoy - Cliente con vencimiento vencido', 'info');
                        } else {
                            this.classList.add('is-valid');
                        }
                    }
                });
            }
        }

        function setupUppercaseInputs() {
            const uppercaseInputs = document.querySelectorAll('.crear-cliente-uppercase-input');
            
            uppercaseInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const cursorPosition = this.selectionStart;
                    const cursorEnd = this.selectionEnd;
                    this.value = this.value.toUpperCase();
                    this.setSelectionRange(cursorPosition, cursorEnd);
                });
                input.addEventListener('blur', function() {
                    this.value = this.value.toUpperCase();
                });
                if (input.value) {
                    input.value = input.value.toUpperCase();
                }
            });
        }

        function initializeForm() {
            updateNavigation();
            showTab(0);
        }

        function setupRealtimeValidation() {
            // Validación de nombre
            const nombreInput = document.getElementById('nombre_cliente');
            if (nombreInput) {
                nombreInput.addEventListener('input', function() {
                    clearTimeout(validationTimeouts.nombre);
                    const value = this.value.trim();
                    
                    this.classList.remove('is-valid', 'is-invalid');
                    this.removeAttribute('data-validation-error');
                    hideValidation('val-nombre');
                    
                    if (value.length < 3) {
                        return;
                    }

                    const cacheKey = `nombre_${value.toUpperCase()}`;
                    if (validationCache.has(cacheKey)) {
                        handleValidationResult('nombre', validationCache.get(cacheKey), value);
                        return;
                    }

                    validationTimeouts.nombre = setTimeout(() => {
                        if (value) {
                            validateClientExists('nombre', value);
                        }
                    }, 800);
                });
            }

            // Validación de NIT
            const nitInput = document.getElementById('nit');
            if (nitInput) {
                nitInput.addEventListener('input', function() {
                    clearTimeout(validationTimeouts.nit);
                    
                    const nit = this.value.trim();
                    
                    this.classList.remove('is-valid', 'is-invalid');
                    this.removeAttribute('data-validation-error');
                    hideValidation('val-nit');
                    
                    if (nit.length === 0) {
                        return;
                    }
                    
                    validationTimeouts.nit = setTimeout(() => {
                        validateNIT();
                    }, 600);
                });
            }

            // Validación de documento
            const documentoInput = document.getElementById('documento');
            if (documentoInput) {
                documentoInput.addEventListener('input', function() {
                    clearTimeout(validationTimeouts.documento);
                    const value = this.value.trim();
                    
                    this.classList.remove('is-valid', 'is-invalid');
                    this.removeAttribute('data-validation-error');
                    hideValidation('val-documento');
                    
                    if (value.length < 3) {
                        return;
                    }

                    const cacheKey = `documento_${value}`;
                    if (validationCache.has(cacheKey)) {
                        handleValidationResult('documento', validationCache.get(cacheKey), value);
                        return;
                    }

                    validationTimeouts.documento = setTimeout(() => {
                        if (value) {
                            validateClientExists('documento', value);
                        }
                    }, 800);
                });
            }
        }

        function validateNIT() {
            const nitInput = document.getElementById('nit');
            
            if (!nitInput) return;
            
            const nit = nitInput.value.trim();
            
            if (nit.length > 0) {
                const cacheKey = `nit_${nit}`;
                if (validationCache.has(cacheKey)) {
                    handleValidationResult('nit', validationCache.get(cacheKey), nit);
                    return;
                }
                
                validateClientExists('nit', nit);
            }
        }

        function validateClientExists(type, value) {
            let url = 'api/validar_cliente.php?';
            if (type === 'nit') {
                url += `tipo=nit&nit=${encodeURIComponent(value)}`;
            } else if (type === 'documento') {
                url += `tipo=documento&documento=${encodeURIComponent(value)}`;
            } else if (type === 'nombre') {
                url += `tipo=nombre&nombre=${encodeURIComponent(value)}`;
            }

            const validationId = type === 'nit' ? 'val-nit' : 
                               type === 'documento' ? 'val-documento' : 'val-nombre';

            showValidation(validationId, '<span class="validation-spinner"></span>Verificando...', 'warning');

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const cacheKey = type === 'nit' ? `nit_${value}` : 
                                   type === 'documento' ? `documento_${value}` : 
                                   `nombre_${value.toUpperCase()}`;
                    validationCache.set(cacheKey, data);
                    
                    handleValidationResult(type, data, value);
                })
                .catch(error => {
                    console.error('Error validando:', error);
                    showValidation(validationId, '⚠️ Error al validar - verifique conexión', 'warning');
                    
                    const input = document.getElementById(
                        type === 'nit' ? 'nit' : 
                        type === 'documento' ? 'documento' : 
                        'nombre_cliente'
                    );
                    if (input) {
                        input.classList.remove('is-valid', 'is-invalid');
                        input.removeAttribute('data-validation-error');
                    }
                });
        }

        function handleValidationResult(type, data, value) {
            const validationId = type === 'nit' ? 'val-nit' : 
                               type === 'documento' ? 'val-documento' : 'val-nombre';
            
            if (data.error) {
                showValidation(validationId, '⚠️ Error del servidor', 'warning');
                return;
            }
            
            if (data.existe) {
                let mensaje = '';
                if (type === 'nit') {
                    mensaje = `❌ NIT ${value} ya registrado`;
                } else if (type === 'documento') {
                    mensaje = `❌ Documento ${value} ya registrado`;
                } else {
                    mensaje = `❌ Nombre "${value}" ya registrado`;
                }
                
                if (data.cliente && data.cliente.Nombre) {
                    mensaje += ` - Cliente: ${data.cliente.Nombre}`;
                }
                
                showValidation(validationId, mensaje, 'error');
                
                const input = document.getElementById(
                    type === 'nit' ? 'nit' : 
                    type === 'documento' ? 'documento' : 
                    'nombre_cliente'
                );
                if (input) {
                    input.classList.add('is-invalid');
                    input.classList.remove('is-valid');
                    input.setAttribute('data-validation-error', 'true');
                }
                
            } else {
                showValidation(validationId, '✅ Disponible', 'success');
                setTimeout(() => hideValidation(validationId), 3000);
                
                const input = document.getElementById(
                    type === 'nit' ? 'nit' : 
                    type === 'documento' ? 'documento' : 
                    'nombre_cliente'
                );
                if (input) {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    input.removeAttribute('data-validation-error');
                }
            }
        }

        function setupEventListeners() {
            // Tab navigation events - CORREGIDO
            const tabLinks = document.querySelectorAll('.crear-cliente-nav-tabs .nav-link');
            tabLinks.forEach((tab, index) => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (validateCurrentStep() || index < currentStep) {
                        currentStep = index;
                        showTab(currentStep);
                        updateNavigation();
                    }
                });
            });

            // Navigation buttons
            const btnAnterior = document.getElementById('btnAnterior');
            const btnSiguiente = document.getElementById('btnSiguiente');
            
            if (btnAnterior) {
                btnAnterior.addEventListener('click', () => {
                    if (currentStep > 0) {
                        currentStep--;
                        showTab(currentStep);
                        updateNavigation();
                    }
                });
            }

            if (btnSiguiente) {
                btnSiguiente.addEventListener('click', () => {
                    if (validateCurrentStep() && currentStep < totalSteps - 1) {
                        currentStep++;
                        showTab(currentStep);
                        updateNavigation();
                    }
                });
            }

            // Identification type
            document.querySelectorAll('input[name="tipo_identificacion"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    clearValidationCache();
                    handleIdentificationTypeChange.call(this);
                });
            });

            // Location handlers
            setupLocationHandlers();

            // Add new options buttons
            setupAddButtons();

            // Form submission
            const clienteForm = document.getElementById('clienteForm');
            if (clienteForm) {
                clienteForm.addEventListener('submit', handleFormSubmit);
            }

            // Radio cards
            document.querySelectorAll('.crear-cliente-radio-card').forEach(card => {
                card.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                        
                        const group = radio.name;
                        document.querySelectorAll(`input[name="${group}"]`).forEach(r => {
                            r.closest('.crear-cliente-radio-card').classList.remove('selected');
                        });
                        this.classList.add('selected');
                    }
                });
            });
        }

        function setupAddButtons() {
            // Agregar nuevo departamento
            const addDepartamentoBtn = document.getElementById('addDepartamento');
            if (addDepartamentoBtn) {
                addDepartamentoBtn.addEventListener('click', function() {
                    const nombre = prompt('Ingrese el nombre del nuevo departamento:');
                    if (nombre && nombre.trim()) {
                        const departamentoSelect = document.getElementById('departamento');
                        const id = nombre.toLowerCase().replace(/ /g, '_').replace(/[áéíóú]/g, function(match) {
                            const map = {'á':'a','é':'e','í':'i','ó':'o','ú':'u'};
                            return map[match];
                        });
                        
                        // Agregar a los datos
                        ubicacionesData.departamentos.push({ id: id, name: nombre.trim() });
                        
                        // Recargar opciones
                        loadDepartamentos();
                        
                        // Seleccionar el nuevo departamento
                        departamentoSelect.value = id;
                        departamentoSelect.dispatchEvent(new Event('change'));
                        
                        showToast('Departamento agregado exitosamente', 'success');
                    }
                });
            }

            // Agregar nueva ciudad
            const addCiudadBtn = document.getElementById('addCiudad');
            if (addCiudadBtn) {
                addCiudadBtn.addEventListener('click', function() {
                    const departamentoSelect = document.getElementById('departamento');
                    if (!departamentoSelect.value) {
                        showToast('Primero seleccione un departamento', 'warning');
                        return;
                    }

                    const nombre = prompt('Ingrese el nombre de la nueva ciudad:');
                    if (nombre && nombre.trim()) {
                        const ciudadSelect = document.getElementById('ciudad');
                        const deptoId = departamentoSelect.value;
                        const id = nombre.toLowerCase().replace(/ /g, '_').replace(/[áéíóú]/g, function(match) {
                            const map = {'á':'a','é':'e','í':'i','ó':'o','ú':'u'};
                            return map[match];
                        });
                        
                        // Agregar a los datos
                        if (!ubicacionesData.ciudades[deptoId]) {
                            ubicacionesData.ciudades[deptoId] = [];
                        }
                        ubicacionesData.ciudades[deptoId].push({ id: id, name: nombre.trim() });
                        
                        // Recargar ciudades
                        loadCiudades(deptoId);
                        
                        // Seleccionar la nueva ciudad
                        ciudadSelect.value = id;
                        ciudadSelect.dispatchEvent(new Event('change'));
                        
                        showToast('Ciudad agregada exitosamente', 'success');
                    }
                });
            }
        }

        function loadDepartamentos() {
            const select = document.getElementById('departamento');
            if (!select) return;
            
            select.innerHTML = '<option value="">Seleccionar departamento...</option>';
            
            ubicacionesData.departamentos.forEach(depto => {
                const option = document.createElement('option');
                option.value = depto.id;
                option.textContent = depto.name;
                select.appendChild(option);
            });

            console.log('Departamentos cargados:', ubicacionesData.departamentos.length);
        }

        function loadCiudades(deptoId) {
            const select = document.getElementById('ciudad');
            if (!select) return;
            
            select.innerHTML = '<option value="">Seleccionar ciudad...</option>';
            
            const ciudades = ubicacionesData.ciudades[deptoId] || [];
            
            ciudades.forEach(ciudad => {
                const option = document.createElement('option');
                option.value = ciudad.id;
                option.textContent = ciudad.name;
                select.appendChild(option);
            });

            console.log(`Ciudades cargadas para ${deptoId}:`, ciudades.length);
        }

        function setupLocationHandlers() {
            const deptoSelect = document.getElementById('departamento');
            const ciudadSelect = document.getElementById('ciudad');

            if (deptoSelect) {
                deptoSelect.addEventListener('change', function() {
                    const deptoTexto = document.getElementById('departamento_texto');
                    if (deptoTexto && this.selectedIndex > 0) {
                        deptoTexto.value = this.options[this.selectedIndex].text;
                        console.log('Departamento seleccionado:', this.options[this.selectedIndex].text);
                    }

                    if (ciudadSelect) {
                        ciudadSelect.innerHTML = '<option value="">Seleccionar ciudad...</option>';
                    }

                    if (this.value) {
                        loadCiudades(this.value);
                    }
                });
            }

            if (ciudadSelect) {
                ciudadSelect.addEventListener('change', function() {
                    const ciudadTexto = document.getElementById('ciudad_texto');
                    if (ciudadTexto && this.selectedIndex > 0) {
                        ciudadTexto.value = this.options[this.selectedIndex].text;
                        console.log('Ciudad seleccionada:', this.options[this.selectedIndex].text);
                    }
                });
            }
        }

        function loadInitialData() {
            loadDepartamentos();
            loadAsesores();
        }

        function loadAsesores() {
            const select = document.getElementById('asesor');
            if (!select) return;
            
            fetch('api/obtener_asesores.php')
                .then(response => response.json())
                .then(data => {
                    select.innerHTML = '<option value="">Seleccionar asesor...</option>';
                    
                    if (Array.isArray(data)) {
                        data.forEach(asesor => {
                            const option = document.createElement('option');
                            option.value = asesor.id;
                            option.textContent = asesor.nombre;
                            
                            // Seleccionar por defecto el asesor actual
                            if (asesor.nombre === currentUser) {
                                option.selected = true;
                            }
                            
                            if (asesor.id === 'Sin Asesor') {
                                option.style.fontStyle = 'italic';
                                option.style.color = '#6b7280';
                            }
                            
                            select.appendChild(option);
                        });
                        console.log('Asesores cargados:', data.length);
                    }
                })
                .catch(error => {
                    console.error('Error cargando asesores:', error);
                    select.innerHTML = '<option value="">Error al cargar asesores</option>';
                });
        }

        function showTab(index) {
            // Ocultar todas las pestañas
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });

            // Remover active de todos los enlaces
            document.querySelectorAll('.crear-cliente-nav-tabs .nav-link').forEach(link => {
                link.classList.remove('active');
            });

            // Mostrar pestaña actual
            const tabPanes = document.querySelectorAll('.tab-pane');
            const tabLinks = document.querySelectorAll('.crear-cliente-nav-tabs .nav-link');
            
            if (tabPanes[index]) {
                tabPanes[index].classList.add('active');
                console.log('Mostrando tab:', index);
            }
            if (tabLinks[index]) {
                tabLinks[index].classList.add('active');
            }
        }

        function updateNavigation() {
            const btnAnterior = document.getElementById('btnAnterior');
            const btnSiguiente = document.getElementById('btnSiguiente');
            const btnGuardar = document.getElementById('btnGuardar');

            if (btnAnterior) {
                btnAnterior.disabled = currentStep === 0;
            }
            
            if (currentStep === totalSteps - 1) {
                if (btnSiguiente) btnSiguiente.style.display = 'none';
                if (btnGuardar) btnGuardar.style.display = 'inline-flex';
            } else {
                if (btnSiguiente) btnSiguiente.style.display = 'inline-flex';
                if (btnGuardar) btnGuardar.style.display = 'none';
            }
        }

        function validateCurrentStep() {
            const activeTab = document.querySelector('.tab-pane.active');
            if (!activeTab) return true;
            
            const requiredFields = activeTab.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                    if (!field.hasAttribute('data-validation-error')) {
                        field.classList.add('is-valid');
                    }
                }
            });

            if (!isValid) {
                showToast('Por favor complete todos los campos obligatorios', 'error');
            }

            return isValid;
        }

        function handleIdentificationTypeChange() {
            const nitSection = document.getElementById('nit-section');
            const documentoSection = document.getElementById('documento-section');
            const nitInput = document.getElementById('nit');
            const documentoInput = document.getElementById('documento');

            if (!nitSection || !documentoSection) return;

            hideValidation('val-nit');
            hideValidation('val-documento');
            clearTimeout(validationTimeouts.nit);
            clearTimeout(validationTimeouts.documento);

            if (this.value === 'juridica') {
                nitSection.classList.add('active');
                documentoSection.classList.remove('active');
                
                if (nitInput) {
                    nitInput.required = true;
                    nitInput.classList.remove('is-valid', 'is-invalid');
                    nitInput.removeAttribute('data-validation-error');
                }
                if (documentoInput) {
                    documentoInput.required = false;
                    documentoInput.value = '';
                    documentoInput.classList.remove('is-valid', 'is-invalid');
                    documentoInput.removeAttribute('data-validation-error');
                }
            } else if (this.value === 'natural') {
                nitSection.classList.remove('active');
                documentoSection.classList.add('active');
                
                if (nitInput) {
                    nitInput.required = false;
                    nitInput.value = '';
                    nitInput.classList.remove('is-valid', 'is-invalid');
                    nitInput.removeAttribute('data-validation-error');
                }
                if (documentoInput) {
                    documentoInput.required = true;
                    documentoInput.classList.remove('is-valid', 'is-invalid');
                    documentoInput.removeAttribute('data-validation-error');
                }
            }
        }

        function handleFormSubmit(e) {
            e.preventDefault();
            
            const errorInputs = document.querySelectorAll('[data-validation-error="true"]');
            if (errorInputs.length > 0) {
                showToast('Hay errores de validación que deben corregirse antes de continuar', 'error');
                
                const firstError = errorInputs[0];
                const tabPane = firstError.closest('.tab-pane');
                if (tabPane) {
                    const tabPanes = Array.from(document.querySelectorAll('.tab-pane'));
                    const tabIndex = tabPanes.indexOf(tabPane);
                    if (tabIndex !== -1) {
                        currentStep = tabIndex;
                        showTab(currentStep);
                        updateNavigation();
                        setTimeout(() => {
                            firstError.scrollIntoView({ 
                                behavior: 'smooth', 
                                block: 'center' 
                            });
                            firstError.focus();
                        }, 300);
                    }
                }
                return;
            }
            
            if (!validateAllSteps()) {
                showToast('Por favor complete todos los campos obligatorios', 'error');
                return;
            }

            const submitBtn = document.getElementById('btnGuardar');
            if (!submitBtn) return;
            
            const originalContent = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>Guardando...';

            const formData = new FormData(this);

            console.log('Enviando formulario...');

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                console.log('Respuesta del servidor:', data);
                if (data.success) {
                    let message = 'Cliente creado exitosamente';
                    if (data.email_sent) {
                        message += ' y notificación enviada por correo';
                    } else {
                        message += ' (correo de notificación no pudo enviarse)';
                    }
                    showToast(message, 'success');
                    submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>¡Guardado!';
                    setTimeout(() => {
                        window.location.href = 'clientes.php';
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Error al crear el cliente');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast(error.message || 'Error al guardar el cliente', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
            });
        }

        function validateAllSteps() {
            const form = document.getElementById('clienteForm');
            if (!form) return false;
            
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            let firstInvalidField = null;

            requiredFields.forEach(field => {
                if (field.offsetParent === null) return;
                
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                    if (!field.hasAttribute('data-validation-error')) {
                        field.classList.add('is-valid');
                    }
                }
            });

            if (!isValid && firstInvalidField) {
                const tabPane = firstInvalidField.closest('.tab-pane');
                if (tabPane) {
                    const tabPanes = Array.from(document.querySelectorAll('.tab-pane'));
                    const tabIndex = tabPanes.indexOf(tabPane);
                    if (tabIndex !== -1) {
                        currentStep = tabIndex;
                        showTab(currentStep);
                        updateNavigation();
                        setTimeout(() => {
                            firstInvalidField.scrollIntoView({ 
                                behavior: 'smooth', 
                                block: 'center' 
                            });
                            firstInvalidField.focus();
                        }, 300);
                    }
                }
            }

            return isValid;
        }

        function showValidation(elementId, message, type) {
            const element = document.getElementById(elementId);
            if (!element) return;

            element.className = `crear-cliente-validation-message ${type}`;
            element.innerHTML = message;
            element.style.display = 'block';
        }

        function hideValidation(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.style.display = 'none';
            }
        }

        function showToast(message, type = 'info') {
            const toast = document.getElementById('toastNotification');
            const toastIcon = document.getElementById('toastIcon');
            const toastTitle = document.getElementById('toastTitle');
            const toastMessage = document.getElementById('toastMessage');

            if (!toast || !toastIcon || !toastTitle || !toastMessage) return;

            const icons = {
                success: 'fas fa-check-circle text-success',
                error: 'fas fa-exclamation-circle text-danger',
                warning: 'fas fa-exclamation-triangle text-warning',
                info: 'fas fa-info-circle text-info'
            };

            const titles = {
                success: 'Éxito',
                error: 'Error',
                warning: 'Advertencia',
                info: 'Información'
            };

            toastIcon.className = icons[type] || icons.info;
            toastTitle.textContent = titles[type] || titles.info;
            toastMessage.textContent = message;

            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: type === 'success' ? 3000 : 5000
            });
            bsToast.show();
        }

        function clearValidationCache() {
            validationCache.clear();
        }

        console.log('=== FORMULARIO CRM CARGADO COMPLETAMENTE ===');
        console.log('✅ Navbar incluido correctamente');
        console.log('✅ Tabs funcionando correctamente');
        console.log('✅ Validaciones en tiempo real activas');
        console.log('✅ Datos de ubicación estáticos cargados');
        console.log('✅ Usuario actual:', currentUser);
        console.log('✅ Sistema completamente funcional');
    </script>
</body>
</html>