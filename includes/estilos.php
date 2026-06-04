<style>
    :root {
        --azul-marino: #002d54;
        --azul-hover: #004080;
    }

    body {
        background-color: #f4f7f6;
        margin: 0;
        font-family: 'Segoe UI', Tahoma, sans-serif;
    }

    /* ========== SIDEBAR CON ANIMACIÓN ========== */
    #sidebar {
        min-width: 250px;
        max-width: 250px;
        background: var(--azul-marino);
        color: white;
        min-height: 100vh;
        margin-left: 0; /* visible por defecto */
        transition: margin-left 0.3s ease; /* solo anima el margen, no todo */
    }

    /* Estado oculto */
    #sidebar.active {
        margin-left: -250px;
    }

    /* Para evitar el "salto" al cargar, desactivamos transición temporalmente */
    #sidebar.no-transition {
        transition: none !important;
    }

    /* Contenido principal (opcional, si quieres que también tenga animación) */
    #content {
        width: 100%;
        transition: margin-left 0.3s ease;
    }

    /* Enlaces del menú */
    #sidebar ul li a {
        padding: 15px 20px;
        display: block;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: background 0.2s;
    }

    #sidebar ul li a:hover,
    .active-item a {
        background: var(--azul-hover);
        color: #fff;
    }

    /* ========== ESTILOS ADICIONALES (no duplicados) ========== */
    .date-moderna {
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }

    .date-moderna:focus {
        border-color: #002d54;
        box-shadow: 0 0 0 0.25rem rgba(0, 45, 84, 0.1);
    }

    input[type="month"]::-webkit-calendar-picker-indicator {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="15" viewBox="0 0 24 24" fill="none" stroke="%23002d54" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>');
        cursor: pointer;
        padding: 5px;
    }

    /* Quitar flechas de inputs number */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type=number] {
        -moz-appearance: textfield;
    }

    /* Estilos para inputs de asistencia (tablas) */
    .asist-input {
        border: none !important;
        height: 50px;
        font-size: 1.1rem;
        font-weight: bold;
        text-align: center;
        padding: 0;
        border-radius: 0;
        background: transparent;
    }
    .asist-input:focus {
        background-color: #fff9c4 !important;
        box-shadow: inset 0 0 5px rgba(0,0,0,0.1);
        outline: none;
    }

    /* Tablas responsivas */
    .table-responsive {
        overflow-x: auto;
        scrollbar-width: thin;
    }
    .table-clean th, .table-clean td {
        border-color: #dee2e6 !important;
        padding: 4px 2px !important;
        font-size: 0.75rem !important;
    }

    /* Utilidades */
    .bg-success { background-color: #28a745 !important; }
    .opacity-25 { opacity: 0.25; }
    .text-navy { color: #002d54; }
</style>