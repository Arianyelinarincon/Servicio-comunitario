<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once __DIR__ . '/../config/conexion.php';

// ==================== AJAX PARA CARGAR SECCIONES ====================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'secciones') {
    header('Content-Type: application/json');
    $sala = $_GET['sala'] ?? '';
    $secciones = [];
    if (!empty($sala)) {
        $stmt = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
        $stmt->bind_param("s", $sala);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $secciones[] = $row;
        }
        $stmt->close();
    }
    echo json_encode($secciones);
    exit;
}

// ==================== AJAX PARA CRUD ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'agregar') {
        $nombre = strtoupper(trim($_POST['nombre']));
        $apellido = strtoupper(trim($_POST['apellido']));
        $cedula = trim($_POST['cedula']);
        $seccion = intval($_POST['seccion']);
        $sala = trim($_POST['sala']);
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        if (empty($nombre) || empty($apellido) || empty($cedula) || empty($sala) || empty($seccion)) {
            echo json_encode(['ok' => false, 'msg' => 'Faltan datos obligatorios']);
            exit;
        }
        $stmt = $conexion->prepare("INSERT INTO profesores (nombre, apellido, cedula, seccion, sala, telefono, direccion, estatus, rol) VALUES (?, ?, ?, ?, ?, ?, ?, 'Activo', 'profesor')");
        $stmt->bind_param("sssisss", $nombre, $apellido, $cedula, $seccion, $sala, $telefono, $direccion);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['ok' => $ok, 'msg' => $ok ? 'Profesor agregado' : 'Error al guardar']);
        exit;
    }
    
    if ($action === 'editar') {
        $id = intval($_POST['id']);
        $nombre = strtoupper(trim($_POST['nombre']));
        $apellido = strtoupper(trim($_POST['apellido']));
        $cedula = trim($_POST['cedula']);
        $seccion = intval($_POST['seccion']);
        $sala = trim($_POST['sala']);
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        $estatus = $_POST['estatus'];
        $stmt = $conexion->prepare("UPDATE profesores SET nombre=?, apellido=?, cedula=?, seccion=?, sala=?, telefono=?, direccion=?, estatus=? WHERE id=?");
        $stmt->bind_param("sssissssi", $nombre, $apellido, $cedula, $seccion, $sala, $telefono, $direccion, $estatus, $id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['ok' => $ok, 'msg' => $ok ? 'Profesor actualizado' : 'Error al actualizar']);
        exit;
    }
    
    if ($action === 'eliminar') {
        $id = intval($_POST['id']);
        $stmt = $conexion->prepare("UPDATE profesores SET estatus = 'Inactivo' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['ok' => $ok, 'msg' => $ok ? 'Profesor eliminado' : 'Error al eliminar']);
        exit;
    }
    
    if ($action === 'cargar') {
        $id = intval($_POST['id']);
        $stmt = $conexion->prepare("SELECT * FROM profesores WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        echo json_encode($data);
        exit;
    }
}

// ==================== LISTADO Y FILTROS ====================
$sala_filtro = $_GET['sala'] ?? '';
$seccion_filtro = $_GET['seccion'] ?? '';
$busqueda = trim($_GET['busqueda'] ?? ''); // Capturar el término de búsqueda

$sql = "SELECT p.*, s.nombre AS nombre_seccion 
        FROM profesores p 
        LEFT JOIN secciones s ON p.seccion = s.id 
        WHERE p.rol = 'profesor'";

if ($sala_filtro) {
    $sql .= " AND p.sala = '" . mysqli_real_escape_string($conexion, $sala_filtro) . "'";
}
if ($seccion_filtro) {
    $sql .= " AND p.seccion = " . intval($seccion_filtro);
}
// Nueva condición de búsqueda
if ($busqueda) {
    $busqueda_safe = mysqli_real_escape_string($conexion, $busqueda);
    $sql .= " AND (p.nombre LIKE '%$busqueda_safe%' OR p.apellido LIKE '%$busqueda_safe%' OR p.cedula LIKE '%$busqueda_safe%')";
}

$sql .= " ORDER BY p.sala, s.nombre, p.nombre";
$result = $conexion->query($sql);
$salas = $conexion->query("SELECT DISTINCT sala FROM secciones ORDER BY sala");

include('../includes/header.php');
?>

<div class="container-fluid px-4">
    <h2 class="mt-4 mb-4">Gestión de Profesores</h2>
    <div id="mensaje"></div>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end" id="filtroForm">
                <div class="col-md-3">
    <label class="form-label">Buscar Profesor</label>
    <input type="text" name="busqueda" class="form-control" placeholder="Nombre o Cédula..." 
           value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
</div>
                <div class="col-md-3">
                    <label class="form-label">Sala / Grado</label>
                    <select name="sala" id="filtro_sala" class="form-select">
                        <option value="">Todas</option>
                        <?php while($row = $salas->fetch_assoc()): ?>
                            <option value="<?= $row['sala'] ?>" <?= ($sala_filtro == $row['sala']) ? 'selected' : '' ?>><?= ucfirst($row['sala']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sección</label>
                    <select name="seccion" id="filtro_seccion" class="form-select" <?= empty($sala_filtro) ? 'disabled' : '' ?>>
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="gestionar_profesores.php" class="btn btn-secondary">Limpiar</a>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProfesor" onclick="abrirModalAgregar()">+ Agregar Profesor</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tabla de profesores -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr><th>Nombre</th><th>Cédula</th><th>Sala</th><th>Sección</th><th>Teléfono</th><th>Estatus</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($p = $result->fetch_assoc()): ?>
                            <tr id="prof-<?= $p['id'] ?>">
                                <td><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></td>
                                <td><?= htmlspecialchars($p['cedula']) ?></td>
                                <td><?= htmlspecialchars($p['sala']) ?></td>
                                <td><?= htmlspecialchars($p['nombre_seccion'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($p['telefono']) ?></td>
                                <td><span class="badge bg-<?= ($p['estatus'] == 'Activo') ? 'success' : 'secondary' ?>"><?= $p['estatus'] ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editarProfesor(<?= $p['id'] ?>)">Editar</button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarProfesor(<?= $p['id'] ?>)">Eliminar</button>
                                   <a href="../estudiantes/listado.php?sala=<?= urlencode($p['sala']) ?>&seccion=<?= $p['seccion'] ?>" class="btn btn-sm btn-info" target="_blank">Ver Estudiantes</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No hay profesores registrados.<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalProfesor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitulo">Registrar Profesor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formProfesor">
                    <input type="hidden" id="profesor_id" name="id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label>Nombre *</label><input type="text" id="nombre" name="nombre" class="form-control text-uppercase" required></div>
                        <div class="col-md-6 mb-3"><label>Apellido *</label><input type="text" id="apellido" name="apellido" class="form-control text-uppercase" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label>Cédula *</label><input type="text" id="cedula" name="cedula" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label>Teléfono</label><input type="text" id="telefono" name="telefono" class="form-control"></div>
                    </div>
                    <div class="mb-3"><label>Dirección</label><textarea id="direccion" name="direccion" class="form-control" rows="2"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Sala / Grado *</label>
                            <select id="sala" name="sala" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="sala4">Sala 4 años</option><option value="sala5">Sala 5 años</option>
                                <option value="1ro">1er Grado</option><option value="2do">2do Grado</option>
                                <option value="3ro">3er Grado</option><option value="4to">4to Grado</option>
                                <option value="5to">5to Grado</option><option value="6to">6to Grado</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Sección *</label>
                            <select id="seccion" name="seccion" class="form-select" required>
                                <option value="">Primero seleccione sala</option>
                            </select>
                        </div>
                    </div>
                    <div id="estatus_div" style="display:none;"><label>Estatus</label><select id="estatus" name="estatus" class="form-select"><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardar">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Función para cargar secciones al cambiar sala en el modal
document.getElementById('sala').addEventListener('change', function() {
    let sala = this.value;
    let sel = document.getElementById('seccion');
    if (!sala) {
        sel.innerHTML = '<option value="">Primero seleccione sala</option>';
        return;
    }
    sel.innerHTML = '<option value="">Cargando...</option>';
    fetch(`gestionar_profesores.php?ajax=secciones&sala=${encodeURIComponent(sala)}`)
        .then(response => response.json())
        .then(data => {
            let options = '<option value="">Seleccione sección</option>';
            data.forEach(sec => {
                options += `<option value="${sec.id}">${sec.nombre}</option>`;
            });
            sel.innerHTML = options;
        })
        .catch(error => {
            console.error('Error:', error);
            sel.innerHTML = '<option value="">Error al cargar</option>';
        });
});

function abrirModalAgregar() {
    document.getElementById('formProfesor').reset();
    document.getElementById('profesor_id').value = '';
    document.getElementById('estatus_div').style.display = 'none';
    document.getElementById('modalTitulo').innerText = 'Registrar Profesor';
    document.getElementById('seccion').innerHTML = '<option value="">Primero seleccione sala</option>';
    // Limpiar el select de sala si es necesario
}

function editarProfesor(id) {
    fetch('gestionar_profesores.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `action=cargar&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.id) {
            document.getElementById('profesor_id').value = data.id;
            document.getElementById('nombre').value = data.nombre;
            document.getElementById('apellido').value = data.apellido;
            document.getElementById('cedula').value = data.cedula;
            document.getElementById('telefono').value = data.telefono;
            document.getElementById('direccion').value = data.direccion;
            document.getElementById('sala').value = data.sala;
            
            // Cargar secciones según la sala y luego seleccionar la sección asignada
            let sala = data.sala;
            let sel = document.getElementById('seccion');
            fetch(`gestionar_profesores.php?ajax=secciones&sala=${encodeURIComponent(sala)}`)
                .then(res => res.json())
                .then(secciones => {
                    let options = '<option value="">Seleccione sección</option>';
                    secciones.forEach(sec => {
                        let selected = (sec.id == data.seccion) ? 'selected' : '';
                        options += `<option value="${sec.id}" ${selected}>${sec.nombre}</option>`;
                    });
                    sel.innerHTML = options;
                });
            
            document.getElementById('estatus').value = data.estatus;
            document.getElementById('estatus_div').style.display = 'block';
            document.getElementById('modalTitulo').innerText = 'Editar Profesor';
            new bootstrap.Modal(document.getElementById('modalProfesor')).show();
        }
    });
}

function eliminarProfesor(id) {
    if (!confirm('¿Desincorporar este profesor?')) return;
    fetch('gestionar_profesores.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `action=eliminar&id=${id}`
    })
    .then(response => response.json())
    .then(res => {
        mostrarMensaje(res.msg, res.ok ? 'success' : 'danger');
        if (res.ok) location.reload();
    });
}

document.getElementById('btnGuardar').addEventListener('click', function() {
    let id = document.getElementById('profesor_id').value;
    let action = id ? 'editar' : 'agregar';
    let formData = new FormData(document.getElementById('formProfesor'));
    formData.append('action', action);
    
    fetch('gestionar_profesores.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        mostrarMensaje(res.msg, res.ok ? 'success' : 'danger');
        if (res.ok) {
            bootstrap.Modal.getInstance(document.getElementById('modalProfesor')).hide();
            location.reload();
        }
    });
});

function mostrarMensaje(msg, tipo) {
    let div = document.getElementById('mensaje');
    div.innerHTML = `<div class="alert alert-${tipo} alert-dismissible fade show">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
    setTimeout(() => div.innerHTML = '', 3000);
}

// Filtros dependientes en la cabecera
let filtroSala = document.getElementById('filtro_sala');
let filtroSeccion = document.getElementById('filtro_seccion');

function cargarSeccionesFiltro(sala, seleccionada) {
    if (!sala) {
        filtroSeccion.innerHTML = '<option value="">Todas</option>';
        filtroSeccion.disabled = true;
        return;
    }
    filtroSeccion.disabled = true;
    filtroSeccion.innerHTML = '<option value="">Cargando...</option>';
    fetch(`gestionar_profesores.php?ajax=secciones&sala=${encodeURIComponent(sala)}`)
        .then(res => res.json())
        .then(data => {
            let opts = '<option value="">Todas</option>';
            data.forEach(sec => {
                let selected = (seleccionada == sec.id) ? 'selected' : '';
                opts += `<option value="${sec.id}" ${selected}>${sec.nombre}</option>`;
            });
            filtroSeccion.innerHTML = opts;
            filtroSeccion.disabled = false;
        })
        .catch(() => {
            filtroSeccion.innerHTML = '<option value="">Error</option>';
            filtroSeccion.disabled = false;
        });
}

filtroSala.addEventListener('change', function() {
    cargarSeccionesFiltro(this.value, null);
    document.getElementById('filtroForm').submit();
});
filtroSeccion.addEventListener('change', () => document.getElementById('filtroForm').submit());

// Inicializar filtros si ya hay sala seleccionada
if (filtroSala.value) {
    cargarSeccionesFiltro(filtroSala.value, <?= json_encode($seccion_filtro) ?>);
} else {
    filtroSeccion.disabled = true;
    filtroSeccion.innerHTML = '<option value="">Todas</option>';
}
</script>

<?php include('../includes/footer.php'); ?>