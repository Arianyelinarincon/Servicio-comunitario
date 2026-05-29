<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario de Boletines Escolares</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .contenedor { background: white; padding: 30px; max-width: 800px; margin: 0 auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #1a237e; border-bottom: 2px solid #1a237e; padding-bottom: 10px; }
        .grupo-form { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
        input[type="text"], textarea { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .cuadricula-notas { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .btn-enviar { background: #1a237e; color: white; padding: 12px 20px; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; width: 100%; margin-top: 20px; font-weight: bold; }
        .btn-enviar:hover { background: #283593; }
        .seccion-materia { background: #f9f9f9; padding: 15px; border-radius: 4px; border-left: 4px solid #1a237e; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="contenedor">
    <h2>Llenar Datos del Boletín Inicial</h2>
    
    <!-- El formulario envía los datos a vista_previa.php usando el método POST -->
    <form action="vista_previa.php" method="POST">
        
        <div class="grupo-form">
            <label>Nombres y Apellidos del Estudiante:</label>
            <input type="text" name="estudiante" required>
        </div>
        
        <div class="grupo-form">
            <label>Grado y Sección:</label>
            <input type="text" name="grado_seccion" required>
        </div>

        <h2>Calificaciones por Momentos</h2>
        
        <div class="seccion-materia">
            <label>Lenguaje y Comunicación:</label>
            <div class="cuadricula-notas">
                <input type="text" name="len_m1" placeholder="1er Momento">
                <input type="text" name="len_m2" placeholder="2do Momento">
                <input type="text" name="len_m3" placeholder="3er Momento">
            </div>
        </div>

        <div class="seccion-materia">
            <label>Matemática:</label>
            <div class="cuadricula-notas">
                <input type="text" name="mat_m1" placeholder="1er Momento">
                <input type="text" name="mat_m2" placeholder="2do Momento">
                <input type="text" name="mat_m3" placeholder="3er Momento">
            </div>
        </div>

        <div class="seccion-materia">
            <label>Ciencias y Tecnología:</label>
            <div class="cuadricula-notas">
                <input type="text" name="cie_m1" placeholder="1er Momento">
                <input type="text" name="cie_m2" placeholder="2do Momento">
                <input type="text" name="cie_m3" placeholder="3er Momento">
            </div>
        </div>

        <h2>Observaciones y Citas</h2>
        
        <div class="grupo-form">
            <label>Observaciones Generales:</label>
            <textarea name="observaciones" rows="5" placeholder="Escribe aquí las observaciones..."></textarea>
        </div>

        <div class="grupo-form">
            <label>Versos Bíblicos / Frases de Simón Bolívar:</label>
            <textarea name="frase_especial" rows="3" placeholder="Ej: Las naciones marchan hacia su grandeza al mismo paso que avanza su educación."></textarea>
        </div>

        <button type="submit" class="btn-enviar">Generar Vista de Impresión</button>
    </form>
</div>

</body>
</html>