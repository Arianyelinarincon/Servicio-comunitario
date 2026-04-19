<?php
session_start();

include_once "../config/conexion.php"; 

require('../fpdf/fpdf.php');
define('FPDF_FONTPATH', '../../fpdf/font/');

if (!isset($_GET['id'])) {
    die("Error: No se indicó el boletín a imprimir.");
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
    die("Error: Boletín no encontrado.");
}

$pdf = new FPDF('P', 'mm', 'A4'); 
$pdf->AddPage();
$pdf->SetMargins(20, 20, 20); // Márgenes de seguridad
$pdf->SetAutoPageBreak(true, 20);

$nombre_completo = utf8_decode($boletin['nombres'] . " " . $boletin['apellidos']);
$cedula = utf8_decode($boletin['cedula_escolar']);
$grado_seccion = utf8_decode($boletin['grado'] . " '" . $boletin['seccion'] . "'");
$proyecto = utf8_decode($boletin['proyecto_aprendizaje']);
$descripcion = utf8_decode($boletin['descripcion_pedagogica']);
$literal = utf8_decode($boletin['literal']);


$pdf->SetFont('Arial', '', 12);

$pdf->SetXY(40, 60); 
$pdf->Cell(100, 10, $nombre_completo, 0, 0, 'L');

$pdf->SetXY(150, 60); 
$pdf->Cell(50, 10, $cedula, 0, 0, 'L');

$pdf->SetXY(40, 70); 
$pdf->Cell(50, 10, $grado_seccion, 0, 0, 'L');

$pdf->SetFont('Arial', '', 11);

$pdf->SetXY(20, 100); 
$pdf->MultiCell(170, 6, $proyecto, 0, 'J');

$pdf->SetXY(20, 150); 
$pdf->MultiCell(170, 6, $descripcion, 0, 'J');

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(90, 240); 
$pdf->Cell(30, 10, "LITERAL: " . $literal, 0, 0, 'C');

$pdf->Output('I', 'Boletin_' . $boletin['cedula_escolar'] . '.pdf');
?>