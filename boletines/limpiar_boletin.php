<?php
session_start();
require_once '../config/conexion.php';

// Verificar autenticación
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

// Recibir parámetros
$id_boletin = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : ''; // 'inicial' o 'primaria'
$seccion = isset($_GET['seccion']) ? $_GET['seccion'] : ''; // 'observacion', 'momento1', 'momento2', 'momento3', 'lapso1', 'lapso2', 'lapso3'

if (empty($tipo) || empty($seccion)) {
    $_SESSION['mensaje_error'] = 'Parámetros inválidos para limpiar.';
    header("Location: historial_boletines.php");
    exit();
}

// ===== DEFINIR CAMPOS A LIMPIAR EN SESIÓN Y EN BD =====
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
            $_SESSION['mensaje_error'] = 'Sección no válida para inicial.';
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
            $_SESSION['mensaje_error'] = 'Sección no válida para primaria.';
            header("Location: historial_boletines.php");
            exit();
    }
} else {
    $_SESSION['mensaje_error'] = 'Tipo de boletín no válido.';
    header("Location: historial_boletines.php");
    exit();
}

// ===== LIMPIAR DATOS DE LA SESIÓN =====
foreach ($campos_sesion as $campo) {
    unset($_SESSION[$campo]);
}

// ===== SI HAY ID DE BOLETÍN, LIMPIAR EN LA BD =====
if ($id_boletin > 0) {
    // Verificar que la tabla boletines exista
    $check = $conexion->query("SHOW TABLES LIKE 'boletines'");
    if ($check && $check->num_rows > 0) {
        // Construir SET para UPDATE
        $set_parts = [];
        foreach ($campos_bd as $campo) {
            $set_parts[] = "$campo = NULL";
        }
        $set_clause = implode(', ', $set_parts);
        
        $sql = "UPDATE boletines SET $set_clause WHERE id = ? AND tipo_boletin = ?";
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("is", $id_boletin, $tipo);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$_SESSION['mensaje_exito'] = 'La sección ha sido limpiada correctamente.';

// ===== REDIRIGIR AL PANEL CORRESPONDIENTE =====
if ($tipo === 'inicial') {
    $redirect_url = "panel_boletin_inicial.php" . ($id_boletin > 0 ? "?editar_id=$id_boletin" : "");
} else {
    $redirect_url = "panel_boletin_primaria.php" . ($id_boletin > 0 ? "?editar_id=$id_boletin" : "");
}
header("Location: $redirect_url");
exit();
?>