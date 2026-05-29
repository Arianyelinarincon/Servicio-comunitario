<?php
// generar_pdf.php
// Cargamos la librería que ya tienes en tu proyecto
require_once '../estadisticas/dompdf/vendor/autoload.php';
use Dompdf\Dompdf;

$dompdf = new Dompdf();

// Aquí el código para capturar lo que escribiste y convertirlo
// Por ahora, lo más fácil es usar la opción de "Imprimir" 
// que te puse en el botón de vista_previa.php
?>