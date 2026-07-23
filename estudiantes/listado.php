<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'admin', 'super_admin', 'directiva'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once '../config/conexion.php';
require_once '../config/configuracion.php';

$periodo_escolar_actual = obtenerPeriodoEscolar();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ========== MANEJAR PETICIONES AJAX ==========
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $sala = isset($_GET['sala']) ? trim($_GET['sala']) : '';
    $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
    $filtro_anio = isset($_GET['anio']) ? trim($_GET['anio']) : '';

    // ===== CONSULTA PARA CONTAR (INDEPENDIENTE) =====
    $sql_count = "
        SELECT COUNT(DISTINCT e.id) AS total,
               SUM(CASE WHEN e.genero = 'V' THEN 1 ELSE 0 END) AS varones,
               SUM(CASE WHEN e.genero = 'H' THEN 1 ELSE 0 END) AS hembras
        FROM estudiantes e
        WHERE e.estatus = 'Activo'
          AND EXISTS (SELECT 1 FROM inscripciones i WHERE i.estudiante_id = e.id)
    ";
    $params_count = [];
    $types_count = "";

    if ($sala) {
        $sql_count .= " AND e.sala = ?";
        $params_count[] = $sala;
        $types_count .= "s";
    }
    if ($busqueda) {
        $sql_count .= " AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.cedula_escolar LIKE ?)";
        $like = '%' . $busqueda . '%';
        $params_count[] = $like;
        $params_count[] = $like;
        $params_count[] = $like;
        $types_count .= "sss";
    }
    if ($filtro_anio) {
        $sql_count .= " AND EXISTS (SELECT 1 FROM inscripciones i2 WHERE i2.estudiante_id = e.id AND i2.ano_escolar = ?)";
        $params_count[] = $filtro_anio;
        $types_count .= "s";
    }

    $stmt_count = $conexion->prepare($sql_count);
    if ($params_count) {
        $stmt_count->bind_param($types_count, ...$params_count);
    }
    $stmt_count->execute();
    $counts = $stmt_count->get_result()->fetch_assoc();
    $stmt_count->close();

    // ===== CONSULTA PARA LA TABLA =====
    $sql = "
        SELECT DISTINCT e.id, e.nombre, e.apellido, e.cedula_escolar, e.sala, e.genero,
               r.nombre_completo AS rep_nombre,
               r.cedula AS rep_cedula,
               r.telefono AS rep_telefono,
               s.nombre AS seccion_nombre,
               p.nombre AS profesor_nombre,
               (SELECT ano_escolar FROM inscripciones WHERE estudiante_id = e.id ORDER BY fecha_inscripcion DESC LIMIT 1) AS ano_escolar_actual
        FROM estudiantes e
        LEFT JOIN representantes r ON e.representante_id = r.id
        LEFT JOIN secciones s ON e.seccion_id = s.id
        LEFT JOIN profesores p ON p.seccion = s.id AND p.estatus = 'Activo'
        WHERE e.estatus = 'Activo'
          AND EXISTS (SELECT 1 FROM inscripciones i WHERE i.estudiante_id = e.id)
    ";

    $params = [];
    $types = "";

    if ($sala) {
        $sql .= " AND e.sala = ?";
        $params[] = $sala;
        $types .= "s";
    }
    if ($busqueda) {
        $sql .= " AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.cedula_escolar LIKE ?)";
        $like = '%' . $busqueda . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= "sss";
    }
    if ($filtro_anio) {
        $sql .= " AND EXISTS (SELECT 1 FROM inscripciones i2 WHERE i2.estudiante_id = e.id AND i2.ano_escolar = ?)";
        $params[] = $filtro_anio;
        $types .= "s";
    }
    
    $sql .= " ORDER BY ano_escolar_actual DESC, e.sala, e.nombre, e.apellido";

    $stmt = $conexion->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-hover table-listado mb-0">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:15%">Nombre Completo</th>
                    <th style="width:8%">Estado</th>
                    <th style="width:10%">Cédula Escolar</th>
                    <th style="width:10%">Sala</th>
                    <th style="width:8%">Sección</th>
                    <th style="width:14%">Profesor</th>
                    <th style="width:12%">Representante</th>
                    <th style="width:10%">Año Escolar</th>
                    <th style="width:10%">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): 
                    $contador = 1;
                    while($e = $result->fetch_assoc()): 
                        $estado = obtenerEstadoCompleto($e['id'], $conexion);
                        $ficha_ok = $estado['ficha']['completa'];
                        $boletin_ok = $estado['boletin']['completo'];
                        $faltantes_ficha = $estado['ficha']['faltantes'];
                        $faltantes_bol = $estado['boletin']['faltantes'];
                        
                        $tooltip = '';
                        $clase_badge = '';
                        $icono = '';
                        $texto_estado = '';
                        
                        if (!$ficha_ok) {
                            $clase_badge = 'bg-danger';
                            $icono = 'fa-exclamation-triangle';
                            $tooltip = '⚠️ Ficha incompleta. Faltan: ' . implode(', ', $faltantes_ficha);
                            $texto_estado = 'Ficha incompleta';
                        } elseif (!$boletin_ok) {
                            $clase_badge = 'bg-warning text-dark';
                            $icono = 'fa-clock';
                            $tooltip = '✅ Ficha completa, pero faltan datos para boletín: ' . implode(', ', $faltantes_bol);
                            $texto_estado = 'Faltan datos boletín';
                        } else {
                            $clase_badge = 'bg-success';
                            $icono = 'fa-check-circle';
                            $tooltip = '✅ Completamente al día.';
                            $texto_estado = 'Completa';
                        }
                        
                        $mapa_salas = [
                            'sala4' => 'Sala 4 Años',
                            'sala5' => 'Sala 5 Años',
                            '1ro' => '1er Grado',
                            '2do' => '2do Grado',
                            '3ro' => '3er Grado',
                            '4to' => '4to Grado',
                            '5to' => '5to Grado',
                            '6to' => '6to Grado'
                        ];
                        $sala_nombre = $mapa_salas[$e['sala']] ?? $e['sala'];
                ?>
                    <tr>
                        <td class="text-center fw-bold text-muted"><?= $contador++ ?></td>
                        <td><strong><?= htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) ?></strong></td>
                        <td>
                            <span class="badge <?= $clase_badge ?> estado-icono" 
                                  style="cursor:help; font-size:0.8rem; padding:5px 10px;"
                                  data-bs-toggle="tooltip"
                                  data-bs-placement="left"
                                  data-bs-html="true"
                                  title="<?= htmlspecialchars($tooltip) ?>">
                                <i class="fas <?= $icono ?> me-1"></i> <?= $texto_estado ?>
                            </span>
                            <?php if (!$ficha_ok): ?>
                                <span class="badge bg-danger rounded-pill ms-1" style="font-size:0.6rem;">
                                    <?= count($faltantes_ficha) ?>
                                </span>
                            <?php elseif (!$boletin_ok): ?>
                                <span class="badge bg-warning text-dark rounded-pill ms-1" style="font-size:0.6rem;">
                                    <?= count($faltantes_bol) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><span class="font-monospace"><?= htmlspecialchars($e['cedula_escolar']) ?></span></td>
                        <td><span class="badge-sala"><?= htmlspecialchars($sala_nombre) ?></span></td>
                        <td><?= htmlspecialchars($e['seccion_nombre'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($e['profesor_nombre'] ?? 'Sin asignar') ?></td>
                        <td><?= htmlspecialchars($e['rep_nombre'] ?? 'No asignado') ?></td>
                        <td class="text-center"><?= htmlspecialchars($e['ano_escolar_actual'] ?? 'No registrado') ?></td>
                        <td class="text-nowrap">
                            <a href="ver_ficha.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-info" target="_blank" title="Ver ficha completa"><i class="fas fa-eye"></i></a>
                            <a href="editar_estudiantes.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-primary" title="Editar datos"><i class="fas fa-edit"></i></a>
                            <?php if ($_SESSION['rol'] === 'super_admin' || $_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'directiva'): ?>
                                <a href="eliminar_estudiante_completo.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-danger" title="Eliminar estudiante" onclick="return confirm('¿Está seguro de eliminar este estudiante? Se eliminarán TODOS sus registros asociados.')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center py-4"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>No se encontraron estudiantes inscritos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $html = ob_get_clean();

    // Devolver JSON con HTML y estadísticas
    header('Content-Type: application/json');
    echo json_encode([
        'html' => $html,
        'total' => (int)($counts['total'] ?? 0),
        'varones' => (int)($counts['varones'] ?? 0),
        'hembras' => (int)($counts['hembras'] ?? 0)
    ]);
    exit;
}

// ========== MANEJAR ELIMINACIÓN ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error CSRF");
    }
    $id = intval($_POST['id']);
    header("Location: eliminar_estudiante_completo.php?id=$id");
    exit();
}

include '../includes/header.php';

$sala_filtro = isset($_GET['sala']) ? trim($_GET['sala']) : '';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_anio = isset($_GET['anio']) ? trim($_GET['anio']) : '';

// ===== OBTENER AÑOS ESCOLARES DISPONIBLES =====
$anios_disponibles = [];
$res_anios = $conexion->query("SELECT DISTINCT ano_escolar FROM inscripciones ORDER BY ano_escolar DESC");
while ($row = $res_anios->fetch_assoc()) {
    $anios_disponibles[] = $row['ano_escolar'];
}
if (empty($anios_disponibles)) {
    $anios_disponibles[] = $periodo_escolar_actual;
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
    .table-listado {
        font-size: 0.875rem;
        vertical-align: middle;
    }
    .table-listado thead th {
        background-color: #f0f4f8;
        color: #002d54;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #002d54;
    }
    .table-listado tbody tr:hover {
        background-color: #e8f4f8;
    }
    .badge-sala {
        background-color: #e9ecef;
        color: #002d54;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
    }
    #busquedaInput {
        transition: all 0.3s ease;
    }
    #busquedaInput:focus {
        border-color: #002d54;
        box-shadow: 0 0 0 3px rgba(0,45,84,0.15);
    }
    .tooltip-inner {
        max-width: 350px;
        text-align: left;
        font-size: 0.85rem;
    }
    .estado-icono {
        transition: transform 0.2s;
    }
    .estado-icono:hover {
        transform: scale(1.05);
    }
    .estadisticas-sutiles {
        font-size: 0.85rem;
        color: rgba(255,255,255,0.8);
        display: flex;
        gap: 18px;
        align-items: center;
    }
    .estadisticas-sutiles span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .estadisticas-sutiles .badge {
        font-size: 0.8rem;
        padding: 2px 8px;
        border-radius: 20px;
    }
</style>

<div class="container-fluid px-4">
    
    <!-- Cabecera -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-user-graduate me-2"></i> Listado de Estudiantes</h4>
            <small class="opacity-75"><i class="fas fa-check-circle me-1"></i> Solo estudiantes con inscripción completada</small>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="inscripcion.php" class="btn btn-light fw-bold me-2">
                <i class="fas fa-user-plus me-2"></i> Nueva Inscripción
            </a>
            <a href="index.php" class="btn btn-light fw-bold">
                <i class="fas fa-arrow-left me-2"></i> Volver
            </a>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Estudiante eliminado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'eliminado'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> Estudiante eliminado permanentemente con todos sus registros asociados.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card card-filtros">
        <div class="card-body p-4">
            <form method="GET" action="listado.php" id="filtroForm" autocomplete="off">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted"><i class="fas fa-graduation-cap me-1"></i> Sala / Grado</label>
                        <select name="sala" id="salaSelect" class="form-select shadow-none">
                            <option value="">Todas</option>
                            <?php
                            $orden_salas = ['sala4', 'sala5', '1ro', '2do', '3ro', '4to', '5to', '6to'];
                            $mapa_salas = [
                                'sala4' => 'Sala 4 años',
                                'sala5' => 'Sala 5 años',
                                '1ro' => '1er Grado',
                                '2do' => '2do Grado',
                                '3ro' => '3er Grado',
                                '4to' => '4to Grado',
                                '5to' => '5to Grado',
                                '6to' => '6to Grado'
                            ];
                            foreach ($orden_salas as $sala) {
                                $selected = ($sala_filtro == $sala) ? 'selected' : '';
                                $nombre_mostrar = $mapa_salas[$sala] ?? $sala;
                                echo '<option value="' . htmlspecialchars($sala) . '" ' . $selected . '>' . htmlspecialchars($nombre_mostrar) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted"><i class="fas fa-calendar-alt me-1"></i> Año Escolar</label>
                        <select name="anio" id="anioSelect" class="form-select shadow-none">
                            <option value="">Todos</option>
                            <?php foreach ($anios_disponibles as $anio): ?>
                                <option value="<?= htmlspecialchars($anio) ?>" <?= ($filtro_anio == $anio) ? 'selected' : '' ?>><?= htmlspecialchars($anio) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted"><i class="fas fa-search me-1"></i> Buscar estudiante</label>
                        <input type="text" name="busqueda" id="busquedaInput" class="form-control shadow-none" placeholder="Nombre, apellido o cédula..." value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                    <div class="col-md-2 text-end">
                        <span class="text-muted small"><i class="fas fa-info-circle me-1"></i> Filtro automático</span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card card-tabla">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fas fa-list-ul me-2"></i> Registro de Estudiantes
                <span class="badge bg-light text-dark ms-2" id="contador-total">0</span>
            </h6>
            <!-- Estadísticas sutiles -->
            <div class="estadisticas-sutiles">
                <span><i class="fas fa-male text-info"></i> Varones: <span id="estadistica-varones" class="badge bg-info text-dark">0</span></span>
                <span><i class="fas fa-female text-danger"></i> Hembras: <span id="estadistica-hembras" class="badge bg-danger text-white">0</span></span>
                <span><i class="fas fa-users text-warning"></i> Total: <span id="estadistica-total" class="badge bg-light text-dark">0</span></span>
            </div>
            <small class="opacity-75"><i class="fas fa-clock me-1"></i> Filtro automático</small>
        </div>
        <div class="card-body p-0" id="tabla-container">
            <!-- El contenido se carga dinámicamente con JavaScript -->
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small"><i class="fas fa-database me-1"></i> <span id="contador-footer">0</span> estudiante(s) encontrados</span>
            <span class="text-muted small"><i class="fas fa-sync-alt me-1"></i> Filtro automático</span>
        </div>
    </div>
</div>

<script>
const busquedaInput = document.getElementById('busquedaInput');
const salaSelect = document.getElementById('salaSelect');
const anioSelect = document.getElementById('anioSelect');
const tablaContainer = document.getElementById('tabla-container');
const contadorTotal = document.getElementById('contador-total');
const contadorFooter = document.getElementById('contador-footer');
const estadisticaTotal = document.getElementById('estadistica-total');
const estadisticaVarones = document.getElementById('estadistica-varones');
const estadisticaHembras = document.getElementById('estadistica-hembras');
let timeoutId = null;

function cargarTabla() {
    const termino = busquedaInput.value.trim();
    const sala = salaSelect.value;
    const anio = anioSelect.value;

    tablaContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-2">Cargando estudiantes...</p>
        </div>
    `;

    const params = new URLSearchParams();
    if (termino) params.append('busqueda', termino);
    if (sala) params.append('sala', sala);
    if (anio) params.append('anio', anio);
    params.append('ajax', '1');

    fetch('listado.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            tablaContainer.innerHTML = data.html;
            // Actualizar contadores
            const total = data.total || 0;
            const varones = data.varones || 0;
            const hembras = data.hembras || 0;
            
            contadorTotal.textContent = total;
            contadorFooter.textContent = total;
            estadisticaTotal.textContent = total;
            estadisticaVarones.textContent = varones;
            estadisticaHembras.textContent = hembras;
            
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (el) {
                    return new bootstrap.Tooltip(el, {
                        html: true,
                        placement: 'left'
                    });
                });
            }
        })
        .catch(() => {
            tablaContainer.innerHTML = `
                <div class="text-center py-5 text-danger">
                    <i class="fas fa-exclamation-circle fa-2x mb-2 d-block"></i>
                    Error al cargar los datos. Intente nuevamente.
                </div>
            `;
        });
}

busquedaInput.addEventListener('input', function() {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(cargarTabla, 400);
});

salaSelect.addEventListener('change', cargarTabla);
anioSelect.addEventListener('change', cargarTabla);

document.addEventListener('DOMContentLoaded', function() {
    cargarTabla();
});
</script>

<?php include '../includes/footer.php'; ?>