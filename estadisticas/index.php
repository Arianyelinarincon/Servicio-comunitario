<?php
require_once "config_db.php";

// ========== FUNCIONES DE SEGURIDAD ==========
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizarEntrada($dato, $tipo = 'string') {
    if (is_array($dato)) {
        return array_map(function($item) use ($tipo) {
            return sanitizarEntrada($item, $tipo);
        }, $dato);
    }
    $dato = trim($dato);
    switch ($tipo) {
        case 'int': return filter_var($dato, FILTER_VALIDATE_INT) ? (int)$dato : 0;
        case 'email': return filter_var($dato, FILTER_VALIDATE_EMAIL) ? $dato : '';
        default: return htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
    }
}

function responderJSON($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ========== AJAX HANDLER ==========
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['usuario'])) {
        http_response_code(403);
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
    
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action == 'cargar_secciones') {
        $sala = $_POST['sala'] ?? '';
        $stmt = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
        $stmt->bind_param("s", $sala);
        $stmt->execute();
        $result = $stmt->get_result();
        $secciones = [];
        while($row = $result->fetch_assoc()) {
            $secciones[] = ['id' => $row['id'], 'nombre' => $row['nombre']];
        }
        echo json_encode(['secciones' => $secciones]);
        $stmt->close();
        exit;
    }
    
    if ($action == 'cargar_docentes') {
        $seccion = (int)$_POST['seccion'];
        $stmt = $conexion->prepare("SELECT id, nombre FROM profesores WHERE seccion = ? ORDER BY nombre ASC");
        $stmt->bind_param("i", $seccion);
        $stmt->execute();
        $result = $stmt->get_result();
        $docentes = [];
        while($row = $result->fetch_assoc()) {
            $docentes[] = ['id' => $row['id'], 'nombre' => $row['nombre']];
        }
        echo json_encode(['docentes' => $docentes]);
        $stmt->close();
        exit;
    }

    // ========== NUEVAS ACCIONES PARA EGRESOS ==========
    if ($action == 'buscar_estudiantes') {
        $termino = sanitizarEntrada($_POST['termino'] ?? '');
        $sala = sanitizarEntrada($_POST['sala'] ?? '');
        $seccion = (int)($_POST['seccion_id'] ?? 0);
        $periodo = sanitizarEntrada($_POST['periodo'] ?? date('Y-m'));
        $tipo = sanitizarEntrada($_POST['tipo'] ?? 'egreso'); // 'ingreso' o 'egreso'

        if (empty($termino) || empty($sala) || $seccion <= 0) {
            responderJSON(['estudiantes' => []]);
        }

        $termino = "%$termino%";
        $anio = date('Y', strtotime($periodo . '-01'));
        $mes = date('m', strtotime($periodo . '-01'));
        $fecha_inicio = "$anio-$mes-01";
        $fecha_fin = date('Y-m-t', strtotime($fecha_inicio));

        $sql = "
            SELECT id, nombre, apellido, 
                   COALESCE(cedula, cedula_escolar) AS cedula,
                   genero, nacionalidad, fecha_nacimiento,
                   CONCAT(apellido, ' ', nombre) AS nombre_completo
            FROM estudiantes
            WHERE sala = ? AND seccion_id = ? AND estatus = 'Activo'
              AND (nombre LIKE ? OR apellido LIKE ? OR COALESCE(cedula, cedula_escolar) LIKE ?)
        ";

        if ($tipo === 'ingreso') {
            // Excluir estudiantes que ya fueron ingresados en este mes (fecha_ingreso en el rango)
            $sql .= " AND (fecha_ingreso IS NULL OR fecha_ingreso < ? OR fecha_ingreso > ?)";
        } elseif ($tipo === 'egreso') {
            // Excluir estudiantes que ya tienen egreso en este periodo
            $sql .= " AND id NOT IN (
                          SELECT estudiante_id FROM egresos 
                          WHERE sala = ? AND seccion_id = ? AND periodo = ?
                      )";
        }

        $sql .= " ORDER BY apellido, nombre LIMIT 15";

        $stmt = $conexion->prepare($sql);

        if ($tipo === 'ingreso') {
            $stmt->bind_param("sisssss", $sala, $seccion, $termino, $termino, $termino, $fecha_inicio, $fecha_fin);
        } elseif ($tipo === 'egreso') {
            $stmt->bind_param("sissssis", $sala, $seccion, $termino, $termino, $termino, $sala, $seccion, $periodo);
        } else {
            $stmt->bind_param("sisss", $sala, $seccion, $termino, $termino, $termino);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $estudiantes = [];
        while ($row = $result->fetch_assoc()) {
            $estudiantes[] = [
                'id' => (int)$row['id'],
                'nombre_completo' => htmlspecialchars($row['apellido'] . ' ' . $row['nombre']),
                'cedula' => htmlspecialchars($row['cedula']),
                'genero' => $row['genero'],
                'nacionalidad' => $row['nacionalidad'] ?? 'Venezolana',
                'fecha_nacimiento' => $row['fecha_nacimiento']
            ];
        }
        responderJSON(['estudiantes' => $estudiantes]);
    }

    if ($action == 'confirmar_egreso') {
        if (!isset($_POST['csrf_token']) || !verificarTokenCSRF($_POST['csrf_token'])) {
            responderJSON(['success' => false, 'error' => 'Token CSRF inválido'], 403);
        }
        
        $estudiante_id = (int)($_POST['estudiante_id'] ?? 0);
        $sala = sanitizarEntrada($_POST['sala'] ?? '');
        $seccion = (int)($_POST['seccion'] ?? 0);
        $periodo = sanitizarEntrada($_POST['periodo'] ?? '');
        $motivo = sanitizarEntrada($_POST['motivo'] ?? 'Retiro');
        
        if (!$estudiante_id || empty($sala) || $seccion <= 0 || empty($periodo)) {
            responderJSON(['success' => false, 'error' => 'Datos incompletos']);
        }
        
        // Verificar duplicado
        $stmt = $conexion->prepare("SELECT id FROM egresos WHERE estudiante_id = ? AND sala = ? AND seccion_id = ? AND periodo = ?");
        $stmt->bind_param("isis", $estudiante_id, $sala, $seccion, $periodo);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            responderJSON(['success' => false, 'error' => 'Este estudiante ya fue egresado en este período']);
        }
        $stmt->close();
        
        // Obtener datos del estudiante
        $stmt = $conexion->prepare("SELECT nombre, apellido, genero, nacionalidad, cedula, fecha_nacimiento FROM estudiantes WHERE id = ?");
        $stmt->bind_param("i", $estudiante_id);
        $stmt->execute();
        $estudiante = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$estudiante) responderJSON(['success' => false, 'error' => 'Estudiante no encontrado']);

        // Asegurar que el género tenga un valor por defecto
        $genero_estudiante = $estudiante['genero'] ?? 'V';
        // Insertar egreso
        $fecha_egreso = date('Y-m-d');
        $stmt = $conexion->prepare("INSERT INTO egresos (estudiante_id, sala, seccion_id, periodo, fecha_egreso, motivo, genero) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isissss", $estudiante_id, $sala, $seccion, $periodo, $fecha_egreso, $motivo, $genero_estudiante);
        $stmt->execute();
        $stmt->close();
        
        // Actualizar estatus del estudiante a Inactivo
        $stmt_up = $conexion->prepare("UPDATE estudiantes SET estatus = 'Inactivo' WHERE id = ?");
        $stmt_up->bind_param("i", $estudiante_id);
        $stmt_up->execute();
        $stmt_up->close();
        
        responderJSON([
            'success' => true,
            'estudiante' => [
                'nombre_completo' => $estudiante['apellido'] . ' ' . $estudiante['nombre'],
                'genero' => $estudiante['genero'],
                'ci' => $estudiante['cedula'],
                'fn' => $estudiante['fecha_nacimiento'],
                'fi' => $fecha_egreso
            ]
        ]);
    }

    // ========== NUEVA ACCIÓN PARA INGRESOS (SIN REDIRECCIÓN) ==========
    if ($action == 'confirmar_ingreso') {
        if (!isset($_POST['csrf_token']) || !verificarTokenCSRF($_POST['csrf_token'])) {
            responderJSON(['success' => false, 'error' => 'Token CSRF inválido'], 403);
        }
        
        $apellido = sanitizarEntrada($_POST['apellido'] ?? '');
        $nombre = sanitizarEntrada($_POST['nombre'] ?? '');
        $sala = sanitizarEntrada($_POST['sala'] ?? '');
        $seccion = (int)($_POST['seccion'] ?? 0);
        $periodo = sanitizarEntrada($_POST['periodo'] ?? '');
        $genero = sanitizarEntrada($_POST['genero'] ?? 'V');
        $cedula = sanitizarEntrada($_POST['cedula'] ?? '');
        $fecha_nacimiento = sanitizarEntrada($_POST['fecha_nacimiento'] ?? '');
        $estudiante_id = (int)($_POST['estudiante_id'] ?? 0);

        if (empty($apellido) || empty($nombre) || empty($sala) || $seccion <= 0 || empty($periodo)) {
            responderJSON(['success' => false, 'error' => 'Datos incompletos']);
        }
        
        // Si no viene estudiante_id, intentamos buscar por coincidencia exacta o insertar nuevo
        if ($estudiante_id == 0) {
            $stmt = $conexion->prepare("SELECT id FROM estudiantes WHERE apellido = ? AND nombre = ? AND sala = ? AND seccion_id = ?");
            $stmt->bind_param("sssi", $apellido, $nombre, $sala, $seccion);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $estudiante_id = $row['id'];
            }
            $stmt->close();
        }
        
        // Si aún no existe, crear estudiante
        if ($estudiante_id == 0) {
            $stmt = $conexion->prepare("INSERT INTO estudiantes (apellido, nombre, genero, cedula, fecha_nacimiento, sala, seccion_id, estatus) VALUES (?, ?, ?, ?, ?, ?, ?, 'Activo')");
            $stmt->bind_param("ssssssi", $apellido, $nombre, $genero, $cedula, $fecha_nacimiento, $sala, $seccion);
            $stmt->execute();
            $estudiante_id = $stmt->insert_id;
            $stmt->close();
        } else {
            // Reactivar si estaba inactivo
            $stmt = $conexion->prepare("UPDATE estudiantes SET estatus = 'Activo' WHERE id = ?");
            $stmt->bind_param("i", $estudiante_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Actualizar fecha de ingreso (para evitar duplicados)
        $fecha_ingreso = date('Y-m-d');
        $stmt = $conexion->prepare("UPDATE estudiantes SET fecha_ingreso = ? WHERE id = ?");
        $stmt->bind_param("si", $fecha_ingreso, $estudiante_id);
        $stmt->execute();
        $stmt->close();
        
        // Obtener datos del estudiante para la respuesta
        $stmt = $conexion->prepare("SELECT nombre, apellido, genero, cedula, fecha_nacimiento FROM estudiantes WHERE id = ?");
        $stmt->bind_param("i", $estudiante_id);
        $stmt->execute();
        $estudiante = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        responderJSON([
            'success' => true,
            'mensaje' => 'Ingreso registrado correctamente',
            'estudiante' => [
                'nombre_completo' => $estudiante['apellido'] . ' ' . $estudiante['nombre'],
                'genero' => $estudiante['genero'],
                'ci' => $estudiante['cedula'],
                'fn' => $estudiante['fecha_nacimiento'],
                'fi' => $fecha_ingreso
            ]
        ]);
    }

    if ($action == 'cargar_egresos_existentes') {
        $sala = sanitizarEntrada($_POST['sala'] ?? '');
        $seccion = (int)($_POST['seccion'] ?? 0);
        $periodo = sanitizarEntrada($_POST['periodo'] ?? '');
        
        if (empty($sala) || $seccion <= 0 || empty($periodo)) {
            responderJSON(['egresos' => []]);
        }
        
        $stmt = $conexion->prepare("
    SELECT est.nombre, est.apellido, e.genero, 
           COALESCE(est.cedula, est.cedula_escolar, '') AS ci,
           est.fecha_nacimiento, e.fecha_egreso
    FROM egresos e
    JOIN estudiantes est ON e.estudiante_id = est.id
    WHERE e.sala = ? AND e.seccion_id = ? AND e.periodo = ?
    ORDER BY e.fecha_egreso DESC
");
        $stmt->bind_param("sis", $sala, $seccion, $periodo);
        $stmt->execute();
        $result = $stmt->get_result();
        $egresos = [];
        while ($row = $result->fetch_assoc()) {
            $egresos[] = [
                'nombre_completo' => $row['apellido'] . ' ' . $row['nombre'],
                'genero' => $row['genero'],
                'ci' => $row['ci'],
                'fn' => $row['fecha_nacimiento'],
                'fi' => $row['fecha_egreso']
            ];
        }
        responderJSON(['egresos' => $egresos]);
    }

    exit;
}

// ========== PROCESAR GUARDADO DE DATOS ==========
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_y_pdf'])) {
    $periodo = $_POST['periodo'];
    $sala = $_POST['sala'];
    $seccion_id = (int)$_POST['seccion'];
    $profesor_id = $_POST['profesor'];
    $nombre_docente = $_POST['nombre_docente'];
    $asist_v = $_POST['asist_v'] ?? [];
    $asist_h = $_POST['asist_h'] ?? [];
    $anio = date('Y', strtotime($periodo));
    $mes = date('m', strtotime($periodo));
    $dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
    
    // Guardar asistencia diaria
    for ($d = 1; $d <= $dias_en_mes; $d++) {
        $fecha = "$anio-$mes-" . str_pad($d, 2, '0', STR_PAD_LEFT);
        $v = (int)($asist_v[$d] ?? 0);
        $h = (int)($asist_h[$d] ?? 0);
        $stmt = $conexion->prepare("INSERT INTO asistencia_diaria (fecha, sala, seccion_id, genero, cantidad) VALUES (?, ?, ?, 'V', ?) ON DUPLICATE KEY UPDATE cantidad = ?");
        $stmt->bind_param("ssiii", $fecha, $sala, $seccion_id, $v, $v);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conexion->prepare("INSERT INTO asistencia_diaria (fecha, sala, seccion_id, genero, cantidad) VALUES (?, ?, ?, 'H', ?) ON DUPLICATE KEY UPDATE cantidad = ?");
        $stmt->bind_param("ssiii", $fecha, $sala, $seccion_id, $h, $h);
        $stmt->execute();
        $stmt->close();
    }
    
   // ========== GUARDAR EGRESOS DEL FORMULARIO ==========
if (isset($_POST['egreso_apellido']) && is_array($_POST['egreso_apellido'])) {
    // Validar que la sala y sección sean válidas
    if (empty($sala) || $seccion_id <= 0) {
        // Si no hay datos válidos, mostramos mensaje pero continuamos
        $mensaje = '⚠️ Advertencia: No se guardaron egresos por falta de sala/sección.';
    } else {
        $seccion_id_int = (int)$seccion_id;
        $periodo_egreso = $periodo;
        
        for ($i = 0; $i < count($_POST['egreso_apellido']); $i++) {
            $apellido = trim($_POST['egreso_apellido'][$i] ?? '');
            $nombre = trim($_POST['egreso_nombre'][$i] ?? '');
            if (empty($apellido) && empty($nombre)) continue;
            
            // Obtener valores con isset para evitar undefined index
            $genero = isset($_POST['egreso_genero'][$i]) ? $_POST['egreso_genero'][$i] : 'V';
            $ci = isset($_POST['egreso_ci'][$i]) ? $_POST['egreso_ci'][$i] : '';
            $fn = isset($_POST['egreso_fn'][$i]) ? $_POST['egreso_fn'][$i] : '';
            $fi = isset($_POST['egreso_fi'][$i]) ? $_POST['egreso_fi'][$i] : '';
            
            // Buscar estudiante existente
            $stmt = $conexion->prepare("SELECT id FROM estudiantes WHERE apellido = ? AND nombre = ? AND sala = ? AND seccion_id = ?");
            if ($stmt) {
                $stmt->bind_param("sssi", $apellido, $nombre, $sala, $seccion_id_int);
                $stmt->execute();
                $result = $stmt->get_result();
                $estudiante = $result->fetch_assoc();
                $stmt->close();
            } else {
                continue;
            }
            
            if ($estudiante) {
                $estudiante_id = $estudiante['id'];
                // Actualizar estatus a Inactivo
                $stmt_up = $conexion->prepare("UPDATE estudiantes SET estatus = 'Inactivo' WHERE id = ?");
                if ($stmt_up) {
                    $stmt_up->bind_param("i", $estudiante_id);
                    $stmt_up->execute();
                    $stmt_up->close();
                }
            } else {
                // Insertar nuevo estudiante
                $estatus = 'Inactivo';
                // Validar que la cedula no sea null para bind_param
                $ci_val = $ci ?? '';
                $fn_val = $fn ?? '';
                
                $stmt_ins = $conexion->prepare("INSERT INTO estudiantes (apellido, nombre, genero, cedula, fecha_nacimiento, sala, seccion_id, estatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt_ins) {
                    $stmt_ins->bind_param("ssssssis", $apellido, $nombre, $genero, $ci_val, $fn_val, $sala, $seccion_id_int, $estatus);
                    $stmt_ins->execute();
                    $estudiante_id = $stmt_ins->insert_id;
                    $stmt_ins->close();
                } else {
                    continue;
                }
            }
            
            // Insertar egreso SOLO si tenemos estudiante_id válido
            if (isset($estudiante_id) && $estudiante_id > 0) {
                $fecha_egreso = !empty($fi) ? $fi : date('Y-m-d');
                $motivo_egreso = 'Retiro';
                
                $stmt_eg = $conexion->prepare("INSERT INTO egresos (estudiante_id, sala, seccion_id, periodo, fecha_egreso, motivo, genero) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt_eg) {
                    // PASO CLAVE: todas las variables deben estar definidas y ser del tipo correcto
                    $stmt_eg->bind_param("iisssss", $estudiante_id, $sala, $seccion_id_int, $periodo_egreso, $fecha_egreso, $motivo_egreso, $genero);
                    $stmt_eg->execute();
                    $stmt_eg->close();
                }
            }
        }
    }
}
    
    include "generar_pdf.php";
    exit;
}

include "../includes/header.php";

// ========== FILTROS EN TIEMPO REAL ==========
$sala_seleccionada = isset($_GET['sala']) ? mysqli_real_escape_string($conexion, $_GET['sala']) : '';
$seccion_seleccionada = isset($_GET['seccion']) ? mysqli_real_escape_string($conexion, $_GET['seccion']) : '';
$profesor_id = isset($_GET['profesor']) ? mysqli_real_escape_string($conexion, $_GET['profesor']) : '';
$periodo = isset($_GET['periodo']) ? mysqli_real_escape_string($conexion, $_GET['periodo']) : date('Y-m');

$mostrar_tabla = ($sala_seleccionada && $profesor_id);

if ($mostrar_tabla) {
    $anio = date('Y', strtotime($periodo));
    $mes_num = date('m', strtotime($periodo));
    $dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $mes_num, $anio);
} else {
    $anio = date('Y');
    $mes_num = date('m');
    $dias_en_mes = 0;
}

$meses_es = ["01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre"];
$nombre_mes = isset($meses_es[$mes_num]) ? $meses_es[$mes_num] : '';

$nombre_profesor = 'No seleccionado';
if ($profesor_id) {
    $stmt_prof = $conexion->prepare("SELECT nombre FROM profesores WHERE id = ?");
    $stmt_prof->bind_param("s", $profesor_id);
    $stmt_prof->execute();
    $prof_data = $stmt_prof->get_result()->fetch_assoc();
    if ($prof_data) {
        $nombre_profesor = htmlspecialchars($prof_data['nombre']);
    }
    $stmt_prof->close();
}

$es_inicial = in_array($sala_seleccionada, ['sala4', 'sala5']);
$edades = $es_inicial ? [4,5,6] : range(6,15);

// Asegurar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<style>
    :root { --navy: #002d54; --weekend-bg: #343a40; }
    .card { border-radius: 12px; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .card-header { background: var(--navy) !important; color: white; }
    .table-asistencia { font-size: 0.75rem; text-align: center; }
    .table-asistencia th { vertical-align: middle; padding: 4px !important; border-color: #dee2e6; }
    .table-asistencia td { vertical-align: middle; padding: 4px !important; }
    .weekend { background-color: var(--weekend-bg) !important; color: white !important; }
    .weekend-cell { background-color: #212529 !important; cursor: not-allowed; }
    .asist-input { border: none !important; background: transparent; text-align: center; width: 100%; font-weight: bold; height: 32px; }
    .asist-input:focus { background-color: #fff9c4 !important; outline: none; }
    input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; }
    .bg-navy { background-color: var(--navy) !important; }
    .matricula-box input { width: 45px; border: 1px solid #ced4da; border-radius: 4px; text-align: center; font-weight: bold; background: #ffffff; color: #000000; }
    
    .tabla-dinamica { width: 100%; table-layout: fixed; }
    .tabla-dinamica th, .tabla-dinamica td { vertical-align: middle; padding: 5px; }
    .tabla-dinamica input, .tabla-dinamica select { width: 100%; box-sizing: border-box; }
    .col-nombre { width: 27%; }
    .col-genero { width: 8%; }
    .col-nacionalidad { width: 10%; }
    .col-ci { width: 12%; min-width: 110px; }
    .col-fecha { width: 10%; }
    .col-accion { width: 5%; }
    
    .tabla-edades { table-layout: fixed; }
    .tabla-edades td, .tabla-edades th { vertical-align: middle; text-align: center; }
    .col-edad { width: 8%; }
    .col-pequeno { width: 6%; }
    .col-mediano { width: 10%; }
    
    .btn-pdf-final {
        background-color: #dc3545;
        color: white;
        font-weight: bold;
        padding: 10px 30px;
        font-size: 1.1rem;
        border-radius: 30px;
        transition: all 0.3s;
        border: none;
    }
    .btn-pdf-final:hover {
        background-color: #b02a37;
        transform: scale(1.02);
    }
    
    .filtro-tiempo-real select,
    .filtro-tiempo-real input {
        transition: all 0.3s ease;
    }
    .filtro-tiempo-real select:focus,
    .filtro-tiempo-real input:focus {
        border-color: #002d54;
        box-shadow: 0 0 0 0.2rem rgba(0,45,84,0.25);
    }
    
    .btn-guardar {
        background-color: #28a745;
        color: white;
        font-weight: bold;
        padding: 10px 30px;
        font-size: 1.1rem;
        border-radius: 30px;
        transition: all 0.3s;
        border: none;
    }
    .btn-guardar:hover {
        background-color: #218838;
        transform: scale(1.02);
    }

    .campo-solo-lectura {
        background-color: #e9ecef !important;
        opacity: 1;
        cursor: default;
        font-weight: 500;
    }
    .btn-buscar-egreso {
        background: var(--navy);
        color: white;
        border: none;
        transition: all 0.2s;
    }
    .btn-buscar-egreso:hover {
        background: #004a7c;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,45,84,0.2);
        color: white;
    }

    /* ===== CORRECCIÓN: PARA QUE EL DROPDOWN NO SE ESCONDA ===== */
    .search-container {
        position: relative;
        z-index: 9999 !important;
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
        z-index: 99999 !important; /* Por encima de todo */
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        display: none;
    }
    .search-results.show {
        display: block;
    }
    #tablaEgresos {
        overflow: visible !important;
    }
    #tablaEgresos tbody td {
        overflow: visible !important;
    }
    .card-body {
        overflow: visible !important;
    }

    /* Estilos para el dropdown de autocompletado */
    .autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 200px;
        overflow-y: auto;
        background: white;
        border: 1px solid #ced4da;
        border-radius: 6px;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .autocomplete-dropdown .dropdown-item {
        padding: 6px 12px;
        cursor: pointer;
    }
    .autocomplete-dropdown .dropdown-item:hover {
        background-color: #e8f4f8;
    }
</style>

<div class="container-fluid py-4">

    <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 text-navy"><i class="fas fa-calendar-check"></i> Control de Asistencia</h4>
        <a href="historial_resumenes.php" class="btn btn-info">
            <i class="fas fa-chart-line"></i> Ver Historial de Resúmenes
        </a>
    </div>

    <!-- ========== FILTROS EN TIEMPO REAL ========== -->
    <div class="card mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end filtro-tiempo-real" id="filtroForm">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">SALA / GRADO</label>
                    <select name="sala" id="select-grado" class="form-select shadow-none" onchange="this.form.submit()">
                        <option value="">Seleccione grado...</option>
                        <optgroup label="Educación Inicial">
                            <option value="sala4" <?= ($sala_seleccionada == 'sala4') ? 'selected' : '' ?>>Sala 4 Años</option>
                            <option value="sala5" <?= ($sala_seleccionada == 'sala5') ? 'selected' : '' ?>>Sala 5 Años</option>
                        </optgroup>
                        <optgroup label="Educación Primaria">
                            <?php for($i=1; $i<=6; $i++): 
                                $val = ($i==1) ? "1ro" : (($i==2) ? "2do" : (($i==3) ? "3ro" : $i."to")); ?>
                                <option value="<?= htmlspecialchars($val) ?>" <?= ($sala_seleccionada == $val) ? 'selected' : '' ?>><?= $i ?>° Grado</option>
                            <?php endfor; ?>
                        </optgroup>
                    </select>
                </div>

                <div class="col-md-3" id="seccion-seccion">
                    <label class="small fw-bold text-muted">SECCIÓN</label>
                    <select name="seccion" id="select-seccion" class="form-select shadow-none" <?= empty($sala_seleccionada) ? 'disabled' : '' ?> onchange="this.form.submit()">
                        <option value="">Primero seleccione grado...</option>
                        <?php 
                        if($sala_seleccionada) {
                            $stmt_sec = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
                            $stmt_sec->bind_param("s", $sala_seleccionada);
                            $stmt_sec->execute();
                            $result_sec = $stmt_sec->get_result();
                            while($sec = $result_sec->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($sec['id']) ?>" <?= ($seccion_seleccionada == $sec['id']) ? 'selected' : '' ?>><?= htmlspecialchars($sec['nombre']) ?></option>
                            <?php endwhile;
                            $stmt_sec->close();
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3" id="seccion-docente">
                    <label class="small fw-bold text-muted">PROFESOR / DOCENTE</label>
                    <select name="profesor" id="select-docente" class="form-select shadow-none" <?= empty($seccion_seleccionada) ? 'disabled' : '' ?> onchange="this.form.submit()">
                        <option value="">Primero seleccione sección...</option>
                        <?php 
                        if($seccion_seleccionada) {
                            $stmt_p = $conexion->prepare("SELECT id, nombre FROM profesores WHERE seccion = ? ORDER BY nombre ASC");
                            $stmt_p->bind_param("s", $seccion_seleccionada);
                            $stmt_p->execute();
                            $result_p = $stmt_p->get_result();
                            while($p = $result_p->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($p['id']) ?>" <?= ($profesor_id == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['nombre']) ?></option>
                            <?php endwhile; 
                            $stmt_p->close();
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3" id="seccion-mes">
                    <label class="small fw-bold text-muted">MES</label>
                    <input type="month" name="periodo" id="select-mes" class="form-control shadow-none" value="<?= htmlspecialchars($periodo) ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

<?php if ($mostrar_tabla): 
    $d_hab = 0;
    for($d=1; $d<=$dias_en_mes; $d++) {
        $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
        if($n_dia != 0 && $n_dia != 6) $d_hab++;
    }
?>
<!-- ========== FORMULARIO CON GUARDAR Y GENERAR PDF ========== -->
<form method="POST" id="form-tabla" target="_blank">
    <input type="hidden" name="guardar_y_pdf" value="1">
    <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
    <input type="hidden" name="sala" value="<?= htmlspecialchars($sala_seleccionada) ?>">
    <input type="hidden" name="seccion" value="<?= htmlspecialchars($seccion_seleccionada) ?>">
    <input type="hidden" name="profesor" value="<?= htmlspecialchars($profesor_id) ?>">
    <input type="hidden" name="nombre_docente" value="<?= $nombre_profesor ?>">
    <input type="hidden" id="tipo_reporte" name="tipo_reporte" value="">
    
    <input type="hidden" name="porcentaje_v" id="porcentaje_v" value="0">
    <input type="hidden" name="porcentaje_h" id="porcentaje_h" value="0">
    <input type="hidden" name="porcentaje_total" id="porcentaje_total" value="100">

    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h6 class="m-0 fw-bold text-uppercase">CONTROL DE ASISTENCIA: <?= strtoupper($nombre_mes) ?> <?= $anio ?></h6>
                <small class="opacity-75">Docente: <?= strtoupper($nombre_profesor) ?> | <?= strtoupper(htmlspecialchars($sala_seleccionada)) ?> <?= $seccion_seleccionada ? '- ' . strtoupper(htmlspecialchars($seccion_seleccionada)) : '' ?></small>
            </div>
            <div class="d-flex gap-3 align-items-center">
                <button type="button" class="btn btn-sm btn-danger fw-bold" onclick="limpiarTodo()">LIMPIAR</button>
                <div class="bg-white text-dark px-3 py-1 rounded small fw-bold matricula-box">
                    Matrícula: V <input type="number" min="0" name="mat_v" id="mat_v" value="" oninput="recalcular()"> 
                    H <input type="number" min="0" name="mat_h" id="mat_h" value="" oninput="recalcular()">
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0 table-asistencia">
                <thead class="bg-light">
                    <tr>
                        <th rowspan="2" width="50">SEXO</th>
                        <?php 
                        for($d=1; $d<=$dias_en_mes; $d++) {
                            $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                            $es_fin = ($n_dia == 0 || $n_dia == 6);
                            $letra = ['D','L','M','M','J','V','S'][$n_dia];
                            echo "<th class='".($es_fin ? 'weekend' : '')."' style='font-size:0.7rem;'>$letra<br><small>".$d."</small></th>";
                        }
                        ?>
                        <th rowspan="2" class="bg-primary text-white">TOTAL</th>
                        <th rowspan="2" class="bg-success text-white">%</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-bold bg-light">V</td>
                        <?php for($d=1; $d<=$dias_en_mes; $d++): 
                            $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                            $es_fin = ($n_dia == 0 || $n_dia == 6); ?>
                            <td class="<?= $es_fin ? 'weekend-cell' : 'p-0' ?>">
                                <?php if(!$es_fin): ?>
                                    <input type="number" min="0" name="asist_v[<?= $d ?>]" class="asist-input in-v" data-dia="<?= $d ?>" value="" oninput="recalcular()" onblur="limpiarCero(this)">
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <td id="res_total_v" class="fw-bold bg-primary text-white">0</td>
                        <td id="res_porc_v" class="fw-bold text-primary">0%</td>
                    </tr>
                    <tr>
                        <td class="fw-bold bg-light">H</td>
                        <?php for($d=1; $d<=$dias_en_mes; $d++): 
                            $n_dia = date('w', strtotime("$anio-$mes_num-$d"));
                            $es_fin = ($n_dia == 0 || $n_dia == 6); ?>
                            <td class="<?= $es_fin ? 'weekend-cell' : 'p-0' ?>">
                                <?php if(!$es_fin): ?>
                                    <input type="number" min="0" name="asist_h[<?= $d ?>]" class="asist-input in-h" data-dia="<?= $d ?>" value="" oninput="recalcular()" onblur="limpiarCero(this)">
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <td id="res_total_h" class="fw-bold bg-primary text-white">0</td>
                        <td id="res_porc_h" class="fw-bold text-primary">0%</td>
                    </tr>
                </tbody>
                <tfoot class="bg-light fw-bold">
                    <tr>
                        <td class="bg-navy text-white">TOTAL</td>
                        <?php for($d=1; $d<=$dias_en_mes; $d++): ?>
                            <td id="total_dia_<?= $d ?>" class="bg-navy text-white">-</td>
                        <?php endfor; ?>
                        <td id="gran_total_asist" class="bg-dark text-white fw-bold">0</td>
                        <td id="gran_total_porc" class="bg-dark text-white fw-bold">0%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <input type="hidden" id="dias_hab_val" value="<?= $d_hab ?>">
            <span class="text-muted small">Días Hábiles: <b><?= $d_hab ?></b></span>
            <span class="h6 mb-0 text-navy">Promedio Diario: <b id="promedio_total">0.0</b></span>
        </div>
    </div>

    <!-- Clasificación editable -->
    <div class="card mb-4">
        <div class="card-header bg-navy text-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Clasificación por Nacionalidad, Edad y Sexo</h6>
            <span class="small">Los totales se calculan automáticamente</span>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-bordered table-sm tabla-edades" style="table-layout:fixed;">
                    <colgroup>
                        <col class="col-edad"><col class="col-pequeno"><col class="col-pequeno"><col class="col-mediano">
                        <col class="col-edad"><col class="col-pequeno"><col class="col-pequeno"><col class="col-mediano">
                    </colgroup>
                    <thead class="table-light">
                        <tr>
                            <th colspan="4">Venezolano</th>
                            <th colspan="4">Extranjero</th>
                        </tr>
                        <tr>
                            <th>Edad</th><th>V</th><th>H</th><th>Total</th>
                            <th>Edad</th><th>V</th><th>H</th><th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($edades as $edad): ?>
                        <tr class="fila-edad">
                            <td><?= $edad ?></td>
                            <td><input type="number" class="form-control form-control-sm text-center ven-v" name="venezolano_v[<?= $edad ?>]" data-edad="<?= $edad ?>" value="" oninput="calcularTotalFila(this, 'venezolano')" onblur="limpiarCero(this)"></td>
                            <td><input type="number" class="form-control form-control-sm text-center ven-h" name="venezolano_h[<?= $edad ?>]" data-edad="<?= $edad ?>" value="" oninput="calcularTotalFila(this, 'venezolano')" onblur="limpiarCero(this)"></td>
                            <td><input type="text" class="form-control form-control-sm text-center ven-total" name="venezolano_total[<?= $edad ?>]" readonly></td>
                            <td><?= $edad ?></td>
                            <td><input type="number" class="form-control form-control-sm text-center ext-v" name="extranjero_v[<?= $edad ?>]" data-edad="<?= $edad ?>" value="" oninput="calcularTotalFila(this, 'extranjero')" onblur="limpiarCero(this)"></td>
                            <td><input type="number" class="form-control form-control-sm text-center ext-h" name="extranjero_h[<?= $edad ?>]" data-edad="<?= $edad ?>" value="" oninput="calcularTotalFila(this, 'extranjero')" onblur="limpiarCero(this)"></td>
                            <td><input type="text" class="form-control form-control-sm text-center ext-total" name="extranjero_total[<?= $edad ?>]" readonly></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========== INGRESO / EGRESO ========== -->
    <div class="card mb-4">
        <div class="card-header bg-navy text-white">
            <h6 class="mb-0">Ingreso / Egreso del Mes</h6>
        </div>
        <div class="card-body p-3">
            <!-- Ingresos -->
            <div class="mb-4">
                <h6 class="text-primary">Ingresos</h6>
                <table class="table table-sm table-bordered tabla-dinamica" id="tablaIngresos">
                    <colgroup>
                        <col class="col-nombre"><col class="col-genero"><col class="col-nacionalidad"><col class="col-ci"><col class="col-fecha"><col class="col-fecha"><col class="col-accion">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Apellido y Nombre</th><th>Género</th><th>Nacionalidad</th><th>CI o CE</th><th>F.N</th><th>F.I</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="fila-ingreso">
                            <td>
                                <input type="text" name="ingreso_apellido[]" class="form-control form-control-sm" placeholder="Apellido">
                                <input type="text" name="ingreso_nombre[]" class="form-control form-control-sm mt-1" placeholder="Nombre">
                            </td>
                            <td>
                                <select name="ingreso_genero[]" class="form-select form-select-sm">
                                    <option value="V">Varón (V)</option>
                                    <option value="H">Hembra (H)</option>
                                </select>
                            </td>
                            <td>
                                <select name="ingreso_nacionalidad[]" class="form-select form-select-sm">
                                    <option value="Venezolana">Venezolana</option>
                                    <option value="Extranjera">Extranjera</option>
                                </select>
                            </td>
                            <td><input type="text" name="ingreso_ci[]" class="form-control form-control-sm" placeholder="Cédula" maxlength="11"></td>
                            <td><input type="text" name="ingreso_fn[]" class="form-control form-control-sm" placeholder="DD/MM/YYYY" onfocus="this.type='date'" onblur="if(!this.value)this.type='text'"></td>
                            <td><input type="text" name="ingreso_fi[]" class="form-control form-control-sm" placeholder="DD/MM/YYYY" onfocus="this.type='date'" onblur="if(!this.value)this.type='text'"></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-success confirmar-ingreso me-1" title="Confirmar ingreso">✓</button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✖</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-light mt-2" onclick="agregarFila('ingreso')">+ Agregar Ingreso</button>
            </div>

            <!-- Egresos con autocompletado -->
            <div>
                <h6 class="text-danger mb-0"><i class="fas fa-user-slash"></i> Egresos</h6>
                <table class="table table-sm table-bordered tabla-dinamica" id="tablaEgresos">
                    <colgroup>
                        <col class="col-nombre"><col class="col-genero"><col class="col-ci"><col class="col-fecha"><col class="col-fecha"><col class="col-accion">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Apellido y Nombre</th><th>Género</th><th>CI o CE</th><th>F.N</th><th>F.I</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Las filas se llenarán vía JavaScript -->
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-light mt-2" onclick="agregarFila('egreso')">+ Agregar Egreso Manual</button>
            </div>
        </div>
    </div>

    <!-- Observaciones -->
    <div class="card mb-4">
        <div class="card-header bg-navy text-white"><h6 class="mb-0">Observaciones Relevantes</h6></div>
        <div class="card-body">
            <textarea name="observaciones" class="form-control" rows="4" placeholder="Escriba aquí las observaciones..."></textarea>
            <div class="text-end mt-2"><small>Director(a)</small></div>
        </div>
    </div>

    <!-- ========== BOTÓN GUARDAR Y GENERAR PDF ========== -->
    <div class="text-center mt-4 mb-4">
        <button type="submit" name="accion" value="guardar_pdf" class="btn btn-guardar px-5 py-2">
            💾 GUARDAR Y GENERAR PDF
        </button>
        <br>
        <small class="text-muted">Los datos se guardarán en el historial y se generará el PDF</small>
    </div>
</form>
<?php endif; ?>
</div>

<script>
// ========== FUNCIONES DE FILTROS ==========
function setTipoReporte() {
    const sala = document.getElementById('select-grado').value;
    const tipo = (sala === 'sala4' || sala === 'sala5') ? 'inicial' : 'regular';
    const tipoInput = document.getElementById('tipo_reporte');
    if (tipoInput) tipoInput.value = tipo;
}

function pasoGrado() {
    const sala = document.getElementById('select-grado').value;
    const seccionSelect = document.getElementById('select-seccion');
    const docenteSelect = document.getElementById('select-docente');
    seccionSelect.innerHTML = '<option value="">Primero seleccione grado...</option>';
    seccionSelect.disabled = true;
    docenteSelect.innerHTML = '<option value="">Primero seleccione sección...</option>';
    docenteSelect.disabled = true;
    document.getElementById('seccion-mes').style.display = 'block';
    document.getElementById('seccion-boton').style.display = 'none';

    if (sala !== "") {
        seccionSelect.innerHTML = '<option value="">Cargando secciones...</option>';
        const formData = new FormData();
        formData.append('action', 'cargar_secciones');
        formData.append('sala', sala);
        fetch('index.php?ajax=1', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                seccionSelect.innerHTML = '<option value="">Seleccione sección...</option>';
                if (data.secciones && data.secciones.length > 0) {
                    data.secciones.forEach(sec => {
                        seccionSelect.innerHTML += `<option value="${sec.id}">${sec.nombre}</option>`;
                    });
                    seccionSelect.disabled = false;
                } else {
                    seccionSelect.innerHTML = '<option value="">No hay secciones disponibles</option>';
                }
            })
            .catch(() => seccionSelect.innerHTML = '<option value="">Error al cargar</option>');
    }
}

function pasoSeccion() {
    const seccion = document.getElementById('select-seccion').value;
    const docenteSelect = document.getElementById('select-docente');
    docenteSelect.innerHTML = '<option value="">Primero seleccione sección...</option>';
    docenteSelect.disabled = true;
    document.getElementById('seccion-mes').style.display = 'block';
    document.getElementById('seccion-boton').style.display = 'none';

    if (seccion !== "") {
        docenteSelect.innerHTML = '<option value="">Cargando docentes...</option>';
        const formData = new FormData();
        formData.append('action', 'cargar_docentes');
        formData.append('seccion', seccion);
        fetch('index.php?ajax=1', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                docenteSelect.innerHTML = '<option value="">Seleccione docente...</option>';
                if(data.docentes && data.docentes.length > 0) {
                    data.docentes.forEach(d => {
                        docenteSelect.innerHTML += `<option value="${d.id}">${d.nombre}</option>`;
                    });
                    docenteSelect.disabled = false;
                } else {
                    docenteSelect.innerHTML = '<option value="">No hay docentes asignados</option>';
                }
            })
            .catch(() => docenteSelect.innerHTML = '<option value="">Error al cargar</option>');
    }
}

window.pasoDocente = function() {
    const profesor = document.getElementById('select-docente').value;
    const mesDiv = document.getElementById('seccion-mes');
    const botonDiv = document.getElementById('seccion-boton');
    if (profesor !== "") {
        mesDiv.style.display = 'block';
        botonDiv.style.display = 'block';
    } else {
        if (mesDiv) mesDiv.style.display = 'none';
        if (botonDiv) botonDiv.style.display = 'none';
    }
};

// ========== LIMPIAR CAMPOS ==========
function limpiarCero(input) {
    if (input.value === '0') {
        input.value = '';
    }
}

window.limpiarTodo = function() {
    const form = document.getElementById('filtroForm');
    if (form) form.reset();
    document.getElementById('select-seccion').innerHTML = '<option value="">Primero seleccione grado...</option>';
    document.getElementById('select-seccion').disabled = true;
    document.getElementById('select-docente').innerHTML = '<option value="">Primero seleccione sección...</option>';
    document.getElementById('select-docente').disabled = true;
    document.getElementById('seccion-mes').style.display = 'none';
    document.getElementById('seccion-boton').style.display = 'none';
    if (document.getElementById('form-tabla')) {
        document.querySelectorAll('.in-v, .in-h').forEach(input => input.value = '');
        window.recalcular();
    }
};

// ========== FUNCIÓN CALCULAR TOTAL FILA (para clasificación) ==========
function calcularTotalFila(input, tipo) {
    const fila = input.closest('tr');
    if (!fila) return;
    if (tipo === 'venezolano') {
        const v = parseInt(fila.querySelector('.ven-v')?.value) || 0;
        const h = parseInt(fila.querySelector('.ven-h')?.value) || 0;
        const total = v + h;
        const totalInput = fila.querySelector('.ven-total');
        if (totalInput) totalInput.value = total > 0 ? total : '';
    } else if (tipo === 'extranjero') {
        const v = parseInt(fila.querySelector('.ext-v')?.value) || 0;
        const h = parseInt(fila.querySelector('.ext-h')?.value) || 0;
        const total = v + h;
        const totalInput = fila.querySelector('.ext-total');
        if (totalInput) totalInput.value = total > 0 ? total : '';
    }
}

// ========== RECÁLCULO DE ASISTENCIA Y PORCENTAJES ==========
window.recalcular = function() {
    const diasHabilesEl = document.getElementById('dias_hab_val');
    const matVEl = document.getElementById('mat_v');
    const matHEl = document.getElementById('mat_h');
    
    const diasHabiles = diasHabilesEl ? parseInt(diasHabilesEl.value) || 0 : 0;
    const matV = matVEl ? (parseInt(matVEl.value) || 0) : 0;
    const matH = matHEl ? (parseInt(matHEl.value) || 0) : 0;
    const matTotal = matV + matH;
    
    let totalV = 0, totalH = 0;
    const totalesDia = {};
    
    document.querySelectorAll('.in-v').forEach(input => {
        let val = parseInt(input.value);
        if (isNaN(val)) val = 0;
        totalV += val;
        const dia = input.dataset.dia;
        totalesDia[dia] = (totalesDia[dia] || 0) + val;
    });
    
    document.querySelectorAll('.in-h').forEach(input => {
        let val = parseInt(input.value);
        if (isNaN(val)) val = 0;
        totalH += val;
        const dia = input.dataset.dia;
        totalesDia[dia] = (totalesDia[dia] || 0) + val;
    });
    
    const totalGeneral = totalV + totalH;
    
    const capacidadV = matV * diasHabiles;
    const capacidadH = matH * diasHabiles;
    const capacidadTotal = matTotal * diasHabiles;
    
    const porcV = capacidadV > 0 ? Math.round((totalV / capacidadV) * 100) : 0;
    const porcH = capacidadH > 0 ? Math.round((totalH / capacidadH) * 100) : 0;
    const porcTotal = capacidadTotal > 0 ? Math.round((totalGeneral / capacidadTotal) * 100) : 0;
    
    document.getElementById('res_total_v').textContent = totalV > 0 ? totalV : '';
    document.getElementById('res_total_h').textContent = totalH > 0 ? totalH : '';
    document.getElementById('gran_total_asist').textContent = totalGeneral > 0 ? totalGeneral : '';
    
    document.getElementById('res_porc_v').textContent = porcV > 0 ? porcV + '%' : '';
    document.getElementById('res_porc_h').textContent = porcH > 0 ? porcH + '%' : '';
    document.getElementById('gran_total_porc').textContent = porcTotal > 0 ? porcTotal + '%' : '';
    
    document.getElementById('porcentaje_v').value = porcV;
    document.getElementById('porcentaje_h').value = porcH;
    document.getElementById('porcentaje_total').value = porcTotal;
    
    const promedio = diasHabiles > 0 ? (totalGeneral / diasHabiles).toFixed(1) : '0.0';
    const promedioTotalEl = document.getElementById('promedio_total');
    if (promedioTotalEl) promedioTotalEl.textContent = promedio;
    
    for(let d = 1; d <= 31; d++) {
        const td = document.getElementById('total_dia_' + d);
        if (td) {
            const val = totalesDia[d] || 0;
            td.textContent = val > 0 ? val : '-';
        }
    }
    
    // Actualizar totales de clasificación
    recalcularTotalesClasificacion();
};

// ========== RECÁLCULO DE TOTALES DE CLASIFICACIÓN ==========
function recalcularTotalesClasificacion() {
    document.querySelectorAll('.ven-v, .ven-h').forEach(inp => {
        calcularTotalFila(inp, 'venezolano');
    });
    document.querySelectorAll('.ext-v, .ext-h').forEach(inp => {
        calcularTotalFila(inp, 'extranjero');
    });
}

// ========== AGREGAR FILAS DINÁMICAS (CON AUTOCOMPLETADO EN EGRESOS) ==========
function agregarFila(tipo) {
    const tbody = document.querySelector(`#tabla${tipo === 'ingreso' ? 'Ingresos' : 'Egresos'} tbody`);
    const fila = document.createElement('tr');
    fila.className = `fila-${tipo}`;
    
    if (tipo === 'ingreso') {
        fila.innerHTML = `
            <td>
                <input type="text" name="ingreso_apellido[]" class="form-control form-control-sm" placeholder="Apellido">
                <input type="text" name="ingreso_nombre[]" class="form-control form-control-sm mt-1" placeholder="Nombre">
            </td>
            <td>
                <select name="ingreso_genero[]" class="form-select form-select-sm">
                    <option value="V">Varón (V)</option>
                    <option value="H">Hembra (H)</option>
                </select>
            </td>
            <td>
                <select name="ingreso_nacionalidad[]" class="form-select form-select-sm">
                    <option value="Venezolana">Venezolana</option>
                    <option value="Extranjera">Extranjera</option>
                </select>
            </td>
            <td><input type="text" name="ingreso_ci[]" class="form-control form-control-sm" placeholder="Cédula" maxlength="11"></td>
            <td><input type="text" name="ingreso_fn[]" class="form-control form-control-sm" placeholder="DD/MM/YYYY" onfocus="this.type='date'" onblur="if(!this.value)this.type='text'"></td>
            <td><input type="text" name="ingreso_fi[]" class="form-control form-control-sm" placeholder="DD/MM/YYYY" onfocus="this.type='date'" onblur="if(!this.value)this.type='text'"></td>
            <td>
                <button type="button" class="btn btn-sm btn-success confirmar-ingreso me-1" title="Confirmar ingreso">✓</button>
                <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✖</button>
            </td>
        `;
    } else { // EGRESO con autocompletado
        fila.innerHTML = `
            <td style="position: relative;">
                <input type="text" name="egreso_apellido[]" class="form-control form-control-sm egreso-apellido" placeholder="Apellido" autocomplete="off">
                <input type="text" name="egreso_nombre[]" class="form-control form-control-sm mt-1 egreso-nombre" placeholder="Nombre" autocomplete="off">
                <div class="autocomplete-dropdown" style="display:none;"></div>
            </td>
            <td>
                <select name="egreso_genero[]" class="form-select form-select-sm egreso-genero">
                    <option value="V">Varón (V)</option>
                    <option value="H">Hembra (H)</option>
                </select>
                <input type="hidden" name="egreso_estudiante_id[]" class="egreso-id" value="">
            </td>
            <td><input type="text" name="egreso_ci[]" class="form-control form-control-sm egreso-ci" placeholder="Cédula" readonly></td>
            <td><input type="text" name="egreso_fn[]" class="form-control form-control-sm egreso-fn" placeholder="F.Nac." readonly></td>
            <td><input type="text" name="egreso_fi[]" class="form-control form-control-sm" placeholder="F.Ingreso" onfocus="this.type='date'" onblur="if(!this.value)this.type='text'"></td>
            <td>
                <button type="button" class="btn btn-sm btn-success confirmar-egreso me-1" title="Confirmar egreso">✓</button>
                <button type="button" class="btn btn-sm btn-danger eliminar-fila" title="Eliminar fila">✖</button>
            </td>
        `;
    }
    
    tbody.appendChild(fila);
    
    // Si es egreso, activar autocompletado en los nuevos campos
    if (tipo === 'egreso') {
        inicializarAutocompletadoEgreso(fila);
    }
}

// ========== AUTOCOMPLETADO PARA EGRESOS ==========
function inicializarAutocompletadoEgreso(fila) {
    const apellidoInput = fila.querySelector('.egreso-apellido');
    const nombreInput = fila.querySelector('.egreso-nombre');
    const dropdown = fila.querySelector('.autocomplete-dropdown');
    let timeoutId;
    
    const realizarBusqueda = () => {
        clearTimeout(timeoutId);
        const apellido = apellidoInput.value.trim();
        const nombre = nombreInput.value.trim();
        const termino = (apellido + ' ' + nombre).trim();
        
        if (termino.length < 2) {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
            return;
        }
        
        timeoutId = setTimeout(() => {
            const sala = document.getElementById('select-grado').value;
            const seccion = document.getElementById('select-seccion').value;
            const periodo = document.getElementById('select-mes').value; // Obtener periodo
            if (!sala || !seccion || !periodo) {
                dropdown.innerHTML = '<div class="p-2 text-muted small">Seleccione sala, sección y mes primero</div>';
                dropdown.style.display = 'block';
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'buscar_estudiantes');
            formData.append('termino', termino);
            formData.append('sala', sala);
            formData.append('seccion_id', seccion);
            formData.append('periodo', periodo);
            formData.append('tipo', 'egreso'); // <-- IMPORTANTE
            
            fetch('index.php?ajax=1', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    dropdown.innerHTML = '';
                    if (data.estudiantes && data.estudiantes.length > 0) {
                        data.estudiantes.forEach(est => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'dropdown-item small';
                            item.innerHTML = `<strong>${est.nombre_completo}</strong> <span class="text-muted">- ${est.cedula}</span>`;
                            item.addEventListener('click', (e) => {
                                e.preventDefault();
                                seleccionarEstudianteEnFila(fila, est);
                                dropdown.style.display = 'none';
                            });
                            dropdown.appendChild(item);
                        });
                        dropdown.style.display = 'block';
                    } else {
                        dropdown.innerHTML = '<div class="p-2 text-muted small">No se encontraron estudiantes activos disponibles para egreso</div>';
                        dropdown.style.display = 'block';
                    }
                })
                .catch(() => {
                    dropdown.innerHTML = '<div class="p-2 text-danger small">Error de conexión</div>';
                    dropdown.style.display = 'block';
                });
        }, 300);
    };
    
    apellidoInput.addEventListener('input', realizarBusqueda);
    nombreInput.addEventListener('input', realizarBusqueda);
    
    apellidoInput.addEventListener('blur', () => {
        setTimeout(() => { dropdown.style.display = 'none'; }, 200);
    });
    nombreInput.addEventListener('blur', () => {
        setTimeout(() => { dropdown.style.display = 'none'; }, 200);
    });
}

// ========== SELECCIONAR ESTUDIANTE EN LA FILA ==========
function seleccionarEstudianteEnFila(fila, estudiante) {
    const partes = estudiante.nombre_completo.split(' ');
    const apellido = partes.slice(1).join(' ') || partes[0];
    const nombre = partes[0] || '';
    
    fila.querySelector('.egreso-apellido').value = apellido;
    fila.querySelector('.egreso-nombre').value = nombre;
    fila.querySelector('.egreso-id').value = estudiante.id;
    fila.querySelector('.egreso-genero').value = estudiante.genero || 'V';
    fila.querySelector('.egreso-ci').value = estudiante.cedula || '';
    fila.querySelector('.egreso-fn').value = estudiante.fecha_nacimiento || '';
    // Fecha de egreso por defecto hoy
    const fiInput = fila.querySelector('[name="egreso_fi[]"]');
    if (!fiInput.value) {
        const hoy = new Date().toISOString().split('T')[0];
        fiInput.value = hoy;
    }
}

// ========== EVENTOS PARA BOTONES CONFIRMAR ==========
document.addEventListener('click', function(e) {
    // Confirmar ingreso (AHORA CON RESPUESTA JSON Y SIN REDIRECCIÓN)
    if (e.target.classList.contains('confirmar-ingreso')) {
        e.preventDefault();
        const fila = e.target.closest('tr');
        const apellido = fila.querySelector('[name="ingreso_apellido[]"]').value.trim();
        const nombre = fila.querySelector('[name="ingreso_nombre[]"]').value.trim();
        if (!apellido || !nombre) {
            alert('Complete al menos apellido y nombre.');
            return;
        }
        
        const sala = document.getElementById('select-grado').value;
        const seccion = document.getElementById('select-seccion').value;
        const periodo = document.getElementById('select-mes').value;
        
        if (!sala || !seccion || !periodo) {
            alert('Seleccione sala, sección y mes antes de confirmar.');
            return;
        }
        
        const btn = e.target;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const formData = new FormData();
        formData.append('action', 'confirmar_ingreso');
        formData.append('apellido', apellido);
        formData.append('nombre', nombre);
        formData.append('genero', fila.querySelector('[name="ingreso_genero[]"]').value);
        formData.append('cedula', fila.querySelector('[name="ingreso_ci[]"]').value);
        formData.append('fecha_nacimiento', fila.querySelector('[name="ingreso_fn[]"]').value);
        formData.append('sala', sala);
        formData.append('seccion', seccion);
        formData.append('periodo', periodo);
        formData.append('csrf_token', '<?= $csrf_token ?>');
        formData.append('estudiante_id', '0');
        
        fetch('index.php?ajax=1', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.mensaje);
                    location.reload(); // Recarga para actualizar la tabla de ingresos
                } else {
                    alert('Error: ' + (data.error || 'No se pudo ingresar'));
                }
            })
            .catch(() => alert('Error de conexión'))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '✓';
            });
    }
    
    // Confirmar egreso (se mantiene igual, ya redirige a egresados.php)
    if (e.target.classList.contains('confirmar-egreso')) {
        e.preventDefault();
        const fila = e.target.closest('tr');
        const idEstudiante = fila.querySelector('.egreso-id')?.value;
        if (!idEstudiante) {
            alert('Primero busque y seleccione un estudiante del autocompletado.');
            return;
        }
        
        const sala = document.getElementById('select-grado').value;
        const seccion = document.getElementById('select-seccion').value;
        const periodo = document.getElementById('select-mes').value;
        const motivo = 'Retiro';
        
        if (!sala || !seccion || !periodo) {
            alert('Seleccione sala, sección y mes antes de confirmar.');
            return;
        }
        
        const btn = e.target;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const formData = new FormData();
        formData.append('action', 'confirmar_egreso');
        formData.append('estudiante_id', idEstudiante);
        formData.append('sala', sala);
        formData.append('seccion', seccion);
        formData.append('periodo', periodo);
        formData.append('motivo', motivo);
        formData.append('csrf_token', '<?= $csrf_token ?>');
        
        fetch('index.php?ajax=1', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'egresados.php';
                } else {
                    alert('Error: ' + (data.error || 'No se pudo egresar'));
                }
            })
            .catch(() => alert('Error de conexión'))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '✓';
            });
    }
    
    // Eliminar fila
    if (e.target.classList.contains('eliminar-fila')) {
        e.target.closest('tr').remove();
        recalcularTotalesClasificacion();
    }
});

// ========== CARGAR EGRESOS EXISTENTES ==========
function cargarEgresosExistentes() {
    const sala = document.getElementById('select-grado').value;
    const seccion = document.getElementById('select-seccion').value;
    const periodo = document.getElementById('select-mes').value;
    if (!sala || !seccion || !periodo) return;
    
    const formData = new FormData();
    formData.append('action', 'cargar_egresos_existentes');
    formData.append('sala', sala);
    formData.append('seccion', seccion);
    formData.append('periodo', periodo);
    
    fetch('index.php?ajax=1', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            const tbody = document.querySelector('#tablaEgresos tbody');
            tbody.innerHTML = '';
            if (data.egresos && data.egresos.length > 0) {
                data.egresos.forEach(eg => {
                    const fila = document.createElement('tr');
                    fila.className = 'fila-egreso';
                    fila.innerHTML = `
                        <td>
                            <input type="text" class="form-control form-control-sm" value="${eg.nombre_completo || ''}" readonly>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" value="${eg.genero === 'V' ? 'Varón' : 'Hembra'}" readonly>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" value="${eg.ci || ''}" readonly></td>
                        <td><input type="text" class="form-control form-control-sm" value="${eg.fn || ''}" readonly></td>
                        <td><input type="text" class="form-control form-control-sm" value="${eg.fi || ''}" readonly></td>
                        <td></td>
                    `;
                    tbody.appendChild(fila);
                });
            }
            recalcularTotalesClasificacion();
        })
        .catch(err => console.error('Error cargando egresos:', err));
}

// ========== EVENTOS DE CARGA ==========
document.getElementById('form-tabla')?.addEventListener('submit', setTipoReporte);

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('form-tabla')) {
        window.recalcular();
    }
    
    const docenteSelect = document.getElementById('select-docente');
    const mesInput = document.getElementById('select-mes');
    
    function triggerCargaEgresos() {
        if (docenteSelect && docenteSelect.value && mesInput && mesInput.value) {
            cargarEgresosExistentes();
        }
    }
    
    if (docenteSelect) {
        docenteSelect.addEventListener('change', triggerCargaEgresos);
    }
    if (mesInput) {
        mesInput.addEventListener('change', triggerCargaEgresos);
    }
    
    if (docenteSelect && docenteSelect.value && mesInput && mesInput.value) {
        cargarEgresosExistentes();
    }
});
</script>

<?php include "../includes/footer.php"; ?>