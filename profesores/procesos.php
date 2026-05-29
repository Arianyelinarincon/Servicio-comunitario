<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM profesores WHERE BINARY usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();

        // 1. Verificamos si la contraseña es correcta
        if (password_verify($password, $data['password'])) {
            
            // 2. Verificamos si el usuario está activo
            if ($data['estatus'] == 'Activo') {
                $_SESSION['id_usuario'] = $data['id'];
                $_SESSION['usuario'] = $data['usuario'];
                $_SESSION['nombre_profesor'] = $data['nombre'];
                $_SESSION['rol'] = $data['rol'];
                $_SESSION['sala'] = $data['sala'];

                if ($data['rol'] === 'administrador') {
                    header("Location: /Servicio-comunitario/index.php");
                } else {
                    header("Location: /Servicio-comunitario/profesores/panel_profesor.php");
                }
                exit();
            } else {
                // Usuario existe y clave correcta, pero está inactivo
                header("Location: /Servicio-comunitario/profesores/Login/login.php?error=inactivo");
            }
        } else {
            // Usuario existe, pero la contraseña es incorrecta
            header("Location: /Servicio-comunitario/profesores/Login/login.php?error=clave_incorrecta");
        }
    } else {
        // No se encontró el usuario
        header("Location: /Servicio-comunitario/profesores/Login/login.php?error=usuario_incorrecto");
    }
} 
?>