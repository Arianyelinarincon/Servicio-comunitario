<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../estadisticas/config_db.php";

if (isset($_GET['buscar_exacto'])) {
    $nombre_busqueda = $_GET['buscar_exacto'];
    $busqueda_con_comodines = "%" . $nombre_busqueda . "%";
    
    // 1. Consulta corregida: Se incluye tanto 'cedula' como 'cedula_escolar' 
    // y se ajusta el JOIN de profesores.
    $sql = "SELECT 
                e.nombre AS est_nombres, 
                e.apellido AS est_apellidos, 
                e.cedula AS cedula_identidad,
                e.cedula_escolar AS ce,
                r.nombre_completo AS rep_nombres, 
                s.nombre AS grupo,
                p.nombre AS doc_nombres,
                p.apellido AS doc_apellidos
            FROM estudiantes e
            LEFT JOIN representantes r ON e.representante_id = r.id
            LEFT JOIN secciones s ON e.seccion_id = s.id
            LEFT JOIN profesores p ON p.seccion = s.id
            WHERE CONCAT(e.nombre, ' ', e.apellido) LIKE ?
            LIMIT 1";
    
    // 2. Preparación segura de la consulta
    $stmt = $conexion->prepare($sql); // Corregido: Usar $conexion en lugar de $conn
    $stmt->bind_param("s", $busqueda_con_comodines);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado && $resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        
        // 3. Lógica para determinar qué cédula mostrar (ya que algunos tienen una y otros otra en tu BD)
        $cedula_final = !empty($fila['cedula_identidad']) ? $fila['cedula_identidad'] : (!empty($fila['ce']) ? $fila['ce'] : 'Sin C.E');

        echo json_encode(array(
            'success' => true,
            'nombre_completo' => $fila['est_nombres'] . ' ' . $fila['est_apellidos'],
            'ce' => $cedula_final,
            'grupo' => !empty($fila['grupo']) ? $fila['grupo'] : 'Sin asignar',
            'representante' => !empty($fila['rep_nombres']) ? $fila['rep_nombres'] : 'Sin asignar',
            'docente' => !empty($fila['doc_nombres']) ? $fila['doc_nombres'] . ' ' . $fila['doc_apellidos'] : 'Sin asignar'
        ));
    } else {
        echo json_encode(array('success' => false));
    }
    $stmt->close();
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escapar los datos antes de guardarlos en la sesión (buena práctica)
    $_SESSION['estudiante'] = htmlspecialchars($_POST['estudiante']);
    $_SESSION['ce'] = htmlspecialchars($_POST['ce']);
    $_SESSION['grupo'] = htmlspecialchars($_POST['grupo']);
    $_SESSION['ano_escolar'] = htmlspecialchars($_POST['ano_escolar']);
    $_SESSION['docente'] = htmlspecialchars($_POST['docente']);
    $_SESSION['representante'] = htmlspecialchars($_POST['representante']);
    
    header('Location: paso2_observaciones.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Paso 1: Portada</title>
    <style>
        body { font-family: Arial, sans-serif; background: rgb(240, 242, 245); padding: 20px; }
        .contenedor { background: white; padding: 30px; border-radius: 8px; max-width: 900px; margin: 0 auto; }
        h2 { color: rgb(26, 35, 126); text-align: center; }
        h3 { border-bottom: 2px solid rgb(26, 35, 126); padding-bottom: 5px; }
        p { margin-bottom: 5px; margin-top: 15px; font-weight: bold; color: #333; }
        input[type="text"] { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        input[readonly] { background-color: #e9ecef; color: #6c757d; cursor: not-allowed; }
        
        .btn-siguiente { background: rgb(26, 35, 126); color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; border-radius: 4px; margin-top: 20px; float: right; opacity: 0.5; }
        .btn-siguiente:disabled { cursor: not-allowed; }
        .alerta-error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-top: 20px; display: none; text-align: center; }
        
        .caja-busqueda { display: flex; gap: 10px; margin-bottom: 10px; }
        .btn-buscar { background: rgb(0, 150, 64); color: white; border: none; padding: 0 20px; border-radius: 4px; cursor: pointer; font-weight: bold; white-space: nowrap; }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>
<div class="contenedor">
    <h2>Paso 1: Datos de Portada</h2>
    
    <form method="POST" action="">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <p style="margin-bottom: 10px;">Estudiante:</p>
                <div class="caja-busqueda">
                    <input type="text" name="estudiante" id="estudiante" autocomplete="off" placeholder="Escribe el nombre aquí...">
                    <button type="button" class="btn-buscar" onclick="buscarEstudianteBtn()">Buscar</button>
                </div>
            </div>
            
            <div>
                <p>C.E (Cédula Escolar / Identidad):</p>
                <input type="text" name="ce" id="ce" readonly>
            </div>
            
            <div>
                <p>Grupo:</p>
                <input type="text" name="grupo" id="grupo" readonly>
            </div>
            
            <div>
                <p>Año Escolar:</p>
                <input type="text" name="ano_escolar" id="ano_escolar" value="2025-2026" readonly>
            </div>
            
            <div>
                <p>Docente:</p>
                <input type="text" name="docente" id="docente" readonly>
            </div>
            
            <div>
                <p>Representante:</p>
                <input type="text" name="representante" id="representante" readonly>
            </div>
        </div>

        <div id="mensaje_error" class="alerta-error">El nombre de estudiante no existe en el sistema escolar o está mal escrito.</div>

        <div style="overflow: hidden;">
            <button type="submit" id="btn_siguiente" class="btn-siguiente" disabled>Siguiente Paso</button>
        </div>
    </form>
</div>

<script>
function buscarEstudianteBtn() {
    let texto = document.getElementById('estudiante').value.trim();
    let msgError = document.getElementById('mensaje_error');
    
    limpiarCampos();
    if (texto.length < 2) {
        msgError.style.display = 'block';
        return;
    }

    fetch('?buscar_exacto=' + encodeURIComponent(texto))
        .then(respuesta => respuesta.json())
        .then(datos => {
            if (datos.success) {
                document.getElementById('estudiante').value = datos.nombre_completo;
                document.getElementById('ce').value = datos.ce;
                document.getElementById('grupo').value = datos.grupo;
                document.getElementById('docente').value = datos.docente;
                document.getElementById('representante').value = datos.representante;
                
                document.getElementById('btn_siguiente').disabled = false;
                document.getElementById('btn_siguiente').style.opacity = '1';
                msgError.style.display = 'none';
            } else {
                msgError.style.display = 'block';
            }
        })
        .catch(error => {
            msgError.style.display = 'block';
        });
}

function limpiarCampos() {
    document.getElementById('ce').value = '';
    document.getElementById('grupo').value = '';
    document.getElementById('docente').value = '';
    document.getElementById('representante').value = '';
    document.getElementById('btn_siguiente').disabled = true;
    document.getElementById('btn_siguiente').style.opacity = '0.5';
}
</script>

</body>
</html>