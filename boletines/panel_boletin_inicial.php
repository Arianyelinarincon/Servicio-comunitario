<?php
session_start();
// Verificar rol ANTES de incluir header
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

// ===== CARGA DE DATOS ANTES DE HEADER =====
require_once '../config/conexion.php';
require_once '../config/configuracion.php';

$periodo_escolar_actual = obtenerPeriodoEscolar();

$tabla_boletines_existe = false;
$check = $conexion->query("SHOW TABLES LIKE 'boletines'");
if ($check && $check->num_rows > 0) {
    $tabla_boletines_existe = true;
}

$nombres_salas = [
    'sala4' => 'Sala 4 Años',
    'sala5' => 'Sala 5 Años',
    '1ro'   => '1er Grado',
    '2do'   => '2do Grado',
    '3ro'   => '3er Grado',
    '4to'   => '4to Grado',
    '5to'   => '5to Grado',
    '6to'   => '6to Grado'
];

// Variables por defecto
$estudiante_id = 0;
$estudiante_nombre = '';
$ce = '';
$ano_escolar = $periodo_escolar_actual;
$docente = 'No asignado';
$representante = 'No registrado';
$sala_codigo = '';
$seccion = 'U';
$grado_legible = '';
$boletin_id = 0;
$modo_edicion = false;

// ===== SI VIENE editar_id, CARGAR DESDE BD =====
if (isset($_GET['editar_id']) && is_numeric($_GET['editar_id']) && $tabla_boletines_existe) {
    $id_editar = intval($_GET['editar_id']);
    
    // SQL corregido con los JOINs correspondientes para docente y representante
    $sql_editar = "SELECT b.*, e.id AS estudiante_id, e.nombre, e.apellido, e.cedula_escolar, e.sala, 
                          s.nombre AS seccion_nombre,
                          r.nombre_completo AS representante_nombre,
                          CONCAT(p.nombre, ' ', p.apellido) AS docente_nombre
                   FROM boletines b
                   INNER JOIN estudiantes e ON b.estudiante_id = e.id
                   LEFT JOIN secciones s ON e.seccion_id = s.id
                   LEFT JOIN representantes r ON e.representante_id = r.id
                   LEFT JOIN profesores p ON p.seccion = e.seccion_id AND p.estatus = 'Activo'
                   WHERE b.id = ?";
                   
    $stmt = $conexion->prepare($sql_editar);
    if ($stmt) {
        $stmt->bind_param("i", $id_editar);
        $stmt->execute();
        $boletin = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($boletin) {
            $modo_edicion = true;
            $boletin_id = $id_editar;
            $estudiante_id = $boletin['estudiante_id'];
            $estudiante_nombre = trim($boletin['nombre'] . ' ' . $boletin['apellido']);
            $ce = $boletin['cedula_escolar'] ?? '';
            $ano_escolar = $boletin['periodo'] ?? $periodo_escolar_actual;
            $docente = !empty($boletin['docente_nombre']) ? $boletin['docente_nombre'] : 'No asignado';
            $representante = !empty($boletin['representante_nombre']) ? $boletin['representante_nombre'] : 'No registrado';
            $sala_codigo = $boletin['sala'] ?? '';
            $seccion = $boletin['seccion_nombre'] ?? 'U';
            $grado_legible = $nombres_salas[$sala_codigo] ?? $sala_codigo;

            // SOBRESCRIBIR LA SESIÓN con los datos exactos del estudiante a editar
            $_SESSION['estudiante'] = $estudiante_nombre;
            $_SESSION['estudiante_id'] = $estudiante_id;
            $_SESSION['ce'] = $ce;
            $_SESSION['ano_escolar'] = $ano_escolar;
            $_SESSION['docente'] = $docente;
            $_SESSION['representante'] = $representante;
            $_SESSION['sala_codigo'] = $sala_codigo;
            $_SESSION['seccion'] = $seccion;

            // Cargar campos del boletín en sesión
            $_SESSION['observacion'] = $boletin['observacion'] ?? '';
            $_SESSION['m1_proyecto'] = $boletin['m1_proyecto'] ?? '';
            $_SESSION['m1_formacion'] = $boletin['m1_formacion'] ?? '';
            $_SESSION['m1_relacion'] = $boletin['m1_relacion'] ?? '';
            $_SESSION['m1_sugerencias'] = $boletin['m1_sugerencias'] ?? '';
            $_SESSION['m2_proyecto'] = $boletin['m2_proyecto'] ?? '';
            $_SESSION['m2_formacion'] = $boletin['m2_formacion'] ?? '';
            $_SESSION['m2_relacion'] = $boletin['m2_relacion'] ?? '';
            $_SESSION['m2_sugerencias'] = $boletin['m2_sugerencias'] ?? '';
            $_SESSION['m3_proyecto'] = $boletin['m3_proyecto'] ?? '';
            $_SESSION['m3_formacion'] = $boletin['m3_formacion'] ?? '';
            $_SESSION['m3_relacion'] = $boletin['m3_relacion'] ?? '';
            $_SESSION['m3_sugerencias'] = $boletin['m3_sugerencias'] ?? '';
        }
    }
}

// ===== SI NO VIENE editar_id, USAR SESIÓN =====
if (!$modo_edicion) {
    if (!isset($_SESSION['estudiante'])) {
        header('Location: paso1_portada.php?tipo=inicial');
        exit;
    }
    $estudiante_id = $_SESSION['estudiante_id'] ?? 0;
    $estudiante_nombre = $_SESSION['estudiante'] ?? '';
    $ce = $_SESSION['ce'] ?? '';
    $ano_escolar = $_SESSION['ano_escolar'] ?? $periodo_escolar_actual;
    $docente = $_SESSION['docente'] ?? 'No asignado';
    $representante = $_SESSION['representante'] ?? 'No registrado';
    $sala_codigo = $_SESSION['sala_codigo'] ?? '';
    $seccion = $_SESSION['seccion'] ?? 'U';
    $grado_legible = $nombres_salas[$sala_codigo] ?? $sala_codigo;

    // Cargar boletín existente para este estudiante y periodo (sin editar_id)
    if ($tabla_boletines_existe && $estudiante_id > 0) {
        $stmt = $conexion->prepare("SELECT id FROM boletines WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = 'inicial'");
        if ($stmt) {
            $stmt->bind_param("is", $estudiante_id, $ano_escolar);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $boletin_id = $row['id'];
            }
            $stmt->close();
        }
        if ($boletin_id > 0) {
            $stmt = $conexion->prepare("SELECT * FROM boletines WHERE id = ? AND tipo_boletin = 'inicial'");
            if ($stmt) {
                $stmt->bind_param("i", $boletin_id);
                $stmt->execute();
                $boletin = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($boletin) {
                    $_SESSION['observacion'] = $boletin['observacion'] ?? '';
                    $_SESSION['m1_proyecto'] = $boletin['m1_proyecto'] ?? '';
                    $_SESSION['m1_formacion'] = $boletin['m1_formacion'] ?? '';
                    $_SESSION['m1_relacion'] = $boletin['m1_relacion'] ?? '';
                    $_SESSION['m1_sugerencias'] = $boletin['m1_sugerencias'] ?? '';
                    $_SESSION['m2_proyecto'] = $boletin['m2_proyecto'] ?? '';
                    $_SESSION['m2_formacion'] = $boletin['m2_formacion'] ?? '';
                    $_SESSION['m2_relacion'] = $boletin['m2_relacion'] ?? '';
                    $_SESSION['m2_sugerencias'] = $boletin['m2_sugerencias'] ?? '';
                    $_SESSION['m3_proyecto'] = $boletin['m3_proyecto'] ?? '';
                    $_SESSION['m3_formacion'] = $boletin['m3_formacion'] ?? '';
                    $_SESSION['m3_relacion'] = $boletin['m3_relacion'] ?? '';
                    $_SESSION['m3_sugerencias'] = $boletin['m3_sugerencias'] ?? '';
                }
            }
        } else {
            // No existe, limpiar sesión
            unset($_SESSION['observacion']);
            unset($_SESSION['m1_proyecto'], $_SESSION['m1_formacion'], $_SESSION['m1_relacion'], $_SESSION['m1_sugerencias']);
            unset($_SESSION['m2_proyecto'], $_SESSION['m2_formacion'], $_SESSION['m2_relacion'], $_SESSION['m2_sugerencias']);
            unset($_SESSION['m3_proyecto'], $_SESSION['m3_formacion'], $_SESSION['m3_relacion'], $_SESSION['m3_sugerencias']);
        }
    }
}

// ===== AHORA INCLUIMOS EL HEADER =====
include '../includes/header.php';

// ===== ESTADO DE SECCIONES =====
$obs_completada = !empty($_SESSION['observacion']);
$m1_completado = !empty($_SESSION['m1_proyecto']) && !empty($_SESSION['m1_formacion']);
$m2_completado = !empty($_SESSION['m2_proyecto']) && !empty($_SESSION['m2_formacion']);
$m3_completado = !empty($_SESSION['m3_proyecto']) && !empty($_SESSION['m3_formacion']);

$puede_generar_pdf = $obs_completada && ($m1_completado || $m2_completado || $m3_completado);

$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
$mensaje_error = $_SESSION['mensaje_error'] ?? '';
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

// Escapar para HTML
$estudiante_nombre = htmlspecialchars($estudiante_nombre);
$ce = htmlspecialchars($ce);
$seccion = htmlspecialchars($seccion);
$grado_mostrado = $grado_legible . ' "' . $seccion . '"';
$ano_escolar = htmlspecialchars($ano_escolar);
$docente = htmlspecialchars($docente);
$representante = htmlspecialchars($representante);

$observacion = htmlspecialchars($_SESSION['observacion'] ?? '');
$m1_proyecto = htmlspecialchars($_SESSION['m1_proyecto'] ?? '');
$m1_formacion = htmlspecialchars($_SESSION['m1_formacion'] ?? '');
$m1_relacion = htmlspecialchars($_SESSION['m1_relacion'] ?? '');
$m1_sugerencias = htmlspecialchars($_SESSION['m1_sugerencias'] ?? '');
$m2_proyecto = htmlspecialchars($_SESSION['m2_proyecto'] ?? '');
$m2_formacion = htmlspecialchars($_SESSION['m2_formacion'] ?? '');
$m2_relacion = htmlspecialchars($_SESSION['m2_relacion'] ?? '');
$m2_sugerencias = htmlspecialchars($_SESSION['m2_sugerencias'] ?? '');
$m3_proyecto = htmlspecialchars($_SESSION['m3_proyecto'] ?? '');
$m3_formacion = htmlspecialchars($_SESSION['m3_formacion'] ?? '');
$m3_relacion = htmlspecialchars($_SESSION['m3_relacion'] ?? '');
$m3_sugerencias = htmlspecialchars($_SESSION['m3_sugerencias'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Boletín Inicial</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Todos los estilos que ya tenías (no los repito por brevedad, pero deben estar aquí) */
        :root {
            --primary: #1a237e;
            --primary-dark: #0d1555;
            --primary-light: #e8eaf6;
            --success: #28a745;
            --success-light: #d4edda;
            --warning: #ffc107;
            --warning-light: #fff3cd;
            --info: #17a2b8;
            --info-light: #d1ecf1;
            --secondary: #6c757d;
            --secondary-light: #e2e3e5;
            --danger: #dc3545;
            --danger-light: #f8d7da;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
            --radius: 10px;
            --radius-sm: 6px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .panel-container { padding: 20px 25px; max-width: 1400px; margin: 0 auto; }
        .panel-card { background: #fff; border-radius: var(--radius); padding: 25px 30px; box-shadow: var(--shadow-md); border: 1px solid var(--gray-200); transition: var(--transition); margin-top: 10px; }
        .panel-card:hover { box-shadow: var(--shadow-lg); }
        .panel-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; border-bottom: 2px solid var(--primary); padding-bottom: 15px; margin-bottom: 22px; }
        .panel-header h2 { color: var(--primary); font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 10px; margin: 0; }
        .panel-header .badge-tipo { background: var(--primary-light); color: var(--primary); padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .panel-header .badge-edicion { background: var(--warning-light); color: #856404; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .estudiante-info { background: var(--primary-light); padding: 16px 20px; border-radius: var(--radius-sm); margin-bottom: 25px; border-left: 4px solid var(--primary); display: flex; flex-wrap: wrap; gap: 12px 28px; font-size: 14px; line-height: 1.8; color: var(--gray-700); }
        .estudiante-info strong { color: var(--primary); font-weight: 600; }
        .estudiante-info i { color: var(--primary); width: 18px; text-align: center; }
        .grid-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .card-item { background: #fff; border-radius: var(--radius); padding: 18px 20px; border: 1px solid var(--gray-200); transition: var(--transition); position: relative; overflow: hidden; }
        .card-item::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--primary); transition: var(--transition); }
        .card-item:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: var(--primary); }
        .card-item:hover::before { height: 6px; }
        .card-item .card-header { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .card-item .card-header i { font-size: 18px; }
        .card-item .card-header h3 { font-size: 15px; font-weight: 600; color: var(--gray-800); margin: 0; }
        .estado-badge { display: inline-block; padding: 3px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-bottom: 8px; }
        .estado-completado { background: var(--success-light); color: #155724; }
        .estado-pendiente { background: var(--warning-light); color: #856404; }
        .preview-texto { font-size: 13px; color: var(--gray-700); margin: 6px 0 12px; padding: 8px 12px; background: var(--gray-100); border-radius: var(--radius-sm); min-height: 38px; word-break: break-word; border: 1px solid var(--gray-200); line-height: 1.5; }
        .preview-texto .vacio { color: var(--gray-500); font-style: italic; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 18px; border: none; border-radius: var(--radius-sm); cursor: pointer; text-decoration: none; font-weight: 600; font-size: 12px; transition: var(--transition); text-align: center; line-height: 1.5; }
        .btn i { font-size: 13px; }
        .btn:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }
        .btn:active { transform: translateY(0); }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: var(--warning); color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .btn-info { background: var(--info); color: #fff; }
        .btn-info:hover { background: #117a8b; }
        .btn-secondary { background: var(--secondary); color: #fff; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: #bd2130; }
        .btn-sm { padding: 5px 14px; font-size: 11px; }
        .btn-lg { padding: 12px 32px; font-size: 15px; }
        .acciones-generales { border-top: 2px solid var(--gray-200); padding-top: 22px; margin-top: 5px; display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; align-items: center; }
        .alerta-pendiente { background: var(--warning-light); padding: 12px 20px; border-radius: var(--radius-sm); text-align: center; border: 1px solid var(--warning); width: 100%; color: #856404; }
        .alerta-pendiente strong { display: block; font-size: 15px; }
        .alerta-pendiente .faltantes { font-size: 13px; }
        .alert { padding: 12px 20px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: var(--success-light); border: 1px solid var(--success); color: #155724; }
        .alert-danger { background: var(--danger-light); border: 1px solid var(--danger); color: #721c24; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .card-item { animation: fadeInUp 0.5s ease forwards; }
        .card-item:nth-child(1) { animation-delay: 0.05s; }
        .card-item:nth-child(2) { animation-delay: 0.1s; }
        .card-item:nth-child(3) { animation-delay: 0.15s; }
        .card-item:nth-child(4) { animation-delay: 0.2s; }
        @media (max-width: 1200px) { .grid-cards { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .panel-container { padding: 12px 15px; } .panel-card { padding: 18px 16px; } .panel-header { flex-direction: column; align-items: flex-start; } .panel-header h2 { font-size: 19px; } .estudiante-info { font-size: 13px; padding: 12px 15px; gap: 8px 18px; } .grid-cards { grid-template-columns: 1fr; } }
        @media (max-width: 576px) { .grid-cards { grid-template-columns: 1fr; } .acciones-generales .btn-lg { width: 100%; justify-content: center; } .panel-card { padding: 14px 12px; } .panel-header h2 { font-size: 17px; } }
    </style>
</head>
<body>
<div class="panel-container">
    <div class="panel-card">
        <div class="panel-header">
            <h2>
                <i class="fas fa-clipboard-list"></i>
                Panel de Control - Boletín Inicial
                <?php if ($modo_edicion): ?>
                    <span class="badge-edicion"><i class="fas fa-edit"></i> Modo Edición</span>
                <?php endif; ?>
            </h2>
            <span class="badge-tipo"><i class="fas fa-baby"></i> Inicial</span>
        </div>

        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <div class="estudiante-info">
            <div><i class="fas fa-user"></i> <strong>Estudiante:</strong> <?php echo $estudiante_nombre; ?></div>
            <div><i class="fas fa-id-card"></i> <strong>C.E:</strong> <?php echo $ce; ?></div>
            <div><i class="fas fa-users"></i> <strong>Grupo:</strong> <?php echo $grado_mostrado; ?></div>
            <div><i class="fas fa-calendar-alt"></i> <strong>Año Escolar:</strong> <?php echo $ano_escolar; ?></div>
            <div><i class="fas fa-chalkboard-teacher"></i> <strong>Docente:</strong> <?php echo $docente; ?></div>
            <div><i class="fas fa-user-tie"></i> <strong>Representante:</strong> <?php echo $representante; ?></div>
        </div>

        <div class="grid-cards">
            <!-- OBSERVACIÓN GENERAL -->
            <div class="card-item" style="--card-color: var(--primary);">
                <div class="card-header">
                    <i class="fas fa-pen" style="color: var(--primary);"></i>
                    <h3>Observación General</h3>
                </div>
                <span class="estado-badge <?php echo $obs_completada ? 'estado-completado' : 'estado-pendiente'; ?>">
                    <?php echo $obs_completada ? '✓ Completada' : '⏳ Pendiente'; ?>
                </span>
                <div class="preview-texto">
                    <?php if ($obs_completada): ?>
                        <?php echo substr($observacion, 0, 80) . (strlen($observacion) > 80 ? '...' : ''); ?>
                    <?php else: ?>
                        <span class="vacio">Sin observación registrada</span>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <a href="editar_observacion_inicial.php?boletin_id=<?= $boletin_id ?>" class="btn <?php echo $obs_completada ? 'btn-warning' : 'btn-primary'; ?> btn-sm">
                        <?php echo $obs_completada ? '<i class="fas fa-edit"></i> Editar' : '<i class="fas fa-plus"></i> Agregar'; ?>
                    </a>
                    <?php if ($obs_completada): ?>
                        <a href="limpiar_boletin.php?id=<?= $boletin_id ?>&tipo=inicial&seccion=observacion" 
                           class="btn btn-danger btn-sm" 
                           onclick="return confirm('¿Estás seguro de limpiar esta observación? Se perderán los datos.')">
                            <i class="fas fa-trash-alt"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- MOMENTO 1 -->
            <div class="card-item" style="--card-color: var(--success);">
                <div class="card-header">
                    <i class="fas fa-book" style="color: var(--success);"></i>
                    <h3>Momento 1</h3>
                </div>
                <span class="estado-badge <?php echo $m1_completado ? 'estado-completado' : 'estado-pendiente'; ?>">
                    <?php echo $m1_completado ? '✓ Completado' : '⏳ Pendiente'; ?>
                </span>
                <div class="preview-texto">
                    <?php if ($m1_completado): ?>
                        Proyecto: <?php echo substr($m1_proyecto, 0, 50) . (strlen($m1_proyecto) > 50 ? '...' : ''); ?>
                    <?php else: ?>
                        <span class="vacio">Sin datos registrados</span>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <a href="editar_momento1_inicial.php?boletin_id=<?= $boletin_id ?>" class="btn <?php echo $m1_completado ? 'btn-warning' : 'btn-success'; ?> btn-sm">
                        <?php echo $m1_completado ? '<i class="fas fa-edit"></i> Editar' : '<i class="fas fa-plus"></i> Agregar'; ?>
                    </a>
                    <?php if ($m1_completado): ?>
                        <a href="limpiar_boletin.php?id=<?= $boletin_id ?>&tipo=inicial&seccion=momento1" 
                           class="btn btn-danger btn-sm" 
                           onclick="return confirm('¿Estás seguro de limpiar el Momento 1? Se perderán los datos.')">
                            <i class="fas fa-trash-alt"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- MOMENTO 2 -->
            <div class="card-item" style="--card-color: var(--info);">
                <div class="card-header">
                    <i class="fas fa-book-open" style="color: var(--info);"></i>
                    <h3>Momento 2</h3>
                </div>
                <span class="estado-badge <?php echo $m2_completado ? 'estado-completado' : 'estado-pendiente'; ?>">
                    <?php echo $m2_completado ? '✓ Completado' : '⏳ Pendiente'; ?>
                </span>
                <div class="preview-texto">
                    <?php if ($m2_completado): ?>
                        Proyecto: <?php echo substr($m2_proyecto, 0, 50) . (strlen($m2_proyecto) > 50 ? '...' : ''); ?>
                    <?php else: ?>
                        <span class="vacio">Sin datos registrados</span>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <a href="editar_momento2_inicial.php?boletin_id=<?= $boletin_id ?>" class="btn <?php echo $m2_completado ? 'btn-warning' : 'btn-info'; ?> btn-sm">
                        <?php echo $m2_completado ? '<i class="fas fa-edit"></i> Editar' : '<i class="fas fa-plus"></i> Agregar'; ?>
                    </a>
                    <?php if ($m2_completado): ?>
                        <a href="limpiar_boletin.php?id=<?= $boletin_id ?>&tipo=inicial&seccion=momento2" 
                           class="btn btn-danger btn-sm" 
                           onclick="return confirm('¿Estás seguro de limpiar el Momento 2? Se perderán los datos.')">
                            <i class="fas fa-trash-alt"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- MOMENTO 3 -->
            <div class="card-item" style="--card-color: var(--warning);">
                <div class="card-header">
                    <i class="fas fa-graduation-cap" style="color: #d39e00;"></i>
                    <h3>Momento 3</h3>
                </div>
                <span class="estado-badge <?php echo $m3_completado ? 'estado-completado' : 'estado-pendiente'; ?>">
                    <?php echo $m3_completado ? '✓ Completado' : '⏳ Pendiente'; ?>
                </span>
                <div class="preview-texto">
                    <?php if ($m3_completado): ?>
                        Proyecto: <?php echo substr($m3_proyecto, 0, 50) . (strlen($m3_proyecto) > 50 ? '...' : ''); ?>
                    <?php else: ?>
                        <span class="vacio">Sin datos registrados</span>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <a href="editar_momento3_inicial.php?boletin_id=<?= $boletin_id ?>" class="btn <?php echo $m3_completado ? 'btn-warning' : 'btn-secondary'; ?> btn-sm">
                        <?php echo $m3_completado ? '<i class="fas fa-edit"></i> Editar' : '<i class="fas fa-plus"></i> Agregar'; ?>
                    </a>
                    <?php if ($m3_completado): ?>
                        <a href="limpiar_boletin.php?id=<?= $boletin_id ?>&tipo=inicial&seccion=momento3" 
                           class="btn btn-danger btn-sm" 
                           onclick="return confirm('¿Estás seguro de limpiar el Momento 3? Se perderán los datos.')">
                            <i class="fas fa-trash-alt"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="acciones-generales">
            <?php if ($puede_generar_pdf): ?>
                <a href="generar_pdf_boletin.php?id=<?= $boletin_id ?>" target="_blank" class="btn btn-success btn-lg">
                    <i class="fas fa-file-pdf"></i> 🖨️ Generar Boletín
                </a>
            <?php else: 
                $faltantes = [];
                if (!$obs_completada) $faltantes[] = 'Observación General';
                if (!$m1_completado && !$m2_completado && !$m3_completado) $faltantes[] = 'Al menos un momento completado';
            ?>
                <div class="alerta-pendiente">
                    <strong><i class="fas fa-exclamation-triangle"></i> ¡Atención! Faltan datos para generar el boletín</strong>
                    <span class="faltantes">Faltan: <?php echo implode(', ', $faltantes); ?></span>
                    <br><small>Se requiere la observación general y al menos un momento completado.</small>
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                <a href="index.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-arrow-left"></i> Volver al Inicio
                </a>
                <a href="historial_boletines.php" class="btn btn-info btn-lg">
                    <i class="fas fa-history"></i> Ver Historial
                </a>
                <?php if ($modo_edicion): ?>
                    <a href="historial_boletines.php" class="btn btn-danger btn-lg">
                        <i class="fas fa-times"></i> Cancelar Edición
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>