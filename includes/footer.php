</div> <!-- cierra .p-4 -->
</div> <!-- cierra #content -->
</div> <!-- cierra .d-flex -->

<footer class="text-center py-3 text-muted mt-auto">
    <small>&copy; <?= date('Y'); ?> - Sistema de Gestión Educativa</small>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Sincronizar el estado desde localStorage (si existe) y guardar en cookie
    var storedStatus = localStorage.getItem('sidebarStatus');
    if (storedStatus === 'hidden') {
        $('#sidebar').addClass('active');
        document.cookie = "sidebarStatus=hidden; path=/";
    } else if (storedStatus === 'visible') {
        $('#sidebar').removeClass('active');
        document.cookie = "sidebarStatus=visible; path=/";
    } else {
        // Si no hay estado, aseguramos visible por defecto
        localStorage.setItem('sidebarStatus', 'visible');
        document.cookie = "sidebarStatus=visible; path=/";
    }

    // Toggle al hacer clic
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
        var isHidden = $('#sidebar').hasClass('active');
        var newStatus = isHidden ? 'hidden' : 'visible';
        localStorage.setItem('sidebarStatus', newStatus);
        document.cookie = "sidebarStatus=" + newStatus + "; path=/";
    });
});
</script>

<?php if (isset($GLOBALS['includes_js_extra'])) echo $GLOBALS['includes_js_extra']; ?>
</body>
</html>