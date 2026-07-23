<?php
// config/configuracion.php
require_once __DIR__ . '/conexion.php';

// ========== FUNCIONES CON MANEJO DE ERRORES ==========

function obtenerConfiguracion($clave) {
    global $conexion;
    
    // Verificar conexión
    if (!$conexion) return null;
    
    $stmt = $conexion->prepare("SELECT valor FROM configuracion WHERE clave = ?");
    if (!$stmt) return null; // Si falla la preparación, devolver null
    
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
    if ($periodo) return $periodo;
    
    // Si no hay período, calcular uno por defecto
    global $conexion;
    if (!$conexion) return '2025-2026';
    
    $mes = date('n');
    $anio = date('Y');
    if ($mes >= 8) {
        $periodo_default = $anio . '-' . ($anio + 1);
    } else {
        $periodo_default = ($anio - 1) . '-' . $anio;
    }
    
    // Intentar insertar el período por defecto
    $stmt = $conexion->prepare("INSERT INTO configuracion (clave, valor, descripcion) 
                                VALUES ('periodo_escolar', ?, 'Periodo escolar actual') 
                                ON DUPLICATE KEY UPDATE valor = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $periodo_default, $periodo_default);
        $stmt->execute();
        $stmt->close();
    }
    return $periodo_default;
}

/**
 * ACTUALIZAR PERÍODO - CORREGIDA CON MANEJO DE ERRORES
 */
function actualizarPeriodoEscolar($nuevo_periodo) {
    global $conexion;
    
    // Validar formato
    if (!preg_match('/^\d{4}-\d{4}$/', $nuevo_periodo)) {
        return false;
    }
    list($inicio, $fin) = explode('-', $nuevo_periodo);
    if (intval($fin) != intval($inicio) + 1) {
        return false;
    }
    
    // Verificar conexión
    if (!$conexion) return false;
    
    // Preparar la consulta
    $stmt = $conexion->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'periodo_escolar'");
    if (!$stmt) {
        // Si la tabla no existe, intentar crearla
        $conexion->query("CREATE TABLE IF NOT EXISTS configuracion (
            id INT PRIMARY KEY AUTO_INCREMENT,
            clave VARCHAR(50) UNIQUE NOT NULL,
            valor VARCHAR(50) NOT NULL,
            descripcion VARCHAR(255) DEFAULT NULL
        )");
        // Intentar insertar el período
        $stmt = $conexion->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES ('periodo_escolar', ?, 'Periodo escolar actual') ON DUPLICATE KEY UPDATE valor = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ss", $nuevo_periodo, $nuevo_periodo);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    $stmt->bind_param("s", $nuevo_periodo);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// ========== EL RESTO DE FUNCIONES (sin cambios, pero con manejo de errores) ==========

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

function validarAnioEscolar($anio) {
    if (empty($anio)) return false;
    $anio = trim($anio);
    if (preg_match('/^(\d{4})[-/](\d{4})$/', $anio, $matches)) {
        $inicio = intval($matches[1]);
        $fin = intval($matches[2]);
        if ($fin == $inicio + 1) return $inicio . '-' . $fin;
    }
    if (preg_match('/^(\d{4})-(\d{2})$/', $anio, $matches)) {
        $inicio = intval($matches[1]);
        $fin = intval('20' . $matches[2]);
        if ($fin == $inicio + 1) return $inicio . '-' . $fin;
    }
    if (preg_match('/^(\d{4})$/', $anio, $matches)) {
        $inicio = intval($matches[1]);
        return $inicio . '-' . ($inicio + 1);
    }
    return false;
}

function normalizarAnioEscolar($anio) {
    if (empty($anio)) return false;
    $anio = trim($anio);
    $anio = str_replace([' / ', '/', ' '], '-', $anio);
    if (preg_match('/^(\d{4})-(\d{4})$/', $anio, $matches)) {
        $inicio = intval($matches[1]);
        $fin = intval($matches[2]);
        if ($fin == $inicio + 1) return $anio;
    }
    if (preg_match('/^(\d{4})-(\d{2})$/', $anio, $matches)) {
        $inicio = intval($matches[1]);
        $fin = intval('20' . $matches[2]);
        if ($fin == $inicio + 1) return $inicio . '-' . $fin;
    }
    if (preg_match('/^(\d{4})$/', $anio, $matches)) {
        $inicio = intval($matches[1]);
        return $inicio . '-' . ($inicio + 1);
    }
    return $anio;
}

// ========== FUNCIONES DE VALIDACIÓN DE FICHA ==========

function obtenerValorConOtro($select, $otro) {
    return ($select === 'OTRO' && !empty($otro)) ? $otro : $select;
}

function verificarInscripcionCompleta($estudiante_id, $conexion) {
    $stmt = $conexion->prepare("SELECT * FROM estudiantes WHERE id = ?");
    if (!$stmt) return ['completa' => false, 'faltantes' => ['Error de conexión']];
    $stmt->bind_param("i", $estudiante_id);
    $stmt->execute();
    $est = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$est) return ['completa' => false, 'faltantes' => ['Estudiante no encontrado']];

    $faltantes = [];
    $obligatorios = [
        'nombre' => 'Nombres',
        'apellido' => 'Apellidos',
        'fecha_nacimiento' => 'Fecha de nacimiento',
        'genero' => 'Sexo',
        'cedula_escolar' => 'Cédula escolar',
        'sala' => 'Sala / Grado',
        'seccion_id' => 'Sección'
    ];
    foreach ($obligatorios as $campo => $label) {
        if (empty($est[$campo]) && $est[$campo] !== '0') {
            $faltantes[] = $label;
        }
    }

    if (!empty($est['representante_id'])) {
        $stmt2 = $conexion->prepare("SELECT * FROM representantes WHERE id = ?");
        if ($stmt2) {
            $stmt2->bind_param("i", $est['representante_id']);
            $stmt2->execute();
            $rep = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            if ($rep) {
                if (empty($rep['nombre_completo'])) $faltantes[] = 'Nombre del representante';
                if (empty($rep['cedula'])) $faltantes[] = 'Cédula del representante';
                if (empty($rep['telefono'])) $faltantes[] = 'Teléfono del representante';
            } else {
                $faltantes[] = 'Representante no encontrado';
            }
        }
    } else {
        $faltantes[] = 'Representante';
    }

    if (empty($est['madre_nombre']) && empty($est['padre_nombre'])) {
        $faltantes[] = 'Nombre de madre o padre (al menos uno)';
    }

    if ($est['enfermedad'] === 'Si' && empty($est['enfermedad_cual'])) {
        $faltantes[] = 'Especificar enfermedad';
    }
    if ($est['educacion_fisica'] === 'No' && empty($est['educacion_fisica_porque'])) {
        $faltantes[] = 'Motivo por el que no puede hacer Educación Física';
    }
    if ($est['alergia'] === 'Si' && empty($est['alergia_cual'])) {
        $faltantes[] = 'Especificar alergias';
    }

    return [
        'completa' => empty($faltantes),
        'faltantes' => $faltantes
    ];
}

function verificarFichaCompleta($estudiante_id, $conexion) {
    $stmt = $conexion->prepare("SELECT * FROM estudiantes WHERE id = ?");
    if (!$stmt) return ['completa' => false, 'faltantes' => ['Error de conexión']];
    $stmt->bind_param("i", $estudiante_id);
    $stmt->execute();
    $est = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$est) return ['completa' => false, 'faltantes' => ['Estudiante no encontrado']];

    $faltantes = [];
    $campos_obligatorios = [
        'nombre' => 'Nombres',
        'apellido' => 'Apellidos',
        'fecha_nacimiento' => 'Fecha de nacimiento',
        'genero' => 'Sexo',
        'cedula_escolar' => 'Cédula escolar',
        'sala' => 'Sala / Grado',
        'seccion_id' => 'Sección',
        'nacionalidad' => 'Nacionalidad',
        'pais_nacimiento' => 'País de nacimiento',
        'estado_nacimiento' => 'Estado de nacimiento',
        'direccion' => 'Dirección',
        'estado_residencia' => 'Estado de residencia',
        'municipio' => 'Municipio',
        'parroquia' => 'Parroquia',
        'ciudad' => 'Ciudad',
        'madre_nombre' => 'Nombre de la madre',
        'padre_nombre' => 'Nombre del padre'
    ];

    foreach ($campos_obligatorios as $campo => $label) {
        if (empty($est[$campo]) && $est[$campo] !== '0') {
            $faltantes[] = $label;
        }
    }

    if (!empty($est['madre_nombre']) && empty($est['madre_cedula'])) {
        $faltantes[] = 'Cédula de la madre';
    }
    if (!empty($est['padre_nombre']) && empty($est['padre_cedula'])) {
        $faltantes[] = 'Cédula del padre';
    }

    if (!empty($est['representante_id'])) {
        $stmt2 = $conexion->prepare("SELECT * FROM representantes WHERE id = ?");
        if ($stmt2) {
            $stmt2->bind_param("i", $est['representante_id']);
            $stmt2->execute();
            $rep = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            if ($rep) {
                $campos_rep = [
                    'nombre_completo' => 'Nombre del representante',
                    'cedula' => 'Cédula del representante',
                    'telefono' => 'Teléfono del representante',
                    'parentesco' => 'Parentesco',
                    'direccion' => 'Dirección del representante',
                    'estado_residencia' => 'Estado de residencia del representante',
                    'municipio' => 'Municipio del representante',
                    'parroquia' => 'Parroquia del representante',
                    'ciudad' => 'Ciudad del representante'
                ];
                foreach ($campos_rep as $campo => $label) {
                    if (empty($rep[$campo]) && $rep[$campo] !== '0') {
                        $faltantes[] = $label;
                    }
                }
            } else {
                $faltantes[] = 'Representante no encontrado';
            }
        }
    } else {
        $faltantes[] = 'Representante';
    }

    if ($est['enfermedad'] === 'Si' && empty($est['enfermedad_cual'])) {
        $faltantes[] = 'Especificar enfermedad';
    }
    if ($est['educacion_fisica'] === 'No' && empty($est['educacion_fisica_porque'])) {
        $faltantes[] = 'Motivo por el que no puede hacer Educación Física';
    }
    if ($est['alergia'] === 'Si' && empty($est['alergia_cual'])) {
        $faltantes[] = 'Especificar alergias';
    }

    return [
        'completa' => empty($faltantes),
        'faltantes' => $faltantes
    ];
}

function verificarDatosBoletin($estudiante_id, $conexion) {
    $stmt = $conexion->prepare("SELECT * FROM inscripciones WHERE estudiante_id = ? ORDER BY ano_escolar DESC LIMIT 1");
    if (!$stmt) return ['completo' => false, 'faltantes' => ['Error de conexión']];
    $stmt->bind_param("i", $estudiante_id);
    $stmt->execute();
    $ins = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $faltantes = [];
    if (!$ins) {
        $faltantes[] = 'No hay registro en el historial escolar';
        return ['completo' => false, 'faltantes' => $faltantes];
    }

    $campos_boletin = [
        'peso' => 'Peso (kg)',
        'talla' => 'Talla (cm)',
        'c' => 'Calificación C',
        'f' => 'Calificación F',
        'p' => 'Calificación P',
        'grado_seccion' => 'Grado y sección'
    ];

    foreach ($campos_boletin as $campo => $label) {
        if ((!isset($ins[$campo]) || $ins[$campo] === '' || $ins[$campo] === null) && $ins[$campo] !== 0) {
            $faltantes[] = $label;
        }
    }

    return [
        'completo' => empty($faltantes),
        'faltantes' => $faltantes
    ];
}

function obtenerEstadoCompleto($estudiante_id, $conexion) {
    $ficha = verificarFichaCompleta($estudiante_id, $conexion);
    $boletin = verificarDatosBoletin($estudiante_id, $conexion);

    return [
        'ficha' => $ficha,
        'boletin' => $boletin
    ];
}
?>