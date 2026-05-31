<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_ingresado = trim($_POST['usuario']);
    $password_ingresado = trim($_POST['password']);

    $sql = "SELECT * FROM administradores WHERE BINARY usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $usuario_ingresado);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();

        // CORRECCIÓN AQUÍ: usamos $password_ingresado y $data['password']
        if($password_ingresado === $data['password']) {
            
            // 2. Verificamos si el usuario está activo
            if ($data['estatus'] == 'Activo') {
                $_SESSION['id_usuario'] = $data['id'];
                $_SESSION['usuario'] = $data['usuario'];
                // Asegúrate que 'nombre_profesores' coincida con tu columna actual
                $_SESSION['nombre_profesor'] = $data['nombre_profesores']; 
                $_SESSION['rol'] = $data['rol'];
                $_SESSION['sala'] = $data['sala'];

                if ($data['rol'] === 'administrador' || $data['rol'] === 'super_admin') {
                    header("Location: /Servicio-comunitario/index.php");
                } else {
                    header("Location: /Servicio-comunitario/profesores/panel_profesor.php");
                }
                exit();
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