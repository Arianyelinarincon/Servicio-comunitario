<?php
session_start();
require_once '../config/conexion.php';
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'super_admin'])) {
    header("Location: /servicio-comunitario/profesores/Login/login.php");
    exit();
}
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: listado.php");
    exit();
}

// Obtener datos del estudiante y representante
$stmt = $conexion->prepare("
    SELECT e.*, 
        r.nombre_completo AS rep_nombre, r.cedula AS rep_cedula, r.telefono AS rep_telefono,
        r.fecha_nacimiento AS rep_fecha_nac, r.estado_civil AS rep_estado_civil, r.afinidad,
        r.sexo AS rep_sexo, r.pais_nacimiento AS rep_pais_nac, r.estado_nacimiento AS rep_estado_nac,
        r.nacionalidad AS rep_nacionalidad, r.direccion AS rep_direccion,
        r.estado_residencia AS rep_estado_res, r.municipio AS rep_municipio,
        r.parroquia AS rep_parroquia, r.ciudad AS rep_ciudad
    FROM estudiantes e 
    LEFT JOIN representantes r ON e.representante_id = r.id 
    WHERE e.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    header("Location: listado.php");
    exit();
}

// Obtener historial escolar
$stmt_ins = $conexion->prepare("SELECT * FROM inscripciones WHERE estudiante_id = ? ORDER BY ano_escolar DESC");
$stmt_ins->bind_param("i", $id);
$stmt_ins->execute();
$inscripciones = $stmt_ins->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_ins->close();

// Obtener sección y docente
$seccion_nombre = '';
$docente_nombre = '';
if (!empty($estudiante['seccion_id'])) {
    $stmt_sec = $conexion->prepare("
        SELECT s.nombre AS seccion_nombre, p.nombre AS docente_nombre
        FROM secciones s
        LEFT JOIN profesores p ON p.seccion = s.id
        WHERE s.id = ?
        LIMIT 1
    ");
    $stmt_sec->bind_param("i", $estudiante['seccion_id']);
    $stmt_sec->execute();
    $sec_data = $stmt_sec->get_result()->fetch_assoc();
    if ($sec_data) {
        $seccion_nombre = $sec_data['seccion_nombre'] ?? '';
        $docente_nombre = $sec_data['docente_nombre'] ?? '';
    }
    $stmt_sec->close();
}

// Función checkbox
function checkbox($valor, $opcion) {
    $checked = ($valor === 'Si' || $valor === 'Sí') ? '☑' : '☐';
    return $checked;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Inscripción - <?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ===== ESTILOS PARA PANTALLA E IMPRESIÓN ===== */
        body {
            background-color: #f4f6f9;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .hoja-impresion {
            background-color: #fff;
            max-width: 850px;
            margin: 0 auto;
            padding: 40px 50px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            color: #000;
            font-size: 13px;
            line-height: 1.4;
        }
        
        /* ===== CABECERA CON FOTOS (CORREGIDA) ===== */
        .header-container {
            position: relative;
            text-align: center;
            margin-bottom: 1.5px;
            padding-top: 10px;
            min-height: 120px;
        }
        .titulo {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .fotos-container {
            position: absolute;
            top: 0;
            right: 0;
            display: flex;
            gap: 10px;
        }
        .foto-box {
            width: 80px;
            height: 100px;
            border: 1.5px solid #000;
            background: #fafafa;
        }

        .seccion-titulo {
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 12px;
            border-bottom: 1px solid #333;
            padding-bottom: 4px;
        }
        .fila {
            display: flex;
            align-items: baseline;
            margin-bottom: 8px;
            flex-wrap: wrap;
            gap: 5px;
        }
        .grupo {
            display: flex;
            align-items: baseline;
            flex-grow: 1;
            white-space: nowrap;
            min-width: 120px;
        }
        .grupo label {
            margin-right: 5px;
            font-size: 13px;
        }
        .dato {
            flex-grow: 1;
            font-weight: bold;
            text-transform: uppercase;
            padding-left: 5px;
            color: #222;
        }
        .dato-line {
            display: inline-block;
            min-width: 25px;
            text-align: center;
            font-weight: bold;
        }
        .opciones-cb {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: 5px;
            font-size: 14px;
        }
        .opciones-cb .cb {
            font-size: 16px;
            margin-right: 2px;
        }
        .tabla-notas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            text-align: center;
            font-size: 11px;
        }
        .tabla-notas th, .tabla-notas td {
            border: 1px solid #000;
            padding: 6px 4px;
        }
        .tabla-notas th {
            font-weight: bold;
            background: transparent;
        }
        .tabla-notas td {
            font-weight: bold;
            text-transform: uppercase;
        }
        .tabla-notas td .dato-line {
            min-width: 20px;
        }

        /* ===== OCULTAR ENCABEZADO Y PIE DE PÁGINA AL IMPRIMIR ===== */
        @media print {
            body { background-color: #fff; padding: 0; margin: 0; }
            .hoja-impresion { box-shadow: none; padding: 20px 30px; margin: 0; width: 100%; max-width: 100%; }
            .no-print { display: none !important; }
            /* Eliminar encabezados y pies de página del navegador */
            @page { 
                margin: 1.5cm; 
                size: letter portrait;
            }
            /* Ocultar la URL y fecha que añade el navegador */
            .header-container { margin-bottom: 20px; }
            .fotos-container { top: 0; right: 0; }
        }
    </style>
</head>
<body>

<div class="container text-center no-print mb-4">
    <button class="btn btn-primary shadow-sm px-4 py-2 me-2" onclick="window.print()">
        <i class="fas fa-print me-2"></i> Imprimir Ficha
    </button>
    <a href="generar_ficha_pdf.php?id=<?= $id ?>&seccion=<?= urlencode($seccion_nombre) ?>&docente=<?= urlencode($docente_nombre) ?>" class="btn btn-success shadow-sm px-4 py-2 me-2" download>
        <i class="fas fa-file-pdf me-2"></i> Descargar PDF
    </a>
    <a href="listado.php" class="btn btn-secondary shadow-sm px-4 py-2">
        <i class="fas fa-arrow-left me-2"></i> Volver
    </a>
</div>

<div class="hoja-impresion">
    
    <div class="header-container">
        <div class="fotos-container">
            <div class="foto-box"></div>
            <div class="foto-box"></div>
        </div>
        <div class="titulo">FICHA DE INSCRIPCIÓN</div>
    </div>

    <!-- ===== DATOS DEL ALUMNO ===== -->
    <div class="seccion-titulo">DATOS DEL ALUMNO</div>
    
    <div class="fila">
        <div class="grupo" style="flex-grow: 3;">
            <label>Nombres y Apellidos</label>
            <div class="dato"><?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></div>
        </div>
        <div class="grupo" style="flex-grow: 1;">
            <label>Fecha Nacimiento:</label>
            <div class="dato"><?= !empty($estudiante['fecha_nacimiento']) ? date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])) : '' ?></div>
        </div>
        <div class="grupo" style="flex-grow: 1;">
            <label>Sexo</label>
            <div class="dato"><?= htmlspecialchars($estudiante['genero'] ?? '') ?></div>
        </div>
    </div>
    
    <div class="fila">
        <div class="grupo">
            <label>Cédula Escolar</label>
            <div class="dato"><?= htmlspecialchars($estudiante['cedula_escolar'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Nacionalidad</label>
            <div class="dato"><?= htmlspecialchars($estudiante['nacionalidad'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>País de Nacimiento</label>
            <div class="dato"><?= htmlspecialchars($estudiante['pais_nacimiento'] ?? '') ?></div>
        </div>
    </div>
    
    <div class="fila">
        <div class="grupo" style="flex-grow: 1;">
            <label>Estado de Nacimiento</label>
            <div class="dato"><?= htmlspecialchars($estudiante['estado_nacimiento'] ?? '') ?></div>
        </div>
        <div class="grupo" style="flex-grow: 3;">
            <label>Dirección:</label>
            <div class="dato"><?= htmlspecialchars($estudiante['direccion'] ?? '') ?></div>
        </div>
    </div>

    <div class="fila">
        <div class="grupo">
            <label>Estado de Residencia</label>
            <div class="dato"><?= htmlspecialchars($estudiante['estado_residencia'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Municipio</label>
            <div class="dato"><?= htmlspecialchars($estudiante['municipio'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Parroquia:</label>
            <div class="dato"><?= htmlspecialchars($estudiante['parroquia'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Ciudad:</label>
            <div class="dato"><?= htmlspecialchars($estudiante['ciudad'] ?? '') ?></div>
        </div>
    </div>

    <div class="fila">
        <div class="grupo">
            <label>Sufre Alguna Enfermedad</label>
            <span class="opciones-cb">
                <span class="cb"><?= checkbox($estudiante['enfermedad'] ?? '', 'Si') ?></span> Si
                <span class="cb" style="margin-left:8px;"><?= ($estudiante['enfermedad'] ?? '') == 'No' ? '☑' : '☐' ?></span> No
            </span>
        </div>
        <div class="grupo" style="flex-grow: 2;">
            <label>Cual:</label>
            <div class="dato"><?= htmlspecialchars($estudiante['enfermedad_cual'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Puede Realizar Educación Física</label>
            <span class="opciones-cb">
                <span class="cb"><?= checkbox($estudiante['educacion_fisica'] ?? '', 'Si') ?></span> Si
                <span class="cb" style="margin-left:8px;"><?= ($estudiante['educacion_fisica'] ?? '') == 'No' ? '☑' : '☐' ?></span> No
            </span>
        </div>
    </div>

    <div class="fila">
        <div class="grupo" style="flex-grow: 2;">
            <label>Porque</label>
            <div class="dato"><?= htmlspecialchars($estudiante['educacion_fisica_porque'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Es alérgico algún medicamento:</label>
            <span class="opciones-cb">
                <span class="cb"><?= checkbox($estudiante['alergia'] ?? '', 'Si') ?></span> Si
                <span class="cb" style="margin-left:8px;"><?= ($estudiante['alergia'] ?? '') == 'No' ? '☑' : '☐' ?></span> No
            </span>
        </div>
        <div class="grupo" style="flex-grow: 2;">
            <label>Cual:</label>
            <div class="dato"><?= htmlspecialchars($estudiante['alergia_cual'] ?? '') ?></div>
        </div>
    </div>

    <!-- ===== DATOS DEL REPRESENTANTE ===== -->
    <div class="seccion-titulo">DATOS DEL REPRESENTANTE</div>

    <div class="fila">
        <div class="grupo">
            <label>Cédula de Identidad:</label>
            <div class="dato"><?= htmlspecialchars($estudiante['rep_cedula'] ?? '') ?></div>
        </div>
        <div class="grupo" style="flex-grow: 3;">
            <label>Nombres y Apellidos:</label>
            <div class="dato"><?= htmlspecialchars($estudiante['rep_nombre'] ?? '') ?></div>
        </div>
    </div>

    <div class="fila">
        <div class="grupo">
            <label>Fecha Nacimiento</label>
            <div class="dato"><?= !empty($estudiante['rep_fecha_nac']) ? date('d/m/Y', strtotime($estudiante['rep_fecha_nac'])) : '' ?></div>
        </div>
        <div class="grupo">
            <label>Estado Civil.</label>
            <div class="dato"><?= htmlspecialchars($estudiante['rep_estado_civil'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Afinidad:</label>
            <div class="dato"><?= htmlspecialchars($estudiante['afinidad'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Teléfono:</label>
            <div class="dato"><?= htmlspecialchars($estudiante['rep_telefono'] ?? '') ?></div>
        </div>
    </div>

    <div class="fila">
        <div class="grupo" style="flex-grow: 0;">
            <label>Sexo</label>
            <div class="dato"><?= htmlspecialchars($estudiante['rep_sexo'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>País de Nacimiento</label>
            <div class="dato"><?= htmlspecialchars($estudiante['rep_pais_nac'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Estado de Nacimiento:</label>
            <div class="dato"><?= htmlspecialchars($estudiante['rep_estado_nac'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Nacionalidad:</label>
            <div class="dato"><?= htmlspecialchars($estudiante['rep_nacionalidad'] ?? '') ?></div>
        </div>
    </div>

    <div class="fila">
        <div class="grupo" style="flex-grow: 2;">
            <label>Dirección</label>
            <div class="dato"><?= htmlspecialchars($estudiante['rep_direccion'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Estado de Residencia.</label>
            <div class="dato"><?= htmlspecialchars($estudiante['rep_estado_res'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Municipio</label>
            <div class="dato"><?= htmlspecialchars($estudiante['rep_municipio'] ?? '') ?></div>
        </div>
    </div>

    <div class="fila">
        <div class="grupo">
            <label>Parroquia</label>
            <div class="dato"><?= htmlspecialchars($estudiante['rep_parroquia'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Ciudad</label>
            <div class="dato"><?= htmlspecialchars($estudiante['rep_ciudad'] ?? '') ?></div>
        </div>
    </div>

    <!-- ===== DATOS DE LOS PADRES ===== -->
    <div class="seccion-titulo">DATOS DE LOS PADRES</div>

    <div class="fila">
        <div class="grupo" style="flex-grow: 2;">
            <label>Nombres y Apellidos de la Madre</label>
            <div class="dato"><?= htmlspecialchars($estudiante['madre_nombre'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Cédula</label>
            <div class="dato"><?= htmlspecialchars($estudiante['madre_cedula'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Teléfono</label>
            <div class="dato"><?= htmlspecialchars($estudiante['madre_telefono'] ?? '') ?></div>
        </div>
    </div>

    <div class="fila">
        <div class="grupo" style="flex-grow: 2;">
            <label>Nombre y Apellido del Padre</label>
            <div class="dato"><?= htmlspecialchars($estudiante['padre_nombre'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Cédula</label>
            <div class="dato"><?= htmlspecialchars($estudiante['padre_cedula'] ?? '') ?></div>
        </div>
        <div class="grupo">
            <label>Teléfono</label>
            <div class="dato"><?= htmlspecialchars($estudiante['padre_telefono'] ?? '') ?></div>
        </div>
    </div>

    <!-- ===== HISTORIAL ESCOLAR ===== -->
    <table class="tabla-notas">
        <thead>
            <tr>
                <th>Año Escolar</th>
                <th>Grado y<br>Sección</th>
                <th>Reg.</th>
                <th>Rep</th>
                <th>C</th>
                <th>F</th>
                <th>P</th>
                <th>Peso</th>
                <th>Talla</th>
                <th>Firma<br>Representante</th>
                <th>Fecha<br>Inscripción</th>
                <th>Funcionario</th>
            </tr>
        </thead>
        <tbody>
            <?php for($i = 0; $i < 8; $i++): ?>
                <?php 
                $ins = $inscripciones[$i] ?? null; 
                if ($ins):
                    $anos = explode('-', $ins['ano_escolar']);
                    $ano1 = isset($anos[0]) ? substr(trim($anos[0]), -2) : '';
                    $ano2 = isset($anos[1]) ? substr(trim($anos[1]), -2) : '';
                ?>
                <tr>
                    <td>20<span class="dato-line"><?= $ano1 ?></span> - 20<span class="dato-line"><?= $ano2 ?></span></td>
                    <td><?= htmlspecialchars($ins['grado_seccion'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['registro'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['repite'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['c'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['f'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['p'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['peso'] ?? '') ?></td>
                    <td><?= htmlspecialchars($ins['talla'] ?? '') ?></td>
                    <td></td>
                    <td><?= !empty($ins['fecha_inscripcion']) ? date('d/m/Y', strtotime($ins['fecha_inscripcion'])) : '' ?></td>
                    <td><?= htmlspecialchars($ins['funcionario'] ?? '') ?></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td>20<span class="dato-line"></span> - 20<span class="dato-line"></span></td>
                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
                <?php endif; ?>
            <?php endfor; ?>
        </tbody>
    </table>

</div>

</body>
</html>