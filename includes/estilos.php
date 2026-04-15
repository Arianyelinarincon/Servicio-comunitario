<style>
    :root {
        --azul-marino: #002d54;
        --azul-hover: #004080;
    }

    body { background-color: #f4f7f6; }

    #sidebar {
        min-width: 250px;
        max-width: 250px;
        background: var(--azul-marino);
        color: #fff;
        transition: all 0.3s;
        min-height: 100vh;
    }

    /* Esta clase es la que hace que el menú se esconda */
    #sidebar.active {
        margin-left: -250px;
    }

    #content {
        width: 100%;
        transition: all 0.3s;
    }

    /* Estilo para los enlaces del menú */
    #sidebar ul li a {
        padding: 15px 20px;
        display: block;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
    }

    #sidebar ul li a:hover, .active-item a {
        background: var(--azul-hover);
        color: #fff;
    }
</style>

<style>
    /* Estilo para el input de mes moderno */
    .date-moderna {
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }

    .date-moderna:focus {
        border-color: #002d54;
        box-shadow: 0 0 0 0.25rem rgba(0, 45, 84, 0.1);
    }

    /* Personalizar el icono nativo del calendario en navegadores modernos */
    input[type="month"]::-webkit-calendar-picker-indicator {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="15" viewBox="0 0 24 24" fill="none" stroke="%23002d54" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>');
        cursor: pointer;
        padding: 5px;
    }
</style>

<style>
    :root {
        --azul-marino: #002d54;
        --azul-hover: #004080;
    }

    body { background-color: #f4f7f6; }

    #sidebar {
        min-width: 250px;
        max-width: 250px;
        background: var(--azul-marino);
        color: #fff;
        transition: all 0.3s;
        min-height: 100vh;
    }

    /* Esta clase es la que hace que el menú se esconda */
    #sidebar.active {
        margin-left: -250px;
    }

    #content {
        width: 100%;
        transition: all 0.3s;
    }

    /* Estilo para los enlaces del menú */
    #sidebar ul li a {
        padding: 15px 20px;
        display: block;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
    }

    #sidebar ul li a:hover, .active-item a {
        background: var(--azul-hover);
        color: #fff;
    }
</style>

<style>
    /* Estilo para el input de mes moderno */
    .date-moderna {
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }

    .date-moderna:focus {
        border-color: #002d54;
        box-shadow: 0 0 0 0.25rem rgba(0, 45, 84, 0.1);
    }

    /* Personalizar el icono nativo del calendario en navegadores modernos */
    input[type="month"]::-webkit-calendar-picker-indicator {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="15" viewBox="0 0 24 24" fill="none" stroke="%23002d54" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>');
        cursor: pointer;
        padding: 5px;
    }

    /* Estado inicial: Sidebar escondido a la izquierda */
#sidebar {
    min-width: 250px;
    max-width: 250px;
    margin-left: -250px; /* Lo saca de la vista */
    transition: all 0.3s;
}

/* Cuando el sidebar tiene la clase 'active', se muestra */
#sidebar.active {
    margin-left: 0;
}

/* El contenido principal debe ocupar todo el ancho si el sidebar está oculto */
.main-content {
    width: 100%;
    padding: 20px;
    transition: all 0.3s;
}

/* Si el sidebar se activa, empujamos un poco el contenido (opcional) */
#sidebar.active + .main-content {
    padding-left: 270px;
}

/* Ajuste para móviles */
@media (max-width: 768px) {
    #sidebar.active + .main-content {
        padding-left: 20px; /* En móviles mejor que flote encima */
    }
}
/* Quitar flechas de subir/bajar números (Chrome, Safari, Edge, Opera) */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Quitar flechas en Firefox */
input[type=number] {
    -moz-appearance: textfield;
}

/* Estilo para que los números se vean grandes y centrados */
.asist-input {
    border: none !important;
    height: 50px; /* Más altura para mejor visibilidad */
    font-size: 1.1rem; /* Números más grandes */
    font-weight: bold;
    text-align: center;
    padding: 0;
    border-radius: 0;
}

.asist-input:focus {
    background-color: #fff9c4 !important; /* Color amarillento suave al escribir */
    box-shadow: inset 0 0 5px rgba(0,0,0,0.1);
    outline: none;
}

/* Para que la tabla respire y no se vea apretada */
.table-clean th, .table-clean td {
    border-color: #dee2e6 !important;
}

<style>
    /* ... tus otros estilos ... */

    .table-responsive {
        overflow-x: auto;
        scrollbar-width: thin;
    }

    /* Reducción de dimensiones para que quepa en pantalla */
    .table-clean th, .table-clean td {
        padding: 4px 2px !important;
        font-size: 0.75rem !important;
    }

    .asist-input {
        border: none !important;
        height: 30px; /* Más compacto */
        width: 100%;
        font-size: 0.85rem;
        font-weight: bold;
        text-align: center;
        background: transparent !important;
    }

    .asist-input:focus {
        background-color: #fff9c4 !important;
        outline: none;
    }

    /* Colores oficiales del formato */
    .bg-success { background-color: #28a745 !important; }
    .opacity-25 { opacity: 0.25; }
    .text-navy { color: #002d54; }
</style>


</style>


