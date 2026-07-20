</div> <!-- cierra .p-4 -->
</div> <!-- cierra #content -->
</div> <!-- cierra .d-flex -->

<footer class="text-center py-3 text-muted mt-auto">
    <small>&copy; <?= date('Y'); ?> - Sistema de Gestión Educativa</small>
</footer>

<!-- Modal Cambiar Periodo Escolar -->
<div class="modal fade" id="modalCambiarPeriodo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i> Cambiar Periodo Escolar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Seleccione el nuevo periodo escolar que se utilizará en todo el sistema.</p>
                <div class="mb-3">
                    <label class="form-label fw-bold">Periodo Actual</label>
                    <input type="text" class="form-control" id="periodo_actual_display" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Nuevo Periodo <span class="text-danger">*</span></label>
                    <select class="form-select" id="nuevo_periodo_select">
                        <?php
                        // ========== RANGO DESDE 2023 EN ADELANTE ==========
                        $anio_actual = date('Y');
                        $anio_inicio = 2023; // Cambiado: comienza desde 2023
                        for ($i = $anio_inicio; $i <= $anio_actual + 3; $i++) {
                            $periodo = $i . '-' . ($i + 1);
                            $selected = ($periodo == $periodo_escolar_actual) ? 'selected' : '';
                            echo "<option value=\"$periodo\" $selected>$periodo</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="alert alert-warning small">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Importante:</strong> Al cambiar el periodo, todos los nuevos registros usarán este periodo.
                    Los registros existentes no se modifican.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarPeriodo">
                    <i class="fas fa-save me-2"></i> Guardar Periodo
                </button>
            </div>
        </div>
    </div>
</div>

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
        localStorage.setItem('sidebarStatus', 'visible');
        document.cookie = "sidebarStatus=visible; path=/";
    }

    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
        var isHidden = $('#sidebar').hasClass('active');
        var newStatus = isHidden ? 'hidden' : 'visible';
        localStorage.setItem('sidebarStatus', newStatus);
        document.cookie = "sidebarStatus=" + newStatus + "; path=/";
    });

    // ========== CAMBIAR PERIODO ESCOLAR ==========
    const modalCambiarPeriodo = new bootstrap.Modal(document.getElementById('modalCambiarPeriodo'));
    
    $('#btnCambiarPeriodo').on('click', function() {
        $('#periodo_actual_display').val($('#periodo-actual-label').text());
        modalCambiarPeriodo.show();
    });

    $('#btnGuardarPeriodo').on('click', function() {
        const nuevoPeriodo = $('#nuevo_periodo_select').val();
        const btn = $(this);
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Guardando...');

        $.ajax({
            url: '/servicio-comunitario/config/ajax_cambiar_periodo.php',
            method: 'POST',
            data: { periodo: nuevoPeriodo },
            dataType: 'json'
        })
        .done(function(data) {
            if (data.success) {
                $('#periodo-actual-label').text(nuevoPeriodo);
                modalCambiarPeriodo.hide();
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'No se pudo guardar el periodo'));
            }
        })
        .fail(function() {
            alert('Error de conexión al servidor.');
        })
        .always(function() {
            btn.prop('disabled', false);
            btn.html('<i class="fas fa-save me-2"></i> Guardar Periodo');
        });
    });
});
</script>

<?php if (isset($GLOBALS['includes_js_extra'])) echo $GLOBALS['includes_js_extra']; ?>
</body>
</html>