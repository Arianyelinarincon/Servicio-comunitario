<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
require_once '../config/conexion.php';

// Procesar eliminación lógica
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conexion->prepare("UPDATE estudiantes SET estatus = 'Inactivo' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: listado.php?msg=deleted");
    exit();
}

// Filtros
$sala_filtro = $_GET['sala'] ?? '';
$ano_filtro = $_GET['ano'] ?? '';

// Query Principal - Solo sala, sin sección
$sql = "SELECT e.*, r.nombre_completo AS rep_nombre 
        FROM estudiantes e 
        LEFT JOIN representantes r ON e.representante_id = r.id
        WHERE e.estatus = 'Activo'";

if ($sala_filtro) {
    $sql .= " AND e.sala = '" . mysqli_real_escape_string($conexion, $sala_filtro) . "'";
}

if ($ano_filtro) {
    $sql .= " AND YEAR(e.created_at) = " . intval($ano_filtro);
}

$sql .= " ORDER BY e.sala, e.apellido, e.nombre";

$result = $conexion->query($sql);

// Obtener lista de salas para el select
$salas = $conexion->query("SELECT DISTINCT sala FROM secciones ORDER BY sala");

include '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h2 class="mt-4 mb-4">Listado de Estudiantes</h2>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Estudiante eliminado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['inscripcion']) && $_GET['inscripcion'] === 'exito'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            ¡Inscripción registrada con éxito! Ya puedes ver la ficha del estudiante.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end" id="filtroForm">
                <div class="col-md-4">
                    <label class="form-label">Sala / Grado</label>
                    <select name="sala" id="filtro_sala" class="form-select">
                        <option value="">Todas</option>
                        <?php while($row = $salas->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['sala']) ?>" <?= ($sala_filtro == $row['sala']) ? 'selected' : '' ?>><?= ucfirst(htmlspecialchars($row['sala'])) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Año Escolar</label>
                    <select name="ano" class="form-select">
                        <option value="">Todos</option>
                        <?php for($y = 2020; $y <= date('Y'); $y++): ?>
                            <option value="<?= $y ?>" <?= ($ano_filtro == $y) ? 'selected' : '' ?>><?= $y ?> - <?= $y+1 ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <button type="button" class="btn btn-secondary w-100" onclick="window.location.href='listado.php'">Limpiar</button>
                </div>
                
                <div class="col-md-3 text-end">
                    <a href="inscripcion.php" class="btn btn-primary w-100">+ Nueva Inscripción</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Buscador rápido -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Buscar:</label>
                    <input type="text" id="buscadorTabla" class="form-control" placeholder="Nombre, cédula o representante...">
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="tablaEstudiantes">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:180px">Nombre Completo</th>
                            <th style="min-width:130px">Cédula Escolar</th>
                            <th style="min-width:80px">Sala</th>
                            <th style="min-width:150px">Representante</th>
                            <th style="min-width:180px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaBody">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($e = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="nombre-col"><?= htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) ?></td>
                                <td class="cedula-col"><?= htmlspecialchars($e['cedula_escolar']) ?></td>
                                <td class="sala-col"><?= htmlspecialchars($e['sala']) ?></td>
                                <td class="representante-col"><?= htmlspecialchars($e['rep_nombre'] ?? 'No asignado') ?></td>
                                <td class="acciones-col text-nowrap">
                                    <a href="ver_ficha.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-info" target="_blank">Ver Ficha</a>
                                    <a href="editar_estudiantes.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
                                    <a href="listado.php?action=delete&id=<?= $e['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este estudiante?')">Eliminar</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No hay estudiantes registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Elementos del DOM
    const filtroSala = document.getElementById('filtro_sala');
    const filtroForm = document.getElementById('filtroForm');
    const buscador = document.getElementById('buscadorTabla');

    // Evento: Cambio en Sala
    filtroSala.addEventListener('change', function() {
        filtroForm.submit();
    });

    // ---- BUSCADOR ----
    if (buscador) {
        buscador.addEventListener('input', function() {
            let filter = this.value.toUpperCase().trim();
            let rows = document.querySelectorAll('#tablaBody tr');
            
            rows.forEach(function(row) {
                // Obtener texto de cada columna (sin sección)
                let nombre = row.querySelector('.nombre-col')?.textContent.toUpperCase() || '';
                let cedula = row.querySelector('.cedula-col')?.textContent.toUpperCase() || '';
                let sala = row.querySelector('.sala-col')?.textContent.toUpperCase() || '';
                let representante = row.querySelector('.representante-col')?.textContent.toUpperCase() || '';
                
                // Verificar si el texto buscado existe en alguna columna
                let found = nombre.includes(filter) || 
                          cedula.includes(filter) || 
                          sala.includes(filter) || 
                          representante.includes(filter);
                
                row.style.display = (filter === '' || found) ? '' : 'none';
            });
        });
    }
</script>

<?php include '../includes/footer.php'; ?>