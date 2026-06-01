<?php include '../includes/header.php'; ?>
<div style="font-family: Arial, sans-serif; background: rgb(240, 242, 245); padding: 20px;">
    <h2 style="color: rgb(26, 35, 126);">Registro de Calificaciones y Promedios</h2>
    <form action="vista_previa.php" method="POST" style="background: white; padding: 30px; border-radius: 8px;">
        
        <p style="font-weight: bold;">Nombre del Estudiante:</p>
        <input type="text" name="estudiante" required style="width: 90%; padding: 10px; border: 1px solid rgb(200, 200, 200);">
        
        <p style="font-weight: bold;">Grado y Sección:</p>
        <input type="text" name="grado_seccion" required style="width: 90%; padding: 10px; border: 1px solid rgb(200, 200, 200);">

        <h3 style="color: rgb(26, 35, 126); margin-top: 30px;">Notas del Primer Momento</h3>
        <p>Lenguaje y Comunicación:</p>
        <input type="number" step="0.1" name="len_m1" style="padding: 10px; width: 50%;">
        <p>Matemática:</p>
        <input type="number" step="0.1" name="mat_m1" style="padding: 10px; width: 50%;">
        <p>Ciencias y Tecnología:</p>
        <input type="number" step="0.1" name="cie_m1" style="padding: 10px; width: 50%;">

        <h3 style="color: rgb(26, 35, 126); margin-top: 30px;">Notas del Segundo Momento</h3>
        <p>Lenguaje y Comunicación:</p>
        <input type="number" step="0.1" name="len_m2" style="padding: 10px; width: 50%;">
        <p>Matemática:</p>
        <input type="number" step="0.1" name="mat_m2" style="padding: 10px; width: 50%;">
        <p>Ciencias y Tecnología:</p>
        <input type="number" step="0.1" name="cie_m2" style="padding: 10px; width: 50%;">

        <h3 style="color: rgb(26, 35, 126); margin-top: 30px;">Notas del Tercer Momento</h3>
        <p>Lenguaje y Comunicación:</p>
        <input type="number" step="0.1" name="len_m3" style="padding: 10px; width: 50%;">
        <p>Matemática:</p>
        <input type="number" step="0.1" name="mat_m3" style="padding: 10px; width: 50%;">
        <p>Ciencias y Tecnología:</p>
        <input type="number" step="0.1" name="cie_m3" style="padding: 10px; width: 50%;">

        <h3 style="color: rgb(26, 35, 126); margin-top: 30px;">Textos Adicionales</h3>
        <p style="font-weight: bold;">Observaciones Generales:</p>
        <textarea name="observaciones" rows="4" style="width: 90%; padding: 10px; border: 1px solid rgb(200, 200, 200);"></textarea>

        <p style="font-weight: bold;">Versos Bíblicos o Frases de Simón Bolívar:</p>
        <textarea name="frase_especial" rows="2" style="width: 90%; padding: 10px; border: 1px solid rgb(200, 200, 200);"></textarea>

        <br><br>
        <button type="submit" style="background: rgb(26, 35, 126); color: white; padding: 15px 20px; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; font-weight: bold;">Generar Boletín Impreso</button>
    </form>
</div>
<?php include '../includes/footer.php'; ?>