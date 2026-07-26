<?php
session_start();
require_once '../config/conexion.php';

// Verificar autenticación
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

// ===== RECIBIR PARÁMETROS =====
$id_boletin = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$seccion = isset($_GET['seccion']) ? $_GET['seccion'] : '';

if (empty($tipo) || empty($seccion)) {
    $_SESSION['mensaje_error'] = '❌ Parámetros inválidos para limpiar.';
    header("Location: historial_boletines.php");
    exit();
}

// ===== SI EL ID NO VIENE, BUSCARLO POR ESTUDIANTE Y PERIODO =====
if ($id_boletin <= 0) {
    $estudiante_id = $_SESSION['estudiante_id'] ?? 0;
    $periodo = $_SESSION['ano_escolar'] ?? obtenerPeriodoEscolar();
    
    if ($estudiante_id > 0 && !empty($periodo)) {
        $stmt = $conexion->prepare("SELECT id FROM boletines WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
        $stmt->bind_param("iss", $estudiante_id, $periodo, $tipo);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $id_boletin = $row['id'];
        }
        $stmt->close();
    }
}

if ($id_boletin <= 0) {
    $_SESSION['mensaje_error'] = '❌ No se encontró el boletín a limpiar. Asegúrate de que el estudiante tenga un boletín activo.';
    header("Location: historial_boletines.php");
    exit();
}

// ===== DEFINIR CAMPOS A LIMPIAR (sesión y base de datos) =====
$campos_sesion = [];
$campos_bd = [];

if ($tipo === 'inicial') {
    switch ($seccion) {
        case 'observacion':
            $campos_sesion = ['observacion'];
            $campos_bd = ['observacion'];
            break;
        case 'momento1':
            $campos_sesion = ['m1_proyecto', 'm1_formacion', 'm1_relacion', 'm1_sugerencias'];
            $campos_bd = ['m1_proyecto', 'm1_formacion', 'm1_relacion', 'm1_sugerencias'];
            break;
        case 'momento2':
            $campos_sesion = ['m2_proyecto', 'm2_formacion', 'm2_relacion', 'm2_sugerencias'];
            $campos_bd = ['m2_proyecto', 'm2_formacion', 'm2_relacion', 'm2_sugerencias'];
            break;
        case 'momento3':
            $campos_sesion = ['m3_proyecto', 'm3_formacion', 'm3_relacion', 'm3_sugerencias'];
            $campos_bd = ['m3_proyecto', 'm3_formacion', 'm3_relacion', 'm3_sugerencias'];
            break;
        default:
            $_SESSION['mensaje_error'] = '❌ Sección no válida para inicial.';
            header("Location: historial_boletines.php");
            exit();
    }
} elseif ($tipo === 'primaria') {
    switch ($seccion) {
        case 'observacion':
            $campos_sesion = ['observacion'];
            $campos_bd = ['observacion'];
            break;
        case 'lapso1':
            // Sesión usa l1_*, base de datos usa m1_*
            $campos_sesion = ['l1_proyecto', 'l1_analisis', 'l1_sugerencias'];
            $campos_bd = ['m1_proyecto', 'm1_formacion', 'm1_sugerencias'];
            break;
        case 'lapso2':
            $campos_sesion = ['l2_proyecto', 'l2_analisis', 'l2_sugerencias'];
            $campos_bd = ['m2_proyecto', 'm2_formacion', 'm2_sugerencias'];
            break;
        case 'lapso3':
            $campos_sesion = ['l3_proyecto', 'l3_analisis', 'l3_sugerencias', 'resultado_final', 'literal_final'];
            $campos_bd = ['m3_proyecto', 'm3_formacion', 'm3_sugerencias', 'resultado_final', 'literal_final'];
            break;
        default:
            $_SESSION['mensaje_error'] = '❌ Sección no válida para primaria.';
            header("Location: historial_boletines.php");
            exit();
    }
} else {
    $_SESSION['mensaje_error'] = '❌ Tipo de boletín no válido.';
    header("Location: historial_boletines.php");
    exit();
}

// ===== LIMPIAR DATOS DE LA SESIÓN =====
foreach ($campos_sesion as $campo) {
    unset($_SESSION[$campo]);
}

// ===== ACTUALIZAR EN LA BASE DE DATOS =====
$check = $conexion->query("SHOW TABLES LIKE 'boletines'");
if ($check && $check->num_rows > 0) {
    $set_parts = [];
    foreach ($campos_bd as $campo) {
        $set_parts[] = "$campo = NULL";
    }
    $set_clause = implode(', ', $set_parts);
    
    $sql = "UPDATE boletines SET $set_clause WHERE id = ? AND tipo_boletin = ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("is", $id_boletin, $tipo);
        if ($stmt->execute()) {
            $filas_afectadas = $stmt->affected_rows;
            if ($filas_afectadas > 0) {
                $_SESSION['mensaje_exito'] = '✅ La sección ha sido limpiada correctamente.';
            } else {
                $_SESSION['mensaje_exito'] = 'ℹ️ La sección ya estaba vacía. No se requirió acción.';
            }
        } else {
            $_SESSION['mensaje_error'] = '❌ Error al limpiar: ' . $conexion->error;
        }
        $stmt->close();
    } else {
        $_SESSION['mensaje_error'] = '❌ Error en la preparación de la consulta: ' . $conexion->error;
    }
} else {
    $_SESSION['mensaje_error'] = '❌ La tabla boletines no existe.';
}

// ===== RECARGAR DATOS DEL BOLETÍN EN SESIÓN PARA QUE EL PANEL LOS VEA ACTUALIZADOS =====
// Volver a leer los datos del boletín desde la base de datos y actualizar la sesión
$stmt = $conexion->prepare("SELECT * FROM boletines WHERE id = ? AND tipo_boletin = ?");
$stmt->bind_param("is", $id_boletin, $tipo);
$stmt->execute();
$boletin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($boletin) {
    if ($tipo === 'inicial') {
        $_SESSION['observacion'] = $boletin['observacion'] ?? '';
        $_SESSION['m1_proyecto'] = $boletin['m1_proyecto'] ?? '';
        $_SESSION['m1_formacion'] = $boletin['m1_formacion'] ?? '';
        $_SESSION['m1_relacion'] = $boletin['m1_relacion'] ?? '';
        $_SESSION['m1_sugerencias'] = $boletin['m1_sugerencias'] ?? '';
        $_SESSION['m2_proyecto'] = $boletin['m2_proyecto'] ?? '';
        $_SESSION['m2_formacion'] = $boletin['m2_formacion'] ?? '';
        $_SESSION['m2_relacion'] = $boletin['m2_relacion'] ?? '';
        $_SESSION['m2_sugerencias'] = $boletin['m2_sugerencias'] ?? '';
        $_SESSION['m3_proyecto'] = $boletin['m3_proyecto'] ?? '';
        $_SESSION['m3_formacion'] = $boletin['m3_formacion'] ?? '';
        $_SESSION['m3_relacion'] = $boletin['m3_relacion'] ?? '';
        $_SESSION['m3_sugerencias'] = $boletin['m3_sugerencias'] ?? '';
    } else {
        $_SESSION['observacion'] = $boletin['observacion'] ?? '';
        $_SESSION['l1_proyecto'] = $boletin['m1_proyecto'] ?? '';
        $_SESSION['l1_analisis'] = $boletin['m1_formacion'] ?? '';
        $_SESSION['l1_sugerencias'] = $boletin['m1_sugerencias'] ?? '';
        $_SESSION['l2_proyecto'] = $boletin['m2_proyecto'] ?? '';
        $_SESSION['l2_analisis'] = $boletin['m2_formacion'] ?? '';
        $_SESSION['l2_sugerencias'] = $boletin['m2_sugerencias'] ?? '';
        $_SESSION['l3_proyecto'] = $boletin['m3_proyecto'] ?? '';
        $_SESSION['l3_analisis'] = $boletin['m3_formacion'] ?? '';
        $_SESSION['l3_sugerencias'] = $boletin['m3_sugerencias'] ?? '';
        $_SESSION['resultado_final'] = $boletin['resultado_final'] ?? '';
        $_SESSION['literal_final'] = $boletin['literal_final'] ?? '';
    }
}

// ===== REDIRIGIR AL PANEL =====
if ($tipo === 'inicial') {
    header("Location: panel_boletin_inicial.php?editar_id=$id_boletin");
} else {
    header("Location: panel_boletin_primaria.php?editar_id=$id_boletin");
}
exit();
?>