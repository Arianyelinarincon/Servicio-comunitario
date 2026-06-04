<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once __DIR__ . '/../config/conexion.php';

// ========== AJAX HANDLER para cargar secciones dinámicamente ==========
if (isset($_GET['ajax']) && $_GET['ajax'] == 'secciones') {
    header('Content-Type: application/json');
    $sala = $_GET['sala'] ?? '';
    $secciones = [];
    if ($sala) {
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

// ========== PROCESAR PETICIONES AJAX DE CRUD ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nombre = strtoupper(trim($_POST['nombre']));
        $apellido = strtoupper(trim($_POST['apellido']));
        $cedula = trim($_POST['cedula']);
        $seccion = intval($_POST['seccion']);
        $sala = trim($_POST['sala']);
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        $permiso_editar = 1;
        if (empty($nombre) || empty($apellido) || empty($cedula) || empty($sala) || empty($seccion)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben llenarse.']);
            exit;
        }
        $sql = "INSERT INTO profesores (nombre, apellido, cedula, seccion, sala, telefono, direccion, permiso_editar_perfil, estatus, rol) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Activo', 'profesor')";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssisssi", $nombre, $apellido, $cedula, $seccion, $sala, $telefono, $direccion, $permiso_editar);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Profesor agregado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al agregar: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $nombre = strtoupper(trim($_POST['nombre']));
        $apellido = strtoupper(trim($_POST['apellido']));
        $cedula = trim($_POST['cedula']);
        $seccion = intval($_POST['seccion']);
        $sala = trim($_POST['sala']);
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);
        $estatus = $_POST['estatus'];
        if (empty($id) || empty($nombre) || empty($apellido) || empty($cedula) || empty($sala) || empty($seccion)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
            exit;
        }
        $sql = "UPDATE profesores SET nombre=?, apellido=?, cedula=?, seccion=?, sala=?, telefono=?, direccion=?, estatus=? WHERE id=? AND rol='profesor'";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssissssi", $nombre, $apellido, $cedula, $seccion, $sala, $telefono, $direccion, $estatus, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Profesor actualizado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conexion->prepare("UPDATE profesores SET estatus = 'Inactivo' WHERE id = ? AND rol = 'profesor'");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $success, 'message' => $success ? 'Profesor eliminado' : 'Error al eliminar']);
        exit;
    }

    if ($action === 'get') {
        $id = intval($_POST['id']);
        $stmt = $conexion->prepare("SELECT * FROM profesores WHERE id = ? AND rol = 'profesor'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        echo json_encode($data);
        exit;
    }
}

// ========== FILTRADO Y LISTADO (para la vista inicial) ==========
$sala_filtro = $_GET['sala'] ?? '';
$seccion_filtro = $_GET['seccion'] ?? '';
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
$sql .= " ORDER BY p.sala, s.nombre, p.nombre";
$result = $conexion->query($sql);
$salas = $conexion->query("SELECT DISTINCT sala FROM secciones ORDER BY sala");

include('../includes/header.php');
?>

<div class="container-fluid px-4">
    <h2 class="mt-4 mb-4">Gestión de Profesores</h2>
    <div id="mensaje-alerta"></div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end" id="filtroForm">
                <div class="col-md-3">
                    <label class="form-label">Sala / Grado</label>
                    <select name="sala" id="select-sala" class="form-select">
                        <option value="">Todas</option>
                        <?php while($row = $salas->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['sala']) ?>" <?= ($sala_filtro == $row['sala']) ? 'selected' : '' ?>>
                                <?= ucfirst($row['sala']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sección</label>
                    <select name="seccion" id="select-seccion" class="form-select" <?= empty($sala_filtro) ? 'disabled' : '' ?>>
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="gestionar_profesores.php" class="btn btn-secondary">Limpiar</a>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#profesorModal" onclick="abrirModalAgregar()">+ Agregar Profesor</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de profesores -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="tablaProfesores">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Cédula</th>
                            <th>Sala</th>
                            <th>Sección</th>
                            <th>Teléfono</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($prof = $result->fetch_assoc()): ?>
                            <tr id="fila-<?= $prof['id'] ?>">
                                <td><?= htmlspecialchars($prof['nombre'] . ' ' . $prof['apellido']) ?></td>
                                <td><?= htmlspecialchars($prof['cedula']) ?></td>
                                <td><?= htmlspecialchars($prof['sala']) ?></td>
                                <td><?= htmlspecialchars($prof['nombre_seccion'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($prof['telefono']) ?></td>
                                <td><span class="badge <?= ($prof['estatus'] == 'Activo') ? 'bg-success' : 'bg-secondary' ?>"><?= $prof['estatus'] ?></span></td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-primary" onclick="editarProfesor(<?= $prof['id'] ?>)">Editar</button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarProfesor(<?= $prof['id'] ?>)">Eliminar</button>
                                    <a href="../estudiantes/index.php?sala=<?= urlencode($prof['sala']) ?>&seccion=<?= $prof['seccion'] ?>" class="btn btn-sm btn-info" target="_blank">Ver Estudiantes</a>
                                 </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No hay profesores registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Agregar/Editar Profesor -->
<div class="modal fade" id="profesorModal" tabindex="-1" aria-labelledby="profesorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="profesorModalLabel">Registrar Profesor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="profesorForm">
                    <input type="hidden" name="id" id="profesorId" value="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" id="nombre" class="form-control text-uppercase" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Apellido *</label>
                            <input type="text" name="apellido" id="apellido" class="form-control text-uppercase" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cédula *</label>
                            <input type="text" name="cedula" id="cedula" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <textarea name="direccion" id="direccion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sala / Grado *</label>
                            <select name="sala" id="sala" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="sala4">Sala 4 años</option>
                                <option value="sala5">Sala 5 años</option>
                                <option value="1ro">1er Grado</option>
                                <option value="2do">2do Grado</option>
                                <option value="3ro">3er Grado</option>
                                <option value="4to">4to Grado</option>
                                <option value="5to">5to Grado</option>
                                <option value="6to">6to Grado</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sección *</label>
                            <select name="seccion" id="seccion" class="form-select" required>
                                <option value="">Primero seleccione sala</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3" id="estatusField" style="display: none;">
                        <label class="form-label">Estatus</label>
                        <select name="estatus" id="estatus" class="form-select">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardarProfesorBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Cargar secciones al cambiar la sala en el modal
    document.getElementById('sala').addEventListener('change', function() {
        const sala = this.value;
        const seccionSelect = document.getElementById('seccion');
        if (!sala) {
            seccionSelect.innerHTML = '<option value="">Primero seleccione sala</option>';
            return;
        }
        seccionSelect.innerHTML = '<option value="">Cargando...</option>';
        fetch(`gestionar_profesores.php?ajax=secciones&sala=${encodeURIComponent(sala)}`)
            .then(res => res.json())
            .then(data => {
                let options = '<option value="">Seleccione sección...</option>';
                data.forEach(sec => {
                    options += `<option value="${sec.id}">${sec.nombre}</option>`;
                });
                seccionSelect.innerHTML = options;
            })
            .catch(() => {
                seccionSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    });

    // Abrir modal para agregar (limpia campos)
    function abrirModalAgregar() {
        document.getElementById('profesorForm').reset();
        document.getElementById('profesorId').value = '';
        document.getElementById('estatusField').style.display = 'none';
        document.getElementById('profesorModalLabel').innerText = 'Registrar Profesor';
        // Limpiar select de sección
        const seccionSelect = document.getElementById('seccion');
        seccionSelect.innerHTML = '<option value="">Primero seleccione sala</option>';
        seccionSelect.disabled = false;
    }

    // Editar profesor: cargar datos vía AJAX y abrir modal
    function editarProfesor(id) {
        fetch('gestionar_profesores.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: `action=get&id=${id}`
        })
        .then(res => res.json())
        .then(data => {
            if (data) {
                document.getElementById('profesorId').value = data.id;
                document.getElementById('nombre').value = data.nombre;
                document.getElementById('apellido').value = data.apellido;
                document.getElementById('cedula').value = data.cedula;
                document.getElementById('telefono').value = data.telefono;
                document.getElementById('direccion').value = data.direccion;
                document.getElementById('sala').value = data.sala;
                // Cargar secciones según la sala
                const sala = data.sala;
                const seccionSelect = document.getElementById('seccion');
                fetch(`gestionar_profesores.php?ajax=secciones&sala=${encodeURIComponent(sala)}`)
                    .then(res => res.json())
                    .then(secciones => {
                        let options = '<option value="">Seleccione sección...</option>';
                        secciones.forEach(sec => {
                            let selected = (sec.id == data.seccion) ? 'selected' : '';
                            options += `<option value="${sec.id}" ${selected}>${sec.nombre}</option>`;
                        });
                        seccionSelect.innerHTML = options;
                    });
                document.getElementById('estatus').value = data.estatus;
                document.getElementById('estatusField').style.display = 'block';
                document.getElementById('profesorModalLabel').innerText = 'Editar Profesor';
                // Mostrar modal
                var myModal = new bootstrap.Modal(document.getElementById('profesorModal'));
                myModal.show();
            }
        });
    }

    // Guardar (agregar o editar)
    document.getElementById('guardarProfesorBtn').addEventListener('click', function() {
        const form = document.getElementById('profesorForm');
        const formData = new FormData(form);
        const id = document.getElementById('profesorId').value;
        const action = id ? 'edit' : 'add';
        formData.append('action', action);
        
        fetch('gestionar_profesores.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta(data.message, 'success');
                cerrarModal();
                recargarTabla(); // recargar la tabla sin recargar página
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        });
    });

    function eliminarProfesor(id) {
        if (!confirm('¿Desincorporar este profesor?')) return;
        fetch('gestionar_profesores.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: `action=delete&id=${id}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta(data.message, 'success');
                recargarTabla();
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        });
    }

    function recargarTabla() {
        // Recargar la misma página conservando filtros
        const sala = document.getElementById('select-sala').value;
        const seccion = document.getElementById('select-seccion').value;
        let url = 'gestionar_profesores.php';
        let params = [];
        if (sala) params.push(`sala=${encodeURIComponent(sala)}`);
        if (seccion) params.push(`seccion=${seccion}`);
        if (params.length) url += '?' + params.join('&');
        window.location.href = url;
    }

    function mostrarAlerta(mensaje, tipo) {
        const alertDiv = document.getElementById('mensaje-alerta');
        alertDiv.innerHTML = `<div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                                ${mensaje}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>`;
        setTimeout(() => {
            const alert = alertDiv.querySelector('.alert');
            if (alert) alert.remove();
        }, 3000);
    }

    function cerrarModal() {
        var modal = bootstrap.Modal.getInstance(document.getElementById('profesorModal'));
        if (modal) modal.hide();
    }

    // Filtros dependientes en la cabecera (mismo que antes)
    const salaSelect = document.getElementById('select-sala');
    const seccionSelect = document.getElementById('select-seccion');
    const filtroForm = document.getElementById('filtroForm');

    function cargarSeccionesFiltro(sala, seleccionadaId) {
        if (!sala) {
            seccionSelect.innerHTML = '<option value="">Todas</option>';
            seccionSelect.disabled = true;
            return;
        }
        seccionSelect.disabled = true;
        seccionSelect.innerHTML = '<option value="">Cargando...</option>';
        fetch(`gestionar_profesores.php?ajax=secciones&sala=${encodeURIComponent(sala)}`)
            .then(res => res.json())
            .then(data => {
                let options = '<option value="">Todas</option>';
                data.forEach(sec => {
                    let selected = (seleccionadaId == sec.id) ? 'selected' : '';
                    options += `<option value="${sec.id}" ${selected}>${sec.nombre}</option>`;
                });
                seccionSelect.innerHTML = options;
                seccionSelect.disabled = false;
            })
            .catch(() => {
                seccionSelect.innerHTML = '<option value="">Error</option>';
            });
    }

    salaSelect.addEventListener('change', function() {
        const sala = this.value;
        if (sala) {
            cargarSeccionesFiltro(sala, null);
        } else {
            seccionSelect.innerHTML = '<option value="">Todas</option>';
            seccionSelect.disabled = true;
        }
        filtroForm.submit();
    });
    seccionSelect.addEventListener('change', () => filtroForm.submit());

    // Inicializar filtros si ya hay sala seleccionada
    const salaInicial = salaSelect.value;
    const seccionInicial = '<?= $seccion_filtro ?>';
    if (salaInicial) {
        cargarSeccionesFiltro(salaInicial, seccionInicial ? parseInt(seccionInicial) : null);
    } else {
        seccionSelect.disabled = true;
        seccionSelect.innerHTML = '<option value="">Todas</option>';
    }
</script>

<?php include('../includes/footer.php'); ?>