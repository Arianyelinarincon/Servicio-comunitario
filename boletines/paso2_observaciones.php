<?php
session_start();
if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
        $_SESSION[$key] = $value;
    }
}
include '../includes/header.php'; 
?>
<div style="font-family: Arial, sans-serif; background: rgb(240, 242, 245); padding: 20px;">
    <h2 style="color: rgb(26, 35, 126); text-align: center;">Paso 2: Observación General</h2>
    <form action="paso3_momento1.php" method="POST" style="background: white; padding: 30px; border-radius: 8px; max-width: 900px; margin: 0 auto;">
        
        <p style="font-weight: bold;">Observación General (Columna Izquierda de la hoja exterior):</p>
        <textarea name="observacion" rows="5" required style="width: 100%; padding: 8px; border: 1px solid #ccc; box-sizing: border-box;"></textarea>
        
        <div style="background: #f9f9f9; padding: 15px; margin-top: 20px; border-left: 4px solid rgb(26,35,126); font-size: 13px;">
            <strong>Nota:</strong> Los versículos bíblicos y las frases de Simón Rodríguez y Simón Bolívar ya están configurados automáticamente en la plantilla final.
        </div>

        <br><br>
        <button type="submit" style="background: rgb(26, 35, 126); color: white; padding: 15px 20px; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; font-weight: bold; width: 100%;">Siguiente (Ir al Primer Momento)</button>
    </form>
</div>
<?php include '../includes/footer.php'; ?>