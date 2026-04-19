<?php
session_start();

include_once "../auth.php"; 
include_once "../config/conexion.php"; 
include_once "../includes/header.php"; 

$grado_docente = $_SESSION['grado'] ?? '1'; 
$seccion_docente = $_SESSION['seccion'] ?? 'A';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_estudiante = $_POST['id_estudiante'];
    $proyecto = $_POST['proyecto_aprendizaje'];
    $descripcion = $_POST['descripcion_pedagogica'];
    $literal = $_POST['literal'];

    $sql = "INSERT INTO boletines (id_estudiante, proyecto_aprendizaje, descripcion_pedagogica, literal) VALUES (?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("isss", $id_estudiante, $proyecto, $descripcion, $literal);

    if ($stmt->execute()) {
        $id_nuevo = $conexion->insert_id;
        echo "<script>window.location.href = 'vistaprevia.php?id=" . $id_nuevo . "';</script>";
        exit;
    } else {
        $mensaje = "<div class='alert alert-danger'>Error al guardar: " . $conexion->error . "</div>";
    }
}
?>

<div class="container-fluid mt-4">
    <h2 class="mb-4" style="color: #003366;">MÓDULO DE BOLETINES - CARGA DE NOTAS</h2>

    <?php if(isset($mensaje)) echo $mensaje; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0" style="color: #003366; font-weight: bold;">EVALUACIÓN CUALITATIVA</h5>
        </div>
        
        <div class="card-body bg-light">
            <form action="cargar.php" method="POST">
                <div class="row">
                    
                    <div class="col-md-12 mb-4">
                        <label for="id_estudiante" class="form-label fw-bold">1. Seleccionar Estudiante:</label>
                        <select class="form-select" id="id_estudiante" name="id_estudiante" required>
                            <option value="">-- Seleccione un alumno de su grado --</option>
                            <?php
                            $query = "SELECT id_estudiante, nombres, apellidos, cedula_escolar 
                                      FROM estudiantes 
                                      WHERE grado = '$grado_docente' AND seccion = '$seccion_docente'";
                            $resultado = $conexion->query($query);

                            if ($resultado && $resultado->num_rows > 0) {
                                while ($row = $resultado->fetch_assoc()) {
                                    echo "<option value='".$row['id_estudiante']."'>".$row['apellidos'].", ".$row['nombres']." (C.E: ".$row['cedula_escolar'].")</option>";
                                }
                            } else {
                                echo "<option value=''>No hay estudiantes registrados en su grado</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label for="proyecto_aprendizaje" class="form-label fw-bold">2. Proyecto de Aprendizaje:</label>
                        <textarea class="form-control" id="proyecto_aprendizaje" name="proyecto_aprendizaje" rows="5" required placeholder="Describa el proyecto trabajado..."></textarea>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label for="descripcion_pedagogica" class="form-label fw-bold">3. Descripción Pedagógica:</label>
                        <textarea class="form-control" id="descripcion_pedagogica" name="descripcion_pedagogica" rows="5" required placeholder="Redacte el desempeño del estudiante..."></textarea>
                    </div>

                    <div class="col-md-12 mb-4">
                        <label for="literal" class="form-label fw-bold">4. Calificación Final (Literal):</label>
                        <select class="form-select w-25" id="literal" name="literal" required>
                            <option value="">Seleccione un literal...</option>
                            <option value="A">A - Avanzado</option>
                            <option value="B">B - Bueno</option>
                            <option value="C">C - En Proceso</option>
                            <option value="D">D - Deficiente</option>
                            <option value="E">E - Requiere Apoyo</option>
                        </select>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-secondary">Limpiar Campos</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar Boletín
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once "../../includes/footer.php"; ?>