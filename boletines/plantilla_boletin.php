<?php
session_start();
if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
        $_SESSION[$key] = $value;
    }
}

$estudiante = htmlspecialchars($_SESSION['estudiante'] ?? '');
$ce = htmlspecialchars($_SESSION['ce'] ?? '');
$grupo = htmlspecialchars($_SESSION['grupo'] ?? '');
$ano_escolar = htmlspecialchars($_SESSION['ano_escolar'] ?? '');
$docente = htmlspecialchars($_SESSION['docente'] ?? '');
$representante = htmlspecialchars($_SESSION['representante'] ?? '');

$observacion = nl2br(htmlspecialchars($_SESSION['observacion'] ?? ''));

$m1_proy = htmlspecialchars($_SESSION['m1_proyecto'] ?? '');
$m1_form = nl2br(htmlspecialchars($_SESSION['m1_formacion'] ?? ''));
$m1_rel = nl2br(htmlspecialchars($_SESSION['m1_relacion'] ?? ''));
$m1_sug = nl2br(htmlspecialchars($_SESSION['m1_sugerencias'] ?? ''));

$m2_proy = htmlspecialchars($_SESSION['m2_proyecto'] ?? '');
$m2_form = nl2br(htmlspecialchars($_SESSION['m2_formacion'] ?? ''));
$m2_rel = nl2br(htmlspecialchars($_SESSION['m2_relacion'] ?? ''));
$m2_sug = nl2br(htmlspecialchars($_SESSION['m2_sugerencias'] ?? ''));

$m3_proy = htmlspecialchars($_SESSION['m3_proyecto'] ?? '');
$m3_form = nl2br(htmlspecialchars($_SESSION['m3_formacion'] ?? ''));
$m3_rel = nl2br(htmlspecialchars($_SESSION['m3_relacion'] ?? ''));
$m3_sug = nl2br(htmlspecialchars($_SESSION['m3_sugerencias'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Imprimir Boletín</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e0e0e0; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; font-size: 11px; }
        .controles { background: white; padding: 15px; width: 279mm; margin-bottom: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; }
        .btn-imprimir { background: rgb(26, 35, 126); color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 14px; font-weight: bold; border-radius: 4px; }
        .ocultar-impresion { width: 100%; }

        /* Estilo para la pantalla (Vista Previa) */
        .hoja {
            background: white; width: 279mm; height: 215.9mm; display: flex; box-sizing: border-box;
            padding: 10mm; box-shadow: 0 4px 8px rgba(0,0,0,0.2); margin-bottom: 20mm;
        }
        
        .columna { flex: 1; padding: 0 15px; box-sizing: border-box; display: flex; flex-direction: column; }
        .col-borde { border-right: 1px dashed transparent; }

        .texto-centrado { text-align: center; }
        .texto-justificado { text-align: justify; }
        .negrita { font-weight: bold; }
        .linea-texto { border-bottom: 1px solid black; display: inline-block; min-width: 40px; padding-left: 5px; }
        .bloque-texto { min-height: 80px; margin-top: 5px; margin-bottom: 15px; }
        .espacio-firmas { display: flex; justify-content: space-between; margin-top: auto; padding-top: 20px; font-size: 10px; }
        .firma { text-align: center; border-top: 1px solid black; width: 45%; padding-top: 5px; }
        .firma-larga { text-align: center; border-top: 1px solid black; width: 80%; margin: 0 auto; padding-top: 5px; margin-top: 30px; }

        .linea-lateral { border-left: 2px solid #000; padding-left: 10px; margin-bottom: 10px; }
        /* ======== CONFIGURACIÓN CRÍTICA DE IMPRESIÓN ======== */
        @media print {
            @page { 
                size: letter landscape; 
                margin: 0; /* Anula los márgenes por defecto del navegador */
            }
            
            html, body { 
                background: white; 
                margin: 0; 
                padding: 0; 
                display: block; /* Quita el flex del body que rompe la impresión */
                width: 100%;
                height: 100%;
            }
            
            .controles, .ocultar-impresion { 
                display: none !important; 
            }
            
            .hoja { 
                box-shadow: none; 
                margin: 0; 
                border: none; 
                width: 100vw; /* Usa el 100% del ancho de la página impresa */
                height: 100vh; /* Usa el 100% del alto de la página impresa */
                max-height: 100vh;
                padding: 8mm; /* Un poco menos de 10mm para evitar que los bordes de la impresora lo corten */
                box-sizing: border-box;
                display: flex;
                page-break-after: always; 
                page-break-inside: avoid;
                overflow: hidden; /* Fuerza a que nada sobresalga y cree hojas en blanco */
            }
            
            /* Evita que se imprima una tercera hoja en blanco al final */
            .hoja:last-of-type {
                page-break-after: auto;
            }

            /* Garantiza que los colores oscuros se impriman nítidos */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <div class="ocultar-impresion">
        <?php include '../includes/header.php'; ?>
    </div>
    
    <div class="controles">
        <button class="btn-imprimir" onclick="window.print()">IMPRIMIR BOLETÍN</button>
        <br><br>
        <a href="paso1_portada.php" style="color: rgb(26,35,126); text-decoration: none; font-weight: bold;">Crear otro boletín</a>
    </div>

    <div class="hoja">
        <div class="columna col-borde">
            <div style="margin-bottom: 60px;">
                <span class="negrita">Observación:</span>
                <div class="bloque-texto texto-justificado"><?php echo $observacion; ?></div>
            </div>
            <div class="texto-centrado" style="margin: 40px 0; color: #555;">SELLO</div>
            <div style="margin-top: auto;">
                <div class="espacio-firmas">
                    <div class="firma">Docente</div>
                    <div class="firma">Director(a)</div>
                </div>
                <div class="firma-larga">Vocera de investigación y formación</div>
            </div>
        </div>

        <div class="columna col-borde" style="justify-content: space-around; padding: 0 30px;">
            <div class="texto-centrado negrita linea-lateral" style="font-size: 13px; font-style: italic;">
                "Instruye al niño en su camino,<br>Y aun cuando fuere viejo no se apartará de él."<br><br>
                Proverbios 22:6
            </div>
            <div class="texto-centrado linea-lateral" style="font-size: 13px; font-style: italic;">
                "Pocos conocen su utilidad"<br><br>
                Frase de Simón Rodríguez
            </div>
            <div class="texto-centrado linea-lateral" style="font-size: 13px; font-style: italic;">
                "La enseñanza de las buenas costumbres o hábitos sociales es tan esencial como la instrucción."<br><br>
                Pensamiento de Simón Bolívar
            </div>
        </div>
        <div class="columna">
            <div class="texto-centrado negrita" style="margin-bottom: 20px; line-height: 1.3;">
                REPÚBLICA BOLIVARIANA DE VENEZUELA<br>MINISTERIO DEL PODER POPULAR PARA LA EDUCACIÓN<br>
                E.B.N "JUAN PABLO PÉREZ ALFONZO"<br>CODIGO DEA OD02912313<br>MARACAIBO - ZULIA
            </div>
            <div class="texto-centrado" style="margin: 20px 0;">
                <div style="width: 80px; height: 80px; border: 1px solid #000; display: inline-block; line-height: 80px; color: #555; font-size: 10px;">LOGO ESCUELA</div>
            </div>
            <div class="texto-centrado negrita" style="font-size: 14px; margin: 30px 0;">
                BOLETÍN INFORMATIVO INICIAL<br><br>
                AÑO ESCOLAR <span class="linea-texto" style="min-width: 80px;"><?php echo $ano_escolar; ?></span>
            </div>
            <div style="margin-top: 30px; line-height: 2;">
                <div>Estudiante: <span class="linea-texto" style="width: 73%;"><?php echo $estudiante; ?></span></div>
                <div>CE: <span class="linea-texto" style="width: 38%;"><?php echo $ce; ?></span> Grupo: <span class="linea-texto" style="width: 30%;"><?php echo $grupo; ?></span></div>
                <div>Docente: <span class="linea-texto" style="width: 78%;"><?php echo $docente; ?></span></div>
                <div>Representante: <span class="linea-texto" style="width: 70%;"><?php echo $representante; ?></span></div>
            </div>
        </div>
    </div>
    
    <div class="hoja">
        <div class="columna col-borde">
            <div class="negrita" style="margin-bottom: 10px;">PRIMER MOMENTO DE EVALUACIÓN<br>PROYECTOS DE APRENDIZAJES:<br><span style="font-weight: normal; text-decoration: underline;"><?php echo $m1_proy; ?></span></div>
            <div class="negrita" style="border-bottom: 1px solid black; margin-bottom: 10px;">ÁREAS DE APRENDIZAJE</div>
            
            <div class="negrita">Formación personal, social y comunicación</div>
            <div class="bloque-texto texto-justificado"><?php echo $m1_form; ?></div>
            <div class="negrita">Relación entre los Componentes del Ambiente</div>
            <div class="bloque-texto texto-justificado"><?php echo $m1_rel; ?></div>
            <div class="negrita">SUGERENCIAS</div>
            <div class="bloque-texto texto-justificado"><?php echo $m1_sug; ?></div>

            <div class="espacio-firmas">
                <div style="width: 45%;">Director(a) <span class="linea-texto" style="width: 50%;"></span></div>
                <div style="width: 45%;">Docente: <span class="linea-texto" style="width: 60%;"></span></div>
            </div>
            <div style="margin-top: 10px; font-size: 10px; display: flex; justify-content: space-between;">
                <div style="width: 60%;">Representante: <span class="linea-texto" style="width: 50%;"></span></div>
                <div style="width: 35%;">Fecha: <span class="linea-texto" style="width: 60%;"></span></div>
            </div>
        </div>

        <div class="columna col-borde">
            <div class="negrita" style="margin-bottom: 10px;">SEGUNDO MOMENTO DE EVALUACIÓN<br>PROYECTOS DE APRENDIZAJES:<br><span style="font-weight: normal; text-decoration: underline;"><?php echo $m2_proy; ?></span></div>
            <div class="negrita" style="border-bottom: 1px solid black; margin-bottom: 10px;">ÁREAS DE APRENDIZAJE</div>
            
            <div class="negrita">Formación personal, social y comunicación</div>
            <div class="bloque-texto texto-justificado"><?php echo $m2_form; ?></div>
            <div class="negrita">Relación entre los Componentes del Ambiente</div>
            <div class="bloque-texto texto-justificado"><?php echo $m2_rel; ?></div>
            <div class="negrita">SUGERENCIAS</div>
            <div class="bloque-texto texto-justificado"><?php echo $m2_sug; ?></div>
            <div class="espacio-firmas">
                <div style="width: 45%;">Director(a) <span class="linea-texto" style="width: 50%;"></span></div>
                <div style="width: 45%;">Docente: <span class="linea-texto" style="width: 60%;"></span></div>
            </div>
            <div style="margin-top: 10px; font-size: 10px; display: flex; justify-content: space-between;">
                <div style="width: 60%;">Representante: <span class="linea-texto" style="width: 50%;"></span></div>
                <div style="width: 35%;">Fecha: <span class="linea-texto" style="width: 60%;"></span></div>
            </div>
        </div>

        <div class="columna">
            <div class="negrita" style="margin-bottom: 10px;">TERCER MOMENTO DE EVALUACIÓN<br>PROYECTOS DE APRENDIZAJES:<br><span style="font-weight: normal; text-decoration: underline;"><?php echo $m3_proy; ?></span></div>
            <div class="negrita" style="border-bottom: 1px solid black; margin-bottom: 10px;">ÁREAS DE APRENDIZAJE</div>
            
            <div class="negrita">Formación personal, social y comunicación</div>
            <div class="bloque-texto texto-justificado"><?php echo $m3_form; ?></div>
            <div class="negrita">Relación entre los Componentes del Ambiente</div>
            <div class="bloque-texto texto-justificado"><?php echo $m3_rel; ?></div>
            <div class="negrita">SUGERENCIAS</div>
            <div class="bloque-texto texto-justificado"><?php echo $m3_sug; ?></div>
            
            <div class="espacio-firmas">
                <div style="width: 45%;">Director(a) <span class="linea-texto" style="width: 50%;"></span></div>
                <div style="width: 45%;">Docente: <span class="linea-texto" style="width: 60%;"></span></div>
            </div>
            <div style="margin-top: 10px; font-size: 10px; display: flex; justify-content: space-between;">
                <div style="width: 60%;">Representante: <span class="linea-texto" style="width: 50%;"></span></div>
                <div style="width: 35%;">Fecha: <span class="linea-texto" style="width: 60%;"></span></div>
            </div>
        </div>
    </div>

    <div class="ocultar-impresion">
        <?php include '../includes/footer.php'; ?>
    </div>


</body>
</html>