<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: listado.php");
    exit();
}

$stmt = $conexion->prepare("SELECT CONCAT(nombre, ' ', apellido) AS nombre_completo FROM estudiantes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$estudiante = $result->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    header("Location: listado.php");
    exit();
}

include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-success text-white text-center py-4">
                    <h2 class="mb-0">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </h2>
                    <h3 class="mb-0 mt-2">¡Inscripción Exitosa!</h3>
                </div>
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-user-graduate text-success" style="font-size: 80px;"></i>
                    </div>
                    <h4 class="mb-3">
                        Se ha inscrito al alumno <strong><?= htmlspecialchars($estudiante['nombre_completo']) ?></strong> satisfactoriamente.
                    </h4>
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i> Todos los datos han sido registrados correctamente en el sistema.
                    </div>
                    
                    <div class="row mt-5">
                        <div class="col-md-6 mb-3">
                            <a href="ver_ficha.php?id=<?= $id ?>" class="btn btn-primary btn-lg w-100" target="_blank">
                                <i class="fas fa-id-card"></i> Ver Ficha del Estudiante
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="inscripcion.php" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-user-plus"></i> Agregar Otro Estudiante
                            </a>
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-md-6 mb-3">
                            <a href="listado.php" class="btn btn-secondary btn-lg w-100">
                                <i class="fas fa-list"></i> Ver Listado de Estudiantes
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="index.php" class="btn btn-outline-secondary btn-lg w-100">
                                <i class="fas fa-arrow-left"></i> Volver a Gestión de Estudiantes
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