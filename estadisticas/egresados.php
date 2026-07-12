<?php
// ============================================================================
// CONFIGURACIÓN Y SEGURIDAD
// ============================================================================
require_once "config_db.php";

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
    
    // REVERTIR EGRESO (reactivar estudiante)
    if ($action === 'revertir_egreso') {
        if (!isset($_POST['csrf_token']) || !verificarTokenCSRF($_POST['csrf_token'])) {
            responderJSON(['success' => false, 'error' => 'Token CSRF inválido'], 403);
        }
        
        $egreso_id = filter_var($_POST['egreso_id'] ?? 0, FILTER_VALIDATE_INT);
        
        if (!$egreso_id) {
            responderJSON(['success' => false, 'error' => 'ID de egreso inválido'], 400);
        }
        
        try {
            $conexion->begin_transaction();
            
            $stmt = $conexion->prepare("SELECT estudiante_id FROM egresos WHERE id = ?");
            $stmt->bind_param("i", $egreso_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $egreso = $result->fetch_assoc();
            
            if (!$egreso) {
                throw new Exception("Egreso no encontrado");
            }
            
            $estudiante_id = $egreso['estudiante_id'];
            
            $stmt_del = $conexion->prepare("DELETE FROM egresos WHERE id = ?");
            $stmt_del->bind_param("i", $egreso_id);
            $stmt_del->execute();
            
            $stmt_up = $conexion->prepare("UPDATE estudiantes SET estatus = 'Activo' WHERE id = ?");
            $stmt_up->bind_param("i", $estudiante_id);
            $stmt_up->execute();
            
            $conexion->commit();
            responderJSON(['success' => true, 'mensaje' => 'Egreso revertido exitosamente. Estudiante reactivado.']);
            
        } catch (Exception $e) {
            $conexion->rollback();
            logError('Error al revertir egreso', ['egreso_id' => $egreso_id, 'error' => $e->getMessage()]);
            responderJSON(['success' => false, 'error' => 'Error al revertir: ' . $e->getMessage()], 500);
        }
        exit;
    }
    
    // ELIMINAR EGRESO Y ESTUDIANTE (borrado permanente)
    if ($action === 'eliminar_egreso') {
        if (!isset($_POST['csrf_token']) || !verificarTokenCSRF($_POST['csrf_token'])) {
            responderJSON(['success' => false, 'error' => 'Token CSRF inválido'], 403);
        }
        
        $egreso_id = filter_var($_POST['egreso_id'] ?? 0, FILTER_VALIDATE_INT);
        
        if (!$egreso_id) {
            responderJSON(['success' => false, 'error' => 'ID de egreso inválido'], 400);
        }
        
        try {
            $conexion->begin_transaction();
            
            // Obtener el estudiante_id
            $stmt = $conexion->prepare("SELECT estudiante_id FROM egresos WHERE id = ?");
            $stmt->bind_param("i", $egreso_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $egreso = $result->fetch_assoc();
            
            if (!$egreso) {
                throw new Exception("Egreso no encontrado");
            }
            
            $estudiante_id = $egreso['estudiante_id'];
            
            // Eliminar el registro de egreso
            $stmt_del_egreso = $conexion->prepare("DELETE FROM egresos WHERE id = ?");
            $stmt_del_egreso->bind_param("i", $egreso_id);
            $stmt_del_egreso->execute();
            
            // Eliminar al estudiante de la tabla estudiantes
            $stmt_del_est = $conexion->prepare("DELETE FROM estudiantes WHERE id = ?");
            $stmt_del_est->bind_param("i", $estudiante_id);
            $stmt_del_est->execute();
            
            $conexion->commit();
            responderJSON(['success' => true, 'mensaje' => 'Egreso y estudiante eliminados permanentemente.']);
            
        } catch (Exception $e) {
            $conexion->rollback();
            logError('Error al eliminar egreso y estudiante', ['egreso_id' => $egreso_id, 'error' => $e->getMessage()]);
            responderJSON(['success' => false, 'error' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
        exit;
    }
    
    if ($action === 'cargar_secciones') {
        $sala = sanitizarEntrada($_POST['sala'] ?? '');
        if (empty($sala)) {
            responderJSON(['secciones' => []]);
        }
        try {
            $stmt = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
            $stmt->bind_param("s", $sala);
            $stmt->execute();
            $result = $stmt->get_result();
            $secciones = [];
            while ($row = $result->fetch_assoc()) {
                $secciones[] = ['id' => (int)$row['id'], 'nombre' => htmlspecialchars($row['nombre'])];
            }
            responderJSON(['secciones' => $secciones]);
        } catch (Exception $e) {
            responderJSON(['secciones' => [], 'error' => $e->getMessage()], 500);
        }
        exit;
    }
    
    responderJSON(['error' => 'Acción no válida'], 400);
    exit;
}

// ============================================================================
// PROCESAR ACCIONES POR POST TRADICIONAL (RESPALDO)
// ============================================================================
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revertir_egreso_id'])) {
    // ... (código existente de reversión por POST)
}

// ============================================================================
// CARGAR VISTA PRINCIPAL
// ============================================================================
include "../includes/header.php";

// Filtros (código existente sin cambios)
$filtro_sala = sanitizarEntrada($_GET['sala'] ?? '');
$filtro_seccion = filter_var($_GET['seccion'] ?? 0, FILTER_VALIDATE_INT);
$filtro_periodo = sanitizarEntrada($_GET['periodo'] ?? '');
$filtro_busqueda = sanitizarEntrada($_GET['busqueda'] ?? '');
$pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta (sin cambios)
$where = [];
$params = [];
$tipos = '';

if (!empty($filtro_sala)) {
    $where[] = "e.sala = ?";
    $params[] = $filtro_sala;
    $tipos .= 's';
}

if (!empty($filtro_seccion)) {
    $where[] = "e.seccion_id = ?";
    $params[] = $filtro_seccion;
    $tipos .= 'i';
}

if (!empty($filtro_periodo)) {
    $where[] = "e.periodo = ?";
    $params[] = $filtro_periodo;
    $tipos .= 's';
}

if (!empty($filtro_busqueda)) {
    $termino = "%$filtro_busqueda%";
    $where[] = "(est.nombre LIKE ? OR est.apellido LIKE ? OR est.cedula LIKE ? OR CONCAT(est.apellido, ' ', est.nombre) LIKE ?)";
    $params[] = $termino;
    $params[] = $termino;
    $params[] = $termino;
    $params[] = $termino;
    $tipos .= 'ssss';
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Contar total
$sql_count = "
    SELECT COUNT(*) as total 
    FROM egresos e 
    LEFT JOIN estudiantes est ON e.estudiante_id = est.id 
    $whereSQL
";

$stmt_count = $conexion->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($tipos, ...$params);
}
$stmt_count->execute();
$total_egresos = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_egresos / $por_pagina);

// Consulta principal (ya corregida, sin created_at)
$sql = "
    SELECT 
        e.id AS egreso_id,
        e.estudiante_id,
        e.sala,
        e.seccion_id,
        e.periodo,
        e.fecha_egreso,
        e.motivo,
        e.genero AS genero_egreso,
        e.fecha_egreso AS fecha_registro,
        est.nombre,
        est.apellido,
        est.cedula,
        est.nacionalidad,
        est.genero AS genero_est,
        est.fecha_nacimiento,
        sec.nombre AS nombre_seccion
    FROM egresos e 
    LEFT JOIN estudiantes est ON e.estudiante_id = est.id 
    LEFT JOIN secciones sec ON e.seccion_id = sec.id
    $whereSQL
    ORDER BY e.fecha_egreso DESC
    LIMIT ? OFFSET ?
";

$params_paginados = $params;
$params_paginados[] = $por_pagina;
$params_paginados[] = $offset;
$tipos_paginados = $tipos . 'ii';

$stmt = $conexion->prepare($sql);
if (!empty($params_paginados)) {
    $stmt->bind_param($tipos_paginados, ...$params_paginados);
}
$stmt->execute();
$egresos = $stmt->get_result();

// Salas disponibles (sin cambios)
$salas_disponibles = [];
try {
    $result_salas = $conexion->query("SELECT DISTINCT sala FROM egresos ORDER BY sala");
    while ($row = $result_salas->fetch_assoc()) {
        $salas_disponibles[] = $row['sala'];
    }
} catch (Exception $e) {
    logError('Error al cargar salas', ['error' => $e->getMessage()]);
}

$nombres_salas = [
    'sala4' => 'Sala 4 Años',
    'sala5' => 'Sala 5 Años',
    '1ro' => '1° Grado',
    '2do' => '2° Grado',
    '3ro' => '3° Grado',
    '4to' => '4° Grado',
    '5to' => '5° Grado',
    '6to' => '6° Grado'
];

$csrf_token = generarTokenCSRF();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Egresos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --navy: #002d54;
            --primary-gradient: linear-gradient(135deg, #002d54 0%, #004a7c 100%);
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        .page-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 12px;
            padding: 20px 28px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,45,84,0.2);
        }
        
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            transition: box-shadow 0.3s ease;
            margin-bottom: 24px;
        }
        .card:hover {
            box-shadow: 0 6px 30px rgba(0,0,0,0.10);
        }
        .card-header {
            background: var(--primary-gradient) !important;
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 14px 20px;
            font-weight: 600;
        }
        
        .table-egresos {
            font-size: 0.875rem;
            vertical-align: middle;
        }
        .table-egresos thead th {
            background-color: #f0f4f8;
            color: var(--navy);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #002d54;
        }
        .table-egresos tbody tr {
            transition: background-color 0.2s ease;
        }
        .table-egresos tbody tr:hover {
            background-color: #e8f4f8;
        }
        
        .badge-estatus {
            font-size: 0.7rem;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        .badge-inactivo {
            background-color: #dc3545;
            color: white;
        }
        .badge-sala {
            background-color: #e9ecef;
            color: var(--navy);
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.78rem;
        }
        
        .btn-revertir {
            background: none;
            border: 1.5px solid #dc3545;
            color: #dc3545;
            border-radius: 8px;
            padding: 5px 14px;
            font-size: 0.78rem;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-right: 4px;
        }
        .btn-revertir:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(220,53,69,0.25);
        }
        
        .btn-eliminar {
            background: none;
            border: 1.5px solid #dc3545;
            background-color: #dc3545;
            color: white;
            border-radius: 8px;
            padding: 5px 14px;
            font-size: 0.78rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-eliminar:hover {
            background-color: #bb2d3b;
            border-color: #bb2d3b;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(220,53,69,0.4);
        }
        
        .btn-filtro {
            background: var(--primary-gradient);
            border: none;
            color: white;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-filtro:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,45,84,0.3);
            color: white;
        }
        
        .btn-limpiar {
            background: white;
            border: 1.5px solid #6c757d;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-limpiar:hover {
            background: #6c757d;
            color: white;
        }
        
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 3px;
            color: var(--navy);
            font-weight: 500;
        }
        .pagination .page-item.active .page-link {
            background: var(--primary-gradient);
            border-color: transparent;
        }
        
        .estado-vacio {
            padding: 50px 20px;
            text-align: center;
            color: #6c757d;
        }
        .estado-vacio i {
            font-size: 3rem;
            color: #adb5bd;
            margin-bottom: 15px;
        }
        
        .info-egreso {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .table-egresos {
                font-size: 0.7rem;
            }
            .btn-revertir, .btn-eliminar {
                font-size: 0.65rem;
                padding: 3px 8px;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    
    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?: 'info' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>
    
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-times me-2"></i> Historial de Egresos
            </h4>
            <small class="opacity-75">
                <i class="fas fa-database me-1"></i> Registro de estudiantes egresados
            </small>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="index.php" class="btn btn-light fw-bold">
                <i class="fas fa-arrow-left me-2"></i> Volver al Control de Asistencia
            </a>
        </div>
    </div>
    
    <!-- Filtros (sin cambios) -->
    <div class="card">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end" id="filtroForm" autocomplete="off">
                <!-- ... exactamente igual que antes ... -->
                <div class="col-md-2">
                    <label class="small fw-bold text-muted" for="filtro-sala"><i class="fas fa-graduation-cap"></i> Sala / Grado</label>
                    <select name="sala" id="filtro-sala" class="form-select shadow-none" onchange="cargarSeccionesFiltro()">
                        <option value="">Todas las salas</option>
                        <?php foreach ($salas_disponibles as $sala_disp): 
                            $nombre_sala = $nombres_salas[$sala_disp] ?? $sala_disp; ?>
                            <option value="<?= htmlspecialchars($sala_disp) ?>" <?= ($filtro_sala == $sala_disp) ? 'selected' : '' ?>><?= htmlspecialchars($nombre_sala) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted" for="filtro-seccion"><i class="fas fa-layer-group"></i> Sección</label>
                    <select name="seccion" id="filtro-seccion" class="form-select shadow-none" <?= empty($filtro_sala) ? 'disabled' : '' ?>>
                        <option value="">Todas las secciones</option>
                        <?php if ($filtro_sala): 
                            $stmt_sec = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
                            $stmt_sec->bind_param("s", $filtro_sala);
                            $stmt_sec->execute();
                            $result_sec = $stmt_sec->get_result();
                            while ($sec = $result_sec->fetch_assoc()): ?>
                                <option value="<?= $sec['id'] ?>" <?= ($filtro_seccion == $sec['id']) ? 'selected' : '' ?>><?= htmlspecialchars($sec['nombre']) ?></option>
                            <?php endwhile; $stmt_sec->close(); endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted" for="filtro-periodo"><i class="fas fa-calendar-alt"></i> Período</label>
                    <input type="month" name="periodo" id="filtro-periodo" class="form-control shadow-none" value="<?= htmlspecialchars($filtro_periodo) ?>">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted" for="filtro-busqueda"><i class="fas fa-search"></i> Buscar estudiante</label>
                    <input type="text" name="busqueda" id="filtro-busqueda" class="form-control shadow-none" placeholder="Nombre, apellido o cédula..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                </div>
                <div class="col-md-3 text-end d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-filtro px-4"><i class="fas fa-filter me-2"></i> Filtrar</button>
                    <a href="egresados.php" class="btn btn-limpiar px-3"><i class="fas fa-eraser me-2"></i> Limpiar</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla de Egresos -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fas fa-list-ul me-2"></i> Registro de Egresos
                <span class="badge bg-light text-dark ms-2"><?= $total_egresos ?> registro(s)</span>
            </h6>
            <small class="opacity-75">
                <i class="fas fa-info-circle me-1"></i> Use "Revertir" para reactivar, "Eliminar" para borrado permanente
            </small>
        </div>
        
        <div class="card-body p-0">
            <?php if ($total_egresos > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-egresos mb-0">
                        <thead>
                            <tr>
                                <th style="width:5%">#</th>
                                <th style="width:16%">Estudiante</th>
                                <th style="width:9%">Cédula</th>
                                <th style="width:7%">Género</th>
                                <th style="width:10%">Sala / Sección</th>
                                <th style="width:8%">Período</th>
                                <th style="width:9%">F. Egreso</th>
                                <th style="width:14%">Motivo</th>
                                <th style="width:7%">Estatus</th>
                                <th style="width:15%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $contador = $offset + 1;
                            while ($egreso = $egresos->fetch_assoc()): 
                                $nombre_completo = htmlspecialchars(($egreso['apellido'] ?? '') . ' ' . ($egreso['nombre'] ?? 'Estudiante eliminado'));
                                $nombre_seccion = htmlspecialchars($egreso['nombre_seccion'] ?? 'N/A');
                                $sala_nombre = $nombres_salas[$egreso['sala']] ?? htmlspecialchars($egreso['sala']);
                            ?>
                                <tr id="fila-egreso-<?= $egreso['egreso_id'] ?>">
                                    <td class="text-center fw-bold text-muted"><?= $contador++ ?></td>
                                    <td>
                                        <strong><?= $nombre_completo ?></strong>
                                        <?php if (!empty($egreso['nacionalidad'])): ?>
                                            <br><small class="info-egreso"><i class="fas fa-flag me-1"></i> <?= htmlspecialchars($egreso['nacionalidad']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="font-monospace"><?= htmlspecialchars($egreso['cedula'] ?? 'N/A') ?></span></td>
                                    <td>
                                        <?php 
                                        $gen = $egreso['genero_egreso'] ?? $egreso['genero_est'] ?? '';
                                        if ($gen === 'V' || $gen === 'Varón'): ?>
                                            <span class="text-primary fw-bold"><i class="fas fa-male me-1"></i> Varón</span>
                                        <?php elseif ($gen === 'H' || $gen === 'Hembra'): ?>
                                            <span class="text-danger fw-bold"><i class="fas fa-female me-1"></i> Hembra</span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-sala"><?= $sala_nombre ?></span>
                                        <?php if ($nombre_seccion !== 'N/A'): ?>
                                            <br><small class="info-egreso">Sec. <?= $nombre_seccion ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><span class="font-monospace small"><?= htmlspecialchars($egreso['periodo']) ?></span></td>
                                    <td class="text-center"><?= date('d/m/Y', strtotime($egreso['fecha_egreso'])) ?></td>
                                    <td>
                                        <?php 
                                        $motivo = htmlspecialchars($egreso['motivo'] ?? 'No especificado');
                                        $icono_motivo = 'fa-circle-info';
                                        if (stripos($egreso['motivo'] ?? '', 'cambio') !== false) $icono_motivo = 'fa-arrow-right-arrow-left';
                                        elseif (stripos($egreso['motivo'] ?? '', 'económico') !== false) $icono_motivo = 'fa-dollar-sign';
                                        elseif (stripos($egreso['motivo'] ?? '', 'enfermedad') !== false) $icono_motivo = 'fa-heart-pulse';
                                        ?>
                                        <i class="fas <?= $icono_motivo ?> me-1 text-muted"></i> <?= $motivo ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-estatus badge-inactivo"><i class="fas fa-user-slash me-1"></i> Inactivo</span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-revertir btn-sm"
                                                onclick="revertirEgreso(<?= $egreso['egreso_id'] ?>, '<?= htmlspecialchars($nombre_completo, ENT_QUOTES) ?>')"
                                                title="Revertir egreso y reactivar estudiante">
                                            <i class="fas fa-undo me-1"></i> Revertir
                                        </button>
                                        <button type="button" class="btn btn-eliminar btn-sm"
                                                onclick="eliminarEgreso(<?= $egreso['egreso_id'] ?>, '<?= htmlspecialchars($nombre_completo, ENT_QUOTES) ?>')"
                                                title="Eliminar permanentemente egreso y estudiante">
                                            <i class="fas fa-trash-alt me-1"></i> Eliminar
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación (sin cambios) -->
                <?php if ($total_paginas > 1): ?>
                    <div class="d-flex justify-content-center py-3 border-top">
                        <nav>
                            <ul class="pagination mb-0">
                                <?php 
                                $query_params = $_GET;
                                unset($query_params['pagina']);
                                $base_url = 'egresados.php?' . http_build_query($query_params);
                                if (!empty($query_params)) $base_url .= '&';
                                ?>
                                <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $base_url ?>pagina=<?= $pagina - 1 ?>"><i class="fas fa-chevron-left"></i></a>
                                </li>
                                <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                                    <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= $base_url ?>pagina=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $base_url ?>pagina=<?= $pagina + 1 ?>"><i class="fas fa-chevron-right"></i></a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="estado-vacio">
                    <i class="fas fa-inbox"></i>
                    <h5 class="fw-bold">No se encontraron egresos</h5>
                    <p class="mb-0">
                        <?php if (!empty($filtro_sala) || !empty($filtro_busqueda) || !empty($filtro_periodo)): ?>
                            No hay egresos que coincidan con los filtros aplicados. <a href="egresados.php" class="text-primary">Limpiar filtros</a>
                        <?php else: ?>
                            Aún no se ha registrado ningún egreso en el sistema.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small"><i class="fas fa-clock me-1"></i> Última actualización: <?= date('d/m/Y H:i:s') ?></span>
            <span class="text-muted small">Mostrando <?= min($por_pagina, $total_egresos - $offset) ?> de <?= $total_egresos ?> egresos</span>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Revertir -->
<div class="modal fade" id="modalRevertir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; border: none;">
            <div class="modal-header bg-warning text-dark" style="border-radius: 14px 14px 0 0;">
                <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Confirmar Reversión</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-1">¿Está seguro de <strong>revertir</strong> el egreso de:</p>
                <p class="fs-5 fw-bold text-center text-navy mb-2" id="nombre-estudiante-revertir"></p>
                <p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i> Esta acción <strong>reactivará</strong> al estudiante y eliminará el registro de egreso.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning fw-bold" id="btn-confirmar-revertir">
                    <i class="fas fa-undo me-2"></i> Sí, Revertir Egreso
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; border: none;">
            <div class="modal-header bg-danger text-white" style="border-radius: 14px 14px 0 0;">
                <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Confirmar Eliminación</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-1">¿Está seguro de <strong>eliminar permanentemente</strong> el egreso y al estudiante:</p>
                <p class="fs-5 fw-bold text-center text-danger mb-2" id="nombre-estudiante-eliminar"></p>
                <p class="small text-muted mb-0"><i class="fas fa-exclamation-circle me-1"></i> <strong>Esta acción no se puede deshacer.</strong> Se borrará el registro de egreso y el estudiante de la base de datos.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger fw-bold" id="btn-confirmar-eliminar">
                    <i class="fas fa-trash-alt me-2"></i> Sí, Eliminar Permanentemente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS (necesario para los modales) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- JavaScript -->
<script>
// ============================================================================
// VARIABLES Y FUNCIONES
// ============================================================================
let egresoIdARevertir = null;
let egresoIdAEliminar = null;
const modalRevertir = new bootstrap.Modal(document.getElementById('modalRevertir'));
const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));

function cargarSeccionesFiltro() {
    const sala = document.getElementById('filtro-sala').value;
    const seccionSelect = document.getElementById('filtro-seccion');
    
    seccionSelect.innerHTML = '<option value="">Cargando...</option>';
    seccionSelect.disabled = true;
    
    if (!sala) {
        seccionSelect.innerHTML = '<option value="">Todas las secciones</option>';
        seccionSelect.disabled = true;
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'cargar_secciones');
    formData.append('sala', sala);
    
    fetch('egresados.php?ajax=1', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            seccionSelect.innerHTML = '<option value="">Todas las secciones</option>';
            if (data.secciones && data.secciones.length > 0) {
                data.secciones.forEach(sec => seccionSelect.add(new Option(sec.nombre, sec.id)));
                seccionSelect.disabled = false;
            } else {
                seccionSelect.innerHTML = '<option value="">Sin secciones</option>';
            }
        })
        .catch(err => {
            console.error('Error al cargar secciones:', err);
            seccionSelect.innerHTML = '<option value="">Error al cargar</option>';
        });
}

function revertirEgreso(egresoId, nombreEstudiante) {
    egresoIdARevertir = egresoId;
    document.getElementById('nombre-estudiante-revertir').textContent = nombreEstudiante;
    modalRevertir.show();
}

function eliminarEgreso(egresoId, nombreEstudiante) {
    egresoIdAEliminar = egresoId;
    document.getElementById('nombre-estudiante-eliminar').textContent = nombreEstudiante;
    modalEliminar.show();
}

// Confirmar Revertir
document.getElementById('btn-confirmar-revertir').addEventListener('click', function() {
    if (!egresoIdARevertir) return;
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Revirtiendo...';
    
    const formData = new FormData();
    formData.append('action', 'revertir_egreso');
    formData.append('egreso_id', egresoIdARevertir);
    formData.append('csrf_token', '<?= $csrf_token ?>');
    
    fetch('egresados.php?ajax=1', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const fila = document.getElementById('fila-egreso-' + egresoIdARevertir);
                if (fila) {
                    fila.style.transition = 'all 0.4s ease';
                    fila.style.opacity = '0';
                    fila.style.transform = 'translateX(30px)';
                    setTimeout(() => fila.remove(), 400);
                }
                modalRevertir.hide();
                mostrarMensaje('success', data.mensaje);
                verificarRecarga();
            } else {
                alert('Error: ' + (data.error || 'No se pudo revertir el egreso.'));
                modalRevertir.hide();
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error de conexión al revertir el egreso.');
            modalRevertir.hide();
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-undo me-2"></i> Sí, Revertir Egreso';
        });
});

// Confirmar Eliminar
document.getElementById('btn-confirmar-eliminar').addEventListener('click', function() {
    if (!egresoIdAEliminar) return;
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Eliminando...';
    
    const formData = new FormData();
    formData.append('action', 'eliminar_egreso');
    formData.append('egreso_id', egresoIdAEliminar);
    formData.append('csrf_token', '<?= $csrf_token ?>');
    
    fetch('egresados.php?ajax=1', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const fila = document.getElementById('fila-egreso-' + egresoIdAEliminar);
                if (fila) {
                    fila.style.transition = 'all 0.4s ease';
                    fila.style.opacity = '0';
                    fila.style.transform = 'translateX(30px)';
                    setTimeout(() => fila.remove(), 400);
                }
                modalEliminar.hide();
                mostrarMensaje('danger', data.mensaje);
                verificarRecarga();
            } else {
                alert('Error: ' + (data.error || 'No se pudo eliminar el egreso.'));
                modalEliminar.hide();
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error de conexión al eliminar el egreso.');
            modalEliminar.hide();
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash-alt me-2"></i> Sí, Eliminar Permanentemente';
        });
});

function mostrarMensaje(tipo, texto) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'trash-alt'} me-2"></i> ${texto}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    `;
    document.querySelector('.container-fluid').prepend(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
}

function verificarRecarga() {
    setTimeout(() => {
        if (document.querySelectorAll('.table-egresos tbody tr').length === 0) {
            location.reload();
        }
    }, 1000);
}

// Limpiar IDs al cerrar modales
document.getElementById('modalRevertir').addEventListener('hidden.bs.modal', () => egresoIdARevertir = null);
document.getElementById('modalEliminar').addEventListener('hidden.bs.modal', () => egresoIdAEliminar = null);
</script>

<?php include "../includes/footer.php"; ?>