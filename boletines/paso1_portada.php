<?php
session_start();
// Reiniciamos las sesiones para evitar que queden datos de un boletín anterior
session_unset();
include '../includes/header.php'; 
?>
<div style="font-family: Arial, sans-serif; background: rgb(240, 242, 245); padding: 20px;">
    <h2 style="color: rgb(26, 35, 126); text-align: center;">Paso 1: Datos de Portada</h2>
    <form action="paso2_observaciones.php" method="POST" style="background: white; padding: 30px; border-radius: 8px; max-width: 900px; margin: 0 auto;">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div><p style="font-weight: bold; margin-bottom: 5px;">Estudiante:</p>
            <input type="text" name="estudiante" required style="width: 100%; padding: 8px; border: 1px solid #ccc; box-sizing: border-box;"></div>
            
            <div><p style="font-weight: bold; margin-bottom: 5px;">C.E (Cédula Escolar):</p>
            <input type="text" name="ce" required style="width: 100%; padding: 8px; border: 1px solid #ccc; box-sizing: border-box;"></div>
            
            <div><p style="font-weight: bold; margin-bottom: 5px;">Grupo:</p>
            <input type="text" name="grupo" required style="width: 100%; padding: 8px; border: 1px solid #ccc; box-sizing: border-box;"></div>
            
            <div><p style="font-weight: bold; margin-bottom: 5px;">Año Escolar:</p>
            <input type="text" name="ano_escolar" value="2025 / 2026" required style="width: 100%; padding: 8px; border: 1px solid #ccc; box-sizing: border-box;"></div>
            
            <div><p style="font-weight: bold; margin-bottom: 5px;">Docente:</p>
            <input type="text" name="docente" required style="width: 100%; padding: 8px; border: 1px solid #ccc; box-sizing: border-box;"></div>
            
            <div><p style="font-weight: bold; margin-bottom: 5px;">Representante:</p>
            <input type="text" name="representante" required style="width: 100%; padding: 8px; border: 1px solid #ccc; box-sizing: border-box;"></div>
        </div>

        <br><br>
        <button type="submit" style="background: rgb(26, 35, 126); color: white; padding: 15px 20px; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; font-weight: bold; width: 100%;">Siguiente (Ir a Observaciones)</button>
    </form>
</div>
<?php include '../includes/footer.php'; ?>