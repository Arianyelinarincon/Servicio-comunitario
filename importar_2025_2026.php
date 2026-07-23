<?php
// importar_2025_2026.php
// Importa solo estudiantes del período 2025-2026 desde el archivo BASE DE DATOS NIÑOS 1.txt

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

// Incluir conexión a la base de datos
require_once 'config/conexion.php';

// ============================
// 1. FUNCIONES AUXILIARES
// ============================

/**
 * Convierte fecha d/m/yyyy o dd/mm/yyyy a YYYY-MM-DD
 */
function convertirFecha($fecha) {
    if (empty($fecha)) return null;
    $parts = explode('/', $fecha);
    if (count($parts) !== 3) return null;
    $d = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
    $m = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
    $y = $parts[2];
    if (!checkdate($m, $d, $y)) return null;
    return "$y-$m-$d";
}

/**
 * Mapea género: Femenino -> H, Masculino -> V
 */
function mapearGenero($texto) {
    $texto = trim($texto);
    if (stripos($texto, 'femenino') !== false || stripos($texto, 'femen') !== false) {
        return 'H';
    }
    if (stripos($texto, 'masculino') !== false || stripos($texto, 'masc') !== false) {
        return 'V';
    }
    return 'V'; // por defecto
}

/**
 * Mapea sala a código interno
 */
function mapearSala($texto) {
    $texto = trim($texto);
    if (stripos($texto, '1er') !== false || stripos($texto, '1°') !== false) return '1ro';
    if (stripos($texto, '2do') !== false || stripos($texto, '2°') !== false) return '2do';
    if (stripos($texto, '3er') !== false || stripos($texto, '3°') !== false) return '3ro';
    if (stripos($texto, '4to') !== false || stripos($texto, '4°') !== false) return '4to';
    if (stripos($texto, '5to') !== false || stripos($texto, '5°') !== false) return '5to';
    if (stripos($texto, '6to') !== false || stripos($texto, '6°') !== false) return '6to';
    if (stripos($texto, 'sala de 4') !== false || stripos($texto, 'sala4') !== false) return 'sala4';
    if (stripos($texto, 'sala de 5') !== false || stripos($texto, 'sala5') !== false) return 'sala5';
    return null;
}

/**
 * Separa nombre completo en nombre y apellido (primer token = nombre, resto = apellido)
 */
function separarNombreApellido($completo) {
    $completo = trim($completo);
    $parts = explode(' ', $completo, 2);
    $nombre = $parts[0] ?? '';
    $apellido = $parts[1] ?? '';
    return [$nombre, $apellido];
}

/**
 * Obtiene el ID de la sección para una sala y nombre de sección ('U' por defecto)
 * Si no existe, la crea.
 */
function obtenerSeccionId($conexion, $sala, $nombreSeccion = 'U') {
    $stmt = $conexion->prepare("SELECT id FROM secciones WHERE sala = ? AND nombre = ?");
    $stmt->bind_param("ss", $sala, $nombreSeccion);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $stmt->close();
        return $row['id'];
    }
    $stmt->close();
    // Si no existe, la creamos
    $stmt = $conexion->prepare("INSERT INTO secciones (nombre, sala) VALUES (?, ?)");
    $stmt->bind_param("ss", $nombreSeccion, $sala);
    $stmt->execute();
    $id = $conexion->insert_id;
    $stmt->close();
    echo "Sección creada: $nombreSeccion - $sala (ID $id)\n";
    return $id;
}

/**
 * Inserta o actualiza un representante. Retorna su ID.
 */
function guardarRepresentante($conexion, $cedula, $nombre, $telefono, $direccion = '') {
    if (empty($cedula)) return null;
    // Limpiar cédula (quitar espacios, etc.)
    $cedula = trim($cedula);
    if (empty($cedula)) return null;

    // Verificar si ya existe
    $stmt = $conexion->prepare("SELECT id, nombre_completo, telefono, direccion FROM representantes WHERE cedula = ?");
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $res = $stmt->get_result();
    $existente = $res->fetch_assoc();
    $stmt->close();

    if ($existente) {
        // Actualizar solo si los campos nuevos no están vacíos
        $actualizar = false;
        $params = [];
        $tipos = '';
        if (empty($existente['nombre_completo']) && !empty($nombre)) {
            $actualizar = true;
            $params[] = $nombre;
            $tipos .= 's';
        }
        if (empty($existente['telefono']) && !empty($telefono)) {
            $actualizar = true;
            $params[] = $telefono;
            $tipos .= 's';
        }
        if (empty($existente['direccion']) && !empty($direccion)) {
            $actualizar = true;
            $params[] = $direccion;
            $tipos .= 's';
        }
        if ($actualizar) {
            // Construir UPDATE dinámico
            $sets = [];
            $idx = 0;
            if (!empty($nombre) && empty($existente['nombre_completo'])) {
                $sets[] = "nombre_completo = ?";
                $paramValues[] = $nombre;
            }
            if (!empty($telefono) && empty($existente['telefono'])) {
                $sets[] = "telefono = ?";
                $paramValues[] = $telefono;
            }
            if (!empty($direccion) && empty($existente['direccion'])) {
                $sets[] = "direccion = ?";
                $paramValues[] = $direccion;
            }
            if (!empty($sets)) {
                $sql = "UPDATE representantes SET " . implode(', ', $sets) . " WHERE cedula = ?";
                $paramValues[] = $cedula;
                $tipos = str_repeat('s', count($sets)) . 's';
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param($tipos, ...$paramValues);
                $stmt->execute();
                $stmt->close();
                echo "Representante $cedula actualizado.\n";
            }
        }
        return $existente['id'];
    } else {
        // Insertar nuevo
        $stmt = $conexion->prepare("INSERT INTO representantes (cedula, nombre_completo, telefono, direccion) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $cedula, $nombre, $telefono, $direccion);
        $stmt->execute();
        $id = $conexion->insert_id;
        $stmt->close();
        echo "Representante $cedula insertado (ID $id).\n";
        return $id;
    }
}

/**
 * Inserta o actualiza un estudiante. Retorna su ID.
 */
function guardarEstudiante($conexion, $data, $representante_id) {
    // Data esperada: cedula_escolar, nombre, apellido, genero, fecha_nac, pais_nac, nacionalidad, direccion, sala, seccion_id
    $cedula_escolar = $data['cedula_escolar'] ?? '';
    if (empty($cedula_escolar)) {
        echo "⚠️ Estudiante sin cédula escolar, omitido.\n";
        return null;
    }

    // Verificar si ya existe
    $stmt = $conexion->prepare("SELECT id FROM estudiantes WHERE cedula_escolar = ?");
    $stmt->bind_param("s", $cedula_escolar);
    $stmt->execute();
    $res = $stmt->get_result();
    $existente = $res->fetch_assoc();
    $stmt->close();

    if ($existente) {
        // Actualizar campos relevantes (opcional)
        $id = $existente['id'];
        $sql = "UPDATE estudiantes SET 
                    nombre = ?, apellido = ?, genero = ?, fecha_nacimiento = ?,
                    lugar_nacimiento = ?, nacionalidad = ?, direccion = ?,
                    sala = ?, seccion_id = ?, representante_id = ?,
                    estatus = 'Activo', inscripcion_completa = 1
                WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            "ssssssssiii",
            $data['nombre'],
            $data['apellido'],
            $data['genero'],
            $data['fecha_nac'],
            $data['pais_nac'],
            $data['nacionalidad'],
            $data['direccion'],
            $data['sala'],
            $data['seccion_id'],
            $representante_id,
            $id
        );
        $stmt->execute();
        $stmt->close();
        echo "Estudiante $cedula_escolar actualizado.\n";
        return $id;
    } else {
        // Insertar nuevo
        $stmt = $conexion->prepare("INSERT INTO estudiantes 
            (cedula_escolar, nombre, apellido, genero, fecha_nacimiento, lugar_nacimiento, nacionalidad, direccion, sala, seccion_id, representante_id, estatus, inscripcion_completa)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Activo', 1)");
        $stmt->bind_param(
            "sssssssssii",
            $data['cedula_escolar'],
            $data['nombre'],
            $data['apellido'],
            $data['genero'],
            $data['fecha_nac'],
            $data['pais_nac'],
            $data['nacionalidad'],
            $data['direccion'],
            $data['sala'],
            $data['seccion_id'],
            $representante_id
        );
        $stmt->execute();
        $id = $conexion->insert_id;
        $stmt->close();
        echo "Estudiante $cedula_escolar insertado (ID $id).\n";
        return $id;
    }
}

/**
 * Guarda inscripción en tabla inscripciones
 */
function guardarInscripcion($conexion, $estudiante_id, $ano_escolar, $grado_seccion, $peso, $talla) {
    // Verificar si ya existe inscripción para este estudiante y año
    $stmt = $conexion->prepare("SELECT id FROM inscripciones WHERE estudiante_id = ? AND ano_escolar = ?");
    $stmt->bind_param("is", $estudiante_id, $ano_escolar);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $stmt->close();
        return; // ya existe
    }
    $stmt->close();

    $stmt = $conexion->prepare("INSERT INTO inscripciones (estudiante_id, ano_escolar, grado_seccion, peso, talla, fecha_inscripcion)
                                VALUES (?, ?, ?, ?, ?, CURDATE())");
    $stmt->bind_param("issdd", $estudiante_id, $ano_escolar, $grado_seccion, $peso, $talla);
    $stmt->execute();
    $stmt->close();
    echo "Inscripción para estudiante $estudiante_id en $ano_escolar guardada.\n";
}

/**
 * Guarda rendimiento en rendimiento_estudiantil
 */
function guardarRendimiento($conexion, $estudiante_id, $periodo, $aprobado, $literal, $observacion) {
    // Verificar si ya existe
    $stmt = $conexion->prepare("SELECT id FROM rendimiento_estudiantil WHERE estudiante_id = ? AND periodo = ?");
    $stmt->bind_param("is", $estudiante_id, $periodo);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $stmt->close();
        // Actualizar
        $stmt = $conexion->prepare("UPDATE rendimiento_estudiantil SET aprobado = ?, observacion = ? WHERE estudiante_id = ? AND periodo = ?");
        $stmt->bind_param("ssis", $aprobado, $observacion, $estudiante_id, $periodo);
        $stmt->execute();
        $stmt->close();
        echo "Rendimiento actualizado para estudiante $estudiante_id.\n";
        return;
    }
    $stmt->close();

    $stmt = $conexion->prepare("INSERT INTO rendimiento_estudiantil (estudiante_id, periodo, aprobado, observacion)
                                VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $estudiante_id, $periodo, $aprobado, $observacion);
    $stmt->execute();
    $stmt->close();
    echo "Rendimiento insertado para estudiante $estudiante_id.\n";
}

// ============================
// 2. CARGAR REPRESENTANTES DESDE REPRESENTANTES.txt
// ============================

$representantes_extras = [];
$archivo_rep = 'REPRESENTANTES.txt';
if (file_exists($archivo_rep)) {
    $lineas = file($archivo_rep, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        // Saltar encabezado
        if (strpos($linea, 'REPRESENTANTES') !== false || strpos($linea, 'Cedula') !== false) continue;
        $cols = explode("\t", $linea);
        if (count($cols) < 2) continue;
        $cedula = trim($cols[0] ?? '');
        $nombre = trim($cols[1] ?? '');
        $telefono = trim($cols[9] ?? '');
        $direccion = trim($cols[8] ?? '');
        if (!empty($cedula)) {
            $representantes_extras[$cedula] = [
                'nombre' => $nombre,
                'telefono' => $telefono,
                'direccion' => $direccion
            ];
        }
    }
    echo "Se cargaron " . count($representantes_extras) . " representantes del archivo REPRESENTANTES.txt\n";
} else {
    echo "Archivo REPRESENTANTES.txt no encontrado, se usarán solo los datos del archivo de estudiantes.\n";
}

// ============================
// 3. PROCESAR BASE DE DATOS NIÑOS 1.txt
// ============================

$archivo = 'BASE DE DATOS NIÑOS 1.txt';
if (!file_exists($archivo)) {
    die("No se encontró el archivo: $archivo");
}

$lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$total = 0;
$insertados = 0;
$errores = 0;

foreach ($lineas as $linea) {
    // Saltar encabezados
    if (strpos($linea, 'BASE DE DATOS NIÑOS') !== false || strpos($linea, 'Cedula Escolar') !== false) continue;

    $cols = explode("\t", $linea);
    // Verificar que tenga al menos 24 columnas (según el formato)
    if (count($cols) < 24) {
        // Si tiene menos columnas, tal vez el archivo no está bien delimitado, omitir
        continue;
    }

    // Extraer período (columna 11)
    $periodo = trim($cols[11] ?? '');
    if ($periodo !== '2025-2026') {
        continue; // Solo nos interesa este período
    }

    $total++;

    // Datos del estudiante
    $cedula_escolar = trim($cols[0] ?? '');
    $nombre_completo = trim($cols[1] ?? '');
    $genero_texto = trim($cols[2] ?? '');
    $fecha_nac = trim($cols[3] ?? '');
    $pais_nac = trim($cols[4] ?? 'Venezuela');
    $nacionalidad = trim($cols[5] ?? 'Venezolano');
    $direccion = trim($cols[7] ?? ''); // dirección principal
    $sala_texto = trim($cols[8] ?? '');
    $seccion_nombre = trim($cols[9] ?? 'U');
    $peso_str = trim($cols[12] ?? '');
    $talla_str = trim($cols[13] ?? '');
    $estado_inscripcion = trim($cols[14] ?? '');
    $cedula_rep = trim($cols[15] ?? '');
    $nombre_rep = trim($cols[16] ?? '');
    $telefono_rep = trim($cols[17] ?? '');
    $aprobado = trim($cols[19] ?? ''); // columna 19, puede tener 'X' o vacío
    $literal = trim($cols[20] ?? '');
    $observacion = trim($cols[21] ?? '');
    // Si la observación está vacía, a veces la dirección se repite, pero usaremos la dirección principal

    // Si la dirección principal está vacía, usar la columna 22
    if (empty($direccion) && isset($cols[22])) {
        $direccion = trim($cols[22]);
    }

    // Mapear género
    $genero = mapearGenero($genero_texto);

    // Mapear sala
    $sala = mapearSala($sala_texto);
    if (!$sala) {
        echo "⚠️ Sala no reconocida: '$sala_texto' para $nombre_completo. Omitido.\n";
        $errores++;
        continue;
    }

    // Separar nombre y apellido
    list($nombre, $apellido) = separarNombreApellido($nombre_completo);

    // Convertir fecha
    $fecha_nac_formateada = convertirFecha($fecha_nac);
    if (!$fecha_nac_formateada) {
        echo "⚠️ Fecha de nacimiento inválida: '$fecha_nac' para $nombre_completo. Se usará NULL.\n";
        $fecha_nac_formateada = null;
    }

    // Peso y talla (convertir a decimal, reemplazar coma por punto)
    $peso = floatval(str_replace(',', '.', $peso_str));
    $talla = floatval(str_replace(',', '.', $talla_str));

    // Aprobado: si columna 19 tiene 'X' -> 'SI', sino 'NO' (por defecto 'SI' si está vacío)
    $aprobado_val = (stripos($aprobado, 'X') !== false) ? 'SI' : 'NO';
    // Si no hay dato, asumir 'SI' (puede ajustarse)
    if (empty($aprobado) && empty($literal)) {
        $aprobado_val = 'SI';
    }

    // Obtener sección_id
    $seccion_id = obtenerSeccionId($conexion, $sala, $seccion_nombre);

    // ----------------------------
    // Guardar representante
    // ----------------------------
    $representante_id = null;
    if (!empty($cedula_rep)) {
        // Buscar en el array de representantes extraídos del archivo REPRESENTANTES.txt
        $nombre_rep_final = $nombre_rep;
        $telefono_rep_final = $telefono_rep;
        $direccion_rep_final = $direccion; // usamos la dirección del estudiante como respaldo

        if (isset($representantes_extras[$cedula_rep])) {
            $extra = $representantes_extras[$cedula_rep];
            if (empty($nombre_rep_final) && !empty($extra['nombre'])) {
                $nombre_rep_final = $extra['nombre'];
            }
            if (empty($telefono_rep_final) && !empty($extra['telefono'])) {
                $telefono_rep_final = $extra['telefono'];
            }
            if (empty($direccion_rep_final) && !empty($extra['direccion'])) {
                $direccion_rep_final = $extra['direccion'];
            }
        }

        // Si aún no hay nombre, pero hay cédula, poner un nombre genérico
        if (empty($nombre_rep_final)) {
            $nombre_rep_final = "Representante de $cedula_rep";
        }

        $representante_id = guardarRepresentante($conexion, $cedula_rep, $nombre_rep_final, $telefono_rep_final, $direccion_rep_final);
    } else {
        // Si no hay representante, se puede crear uno ficticio o dejar NULL
        // Podríamos crear un representante genérico? Mejor dejar NULL
    }

    // ----------------------------
    // Guardar estudiante
    // ----------------------------
    $data_estudiante = [
        'cedula_escolar' => $cedula_escolar,
        'nombre' => $nombre,
        'apellido' => $apellido,
        'genero' => $genero,
        'fecha_nac' => $fecha_nac_formateada,
        'pais_nac' => $pais_nac,
        'nacionalidad' => $nacionalidad,
        'direccion' => $direccion,
        'sala' => $sala,
        'seccion_id' => $seccion_id,
    ];

    $estudiante_id = guardarEstudiante($conexion, $data_estudiante, $representante_id);
    if (!$estudiante_id) {
        $errores++;
        continue;
    }

    // ----------------------------
    // Guardar inscripción
    // ----------------------------
    $ano_escolar = '2025-2026';
    $grado_seccion = $sala_texto . ' ' . $seccion_nombre; // ej: "1er Grado U"
    guardarInscripcion($conexion, $estudiante_id, $ano_escolar, $grado_seccion, $peso, $talla);

    // ----------------------------
    // Guardar rendimiento
    // ----------------------------
    if (!empty($literal) || !empty($observacion) || $aprobado_val !== 'SI') {
        guardarRendimiento($conexion, $estudiante_id, $ano_escolar, $aprobado_val, $literal, $observacion);
    }

    $insertados++;
    echo "Procesado: $nombre_completo ($cedula_escolar)\n";
}

// ============================
// 4. RESUMEN
// ============================
echo "\n========== RESUMEN ==========\n";
echo "Total líneas con período 2025-2026: $total\n";
echo "Estudiantes insertados/actualizados: $insertados\n";
echo "Errores: $errores\n";
echo "¡Proceso completado!\n";