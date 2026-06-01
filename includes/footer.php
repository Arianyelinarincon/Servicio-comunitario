</div> </div> </div> <footer class="text-center py-3 text-muted mt-auto">
    <small>&copy; <?= date('Y'); ?> - Sistema de Estadísticas Escolares</small>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function () {
    // 1. Al cargar la página, verificar si hay un estado guardado en localStorage
    if (localStorage.getItem('sidebarStatus') === 'hidden') {
        $('#sidebar').addClass('active');
    } else {
        $('#sidebar').removeClass('active');
    }

    // 2. Controlar el clic en el botón de las tres rayitas
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
        
        // 3. Guardar el nuevo estado según si tiene o no la clase 'active'
        if ($('#sidebar').hasClass('active')) {
            localStorage.setItem('sidebarStatus', 'hidden');
        } else {
            localStorage.setItem('sidebarStatus', 'visible');
        }
    });

    // Inicializar tooltips si usas alguno
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>

<script src="../assets/js/funciones.js"></script>

</body>
</html>