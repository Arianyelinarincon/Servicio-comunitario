<?php
// 1. Incluir archivos de configuración y lógica de datos
include_once '../config/conexion.php';
include_once 'gestion.php';

// Variables iniciales para el formulario (Vacías por defecto para Nueva Inscripción)
$modo_edicion = false;
$estudiante = [
    'id' => '',
    'nombre' => '',
    'cedula_escolar' => '',
    'fecha_nacimiento' => '',
    'genero' => '',
    'sala' => '',
    'alergias_condiciones' => '',
    'rep_nombre' => '',
    'rep_cedula' => '',
    'rep_telefono' => ''
];

// 2. DETECTAR MODO EDICIÓN: Si viene un ID por la URL, precargamos los datos
if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id'])) {
    $id_editar = $_GET['id'];
    
    $datos_alumno = obtenerEstudiantePorId($conexion, $id_editar);
    
    if ($datos_alumno) {
        $modo_edicion = true;
        $estudiante = $datos_alumno;
    }
}

// 3. Obtener la lista de estudiantes registrados para la tabla inferior
$lista_estudiantes = obtenerListaEstudiantes($conexion);

// 4. Incluir el encabezado visual global del sistema
include_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h2 class="mt-4 mb-4 fw-bold text-dark">
        <?php echo $modo_edicion ? 'MÓDULO DE ESTUDIANTES - EDITAR REGISTRO' : 'MÓDULO DE ESTUDIANTES - NUEVA INSCRIPCIÓN'; ?>
    </h2>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'registrado_exito'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                ¡Estudiante inscrito exitosamente en el sistema!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'actualizado_exito'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                ¡Los datos del estudiante y representante se actualizaron correctamente!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'eliminado_exito'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                El estudiante ha sido desincorporado del listado activo.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'error'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                Hubo un inconveniente al procesar la solicitud. Verifique los campos.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form action="proceso.php" method="POST" class="row g-3">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($estudiante['id']); ?>">
        
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white text-center fw-bold py-3 text-secondary text-uppercase border-bottom">
                    Datos del Estudiante
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Nombre Completo</label>
                        <input type="text" name="nombre" class="form-control text-uppercase" required 
                               placeholder="Ej. JUANITO ALCADE" 
                               value="<?php echo htmlspecialchars($estudiante['nombre']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Cédula Escolar / ID</label>
                        <input type="text" name="cedula_escolar" class="form-control" required
                               value="<?php echo htmlspecialchars($estudiante['cedula_escolar']); ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control" required
                                   value="<?php echo htmlspecialchars($estudiante['fecha_nacimiento']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary">Género</label>
                            <select name="genero" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="V" <?php echo $estudiante['genero'] === 'V' ? 'selected' : ''; ?>>Varón</option>
                                <option value="H" <?php echo $estudiante['genero'] === 'H' ? 'selected' : ''; ?>>Hembra</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Sala Asignada</label>
                        <input type="text" name="sala" class="form-control" placeholder="Sala 4" required
                               value="<?php echo htmlspecialchars($estudiante['sala']); ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white text-center fw-bold py-3 text-secondary text-uppercase border-bottom">
                    Datos del Representante
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Nombre Completo</label>
                        <input type="text" name="rep_nombre" class="form-control text-uppercase" required
                               value="<?php echo htmlspecialchars($estudiante['rep_nombre']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Cédula / ID</label>
                        <input type="text" name="rep_cedula" class="form-control" required
                               value="<?php echo htmlspecialchars($estudiante['rep_cedula']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Teléfono</label>
                        <input type="text" name="rep_telefono" class="form-control" required
                               value="<?php echo htmlspecialchars($estudiante['rep_telefono']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Alergias o condiciones del Estudiante</label>
                        <textarea name="alergias" class="form-control" rows="2" placeholder="Describa si posee alguna condición..."><?php echo htmlspecialchars($estudiante['alergias_condiciones']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 text-center mt-4 mb-4">
            <?php if ($modo_edicion): ?>
                <button type="submit" name="accion" value="actualizar" class="btn btn-primary btn-lg px-5 me-2 shadow-sm">GUARDAR CAMBIOS</button>
                <a href="index.php" class="btn btn-secondary btn-lg px-4 shadow-sm">Cancelar Edición</a>
            <?php else: ?>
                <button type="submit" name="accion" value="registrar" class="btn btn-success btn-lg px-5 me-2 shadow-sm">REGISTRAR ESTUDIANTE</button>
                <a href="index.php" class="btn btn-secondary btn-lg px-4 shadow-sm">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>

    <hr class="my-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold text-dark mb-0">LISTADO DE ESTUDIANTES</h3>
        <?php if ($modo_edicion): ?>
            <a href="index.php" class="btn btn-success btn-sm shadow-sm">+ Nuevo Registro</a>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mb-5 border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 border">
                <thead class="table-light text-secondary">
                    <tr>
                        <th class="ps-3" style="width: 8%;">ID</th>
                        <th style="width: 15%;">Cédula</th>
                        <th style="width: 30%;">Nombre</th>
                        <th style="width: 10%;">Género</th>
                        <th style="width: 12%;">Sala</th>
                        <th class="text-center" style="width: 25%;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lista_estudiantes)): ?>
                        <?php foreach ($lista_estudiantes as $alumno): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-secondary"><?php echo htmlspecialchars($alumno['id']); ?></td>
                                <td><?php echo htmlspecialchars($alumno['cedula_escolar'] ?: ($alumno['cedula'] ?: 'S/C')); ?></td>
                                <td class="text-uppercase fw-semibold text-dark">
                                    <?php echo htmlspecialchars($alumno['nombre'] . ' ' . ($alumno['apellido'] ?? '')); ?>
                                </td>
                                <td><?php echo htmlspecialchars($alumno['genero']); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($alumno['sala']); ?></span></td>
                                <td class="text-center">
                                    <a href="../generar_pdf.php?id=<?php echo $alumno['id']; ?>" target="_blank" class="btn btn-info btn-sm text-white px-2 shadow-sm">Ver Ficha</a>
                                    <a href="index.php?accion=editar&id=<?php echo $alumno['id']; ?>" class="btn btn-primary btn-sm px-2 shadow-sm">Editar</a>
                                    <a href="proceso.php?accion=eliminar&id=<?php echo $alumno['id']; ?>" 
                                       class="btn btn-danger btn-sm px-2 shadow-sm" 
                                       onclick="return confirm('¿Está seguro de que desea desincorporar a este estudiante?');">
                                        Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No existen registros de estudiantes activos en este momento.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>