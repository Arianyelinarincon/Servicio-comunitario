<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_ingresado = trim($_POST['usuario']);
    $password_ingresado = trim($_POST['password']);

    $sql = "SELECT * FROM profesores WHERE BINARY usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $usuario_ingresado);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();

        if($password_ingresado === $data['password']) {
            
            if ($data['estatus'] == 'Activo') {
                $_SESSION['id_usuario'] = $data['id'];
                $_SESSION['usuario'] = $data['usuario'];
                $_SESSION['nombre_profesor'] = $data['nombre'] . ' ' . $data['apellido']; 
                $_SESSION['rol'] = $data['rol'];
                $_SESSION['sala'] = $data['sala'];

                // --- LOGICA DE REDIRECCION MODIFICADA ---
                if ($data['rol'] === 'super_admin') {
                    // Redirige exclusivamente al nuevo panel del super administrador
                    header("Location: /Servicio-comunitario/profesores/panel_super_admin.php");
                } elseif ($data['rol'] === 'administrador') {
                    // Redirige al panel de administración normal
                    header("Location: /Servicio-comunitario/index.php");
                } else {
                    // Redirige al panel de profesor
                    header("Location: /Servicio-comunitario/profesores/panel_profesor.php");
                }
                exit();
                // ----------------------------------------

            } else {
                header("Location: /Servicio-comunitario/profesores/Login/login.php?error=inactivo");
            }
        } else {
            header("Location: /Servicio-comunitario/profesores/Login/login.php?error=clave_incorrecta");
        }
    } else {
        header("Location: /Servicio-comunitario/profesores/Login/login.php?error=usuario_incorrecto");
    }
} 
?>