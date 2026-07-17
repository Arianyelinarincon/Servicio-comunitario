<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'super_admin') {
    header("Location: /servicio-comunitario/index.php");
    exit();
}
require_once '../config/conexion.php';

// ========== FUNCIÓN DE AUDITORÍA ==========
if (!function_exists('registrarAuditoria')) {
    function registrarAuditoria($conexion, $usuario_id, $accion, $tabla, $registro_id, $detalles = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt = $conexion->prepare("INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalles, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ississ", $usuario_id, $accion, $tabla, $registro_id, $detalles, $ip, $user_agent);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = $_POST['rol'] ?? 'admin';
    $estatus = $_POST['estatus'] ?? 'Activo';

    // Validar
    if (empty($nombre) || empty($usuario) || empty($password)) {
        header("Location: agregar_secretaria.php?error=campos_requeridos");
        exit();
    }
    if ($password !== $confirm) {
        header("Location: agregar_secretaria.php?error=password_no_coincide");
        exit();
    }
    if (strlen($password) < 4) {
        header("Location: agregar_secretaria.php?error=password_corta");
        exit();
    }

    // Verificar si el usuario ya existe
    $stmt = $conexion->prepare("SELECT id FROM secretaria WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: agregar_secretaria.php?error=usuario_existente");
        exit();
    }
    $stmt->close();

    // Hash de contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insertar
    $stmt = $conexion->prepare("INSERT INTO secretaria (nombre, usuario, password, telefono, email, rol, estatus) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nombre, $usuario, $hashed_password, $telefono, $email, $rol, $estatus);
    $stmt->execute();
    $new_id = $conexion->insert_id;
    $stmt->close();

    // Auditoría
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $detalles = "Agregó secretaria: $nombre (Usuario: $usuario, Rol: $rol)";
    registrarAuditoria($conexion, $usuario_id, 'AGREGAR_SECRETARIA', 'secretaria', $new_id, $detalles);

    header("Location: gestionar_usuarios.php?msg=added");
    exit();

} elseif ($action === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = $_POST['rol'] ?? 'admin';
    $estatus = $_POST['estatus'] ?? 'Activo';

    if (empty($nombre) || empty($usuario)) {
        header("Location: editar_secretaria.php?id=$id&error=campos_requeridos");
        exit();
    }

    // Obtener datos actuales para auditoría
    $stmt = $conexion->prepare("SELECT nombre, usuario, rol, estatus FROM secretaria WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $old_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Construir query de actualización
    $sql = "UPDATE secretaria SET nombre = ?, usuario = ?, telefono = ?, email = ?, rol = ?, estatus = ?";
    $params = [$nombre, $usuario, $telefono, $email, $rol, $estatus];
    $types = "ssssss";

    // Si se proporcionó nueva contraseña
    if (!empty($password)) {
        if ($password !== $confirm) {
            header("Location: editar_secretaria.php?id=$id&error=password_no_coincide");
            exit();
        }
        if (strlen($password) < 4) {
            header("Location: editar_secretaria.php?id=$id&error=password_corta");
            exit();
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;
    $types .= "i";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();

    // Auditoría
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $detalles = "Editó secretaria ID $id. Datos anteriores: Nombre: {$old_data['nombre']}, Usuario: {$old_data['usuario']}, Rol: {$old_data['rol']}, Estatus: {$old_data['estatus']}";
    registrarAuditoria($conexion, $usuario_id, 'EDITAR_SECRETARIA', 'secretaria', $id, $detalles);

    header("Location: gestionar_usuarios.php?msg=updated");
    exit();
}

header("Location: gestionar_usuarios.php");
exit();