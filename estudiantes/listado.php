<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'admin', 'super_admin', 'directiva'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once '../config/conexion.php';

// ========== FUNCIÓN PARA VERIFICAR CAMPOS COMPLETOS (MEJORADA) ==========
function verificarCamposCompletos($datos) {
    $faltantes = [];
    
    // Campos del estudiante (obligatorios)
    $campos_estudiante = [
        'nombre' => 'Nombre',
        'apellido' => 'Apellido',
        'fecha_nacimiento' => 'Fecha Nac.',
        'genero' => 'Sexo',
        'cedula_escolar' => 'Cédula Escolar',
    ];
    
    // Campos del representante (obligatorios)
    $campos_representante = [
        'rep_nombre' => 'Nombre',
        'rep_cedula' => 'Cédula',
        'rep_telefono' => 'Teléfono',
    ];
    
    // Sala y sección
    $campos_sala = [
        'sala' => 'Sala/Grado',
        'seccion_id' => 'Sección',
    ];
    
    // Padres (al menos uno)
    $campos_padres = [
        'madre_nombre' => 'Madre (nombre)',
        'padre_nombre' => 'Padre (nombre)',
    ];
    
    // Condicionales (enfermedad, educación física, alergia)
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
    
    // Verificar cada grupo
    $faltantes_por_grupo = [];
    
    // Estudiante
    foreach ($campos_estudiante as $campo => $label) {
        if (empty($datos[$campo]) && $datos[$campo] !== '0') {
            $faltantes_por_grupo['Estudiante'][] = $label;
        }
    }
    
    // Representante
    foreach ($campos_representante as $campo => $label) {
        if (empty($datos[$campo]) && $datos[$campo] !== '0') {
            $faltantes_por_grupo['Representante'][] = $label;
        }
    }
    
    // Sala / Sección
    foreach ($campos_sala as $campo => $label) {
        if (!isset($datos[$campo]) || $datos[$campo] === '' || $datos[$campo] === NULL) {
            $faltantes_por_grupo['Sala/Sección'][] = $label;
        }
    }
    
    // Padres (al menos uno)
    $madre = !empty($datos['madre_nombre']);
    $padre = !empty($datos['padre_nombre']);
    if (!$madre && !$padre) {
        $faltantes_por_grupo['Padres'][] = 'Madre o Padre (al menos uno)';
    }
    
    // Condicionales
    foreach ($campos_condicionales as $campo => $label) {
        if (empty($datos[$campo])) {
            $faltantes_por_grupo['Condicionales'][] = $label;
        }
    }
    
    // Construir array plano para tooltip simple
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
                                <!-- Icono + tooltip mejorado -->
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
                                <!-- Tooltip simple como respaldo -->
                                <span class="d-none" data-faltantes="<?= htmlspecialchars(implode(' | ', $faltantes_plano)) ?>"></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="font-monospace"><?= htmlspecialchars($e['cedula_escolar']) ?></span></td>
                        <td><span class="badge-sala"><?= htmlspecialchars($e['sala']) ?></span></td>
                        <td><?= htmlspecialchars($e['seccion_nombre'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($e['profesor_nombre'] ?? 'Sin asignar') ?></td>
                        <td><?= htmlspecialchars($e['rep_nombre'] ?? 'No asignado') ?></td>
                        <td class="text-center"><?= htmlspecialchars($e['ano_escolar_actual'] ?? 'No registrado') ?></td>
                        <td class="text-nowrap">
                            <a href="ver_ficha.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-info" target="_blank" title="Ver ficha completa"><i class="fas fa-eye"></i></a>
                            <a href="editar_estudiantes.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-primary" title="Editar datos"><i class="fas fa-edit"></i></a>
                            <?php if ($_SESSION['rol'] === 'super_admin' || $_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'directiva'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este estudiante?')">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                            </form>
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

// ========== MANEJAR ELIMINACIÓN (POST) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Error CSRF");
    }
    $id = intval($_POST['id']);

    $stmt_nombre = $conexion->prepare("SELECT nombre, apellido FROM estudiantes WHERE id = ?");
    $stmt_nombre->bind_param("i", $id);
    $stmt_nombre->execute();
    $result_nombre = $stmt_nombre->get_result();
    $estudiante = $result_nombre->fetch_assoc();
    $stmt_nombre->close();
    $nombre_estudiante = $estudiante ? $estudiante['nombre'] . ' ' . $estudiante['apellido'] : 'ID: ' . $id;

    $stmt = $conexion->prepare("UPDATE estudiantes SET estatus = 'Inactivo' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    if ($usuario_id > 0 && function_exists('registrarAuditoria')) {
        registrarAuditoria($conexion, $usuario_id, 'ELIMINAR_ESTUDIANTE', 'estudiantes', $id, "Baja lógica (estatus -> Inactivo) del estudiante: $nombre_estudiante");
    }

    header("Location: listado.php?msg=deleted");
    exit();
}

include '../includes/header.php';

$sala_filtro = isset($_GET['sala']) ? trim($_GET['sala']) : '';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
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
    /* Estilo para el popover */
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
                            $salas_disponibles = $conexion->query("SELECT DISTINCT sala FROM secciones ORDER BY sala");
                            while ($row = $salas_disponibles->fetch_assoc()):
                                $selected = ($sala_filtro == $row['sala']) ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars($row['sala']) ?>" <?= $selected ?>><?= ucfirst($row['sala']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="small fw-bold text-muted"><i class="fas fa-search me-1"></i> Buscar estudiante</label>
                        <input type="text" name="busqueda" id="busquedaInput" class="form-control shadow-none" placeholder="Nombre, apellido o cédula..." value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                    <div class="col-md-4 text-end">
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
const tablaContainer = document.getElementById('tabla-container');
const contadorTotal = document.getElementById('contador-total');
const contadorFooter = document.getElementById('contador-footer');
let timeoutId = null;

function cargarTabla() {
    const termino = busquedaInput.value.trim();
    const sala = salaSelect.value;

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
    params.append('ajax', '1');

    fetch('listado.php?' + params.toString())
        .then(response => response.text())
        .then(html => {
            tablaContainer.innerHTML = html;
            const filas = tablaContainer.querySelectorAll('tbody tr');
            const total = filas.length;
            contadorTotal.textContent = total;
            contadorFooter.textContent = total;
            
            // Inicializar popovers de Bootstrap para los iconos de estado
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