<?php
session_start();
unset($_SESSION['paso_actual']);

require_once "../estadisticas/config_db.php";
require_once "../config/configuracion.php";

$periodo_escolar_actual = obtenerPeriodoEscolar();
$_SESSION['tipo_boletin'] = 'primaria';

// Búsqueda AJAX para autocompletado
if (isset($_GET['buscar_estudiante'])) {
    $termino = trim($_GET['buscar_estudiante']);
    $periodo_actual = $_GET['periodo'] ?? $periodo_escolar_actual;
    
    $ano_inicio = substr($periodo_actual, 0, 4);
    $like_periodo = $ano_inicio . '%';
    $buscar = "%$termino%";
    
    $sql = "SELECT e.id, e.nombre, e.apellido, e.cedula_escolar, e.sala,
                   r.nombre_completo AS rep_nombre,
                   s.nombre AS grupo, 
                   CONCAT(p.nombre, ' ', p.apellido) AS docente_nombre
            FROM estudiantes e
            LEFT JOIN representantes r ON e.representante_id = r.id
            LEFT JOIN secciones s ON e.seccion_id = s.id
            LEFT JOIN profesores p ON p.seccion = s.id
            WHERE LOWER(CONCAT(IFNULL(e.nombre,''), ' ', IFNULL(e.apellido,''))) LIKE LOWER(?)
              AND e.estatus = 'Activo'
              AND e.sala IN ('1ro', '2do', '3ro', '4to', '5to', '6to')
              AND e.sala IS NOT NULL AND e.sala != ''
              AND e.seccion_id IS NOT NULL AND e.seccion_id > 0
              AND (e.madre_nombre IS NOT NULL AND e.madre_nombre != '' 
                   OR e.padre_nombre IS NOT NULL AND e.padre_nombre != '')
              AND EXISTS (SELECT 1 FROM profesores p2 WHERE p2.seccion = e.seccion_id AND p2.estatus = 'Activo')
              AND e.fecha_egreso IS NULL
              AND EXISTS (SELECT 1 FROM inscripciones i WHERE i.estudiante_id = e.id AND i.ano_escolar LIKE ?)
            ORDER BY e.apellido, e.nombre
            LIMIT 10";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $buscar, $like_periodo);
    $stmt->execute();
    $result = $stmt->get_result();
    $sugerencias = [];
    while ($row = $result->fetch_assoc()) {
        $sugerencias[] = [
            'id' => $row['id'],
            'nombre_completo' => $row['nombre'] . ' ' . $row['apellido'],
            'ce' => $row['cedula_escolar'],
            'grupo' => $row['grupo'] ?? 'N/A',
            'sala' => $row['sala'],
            'docente' => $row['docente_nombre'] ?? 'Sin asignar',
            'representante' => $row['rep_nombre'] ?? 'N/A'
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($sugerencias);
    exit;
}

// Procesar selección de estudiante
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estudiante_id = intval($_POST['estudiante_id']);
    $periodo_actual = $_POST['ano_escolar'] ?? $periodo_escolar_actual;
    $ano_inicio = substr($periodo_actual, 0, 4);
    $like_periodo = $ano_inicio . '%';
    
    $stmt_check = $conexion->prepare("SELECT e.id, e.sala, s.nombre AS seccion_nombre
                                      FROM estudiantes e
                                      LEFT JOIN secciones s ON e.seccion_id = s.id
                                      WHERE e.id = ? 
                                        AND e.estatus = 'Activo'
                                        AND e.sala IN ('1ro', '2do', '3ro', '4to', '5to', '6to')
                                        AND e.sala IS NOT NULL AND e.sala != ''
                                        AND e.seccion_id IS NOT NULL AND e.seccion_id > 0
                                        AND (e.madre_nombre IS NOT NULL AND e.madre_nombre != '' 
                                             OR e.padre_nombre IS NOT NULL AND e.padre_nombre != '')
                                        AND EXISTS (SELECT 1 FROM profesores p2 WHERE p2.seccion = e.seccion_id AND p2.estatus = 'Activo')
                                        AND e.fecha_egreso IS NULL
                                        AND EXISTS (SELECT 1 FROM inscripciones i WHERE i.estudiante_id = e.id AND i.ano_escolar LIKE ?)
                                      LIMIT 1");
    $stmt_check->bind_param("is", $estudiante_id, $like_periodo);
    $stmt_check->execute();
    $existe = $stmt_check->get_result()->fetch_assoc();
    if (!$existe) {
        header('Location: paso1_portada_primaria.php?error=no_valido');
        exit;
    }

    // Guardar en sesión - DOCENTE COMPLETO (viene del POST)
    $_SESSION['estudiante'] = htmlspecialchars($_POST['estudiante']);
    $_SESSION['ce'] = htmlspecialchars($_POST['ce']);
    $_SESSION['seccion'] = htmlspecialchars($_POST['grado']);
    $_SESSION['sala_codigo'] = htmlspecialchars($existe['sala']);
    $_SESSION['seccion_nombre'] = htmlspecialchars($existe['seccion_nombre'] ?? 'U');
    $_SESSION['ano_escolar'] = htmlspecialchars($_POST['ano_escolar'] ?? $periodo_escolar_actual);
    $_SESSION['docente'] = htmlspecialchars($_POST['docente']); // <-- COMPLETO (nombre + apellido)
    $_SESSION['representante'] = htmlspecialchars($_POST['representante']);
    $_SESSION['estudiante_id'] = intval($_POST['estudiante_id']);
    
    header('Location: panel_boletin_primaria.php');
    exit;
}

include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Estudiante - Boletín Primaria</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* (Mismos estilos que en inicial, solo cambia el título) */
        :root {
            --primary: #1a237e;
            --primary-dark: #0d1555;
            --primary-light: #e8eaf6;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
            --radius: 10px;
            --radius-sm: 6px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .portada-container { padding: 25px 30px; max-width: 850px; margin: 0 auto; }
        .card-portada { background: #fff; border-radius: var(--radius); padding: 30px 35px; box-shadow: var(--shadow-md); border: 1px solid var(--gray-200); transition: var(--transition); margin-top: 10px; }
        .card-portada:hover { box-shadow: var(--shadow-lg); }
        .card-portada h2 { color: var(--primary); text-align: center; margin-bottom: 25px; display: flex; align-items: center; justify-content: center; gap: 10px; border-bottom: 2px solid var(--primary); padding-bottom: 15px; font-size: 22px; font-weight: 700; }
        .grid-portada { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .caja-busqueda { position: relative; }
        .sugerencias { position: absolute; background: #fff; border: 1px solid var(--gray-300); max-height: 200px; overflow-y: auto; width: 100%; z-index: 1000; display: none; border-radius: var(--radius-sm); box-shadow: var(--shadow-lg); margin-top: 2px; }
        .sugerencias div { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid var(--gray-200); transition: var(--transition); font-size: 14px; color: var(--gray-700); }
        .sugerencias div:hover { background: var(--primary-light); color: var(--primary); }
        .grupo-form { margin-bottom: 5px; }
        .grupo-form label { font-weight: 600; color: var(--gray-700); display: block; margin-bottom: 5px; font-size: 14px; }
        .grupo-form input { width: 100%; padding: 10px 12px; border: 2px solid var(--gray-300); border-radius: var(--radius-sm); font-size: 14px; transition: var(--transition); box-sizing: border-box; color: var(--gray-700); }
        .grupo-form input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.15); }
        .grupo-form input[readonly] { background: var(--gray-100); color: var(--gray-600); }
        .btn-seleccionar { background: var(--primary); color: #fff; padding: 12px 35px; border: none; border-radius: var(--radius-sm); float: right; margin-top: 20px; font-size: 16px; font-weight: 600; cursor: pointer; transition: var(--transition); display: inline-flex; align-items: center; gap: 8px; }
        .btn-seleccionar:hover:enabled { background: var(--primary-dark); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-seleccionar:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; }
        .alerta-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px 16px; border-radius: var(--radius-sm); margin-top: 15px; display: none; text-align: center; font-size: 14px; }
        .alerta-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; padding: 10px 16px; border-radius: var(--radius-sm); margin-top: 10px; text-align: center; font-size: 13px; }
        .clearfix { clear: both; overflow: hidden; }
        .badge-info { background: #17a2b8; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 500; }
        @media (max-width: 768px) { .portada-container { padding: 12px 15px; } .card-portada { padding: 20px; } .card-portada h2 { font-size: 19px; } }
        @media (max-width: 576px) { .grid-portada { grid-template-columns: 1fr; } .card-portada { padding: 16px; } .card-portada h2 { font-size: 17px; } .btn-seleccionar { width: 100%; float: none; justify-content: center; } }
    </style>
</head>
<body>
    <div class="portada-container">
        <div class="card-portada">
            <h2><i class="fas fa-user-graduate"></i> Seleccionar Estudiante - Boletín Primaria</h2>
            <div style="text-align:center; margin-bottom:15px;">
                <span class="badge-info"><i class="fas fa-graduation-cap"></i> Solo estudiantes de 1ro a 6to grado</span>
                <span class="badge-info" style="margin-left:8px; background:#28a745;"><i class="fas fa-check-circle"></i> Requisitos: Grado, Sección, Docente asignado y al menos un padre/madre</span>
            </div>
            <div class="alerta-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Nota:</strong> Solo aparecen estudiantes con <strong>grado</strong>, <strong>sección</strong>, <strong>docente asignado</strong> y <strong>al menos un padre/madre registrado</strong>. No es necesario tener la ficha completa.
            </div>
            <form method="POST">
                <div class="grid-portada">
                    <div class="grupo-form">
                        <label><i class="fas fa-user"></i> Estudiante:</label>
                        <div class="caja-busqueda">
                            <input type="text" name="estudiante" id="estudiante" autocomplete="off" placeholder="Escriba el nombre...">
                            <div id="sugerencias" class="sugerencias"></div>
                        </div>
                    </div>
                    <div class="grupo-form">
                        <label><i class="fas fa-id-card"></i> C.E (Cédula Escolar):</label>
                        <input type="text" name="ce" id="ce" readonly>
                    </div>
                    <div class="grupo-form">
                        <label><i class="fas fa-users"></i> Grado:</label>
                        <input type="text" name="grado" id="grado" readonly>
                    </div>
                    <div class="grupo-form">
                        <label><i class="fas fa-calendar-alt"></i> Año Escolar:</label>
                        <input type="text" name="ano_escolar" id="ano_escolar" value="<?= htmlspecialchars($periodo_escolar_actual) ?>">
                    </div>
                    <div class="grupo-form">
                        <label><i class="fas fa-chalkboard-teacher"></i> Docente:</label>
                        <input type="text" name="docente" id="docente" readonly>
                    </div>
                    <div class="grupo-form">
                        <label><i class="fas fa-user-tie"></i> Representante:</label>
                        <input type="text" name="representante" id="representante" readonly>
                    </div>
                    <input type="hidden" name="sala" id="sala">
                    <input type="hidden" name="estudiante_id" id="estudiante_id">
                </div>
                <div id="mensaje_error" class="alerta-error">
                    <i class="fas fa-exclamation-triangle"></i> Estudiante no encontrado o no cumple con los requisitos mínimos (grado, sección, docente, padre/madre).
                </div>
                <div class="clearfix">
                    <button type="submit" id="btn_siguiente" class="btn-seleccionar" disabled>
                        <i class="fas fa-arrow-right"></i> Seleccionar Estudiante
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const inputEstudiante = document.getElementById('estudiante');
        const sugerenciasDiv = document.getElementById('sugerencias');
        const ceInput = document.getElementById('ce');
        const gradoInput = document.getElementById('grado');
        const docenteInput = document.getElementById('docente');
        const representanteInput = document.getElementById('representante');
        const btnSiguiente = document.getElementById('btn_siguiente');
        const mensajeError = document.getElementById('mensaje_error');
        const estudianteIdInput = document.getElementById('estudiante_id');
        const salaInput = document.getElementById('sala');
        const anoEscolarInput = document.getElementById('ano_escolar');

        function limpiarCampos() {
            ceInput.value = ''; gradoInput.value = ''; docenteInput.value = ''; representanteInput.value = '';
            estudianteIdInput.value = ''; salaInput.value = '';
            btnSiguiente.disabled = true; mensajeError.style.display = 'none';
        }
        function seleccionarEstudiante(data) {
            inputEstudiante.value = data.nombre_completo;
            ceInput.value = data.ce;
            gradoInput.value = data.grupo;
            docenteInput.value = data.docente;
            representanteInput.value = data.representante;
            estudianteIdInput.value = data.id;
            salaInput.value = data.sala;
            btnSiguiente.disabled = false;
            sugerenciasDiv.style.display = 'none';
            mensajeError.style.display = 'none';
        }
        inputEstudiante.addEventListener('input', function() {
            const texto = this.value.trim();
            const periodo = anoEscolarInput.value.trim();
            if (texto.length < 2) { sugerenciasDiv.style.display = 'none'; limpiarCampos(); return; }
            fetch('?buscar_estudiante=' + encodeURIComponent(texto) + '&periodo=' + encodeURIComponent(periodo))
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) { sugerenciasDiv.style.display = 'none'; limpiarCampos(); mensajeError.style.display = 'block'; return; }
                    sugerenciasDiv.innerHTML = '';
                    data.forEach(est => {
                        const div = document.createElement('div');
                        div.textContent = est.nombre_completo + ' (' + est.grupo + ') - ' + est.docente;
                        div.onclick = () => seleccionarEstudiante(est);
                        sugerenciasDiv.appendChild(div);
                    });
                    sugerenciasDiv.style.display = 'block';
                    mensajeError.style.display = 'none';
                })
                .catch(() => { sugerenciasDiv.style.display = 'none'; mensajeError.style.display = 'block'; });
        });
        document.addEventListener('click', function(e) {
            if (!sugerenciasDiv.contains(e.target) && e.target !== inputEstudiante) {
                sugerenciasDiv.style.display = 'none';
            }
        });
    </script>

<?php include '../includes/footer.php'; ?>
</body>
</html>