<?php
// ============================================================================
// CONFIGURACIÓN Y SEGURIDAD
// ============================================================================
require_once "config_db.php";

// Configurar manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/errores.log');

// ============================================================================
// AUTENTICACIÓN CENTRALIZADA
// ============================================================================
if (session_status() === PHP_SESSION_NONE) session_start();

function verificarAutenticacion($ajax = false) {
    if (!isset($_SESSION['usuario'])) {
        if ($ajax) {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado', 'code' => 'UNAUTHORIZED']);
            exit;
        }
        header('Location: /login.php');
        exit;
    }
    return true;
}

// Verificar autenticación para todas las solicitudes
$esAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';
verificarAutenticacion($esAjax);

// ============================================================================
// CSRF PROTECTION
// ============================================================================
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================================================
// FUNCIONES AUXILIARES
// ============================================================================
function sanitizarEntrada($dato, $tipo = 'string') {
    if (is_array($dato)) {
        return array_map(function($item) use ($tipo) {
            return sanitizarEntrada($item, $tipo);
        }, $dato);
    }
    
    $dato = trim($dato);
    switch ($tipo) {
        case 'int':
            return filter_var($dato, FILTER_VALIDATE_INT) ? (int)$dato : 0;
        case 'email':
            return filter_var($dato, FILTER_VALIDATE_EMAIL) ? $dato : '';
        case 'string':
        default:
            return htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
    }
}

function responderJSON($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function logError($mensaje, $contexto = []) {
    error_log(sprintf(
        "[%s] %s %s",
        date('Y-m-d H:i:s'),
        $mensaje,
        json_encode($contexto)
    ));
}

// ============================================================================
// AJAX HANDLER
// ============================================================================
if ($esAjax) {
    $action = sanitizarEntrada($_POST['action'] ?? '');
    
    // ========================================================================
    // CARGAR SECCIONES
    // ========================================================================
    if ($action === 'cargar_secciones') {
        $sala = sanitizarEntrada($_POST['sala'] ?? '');
        
        if (empty($sala)) {
            responderJSON(['secciones' => [], 'error' => 'Sala no especificada']);
        }
        
        try {
            $stmt = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
            $stmt->bind_param("s", $sala);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $secciones = [];
            while ($row = $result->fetch_assoc()) {
                $secciones[] = [
                    'id' => (int)$row['id'],
                    'nombre' => htmlspecialchars($row['nombre'])
                ];
            }
            
            responderJSON(['secciones' => $secciones]);
        } catch (Exception $e) {
            logError('Error al cargar secciones', ['sala' => $sala, 'error' => $e->getMessage()]);
            responderJSON(['secciones' => [], 'error' => 'Error al cargar secciones'], 500);
        }
        exit;
    }
    
    // ========================================================================
    // CARGAR DOCENTES
    // ========================================================================
    if ($action === 'cargar_docentes') {
        $seccion = filter_var($_POST['seccion'] ?? 0, FILTER_VALIDATE_INT);
        
        if (!$seccion) {
            responderJSON(['docentes' => [], 'error' => 'Sección no especificada']);
        }
        
        try {
            $stmt = $conexion->prepare("SELECT id, nombre FROM profesores WHERE seccion = ? AND estatus = 'Activo' ORDER BY nombre ASC");
            $stmt->bind_param("i", $seccion);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $docentes = [];
            while ($row = $result->fetch_assoc()) {
                $docentes[] = [
                    'id' => (int)$row['id'],
                    'nombre' => htmlspecialchars($row['nombre'])
                ];
            }
            
            responderJSON(['docentes' => $docentes]);
        } catch (Exception $e) {
            logError('Error al cargar docentes', ['seccion' => $seccion, 'error' => $e->getMessage()]);
            responderJSON(['docentes' => [], 'error' => 'Error al cargar docentes'], 500);
        }
        exit;
    }
    
    // ========================================================================
    // BUSCAR ESTUDIANTE - OPTIMIZADO
    // ========================================================================
    if ($action === 'buscar_estudiante') {
        $busqueda = sanitizarEntrada($_POST['busqueda'] ?? '');
        $sala = sanitizarEntrada($_POST['sala'] ?? '');
        $seccion_id = filter_var($_POST['seccion'] ?? 0, FILTER_VALIDATE_INT);
        
        if (strlen($busqueda) < 2) {
            responderJSON(['estudiantes' => [], 'mensaje' => 'Escriba al menos 2 caracteres']);
        }
        
        if (empty($sala) || !$seccion_id) {
            responderJSON(['estudiantes' => [], 'error' => 'Filtros incompletos']);
        }
        
        try {
            $termino = "%" . trim($busqueda) . "%";
            
            $sql = "
                SELECT 
                    id, nombre, apellido, cedula, nacionalidad, genero,
                    fecha_nacimiento, fecha_ingreso,
                    CASE 
                        WHEN CONCAT(apellido, ' ', nombre) LIKE ? THEN 1
                        WHEN apellido LIKE ? THEN 2
                        WHEN nombre LIKE ? THEN 3
                        WHEN cedula LIKE ? THEN 4
                        ELSE 5
                    END as relevancia
                FROM estudiantes 
                WHERE (
                    apellido LIKE ? 
                    OR nombre LIKE ? 
                    OR cedula LIKE ? 
                    OR CONCAT(apellido, ' ', nombre) LIKE ?
                    OR CONCAT(nombre, ' ', apellido) LIKE ?
                )
                AND sala = ? 
                AND seccion_id = ? 
                AND estatus = 'Activo' 
                ORDER BY relevancia ASC, apellido ASC, nombre ASC
                LIMIT 10
            ";
            
            $stmt = $conexion->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error en preparación de consulta: " . $conexion->error);
            }
            
            $stmt->bind_param(
                "ssssssssssi",
                $termino, $termino, $termino, $termino,
                $termino, $termino, $termino, $termino, $termino,
                $sala, 
                $seccion_id 
            );
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $estudiantes = [];
            while ($row = $result->fetch_assoc()) {
                $estudiantes[] = [
                    'id' => (int)$row['id'],
                    'apellido' => htmlspecialchars($row['apellido']),
                    'nombre' => htmlspecialchars($row['nombre']),
                    'nombre_completo' => htmlspecialchars($row['apellido'] . ' ' . $row['nombre']),
                    'genero' => htmlspecialchars($row['genero']),
                    'nacionalidad' => htmlspecialchars($row['nacionalidad']),
                    'cedula' => htmlspecialchars($row['cedula'] ?? ''),
                    'fecha_nacimiento' => $row['fecha_nacimiento'] ? date('d/m/Y', strtotime($row['fecha_nacimiento'])) : null,
                    'fecha_ingreso' => $row['fecha_ingreso'] ? date('d/m/Y', strtotime($row['fecha_ingreso'])) : null,
                ];
            }
            
            responderJSON([
                'estudiantes' => $estudiantes,
                'total' => count($estudiantes)
            ]);
            
        } catch (Exception $e) {
            logError('Error en búsqueda de estudiantes', [
                'busqueda' => $busqueda,
                'sala' => $sala,
                'seccion' => $seccion_id,
                'error' => $e->getMessage()
            ]);
            responderJSON(['estudiantes' => [], 'error' => 'Error al buscar estudiantes'], 500);
        }
        exit;
    }
    
    // ========================================================================
    // VERIFICAR EGRESO DUPLICADO
    // ========================================================================
    if ($action === 'verificar_egreso') {
        $estudiante_id = filter_var($_POST['estudiante_id'] ?? 0, FILTER_VALIDATE_INT);
        $periodo = sanitizarEntrada($_POST['periodo'] ?? date('Y-m'));
        
        if (!$estudiante_id) {
            responderJSON(['existe' => false, 'error' => 'ID de estudiante inválido']);
        }
        
        try {
            $stmt = $conexion->prepare("SELECT COUNT(*) as existe FROM egresos WHERE estudiante_id = ? AND periodo = ?");
            $stmt->bind_param("is", $estudiante_id, $periodo);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            responderJSON(['existe' => (int)$row['existe'] > 0]);
            
        } catch (Exception $e) {
            logError('Error al verificar egreso', ['estudiante' => $estudiante_id, 'error' => $e->getMessage()]);
            responderJSON(['existe' => false, 'error' => 'Error al verificar'], 500);
        }
        exit;
    }
    
    // ========================================================================
    // CONFIRMAR EGRESO (GUARDAR INMEDIATAMENTE)
    // ========================================================================
    if ($action === 'confirmar_egreso') {
        $estudiante_id = filter_var($_POST['estudiante_id'] ?? 0, FILTER_VALIDATE_INT);
        $sala = sanitizarEntrada($_POST['sala'] ?? '');
        $seccion_id = filter_var($_POST['seccion_id'] ?? 0, FILTER_VALIDATE_INT);
        $periodo = sanitizarEntrada($_POST['periodo'] ?? '');
        $fecha_egreso = sanitizarEntrada($_POST['fecha_egreso'] ?? '');
        $motivo = sanitizarEntrada($_POST['motivo'] ?? '');
        $genero = sanitizarEntrada($_POST['genero'] ?? '');

        if (!$estudiante_id || empty($sala) || !$seccion_id || empty($periodo) || empty($fecha_egreso)) {
            responderJSON(['success' => false, 'error' => 'Faltan datos obligatorios'], 400);
        }

        try {
            $conexion->begin_transaction();

            // 1. Insertar en egresos
            $stmt = $conexion->prepare("
                INSERT INTO egresos (estudiante_id, sala, seccion_id, periodo, fecha_egreso, motivo, genero) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iisssss", $estudiante_id, $sala, $seccion_id, $periodo, $fecha_egreso, $motivo, $genero);
            $stmt->execute();
            $stmt->close();

            // 2. Actualizar estudiante a Inactivo
            $stmt_up = $conexion->prepare("UPDATE estudiantes SET estatus = 'Inactivo' WHERE id = ?");
            $stmt_up->bind_param("i", $estudiante_id);
            $stmt_up->execute();
            $stmt_up->close();

            $conexion->commit();
            responderJSON(['success' => true]);
        } catch (Exception $e) {
            $conexion->rollback();
            logError('Error al confirmar egreso', ['estudiante_id' => $estudiante_id, 'error' => $e->getMessage()]);
            responderJSON(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
        exit;
    }
    
    // ========================================================================
    // CONFIRMAR INGRESO (GUARDAR INDIVIDUALMENTE)
    // ========================================================================
    if ($action === 'confirmar_ingreso') {
        $sala = sanitizarEntrada($_POST['sala'] ?? '');
        $seccion_id = filter_var($_POST['seccion_id'] ?? 0, FILTER_VALIDATE_INT);
        $periodo = sanitizarEntrada($_POST['periodo'] ?? '');
        $apellido = sanitizarEntrada($_POST['apellido'] ?? '');
        $nombre = sanitizarEntrada($_POST['nombre'] ?? '');
        $genero = sanitizarEntrada($_POST['genero'] ?? 'V');
        $nacionalidad = sanitizarEntrada($_POST['nacionalidad'] ?? 'Venezolana');
        $ci = sanitizarEntrada($_POST['ci'] ?? '');
        $fn = sanitizarEntrada($_POST['fn'] ?? '');
        $fi = sanitizarEntrada($_POST['fi'] ?? '');

        if (empty($apellido) && empty($nombre)) {
            responderJSON(['success' => false, 'error' => 'Datos incompletos'], 400);
        }

        try {
            $stmt = $conexion->prepare("
                INSERT INTO ingresos (sala, seccion_id, periodo, apellido, nombre, genero, nacionalidad, ci, fecha_nacimiento, fecha_ingreso, confirmado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->bind_param("sissssssss", $sala, $seccion_id, $periodo, $apellido, $nombre, $genero, $nacionalidad, $ci, $fn, $fi);
            $stmt->execute();
            responderJSON(['success' => true, 'id' => $stmt->insert_id]);
        } catch (Exception $e) {
            logError('Error al confirmar ingreso', ['error' => $e->getMessage()]);
            responderJSON(['success' => false, 'error' => 'Error al guardar'], 500);
        }
        exit;
    }
    
    // Si la acción no es válida
    responderJSON(['error' => 'Acción no válida'], 400);
    exit;
}

// ============================================================================
// PROCESAR GUARDADO DE DATOS (EGRESOS YA NO SE GUARDAN AQUÍ)
// ============================================================================
$mensaje = '';
$errores_validacion = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_y_pdf'])) {
    // Verificar CSRF
    if (!isset($_POST['csrf_token']) || !verificarTokenCSRF($_POST['csrf_token'])) {
        $mensaje = 'Error de seguridad: Token CSRF inválido.';
        $errores_validacion[] = 'CSRF_INVALID';
    }
    
    // Validar datos obligatorios
    $periodo = sanitizarEntrada($_POST['periodo'] ?? '');
    $sala = sanitizarEntrada($_POST['sala'] ?? '');
    $seccion_id = filter_var($_POST['seccion'] ?? 0, FILTER_VALIDATE_INT);
    $profesor_id = filter_var($_POST['profesor'] ?? 0, FILTER_VALIDATE_INT);
    $nombre_docente = sanitizarEntrada($_POST['nombre_docente'] ?? '');
    
    if (empty($periodo) || empty($sala) || !$seccion_id || !$profesor_id) {
        $mensaje = 'Error: Datos incompletos para guardar.';
        $errores_validacion[] = 'DATOS_INCOMPLETOS';
    }
    
    // Si hay errores, mostrar mensaje y continuar (no se guarda)
    if (empty($errores_validacion)) {
        try {
            // Iniciar transacción
            $conexion->begin_transaction();
            
            $anio = date('Y', strtotime($periodo));
            $mes = date('m', strtotime($periodo));
            $dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
            
            // Preparar consultas de asistencia
            $stmt_asist_v = $conexion->prepare("
                INSERT INTO asistencia_diaria (fecha, sala, seccion_id, genero, cantidad) 
                VALUES (?, ?, ?, 'V', ?) 
                ON DUPLICATE KEY UPDATE cantidad = ?
            ");
            $stmt_asist_h = $conexion->prepare("
                INSERT INTO asistencia_diaria (fecha, sala, seccion_id, genero, cantidad) 
                VALUES (?, ?, ?, 'H', ?) 
                ON DUPLICATE KEY UPDATE cantidad = ?
            ");
            
            // Guardar asistencia diaria
            for ($d = 1; $d <= $dias_en_mes; $d++) {
                $fecha = "$anio-$mes-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                $v = filter_var($_POST["asist_v"][$d] ?? 0, FILTER_VALIDATE_INT);
                $h = filter_var($_POST["asist_h"][$d] ?? 0, FILTER_VALIDATE_INT);
                
                $stmt_asist_v->bind_param("ssiii", $fecha, $sala, $seccion_id, $v, $v);
                $stmt_asist_v->execute();
                
                $stmt_asist_h->bind_param("ssiii", $fecha, $sala, $seccion_id, $h, $h);
                $stmt_asist_h->execute();
            }
            
            // Guardar ingresos
            if (isset($_POST['ingreso_apellido']) && is_array($_POST['ingreso_apellido'])) {
                $stmt_ingreso = $conexion->prepare("
                    INSERT INTO ingresos (sala, seccion_id, periodo, apellido, nombre, genero, nacionalidad, ci, fecha_nacimiento, fecha_ingreso) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $count = count($_POST['ingreso_apellido']);
                for ($i = 0; $i < $count; $i++) {
                    $apellido = sanitizarEntrada($_POST['ingreso_apellido'][$i] ?? '');
                    $nombre = sanitizarEntrada($_POST['ingreso_nombre'][$i] ?? '');
                    
                    if (empty($apellido) && empty($nombre)) continue;
                    
                    $genero = sanitizarEntrada($_POST['ingreso_genero'][$i] ?? 'V');
                    $nacionalidad = sanitizarEntrada($_POST['ingreso_nacionalidad'][$i] ?? 'Venezolana');
                    $ci = sanitizarEntrada($_POST['ingreso_ci'][$i] ?? '');
                    $fn = sanitizarEntrada($_POST['ingreso_fn'][$i] ?? '');
                    $fi = sanitizarEntrada($_POST['ingreso_fi'][$i] ?? '');
                    
                    $stmt_ingreso->bind_param(
                        "ssssssssss",
                        $sala, $seccion_id, $periodo, $apellido, $nombre,
                        $genero, $nacionalidad, $ci, $fn, $fi
                    );
                    $stmt_ingreso->execute();
                }
                $stmt_ingreso->close();
            }
            
            // Los egresos ya fueron guardados individualmente por AJAX, no se procesan aquí
            
            // Confirmar transacción
            $conexion->commit();
            $mensaje = '✅ Datos guardados correctamente. Generando PDF...';
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $conexion->rollback();
            logError('Error al guardar datos', ['error' => $e->getMessage()]);
            $mensaje = '❌ Error al guardar los datos: ' . $e->getMessage();
            $errores_validacion[] = 'ERROR_GUARDAR';
        }
    }
    
    // Generar PDF solo si no hay errores
    if (empty($errores_validacion)) {
        include "generar_pdf.php";
        exit;
    }
}

// ============================================================================
// CARGAR VISTA PRINCIPAL
// ============================================================================
include "../includes/header.php";

// Obtener filtros
$sala_seleccionada = sanitizarEntrada($_GET['sala'] ?? '');
$seccion_seleccionada = filter_var($_GET['seccion'] ?? 0, FILTER_VALIDATE_INT);
$profesor_id = filter_var($_GET['profesor'] ?? 0, FILTER_VALIDATE_INT);
$periodo = sanitizarEntrada($_GET['periodo'] ?? date('Y-m'));

$mostrar_tabla = $sala_seleccionada && $profesor_id;

if ($mostrar_tabla) {
    $anio = date('Y', strtotime($periodo));
    $mes_num = date('m', strtotime($periodo));
    $dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $mes_num, $anio);
} else {
    $anio = date('Y');
    $mes_num = date('m');
    $dias_en_mes = 0;
}

$meses_es = [
    "01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril",
    "05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto",
    "09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"
];
$nombre_mes = $meses_es[$mes_num] ?? '';

// Obtener nombre del profesor
$nombre_profesor = 'No seleccionado';
if ($profesor_id) {
    try {
        $stmt_prof = $conexion->prepare("SELECT nombre FROM profesores WHERE id = ?");
        $stmt_prof->bind_param("i", $profesor_id);
        $stmt_prof->execute();
        $prof_data = $stmt_prof->get_result()->fetch_assoc();
        if ($prof_data) {
            $nombre_profesor = htmlspecialchars($prof_data['nombre'], ENT_QUOTES, 'UTF-8');
        }
        $stmt_prof->close();
    } catch (Exception $e) {
        logError('Error al cargar profesor', ['id' => $profesor_id, 'error' => $e->getMessage()]);
    }
}

$es_inicial = in_array($sala_seleccionada, ['sala4', 'sala5']);
$edades = $es_inicial ? [4,5,6] : range(6,15);

// Generar token CSRF para el formulario
$csrf_token = generarTokenCSRF();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Asistencia</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --navy: #002d54;
            --weekend-bg: #343a40;
            --primary-gradient: linear-gradient(135deg, #002d54 0%, #004a7c 100%);
        }
        
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: box-shadow 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 6px 30px rgba(0,0,0,0.12);
        }
        .card-header {
            background: var(--primary-gradient) !important;
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 14px 20px;
        }
        
        .table-asistencia {
            font-size: 0.75rem;
            text-align: center;
        }
        .table-asistencia th, .table-asistencia td {
            vertical-align: middle;
            padding: 4px !important;
            border-color: #dee2e6;
        }
        .weekend {
            background-color: var(--weekend-bg) !important;
            color: white !important;
        }
        .weekend-cell {
            background-color: #212529 !important;
            cursor: not-allowed;
        }
        
        .asist-input {
            border: none !important;
            background: transparent;
            text-align: center;
            width: 100%;
            font-weight: bold;
            height: 32px;
            transition: background-color 0.2s ease;
        }
        .asist-input:focus {
            background-color: #fff9c4 !important;
            outline: 2px solid #ffc107;
            outline-offset: -2px;
        }
        .asist-input:hover {
            background-color: #f8f9fa;
        }
        
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }
        
        .bg-navy {
            background-color: var(--navy) !important;
        }
        .matricula-box input {
            width: 50px;
            border: 2px solid #ced4da;
            border-radius: 6px;
            text-align: center;
            font-weight: bold;
            transition: border-color 0.2s ease;
        }
        .matricula-box input:focus {
            border-color: #ffc107;
            outline: none;
        }
        
        .search-container {
            position: relative;
        }
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 280px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ced4da;
            border-radius: 8px;
            z-index: 1050;
            display: none;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        .search-results.show {
            display: block;
            animation: slideDown 0.25s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .search-result-item {
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.15s ease;
        }
        .search-result-item:hover {
            background-color: #e8f4f8;
            transform: translateX(4px);
        }
        .search-result-item:last-child {
            border-bottom: none;
        }
        .search-result-item .ci-info {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .search-result-item mark {
            background-color: #fff3cd;
            padding: 0 3px;
            border-radius: 2px;
        }
        .search-result-item .badge-genero {
            font-size: 0.7rem;
            padding: 3px 10px;
        }
        .search-counter {
            padding: 8px 12px;
            background: #f8f9fa;
            font-size: 0.8rem;
            color: #6c757d;
            text-align: center;
            border-top: 1px solid #eee;
            font-weight: 500;
        }
        .search-loading {
            padding: 25px;
            text-align: center;
            color: #6c757d;
        }
        .search-empty {
            padding: 25px;
            text-align: center;
            color: #6c757d;
        }
        
        .campo-rellenado {
            animation: highlightField 0.6s ease;
        }
        @keyframes highlightField {
            0% { background-color: #d4edda; }
            100% { background-color: transparent; }
        }
        
        .table-responsive-asistencia {
            max-height: 600px;
            overflow-y: auto;
        }
        .table-responsive-asistencia thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .btn-primary-gradient {
            background: var(--primary-gradient);
            border: none;
            color: white;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,45,84,0.3);
            color: white;
        }
        
        @media (max-width: 768px) {
            .table-asistencia {
                font-size: 0.6rem;
            }
            .asist-input {
                height: 24px;
                font-size: 0.6rem;
            }
            .matricula-box input {
                width: 35px;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    
    <?php if ($mensaje): ?>
        <div class="alert <?= empty($errores_validacion) ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>
    
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h4 class="mb-0 text-navy">
            <i class="fas fa-calendar-check text-primary"></i> Control de Asistencia
        </h4>
        <div>
            <a href="egresados.php" class="btn btn-outline-warning me-2">
                <i class="fas fa-user-times"></i> Ver Egresados
            </a>
            <a href="historial_resumenes.php" class="btn btn-info">
                <i class="fas fa-chart-line"></i> Historial de Resúmenes
            </a>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end" id="filtroForm" autocomplete="off">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted" for="select-grado">
                        <i class="fas fa-graduation-cap"></i> Sala / Grado
                    </label>
                    <select name="sala" id="select-grado" class="form-select shadow-none" onchange="actualizarFiltros()">
                        <option value="">Seleccione grado...</option>
                        <optgroup label="Educación Inicial">
                            <option value="sala4" <?= ($sala_seleccionada == 'sala4') ? 'selected' : '' ?>>Sala 4 Años</option>
                            <option value="sala5" <?= ($sala_seleccionada == 'sala5') ? 'selected' : '' ?>>Sala 5 Años</option>
                        </optgroup>
                        <optgroup label="Educación Primaria">
                            <?php for($i=1; $i<=6; $i++): 
                                $val = ($i==1) ? "1ro" : (($i==2) ? "2do" : (($i==3) ? "3ro" : $i."to")); ?>
                                <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>" <?= ($sala_seleccionada == $val) ? 'selected' : '' ?>><?= $i ?>° Grado</option>
                            <?php endfor; ?>
                        </optgroup>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="small fw-bold text-muted" for="select-seccion">
                        <i class="fas fa-layer-group"></i> Sección
                    </label>
                    <select name="seccion" id="select-seccion" class="form-select shadow-none" <?= empty($sala_seleccionada) ? 'disabled' : '' ?> onchange="actualizarDocentes()">
                        <option value="">Seleccione sección...</option>
                        <?php 
                        if($sala_seleccionada) {
                            try {
                                $stmt_sec = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
                                $stmt_sec->bind_param("s", $sala_seleccionada);
                                $stmt_sec->execute();
                                $result_sec = $stmt_sec->get_result();
                                while($sec = $result_sec->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($sec['id'], ENT_QUOTES, 'UTF-8') ?>" <?= ($seccion_seleccionada == $sec['id']) ? 'selected' : '' ?>><?= htmlspecialchars($sec['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endwhile;
                                $stmt_sec->close();
                            } catch (Exception $e) {
                                logError('Error al cargar secciones en filtro', ['sala' => $sala_seleccionada, 'error' => $e->getMessage()]);
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="small fw-bold text-muted" for="select-docente">
                        <i class="fas fa-user-tie"></i> Profesor / Docente
                    </label>
                    <select name="profesor" id="select-docente" class="form-select shadow-none" <?= empty($seccion_seleccionada) ? 'disabled' : '' ?>>
                        <option value="">Seleccione docente...</option>
                        <?php 
                        if($seccion_seleccionada) {
                            try {
                                $stmt_p = $conexion->prepare("SELECT id, nombre FROM profesores WHERE seccion = ? AND estatus = 'Activo' ORDER BY nombre ASC");
                                $stmt_p->bind_param("i", $seccion_seleccionada);
                                $stmt_p->execute();
                                $result_p = $stmt_p->get_result();
                                while($p = $result_p->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8') ?>" <?= ($profesor_id == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endwhile; 
                                $stmt_p->close();
                            } catch (Exception $e) {
                                logError('Error al cargar docentes en filtro', ['seccion' => $seccion_seleccionada, 'error' => $e->getMessage()]);
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="small fw-bold text-muted" for="select-mes">
                        <i class="fas fa-calendar-alt"></i> Mes
                    </label>
                    <input type="month" name="periodo" id="select-mes" class="form-control shadow-none" value="<?= htmlspecialchars($periodo, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary-gradient px-4">
                        <i class="fas fa-search me-2"></i> Cargar Datos
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($mostrar_tabla): 
        // Calcular días hábiles
        $d_hab = 0;
        for($d=1; $d<=$dias_en_mes; $d++) {
            $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
            if($n_dia != 0 && $n_dia != 6) $d_hab++;
        }
        
        // Cargar asistencia existente
        $asistencia_existente = [];
        try {
            $stmt_asist = $conexion->prepare("SELECT fecha, genero, cantidad FROM asistencia_diaria WHERE sala = ? AND seccion_id = ? AND YEAR(fecha) = ? AND MONTH(fecha) = ?");
            $stmt_asist->bind_param("siii", $sala_seleccionada, $seccion_seleccionada, $anio, $mes_num);
            $stmt_asist->execute();
            $result_asist = $stmt_asist->get_result();
            while($row = $result_asist->fetch_assoc()) {
                $dia = (int)date('j', strtotime($row['fecha']));
                $asistencia_existente[$row['genero']][$dia] = (int)$row['cantidad'];
            }
            $stmt_asist->close();
        } catch (Exception $e) {
            logError('Error al cargar asistencia', ['sala' => $sala_seleccionada, 'seccion' => $seccion_seleccionada, 'error' => $e->getMessage()]);
        }
    ?>
    
    <form method="POST" id="form-tabla" target="_blank" novalidate>
        <input type="hidden" name="guardar_y_pdf" value="1">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="sala" value="<?= htmlspecialchars($sala_seleccionada, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="seccion" value="<?= htmlspecialchars($seccion_seleccionada, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="profesor" value="<?= htmlspecialchars($profesor_id, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="nombre_docente" value="<?= htmlspecialchars($nombre_profesor, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" id="tipo_reporte" name="tipo_reporte" value="<?= $es_inicial ? 'inicial' : 'regular' ?>">
        <input type="hidden" name="porcentaje_v" id="porcentaje_v" value="0">
        <input type="hidden" name="porcentaje_h" id="porcentaje_h" value="0">
        <input type="hidden" name="porcentaje_total" id="porcentaje_total" value="100">
        
        <!-- Tabla de Asistencia -->
        <div class="card shadow mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h6 class="m-0 fw-bold text-uppercase">
                        <i class="fas fa-clipboard-list me-2"></i>
                        Control de Asistencia: <?= htmlspecialchars(strtoupper($nombre_mes), ENT_QUOTES, 'UTF-8') ?> <?= $anio ?>
                    </h6>
                    <small class="opacity-75">
                        <i class="fas fa-user me-1"></i> Docente: <?= htmlspecialchars(strtoupper($nombre_profesor), ENT_QUOTES, 'UTF-8') ?>
                        <span class="mx-2">|</span>
                        <i class="fas fa-school me-1"></i> <?= htmlspecialchars(strtoupper($sala_seleccionada), ENT_QUOTES, 'UTF-8') ?>
                        <?php if($seccion_seleccionada): ?> - Sec. <?= htmlspecialchars(strtoupper($seccion_seleccionada), ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                    </small>
                </div>
                <div class="d-flex gap-3 align-items-center mt-2 mt-md-0">
                    <button type="button" class="btn btn-sm btn-danger fw-bold" onclick="limpiarTodo()">
                        <i class="fas fa-eraser me-1"></i> LIMPIAR
                    </button>
                    <div class="bg-white text-dark px-3 py-1 rounded small fw-bold matricula-box">
                        <i class="fas fa-users me-1"></i>
                        V <input type="number" min="0" name="mat_v" id="mat_v" value="" oninput="recalcular()" aria-label="Matrícula varones"> 
                        H <input type="number" min="0" name="mat_h" id="mat_h" value="" oninput="recalcular()" aria-label="Matrícula hembras">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive table-responsive-asistencia">
                <table class="table table-bordered align-middle mb-0 table-asistencia" id="tablaAsistencia">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th rowspan="2" width="50">SEXO</th>
                            <?php 
                            for($d=1; $d<=$dias_en_mes; $d++) {
                                $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                                $es_fin = ($n_dia == 0 || $n_dia == 6);
                                $letra = ['D','L','M','M','J','V','S'][$n_dia];
                                $clase = $es_fin ? 'weekend' : '';
                                echo "<th class='$clase' style='font-size:0.7rem; min-width:35px;'>
                                        $letra<br><small>$d</small>
                                      </th>";
                            }
                            ?>
                            <th rowspan="2" class="bg-primary text-white" style="min-width:60px;">TOTAL</th>
                            <th rowspan="2" class="bg-success text-white" style="min-width:70px;">% ASIST.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold bg-light">
                                <i class="fas fa-male text-primary"></i> V
                            </td>
                            <?php for($d=1; $d<=$dias_en_mes; $d++): 
                                $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                                $es_fin = ($n_dia == 0 || $n_dia == 6);
                                $valor_guardado = $asistencia_existente['V'][$d] ?? ''; ?>
                                <td class="<?= $es_fin ? 'weekend-cell' : 'p-0' ?>">
                                    <?php if(!$es_fin): ?>
                                        <input type="number" min="0" name="asist_v[<?= $d ?>]" 
                                               class="asist-input in-v" data-dia="<?= $d ?>" 
                                               value="<?= $valor_guardado ?>" 
                                               oninput="recalcular()" onblur="limpiarCero(this)"
                                               aria-label="Asistencia varones día <?= $d ?>">
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                            <td id="res_total_v" class="fw-bold bg-primary text-white">0</td>
                            <td id="res_porc_v" class="fw-bold text-primary">0%</td>
                        </tr>
                        
                        <tr>
                            <td class="fw-bold bg-light">
                                <i class="fas fa-female text-danger"></i> H
                            </td>
                            <?php for($d=1; $d<=$dias_en_mes; $d++): 
                                $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                                $es_fin = ($n_dia == 0 || $n_dia == 6);
                                $valor_guardado = $asistencia_existente['H'][$d] ?? ''; ?>
                                <td class="<?= $es_fin ? 'weekend-cell' : 'p-0' ?>">
                                    <?php if(!$es_fin): ?>
                                        <input type="number" min="0" name="asist_h[<?= $d ?>]" 
                                               class="asist-input in-h" data-dia="<?= $d ?>" 
                                               value="<?= $valor_guardado ?>" 
                                               oninput="recalcular()" onblur="limpiarCero(this)"
                                               aria-label="Asistencia hembras día <?= $d ?>">
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                            <td id="res_total_h" class="fw-bold bg-primary text-white">0</td>
                            <td id="res_porc_h" class="fw-bold text-primary">0%</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td class="bg-navy text-white">
                                <i class="fas fa-calculator"></i> TOTAL
                            </td>
                            <?php for($d=1; $d<=$dias_en_mes; $d++): ?>
                                <td id="total_dia_<?= $d ?>" class="bg-navy text-white">-</td>
                            <?php endfor; ?>
                            <td id="gran_total_asist" class="bg-dark text-white fw-bold">0</td>
                            <td id="gran_total_porc" class="bg-dark text-white fw-bold">0%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center py-3">
                <input type="hidden" id="dias_hab_val" value="<?= $d_hab ?>">
                <span class="text-muted small">
                    <i class="fas fa-calendar-day me-1"></i> Días Hábiles: <b><?= $d_hab ?></b>
                </span>
                <span class="h6 mb-0 text-navy">
                    <i class="fas fa-chart-simple me-1"></i> Promedio Diario: <b id="promedio_total">0.0</b>
                </span>
            </div>
        </div>
        
        <!-- Clasificación por Nacionalidad, Edad y Sexo -->
        <div class="card mb-4">
            <div class="card-header bg-navy text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-flag me-2"></i> Clasificación por Nacionalidad, Edad y Sexo</h6>
                <span class="small opacity-75"><i class="fas fa-sync-alt me-1"></i> Los totales se calculan automáticamente</span>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm tabla-edades">
                        <colgroup>
                            <col style="width:8%"><col style="width:6%"><col style="width:6%"><col style="width:10%">
                            <col style="width:8%"><col style="width:6%"><col style="width:6%"><col style="width:10%">
                        </colgroup>
                        <thead class="table-light">
                            <tr>
                                <th colspan="4" class="text-center bg-primary text-white">Venezolano</th>
                                <th colspan="4" class="text-center bg-info text-white">Extranjero</th>
                            </tr>
                            <tr>
                                <th>Edad</th><th>V</th><th>H</th><th>Total</th>
                                <th>Edad</th><th>V</th><th>H</th><th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($edades as $edad): ?>
                            <tr class="fila-edad">
                                <td><strong><?= $edad ?></strong></td>
                                <td><input type="number" class="form-control form-control-sm text-center ven-v" name="venezolano_v[<?= $edad ?>]" value="" oninput="calcularTotalFila(this, 'venezolano')" onblur="limpiarCero(this)"></td>
                                <td><input type="number" class="form-control form-control-sm text-center ven-h" name="venezolano_h[<?= $edad ?>]" value="" oninput="calcularTotalFila(this, 'venezolano')" onblur="limpiarCero(this)"></td>
                                <td><input type="text" class="form-control form-control-sm text-center ven-total bg-light" readonly></td>
                                <td><strong><?= $edad ?></strong></td>
                                <td><input type="number" class="form-control form-control-sm text-center ext-v" name="extranjero_v[<?= $edad ?>]" value="" oninput="calcularTotalFila(this, 'extranjero')" onblur="limpiarCero(this)"></td>
                                <td><input type="number" class="form-control form-control-sm text-center ext-h" name="extranjero_h[<?= $edad ?>]" value="" oninput="calcularTotalFila(this, 'extranjero')" onblur="limpiarCero(this)"></td>
                                <td><input type="text" class="form-control form-control-sm text-center ext-total bg-light" readonly></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Ingreso del Mes -->
        <div class="card mb-4">
            <div class="card-header bg-navy text-white">
                <h6 class="mb-0"><i class="fas fa-user-plus me-2"></i> Ingreso del Mes</h6>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="tablaIngresos">
                        <thead class="table-light">
                            <tr>
                                <th>Apellido y Nombre</th>
                                <th>Género</th>
                                <th>Nacionalidad</th>
                                <th>CI o CE</th>
                                <th>F.N</th>
                                <th>F.I</th>
                                <th style="width:120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="fila-ingreso" data-confirmada="false">
                                <td>
                                    <input type="text" name="ingreso_apellido[]" class="form-control form-control-sm campo-apellido" placeholder="Apellido">
                                    <input type="text" name="ingreso_nombre[]" class="form-control form-control-sm mt-1 campo-nombre" placeholder="Nombre">
                                </td>
                                <td>
                                    <select name="ingreso_genero[]" class="form-select form-select-sm campo-genero">
                                        <option value="V">Varón (V)</option>
                                        <option value="H">Hembra (H)</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="ingreso_nacionalidad[]" class="form-select form-select-sm campo-nacionalidad">
                                        <option value="Venezolana">Venezolana</option>
                                        <option value="Extranjera">Extranjera</option>
                                    </select>
                                </td>
                                <td><input type="text" name="ingreso_ci[]" class="form-control form-control-sm campo-ci" placeholder="Cédula" maxlength="11"></td>
                                <td><input type="date" name="ingreso_fn[]" class="form-control form-control-sm campo-fn"></td>
                                <td><input type="date" name="ingreso_fi[]" class="form-control form-control-sm campo-fi"></td>
                                <td style="white-space: nowrap;">
                                    <button type="button" class="btn btn-sm btn-outline-success btn-confirmar-ingreso" 
                                            title="Confirmar ingreso" style="display:none;">
                                        <i class="fas fa-check"></i> Confirmar
                                    </button>
                                    <span class="badge bg-success estado-confirmado" style="display:none;">
                                        <i class="fas fa-check-circle"></i> Confirmado
                                    </span>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="eliminarFila(this, 'ingreso')" aria-label="Eliminar ingreso">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="agregarFila('ingreso')">
                    <i class="fas fa-plus me-1"></i> Agregar Ingreso
                </button>
            </div>
        </div>
        
        <!-- Egresos con Búsqueda Inteligente y Confirmación -->
        <div class="card mb-4">
            <div class="card-header bg-navy text-white">
                <h6 class="mb-0"><i class="fas fa-user-minus me-2"></i> Egresos del Mes</h6>
            </div>
            <div class="card-body p-3">
                <div class="alert alert-info small mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Busque estudiantes activos</strong> por apellido, nombre o cédula. 
                    Al seleccionar uno, haga clic en <strong>"Confirmar"</strong> para registrar el egreso. 
                    <strong>La búsqueda se limita al grado y sección seleccionados.</strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="tablaEgresos">
                        <thead class="table-light">
                            <tr>
                                <th style="width:28%">Buscar Estudiante</th>
                                <th style="width:8%">Género</th>
                                <th style="width:12%">CI</th>
                                <th style="width:10%">F. Nacimiento</th>
                                <th style="width:10%">F. Egreso</th>
                                <th style="width:18%">Motivo</th>
                                <th style="width:8%">Confirmar</th>
                                <th style="width:6%"></th>
                            </tr>
                        </thead>
                        <tbody id="egresos-tbody">
                            <tr class="fila-egreso" data-id="0" data-confirmada="false">
                                <td class="search-container">
                                    <input type="text" class="form-control form-control-sm busqueda-estudiante" 
                                           placeholder="Apellido, nombre o cédula..." autocomplete="off">
                                    <input type="hidden" name="egreso_estudiante_id[]" class="estudiante-id-input" value="">
                                    <div class="search-results" role="listbox"></div>
                                </td>
                                <td><input type="text" name="egreso_genero[]" class="form-control form-control-sm campo-genero" readonly></td>
                                <td><input type="text" name="egreso_ci[]" class="form-control form-control-sm campo-ci" readonly></td>
                                <td><input type="text" name="egreso_fn[]" class="form-control form-control-sm campo-fn" readonly></td>
                                <td><input type="date" name="egreso_fecha[]" class="form-control form-control-sm campo-fecha-egreso" value="<?= date('Y-m-d') ?>"></td>
                                <td>
                                    <select name="egreso_motivo[]" class="form-select form-select-sm campo-motivo">
                                        <option value="">Seleccione motivo...</option>
                                        <option value="Cambio de domicilio">Cambio de domicilio</option>
                                        <option value="Cambio de institución">Cambio de institución</option>
                                        <option value="Problemas económicos">Problemas económicos</option>
                                        <option value="Enfermedad">Enfermedad</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-success btn-confirmar-egreso" 
                                            title="Confirmar egreso" style="display:none;">
                                        <i class="fas fa-check"></i> Confirmar
                                    </button>
                                    <span class="badge bg-success estado-confirmado" style="display:none;">
                                        <i class="fas fa-check-circle"></i> Confirmado
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="eliminarFilaEgreso(this)" aria-label="Eliminar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="agregarFila('egreso')">
                    <i class="fas fa-plus me-1"></i> Agregar Egreso
                </button>
            </div>
        </div>
        
        <!-- Observaciones -->
        <div class="card mb-4">
            <div class="card-header bg-navy text-white">
                <h6 class="mb-0"><i class="fas fa-comment me-2"></i> Observaciones Relevantes</h6>
            </div>
            <div class="card-body">
                <textarea name="observaciones" class="form-control" rows="4" placeholder="Escriba aquí las observaciones..."></textarea>
                <div class="text-end mt-2 text-muted small">
                    <i class="fas fa-user-tie me-1"></i> Director(a)
                </div>
            </div>
        </div>
        
        <!-- Botón de guardar -->
        <div class="text-center mt-4 mb-4">
            <button type="submit" class="btn btn-success px-5 py-2 fw-bold" style="border-radius: 30px; font-size: 1.1rem;">
                <i class="fas fa-save me-2"></i> GUARDAR Y GENERAR PDF
            </button>
            <br>
            <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Solo los egresos confirmados se guardarán</small>
        </div>
    </form>
    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- JavaScript -->
<script>
// ============================================================================
// APLICACIÓN PRINCIPAL
// ============================================================================
const AppAsistencia = {
    _searchTimeout: null,
    
    elementos: {
        sala: document.getElementById('select-grado'),
        seccion: document.getElementById('select-seccion'),
        docente: document.getElementById('select-docente'),
        mes: document.getElementById('select-mes')
    },
    
    actualizarFiltros: function() {
        const sala = this.elementos.sala.value;
        const seccionSelect = this.elementos.seccion;
        
        seccionSelect.innerHTML = '<option value="">Cargando secciones...</option>';
        seccionSelect.disabled = true;
        
        if (!sala) {
            seccionSelect.innerHTML = '<option value="">Primero seleccione grado...</option>';
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'cargar_secciones');
        formData.append('sala', sala);
        
        fetch('index.php?ajax=1', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                seccionSelect.innerHTML = '<option value="">Seleccione sección...</option>';
                if (data.secciones && data.secciones.length > 0) {
                    data.secciones.forEach(sec => seccionSelect.add(new Option(sec.nombre, sec.id)));
                    seccionSelect.disabled = false;
                } else {
                    seccionSelect.innerHTML = '<option value="">No hay secciones disponibles</option>';
                }
            })
            .catch(err => {
                console.error('Error al cargar secciones:', err);
                seccionSelect.innerHTML = '<option value="">Error al cargar secciones</option>';
                this.mostrarError('Error al cargar las secciones');
            });
    },
    
    actualizarDocentes: function() {
        const seccion = this.elementos.seccion.value;
        const docenteSelect = this.elementos.docente;
        
        docenteSelect.innerHTML = '<option value="">Cargando docentes...</option>';
        docenteSelect.disabled = true;
        
        if (!seccion) {
            docenteSelect.innerHTML = '<option value="">Primero seleccione sección...</option>';
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'cargar_docentes');
        formData.append('seccion', seccion);
        
        fetch('index.php?ajax=1', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                docenteSelect.innerHTML = '<option value="">Seleccione docente...</option>';
                if (data.docentes && data.docentes.length > 0) {
                    data.docentes.forEach(d => docenteSelect.add(new Option(d.nombre, d.id)));
                    docenteSelect.disabled = false;
                } else {
                    docenteSelect.innerHTML = '<option value="">No hay docentes activos</option>';
                }
            })
            .catch(err => {
                console.error('Error al cargar docentes:', err);
                docenteSelect.innerHTML = '<option value="">Error al cargar docentes</option>';
                this.mostrarError('Error al cargar los docentes');
            });
    },
    
    buscarEstudiante: function(input, fila) {
        const busqueda = input.value.trim();
        const resultadosDiv = fila.querySelector('.search-results');
        
        if (this._searchTimeout) clearTimeout(this._searchTimeout);
        
        if (busqueda.length < 2) {
            resultadosDiv.classList.remove('show');
            resultadosDiv.innerHTML = '';
            return;
        }
        
        resultadosDiv.innerHTML = `
            <div class="search-loading">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Buscando estudiantes...
            </div>
        `;
        resultadosDiv.classList.add('show');
        
        this._searchTimeout = setTimeout(() => {
            const formData = new FormData();
            formData.append('action', 'buscar_estudiante');
            formData.append('busqueda', busqueda);
            formData.append('sala', this.elementos.sala.value);
            formData.append('seccion', this.elementos.seccion.value);
            
            fetch('index.php?ajax=1', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    resultadosDiv.innerHTML = '';
                    
                    if (!data.estudiantes || data.estudiantes.length === 0) {
                        const salaSel = this.elementos.sala.options[this.elementos.sala.selectedIndex]?.text || 'sin seleccionar';
                        const seccionSel = this.elementos.seccion.options[this.elementos.seccion.selectedIndex]?.text || 'sin seleccionar';
                        resultadosDiv.innerHTML = `
                            <div class="search-empty">
                                <i class="fas fa-user-slash fa-2x mb-2 text-muted"></i>
                                <p class="mb-1">No se encontraron estudiantes</p>
                                <small>
                                    <strong>Filtros activos:</strong> ${salaSel} – Sección ${seccionSel}<br>
                                    Verifique que la sala y sección seleccionadas coincidan con las del estudiante.
                                </small>
                            </div>
                        `;
                        return;
                    }
                    
                    data.estudiantes.forEach(est => {
                        const div = document.createElement('div');
                        div.className = 'search-result-item';
                        div.setAttribute('role', 'option');
                        div.innerHTML = `
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <strong>${this.resaltarTexto(est.nombre_completo, busqueda)}</strong>
                                    <div class="ci-info">
                                        <i class="fas fa-id-card me-1"></i> CI: ${est.ci || 'No registrada'}
                                        <span class="mx-1">|</span>
                                        <i class="fas fa-flag me-1"></i> ${est.nacionalidad}
                                    </div>
                                </div>
                                <span class="badge ${est.genero === 'V' ? 'bg-primary' : 'bg-danger'} badge-genero">
                                    ${est.genero === 'V' ? '♂ Varón' : '♀ Hembra'}
                                </span>
                            </div>
                        `;
                        div.addEventListener('click', () => this.seleccionarEstudiante(fila, est));
                        resultadosDiv.appendChild(div);
                    });
                    
                    const contador = document.createElement('div');
                    contador.className = 'search-counter';
                    contador.innerHTML = `<i class="fas fa-search me-1"></i> ${data.total} estudiante(s) encontrado(s)`;
                    resultadosDiv.appendChild(contador);
                })
                .catch(err => {
                    console.error('Error en búsqueda:', err);
                    resultadosDiv.innerHTML = `<div class="search-error"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p class="mb-0">Error al buscar</p><small>Intente nuevamente</small></div>`;
                });
        }, 350);
    },
    
    resaltarTexto: function(texto, busqueda) {
        if (!busqueda || busqueda.length < 2) return texto;
        const escaped = busqueda.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${escaped})`, 'gi');
        return texto.replace(regex, '<mark>$1</mark>');
    },
    
    seleccionarEstudiante: function(fila, estudiante) {
        const campos = {
            idInput: fila.querySelector('.estudiante-id-input'),
            busquedaInput: fila.querySelector('.busqueda-estudiante'),
            generoInput: fila.querySelector('.campo-genero'),
            ciInput: fila.querySelector('.campo-ci'),
            fnInput: fila.querySelector('.campo-fn')
        };
        
        campos.idInput.value = estudiante.id;
        campos.busquedaInput.value = estudiante.nombre_completo;
        campos.generoInput.value = estudiante.genero === 'V' ? 'Varón' : 'Hembra';
        campos.ciInput.value = estudiante.ci || '';
        campos.fnInput.value = estudiante.fecha_nacimiento || '';
        
        [campos.generoInput, campos.ciInput, campos.fnInput].forEach(el => {
            el.classList.add('campo-rellenado');
            setTimeout(() => el.classList.remove('campo-rellenado'), 600);
        });
        
        // Mostrar botón de confirmar y quitar estado confirmado previo
        const btnConfirmar = fila.querySelector('.btn-confirmar-egreso');
        const estadoConfirmado = fila.querySelector('.estado-confirmado');
        if (btnConfirmar) btnConfirmar.style.display = 'inline-block';
        if (estadoConfirmado) estadoConfirmado.style.display = 'none';
        fila.setAttribute('data-confirmada', 'false');
        
        // Desbloquear campos por si estaban bloqueados
        fila.querySelectorAll('.campo-fecha-egreso, .campo-motivo').forEach(el => el.disabled = false);
        
        // Ocultar resultados
        fila.querySelector('.search-results').classList.remove('show');
    },
    
    verificarEgresoDuplicado: function(estudianteId, callback) {
        const formData = new FormData();
        formData.append('action', 'verificar_egreso');
        formData.append('estudiante_id', estudianteId);
        formData.append('periodo', this.elementos.mes.value || '<?= date('Y-m') ?>');
        
        fetch('index.php?ajax=1', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => callback(data.existe || false))
            .catch(() => callback(false));
    },
    
    mostrarError: function(mensaje) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.innerHTML = `
            <i class="fas fa-exclamation-circle me-2"></i> ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        `;
        document.querySelector('.container-fluid').prepend(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }
};

// ============================================================================
// FUNCIONES GLOBALES
// ============================================================================
function actualizarFiltros() { AppAsistencia.actualizarFiltros(); }
function actualizarDocentes() { AppAsistencia.actualizarDocentes(); }

function limpiarCero(input) {
    if (input.value === '0') input.value = '';
}

function limpiarTodo() {
    if (!confirm('¿Está seguro de limpiar todos los campos de asistencia?')) return;
    document.querySelectorAll('.in-v, .in-h').forEach(input => input.value = '');
    recalcular();
}

// ============================================================================
// RECÁLCULO DE ASISTENCIA
// ============================================================================
function recalcular() {
    const diasHabilesEl = document.getElementById('dias_hab_val');
    const matVEl = document.getElementById('mat_v');
    const matHEl = document.getElementById('mat_h');
    
    const diasHabilesVal = diasHabilesEl ? parseInt(diasHabilesEl.value) || 0 : 0;
    let matVVal = matVEl ? (parseInt(matVEl.value) || 0) : 0;
    let matHVal = matHEl ? (parseInt(matHEl.value) || 0) : 0;
    const matriculaTotal = matVVal + matHVal;
    
    let totalV = 0, totalH = 0;
    const totalesDia = {};
    
    document.querySelectorAll('.in-v').forEach(input => {
        const val = parseInt(input.value) || 0;
        totalV += val;
        const dia = input.dataset.dia;
        totalesDia[dia] = (totalesDia[dia] || 0) + val;
    });
    
    document.querySelectorAll('.in-h').forEach(input => {
        const val = parseInt(input.value) || 0;
        totalH += val;
        const dia = input.dataset.dia;
        totalesDia[dia] = (totalesDia[dia] || 0) + val;
    });
    
    const totalGeneral = totalV + totalH;
    
    const porcV = matriculaTotal > 0 ? Math.round((totalV / matriculaTotal) * 100) : 0;
    const porcH = matriculaTotal > 0 ? Math.round((totalH / matriculaTotal) * 100) : 0;
    const porcTotal = matriculaTotal > 0 ? Math.round((totalGeneral / matriculaTotal) * 100) : 0;
    
    document.getElementById('res_total_v').textContent = totalV;
    document.getElementById('res_total_h').textContent = totalH;
    document.getElementById('gran_total_asist').textContent = totalGeneral;
    document.getElementById('res_porc_v').textContent = porcV + '%';
    document.getElementById('res_porc_h').textContent = porcH + '%';
    document.getElementById('gran_total_porc').textContent = porcTotal + '%';
    
    document.getElementById('porcentaje_v').value = porcV;
    document.getElementById('porcentaje_h').value = porcH;
    document.getElementById('porcentaje_total').value = porcTotal;
    
    const promedio = diasHabilesVal > 0 ? (totalGeneral / diasHabilesVal).toFixed(1) : '0.0';
    const promedioTotalEl = document.getElementById('promedio_total');
    if (promedioTotalEl) promedioTotalEl.textContent = promedio;
    
    for(let d = 1; d <= 31; d++) {
        const td = document.getElementById('total_dia_' + d);
        if (td) td.textContent = (totalesDia[d] !== undefined) ? totalesDia[d] : '-';
    }
}

// ============================================================================
// CÁLCULO DE FILAS DE EDADES
// ============================================================================
function calcularTotalFila(input, tipo) {
    const fila = input.closest('tr');
    const prefijo = tipo === 'venezolano' ? 'ven' : 'ext';
    const v = parseInt(fila.querySelector(`.${prefijo}-v`)?.value) || 0;
    const h = parseInt(fila.querySelector(`.${prefijo}-h`)?.value) || 0;
    const totalInput = fila.querySelector(`.${prefijo}-total`);
    if (totalInput) totalInput.value = v + h;
}

// ============================================================================
// MANEJO DE FILAS DINÁMICAS
// ============================================================================
function agregarFila(tipo) {
    if (tipo === 'ingreso') {
        const tbody = document.querySelector('#tablaIngresos tbody');
        const fila = document.createElement('tr');
        fila.className = 'fila-ingreso';
        fila.setAttribute('data-confirmada', 'false');
        fila.innerHTML = `
            <td>
                <input type="text" name="ingreso_apellido[]" class="form-control form-control-sm campo-apellido" placeholder="Apellido">
                <input type="text" name="ingreso_nombre[]" class="form-control form-control-sm mt-1 campo-nombre" placeholder="Nombre">
            </td>
            <td>
                <select name="ingreso_genero[]" class="form-select form-select-sm campo-genero">
                    <option value="V">Varón (V)</option>
                    <option value="H">Hembra (H)</option>
                </select>
            </td>
            <td>
                <select name="ingreso_nacionalidad[]" class="form-select form-select-sm campo-nacionalidad">
                    <option value="Venezolana">Venezolana</option>
                    <option value="Extranjera">Extranjera</option>
                </select>
            </td>
            <td><input type="text" name="ingreso_ci[]" class="form-control form-control-sm campo-ci" placeholder="Cédula" maxlength="11"></td>
            <td><input type="date" name="ingreso_fn[]" class="form-control form-control-sm campo-fn"></td>
            <td><input type="date" name="ingreso_fi[]" class="form-control form-control-sm campo-fi"></td>
            <td style="white-space: nowrap;">
                <button type="button" class="btn btn-sm btn-outline-success btn-confirmar-ingreso" 
                        title="Confirmar ingreso" style="display:none;">
                    <i class="fas fa-check"></i> Confirmar
                </button>
                <span class="badge bg-success estado-confirmado" style="display:none;">
                    <i class="fas fa-check-circle"></i> Confirmado
                </span>
                <button type="button" class="btn btn-sm btn-danger" onclick="eliminarFila(this, 'ingreso')" aria-label="Eliminar ingreso">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        tbody.appendChild(fila);
    } else if (tipo === 'egreso') {
        const tbody = document.getElementById('egresos-tbody');
        const fila = document.createElement('tr');
        fila.className = 'fila-egreso';
        fila.setAttribute('data-id', Date.now());
        fila.setAttribute('data-confirmada', 'false');
        fila.innerHTML = `
            <td class="search-container">
                <input type="text" class="form-control form-control-sm busqueda-estudiante" 
                       placeholder="Apellido, nombre o cédula..." autocomplete="off">
                <input type="hidden" name="egreso_estudiante_id[]" class="estudiante-id-input">
                <div class="search-results" role="listbox"></div>
            </td>
            <td><input type="text" name="egreso_genero[]" class="form-control form-control-sm campo-genero" readonly></td>
            <td><input type="text" name="egreso_ci[]" class="form-control form-control-sm campo-ci" readonly></td>
            <td><input type="text" name="egreso_fn[]" class="form-control form-control-sm campo-fn" readonly></td>
            <td><input type="date" name="egreso_fecha[]" class="form-control form-control-sm campo-fecha-egreso" value="<?= date('Y-m-d') ?>"></td>
            <td>
                <select name="egreso_motivo[]" class="form-select form-select-sm campo-motivo">
                    <option value="">Seleccione motivo...</option>
                    <option value="Cambio de domicilio">Cambio de domicilio</option>
                    <option value="Cambio de institución">Cambio de institución</option>
                    <option value="Problemas económicos">Problemas económicos</option>
                    <option value="Enfermedad">Enfermedad</option>
                    <option value="Otro">Otro</option>
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-success btn-confirmar-egreso" 
                        title="Confirmar egreso" style="display:none;">
                    <i class="fas fa-check"></i> Confirmar
                </button>
                <span class="badge bg-success estado-confirmado" style="display:none;">
                    <i class="fas fa-check-circle"></i> Confirmado
                </span>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="eliminarFilaEgreso(this)" aria-label="Eliminar">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        tbody.appendChild(fila);
        
        const busquedaInput = fila.querySelector('.busqueda-estudiante');
        busquedaInput.addEventListener('input', function() {
            AppAsistencia.buscarEstudiante(this, fila);
        });
        
        document.addEventListener('click', function(e) {
            if (!fila.contains(e.target)) {
                fila.querySelector('.search-results')?.classList.remove('show');
            }
        });
    }
}

function eliminarFila(btn, tipo) {
    const fila = btn.closest('tr');
    if (tipo === 'ingreso') {
        const totalFilas = document.querySelectorAll('#tablaIngresos tbody tr').length;
        if (totalFilas <= 1) {
            alert('Debe haber al menos una fila de ingreso.');
            return;
        }
    }
    fila.remove();
}

function eliminarFilaEgreso(btn) {
    const fila = btn.closest('tr');
    const estudianteId = fila.querySelector('.estudiante-id-input')?.value;
    if (estudianteId && estudianteId !== '0' && fila.getAttribute('data-confirmada') === 'true') {
        if (!confirm('¿Está seguro de eliminar este egreso ya confirmado? El estudiante permanecerá activo.')) {
            return;
        }
    }
    fila.remove();
}

function confirmarEgreso(btn) {
    const fila = btn.closest('tr');
    const estudianteId = fila.querySelector('.estudiante-id-input').value;
    
    if (!estudianteId || estudianteId === '0') {
        alert('Debe seleccionar un estudiante primero.');
        return;
    }
    
    const fechaEgreso = fila.querySelector('.campo-fecha-egreso').value;
    const motivo = fila.querySelector('.campo-motivo').value;
    const genero = fila.querySelector('.campo-genero').value === 'Varón' ? 'V' : 'H';
    const sala = AppAsistencia.elementos.sala.value;
    const seccion = AppAsistencia.elementos.seccion.value;
    const periodo = AppAsistencia.elementos.mes.value || '<?= date('Y-m') ?>';
    
    if (!fechaEgreso) {
        alert('Debe ingresar la fecha de egreso.');
        return;
    }
    
    AppAsistencia.verificarEgresoDuplicado(estudianteId, (existe) => {
        if (existe) {
            alert('⚠️ Este estudiante ya fue registrado como egresado en este período. No se puede confirmar.');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'confirmar_egreso');
        formData.append('estudiante_id', estudianteId);
        formData.append('sala', sala);
        formData.append('seccion_id', seccion);
        formData.append('periodo', periodo);
        formData.append('fecha_egreso', fechaEgreso);
        formData.append('motivo', motivo);
        formData.append('genero', genero);
        
        fetch('index.php?ajax=1', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    fila.setAttribute('data-confirmada', 'true');
                    btn.style.display = 'none';
                    const estadoConfirmado = fila.querySelector('.estado-confirmado');
                    if (estadoConfirmado) estadoConfirmado.style.display = 'inline-block';
                    
                    fila.querySelectorAll('input, select').forEach(el => {
                        if (!el.classList.contains('estudiante-id-input') && el.type !== 'hidden') {
                            el.disabled = true;
                        }
                    });
                } else {
                    alert('Error: ' + (data.error || 'No se pudo confirmar el egreso.'));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error de conexión al confirmar el egreso.');
            });
    });
}

// ============================================================================
// CONFIRMAR INGRESO INDIVIDUAL
// ============================================================================
function confirmarIngreso(btn) {
    const fila = btn.closest('tr');
    const apellido = fila.querySelector('.campo-apellido').value.trim();
    const nombre = fila.querySelector('.campo-nombre').value.trim();
    const genero = fila.querySelector('.campo-genero').value;
    const nacionalidad = fila.querySelector('.campo-nacionalidad').value;
    const ci = fila.querySelector('.campo-ci').value.trim();
    const fn = fila.querySelector('.campo-fn').value;
    const fi = fila.querySelector('.campo-fi').value;
    const sala = AppAsistencia.elementos.sala.value;
    const seccion = AppAsistencia.elementos.seccion.value;
    const periodo = AppAsistencia.elementos.mes.value || '<?= date('Y-m') ?>';

    if (!apellido && !nombre) {
        alert('Debe ingresar al menos apellido o nombre.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'confirmar_ingreso');
    formData.append('sala', sala);
    formData.append('seccion_id', seccion);
    formData.append('periodo', periodo);
    formData.append('apellido', apellido);
    formData.append('nombre', nombre);
    formData.append('genero', genero);
    formData.append('nacionalidad', nacionalidad);
    formData.append('ci', ci);
    formData.append('fn', fn);
    formData.append('fi', fi);

    fetch('index.php?ajax=1', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                fila.setAttribute('data-confirmada', 'true');
                btn.style.display = 'none';
                fila.querySelector('.estado-confirmado').style.display = 'inline-block';
                fila.querySelectorAll('input, select').forEach(el => el.disabled = true);
                setTimeout(() => {
                    window.location.href = 'ingresos.php';
                }, 800);
            } else {
                alert('Error: ' + (data.error || 'No se pudo confirmar el ingreso.'));
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error de conexión al confirmar el ingreso.');
        });
}

// Mostrar/ocultar botón confirmar según contenido de la fila
document.getElementById('tablaIngresos')?.addEventListener('input', function(e) {
    const fila = e.target.closest('.fila-ingreso');
    if (!fila) return;
    const apellido = fila.querySelector('.campo-apellido')?.value.trim() || '';
    const nombre = fila.querySelector('.campo-nombre')?.value.trim() || '';
    const btnConfirmar = fila.querySelector('.btn-confirmar-ingreso');
    if (btnConfirmar && fila.getAttribute('data-confirmada') !== 'true') {
        btnConfirmar.style.display = (apellido || nombre) ? 'inline-block' : 'none';
    }
});

// Delegación de evento click para confirmar ingreso
document.getElementById('tablaIngresos')?.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-confirmar-ingreso');
    if (btn) confirmarIngreso(btn);
});

// ============================================================================
// INICIALIZACIÓN
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('form-tabla')) {
        recalcular();
        document.querySelectorAll('.ven-v, .ven-h').forEach(inp => calcularTotalFila(inp, 'venezolano'));
        document.querySelectorAll('.ext-v, .ext-h').forEach(inp => calcularTotalFila(inp, 'extranjero'));
    }
    
    document.querySelectorAll('.busqueda-estudiante').forEach(input => {
        input.addEventListener('input', function() {
            const fila = this.closest('.fila-egreso');
            AppAsistencia.buscarEstudiante(this, fila);
        });
    });
    
    // Delegación de eventos para botones de confirmar
    document.getElementById('egresos-tbody').addEventListener('click', function(e) {
        if (e.target.closest('.btn-confirmar-egreso')) {
            confirmarEgreso(e.target.closest('.btn-confirmar-egreso'));
        }
    });
    
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.search-results').forEach(div => {
            if (!div.parentElement.contains(e.target)) {
                div.classList.remove('show');
            }
        });
    });
    
    // Antes de enviar, deshabilitar filas de egreso no confirmadas
    document.getElementById('form-tabla')?.addEventListener('submit', function(e) {
        document.querySelectorAll('.fila-egreso[data-confirmada="false"]').forEach(fila => {
            fila.querySelectorAll('input, select').forEach(el => el.disabled = true);
        });
        
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';
    });
});
</script>

<?php include "../includes/footer.php"; ?>