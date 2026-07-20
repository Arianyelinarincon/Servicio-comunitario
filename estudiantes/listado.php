<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'admin', 'super_admin', 'directiva'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once '../config/conexion.php';
require_once '../config/configuracion.php';

$periodo_escolar_actual = obtenerPeriodoEscolar();

// ========== FUNCIÓN PARA VERIFICAR CAMPOS COMPLETOS (MEJORADA) ==========
function verificarCamposCompletos($datos) {
    $faltantes = [];
    
    $campos_estudiante = [
        'nombre' => 'Nombre',
        'apellido' => 'Apellido',
        'fecha_nacimiento' => 'Fecha Nac.',
        'genero' => 'Sexo',
        'cedula_escolar' => 'Cédula Escolar',
    ];
    
    $campos_representante = [
        'rep_nombre' => 'Nombre',
        'rep_cedula' => 'Cédula',
        'rep_telefono' => 'Teléfono',
    ];
    
    $campos_sala = [
        'sala' => 'Sala/Grado',
        'seccion_id' => 'Sección',
    ];
    
    $campos_padres = [
        'madre_nombre' => 'Madre (nombre)',
        'padre_nombre' => 'Padre (nombre)',
    ];
    
    $campos_condicionales = [];
    if (!empty($datos['enfermedad']) && $datos['enfermedad'] === 'Si') {
        $campos_condicionales['enfermedad_cual'] = 'Enfermedad (cuál)';
    }
    if (!empty($datos['educacion_fisica']) && $datos['educacion_fisica'] === 'No') {
        $campos_condicionales['educacion_fisica_porque'] = 'Ed. Física (por qué no)';
    }
    if (!empty($datos['alergia']) && $datos['alergia'] === 'Si') {
        $campos_condicionales['alergia_cual'] = 'Alergia (cuál)';
    }
    
    $faltantes_por_grupo = [];
    
    foreach ($campos_estudiante as $campo => $label) {
        if (empty($datos[$campo]) && $datos[$campo] !== '0') {
            $faltantes_por_grupo['Estudiante'][] = $label;
        }
    }
    
    foreach ($campos_representante as $campo => $label) {
        if (empty($datos[$campo]) && $datos[$campo] !== '0') {
            $faltantes_por_grupo['Representante'][] = $label;
        }
    }
    
    foreach ($campos_sala as $campo => $label) {
        if (!isset($datos[$campo]) || $datos[$campo] === '' || $datos[$campo] === NULL) {
            $faltantes_por_grupo['Sala/Sección'][] = $label;
        }
    }
    
    $madre = !empty($datos['madre_nombre']);
    $padre = !empty($datos['padre_nombre']);
    if (!$madre && !$padre) {
        $faltantes_por_grupo['Padres'][] = 'Madre o Padre (al menos uno)';
    }
    
    foreach ($campos_condicionales as $campo => $label) {
        if (empty($datos[$campo])) {
            $faltantes_por_grupo['Condicionales'][] = $label;
        }
    }
    
    $faltantes_plano = [];
    foreach ($faltantes_por_grupo as $grupo => $items) {
        foreach ($items as $item) {
            $faltantes_plano[] = $grupo . ': ' . $item;
        }
    }
    
    return [
        'por_grupo' => $faltantes_por_grupo,
        'plano' => $faltantes_plano,
        'total' => count($faltantes_plano)
    ];
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ========== MANEJAR PETICIONES AJAX ==========
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $sala = isset($_GET['sala']) ? trim($_GET['sala']) : '';
    $busqueda = isset($_GET['busqueda']) ? '%' . trim($_GET['busqueda']) . '%' : '';
    $filtro_anio = isset($_GET['anio']) ? trim($_GET['anio']) : '';

    $sql = "
        SELECT DISTINCT e.*, 
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
        $params[] = $busqueda;
        $params[] = $busqueda;
        $params[] = $busqueda;
        $types .= "sss";
    }
    if ($filtro_anio) {
        $sql .= " AND EXISTS (SELECT 1 FROM inscripciones i2 WHERE i2.estudiante_id = e.id AND i2.ano_escolar = ?)";
        $params[] = $filtro_anio;
        $types .= "s";
    }
    // ===== ORDEN: Año más reciente arriba, luego alfabético =====
    $sql .= " ORDER BY e.sala, e.nombre, e.apellido";

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
                        $datos_verificar = [
                            'nombre' => $e['nombre'],
                            'apellido' => $e['apellido'],
                            'fecha_nacimiento' => $e['fecha_nacimiento'],
                            'genero' => $e['genero'],
                            'cedula_escolar' => $e['cedula_escolar'],
                            'rep_nombre' => $e['rep_nombre'],
                            'rep_cedula' => $e['rep_cedula'],
                            'rep_telefono' => $e['rep_telefono'],
                            'sala' => $e['sala'],
                            'seccion_id' => $e['seccion_id'],
                            'madre_nombre' => $e['madre_nombre'],
                            'padre_nombre' => $e['padre_nombre'],
                            'enfermedad' => $e['enfermedad'],
                            'enfermedad_cual' => $e['enfermedad_cual'],
                            'educacion_fisica' => $e['educacion_fisica'],
                            'educacion_fisica_porque' => $e['educacion_fisica_porque'],
                            'alergia' => $e['alergia'],
                            'alergia_cual' => $e['alergia_cual'],
                        ];
                        $resultado_verificacion = verificarCamposCompletos($datos_verificar);
                        $faltantes_plano = $resultado_verificacion['plano'];
                        $faltantes_por_grupo = $resultado_verificacion['por_grupo'];
                        $completa = ($resultado_verificacion['total'] == 0);
                        
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
                            <?php if ($completa): ?>
                                <span class="text-success" title="✅ Inscripción completa">
                                    <i class="fas fa-check-circle fa-lg"></i>
                                </span>
                            <?php else: ?>
                                <span class="text-warning estado-icono" 
                                      style="cursor:help; position:relative;"
                                      data-bs-toggle="popover"
                                      data-bs-placement="left"
                                      data-bs-trigger="hover focus"
                                      data-bs-html="true"
                                      data-bs-content="<?= htmlspecialchars(construirContenidoPopover($faltantes_por_grupo)) ?>"
                                      title="❌ Inscripción incompleta">
                                    <i class="fas fa-exclamation-triangle fa-lg"></i>
                                    <span class="badge bg-danger rounded-pill ms-1" style="font-size:0.6rem;">
                                        <?= $resultado_verificacion['total'] ?>
                                    </span>
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
                                <a href="eliminar_estudiante_completo.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-danger" title="Eliminar estudiante (con confirmación avanzada)" onclick="return confirm('¿Está seguro de eliminar este estudiante? Se eliminarán TODOS sus registros asociados (boletines, asistencia, inscripciones, rendimiento).')">
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
    echo $html;
    exit;
}

// ========== FUNCIÓN PARA CONSTRUIR EL CONTENIDO DEL POPOVER ==========
function construirContenidoPopover($faltantes_por_grupo) {
    $html = '<div style="font-size:0.8rem; max-width:300px;">';
    $html .= '<strong class="text-danger">⚠️ Faltan datos:</strong><ul style="padding-left:15px; margin-top:5px; margin-bottom:0;">';
    foreach ($faltantes_por_grupo as $grupo => $items) {
        if (!empty($items)) {
            $html .= '<li><strong>' . htmlspecialchars($grupo) . ':</strong> ' . htmlspecialchars(implode(', ', $items)) . '</li>';
        }
    }
    $html .= '</ul></div>';
    return $html;
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
    .popover {
        max-width: 350px;
        font-size: 0.85rem;
    }
    .popover-body {
        padding: 10px 14px;
    }
    .popover-body ul {
        margin-bottom: 0;
    }
    .estado-icono {
        transition: transform 0.2s;
    }
    .estado-icono:hover {
        transform: scale(1.1);
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
                                'sala4' => 'Sala de 4 años',
                                'sala5' => 'Sala de 5 años',
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
            <h6 class="mb-0"><i class="fas fa-list-ul me-2"></i> Registro de Estudiantes <span class="badge bg-light text-dark ms-2" id="contador-total">0</span></h6>
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
        .then(response => response.text())
        .then(html => {
            tablaContainer.innerHTML = html;
            const filas = tablaContainer.querySelectorAll('tbody tr');
            const total = filas.length;
            contadorTotal.textContent = total;
            contadorFooter.textContent = total;
            
            if (typeof bootstrap !== 'undefined' && bootstrap.Popover) {
                const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                popoverTriggerList.map(function (popoverTriggerEl) {
                    return new bootstrap.Popover(popoverTriggerEl, {
                        trigger: 'hover focus',
                        html: true,
                        container: 'body'
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
    this.focus();
});

salaSelect.addEventListener('change', cargarTabla);
anioSelect.addEventListener('change', cargarTabla);

document.addEventListener('DOMContentLoaded', function() {
    if (busquedaInput) {
        busquedaInput.focus();
        const length = busquedaInput.value.length;
        busquedaInput.setSelectionRange(length, length);
    }
    cargarTabla();
});
</script>

<?php include '../includes/footer.php'; ?>