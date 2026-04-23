<?php
// 1. CONFIGURACIÓN DE RUTAS
// Usamos __DIR__ para asegurar que partimos de la carpeta 'estadisticas'
$directorio_actual = __DIR__;
$ruta_libreria = $directorio_actual . DIRECTORY_SEPARATOR . 'dompdf' . DIRECTORY_SEPARATOR . 'autoload.inc.php';

// 2. VERIFICACIÓN DE SEGURIDAD
if (!file_exists($ruta_libreria)) {
    echo "<h3>Error de Ruta</h3>";
    echo "PHP buscó aquí: <b>" . $ruta_libreria . "</b><br>";
    echo "Contenido de la carpeta actual: <pre>";
    print_r(scandir($directorio_actual)); // Esto nos dirá qué carpetas ve PHP realmente
    echo "</pre>";
    die("Deteniendo ejecución: No se encontró el cargador de Dompdf.");
}

require_once $ruta_libreria;

use Dompdf\Dompdf;
use Dompdf\Options;

// 3. CAPTURA Y TRADUCCIÓN DE DATOS (Para que coincidan con plantilla_pdf.php)
$post = $_POST;
$datos = [
    'docente'             => $post['nombre_docente'] ?? 'No definido',
    'grado'               => $post['sala'] ?? '',
    'mes'                 => $post['periodo'] ?? '',
    'dias_habiles'        => $post['dias_habiles'] ?? 0,
    'matricula_v'         => $post['mat_v'] ?? 0,
    'matricula_h'         => $post['mat_h'] ?? 0,
    'resumen_v_1'         => $post['resumen_v_1'] ?? 0,
    'resumen_h_1'         => $post['resumen_h_1'] ?? 0,
    'resumen_total_1'     => $post['total_general'] ?? 0,
    'promedio_asistencia' => $post['porcentaje_total'] ?? '0%',
    'asistencia_v'        => $post['asist_v'] ?? [],
    'asistencia_h'        => $post['asist_h'] ?? [],
    'observaciones'       => $post['observaciones'] ?? ''
];

// 4. INICIALIZAR DOMPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('chroot', $directorio_actual); 

$dompdf = new Dompdf($options);

// 5. CARGAR EL HTML
ob_start();
include 'plantilla_pdf.php'; 
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'landscape');
$dompdf->render();

// 6. DESCARGAR
$dompdf->stream("Asistencia_Mensual.pdf", array("Attachment" => false));