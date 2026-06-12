<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador de Hash</title>
    <style>
        body { 
            font-family: sans-serif; 
            background-color: #e0e0e0; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0; 
        }
        .container { 
            border-top: 4px solid #003366; 
            background: white; 
            padding: 25px; 
            border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
            width: 400px; 
            text-align: center; 
        }
        input[type="text"] { 
            width: 90%; 
            padding: 10px; 
            margin-bottom: 15px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            font-size: 16px;
        }
        button { 
            width: 100%; 
            padding: 10px; 
            background-color: #004a99; 
            border: none; 
            color: white; 
            font-weight: bold; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 16px;
        }
        button:hover { background-color: #003366; }
        .resultado { 
            margin-top: 20px; 
            padding: 15px; 
            background-color: #e8f5e9; 
            border: 1px solid #4caf50; 
            border-radius: 4px; 
            text-align: left;
            word-wrap: break-word; /* Para que el hash no se salga de la caja */
        }
        .resultado code {
            font-weight: bold;
            color: #d32f2f;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Generar Hash Seguro</h2>
    <p style="color: #666; font-size: 14px;">Escribe una contraseña para obtener su versión encriptada.</p>
    
    <form method="POST" action="">
        <input type="text" name="clave_plana" placeholder="12345" required autocomplete="off">
        <button type="submit">Generar Hash</button>
    </form>

    <?php
    // Validamos si se envió el formulario
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['clave_plana'])) {
        $clave = trim($_POST['clave_plana']);
        
        // Función nativa de PHP para crear el hash seguro
        $hash = password_hash($clave, PASSWORD_DEFAULT);

        echo "<div class='resultado'>";
        echo "<p><strong>Contraseña original:</strong> " . htmlspecialchars($clave) . "</p>";
        echo "<p><strong>Copia este Hash en tu base de datos:</strong></p>";
        echo "<code>" . $hash . "</code>";
        echo "</div>";
    }
    ?>
</div>

</body>
</html>