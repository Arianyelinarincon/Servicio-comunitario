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
    // --- Datos de Encabezado ---
    'periodo_inicio'      => $post['periodo_inicio'] ?? '',
    'periodo_fin'         => $post['periodo_fin'] ?? '',
    'docente'             => $post['docente'] ?? '',
    'grado'               => $post['grado'] ?? '',
    'seccion'             => $post['seccion'] ?? '',
    'turno'               => $post['turno'] ?? '',
    'dias_habiles'        => $post['dias_habiles'] ?? '',
    'promedio_asistencia' => $post['promedio_asistencia'] ?? '',
    'mes'                 => $post['mes'] ?? '',
    'matricula_v'         => $post['matricula_v'] ?? '',
    'matricula_h'         => $post['matricula_h'] ?? '',
    'matricula_total'     => $post['matricula_total'] ?? '',

    // --- Asistencia y Totales ---
    'dias_total'          => $post['dias_total'] ?? '',
    'dias_porcentaje'     => $post['dias_porcentaje'] ?? '',
    'asistencia_v'        => $post['asistencia_v'] ?? [],
    'asistencia_h'        => $post['asistencia_h'] ?? [],
    'asistencia_total'    => $post['asistencia_total'] ?? [],

    // --- Clasificación por Edades (Arreglos) ---
    'venezolano_v'        => $post['venezolano_v'] ?? [],
    'venezolano_h'        => $post['venezolano_h'] ?? [],
    'venezolano_total'    => $post['venezolano_total'] ?? [],
    'extranjero_v'        => $post['extranjero_v'] ?? [],
    'extranjero_h'        => $post['extranjero_h'] ?? [],
    'extranjero_total'    => $post['extranjero_total'] ?? [],
    
    // --- Ingresos y Egresos (Arreglos) ---
    'ingreso_apellido'    => $post['ingreso_apellido'] ?? [],
    'ingreso_v'           => $post['ingreso_v'] ?? [],
    'ingreso_h'           => $post['ingreso_h'] ?? [],
    'ingreso_ci'          => $post['ingreso_ci'] ?? [],
    'ingreso_fn'          => $post['ingreso_fn'] ?? [],
    'ingreso_fi'          => $post['ingreso_fi'] ?? [],
    
    'egreso_apellido'     => $post['egreso_apellido'] ?? [],
    'egreso_v'            => $post['egreso_v'] ?? [],
    'egreso_h'            => $post['egreso_h'] ?? [],
    'egreso_ci'           => $post['egreso_ci'] ?? [],
    'egreso_fn'           => $post['egreso_fn'] ?? [],
    'egreso_fi'           => $post['egreso_fi'] ?? [],

    // --- Resumen General y Observaciones ---
    'resumen_v_1'         => $post['resumen_v_1'] ?? '',
    'resumen_v_2'         => $post['resumen_v_2'] ?? '',
    'resumen_h_1'         => $post['resumen_h_1'] ?? '',
    'resumen_h_2'         => $post['resumen_h_2'] ?? '',
    'resumen_total_1'     => $post['resumen_total_1'] ?? '',
    'resumen_total_2'     => $post['resumen_total_2'] ?? '',
    'observaciones'       => $post['observaciones'] ?? ''
];

// 4. INICIALIZAR DOMPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('chroot', $directorio_actual); 

$dompdf = new Dompdf($options);

// 5. GENERAR HTML DESDE LA PLANTILLA
ob_start();

// CORRECCIÓN: Capturamos el tipo antes de usarlo
$tipo = $_POST['tipo_reporte'] ?? 'regular'; 

// Definimos el rango aquí para que la plantilla lo use
$rango_edades = ($tipo == 'inicial') ? range(4, 6) : range(7, 15);

// Incluimos la plantilla base
include 'plantilla_base.php'; 

$html = ob_get_clean();
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'landscape');
$dompdf->render();

// 6. DESCARGAR
$dompdf->stream("Asistencia_Mensual.pdf", array("Attachment" => false));