<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

require_once '../config/conexion.php';
require_once '../config/configuracion.php';

// ========== RECIBIR DATOS ==========
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
$boletin_id = isset($_POST['boletin_id']) ? intval($_POST['boletin_id']) : 0;

if (empty($tipo)) {
    $_SESSION['mensaje_error'] = '❌ Tipo de boletín no especificado.';
    header("Location: index.php");
    exit();
}

// ========== SI NO HAY ID, BUSCARLO O CREARLO ==========
if ($boletin_id <= 0) {
    $estudiante_id = $_SESSION['estudiante_id'] ?? 0;
    $periodo = $_SESSION['ano_escolar'] ?? obtenerPeriodoEscolar();
    
    if ($estudiante_id > 0) {
        // Buscar si ya existe
        $stmt = $conexion->prepare("SELECT id FROM boletines WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = ?");
        $stmt->bind_param("iss", $estudiante_id, $periodo, $tipo);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $boletin_id = $row['id'];
        }
        $stmt->close();
        
        // Si no existe, CREARLO
        if ($boletin_id <= 0) {
            $stmt = $conexion->prepare("INSERT INTO boletines (estudiante_id, periodo, tipo_boletin, fecha_emision) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iss", $estudiante_id, $periodo, $tipo);
            if ($stmt->execute()) {
                $boletin_id = $conexion->insert_id;
            }
            $stmt->close();
        }
    }
}

// Si todavía no hay ID, error
if ($boletin_id <= 0) {
    $_SESSION['mensaje_error'] = '❌ No se pudo identificar el boletín a guardar.';
    header("Location: " . ($tipo == 'inicial' ? 'panel_boletin_inicial.php' : 'panel_boletin_primaria.php'));
    exit();
}

// ========== VERIFICAR REQUISITOS MÍNIMOS ==========
if ($tipo === 'inicial') {
    $observacion = $_SESSION['observacion'] ?? '';
    $m1_completado = !empty($_SESSION['m1_proyecto']) && !empty($_SESSION['m1_formacion']);
    $m2_completado = !empty($_SESSION['m2_proyecto']) && !empty($_SESSION['m2_formacion']);
    $m3_completado = !empty($_SESSION['m3_proyecto']) && !empty($_SESSION['m3_formacion']);
    $tiene_lapso = $m1_completado || $m2_completado || $m3_completado;
    
    if (empty($observacion) || !$tiene_lapso) {
        $_SESSION['mensaje_error'] = '⚠️ Para guardar el boletín debe tener la Observación General y al menos un momento completado.';
        header("Location: panel_boletin_inicial.php?editar_id=$boletin_id");
        exit();
    }
    
    $sql = "UPDATE boletines SET 
            observacion = ?,
            m1_proyecto = ?, m1_formacion = ?, m1_relacion = ?, m1_sugerencias = ?,
            m2_proyecto = ?, m2_formacion = ?, m2_relacion = ?, m2_sugerencias = ?,
            m3_proyecto = ?, m3_formacion = ?, m3_relacion = ?, m3_sugerencias = ?
            WHERE id = ? AND tipo_boletin = 'inicial'";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssssssssssi",
        $_SESSION['observacion'],
        $_SESSION['m1_proyecto'], $_SESSION['m1_formacion'], $_SESSION['m1_relacion'], $_SESSION['m1_sugerencias'],
        $_SESSION['m2_proyecto'], $_SESSION['m2_formacion'], $_SESSION['m2_relacion'], $_SESSION['m2_sugerencias'],
        $_SESSION['m3_proyecto'], $_SESSION['m3_formacion'], $_SESSION['m3_relacion'], $_SESSION['m3_sugerencias'],
        $boletin_id
    );
    
} else { // primaria
    $observacion = $_SESSION['observacion'] ?? '';
    $l1_completado = !empty($_SESSION['l1_proyecto']) && !empty($_SESSION['l1_analisis']) && !empty($_SESSION['l1_sugerencias']);
    $l2_completado = !empty($_SESSION['l2_proyecto']) && !empty($_SESSION['l2_analisis']) && !empty($_SESSION['l2_sugerencias']);
    $l3_completado = !empty($_SESSION['l3_proyecto']) && !empty($_SESSION['l3_analisis']) && !empty($_SESSION['l3_sugerencias']) && !empty($_SESSION['resultado_final']);
    $tiene_lapso = $l1_completado || $l2_completado || $l3_completado;
    
    if (empty($observacion) || !$tiene_lapso) {
        $_SESSION['mensaje_error'] = '⚠️ Para guardar el boletín debe tener la Observación General y al menos un lapso completado.';
        header("Location: panel_boletin_primaria.php?editar_id=$boletin_id");
        exit();
    }
    
    $sql = "UPDATE boletines SET 
            observacion = ?,
            m1_proyecto = ?, m1_formacion = ?, m1_sugerencias = ?,
            m2_proyecto = ?, m2_formacion = ?, m2_sugerencias = ?,
            m3_proyecto = ?, m3_formacion = ?, m3_sugerencias = ?,
            resultado_final = ?, literal_final = ?
            WHERE id = ? AND tipo_boletin = 'primaria'";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssssssssssi",
        $_SESSION['observacion'],
        $_SESSION['l1_proyecto'], $_SESSION['l1_analisis'], $_SESSION['l1_sugerencias'],
        $_SESSION['l2_proyecto'], $_SESSION['l2_analisis'], $_SESSION['l2_sugerencias'],
        $_SESSION['l3_proyecto'], $_SESSION['l3_analisis'], $_SESSION['l3_sugerencias'],
        $_SESSION['resultado_final'], $_SESSION['literal_final'],
        $boletin_id
    );
}

// ========== EJECUTAR ==========
if ($stmt->execute()) {
    $_SESSION['mensaje_exito'] = '✅ Boletín guardado exitosamente.';
} else {
    $_SESSION['mensaje_error'] = '❌ Error al guardar: ' . $conexion->error;
}
$stmt->close();

// ========== REDIRIGIR ==========
if ($tipo === 'inicial') {
    header("Location: panel_boletin_inicial.php?editar_id=$boletin_id");
} else {
    header("Location: panel_boletin_primaria.php?editar_id=$boletin_id");
}
exit();
?>