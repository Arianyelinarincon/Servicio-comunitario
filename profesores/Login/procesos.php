<?php
session_start();
require_once __DIR__ . '/../../config/conexion.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php?error=acceso_denegado");
    exit();
}

$usuario = trim($_POST['usuario']);
$password = trim($_POST['password']);

$sql = "SELECT * FROM profesores WHERE BINARY usuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: login.php?error=usuario_incorrecto");
    exit();
}

$user = $result->fetch_assoc();

if ($password !== $user['password']) {
    header("Location: login.php?error=clave_incorrecta");
    exit();
}

if ($user['estatus'] !== 'Activo') {
    header("Location: login.php?error=inactivo");
    exit();
}

$rol = strtolower(trim($user['rol']));
if (!in_array($rol, ['administrador', 'super_admin'])) {
    header("Location: login.php?error=sin_permiso");
    exit();
}

$_SESSION['id_usuario']   = $user['id'];
$_SESSION['usuario']      = $user['usuario'];
$_SESSION['nombre_profesor'] = $user['nombre'] . ' ' . ($user['apellido'] ?? '');
$_SESSION['rol']          = $rol;
$_SESSION['sala']         = $user['sala'] ?? '';

// Redirigir al index de la raíz
header("Location: ../../index.php");
exit();
?>