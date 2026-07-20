<?php
// config/configuracion.php
require_once __DIR__ . '/conexion.php';

function obtenerConfiguracion($clave) {
    global $conexion;
    $stmt = $conexion->prepare("SELECT valor FROM configuracion WHERE clave = ?");
    $stmt->bind_param("s", $clave);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['valor'];
    }
    $stmt->close();
    return null;
}

function obtenerPeriodoEscolar() {
    $periodo = obtenerConfiguracion('periodo_escolar');
    if (!$periodo) {
        global $conexion;
        // Calcular automáticamente basado en el mes actual
        $mes = date('n');
        $anio = date('Y');
        if ($mes >= 8) { // Agosto a Diciembre
            $periodo_default = $anio . '-' . ($anio + 1);
        } else { // Enero a Julio
            $periodo_default = ($anio - 1) . '-' . $anio;
        }
        $stmt = $conexion->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES ('periodo_escolar', ?, 'Periodo escolar actual') ON DUPLICATE KEY UPDATE valor = ?");
        $stmt->bind_param("ss", $periodo_default, $periodo_default);
        $stmt->execute();
        $stmt->close();
        return $periodo_default;
    }
    return $periodo;
}

function actualizarPeriodoEscolar($nuevo_periodo) {
    global $conexion;
    // Validar formato YYYY-YYYY
    if (!preg_match('/^\d{4}-\d{4}$/', $nuevo_periodo)) {
        return false;
    }
    list($inicio, $fin) = explode('-', $nuevo_periodo);
    if (intval($fin) != intval($inicio) + 1) {
        return false;
    }
    $stmt = $conexion->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'periodo_escolar'");
    $stmt->bind_param("s", $nuevo_periodo);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Genera opciones para select de años escolares
 */
function generarOpcionesAniosEscolares($selected = null, $inicio = 2020, $fin = null) {
    if ($fin === null) {
        $fin = date('Y') + 2;
    }
    if ($selected === null) {
        $selected = obtenerPeriodoEscolar();
    }
    $html = '';
    for ($i = $fin; $i >= $inicio; $i--) {
        $anio = $i . '-' . ($i + 1);
        $seleccionado = ($anio == $selected) ? 'selected' : '';
        $html .= "<option value=\"$anio\" $seleccionado>$anio</option>";
    }
    return $html;
}

/**
 * Valida y normaliza un año escolar
 */
function validarAnioEscolar($anio) {
    if (empty($anio)) {
        return false;
    }
    $anio = trim($anio);
    // Formato YYYY-YYYY
    if (preg_match('/^(\d{4})[-/](\d{4})$/', $anio, $matches)) {
        $inicio = intval($matches[1]);
        $fin = intval($matches[2]);
        if ($fin == $inicio + 1) {
            return $inicio . '-' . $fin;
        }
    }
    // Formato YYYY-YY
    if (preg_match('/^(\d{4})-(\d{2})$/', $anio, $matches)) {
        $inicio = intval($matches[1]);
        $fin = intval('20' . $matches[2]);
        if ($fin == $inicio + 1) {
            return $inicio . '-' . $fin;
        }
    }
    // Solo YYYY
    if (preg_match('/^(\d{4})$/', $anio, $matches)) {
        $inicio = intval($matches[1]);
        return $inicio . '-' . ($inicio + 1);
    }
    return false;
}

/**
 * Normaliza un año escolar a formato YYYY-YYYY
 */
function normalizarAnioEscolar($anio) {
    if (empty($anio)) {
        return false;
    }
    $anio = trim($anio);
    
    // Reemplazar separadores
    $anio = str_replace([' / ', '/', ' '], '-', $anio);
    
    // Verificar formato YYYY-YYYY
    if (preg_match('/^(\d{4})-(\d{4})$/', $anio, $matches)) {
        $inicio = intval($matches[1]);
        $fin = intval($matches[2]);
        if ($fin == $inicio + 1) {
            return $anio;
        }
    }
    
    // Verificar formato YYYY-YY
    if (preg_match('/^(\d{4})-(\d{2})$/', $anio, $matches)) {
        $inicio = intval($matches[1]);
        $fin = intval('20' . $matches[2]);
        if ($fin == $inicio + 1) {
            return $inicio . '-' . $fin;
        }
    }
    
    // Solo YYYY
    if (preg_match('/^(\d{4})$/', $anio, $matches)) {
        $inicio = intval($matches[1]);
        return $inicio . '-' . ($inicio + 1);
    }
    
    return $anio;
}
?>

