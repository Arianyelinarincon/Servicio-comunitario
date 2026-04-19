<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Capturamos los datos que escribiste en el formulario
$datos = $_POST;

// Configuramos Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// 1. Iniciamos la captura de pantalla interna de PHP
ob_start();

// 2. Cargamos el archivo con el diseño (que crearemos en el paso 2)
include 'plantilla_pdf.php'; 

// 3. Guardamos todo el diseño en la variable $html y limpiamos la memoria
$html = ob_get_clean();

// 4. Se lo pasamos a Dompdf
$dompdf->loadHtml($html);

// 5. Configuramos tamaño carta y horizontal
$dompdf->setPaper('letter', 'landscape');

// 6. Generamos y descargamos
$dompdf->render();
$dompdf->stream("Resumen_Estadistico.pdf", array("Attachment" => true));
?>