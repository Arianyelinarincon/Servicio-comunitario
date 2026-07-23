<?php
session_start();
require_once '../config/conexion.php';
require_once '../config/configuracion.php';

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
$completa = isset($_GET['completa']) ? intval($_GET['completa']) : 1;

if (!$id) {
    header("Location: listado.php");
    exit();
}

// Obtener datos del estudiante
$stmt = $conexion->prepare("
    SELECT e.*, 
           r.nombre_completo AS rep_nombre,
           s.nombre AS seccion_nombre
    FROM estudiantes e 
    LEFT JOIN representantes r ON e.representante_id = r.id
    LEFT JOIN secciones s ON e.seccion_id = s.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    header("Location: listado.php");
    exit();
}

$nombre_completo = $estudiante['nombre'] . ' ' . $estudiante['apellido'];
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
$sala_nombre = $mapa_salas[$estudiante['sala']] ?? $estudiante['sala'];

// ========== SI LA INSCRIPCIÓN NO ESTÁ COMPLETA, OBTENER FALTANTES ==========
$faltantes = [];
if ($completa == 0) {
    $estado = verificarFichaCompleta($id, $conexion);
    $faltantes = $estado['faltantes'] ?? [];
}

include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header <?= $completa ? 'bg-success' : 'bg-warning' ?> text-white text-center py-4">
                    <?php if ($completa): ?>
                        <h2 class="mb-0"><i class="fas fa-check-circle fa-2x"></i></h2>
                        <h3 class="mb-0 mt-2">¡FICHA COMPLETA!</h3>
                        <h5 class="mb-0 mt-1"><span class="badge bg-light text-success">✔️ Inscripción Completada</span></h5>
                    <?php else: ?>
                        <h2 class="mb-0"><i class="fas fa-exclamation-triangle fa-2x"></i></h2>
                        <h3 class="mb-0 mt-2">Inscripción Registrada</h3>
                        <h5 class="mb-0 mt-1"><span class="badge bg-light text-warning">⚠️ Ficha Incompleta</span></h5>
                    <?php endif; ?>
                </div>
                <div class="card-body text-center py-4">
                    <div class="mb-4">
                        <i class="fas fa-user-graduate text-<?= $completa ? 'success' : 'warning' ?>" style="font-size: 80px;"></i>
                    </div>
                    
                    <h4 class="mb-3">
                        Se ha inscrito al alumno <strong><?= htmlspecialchars($nombre_completo) ?></strong> 
                        en <strong><?= htmlspecialchars($sala_nombre) ?></strong>
                        <?php if (!empty($estudiante['seccion_nombre'])): ?>
                            - Sección <strong><?= htmlspecialchars($estudiante['seccion_nombre']) ?></strong>
                        <?php endif; ?>
                    </h4>

                    <?php if ($completa): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> 
                            <strong>¡Todos los datos están completos!</strong> La ficha del estudiante está al 100%.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>La ficha no está completamente llena.</strong><br>
                            Faltan los siguientes campos por completar:
                            <ul class="text-start mt-2 mb-0" style="display: inline-block; text-align: left;">
                                <?php foreach ($faltantes as $campo): ?>
                                    <li><?= htmlspecialchars($campo) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <br><br>
                            <span class="text-muted small">
                                <i class="fas fa-edit me-1"></i> 
                                Puede completar estos datos editando la ficha del estudiante.
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <a href="ver_ficha.php?id=<?= $id ?>" class="btn btn-primary btn-lg w-100" target="_blank">
                                <i class="fas fa-id-card me-2"></i> Ver Ficha del Estudiante
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="inscripcion.php" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-user-plus me-2"></i> Inscribir Otro Estudiante
                            </a>
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-md-6 mb-3">
                            <a href="listado.php" class="btn btn-secondary btn-lg w-100">
                                <i class="fas fa-list me-2"></i> Ver Listado de Estudiantes
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="editar_estudiantes.php?id=<?= $id ?>" class="btn btn-warning btn-lg w-100">
                                <i class="fas fa-edit me-2"></i> Editar Ficha
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted text-center">
                    <small>U.E.B.N. Juan Pablo Pérez Alfonzo - Sistema de Gestión Educativa</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>