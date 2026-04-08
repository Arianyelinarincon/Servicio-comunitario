<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión U.E.B.N. Juan Pablo Pérez Alfonzo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --unefa-azul: #003366; /* Azul Institucional UNEFA */
            --unefa-oro: #C5A059;  /* Dorado Institucional */
        }
        .navbar-custom {
            background-color: var(--unefa-azul);
            border-bottom: 3px solid var(--unefa-oro);
        }
        .navbar-brand img {
            height: 50px;
            margin-right: 15px;
        }
        .nav-link {
            color: white !important;
            font-weight: 500;
            transition: 0.3s;
        }
        .nav-link:hover {
            color: var(--unefa-oro) !important;
        }
        .user-info {
            color: white;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
 <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center text-white" href="dashboard.php">
            <img src="img/unefa_logo.png" alt="Logo UNEFA"> 
            <div class="d-none d-md-block" style="line-height: 1.2;">
                <span class="fs-6 fw-bold">SISTEMA DE GESTIÓN ESCOLAR</span><br>
                <small style="font-size: 0.7rem; color: var(--unefa-oro);">SERVICIO COMUNITARIO - UNEFA ZULIA</small>
            </div>
        </a>

        <button class="navbar-toggler border-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php"><i class="bi bi-house-door"></i> Inicio</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-people"></i> Estudiantes
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="estudiantes_sala4.php">Sala 4 Años</a></li>
                        <li><a class="dropdown-item" href="estudiantes_sala5.php">Sala 5 Años</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="registro_nuevo.php">Inscribir Nuevo</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="boletines.php"><i class="bi bi-file-earmark-text"></i> Boletines</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="estadisticas.php"><i class="bi bi-bar-chart"></i> Estadísticas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="respaldos.php"><i class="bi bi-database-fill-check"></i> Respaldos</a>
                </li>
            </ul>

            <div class="d-flex align-items-center user-info">
                <div class="text-end me-3 d-none d-sm-block">
                    <span class="d-block fw-bold text-uppercase">ADMIN - SECRETARÍA</span>
                    <small style="color: var(--unefa-oro);">Sede: Maracaibo</small>
                </div>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>   
</body>
</html>