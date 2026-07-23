<?php
session_start();

// ========== VERIFICAR AUTENTICACIÓN ==========
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

require_once __DIR__ . '/../config/conexion.php';

// ========== RECIBIR DATOS DEL FORMULARIO ==========
$nombre = strtoupper(trim($_POST['nombre'] ?? ''));
$apellido = strtoupper(trim($_POST['apellido'] ?? ''));
$cedula = trim($_POST['cedula'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$seccion_id = intval($_POST['seccion_id'] ?? 0);

// ========== DETECTAR SI ES EDICIÓN O INSERCIÓN ==========
$es_edicion = isset($_POST['editar']) && $_POST['editar'] == '1';
$profesor_id = $es_edicion ? intval($_POST['profesor_id'] ?? 0) : 0;

// ========== VALIDACIONES ==========
if (empty($nombre) || empty($apellido) || empty($cedula) || $seccion_id <= 0) {
    $destino = $es_edicion ? "editar_profesor.php?id=$profesor_id" : "agregar_profesor.php";
    header("Location: $destino&error=campos_requeridos");
    exit();
}

// Verificar que la sección existe y obtener su sala
$stmt_sec = $conexion->prepare("SELECT id, sala, nombre FROM secciones WHERE id = ?");
if (!$stmt_sec) {
    $destino = $es_edicion ? "editar_profesor.php?id=$profesor_id" : "agregar_profesor.php";
    header("Location: $destino&error=db_error");
    exit();
}
$stmt_sec->bind_param("i", $seccion_id);
$stmt_sec->execute();
$sec_result = $stmt_sec->get_result();
$seccion = $sec_result->fetch_assoc();
$stmt_sec->close();

if (!$seccion) {
    $destino = $es_edicion ? "editar_profesor.php?id=$profesor_id" : "agregar_profesor.php";
    header("Location: $destino&error=seccion_invalida");
    exit();
}

$sala = $seccion['sala']; // Ej: '1ro', 'sala4', etc.

// ========== VERIFICAR CÉDULA DUPLICADA ==========
if ($es_edicion) {
    // Excluir al mismo profesor
    $stmt_check = $conexion->prepare("SELECT id FROM profesores WHERE cedula = ? AND id != ?");
    $stmt_check->bind_param("si", $cedula, $profesor_id);
} else {
    $stmt_check = $conexion->prepare("SELECT id FROM profesores WHERE cedula = ?");
    $stmt_check->bind_param("s", $cedula);
}

if (!$stmt_check) {
    $destino = $es_edicion ? "editar_profesor.php?id=$profesor_id" : "agregar_profesor.php";
    header("Location: $destino&error=db_error");
    exit();
}
$stmt_check->execute();
$check_result = $stmt_check->get_result();
if ($check_result->num_rows > 0) {
    $stmt_check->close();
    $destino = $es_edicion ? "editar_profesor.php?id=$profesor_id" : "agregar_profesor.php";
    header("Location: $destino&error=cedula_duplicada");
    exit();
}
$stmt_check->close();

// ========== PROCESAR SEGÚN SEA EDICIÓN O INSERCIÓN ==========
if ($es_edicion) {
    // ========== ACTUALIZAR PROFESOR ==========
    $stmt_update = $conexion->prepare("
        UPDATE profesores SET 
            cedula = ?,
            nombre = ?,
            apellido = ?,
            seccion = ?,
            sala = ?,
            telefono = ?,
            direccion = ?
        WHERE id = ?
    ");
    if (!$stmt_update) {
        header("Location: editar_profesor.php?id=$profesor_id&error=db_error");
        exit();
    }
    $stmt_update->bind_param(
        "sssisssi",
        $cedula,
        $nombre,
        $apellido,
        $seccion_id,
        $sala,
        $telefono,
        $direccion,
        $profesor_id
    );
    if ($stmt_update->execute()) {
        $stmt_update->close();
        // Auditoría
        if (function_exists('registrarAuditoria')) {
            $usuario_id = $_SESSION['usuario_id'] ?? 0;
            $detalles = "Profesor editado: $nombre $apellido (Cédula: $cedula) - Sección: {$seccion['nombre']} - Sala: $sala";
            registrarAuditoria($conexion, $usuario_id, 'EDITAR_PROFESOR', 'profesores', $profesor_id, $detalles);
        }
        header("Location: gestionar_profesores.php?msg=updated");
        exit();
    } else {
        $stmt_update->close();
        header("Location: editar_profesor.php?id=$profesor_id&error=db_error");
        exit();
    }
} else {
    // ========== INSERTAR NUEVO PROFESOR ==========
    $usuario = $cedula;
    $password_hash = password_hash('12345', PASSWORD_DEFAULT);

    $stmt_insert = $conexion->prepare("
        INSERT INTO profesores 
        (cedula, nombre, apellido, seccion, sala, telefono, direccion, usuario, password, rol, estatus)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'profesor', 'Activo')
    ");
    if (!$stmt_insert) {
        header("Location: agregar_profesor.php?error=db_error");
        exit();
    }
    $stmt_insert->bind_param(
        "sssississ",
        $cedula,
        $nombre,
        $apellido,
        $seccion_id,
        $sala,
        $telefono,
        $direccion,
        $usuario,
        $password_hash
    );
    if ($stmt_insert->execute()) {
        $stmt_insert->close();
        if (function_exists('registrarAuditoria')) {
            $usuario_id = $_SESSION['usuario_id'] ?? 0;
            $detalles = "Nuevo profesor: $nombre $apellido (Cédula: $cedula) - Sección: {$seccion['nombre']} - Sala: $sala";
            registrarAuditoria($conexion, $usuario_id, 'CREAR_PROFESOR', 'profesores', $conexion->insert_id, $detalles);
        }
        header("Location: gestionar_profesores.php?msg=creado");
        exit();
    } else {
        $stmt_insert->close();
        header("Location: agregar_profesor.php?error=db_error");
        exit();
    }
}
?>