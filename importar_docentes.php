<?php
// importar_docentes.php
// Importa solo docentes (TIPO = 'D') desde PERSONAL.txt
// Los inserta en la tabla profesores con cedula, nombre, apellido, estatus Activo

require_once 'config/conexion.php';

$archivo = 'PERSONAL.txt';
if (!file_exists($archivo)) {
    die("No se encontró el archivo: $archivo");
}

$lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$total = 0;
$insertados = 0;
$errores = 0;

foreach ($lineas as $linea) {
    // Saltar encabezados
    if (strpos($linea, 'CEDULA') !== false || strpos($linea, 'NOMBRE Y APELLIDO') !== false) continue;

    $cols = explode("\t", $linea);
    if (count($cols) < 7) continue;

    $cedula = trim($cols[0] ?? '');
    $nombre_completo = trim($cols[1] ?? '');
    $tipo = trim($cols[4] ?? '');

    // Solo importar si es docente (TIPO = 'D')
    if ($tipo !== 'D') continue;

    if (empty($cedula) || empty($nombre_completo)) {
        $errores++;
        continue;
    }

    // Separar nombre y apellido
    $parts = explode(' ', $nombre_completo, 2);
    $nombre = $parts[0] ?? '';
    $apellido = $parts[1] ?? '';

    // Verificar si el docente ya existe por cédula
    $stmt = $conexion->prepare("SELECT id FROM profesores WHERE cedula = ?");
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        // Actualizar nombre y apellido por si cambió
        $stmt = $conexion->prepare("UPDATE profesores SET nombre = ?, apellido = ?, estatus = 'Activo' WHERE cedula = ?");
        $stmt->bind_param("sss", $nombre, $apellido, $cedula);
        $stmt->execute();
        echo "Docente $cedula actualizado.\n";
    } else {
        // Insertar nuevo docente (sin usuario, password, rol)
        $stmt = $conexion->prepare("INSERT INTO profesores (cedula, nombre, apellido, estatus) VALUES (?, ?, ?, 'Activo')");
        $stmt->bind_param("sss", $cedula, $nombre, $apellido);
        $stmt->execute();
        $id = $conexion->insert_id;
        echo "Docente $cedula insertado (ID $id).\n";
        $insertados++;
    }
    $total++;
}

echo "\n========== RESUMEN ==========\n";
echo "Total docentes procesados: $total\n";
echo "Insertados/actualizados: $insertados\n";
echo "Errores: $errores\n";
echo "¡Proceso completado!\n";
?>