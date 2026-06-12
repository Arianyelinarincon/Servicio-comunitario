<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once __DIR__ . '/../config/conexion.php';

// ==================== AJAX PARA CARGAR SECCIONES (para el modal) ====================
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

// ==================== FILTROS (REDISEÑADOS) ====================
$busqueda = trim($_GET['busqueda'] ?? '');
$estatus_filtro = trim($_GET['estatus'] ?? '');
$seccion_filtro = trim($_GET['seccion'] ?? '');

// ========== PAGINACIÓN ==========
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Consulta para contar total
$sql_count = "SELECT COUNT(*) as total FROM profesores p WHERE p.rol = 'profesor'";
if ($busqueda) {
    $busqueda_safe = mysqli_real_escape_string($conexion, $busqueda);
    $sql_count .= " AND (p.nombre LIKE '%$busqueda_safe%' OR p.apellido LIKE '%$busqueda_safe%' OR p.cedula LIKE '%$busqueda_safe%')";
}
if ($estatus_filtro) {
    $sql_count .= " AND p.estatus = '" . mysqli_real_escape_string($conexion, $estatus_filtro) . "'";
}
if ($seccion_filtro) {
    $sql_count .= " AND p.seccion = " . intval($seccion_filtro);
}
$total_registros = $conexion->query($sql_count)->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta principal con LIMIT
$sql = "SELECT p.*, s.nombre AS nombre_seccion 
        FROM profesores p 
        LEFT JOIN secciones s ON p.seccion = s.id 
        WHERE p.rol = 'profesor'";
if ($busqueda) {
    $busqueda_safe = mysqli_real_escape_string($conexion, $busqueda);
    $sql .= " AND (p.nombre LIKE '%$busqueda_safe%' OR p.apellido LIKE '%$busqueda_safe%' OR p.cedula LIKE '%$busqueda_safe%')";
}
if ($estatus_filtro) {
    $sql .= " AND p.estatus = '" . mysqli_real_escape_string($conexion, $estatus_filtro) . "'";
}
if ($seccion_filtro) {
    $sql .= " AND p.seccion = " . intval($seccion_filtro);
}
$sql .= " ORDER BY p.sala, s.nombre, p.nombre LIMIT $offset, $registros_por_pagina";
$result = $conexion->query($sql);

// Obtener listas para los combos de filtros
$secciones_lista = $conexion->query("SELECT id, nombre, sala FROM secciones ORDER BY sala, nombre");
$estatus_opciones = ['Activo', 'Inactivo'];

include('../includes/header.php');
?>

<div class="container-fluid px-4">
    <h2 class="mt-4 mb-4">Gestión de Profesores</h2>
    <div id="mensaje"></div>
    
    <!-- Filtros rediseñados -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end" id="filtroForm">
                <div class="col-md-4">
                    <label class="form-label">Buscar (nombre, apellido o cédula)</label>
                    <input type="text" name="busqueda" class="form-control" placeholder="Ej: Juan Pérez" 
                           value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estatus</label>
                    <select name="estatus" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach($estatus_opciones as $est): ?>
                            <option value="<?= $est ?>" <?= ($estatus_filtro == $est) ? 'selected' : '' ?>><?= $est ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sección</label>
                    <select name="seccion" class="form-select">
                        <option value="">Todas</option>
                        <?php while($sec = $secciones_lista->fetch_assoc()): ?>
                            <option value="<?= $sec['id'] ?>" <?= ($seccion_filtro == $sec['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sec['sala'] . ' - Sección ' . $sec['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filtrar</button>
                </div>
                <div class="col-md-12 text-end">
                    <a href="gestionar_profesores.php" class="btn btn-secondary btn-sm">Limpiar filtros</a>
                    <button type="button" class="btn btn-success btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#modalProfesor" onclick="abrirModalAgregar()">
                        <i class="fas fa-plus"></i> Agregar Profesor
                    </button>
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
                        <tr>
                            <th>Nombre</th><th>Cédula</th><th>Sala</th><th>Sección</th><th>Teléfono</th><th>Estatus</th><th>Acciones</th>
                        </tr>
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
                                 </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No hay profesores registrados con esos filtros.<?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?= ($i == $pagina_actual) ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Agregar/Editar Profesor (igual que antes) -->
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
// Cargar secciones según sala en el modal (igual que antes)
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
        .catch(() => sel.innerHTML = '<option value="">Error al cargar</option>');
});

function abrirModalAgregar() {
    document.getElementById('formProfesor').reset();
    document.getElementById('profesor_id').value = '';
    document.getElementById('estatus_div').style.display = 'none';
    document.getElementById('modalTitulo').innerText = 'Registrar Profesor';
    document.getElementById('seccion').innerHTML = '<option value="">Primero seleccione sala</option>';
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
</script>

<?php include('../includes/footer.php'); ?>