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
    <h2 style="color: rgb(26, 35, 126); text-align: center;">Paso 3: Evaluación del Primer Momento</h2>
    <form action="paso4_momento2.php" method="POST" style="background: white; padding: 30px; border-radius: 8px; max-width: 900px; margin: 0 auto;">
        
        <p style="font-weight: bold;">Proyecto de Aprendizaje:</p>
        <input type="text" name="m1_proyecto" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">
        
        <p style="font-weight: bold;">Formación personal, social y comunicación:</p>
        <textarea name="m1_formacion" rows="4" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;"></textarea>
        
        <p style="font-weight: bold;">Relación entre los Componentes del Ambiente:</p>
        <textarea name="m1_relacion" rows="4" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;"></textarea>
        
        <p style="font-weight: bold;">Sugerencias:</p>
        <textarea name="m1_sugerencias" rows="3" required style="width: 100%; padding: 8px; box-sizing: border-box;"></textarea>

        <br><br>
        <button type="submit" style="background: rgb(26, 35, 126); color: white; padding: 15px 20px; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; font-weight: bold; width: 100%;">Siguiente (Ir al Segundo Momento)</button>
    </form>
</div>
<?php include '../includes/footer.php'; ?>