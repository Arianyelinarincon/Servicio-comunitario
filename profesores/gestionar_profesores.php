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
    // Validación más estricta de la sala para evitar inyecciones en el GET
    $sala = isset($_GET['sala']) ? trim($_GET['sala']) : '';
    $secciones = [];
    if (!empty($sala)) {
        // Usar consulta preparada para seguridad
        $stmt = $conexion->prepare("SELECT id, nombre FROM secciones WHERE sala = ? ORDER BY nombre");
        if ($stmt) {
            $stmt->bind_param("s", $sala);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $secciones[] = $row;
                }
            }
            $stmt->close();
        }
    }
    echo json_encode($secciones);
    exit;
}

// ==================== AJAX PARA FILTRAR TABLA EN TIEMPO REAL ====================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'tabla') {
    header('Content-Type: application/json');

    // 1. Obtener y sanitizar parámetros de entrada
    $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
    $estatus_filtro = isset($_GET['estatus']) ? trim($_GET['estatus']) : '';
    $seccion_filtro = isset($_GET['seccion']) ? trim($_GET['seccion']) : '';
    // Asegurar que pagina_actual sea un entero positivo
    $pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $registros_por_pagina = 10;
    $offset = ($pagina_actual - 1) * $registros_por_pagina;

    // 2. Construir la cláusula WHERE de manera segura y dinámica.
    //    Usaremos un array para guardar condiciones y otro para los parámetros.
    $where_conditions = ["p.rol = 'profesor'"];
    $params = [];
    $types = "";

    if (!empty($busqueda)) {
        $like_busqueda = "%$busqueda%"; // Ya está escapado por la preparación
        $where_conditions[] = "(p.nombre LIKE ? OR p.apellido LIKE ? OR p.cedula LIKE ?)";
        $params[] = $like_busqueda;
        $params[] = $like_busqueda;
        $params[] = $like_busqueda;
        $types .= "sss";
    }

    if (!empty($estatus_filtro)) {
        $where_conditions[] = "p.estatus = ?";
        $params[] = $estatus_filtro;
        $types .= "s";
    }

    if (!empty($seccion_filtro)) {
        $seccion_filtro_int = (int)$seccion_filtro;
        $where_conditions[] = "p.seccion = ?";
        $params[] = $seccion_filtro_int;
        $types .= "i";
    }

    $where_clause = " WHERE " . implode(" AND ", $where_conditions);

    // 3. Preparar la consulta de conteo TOTAL (con seguridad)
    $sql_count = "SELECT COUNT(*) as total FROM profesores p $where_clause";
    $total_registros = 0;
    if ($stmt_count = $conexion->prepare($sql_count)) {
        if (!empty($params)) {
            // bind_param requiere que los parámetros se pasen por referencia. Usamos un pequeño truco.
            $stmt_count->bind_param($types, ...$params);
        }
        if ($stmt_count->execute()) {
            $result_count = $stmt_count->get_result();
            $total_registros = $result_count->fetch_assoc()['total'];
        }
        $stmt_count->close();
    }
    $total_paginas = ($registros_por_pagina > 0) ? ceil($total_registros / $registros_por_pagina) : 1;


    // 4. Preparar la consulta de DATOS (con seguridad)
    $sql_data = "SELECT p.*, s.nombre AS nombre_seccion 
                 FROM profesores p 
                 LEFT JOIN secciones s ON p.seccion = s.id 
                 $where_clause
                 ORDER BY p.sala, s.nombre, p.nombre 
                 LIMIT ? OFFSET ?";

    // Añadir los parámetros de LIMIT y OFFSET al array de parámetros
    $params_data = $params; // Copia los parámetros del WHERE
    $params_data[] = $registros_por_pagina;
    $params_data[] = $offset;
    $types_data = $types . "ii"; // Añadir los tipos para los enteros

    $html = '';
    $result_data = false;
    if ($stmt_data = $conexion->prepare($sql_data)) {
        if (!empty($params_data)) {
            $stmt_data->bind_param($types_data, ...$params_data);
        }
        if ($stmt_data->execute()) {
            $result_data = $stmt_data->get_result();
            if ($result_data && $result_data->num_rows > 0) {
                while ($p = $result_data->fetch_assoc()) {
                    // ... (Generación de HTML, igual que antes, pero con escapado adecuado) ...
                    $badge = ($p['estatus'] == 'Activo') ? 'success' : 'secondary';
                    $nombre_sec = htmlspecialchars($p['nombre_seccion'] ?? 'N/A');
                    $url_est = urlencode($p['sala']);
                    
                    $html .= "<tr id='prof-{$p['id']}'>
                                <td>" . htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) . "</td>
                                <td>" . htmlspecialchars($p['cedula']) . "</td>
                                <td>" . htmlspecialchars($p['sala']) . "</td>
                                <td>" . $nombre_sec . "</td>
                                <td>" . htmlspecialchars($p['telefono'] ?? '') . "</td>
                                <td><span class='badge bg-{$badge}'>" . htmlspecialchars($p['estatus']) . "</span></td>
                                <td>
                                    <button class='btn btn-sm btn-primary' onclick='editarProfesor({$p['id']})'>Editar</button>
                                    <button class='btn btn-sm btn-danger' onclick='eliminarProfesor({$p['id']})'>Eliminar</button>
                                    <a href='../estudiantes/listado.php?sala={$url_est}&seccion={$p['seccion']}' class='btn btn-sm btn-info' target='_blank'>Ver Estudiantes</a>
                                </td>
                              </tr>";
                }
            } else {
                $html = "<tr><td colspan='7' class='text-center'>No hay profesores registrados con esos filtros.</td></tr>";
            }
        }
        $stmt_data->close();
    } else {
        // Error al preparar la consulta
        $html = "<tr><td colspan='7' class='text-center'>Error al cargar los datos.</td></tr>";
    }


    // 5. Generar HTML de Paginación
    $paginacion = '';
    if ($total_paginas > 1) {
        $paginacion .= '<ul class="pagination justify-content-center">';
        for ($i = 1; $i <= $total_paginas; $i++) {
            $active = ($i == $pagina_actual) ? 'active' : '';
            $paginacion .= "<li class='page-item {$active}'><a class='page-link' href='#' onclick='filtrarTabla(event, {$i})'>{$i}</a></li>";
        }
        $paginacion .= '</ul>';
    }

    echo json_encode(['html' => $html, 'paginacion' => $paginacion]);
    exit;
}

// ==================== AJAX PARA CRUD ====================
// Se recomienda implementar protección CSRF aquí.
// Ejemplo: if (!isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) { ... }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // --- Función de validación centralizada (opcional pero recomendada) ---
    function validarProfesor($datos) {
        $errores = [];
        if (empty($datos['nombre'])) $errores[] = "El nombre es obligatorio.";
        if (empty($datos['apellido'])) $errores[] = "El apellido es obligatorio.";
        if (empty($datos['cedula'])) $errores[] = "La cédula es obligatoria.";
        if (empty($datos['sala'])) $errores[] = "La sala es obligatoria.";
        if (empty($datos['seccion'])) $errores[] = "La sección es obligatoria.";
        // Validar que seccion y otros campos sean números si es necesario
        if (isset($datos['seccion']) && !is_numeric($datos['seccion'])) $errores[] = "La sección debe ser un número.";
        return $errores;
    }

    if ($action === 'agregar') {
        $nombre = strtoupper(trim($_POST['nombre']));
        $apellido = strtoupper(trim($_POST['apellido']));
        $cedula = trim($_POST['cedula']);
        $seccion = intval($_POST['seccion']);
        $sala = trim($_POST['sala']);
        $telefono = trim($_POST['telefono']);
        $direccion = trim($_POST['direccion']);

        $errores = validarProfesor($_POST);
        if (!empty($errores)) {
            echo json_encode(['ok' => false, 'msg' => 'Faltan datos obligatorios: ' . implode(' ', $errores)]);
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO profesores (nombre, apellido, cedula, seccion, sala, telefono, direccion, estatus, rol) VALUES (?, ?, ?, ?, ?, ?, ?, 'Activo', 'profesor')");
        if ($stmt) {
            $stmt->bind_param("sssisss", $nombre, $apellido, $cedula, $seccion, $sala, $telefono, $direccion);
            $ok = $stmt->execute();
            $msg = $ok ? 'Profesor agregado correctamente.' : 'Error al guardar en la base de datos.';
            $stmt->close();
        } else {
            $ok = false;
            $msg = 'Error al preparar la consulta.';
        }
        echo json_encode(['ok' => $ok, 'msg' => $msg]);
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

        $errores = validarProfesor($_POST);
        if (!empty($errores)) {
            echo json_encode(['ok' => false, 'msg' => 'Faltan datos obligatorios: ' . implode(' ', $errores)]);
            exit;
        }

        $stmt = $conexion->prepare("UPDATE profesores SET nombre=?, apellido=?, cedula=?, seccion=?, sala=?, telefono=?, direccion=?, estatus=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("sssissssi", $nombre, $apellido, $cedula, $seccion, $sala, $telefono, $direccion, $estatus, $id);
            $ok = $stmt->execute();
            $msg = $ok ? 'Profesor actualizado correctamente.' : 'Error al actualizar en la base de datos.';
            $stmt->close();
        } else {
            $ok = false;
            $msg = 'Error al preparar la consulta.';
        }
        echo json_encode(['ok' => $ok, 'msg' => $msg]);
        exit;
    }
    
    if ($action === 'eliminar') {
        $id = intval($_POST['id']);
        $stmt = $conexion->prepare("UPDATE profesores SET estatus = 'Inactivo' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $ok = $stmt->execute();
            $msg = $ok ? 'Profesor desincorporado correctamente.' : 'Error al desincorporar.';
            $stmt->close();
        } else {
            $ok = false;
            $msg = 'Error al preparar la consulta.';
        }
        echo json_encode(['ok' => $ok, 'msg' => $msg]);
        exit;
    }
    
    if ($action === 'cargar') {
        $id = intval($_POST['id']);
        $stmt = $conexion->prepare("SELECT * FROM profesores WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            echo json_encode($data ?: []);
        } else {
            echo json_encode(['error' => 'No se pudo cargar el profesor.']);
        }
        exit;
    }
}

// ==================== CARGA INICIAL DE LA PÁGINA ====================
// Esta lógica es casi idéntica a la del AJAX 'tabla' pero sin los filtros.
// La refactorización es recomendable: se podría mover a una función.
$registros_por_pagina = 10;
$pagina_actual = 1;
$offset = 0;
$sql_initial = "SELECT p.*, s.nombre AS nombre_seccion 
                FROM profesores p 
                LEFT JOIN secciones s ON p.seccion = s.id 
                WHERE p.rol = 'profesor' 
                ORDER BY p.sala, s.nombre, p.nombre 
                LIMIT ? OFFSET ?";
$result_initial = false;
if ($stmt_initial = $conexion->prepare($sql_initial)) {
    $stmt_initial->bind_param("ii", $registros_por_pagina, $offset);
    if ($stmt_initial->execute()) {
        $result_initial = $stmt_initial->get_result();
    }
    $stmt_initial->close();
}

// Cálculo del total de páginas para la paginación inicial
$sql_count_initial = "SELECT COUNT(*) as t FROM profesores WHERE rol='profesor'";
$total_initial = 0;
if ($res_count = $conexion->query($sql_count_initial)) {
    $total_initial = $res_count->fetch_assoc()['t'];
}
$total_paginas_initial = ceil($total_initial / $registros_por_pagina);

// Cargar lista de secciones para el filtro
$secciones_lista = $conexion->query("SELECT id, nombre, sala FROM secciones ORDER BY sala, nombre");
$estatus_opciones = ['Activo', 'Inactivo'];

include('../includes/header.php');
?>

<div class="container-fluid px-4">
    <h2 class="mt-4 mb-4">Gestión de Profesores</h2>
    <div id="mensaje"></div>
    
    <!-- Filtros Dinámicos -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-end" id="filtroForm" onsubmit="event.preventDefault();">
                <div class="col-md-5">
                    <label class="form-label"><i class="fas fa-search"></i> Buscar (nombre, apellido o cédula)</label>
                    <input type="text" id="inputBusqueda" class="form-control" placeholder="Escribe para buscar...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estatus</label>
                    <select id="selectEstatus" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach($estatus_opciones as $est): ?>
                            <option value="<?= $est ?>"><?= $est ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sección</label>
                    <select id="selectSeccion" class="form-select">
                        <option value="">Todas</option>
                        <?php while($sec = $secciones_lista->fetch_assoc()): ?>
                            <option value="<?= $sec['id'] ?>">
                                <?= htmlspecialchars($sec['sala'] . ' - Sección ' . $sec['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-12 text-end mt-3">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="limpiarFiltros()">Limpiar filtros</button>
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
                    <tbody id="tablaCuerpo">
                        <?php if ($result_initial && $result_initial->num_rows > 0): ?>
                            <?php while($p = $result_initial->fetch_assoc()): ?>
                            <tr id="prof-<?= $p['id'] ?>">
                                <td><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></td>
                                <td><?= htmlspecialchars($p['cedula']) ?></td>
                                <td><?= htmlspecialchars($p['sala']) ?></td>
                                <td><?= htmlspecialchars($p['nombre_seccion'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($p['telefono'] ?? '') ?></td>
                                <td><span class="badge bg-<?= ($p['estatus'] == 'Activo') ? 'success' : 'secondary' ?>"><?= htmlspecialchars($p['estatus']) ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editarProfesor(<?= $p['id'] ?>)">Editar</button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarProfesor(<?= $p['id'] ?>)">Eliminar</button>
                                    <a href="../estudiantes/listado.php?sala=<?= urlencode($p['sala']) ?>&seccion=<?= $p['seccion'] ?>" class="btn btn-sm btn-info" target="_blank">Ver Estudiantes</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No hay profesores registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Contenedor de Paginación Inicial -->
            <nav class="mt-3" id="paginacionContainer">
                <?php if ($total_paginas_initial > 1): ?>
                    <ul class="pagination justify-content-center">
                        <?php for($i = 1; $i <= $total_paginas_initial; $i++): ?>
                            <li class="page-item <?= ($i == 1) ? 'active' : '' ?>">
                                <a class="page-link" href="#" onclick="filtrarTabla(event, <?= $i ?>)"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                <?php endif; ?>
            </nav>
            
        </div>
    </div>
</div>

<!-- Modal para Agregar/Editar Profesor (Sin cambios estructurales) -->
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
// ==================== LÓGICA DE BÚSQUEDA DINÁMICA ====================
let timeoutBusqueda;

function filtrarTabla(event = null, pagina = 1) {
    if(event) event.preventDefault();
    
    let busqueda = document.getElementById('inputBusqueda').value;
    let estatus = document.getElementById('selectEstatus').value;
    let seccion = document.getElementById('selectSeccion').value;

    // (Opcional) Mostrar indicador de carga
    document.getElementById('tablaCuerpo').innerHTML = '<tr><td colspan="7" class="text-center">Cargando...</td></tr>';

    fetch(`gestionar_profesores.php?ajax=tabla&busqueda=${encodeURIComponent(busqueda)}&estatus=${encodeURIComponent(estatus)}&seccion=${encodeURIComponent(seccion)}&pagina=${pagina}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('tablaCuerpo').innerHTML = data.html;
            document.getElementById('paginacionContainer').innerHTML = data.paginacion;
        })
        .catch(error => {
            console.error("Error cargando tabla:", error);
            document.getElementById('tablaCuerpo').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error al cargar los datos.</td></tr>';
        });
}

// Event listeners para filtros con debounce
document.getElementById('inputBusqueda').addEventListener('keyup', function() {
    clearTimeout(timeoutBusqueda);
    timeoutBusqueda = setTimeout(() => { filtrarTabla(null, 1); }, 300);
});

document.getElementById('selectEstatus').addEventListener('change', () => filtrarTabla(null, 1));
document.getElementById('selectSeccion').addEventListener('change', () => filtrarTabla(null, 1));

function limpiarFiltros() {
    document.getElementById('inputBusqueda').value = '';
    document.getElementById('selectEstatus').value = '';
    document.getElementById('selectSeccion').value = '';
    filtrarTabla(null, 1);
}

// ==================== LÓGICA DEL CRUD ====================

// Cargar secciones según sala en el modal
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
            // Poblar el formulario
            document.getElementById('profesor_id').value = data.id;
            document.getElementById('nombre').value = data.nombre;
            document.getElementById('apellido').value = data.apellido;
            document.getElementById('cedula').value = data.cedula;
            document.getElementById('telefono').value = data.telefono;
            document.getElementById('direccion').value = data.direccion;
            document.getElementById('sala').value = data.sala;
            
            // Cargar secciones y seleccionar la correcta
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
        } else {
            mostrarMensaje('Error al cargar los datos del profesor.', 'danger');
        }
    })
    .catch(error => {
        console.error('Error al cargar profesor:', error);
        mostrarMensaje('Error de comunicación con el servidor.', 'danger');
    });
}

function eliminarProfesor(id) {
    if (!confirm('¿Estás seguro de que deseas desincorporar (dar de baja) a este profesor?')) return;
    fetch('gestionar_profesores.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `action=eliminar&id=${id}`
    })
    .then(response => response.json())
    .then(res => {
        mostrarMensaje(res.msg, res.ok ? 'success' : 'danger');
        if (res.ok) filtrarTabla(); // Recarga la tabla
    })
    .catch(error => {
        console.error('Error al eliminar:', error);
        mostrarMensaje('Error de comunicación con el servidor.', 'danger');
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
            filtrarTabla(); // Recarga la tabla
        }
    })
    .catch(error => {
        console.error('Error al guardar:', error);
        mostrarMensaje('Error de comunicación con el servidor.', 'danger');
    });
});

function mostrarMensaje(msg, tipo) {
    let div = document.getElementById('mensaje');
    div.innerHTML = `<div class="alert alert-${tipo} alert-dismissible fade show">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
    setTimeout(() => div.innerHTML = '', 3000);
}
</script>

<?php include('../includes/footer.php'); ?>