<?php
session_start();
if (!isset($_SESSION['estudiante'])) {
    header('Location: paso1_portada_primaria.php?tipo=primaria');
    exit;
}

include '../includes/header.php';
require_once '../config/conexion.php';

$estudiante_id = $_SESSION['estudiante_id'];
$periodo = $_SESSION['ano_escolar'] ?? '2025 / 2026';

// Verificar si ya existen datos guardados
$stmt = $conexion->prepare("SELECT * FROM boletines WHERE estudiante_id = ? AND periodo = ? AND tipo_boletin = 'primaria'");
$stmt->bind_param("is", $estudiante_id, $periodo);
$stmt->execute();
$boletin_existente = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Cargar datos a la sesión si existen
if ($boletin_existente) {
    $_SESSION['observacion'] = $boletin_existente['observacion'] ?? '';
    
    // Lapso 1
    $_SESSION['l1_proyecto'] = $boletin_existente['m1_proyecto'] ?? '';
    $_SESSION['l1_analisis'] = $boletin_existente['m1_formacion'] ?? '';
    $_SESSION['l1_sugerencias'] = $boletin_existente['m1_sugerencias'] ?? '';
    
    // Lapso 2
    $_SESSION['l2_proyecto'] = $boletin_existente['m2_proyecto'] ?? '';
    $_SESSION['l2_analisis'] = $boletin_existente['m2_formacion'] ?? '';
    $_SESSION['l2_sugerencias'] = $boletin_existente['m2_sugerencias'] ?? '';
    
    // Lapso 3
    $_SESSION['l3_proyecto'] = $boletin_existente['m3_proyecto'] ?? '';
    $_SESSION['l3_analisis'] = $boletin_existente['m3_formacion'] ?? '';
    $_SESSION['l3_sugerencias'] = $boletin_existente['m3_sugerencias'] ?? '';
    
    // Resultado Final
    $_SESSION['resultado_final'] = $boletin_existente['resultado_final'] ?? '';
    $_SESSION['literal_final'] = $boletin_existente['literal_final'] ?? '';
}

// Definir variables de estado
$obs_completada = !empty($_SESSION['observacion']);
$l1_completado = !empty($_SESSION['l1_proyecto']) && !empty($_SESSION['l1_analisis']);
$l2_completado = !empty($_SESSION['l2_proyecto']) && !empty($_SESSION['l2_analisis']);
$l3_completado = !empty($_SESSION['l3_proyecto']) && !empty($_SESSION['l3_analisis']) && !empty($_SESSION['resultado_final']);
$todo_completo = $obs_completada && $l1_completado && $l2_completado && $l3_completado;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Boletín Primaria</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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

        .panel-header .badge-tipo {
            background: var(--primary-light);
            color: var(--primary);
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

        /* ======== ESTILOS PARA EL PREVIEW DE LAPSO ======== */
        .lapso-preview {
            font-size: 12px;
            line-height: 1.4;
            margin: 4px 0 10px 0;
            padding: 6px 10px;
            background: var(--gray-100);
            border-radius: var(--radius-sm);
            border: 1px solid var(--gray-200);
            min-height: 40px;
        }
        .lapso-preview .label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .lapso-preview .texto {
            color: var(--gray-800);
            margin-top: 2px;
            word-break: break-word;
        }
        .lapso-preview .vacio {
            color: var(--gray-500);
            font-style: italic;
        }
        .lapso-preview .separador {
            border-top: 0.5px solid var(--gray-300);
            margin: 4px 0;
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
        }
    </style>
</head>
<body>
    <div class="panel-container">
        <div class="panel-card">
            <div class="panel-header">
                <h2>
                    <i class="fas fa-clipboard-list"></i>
                    Panel de Control - Boletín Primaria
                </h2>
                <span class="badge-tipo">
                    <i class="fas fa-child"></i> Primaria
                </span>
            </div>
            
            <div class="estudiante-info">
                <div><i class="fas fa-user"></i> <strong>Estudiante:</strong> <?php echo htmlspecialchars($_SESSION['estudiante']); ?></div>
                <div><i class="fas fa-id-card"></i> <strong>C.E:</strong> <?php echo htmlspecialchars($_SESSION['ce']); ?></div>
                <div><i class="fas fa-users"></i> <strong>Grado:</strong> <?php echo htmlspecialchars($_SESSION['grado']); ?></div>
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
                        <?php echo $obs_completada ? 'Completada' : 'Pendiente'; ?>
                    </span>
                    <div class="preview-texto">
                        <?php if ($obs_completada): ?>
                            <?php echo substr(htmlspecialchars($_SESSION['observacion']), 0, 80) . (strlen($_SESSION['observacion']) > 80 ? '...' : ''); ?>
                        <?php else: ?>
                            <span class="vacio">Sin observación registrada</span>
                        <?php endif; ?>
                    </div>
                    <a href="editar_observacion_primaria.php" class="btn <?php echo $obs_completada ? 'btn-warning' : 'btn-primary'; ?> btn-sm">
                        <?php echo $obs_completada ? '<i class="fas fa-edit"></i> Editar' : '<i class="fas fa-plus"></i> Agregar'; ?>
                    </a>
                </div>
                
                <!-- LAPSO 1 -->
                <div class="card-item" style="--card-color: var(--success);">
                    <div class="card-header">
                        <i class="fas fa-book" style="color: var(--success);"></i>
                        <h3>Lapso 1</h3>
                    </div>
                    <span class="estado-badge <?php echo $l1_completado ? 'estado-completado' : 'estado-pendiente'; ?>">
                        <?php echo $l1_completado ? 'Completado' : 'Pendiente'; ?>
                    </span>
                    <div class="lapso-preview">
                        <div class="label">PROYECTOS DE APRENDIZAJES</div>
                        <div class="texto"><?php echo !empty($_SESSION['l1_proyecto']) ? htmlspecialchars($_SESSION['l1_proyecto']) : '<span class="vacio">Sin datos</span>'; ?></div>
                        
                        <div class="separador"></div>
                        
                        <div class="label">ANÁLISIS CUALITATIVO</div>
                        <div class="texto"><?php echo !empty($_SESSION['l1_analisis']) ? htmlspecialchars($_SESSION['l1_analisis']) : '<span class="vacio">Sin datos</span>'; ?></div>
                        
                        <div class="separador"></div>
                        
                        <div class="label">SUGERENCIAS</div>
                        <div class="texto"><?php echo !empty($_SESSION['l1_sugerencias']) ? htmlspecialchars($_SESSION['l1_sugerencias']) : '<span class="vacio">Sin datos</span>'; ?></div>
                    </div>
                    <a href="editar_lapso1_primaria.php" class="btn <?php echo $l1_completado ? 'btn-warning' : 'btn-success'; ?> btn-sm">
                        <?php echo $l1_completado ? '<i class="fas fa-edit"></i> Editar' : '<i class="fas fa-plus"></i> Agregar'; ?>
                    </a>
                </div>
                
                <!-- LAPSO 2 -->
                <div class="card-item" style="--card-color: var(--info);">
                    <div class="card-header">
                        <i class="fas fa-book-open" style="color: var(--info);"></i>
                        <h3>Lapso 2</h3>
                    </div>
                    <span class="estado-badge <?php echo $l2_completado ? 'estado-completado' : 'estado-pendiente'; ?>">
                        <?php echo $l2_completado ? 'Completado' : 'Pendiente'; ?>
                    </span>
                    <div class="lapso-preview">
                        <div class="label">PROYECTOS DE APRENDIZAJES</div>
                        <div class="texto"><?php echo !empty($_SESSION['l2_proyecto']) ? htmlspecialchars($_SESSION['l2_proyecto']) : '<span class="vacio">Sin datos</span>'; ?></div>
                        
                        <div class="separador"></div>
                        
                        <div class="label">ANÁLISIS CUALITATIVO</div>
                        <div class="texto"><?php echo !empty($_SESSION['l2_analisis']) ? htmlspecialchars($_SESSION['l2_analisis']) : '<span class="vacio">Sin datos</span>'; ?></div>
                        
                        <div class="separador"></div>
                        
                        <div class="label">SUGERENCIAS</div>
                        <div class="texto"><?php echo !empty($_SESSION['l2_sugerencias']) ? htmlspecialchars($_SESSION['l2_sugerencias']) : '<span class="vacio">Sin datos</span>'; ?></div>
                    </div>
                    <a href="editar_lapso2_primaria.php" class="btn <?php echo $l2_completado ? 'btn-warning' : 'btn-info'; ?> btn-sm">
                        <?php echo $l2_completado ? '<i class="fas fa-edit"></i> Editar' : '<i class="fas fa-plus"></i> Agregar'; ?>
                    </a>
                </div>
                
                <!-- LAPSO 3 + RESULTADO -->
                <div class="card-item" style="--card-color: var(--warning);">
                    <div class="card-header">
                        <i class="fas fa-graduation-cap" style="color: #d39e00;"></i>
                        <h3>Lapso 3 + Resultado</h3>
                    </div>
                    <span class="estado-badge <?php echo $l3_completado ? 'estado-completado' : 'estado-pendiente'; ?>">
                        <?php echo $l3_completado ? 'Completado' : 'Pendiente'; ?>
                    </span>
                    <div class="lapso-preview">
                        <div class="label">PROYECTOS DE APRENDIZAJES</div>
                        <div class="texto"><?php echo !empty($_SESSION['l3_proyecto']) ? htmlspecialchars($_SESSION['l3_proyecto']) : '<span class="vacio">Sin datos</span>'; ?></div>
                        
                        <div class="separador"></div>
                        
                        <div class="label">ANÁLISIS CUALITATIVO</div>
                        <div class="texto"><?php echo !empty($_SESSION['l3_analisis']) ? htmlspecialchars($_SESSION['l3_analisis']) : '<span class="vacio">Sin datos</span>'; ?></div>
                        
                        <div class="separador"></div>
                        
                        <div class="label">SUGERENCIAS</div>
                        <div class="texto"><?php echo !empty($_SESSION['l3_sugerencias']) ? htmlspecialchars($_SESSION['l3_sugerencias']) : '<span class="vacio">Sin datos</span>'; ?></div>
                        
                        <div class="separador"></div>
                        
                        <div class="label">RESULTADO FINAL</div>
                        <div class="texto">
                            <?php if (!empty($_SESSION['resultado_final'])): ?>
                                <strong><?php echo htmlspecialchars($_SESSION['resultado_final']); ?></strong> (Literal <?php echo htmlspecialchars($_SESSION['literal_final'] ?? 'N/A'); ?>)
                            <?php else: ?>
                                <span class="vacio">Sin resultado registrado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="editar_lapso3_primaria.php" class="btn <?php echo $l3_completado ? 'btn-warning' : 'btn-secondary'; ?> btn-sm">
                        <?php echo $l3_completado ? '<i class="fas fa-edit"></i> Editar' : '<i class="fas fa-plus"></i> Agregar'; ?>
                    </a>
                </div>
            </div>
            
            <div class="acciones-generales">
                <?php if ($todo_completo): ?>
                    <a href="generar_pdf_boletin_primaria.php" target="_blank" class="btn btn-success btn-lg">
                        <i class="fas fa-file-pdf"></i> Generar Boletín Completo
                    </a>
                <?php else: 
                    $faltantes = [];
                    if (!$obs_completada) $faltantes[] = 'Observación General';
                    if (!$l1_completado) $faltantes[] = 'Lapso 1';
                    if (!$l2_completado) $faltantes[] = 'Lapso 2';
                    if (!$l3_completado) $faltantes[] = 'Lapso 3 + Resultado Final';
                ?>
                    <div class="alerta-pendiente">
                        <strong><i class="fas fa-exclamation-triangle"></i> ¡Atención! Faltan datos para generar el boletín</strong>
                        <span class="faltantes">Faltan: <?php echo implode(', ', $faltantes); ?></span>
                    </div>
                <?php endif; ?>
                
                <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                    <a href="index.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-left"></i> Volver al Inicio
                    </a>
                    <a href="historial_boletines.php" class="btn btn-info btn-lg">
                        <i class="fas fa-history"></i> Ver Historial
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>
</body>
</html>