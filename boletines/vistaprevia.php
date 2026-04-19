<?php
session_start();
include_once "../auth.php"; 
include_once "../config/conexion.php"; 
include_once "../includes/header.php"; 

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger m-4'>Error: No se seleccionó ningún boletín.</div>";
    include_once "../../includes/footer.php";
    exit;
}

$id_boletin = $_GET['id'];

$sql = "SELECT b.*, e.nombres, e.apellidos, e.cedula_escolar, e.grado, e.seccion 
        FROM boletines b 
        INNER JOIN estudiantes e ON b.id_estudiante = e.id_estudiante 
        WHERE b.id_boletin = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_boletin);
$stmt->execute();
$resultado = $stmt->get_result();
$boletin = $resultado->fetch_assoc();

if (!$boletin) {
    echo "<div class='alert alert-danger m-4'>Boletín no encontrado.</div>";
    include_once "../../includes/footer.php";
    exit;
}
?>

<style>
    .hoja-boletin {
        background: white;
        width: 210mm;
        min-height: 297mm;
        margin: 20px auto;
        padding: 40px;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
        border: 1px solid #ccc;
        font-family: "Times New Roman", serif;
        color: #000;
    }
    .hoja-boletin h3, .hoja-boletin h4 { text-align: center; font-weight: bold; }
    .hoja-boletin h4 { font-size: 16px; margin-bottom: 5px; }
    .seccion-datos { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; margin-top: 20px;}
    .etiqueta { font-weight: bold; }
    .texto-justificado { text-align: justify; }
</style>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between mb-3 align-items-center">
        <h2 style="color: #003366;">VISTA PREVIA DEL BOLETÍN</h2>
        <div>
            <a href="cargar.php" class="btn btn-secondary">Cargar Otro</a>
            <a href="imprimir.php?id=<?php echo $id_boletin; ?>" target="_blank" class="btn btn-success">
                <i class="fas fa-print"></i> Generar PDF Oficial
            </a>
        </div>
    </div>

    <div class="hoja-boletin">
        <div class="text-center mb-4">
            <h4>REPÚBLICA BOLIVARIANA DE VENEZUELA</h4>
            <h4>MINISTERIO DEL PODER POPULAR PARA LA EDUCACIÓN</h4>
            <h4>BOLETÍN INFORMATIVO DE RENDIMIENTO ESTUDIANTIL</h4>
        </div>

        <div class="seccion-datos">
            <p><span class="etiqueta">Estudiante:</span> <?php echo $boletin['nombres'] . " " . $boletin['apellidos']; ?></p>
            <p><span class="etiqueta">Cédula Escolar:</span> <?php echo $boletin['cedula_escolar']; ?></p>
            <p><span class="etiqueta">Grado y Sección:</span> <?php echo $boletin['grado'] . " '" . $boletin['seccion'] . "'"; ?></p>
        </div>

        <div class="mb-4 texto-justificado">
            <h5 class="etiqueta">Proyecto de Aprendizaje:</h5>
            <p><?php echo nl2br($boletin['proyecto_aprendizaje']); ?></p>
        </div>

        <div class="mb-4 texto-justificado">
            <h5 class="etiqueta">Descripción Pedagógica:</h5>
            <p><?php echo nl2br($boletin['descripcion_pedagogica']); ?></p>
        </div>

        <div class="mt-5 text-center">
            <h3 style="text-decoration: underline;">LITERAL ASIGNADO: <?php echo $boletin['literal']; ?></h3>
        </div>
    </div>
</div>

<?php include_once "../../includes/footer.php"; ?>