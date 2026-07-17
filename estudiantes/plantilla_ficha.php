<?php
// plantilla_ficha.php - NO debe tener session_start()
// Variables esperadas: $estudiante, $inscripciones, $id, $es_pdf, $es_preview

if (!function_exists('checkbox')) {
    function checkbox($valor) {
        $marcado = ($valor === 'Si' || $valor === 'Sí') ? 'checked-box' : '';
        return '<span class="checkbox-box ' . $marcado . '"></span>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Inscripción - <?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></title>
    
    <?php if ($es_preview): ?>
    <!-- Cargar Bootstrap y FontAwesome solo en preview -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php endif; ?>

    <style>
        /* ===== ESTILOS PARA PANTALLA Y PDF ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background-color: <?= $es_preview ? '#f4f6f9' : '#fff' ?>;
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: <?= $es_preview ? '20px' : '0' ?>;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .hoja-impresion {
            background-color: #fff;
            max-width: <?= $es_preview ? '850px' : '100%' ?>;
            width: 100%;
            margin: 0 auto;
            padding: <?= $es_preview ? '40px 50px' : '0.8cm 1cm' ?>;
            box-shadow: <?= $es_preview ? '0 0 10px rgba(0,0,0,0.1)' : 'none' ?>;
            color: #000;
            font-size: 11px;
            line-height: 1.3;
            box-sizing: border-box;
        }

        /* ===== CABECERA CON FOTOS ===== */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        .header-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding-top: 5px;
        }
        .fotos-box {
            text-align: right;
            white-space: nowrap;
        }
        .foto-box {
            display: inline-block;
            width: 65px;
            height: 85px;
            border: 1.5px solid #000;
            background: #fafafa;
            margin-left: 5px;
        }

        /* ===== SECCIONES ===== */
        .seccion-titulo {
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 14px;
            margin-bottom: 6px;
            font-size: 11px;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
        }
        .fila {
            display: table;
            width: 100%;
            margin-bottom: 2px;
        }
        .grupo {
            display: table-cell;
            vertical-align: baseline;
            padding-right: 4px;
            white-space: nowrap;
        }
        .grupo label {
            font-size: 10px;
            font-weight: normal;
        }
        .dato {
            font-weight: bold;
            text-transform: uppercase;
            color: #222;
            font-size: 10px;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 30px;
            padding-bottom: 0px;
            margin-left: 2px;
        }
        .dato-line {
            display: inline-block;
            min-width: 18px;
            text-align: center;
            font-weight: bold;
        }
        .opciones-cb {
            display: inline-block;
            font-size: 12px;
            margin-left: 2px;
        }
        .checkbox-box {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            margin-right: 3px;
            vertical-align: middle;
        }
        .checked-box {
            background-color: #000;
        }

        /* ===== TABLA DE HISTORIAL ESCOLAR ===== */
        .tabla-notas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8px;
            text-align: center;
        }
        .tabla-notas th, .tabla-notas td {
            border: 1px solid #000;
            padding: 2px 1px;
            vertical-align: middle;
        }
        .tabla-notas th {
            font-weight: bold;
            background: transparent;
            font-size: 7px;
            text-transform: uppercase;
        }
        .tabla-notas td {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7px;
        }
        .tabla-notas td .dato-line {
            min-width: 12px;
        }

        /* ===== PIE DE PÁGINA ===== */
        .footer-pdf {
            text-align: center;
            font-size: 6.5px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 4px;
            margin-top: 10px;
        }

        /* ===== OCULTAR ENCABEZADO Y PIE DE PÁGINA AL IMPRIMIR ===== */
        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .hoja-impresion { 
                box-shadow: none !important; 
                padding: 0.8cm 1cm; 
                max-width: 100%; 
                border: none !important;
                margin: 0 !important;
            }
            @page { 
                margin: 1.2cm; 
                size: letter portrait;
            }
            .header-table { margin-bottom: 5px; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<?php if ($es_preview): ?>
<!-- Botones de acción alineados al diseño solicitado -->
<div class="mt-2 mb-4 d-print-none text-center">
    <button onclick="window.print()" class="btn btn-info text-white me-2">
        <i class="fas fa-print me-2"></i> Imprimir
    </button>
    <a href="generar_ficha_pdf.php?id=<?= $id ?>&download=1" class="btn btn-success me-2">
        <i class="fas fa-file-pdf me-2"></i> Descargar PDF
    </a>
    <a href="ver_ficha.php?id=<?= $id ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i> Volver
    </a>
</div>
<?php endif; ?>

<div class="hoja-impresion">
    
    <!-- ===== CABECERA CON FOTOS ===== -->
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div class="header-title">FICHA DE INSCRIPCIÓN</div>
            </td>
            <td style="width: 30%;" class="fotos-box">
                <span class="foto-box"></span>
                <span class="foto-box"></span>
            </td>
        </tr>
    </table>

    <!-- ===== DATOS DEL ALUMNO ===== -->
    <div class="seccion-titulo">DATOS DEL ALUMNO</div>
    
    <div class="fila">
        <span class="grupo" style="width:45%;"><label>Nombres y Apellidos</label> <span class="dato"><?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></span></span>
        <span class="grupo" style="width:22%;"><label>Fecha Nacimiento:</label> <span class="dato"><?= !empty($estudiante['fecha_nacimiento']) ? date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])) : '' ?></span></span>
        <span class="grupo" style="width:15%;"><label>Sexo</label> <span class="dato"><?= htmlspecialchars($estudiante['genero'] ?? '') ?></span></span>
    </div>
    
    <div class="fila">
        <span class="grupo" style="width:25%;"><label>Cédula Escolar</label> <span class="dato"><?= htmlspecialchars($estudiante['cedula_escolar'] ?? '') ?></span></span>
        <span class="grupo" style="width:25%;"><label>Nacionalidad</label> <span class="dato"><?= htmlspecialchars($estudiante['nacionalidad'] ?? '') ?></span></span>
        <span class="grupo" style="width:25%;"><label>País de Nacimiento</label> <span class="dato"><?= htmlspecialchars($estudiante['pais_nacimiento'] ?? '') ?></span></span>
    </div>
    
    <div class="fila">
        <span class="grupo" style="width:25%;"><label>Estado de Nacimiento</label> <span class="dato"><?= htmlspecialchars($estudiante['estado_nacimiento'] ?? '') ?></span></span>
        <span class="grupo" style="width:75%;"><label>Dirección:</label> <span class="dato"><?= htmlspecialchars($estudiante['direccion'] ?? '') ?></span></span>
    </div>

    <div class="fila">
        <span class="grupo" style="width:20%;"><label>Estado de Residencia</label> <span class="dato"><?= htmlspecialchars($estudiante['estado_residencia'] ?? '') ?></span></span>
        <span class="grupo" style="width:20%;"><label>Municipio</label> <span class="dato"><?= htmlspecialchars($estudiante['municipio'] ?? '') ?></span></span>
        <span class="grupo" style="width:20%;"><label>Parroquia:</label> <span class="dato"><?= htmlspecialchars($estudiante['parroquia'] ?? '') ?></span></span>
        <span class="grupo" style="width:20%;"><label>Ciudad:</label> <span class="dato"><?= htmlspecialchars($estudiante['ciudad'] ?? '') ?></span></span>
    </div>

    <div class="fila">
        <span class="grupo" style="width:22%;">
            <label>Sufre Alguna Enfermedad</label>
            <span class="opciones-cb">
                <?= checkbox($estudiante['enfermedad'] ?? '') ?> Si
                <?php $no = ($estudiante['enfermedad'] ?? '') == 'No' ? 'checked-box' : ''; ?>
                <span class="checkbox-box <?= $no ?>" style="margin-left:8px;"></span> No
            </span>
        </span>
        <span class="grupo" style="width:38%;"><label>Cual:</label> <span class="dato"><?= htmlspecialchars($estudiante['enfermedad_cual'] ?? '') ?></span></span>
        <span class="grupo" style="width:30%;">
            <label>Puede Realizar Educación Física</label>
            <span class="opciones-cb">
                <?= checkbox($estudiante['educacion_fisica'] ?? '') ?> Si
                <?php $no2 = ($estudiante['educacion_fisica'] ?? '') == 'No' ? 'checked-box' : ''; ?>
                <span class="checkbox-box <?= $no2 ?>" style="margin-left:8px;"></span> No
            </span>
        </span>
    </div>

    <div class="fila">
        <span class="grupo" style="width:30%;"><label>Porque</label> <span class="dato"><?= htmlspecialchars($estudiante['educacion_fisica_porque'] ?? '') ?></span></span>
        <span class="grupo" style="width:28%;">
            <label>Es alérgico algún medicamento:</label>
            <span class="opciones-cb">
                <?= checkbox($estudiante['alergia'] ?? '') ?> Si
                <?php $no3 = ($estudiante['alergia'] ?? '') == 'No' ? 'checked-box' : ''; ?>
                <span class="checkbox-box <?= $no3 ?>" style="margin-left:8px;"></span> No
            </span>
        </span>
        <span class="grupo" style="width:35%;"><label>Cual:</label> <span class="dato"><?= htmlspecialchars($estudiante['alergia_cual'] ?? '') ?></span></span>
    </div>

    <!-- ===== DATOS DEL REPRESENTANTE ===== -->
    <div class="seccion-titulo">DATOS DEL REPRESENTANTE</div>

    <div class="fila">
        <span class="grupo" style="width:22%;"><label>Cédula de Identidad:</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_cedula'] ?? '') ?></span></span>
        <span class="grupo" style="width:78%;"><label>Nombres y Apellidos:</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_nombre'] ?? '') ?></span></span>
    </div>

    <div class="fila">
        <span class="grupo" style="width:18%;"><label>Fecha Nacimiento</label> <span class="dato"><?= !empty($estudiante['rep_fecha_nac']) ? date('d/m/Y', strtotime($estudiante['rep_fecha_nac'])) : '' ?></span></span>
        <span class="grupo" style="width:18%;"><label>Estado Civil.</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_estado_civil'] ?? '') ?></span></span>
        <span class="grupo" style="width:18%;"><label>Afinidad:</label> <span class="dato"><?= htmlspecialchars($estudiante['afinidad'] ?? '') ?></span></span>
        <span class="grupo" style="width:22%;"><label>Teléfono:</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_telefono'] ?? '') ?></span></span>
    </div>

    <div class="fila">
        <span class="grupo" style="width:12%;"><label>Sexo</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_sexo'] ?? '') ?></span></span>
        <span class="grupo" style="width:18%;"><label>País de Nacimiento</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_pais_nac'] ?? '') ?></span></span>
        <span class="grupo" style="width:18%;"><label>Estado de Nacimiento:</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_estado_nac'] ?? '') ?></span></span>
        <span class="grupo" style="width:18%;"><label>Nacionalidad:</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_nacionalidad'] ?? '') ?></span></span>
    </div>

    <div class="fila">
        <span class="grupo" style="width:35%;"><label>Dirección</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_direccion'] ?? '') ?></span></span>
        <span class="grupo" style="width:18%;"><label>Estado de Residencia.</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_estado_res'] ?? '') ?></span></span>
        <span class="grupo" style="width:18%;"><label>Municipio</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_municipio'] ?? '') ?></span></span>
    </div>

    <div class="fila">
        <span class="grupo" style="width:18%;"><label>Parroquia</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_parroquia'] ?? '') ?></span></span>
        <span class="grupo" style="width:18%;"><label>Ciudad</label> <span class="dato"><?= htmlspecialchars($estudiante['rep_ciudad'] ?? '') ?></span></span>
    </div>

    <!-- ===== DATOS DE LOS PADRES ===== -->
    <div class="seccion-titulo">DATOS DE LOS PADRES</div>

    <div class="fila">
        <span class="grupo" style="width:38%;"><label>Nombres y Apellidos de la Madre</label> <span class="dato"><?= htmlspecialchars($estudiante['madre_nombre'] ?? '') ?></span></span>
        <span class="grupo" style="width:18%;"><label>Cédula</label> <span class="dato"><?= htmlspecialchars($estudiante['madre_cedula'] ?? '') ?></span></span>
        <span class="grupo" style="width:18%;"><label>Teléfono</label> <span class="dato"><?= htmlspecialchars($estudiante['madre_telefono'] ?? '') ?></span></span>
    </div>

    <div class="fila">
        <span class="grupo" style="width:38%;"><label>Nombre y Apellido del Padre</label> <span class="dato"><?= htmlspecialchars($estudiante['padre_nombre'] ?? '') ?></span></span>
        <span class="grupo" style="width:18%;"><label>Cédula</label> <span class="dato"><?= htmlspecialchars($estudiante['padre_cedula'] ?? '') ?></span></span>
        <span class="grupo" style="width:18%;"><label>Teléfono</label> <span class="dato"><?= htmlspecialchars($estudiante['padre_telefono'] ?? '') ?></span></span>
    </div>

    <!-- ===== HISTORIAL ESCOLAR (CON FUNCIONARIO VACÍO) ===== -->
    <table class="tabla-notas">
        <thead>
            <tr>
                <th style="width:11%;">Año Escolar</th>
                <th style="width:11%;">Grado y<br>Sección</th>
                <th style="width:5%;">Reg.</th>
                <th style="width:5%;">Rep</th>
                <th style="width:5%;">C</th>
                <th style="width:5%;">F</th>
                <th style="width:5%;">P</th>
                <th style="width:6%;">Peso</th>
                <th style="width:6%;">Talla</th>
                <th style="width:12%;">Firma<br>Representante</th>
                <th style="width:10%;">Fecha<br>Inscripción</th>
                <th style="width:10%;">Funcionario</th>
            </tr>
        </thead>
        <tbody>
            <?php for($i = 0; $i < 8; $i++): ?>
                <?php 
                $ins = isset($inscripciones[$i]) ? $inscripciones[$i] : null; 
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
                    <td></td>
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

    <div class="footer-pdf">
        Documento generado por el Sistema de Gestión Educativa - U.E.B.N. Juan Pablo Pérez Alfonzo<br>
        Fecha: <?= date('d/m/Y H:i:s') ?>
    </div>

</div>

</body>
</html>