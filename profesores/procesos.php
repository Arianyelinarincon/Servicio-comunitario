<?php
session_start();

// Activar errores por si acaso
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Ruta corregida para salir dos niveles hasta la raíz del proyecto
include_once("../../config/conexion.php"); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    // 2. VALIDACIÓN DEL SUPER ADMIN (Credenciales maestras)
    if ($usuario === "admin_unefa" && $password === "superadmin123") {
        $_SESSION['usuario'] = "Super Admin";
        $_SESSION['rol'] = "superadmin";
        
        // Redirige correctamente saliendo de la carpeta login hacia profesores/index.php
        header("Location: ../index.php");
        exit();
    }

    // 3. VALIDACIÓN DE PROFESORES REGULARES
    // Buscamos en tu tabla 'profesores' asegurando que su estatus sea 'Activo'
    $query = "SELECT * FROM profesores WHERE nombre = ? AND estatus = 'Activo'";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $profesor = $result->fetch_assoc();
        
        // Clave provisional de desarrollo para los profesores regulares
        if ($password === "123456") { 
            $_SESSION['usuario'] = $profesor['nombre'];
            $_SESSION['rol'] = "profesor";
            $_SESSION['profesor_id'] = $profesor['id'];
            
            // Redirige al index.php de afuera
            header("Location: ../index.php");
            exit();
        } else {
            header("Location: login.php?error=clave_incorrecta");
            exit();
        }
    } else {
        // Si no existe o su estatus es 'Inactivo' (eliminado), no entra
        header("Location: login.php?error=no_autorizado_o_inactivo");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>