<?php
die("SI ESTAS LEYENDO ESTO, EL ARCHIVO SI SE GUARDO");
include_once("Login/conexion.php");

session_start();

if (isset($_POST['login'])) {
    
    $usuario = mysqli_real_escape_string($conexion, $_POST['usuario']);
    $password = mysqli_real_escape_string($conexion, $_POST['password']);

    $sql = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND password = '$password'";
    $resultado = mysqli_query($conexion, $sql);

    if (mysqli_num_rows($resultado) > 0) {
        $fila = mysqli_fetch_array($resultado);
        $_SESSION['usuario'] = $fila['usuario'];
    
        
        header("Location: index.php"); 
        exit();
    } else {
        echo "<script>alert('Usuario o contraseña incorrectos'); window.location='Login/login.php';</script>";
    }
}
?>