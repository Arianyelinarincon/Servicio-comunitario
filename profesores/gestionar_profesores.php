<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';

// ========== VERIFICAR AUTENTICACIÓN ==========
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /Servicio-comunitario/profesores/Login/login.php");
    exit();
}

// ========== MANEJAR PETICIONES AJAX ==========
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $buscar = isset($_GET['buscar']) ? '%' . trim($_GET['buscar']) . '%' : '';
    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    $sala = isset($_GET['sala']) ? trim($_GET['sala']) : '';

    $sql = "SELECT p.*, s.nombre AS seccion_nombre 
            FROM profesores p
            LEFT JOIN secciones s ON p.seccion = s.id
            WHERE 1=1";
    $params = [];
    $types = "";

    if ($buscar) {
        $sql .= " AND (p.nombre LIKE ? OR p.apellido LIKE ? OR p.cedula LIKE ?)";
        $params[] = $buscar; $params[] = $buscar; $params[] = $buscar;
        $types .= "sss";
    }
    if ($estado) {
        $sql .= " AND p.estatus = ?";
        $params[] = $estado;
        $types .= "s";
    }
    if ($sala) {
        $sql .= " AND p.sala = ?";
        $params[] = $sala;
        $types .= "s";
    }
    $sql .= " ORDER BY p.estatus DESC, p.nombre ASC";

    $stmt = $conexion->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Generar HTML de la tabla
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-hover table-profesores mb-0">
            <thead>
                <tr>
                    <th style="width:5%">ID</th>
                    <th style="width:10%">Cédula</th>
                    <th style="width:18%">Nombre</th>
                    <th style="width:15%">Apellido</th>
                    <th style="width:12%">Sala</th>
                    <th style="width:10%">Sección</th>
                    <th style="width:12%">Estado</th>
                    <th style="width:18%">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        $rol_label = '';
                        if ($row['rol'] === 'super_admin') $rol_label = '<span class="badge bg-danger ms-1">Super Admin</span>';
                        elseif ($row['rol'] === 'administrador') $rol_label = '<span class="badge bg-primary ms-1">Admin</span>';
                        $estado_clase = ($row['estatus'] === 'Activo') ? 'badge-activo' : 'badge-inactivo';
                    ?>
                    <tr>
                        <td class="text-center fw-bold text-muted"><?= $row['id'] ?></td>
                        <td><span class="font-monospace"><?= htmlspecialchars($row['cedula'] ?? '-') ?></span></td>
                        <td><strong><?= htmlspecialchars($row['nombre'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($row['apellido'] ?? '-') ?></td>
                        <td><span class="badge-sala"><?= htmlspecialchars($row['sala'] ?? '-') ?></span></td>
                        <td><?= htmlspecialchars($row['seccion_nombre'] ?? 'Sin asignar') ?></td>
                        <td>
                            <span class="badge badge-estado <?= $estado_clase ?>"><?= $row['estatus'] ?></span>
                            <?= $rol_label ?>
                        </td>
                        <td class="text-nowrap">
                            <a href="editar_profesor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary btn-accion" title="Editar profesor">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="detalle_profesor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info btn-accion" title="Ver detalles del profesor">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="../estudiantes/listado.php?sala=<?= urlencode($row['sala']) ?>&seccion=<?= $row['seccion'] ?>" class="btn btn-sm btn-success btn-accion" title="Ver estudiantes asignados" target="_blank">
                                <i class="fas fa-user-graduate"></i>
                            </a>
                            <?php if ($_SESSION['rol'] === 'super_admin' || $_SESSION['rol'] === 'administrador'): ?>
                            <button class="btn btn-sm btn-danger btn-accion" onclick="eliminarProfesor(<?= $row['id'] ?>, '<?= addslashes($row['nombre'] . ' ' . $row['apellido']) ?>')" title="Eliminar permanentemente">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center py-4"><i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>No se encontraron profesores.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $html = ob_get_clean();
    echo $html;
    exit;
}

include('../includes/header.php');
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
    .table-profesores {
        font-size: 0.875rem;
        vertical-align: middle;
    }
    .table-profesores thead th {
        background-color: #f0f4f8;
        color: #002d54;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #002d54;
    }
    .table-profesores tbody tr:hover {
        background-color: #e8f4f8;
    }
    .badge-estado {
        font-size: 0.7rem;
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: 500;
    }
    .badge-activo { background-color: #d4edda; color: #155724; }
    .badge-inactivo { background-color: #f8d7da; color: #721c24; }
    .badge-sala {
        background-color: #e9ecef;
        color: #002d54;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.78rem;
    }
    .btn-accion {
        margin: 2px;
        border-radius: 6px;
        padding: 5px 10px;
        font-size: 0.78rem;
    }
    .btn-accion i { font-size: 0.9rem; }
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
    /* Buscador en tiempo real */
    #busquedaInput {
        transition: all 0.3s ease;
    }
    #busquedaInput:focus {
        border-color: #002d54;
        box-shadow: 0 0 0 3px rgba(0,45,84,0.15);
    }
</style>

<div class="container-fluid px-4">
    
    <!-- Cabecera -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-users me-2"></i> Gestionar Profesores</h4>
            <small class="opacity-75"><i class="fas fa-user-tie me-1"></i> Listado completo de docentes (activos e inactivos)</small>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="agregar_profesor.php" class="btn btn-light fw-bold me-2">
                <i class="fas fa-user-plus me-2"></i> Agregar Profesor
            </a>
            <a href="panel_profesor.php" class="btn btn-light fw-bold">
                <i class="fas fa-arrow-left me-2"></i> Volver
            </a>
        </div>
    </div>

    <!-- Mensajes -->
    <?php
    if (isset($_GET['msg'])) {
        $msg = $_GET['msg'];
        $clase = 'success';
        $texto = '';
        if ($msg === 'added') $texto = '✅ Profesor agregado correctamente.';
        elseif ($msg === 'updated') $texto = '✅ Profesor actualizado correctamente.';
        elseif ($msg === 'deleted') $texto = '🗑️ Profesor eliminado permanentemente.';
        elseif ($msg === 'error') { $texto = '❌ Ocurrió un error al procesar la solicitud.'; $clase = 'danger'; }
        if ($texto): ?>
        <div class="alert alert-<?= $clase ?> alert-dismissible fade show">
            <?= $texto ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif;
    }
    ?>

    <!-- Filtros con buscador en tiempo real (sin botones) -->
    <div class="card card-filtros">
        <div class="card-body p-4">
            <form method="GET" action="gestionar_profesores.php" id="filtroForm" autocomplete="off">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted"><i class="fas fa-search me-1"></i> Buscar profesor</label>
                        <input type="text" name="buscar" id="busquedaInput" class="form-control shadow-none" placeholder="Nombre, apellido o cédula..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted"><i class="fas fa-filter me-1"></i> Estado</label>
                        <select name="estado" id="estadoSelect" class="form-select shadow-none">
                            <option value="">Todos</option>
                            <option value="Activo" <?= (isset($_GET['estado']) && $_GET['estado'] === 'Activo') ? 'selected' : '' ?>>Activo</option>
                            <option value="Inactivo" <?= (isset($_GET['estado']) && $_GET['estado'] === 'Inactivo') ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted"><i class="fas fa-graduation-cap me-1"></i> Sala</label>
                        <select name="sala" id="salaSelect" class="form-select shadow-none">
                            <option value="">Todas</option>
                            <?php
                            $salas = ['sala4'=>'Sala 4 Años', 'sala5'=>'Sala 5 Años', '1ro'=>'1° Grado', '2do'=>'2° Grado', '3ro'=>'3° Grado', '4to'=>'4° Grado', '5to'=>'5° Grado', '6to'=>'6° Grado'];
                            $sala_seleccionada = $_GET['sala'] ?? '';
                            foreach ($salas as $k => $v) {
                                echo '<option value="'.$k.'" '.($sala_seleccionada==$k?'selected':'').'>'.$v.'</option>';
                            }
                            ?>
                        </select>
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
            <h6 class="mb-0"><i class="fas fa-list-ul me-2"></i> Lista de Profesores <span class="badge bg-light text-dark ms-2" id="contador-total">0</span></h6>
            <small class="opacity-75"><i class="fas fa-clock me-1"></i> Filtro automático</small>
        </div>
        <div class="card-body p-0" id="tabla-container">
            <!-- El contenido se carga dinámicamente con JavaScript -->
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small"><i class="fas fa-database me-1"></i> <span id="contador-footer">0</span> profesor(es) encontrados</span>
            <span class="text-muted small"><i class="fas fa-sync-alt me-1"></i> Filtro automático</span>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de <strong>eliminar permanentemente</strong> al profesor <span id="nombre-profesor-eliminar" class="fw-bold text-danger"></span>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Esta acción es irreversible.</strong>
                </div>
            </div>
            <div class="modal-footer">
                <form action="eliminar_profesor.php" method="POST" id="form-eliminar">
                    <input type="hidden" name="id" id="eliminar-id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt me-2"></i>Sí, eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// ========== FILTRO EN TIEMPO REAL ==========
const busquedaInput = document.getElementById('busquedaInput');
const estadoSelect = document.getElementById('estadoSelect');
const salaSelect = document.getElementById('salaSelect');
const tablaContainer = document.getElementById('tabla-container');
const contadorTotal = document.getElementById('contador-total');
const contadorFooter = document.getElementById('contador-footer');
let timeoutId = null;

function cargarTabla() {
    const termino = busquedaInput.value.trim();
    const estado = estadoSelect.value;
    const sala = salaSelect.value;

    // Mostrar loading
    tablaContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-2">Cargando profesores...</p>
        </div>
    `;

    const params = new URLSearchParams();
    if (termino) params.append('buscar', termino);
    if (estado) params.append('estado', estado);
    if (sala) params.append('sala', sala);
    params.append('ajax', '1');

    fetch('gestionar_profesores.php?' + params.toString())
        .then(response => response.text())
        .then(html => {
            tablaContainer.innerHTML = html;
            // Actualizar contadores
            const filas = tablaContainer.querySelectorAll('tbody tr');
            const total = filas.length;
            contadorTotal.textContent = total;
            contadorFooter.textContent = total;
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

// Disparar búsqueda al escribir (con debounce)
busquedaInput.addEventListener('input', function() {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(cargarTabla, 400);
    // Mantener el foco
    this.focus();
});

// Disparar búsqueda al cambiar selects
estadoSelect.addEventListener('change', cargarTabla);
salaSelect.addEventListener('change', cargarTabla);

// Mantener el foco después de recargar
document.addEventListener('DOMContentLoaded', function() {
    if (busquedaInput) {
        busquedaInput.focus();
        const length = busquedaInput.value.length;
        busquedaInput.setSelectionRange(length, length);
    }
    cargarTabla();
});

// ========== ELIMINAR PROFESOR ==========
function eliminarProfesor(id, nombre) {
    document.getElementById('nombre-profesor-eliminar').textContent = nombre;
    document.getElementById('eliminar-id').value = id;
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include('../includes/footer.php'); ?>