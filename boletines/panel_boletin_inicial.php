<?php
session_start();
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin', 'directiva'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}

include '../includes/header.php';
require_once '../config/conexion.php';

// ========== SI VIENE CON editar_id, CARGAR DATOS DESDE LA BD ==========
if (isset($_GET['editar_id']) && is_numeric($_GET['editar_id'])) {
    $id_editar = intval($_GET['editar_id']);
    
    $stmt = $conexion->prepare("SELECT * FROM boletines WHERE id = ? AND tipo_boletin = 'inicial'");
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $boletin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($boletin) {
        $stmt_est = $conexion->prepare("SELECT e.nombre, e.apellido, e.cedula_escolar, e.sala, r.nombre_completo AS rep_nombre, p.nombre AS doc_nombre
                                        FROM estudiantes e
                                        LEFT JOIN representantes r ON e.representante_id = r.id
                                        LEFT JOIN secciones s ON e.seccion_id = s.id
                                        LEFT JOIN profesores p ON p.seccion = s.id
                                        WHERE e.id = ?");
        $stmt_est->bind_param("i", $boletin['estudiante_id']);
        $stmt_est->execute();
        $estudiante = $stmt_est->get_result()->fetch_assoc();
        $stmt_est->close();
        
        if ($estudiante) {
            $_SESSION['estudiante'] = $estudiante['nombre'] . ' ' . $estudiante['apellido'];
            $_SESSION['ce'] = $estudiante['cedula_escolar'];
            $_SESSION['grupo'] = $estudiante['sala'];
            $_SESSION['ano_escolar'] = $boletin['periodo'];
            $_SESSION['docente'] = $estudiante['doc_nombre'] ?? 'No asignado';
            $_SESSION['representante'] = $estudiante['rep_nombre'] ?? 'No registrado';
            $_SESSION['estudiante_id'] = $boletin['estudiante_id'];
            
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

// ========== SI NO HAY ESTUDIANTE EN SESIÓN, REDIRIGIR ==========
if (!isset($_SESSION['estudiante'])) {
    header('Location: paso1_portada.php?tipo=inicial');
    exit;
}

// ========== DEFINIR VARIABLES DE ESTADO ==========
$obs_completada = !empty($_SESSION['observacion']);
$m1_completado = !empty($_SESSION['m1_proyecto']) && !empty($_SESSION['m1_formacion']);
$m2_completado = !empty($_SESSION['m2_proyecto']) && !empty($_SESSION['m2_formacion']);
$m3_completado = !empty($_SESSION['m3_proyecto']) && !empty($_SESSION['m3_formacion']);

// NUEVA CONDICIÓN: observación + al menos un momento completado
$puede_generar_pdf = $obs_completada && ($m1_completado || $m2_completado || $m3_completado);

$modo_edicion = isset($_GET['editar_id']) ? true : false;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Boletín Inicial</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ===== ESTILOS (idénticos a la versión anterior, solo cambio la variable $todo_completo) ===== */
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

        .panel-container {
            padding: 20px 25px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .panel-card {
            background: #ffffff;
            border-radius: var(--radius);
            padding: 25px 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            transition: var(--transition);
            margin-top: 10px;
        }

        .panel-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 15px;
            margin-bottom: 22px;
        }

        .panel-header h2 {
            color: var(--primary);
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .panel-header h2 i {
            color: var(--primary);
        }

        .panel-header .badge-tipo {
            background: var(--primary-light);
            color: var(--primary);
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .panel-header .badge-edicion {
            background: var(--warning-light);
            color: #856404;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .estudiante-info {
            background: var(--primary-light);
            padding: 16px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 25px;
            border-left: 4px solid var(--primary);
            display: flex;
            flex-wrap: wrap;
            gap: 12px 28px;
            font-size: 14px;
            line-height: 1.8;
            color: var(--gray-700);
        }

        .estudiante-info strong {
            color: var(--primary);
            font-weight: 600;
        }

        .estudiante-info i {
            color: var(--primary);
            width: 18px;
            text-align: center;
        }

        .grid-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .card-item {
            background: #ffffff;
            border-radius: var(--radius);
            padding: 18px 20px;
            border: 1px solid var(--gray-200);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .card-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary);
            transition: var(--transition);
        }

        .card-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .card-item:hover::before {
            height: 6px;
        }

        .card-item .card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .card-item .card-header i {
            font-size: 18px;
        }

        .card-item .card-header h3 {
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-800);
            margin: 0;
        }

        .estado-badge {
            display: inline-block;
            padding: 3px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .estado-completado {
            background: var(--success-light);
            color: #155724;
        }

        .estado-pendiente {
            background: var(--warning-light);
            color: #856404;
        }

        .preview-texto {
            font-size: 13px;
            color: var(--gray-700);
            margin: 6px 0 12px;
            padding: 8px 12px;
            background: var(--gray-100);
            border-radius: var(--radius-sm);
            min-height: 38px;
            word-break: break-word;
            border: 1px solid var(--gray-200);
            line-height: 1.5;
        }

        .preview-texto .vacio {
            color: var(--gray-500);
            font-style: italic;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 18px;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            transition: var(--transition);
            text-align: center;
            line-height: 1.5;
        }

        .btn i {
            font-size: 13px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .btn:active {
            transform: translateY(0);
        }

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

        .acciones-generales {
            border-top: 2px solid var(--gray-200);
            padding-top: 22px;
            margin-top: 5px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            align-items: center;
        }

        .alerta-pendiente {
            background: var(--warning-light);
            padding: 12px 20px;
            border-radius: var(--radius-sm);
            text-align: center;
            border: 1px solid var(--warning);
            width: 100%;
            color: #856404;
        }

        .alerta-pendiente strong {
            display: block;
            font-size: 15px;
        }

        .alerta-pendiente .faltantes {
            font-size: 13px;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-item {
            animation: fadeInUp 0.5s ease forwards;
        }

        .card-item:nth-child(1) { animation-delay: 0.05s; }
        .card-item:nth-child(2) { animation-delay: 0.1s; }
        .card-item:nth-child(3) { animation-delay: 0.15s; }
        .card-item:nth-child(4) { animation-delay: 0.2s; }

        @media (max-width: 1200px) {
            .grid-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .panel-container {
                padding: 12px 15px;
            }
            .panel-card {
                padding: 18px 16px;
            }
            .panel-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .panel-header h2 {
                font-size: 19px;
            }
            .estudiante-info {
                font-size: 13px;
                padding: 12px 15px;
                gap: 8px 18px;
            }
        }

        @media (max-width: 576px) {
            .grid-cards {
                grid-template-columns: 1fr;
            }
            .acciones-generales .btn-lg {
                width: 100%;
                justify-content: center;
            }
            .panel-card {
                padding: 14px 12px;
            }
            .panel-header h2 {
                font-size: 17px;
            }
        }
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
                <span class="badge-tipo">
                    <i class="fas fa-baby"></i> Inicial
                </span>
            </div>

            <div class="estudiante-info">
                <div><i class="fas fa-user"></i> <strong>Estudiante:</strong> <?php echo htmlspecialchars($_SESSION['estudiante']); ?></div>
                <div><i class="fas fa-id-card"></i> <strong>C.E:</strong> <?php echo htmlspecialchars($_SESSION['ce']); ?></div>
                <div><i class="fas fa-users"></i> <strong>Grupo:</strong> <?php echo htmlspecialchars($_SESSION['grupo']); ?></div>
                <div><i class="fas fa-calendar-alt"></i> <strong>Año Escolar:</strong> <?php echo htmlspecialchars($_SESSION['ano_escolar']); ?></div>
                <div><i class="fas fa-chalkboard-teacher"></i> <strong>Docente:</strong> <?php echo htmlspecialchars($_SESSION['docente']); ?></div>
                <div><i class="fas fa-user-tie"></i> <strong>Representante:</strong> <?php echo htmlspecialchars($_SESSION['representante']); ?></div>
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
                            <?php echo substr(htmlspecialchars($_SESSION['observacion']), 0, 80) . (strlen($_SESSION['observacion']) > 80 ? '...' : ''); ?>
                        <?php else: ?>
                            <span class="vacio">Sin observación registrada</span>
                        <?php endif; ?>
                    </div>
                    <a href="editar_observacion_inicial.php" class="btn <?php echo $obs_completada ? 'btn-warning' : 'btn-primary'; ?> btn-sm">
                        <?php echo $obs_completada ? '<i class="fas fa-edit"></i> Editar' : '<i class="fas fa-plus"></i> Agregar'; ?>
                    </a>
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
                            Proyecto: <?php echo substr(htmlspecialchars($_SESSION['m1_proyecto']), 0, 50) . (strlen($_SESSION['m1_proyecto']) > 50 ? '...' : ''); ?>
                        <?php else: ?>
                            <span class="vacio">Sin datos registrados</span>
                        <?php endif; ?>
                    </div>
                    <a href="editar_momento1_inicial.php" class="btn <?php echo $m1_completado ? 'btn-warning' : 'btn-success'; ?> btn-sm">
                        <?php echo $m1_completado ? '<i class="fas fa-edit"></i> Editar' : '<i class="fas fa-plus"></i> Agregar'; ?>
                    </a>
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
                            Proyecto: <?php echo substr(htmlspecialchars($_SESSION['m2_proyecto']), 0, 50) . (strlen($_SESSION['m2_proyecto']) > 50 ? '...' : ''); ?>
                        <?php else: ?>
                            <span class="vacio">Sin datos registrados</span>
                        <?php endif; ?>
                    </div>
                    <a href="editar_momento2_inicial.php" class="btn <?php echo $m2_completado ? 'btn-warning' : 'btn-info'; ?> btn-sm">
                        <?php echo $m2_completado ? '<i class="fas fa-edit"></i> Editar' : '<i class="fas fa-plus"></i> Agregar'; ?>
                    </a>
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
                            Proyecto: <?php echo substr(htmlspecialchars($_SESSION['m3_proyecto']), 0, 50) . (strlen($_SESSION['m3_proyecto']) > 50 ? '...' : ''); ?>
                        <?php else: ?>
                            <span class="vacio">Sin datos registrados</span>
                        <?php endif; ?>
                    </div>
                    <a href="editar_momento3_inicial.php" class="btn <?php echo $m3_completado ? 'btn-warning' : 'btn-secondary'; ?> btn-sm">
                        <?php echo $m3_completado ? '<i class="fas fa-edit"></i> Editar' : '<i class="fas fa-plus"></i> Agregar'; ?>
                    </a>
                </div>
            </div>

            <div class="acciones-generales">
                <?php if ($puede_generar_pdf): ?>
                    <a href="generar_pdf_boletin.php" target="_blank" class="btn btn-success btn-lg">
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