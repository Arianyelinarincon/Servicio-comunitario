<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /servicio-comunitario/index.php");
    exit();
}
include '../includes/header.php';
require_once '../config/conexion.php';

// Paginación
$registros_por_pagina = 20;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Filtros
$filtro_accion = isset($_GET['accion']) ? trim($_GET['accion']) : '';
$filtro_usuario = isset($_GET['usuario']) ? intval($_GET['usuario']) : 0;
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';

// ========== CONSULTA PRINCIPAL ==========
$sql = "SELECT a.*, 
               COALESCE(s.nombre, p.nombre, 'Sistema') AS usuario_nombre,
               CASE 
                   WHEN s.id IS NOT NULL THEN 'secretaria'
                   WHEN p.id IS NOT NULL THEN 'profesor'
                   ELSE 'sistema'
               END AS usuario_tipo_detectado
        FROM auditoria a
        LEFT JOIN secretaria s ON a.usuario_id = s.id
        LEFT JOIN profesores p ON a.usuario_id = p.id
        WHERE 1=1";
$params = [];
$types = "";

if ($filtro_accion) {
    $sql .= " AND a.accion LIKE ?";
    $params[] = "%$filtro_accion%";
    $types .= "s";
}
if ($filtro_usuario > 0) {
    $sql .= " AND a.usuario_id = ?";
    $params[] = $filtro_usuario;
    $types .= "i";
}
if ($filtro_fecha_desde) {
    $sql .= " AND DATE(a.fecha) >= ?";
    $params[] = $filtro_fecha_desde;
    $types .= "s";
}
if ($filtro_fecha_hasta) {
    $sql .= " AND DATE(a.fecha) <= ?";
    $params[] = $filtro_fecha_hasta;
    $types .= "s";
}

// ========== CONTAR TOTAL ==========
$sql_count = "SELECT COUNT(*) as total FROM auditoria a WHERE 1=1";
if ($filtro_accion) {
    $sql_count .= " AND a.accion LIKE ?";
}
if ($filtro_usuario > 0) {
    $sql_count .= " AND a.usuario_id = ?";
}
if ($filtro_fecha_desde) {
    $sql_count .= " AND DATE(a.fecha) >= ?";
}
if ($filtro_fecha_hasta) {
    $sql_count .= " AND DATE(a.fecha) <= ?";
}

$stmt_count = $conexion->prepare($sql_count);
if (!$stmt_count) {
    die("Error en prepare (count): " . $conexion->error);
}
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// ========== EJECUTAR CONSULTA PRINCIPAL ==========
$sql .= " ORDER BY a.fecha DESC LIMIT ? OFFSET ?";
$params[] = $registros_por_pagina;
$params[] = $offset;
$types .= "ii";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    die("Error en prepare (main): " . $conexion->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// ========== OBTENER USUARIOS PARA FILTRO ==========
$usuarios = [];
$query_usuarios = "SELECT id, nombre FROM secretaria UNION SELECT id, nombre FROM profesores ORDER BY nombre";
$result_usuarios = $conexion->query($query_usuarios);
if ($result_usuarios) {
    while ($row = $result_usuarios->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

// ========== MAPEO DE COLORES E ICONOS ==========
$colores_acciones = [
    'INSCRIBIR_ESTUDIANTE' => 'success',
    'ACTUALIZAR_INSCRIPCION' => 'info',
    'ELIMINAR_ESTUDIANTE' => 'danger',
    'REVERTIR_EGRESO' => 'warning',
    'ELIMINAR_EGRESO' => 'danger',
    'AGREGAR_PROFESOR' => 'success',
    'EDITAR_PROFESOR' => 'info',
    'ELIMINAR_PROFESOR' => 'danger',
    'CREAR_SECRETARIA' => 'success',
    'EDITAR_SECRETARIA' => 'info',
    'ELIMINAR_SECRETARIA' => 'danger',
    'AGREGAR_SECRETARIA' => 'success',
    'EDITAR_ESTUDIANTE' => 'info',
    'PASE_DE_AÑO' => 'success',
    'ELIMINAR_BOLETIN' => 'danger',
    'EDITAR_BOLETIN' => 'info',
    'GUARDAR_BOLETIN' => 'info',
];

$iconos_acciones = [
    'INSCRIBIR_ESTUDIANTE' => 'fa-user-plus',
    'ACTUALIZAR_INSCRIPCION' => 'fa-sync-alt',
    'ELIMINAR_ESTUDIANTE' => 'fa-user-times',
    'REVERTIR_EGRESO' => 'fa-undo',
    'ELIMINAR_EGRESO' => 'fa-trash-alt',
    'AGREGAR_PROFESOR' => 'fa-user-plus',
    'EDITAR_PROFESOR' => 'fa-edit',
    'ELIMINAR_PROFESOR' => 'fa-user-slash',
    'CREAR_SECRETARIA' => 'fa-user-plus',
    'EDITAR_SECRETARIA' => 'fa-edit',
    'ELIMINAR_SECRETARIA' => 'fa-user-slash',
    'AGREGAR_SECRETARIA' => 'fa-user-plus',
    'EDITAR_ESTUDIANTE' => 'fa-edit',
    'PASE_DE_AÑO' => 'fa-arrow-up',
    'ELIMINAR_BOLETIN' => 'fa-file-times',
    'EDITAR_BOLETIN' => 'fa-edit',
    'GUARDAR_BOLETIN' => 'fa-save',
];

function getColorAccion($accion) {
    global $colores_acciones;
    foreach ($colores_acciones as $key => $color) {
        if (strpos($accion, $key) !== false) {
            return $color;
        }
    }
    return 'secondary';
}

function getIconoAccion($accion) {
    global $iconos_acciones;
    foreach ($iconos_acciones as $key => $icono) {
        if (strpos($accion, $key) !== false) {
            return $icono;
        }
    }
    return 'fa-clock';
}

function getBadgeUsuario($tipo) {
    switch ($tipo) {
        case 'secretaria':
            return '<span class="badge-usuario badge-secretaria"><i class="fas fa-user-tie"></i> Secretaria</span>';
        case 'profesor':
            return '<span class="badge-usuario badge-profesor"><i class="fas fa-chalkboard-teacher"></i> Profesor</span>';
        default:
            return '<span class="badge-usuario badge-sistema"><i class="fas fa-desktop"></i> Sistema</span>';
    }
}
?>

<style>
    :root { 
        --primary-gradient: linear-gradient(135deg, #002d54 0%, #004a7c 100%);
        --navy: #002d54;
    }
    .page-header {
        background: var(--primary-gradient);
        color: white;
        border-radius: 12px;
        padding: 20px 28px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(0,45,84,0.2);
    }
    .card-filtros {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        margin-bottom: 24px;
    }
    .card-tabla {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .card-tabla .card-header {
        background: var(--primary-gradient) !important;
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 14px 20px;
        font-weight: 600;
    }
    .table-auditoria {
        font-size: 0.875rem;
        vertical-align: middle;
    }
    .table-auditoria thead th {
        background-color: #f0f4f8;
        color: #002d54;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #002d54;
        text-align: center;
        white-space: nowrap;
    }
    .table-auditoria tbody td {
        text-align: center;
        vertical-align: middle;
        padding: 10px 8px;
    }
    .table-auditoria tbody tr:hover {
        background-color: #e8f4f8;
    }
    .table-auditoria tbody td:first-child {
        text-align: left;
        padding-left: 15px;
    }
    .badge-accion {
        font-size: 0.7rem;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 500;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .badge-accion i {
        font-size: 0.75rem;
    }
    .badge-tabla {
        background-color: #e9ecef;
        color: #002d54;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
        white-space: nowrap;
    }
    .badge-usuario {
        font-size: 0.6rem;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: 500;
        margin-left: 4px;
    }
    .badge-secretaria {
        background-color: #cce5ff;
        color: #004085;
    }
    .badge-profesor {
        background-color: #d4edda;
        color: #155724;
    }
    .badge-sistema {
        background-color: #e2e3e5;
        color: #383d41;
    }
    .detalle-texto {
        font-size: 0.82rem;
        color: #495057;
        max-width: 250px;
        display: block;
        text-align: left;
        margin: 0 auto;
        word-break: break-word;
    }
    .detalle-texto .highlight {
        color: #002d54;
        font-weight: 500;
    }
    .btn-filtro {
        background: var(--primary-gradient);
        color: white;
        border: none;
        font-weight: 500;
        padding: 8px 24px;
        border-radius: 8px;
        transition: all 0.3s;
    }
    .btn-filtro:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,45,84,0.3);
        color: white;
    }
    .btn-limpiar {
        background: #6c757d;
        color: white;
        border: none;
        font-weight: 500;
        padding: 8px 24px;
        border-radius: 8px;
        transition: all 0.3s;
    }
    .btn-limpiar:hover {
        background: #5a6268;
        color: white;
    }
    .pagination .page-link {
        border-radius: 8px;
        margin: 0 3px;
        color: #002d54;
        font-weight: 500;
    }
    .pagination .page-item.active .page-link {
        background: var(--primary-gradient);
        border-color: transparent;
    }
    .empty-state {
        padding: 50px 20px;
        text-align: center;
        color: #6c757d;
    }
    .empty-state i {
        font-size: 3rem;
        color: #adb5bd;
        margin-bottom: 15px;
        display: block;
    }
    .usuario-nombre {
        font-weight: 600;
        color: #002d54;
    }
    .fecha-registro {
        font-size: 0.78rem;
        color: #6c757d;
        white-space: nowrap;
    }
    .boton-accion-eliminar {
        background: #dc3545;
        color: white;
        border: none;
        padding: 2px 10px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
    }
    .boton-accion-eliminar:hover {
        background: #bd2130;
        color: white;
    }
    @media (max-width: 768px) {
        .table-auditoria {
            font-size: 0.75rem;
        }
        .detalle-texto {
            max-width: 120px;
        }
        .badge-accion {
            font-size: 0.6rem;
            padding: 3px 8px;
        }
    }
</style>

<div class="container-fluid px-4">
    
    <!-- Cabecera -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-history me-2"></i> Historial de Cambios</h4>
            <small class="opacity-75"><i class="fas fa-clipboard-list me-1"></i> Registro completo de todas las acciones del sistema</small>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="gestionar_permisos.php" class="btn btn-light fw-bold">
                <i class="fas fa-arrow-left me-2"></i> Volver a Seguridad
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card card-filtros">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end" id="filtroForm">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted"><i class="fas fa-tag me-1"></i> Acción</label>
                    <input type="text" name="accion" class="form-control shadow-none" placeholder="Buscar acción..." value="<?= htmlspecialchars($filtro_accion) ?>">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted"><i class="fas fa-user me-1"></i> Usuario</label>
                    <select name="usuario" class="form-select shadow-none">
                        <option value="0">Todos</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($filtro_usuario == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted"><i class="fas fa-calendar-alt me-1"></i> Desde</label>
                    <input type="date" name="fecha_desde" class="form-control shadow-none" value="<?= htmlspecialchars($filtro_fecha_desde) ?>">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted"><i class="fas fa-calendar-alt me-1"></i> Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control shadow-none" value="<?= htmlspecialchars($filtro_fecha_hasta) ?>">
                </div>
                <div class="col-md-3 text-end d-flex gap-2 justify-content-end align-self-end">
                    <button type="submit" class="btn btn-filtro px-4">
                        <i class="fas fa-filter me-2"></i> Filtrar
                    </button>
                    <a href="historial_auditoria.php" class="btn btn-limpiar px-3">
                        <i class="fas fa-eraser me-2"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card card-tabla">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list-ul me-2"></i> Registro de Cambios 
                <span class="badge bg-light text-dark ms-2"><?= $total_registros ?> registro(s)</span>
            </h6>
            <small class="opacity-75"><i class="fas fa-clock me-1"></i> Últimos cambios</small>
        </div>
        <div class="card-body p-0">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-auditoria mb-0">
                        <thead>
                            <tr>
                                <th style="width:15%">Usuario</th>
                                <th style="width:20%">Acción</th>
                                <th style="width:10%">Tabla</th>
                                <th style="width:8%">Registro</th>
                                <th style="width:27%">Detalles</th>
                                <th style="width:12%">Fecha</th>
                                <th style="width:8%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $contador = $offset + 1;
                            while($row = $result->fetch_assoc()): 
                                $color = getColorAccion($row['accion']);
                                $icono = getIconoAccion($row['accion']);
                                $fecha = date('d/m/Y H:i:s', strtotime($row['fecha']));
                                $usuario = $row['usuario_nombre'] ?? 'Sistema';
                                $usuario_tipo = $row['usuario_tipo_detectado'] ?? 'sistema';
                                $badge_usuario = getBadgeUsuario($usuario_tipo);
                                
                                // Determinar si es una eliminación
                                $es_eliminacion = strpos($row['accion'], 'ELIMINAR') !== false;
                            ?>
                            <tr>
                                <td>
                                    <span class="usuario-nombre">
                                        <i class="fas fa-user-circle me-1"></i>
                                        <?= htmlspecialchars($usuario) ?>
                                    </span>
                                    <?= $badge_usuario ?>
                                </td>
                                <td>
                                    <span class="badge-accion bg-<?= $color ?> text-white">
                                        <i class="fas <?= $icono ?>"></i>
                                        <?= htmlspecialchars($row['accion']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-tabla">
                                        <?= htmlspecialchars($row['tabla_afectada'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-tabla">
                                        #<?= $row['registro_id'] ?? '-' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="detalle-texto">
                                        <?php 
                                        $detalle = $row['detalles'] ?? 'Sin detalles';
                                        $detalle = htmlspecialchars($detalle);
                                        if (strlen($detalle) > 100) {
                                            $detalle = substr($detalle, 0, 100) . '...';
                                        }
                                        echo $detalle;
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fecha-registro">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= $fecha ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($es_eliminacion && $row['tabla_afectada'] === 'estudiantes'): ?>
                                        <button class="boton-accion-eliminar" onclick="alert('Para revertir una eliminación, debe restaurar desde una copia de seguridad.')">
                                            <i class="fas fa-undo"></i> Revertir
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="d-flex justify-content-center py-3 border-top">
                        <nav>
                            <ul class="pagination mb-0">
                                <?php 
                                $query_params = $_GET;
                                unset($query_params['pagina']);
                                $base_url = 'historial_auditoria.php?' . http_build_query($query_params);
                                if (!empty($query_params)) $base_url .= '&';
                                ?>
                                <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $base_url ?>pagina=<?= $pagina_actual - 1 ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                                    <li class="page-item <?= ($i == $pagina_actual) ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= $base_url ?>pagina=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $base_url ?>pagina=<?= $pagina_actual + 1 ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h5 class="fw-bold">No hay registros de auditoría</h5>
                    <p class="text-muted">No se encontraron cambios con los filtros seleccionados.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small"><i class="fas fa-database me-1"></i> Total: <?= $total_registros ?> registro(s)</span>
            <span class="text-muted small"><i class="fas fa-sync-alt me-1"></i> Actualización en tiempo real</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit al cambiar select de usuario
    document.querySelector('select[name="usuario"]')?.addEventListener('change', function() {
        this.form.submit();
    });
});
</script>

<?php include '../includes/footer.php'; ?>