<?php
require_once "../estadisticas/config_db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$sala_seleccionada = $_GET['sala'] ?? ''; // Mantiene 'sala' de la DB que representa el Grado
$seccion_id = $_GET['seccion'] ?? '';
$profesor_id = $_GET['profesor'] ?? '';
$periodo = $_GET['periodo'] ?? '';

if(empty($sala_seleccionada) || empty($seccion_id)) {
    die("<div class='alert alert-danger'>Error: Faltan parámetros. Genere el formulario desde el panel principal.</div>");
}

// 1. Datos de cabecera
$nombre_profesor = 'No definido';
$nombre_seccion = '';
$stmt_prof = $conexion->prepare("SELECT nombre FROM profesores WHERE id = ?");
$stmt_prof->bind_param("i", $profesor_id);
$stmt_prof->execute();
if ($row = $stmt_prof->get_result()->fetch_assoc()) $nombre_profesor = $row['nombre'];

$stmt_sec = $conexion->prepare("SELECT nombre FROM secciones WHERE id = ?");
$stmt_sec->bind_param("i", $seccion_id);
$stmt_sec->execute();
if ($row = $stmt_sec->get_result()->fetch_assoc()) $nombre_seccion = $row['nombre'];

// 2. Consulta de estudiantes (Mantiene las variables exactas de tu estructura SQL)
$query_est = "SELECT id, cedula, cedula_escolar, nombre, apellido, genero, 
                     fecha_nacimiento, lugar_nacimiento 
              FROM estudiantes 
              WHERE sala = ? AND seccion_id = ? AND estatus = 'Activo' 
              ORDER BY apellido ASC, nombre ASC";

$stmt_est = $conexion->prepare($query_est);
$stmt_est->bind_param("si", $sala_seleccionada, $seccion_id); 
$stmt_est->execute();
$result_est = $stmt_est->get_result();
$estudiantes = $result_est->fetch_all(MYSQLI_ASSOC);

include "../includes/header.php";
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
    body { background-color: #f8f9fa; }
    .hoja-rendimiento { 
        background: #fff; 
        padding: 30px; 
        margin: 20px auto; 
        max-width: 1300px; 
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .encabezado-ministerio { text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 0.9rem; }
    
    .tabla-rendimiento { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.85rem; }
    .tabla-rendimiento th, .tabla-rendimiento td { 
        border: 1px solid #000; 
        padding: 6px; 
        text-align: center; 
        vertical-align: middle;
    }
    .tabla-rendimiento th { 
        background-color: #f2f2f2; 
        text-transform: uppercase; 
        font-weight: bold;
    }
    .text-left { text-align: left !important; }
    .input-print { border: none; background: transparent; width: 100%; outline: none; text-align: center; }
    .select-print { border: none; background: transparent; outline: none; text-align: center; cursor: pointer; font-weight: bold; }
    
    /* Clases para el modo de exportación PDF */
    .modo-exportacion .input-print { border-bottom: 1px solid #fff; }
    .modo-exportacion .select-print { 
        appearance: none; 
        -webkit-appearance: none; 
        -moz-appearance: none; 
    }
</style>

<div class="container-fluid">
    <div class="hoja-rendimiento" id="documento-pdf">
        
        <div class="encabezado-ministerio">
            REPÚBLICA BOLIVARIANA DE VENEZUELA<br>
            MINISTERIO DEL PODER POPULAR PARA LA EDUCACIÓN<br>
            <h5 class="fw-bold mt-2">EVALUACIÓN DEL RENDIMIENTO ESTUDIANTIL DE EDUCACIÓN PRIMARIA</h5>
        </div>

        <div class="row mb-2" style="font-size: 0.9rem;">
            <div class="col-8">
                <strong>DOCENTE:</strong> <?= mb_strtoupper($nombre_profesor) ?> &nbsp;&nbsp;|&nbsp;&nbsp; 
                <strong>GRADO Y SECCIÓN:</strong> <?= htmlspecialchars($sala_seleccionada) ?> "<?= htmlspecialchars($nombre_seccion) ?>"
            </div>
            <div class="col-4 text-end">
                <strong>PERÍODO/AÑO ESCOLAR:</strong> <?= htmlspecialchars($periodo) ?>
            </div>
        </div>

        <form action="procesar_rendimiento.php" method="POST" id="form-rendimiento">
            <table class="tabla-rendimiento">
                <thead>
                    <tr>
                        <th rowspan="2">N°</th>
                        <th rowspan="2">C.I O CÉDULA ESCOLAR</th>
                        <th rowspan="2" class="text-left">APELLIDOS Y NOMBRES DEL ALUMNO</th>
                        <th rowspan="2">SEXO<br>(V / H)</th>
                        <th colspan="3">FECHA DE NACIMIENTO</th>
                        <th rowspan="2">EDAD</th>
                        <th rowspan="2">LUGAR DE NACIMIENTO</th>
                        <th rowspan="2">LITERAL<br>OBTIENE</th>
                        <th rowspan="2">OBSERVACIÓN</th>
                    </tr>
                    <tr>
                        <th>DÍA</th>
                        <th>MES</th>
                        <th>AÑO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($estudiantes)): ?>
                        <tr><td colspan="11" class="text-center text-danger fw-bold py-3">No se encontraron estudiantes registrados para esta sección.</td></tr>
                    <?php else: ?>
                        <?php foreach($estudiantes as $index => $est): 
                            
                            $fecha_nac_val = $est['fecha_nacimiento'] ?? null;
                            $dia = $mes = $anio = '';
                            $edad = 'N/A';

                            if ($fecha_nac_val) {
                                $fecha_nac = new DateTime($fecha_nac_val);
                                $hoy = new DateTime();
                                $edad = $hoy->diff($fecha_nac)->y;
                                $dia = $fecha_nac->format('d');
                                $mes = $fecha_nac->format('m');
                                $anio = $fecha_nac->format('Y');
                            }
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <?php 
                            $valor_a_mostrar = (!empty($est['cedula'])) ? $est['cedula'] : ($est['cedula_escolar'] ?? 'S/C');
                            ?>
                            <td><?= htmlspecialchars($valor_a_mostrar) ?></td>                            
                            <td class="text-left fw-bold"><?= mb_strtoupper($est['apellido'] . ' ' . $est['nombre']) ?></td>
                            <td><?= mb_strtoupper($est['genero'] ?? '') ?></td>
                            <td><?= $dia ?></td>
                            <td><?= $mes ?></td>
                            <td><?= $anio ?></td>
                            <td><?= $edad ?></td>
                            <td><?= mb_strtoupper($est['lugar_nacimiento'] ?? 'N/A') ?></td>
                            <td>
                                <select name="literal[<?= $est['id'] ?>]" class="form-select form-select-sm select-print text-center">
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="observacion[<?= $est['id'] ?>]" class="input-print" placeholder="...">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="text-center mt-4" id="botones-accion">
                <button type="submit" class="btn btn-dark px-5 me-2">GUARDAR DATOS</button>
                <button type="button" class="btn btn-outline-dark px-5" onclick="generarPDF()">DESCARGAR PDF</button>
            </div>
        </form>
    </div>
</div>

<script>
    function generarPDF() {
        const elemento = document.getElementById('documento-pdf');
        const botones = document.getElementById('botones-accion');
        
        botones.style.display = 'none';
        elemento.classList.add('modo-exportacion');

        const opciones = {
            margin:       0.5,
            filename:     'Rendimiento_Final_Primaria_<?= htmlspecialchars($sala_seleccionada) ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'in', format: 'legal', orientation: 'landscape' }
        };

        html2pdf().set(opciones).from(elemento).save().then(() => {
            botones.style.display = 'block';
            elemento.classList.remove('modo-exportacion');
        });
    }
</script>

<?php include "../includes/footer.php"; ?>