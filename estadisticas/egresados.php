<?php
// ============================================================================
// CONFIGURACIÓN Y SEGURIDAD
// ============================================================================
require_once "../config/conexion.php";

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/errores.log');

if (session_status() === PHP_SESSION_NONE) session_start();

function verificarAutenticacion($ajax = false) {
    if (!isset($_SESSION['usuario'])) {
        if ($ajax) {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        header('Location: /servicio-comunitario/profesores/Login/login.php');
        exit;
    }
    return true;
}

$esAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';
verificarAutenticacion($esAjax);

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

function logError($mensaje, $contexto = []) {
    error_log(sprintf("[%s] %s %s", date('Y-m-d H:i:s'), $mensaje, json_encode($contexto)));
}

// ============================================================================
// AJAX HANDLER
// ============================================================================
if ($esAjax) {
    $action = sanitizarEntrada($_POST['action'] ?? '');
    
    // ===== CARGAR SECCIONES (para el select de sección) =====
    if ($action === 'cargar_secciones') {
        $sala = sanitizarEntrada($_POST['sala'] ?? '');
        if (empty($sala)) responderJSON(['secciones' => []]);
        $stmt = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
        $stmt->bind_param("s", $sala);
        $stmt->execute();
        $result = $stmt->get_result();
        $secciones = [];
        while ($row = $result->fetch_assoc()) {
            $secciones[] = ['id' => (int)$row['id'], 'nombre' => htmlspecialchars($row['nombre'])];
        }
        responderJSON(['secciones' => $secciones]);
    }
    
    // ===== CARGAR EGRESOS FILTRADOS (nuevo) =====
    if ($action === 'cargar_egresos') {
        $filtro_sala = sanitizarEntrada($_POST['sala'] ?? '');
        $filtro_seccion = filter_var($_POST['seccion'] ?? 0, FILTER_VALIDATE_INT);
        $filtro_periodo = sanitizarEntrada($_POST['periodo'] ?? '');
        $filtro_busqueda = sanitizarEntrada($_POST['busqueda'] ?? '');
        $pagina = filter_var($_POST['pagina'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        $por_pagina = 20;
        $offset = ($pagina - 1) * $por_pagina;
        
        // Construir consulta
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
            $params[] = $termino; $params[] = $termino; $params[] = $termino; $params[] = $termino;
            $tipos .= 'ssss';
        }
        
        // Siempre filtrar por inscripción completa (para mostrar solo egresos de estudiantes que estuvieron completos)
        $where[] = "est.inscripcion_completa = 1";
        
        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Contar total
        $sql_count = "SELECT COUNT(*) as total FROM egresos e LEFT JOIN estudiantes est ON e.estudiante_id = est.id $whereSQL";
        $stmt_count = $conexion->prepare($sql_count);
        if (!empty($params)) $stmt_count->bind_param($tipos, ...$params);
        $stmt_count->execute();
        $total_egresos = $stmt_count->get_result()->fetch_assoc()['total'];
        $total_paginas = ceil($total_egresos / $por_pagina);
        
        // Consulta principal
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
            ORDER BY e.fecha_egreso DESC, e.id DESC
            LIMIT ? OFFSET ?
        ";
        
        $params_paginados = $params;
        $params_paginados[] = $por_pagina;
        $params_paginados[] = $offset;
        $tipos_paginados = $tipos . 'ii';
        
        $stmt = $conexion->prepare($sql);
        if (!empty($params_paginados)) $stmt->bind_param($tipos_paginados, ...$params_paginados);
        $stmt->execute();
        $egresos = $stmt->get_result();
        
        // Generar HTML de la tabla
        $html_tabla = '';
        if ($total_egresos > 0) {
            $nombres_salas = [
                'sala4' => 'Sala 4 Años', 'sala5' => 'Sala 5 Años',
                '1ro' => '1° Grado', '2do' => '2° Grado', '3ro' => '3° Grado',
                '4to' => '4° Grado', '5to' => '5° Grado', '6to' => '6° Grado'
            ];
            $contador = $offset + 1;
            ob_start();
            ?>
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
                        <?php while ($egreso = $egresos->fetch_assoc()): 
                            $nombre_completo = htmlspecialchars(($egreso['apellido'] ?? '') . ' ' . ($egreso['nombre'] ?? 'Estudiante eliminado'));
                            $sala_nombre = $nombres_salas[$egreso['sala']] ?? htmlspecialchars($egreso['sala']);
                        ?>
                            <tr id="fila-egreso-<?= $egreso['egreso_id'] ?>">
                                <td class="text-center fw-bold text-muted"><?= $contador++ ?></td>
                                <td><strong><?= $nombre_completo ?></strong></td>
                                <td><span class="font-monospace"><?= htmlspecialchars($egreso['cedula'] ?? 'N/A') ?></span></td>
                                <td>
                                    <?php $gen = $egreso['genero_egreso'] ?? $egreso['genero_est'] ?? '';
                                    if ($gen === 'V'): ?><span class="text-primary fw-bold"><i class="fas fa-male me-1"></i> Varón</span>
                                    <?php elseif ($gen === 'H'): ?><span class="text-danger fw-bold"><i class="fas fa-female me-1"></i> Hembra</span>
                                    <?php else: ?><span class="text-muted">N/A</span><?php endif; ?>
                                </td>
                                <td><span class="badge-sala"><?= $sala_nombre ?></span><?php if ($egreso['nombre_seccion']): ?><br><small class="info-egreso">Sec. <?= htmlspecialchars($egreso['nombre_seccion']) ?></small><?php endif; ?></td>
                                <td class="text-center"><span class="font-monospace small"><?= htmlspecialchars($egreso['periodo']) ?></span></td>
                                <td class="text-center"><?= date('d/m/Y', strtotime($egreso['fecha_egreso'])) ?></td>
                                <td><?= htmlspecialchars($egreso['motivo'] ?? 'No especificado') ?></td>
                                <td class="text-center"><span class="badge badge-estatus"><i class="fas fa-user-slash me-1"></i> Inactivo</span></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-revertir btn-sm" onclick="revertirEgreso(<?= $egreso['egreso_id'] ?>, '<?= addslashes($nombre_completo) ?>')"><i class="fas fa-undo me-1"></i> Revertir</button>
                                    <button type="button" class="btn btn-eliminar btn-sm" onclick="eliminarEgreso(<?= $egreso['egreso_id'] ?>, '<?= addslashes($nombre_completo) ?>')"><i class="fas fa-trash-alt me-1"></i> Eliminar</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php
            // Paginación
            if ($total_paginas > 1) {
                $query_params = $_GET;
                unset($query_params['pagina']);
                $base_url = '?';
                if (!empty($query_params)) $base_url .= http_build_query($query_params) . '&';
                ?>
                <div class="d-flex justify-content-center py-3 border-top">
                    <nav><ul class="pagination mb-0">
                        <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>"><a class="page-link" href="#" data-pagina="<?= $pagina - 1 ?>"><i class="fas fa-chevron-left"></i></a></li>
                        <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                            <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>"><a class="page-link" href="#" data-pagina="<?= $i ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>"><a class="page-link" href="#" data-pagina="<?= $pagina + 1 ?>"><i class="fas fa-chevron-right"></i></a></li>
                    </ul></nav>
                </div>
                <?php
            }
            $html_tabla = ob_get_clean();
        } else {
            $html_tabla = '
                <div class="estado-vacio"><i class="fas fa-inbox"></i><h5 class="fw-bold">No se encontraron egresos</h5>
                <p class="mb-0">'.(!empty($filtro_sala) || !empty($filtro_busqueda) || !empty($filtro_periodo) ? 'No hay egresos que coincidan con los filtros aplicados.' : 'Aún no se ha registrado ningún egreso.').'</p></div>';
        }
        
        $html_footer = '
            <div class="d-flex justify-content-between align-items-center py-3">
                <span class="text-muted small"><i class="fas fa-clock me-1"></i> Última actualización: '.date('d/m/Y H:i:s').'</span>
                <span class="text-muted small">Mostrando '.min($por_pagina, max(0, $total_egresos - $offset)).' de '.$total_egresos.' egresos</span>
            </div>
        ';
        
        responderJSON([
            'success' => true,
            'html_tabla' => $html_tabla,
            'html_footer' => $html_footer,
            'total' => $total_egresos,
            'pagina' => $pagina,
            'total_paginas' => $total_paginas
        ]);
    }
    
    // ===== REVERTIR EGRESO =====
    if ($action === 'revertir_egreso') {
        if (!isset($_POST['csrf_token']) || !verificarTokenCSRF($_POST['csrf_token'])) {
            responderJSON(['success' => false, 'error' => 'Token CSRF inválido'], 403);
        }
        $egreso_id = filter_var($_POST['egreso_id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$egreso_id) responderJSON(['success' => false, 'error' => 'ID inválido']);
        
        try {
            $conexion->begin_transaction();
            
            $stmt = $conexion->prepare("SELECT estudiante_id, sala, seccion_id, periodo FROM egresos WHERE id = ?");
            $stmt->bind_param("i", $egreso_id);
            $stmt->execute();
            $egreso = $stmt->get_result()->fetch_assoc();
            if (!$egreso) throw new Exception("Egreso no encontrado");
            $estudiante_id = $egreso['estudiante_id'];
            $stmt->close();
            
            $stmt_nom = $conexion->prepare("SELECT nombre, apellido FROM estudiantes WHERE id = ?");
            $stmt_nom->bind_param("i", $estudiante_id);
            $stmt_nom->execute();
            $est = $stmt_nom->get_result()->fetch_assoc();
            $stmt_nom->close();
            $nombre_estudiante = $est ? $est['nombre'] . ' ' . $est['apellido'] : 'ID: ' . $estudiante_id;
            
            $stmt_del = $conexion->prepare("DELETE FROM egresos WHERE id = ?");
            $stmt_del->bind_param("i", $egreso_id);
            $stmt_del->execute();
            $stmt_del->close();
            
            $stmt_up = $conexion->prepare("UPDATE estudiantes SET estatus = 'Activo' WHERE id = ?");
            $stmt_up->bind_param("i", $estudiante_id);
            $stmt_up->execute();
            $stmt_up->close();
            
            $usuario_id = $_SESSION['usuario_id'] ?? 0;
            if ($usuario_id > 0 && function_exists('registrarAuditoria')) {
                $detalles = "Egreso revertido. Estudiante: $nombre_estudiante (ID: $estudiante_id), Sala: {$egreso['sala']}, Sección: {$egreso['seccion_id']}, Período: {$egreso['periodo']}";
                registrarAuditoria($conexion, $usuario_id, 'REVERTIR_EGRESO', 'egresos', $egreso_id, $detalles);
            }
            
            $conexion->commit();
            responderJSON(['success' => true, 'mensaje' => 'Egreso revertido exitosamente.']);
        } catch (Exception $e) {
            $conexion->rollback();
            logError('Error revertir egreso', ['error' => $e->getMessage()]);
            responderJSON(['success' => false, 'error' => $e->getMessage()], 500);
        }
        exit;
    }
    
    // ===== ELIMINAR EGRESO =====
    if ($action === 'eliminar_egreso') {
        if (!isset($_POST['csrf_token']) || !verificarTokenCSRF($_POST['csrf_token'])) {
            responderJSON(['success' => false, 'error' => 'Token CSRF inválido'], 403);
        }
        $egreso_id = filter_var($_POST['egreso_id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$egreso_id) responderJSON(['success' => false, 'error' => 'ID inválido']);
        
        try {
            $conexion->begin_transaction();
            
            $stmt = $conexion->prepare("SELECT estudiante_id, sala, seccion_id, periodo FROM egresos WHERE id = ?");
            $stmt->bind_param("i", $egreso_id);
            $stmt->execute();
            $egreso = $stmt->get_result()->fetch_assoc();
            if (!$egreso) throw new Exception("Egreso no encontrado");
            $estudiante_id = $egreso['estudiante_id'];
            $stmt->close();
            
            $stmt_nom = $conexion->prepare("SELECT nombre, apellido FROM estudiantes WHERE id = ?");
            $stmt_nom->bind_param("i", $estudiante_id);
            $stmt_nom->execute();
            $est = $stmt_nom->get_result()->fetch_assoc();
            $stmt_nom->close();
            $nombre_estudiante = $est ? $est['nombre'] . ' ' . $est['apellido'] : 'ID: ' . $estudiante_id;
            
            $stmt_del_egreso = $conexion->prepare("DELETE FROM egresos WHERE id = ?");
            $stmt_del_egreso->bind_param("i", $egreso_id);
            $stmt_del_egreso->execute();
            $stmt_del_egreso->close();
            
            $stmt_del_est = $conexion->prepare("DELETE FROM estudiantes WHERE id = ?");
            $stmt_del_est->bind_param("i", $estudiante_id);
            $stmt_del_est->execute();
            $stmt_del_est->close();
            
            $usuario_id = $_SESSION['usuario_id'] ?? 0;
            if ($usuario_id > 0 && function_exists('registrarAuditoria')) {
                $detalles = "Egreso y estudiante eliminados permanentemente. Estudiante: $nombre_estudiante (ID: $estudiante_id), Sala: {$egreso['sala']}, Sección: {$egreso['seccion_id']}, Período: {$egreso['periodo']}";
                registrarAuditoria($conexion, $usuario_id, 'ELIMINAR_EGRESO', 'egresos', $egreso_id, $detalles);
            }
            
            $conexion->commit();
            responderJSON(['success' => true, 'mensaje' => 'Egreso y estudiante eliminados permanentemente.']);
        } catch (Exception $e) {
            $conexion->rollback();
            logError('Error eliminar egreso', ['error' => $e->getMessage()]);
            responderJSON(['success' => false, 'error' => $e->getMessage()], 500);
        }
        exit;
    }
    
    responderJSON(['error' => 'Acción no válida'], 400);
    exit;
}

// ============================================================================
// CARGAR VISTA PRINCIPAL (HTML)
// ============================================================================
include "../includes/header.php";

// Obtener salas disponibles para el filtro
$salas_disponibles = [];
$result_salas = $conexion->query("SELECT DISTINCT sala FROM egresos ORDER BY sala");
while ($row = $result_salas->fetch_assoc()) {
    $salas_disponibles[] = $row['sala'];
}

$nombres_salas = [
    'sala4' => 'Sala 4 Años', 'sala5' => 'Sala 5 Años',
    '1ro' => '1° Grado', '2do' => '2° Grado', '3ro' => '3° Grado',
    '4to' => '4° Grado', '5to' => '5° Grado', '6to' => '6° Grado'
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
        :root { --navy: #002d54; --primary-gradient: linear-gradient(135deg, #002d54 0%, #004a7c 100%); }
        body { background-color: #f5f7fa; font-family: 'Segoe UI', system-ui, sans-serif; }
        .page-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 12px;
            padding: 20px 28px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,45,84,0.2);
        }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 24px; }
        .card-header { background: var(--primary-gradient) !important; color: white; border-radius: 12px 12px 0 0 !important; padding: 14px 20px; font-weight: 600; }
        .table-egresos { font-size: 0.875rem; vertical-align: middle; }
        .table-egresos thead th { background-color: #f0f4f8; color: var(--navy); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; border-bottom: 2px solid #002d54; }
        .table-egresos tbody tr:hover { background-color: #e8f4f8; }
        .badge-estatus { font-size: 0.7rem; padding: 5px 10px; border-radius: 20px; font-weight: 500; background-color: #dc3545; color: white; }
        .badge-sala { background-color: #e9ecef; color: var(--navy); font-weight: 600; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem; }
        .btn-revertir { background: none; border: 1.5px solid #dc3545; color: #dc3545; border-radius: 8px; padding: 5px 14px; font-size: 0.78rem; font-weight: 500; transition: all 0.2s; margin-right: 4px; }
        .btn-revertir:hover { background: #dc3545; color: white; transform: translateY(-1px); box-shadow: 0 3px 10px rgba(220,53,69,0.25); }
        .btn-eliminar { background: none; border: 1.5px solid #dc3545; background-color: #dc3545; color: white; border-radius: 8px; padding: 5px 14px; font-size: 0.78rem; font-weight: 500; transition: all 0.2s; }
        .btn-eliminar:hover { background-color: #bb2d3b; border-color: #bb2d3b; color: white; transform: translateY(-1px); box-shadow: 0 3px 10px rgba(220,53,69,0.4); }
        .btn-limpiar-filtros { background: white; border: 1.5px solid #6c757d; color: #6c757d; font-weight: 500; border-radius: 8px; padding: 7px 20px; }
        .btn-limpiar-filtros:hover { background: #6c757d; color: white; }
        .pagination .page-link { border-radius: 8px; margin: 0 3px; color: var(--navy); font-weight: 500; cursor: pointer; }
        .pagination .page-item.active .page-link { background: var(--primary-gradient); border-color: transparent; }
        .estado-vacio { padding: 50px 20px; text-align: center; color: #6c757d; }
        .estado-vacio i { font-size: 3rem; color: #adb5bd; margin-bottom: 15px; }
        .info-egreso { font-size: 0.75rem; color: #6c757d; }
        .filtro-auto { font-size: 0.75rem; color: #6c757d; }
        @media (max-width: 768px) { .table-egresos { font-size: 0.7rem; } .btn-revertir, .btn-eliminar { font-size: 0.65rem; padding: 3px 8px; } }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-user-times me-2"></i> Historial de Egresos</h4>
            <small class="opacity-75"><i class="fas fa-database me-1"></i> Registro de estudiantes egresados</small>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="index.php" class="btn btn-light fw-bold"><i class="fas fa-arrow-left me-2"></i> Volver al Control de Asistencia</a>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="small fw-bold text-muted"><i class="fas fa-graduation-cap"></i> Sala / Grado</label>
                    <select name="sala" id="filtro-sala" class="form-select shadow-none" onchange="cargarSeccionesFiltro()">
                        <option value="">Todas las salas</option>
                        <?php foreach ($salas_disponibles as $sala_disp): ?>
                            <option value="<?= htmlspecialchars($sala_disp) ?>"><?= htmlspecialchars($nombres_salas[$sala_disp] ?? $sala_disp) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted"><i class="fas fa-layer-group"></i> Sección</label>
                    <select name="seccion" id="filtro-seccion" class="form-select shadow-none" disabled>
                        <option value="">Todas las secciones</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted"><i class="fas fa-calendar-alt"></i> Período</label>
                    <input type="month" name="periodo" id="filtro-periodo" class="form-control shadow-none">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted"><i class="fas fa-search"></i> Buscar estudiante</label>
                    <input type="text" name="busqueda" id="filtro-busqueda" class="form-control shadow-none" placeholder="Nombre, apellido o cédula...">
                </div>
                <div class="col-md-3 text-end d-flex gap-2 justify-content-end">
                    <button type="button" id="btn-limpiar-filtros" class="btn-limpiar-filtros">
                        <i class="fas fa-eraser me-2"></i> Limpiar filtros
                    </button>
                    <span class="filtro-auto align-self-center"><i class="fas fa-sync-alt me-1"></i> Filtro automático</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabla -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list-ul me-2"></i> Registro de Egresos <span class="badge bg-light text-dark ms-2" id="total-egresos">0</span></h6>
            <small class="opacity-75"><i class="fas fa-info-circle me-1"></i> Use "Revertir" para reactivar, "Eliminar" para borrado permanente</small>
        </div>
        <div class="card-body p-0" id="tabla-container">
            <!-- El contenido se carga dinámicamente con JavaScript -->
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
                <p class="text-muted mt-2">Cargando egresos...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modales -->
<div class="modal fade" id="modalRevertir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Confirmar Reversión</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-1">¿Está seguro de <strong>revertir</strong> el egreso de:</p>
                <p class="fs-5 fw-bold text-center text-navy mb-2" id="nombre-estudiante-revertir"></p>
                <p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i> Esta acción <strong>reactivará</strong> al estudiante.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning fw-bold" id="btn-confirmar-revertir"><i class="fas fa-undo me-2"></i> Sí, Revertir</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Confirmar Eliminación</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-1">¿Está seguro de <strong>eliminar permanentemente</strong> el egreso y al estudiante:</p>
                <p class="fs-5 fw-bold text-center text-danger mb-2" id="nombre-estudiante-eliminar"></p>
                <p class="small text-muted mb-0"><i class="fas fa-exclamation-circle me-1"></i> <strong>Esta acción no se puede deshacer.</strong></p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger fw-bold" id="btn-confirmar-eliminar"><i class="fas fa-trash-alt me-2"></i> Sí, Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ===== VARIABLES GLOBALES =====
let egresoIdARevertir = null, egresoIdAEliminar = null;
const modalRevertir = new bootstrap.Modal(document.getElementById('modalRevertir'));
const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminar'));

const filtroSala = document.getElementById('filtro-sala');
const filtroSeccion = document.getElementById('filtro-seccion');
const filtroPeriodo = document.getElementById('filtro-periodo');
const filtroBusqueda = document.getElementById('filtro-busqueda');
const tablaContainer = document.getElementById('tabla-container');
const totalEgresos = document.getElementById('total-egresos');
let timeoutId = null;
let paginaActual = 1;

// ===== CARGAR SECCIONES =====
function cargarSeccionesFiltro() {
    const sala = filtroSala.value;
    filtroSeccion.innerHTML = '<option value="">Cargando...</option>';
    filtroSeccion.disabled = true;
    if (!sala) {
        filtroSeccion.innerHTML = '<option value="">Todas las secciones</option>';
        filtroSeccion.disabled = true;
        return;
    }
    const formData = new FormData();
    formData.append('action', 'cargar_secciones');
    formData.append('sala', sala);
    fetch('egresados.php?ajax=1', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            filtroSeccion.innerHTML = '<option value="">Todas las secciones</option>';
            if (data.secciones?.length) {
                data.secciones.forEach(sec => {
                    filtroSeccion.add(new Option(sec.nombre, sec.id));
                });
                filtroSeccion.disabled = false;
            } else {
                filtroSeccion.innerHTML = '<option value="">Sin secciones</option>';
            }
            cargarEgresos();
        })
        .catch(() => {
            filtroSeccion.innerHTML = '<option value="">Error al cargar</option>';
            filtroSeccion.disabled = true;
        });
}

// ===== CARGAR EGRESOS FILTRADOS =====
function cargarEgresos(pagina = 1) {
    paginaActual = pagina;
    const sala = filtroSala.value;
    const seccion = filtroSeccion.value;
    const periodo = filtroPeriodo.value;
    const busqueda = filtroBusqueda.value.trim();

    tablaContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
            <p class="text-muted mt-2">Cargando egresos...</p>
        </div>
    `;

    const formData = new FormData();
    formData.append('action', 'cargar_egresos');
    formData.append('sala', sala);
    formData.append('seccion', seccion);
    formData.append('periodo', periodo);
    formData.append('busqueda', busqueda);
    formData.append('pagina', pagina);

    fetch('egresados.php?ajax=1', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                tablaContainer.innerHTML = data.html_tabla + data.html_footer;
                totalEgresos.textContent = data.total || 0;
                
                // Manejar clics en la paginación
                document.querySelectorAll('.page-link[data-pagina]').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const p = parseInt(this.dataset.pagina);
                        if (p && !isNaN(p)) cargarEgresos(p);
                    });
                });
            } else {
                tablaContainer.innerHTML = `<div class="text-center py-5 text-danger"><i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i> Error al cargar los datos.</div>`;
            }
        })
        .catch(() => {
            tablaContainer.innerHTML = `<div class="text-center py-5 text-danger"><i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i> Error de conexión.</div>`;
        });
}

// ===== FILTROS AUTOMÁTICOS =====
filtroSala.addEventListener('change', function() {
    cargarSeccionesFiltro(); // Esta función ya llama a cargarEgresos al final
});

filtroSeccion.addEventListener('change', function() {
    cargarEgresos(1);
});

filtroPeriodo.addEventListener('change', function() {
    cargarEgresos(1);
});

filtroBusqueda.addEventListener('input', function() {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => cargarEgresos(1), 400);
});

// ===== LIMPIAR FILTROS =====
document.getElementById('btn-limpiar-filtros').addEventListener('click', function() {
    filtroSala.value = '';
    filtroSeccion.innerHTML = '<option value="">Todas las secciones</option>';
    filtroSeccion.disabled = true;
    filtroPeriodo.value = '';
    filtroBusqueda.value = '';
    cargarEgresos(1);
});

// ===== FUNCIONES PARA REVERTIR / ELIMINAR =====
function revertirEgreso(id, nombre) {
    egresoIdARevertir = id;
    document.getElementById('nombre-estudiante-revertir').textContent = nombre;
    modalRevertir.show();
}

function eliminarEgreso(id, nombre) {
    egresoIdAEliminar = id;
    document.getElementById('nombre-estudiante-eliminar').textContent = nombre;
    modalEliminar.show();
}

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
                modalRevertir.hide();
                mostrarNotificacion('Egreso revertido exitosamente.', 'success');
                cargarEgresos(paginaActual);
            } else {
                alert('Error: ' + (data.error || 'No se pudo revertir.'));
            }
        })
        .catch(() => alert('Error de conexión.'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-undo me-2"></i> Sí, Revertir';
            egresoIdARevertir = null;
        });
});

document.getElementById('btn-confirmar-eliminar').addEventListener('click', function() {
    if (!egresoIdAEliminar) return;
    if (!confirm('¿Está ABSOLUTAMENTE SEGURO? Esta acción es irreversible.')) return;
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
                modalEliminar.hide();
                mostrarNotificacion('Egreso y estudiante eliminados permanentemente.', 'danger');
                cargarEgresos(paginaActual);
            } else {
                alert('Error: ' + (data.error || 'No se pudo eliminar.'));
            }
        })
        .catch(() => alert('Error de conexión.'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash-alt me-2"></i> Sí, Eliminar';
            egresoIdAEliminar = null;
        });
});

document.getElementById('modalRevertir').addEventListener('hidden.bs.modal', () => egresoIdARevertir = null);
document.getElementById('modalEliminar').addEventListener('hidden.bs.modal', () => egresoIdAEliminar = null);

// ===== NOTIFICACIONES =====
function mostrarNotificacion(mensaje, tipo = 'success') {
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alerta.style.zIndex = '9999';
    alerta.style.maxWidth = '400px';
    alerta.innerHTML = `${mensaje} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(alerta);
    setTimeout(() => alerta.remove(), 4000);
}

// ===== CARGA INICIAL =====
document.addEventListener('DOMContentLoaded', function() {
    cargarEgresos(1);
});
</script>

<?php include "../includes/footer.php"; ?>