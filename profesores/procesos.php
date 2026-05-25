<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    // Consultamos por la columna 'usuario'
    $sql = "SELECT * FROM profesores WHERE usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();

        // Verificamos contraseña
        if ($password == $data['password']) {
            $_SESSION['usuario'] = $data['usuario'];
            $_SESSION['rol'] = $data['rol'];

            if ($data['rol'] === 'administrador') {
                header("Location: /Servicio-comunitario/index.php");
            } else {
                header("Location: /Servicio-comunitario//profesores/panel_profesor.php");
            }
            exit();
        } else {
            header("Location: /Servicio-comunitario/profesores/Login/login.php?error=clave");
        }
    } else {
        header("Location: /Servicio-comunitario/profesores/Login/login.php?error=usuario");
    }
} 
?>