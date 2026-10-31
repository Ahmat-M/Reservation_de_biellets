<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TchadBus Express - Réservation de Billets de Bus au Tchad</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Style personnalisé -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=2.0">
    <style>
        .navbar-brand { font-weight: 800; letter-spacing: 0.5px; }
        .hover-shadow:hover { transform: translateY(-3px); transition: all 0.25s ease-in-out; }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background-color: #0d3b66;">
        <div class="container">
            <a class="navbar-brand text-white" href="<?php echo BASE_URL; ?>index.php">
                <i class="fas fa-bus-alt text-warning me-2"></i> TCHAD<span class="text-warning">BUS</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>index.php"><i class="fas fa-home me-1"></i> Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>recherche.php"><i class="fas fa-search me-1"></i> Rechercher un voyage</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if(isLoggedIn()): ?>
                        <?php if(isAdmin()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle text-warning fw-bold" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-shield me-1"></i> Administration
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/dashboard.php"><i class="fas fa-tachometer-alt me-2 text-primary"></i> Tableau de bord</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/voyages/index.php"><i class="fas fa-route me-2 text-primary"></i> Gérer les voyages</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/reservations/index.php"><i class="fas fa-ticket-alt me-2 text-primary"></i> Toutes les réservations</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/vehicules/index.php"><i class="fas fa-bus me-2 text-secondary"></i> Flotte de bus</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/chauffeurs/index.php"><i class="fas fa-id-card me-2 text-secondary"></i> Chauffeurs</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>mes_reservations.php">
                                <i class="fas fa-ticket-alt me-1"></i> Mes Billets
                            </a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <span class="navbar-text text-light small me-2 d-none d-lg-inline">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>
                            </span>
                            <a class="btn btn-outline-warning btn-sm" href="<?php echo BASE_URL; ?>logout.php">
                                <i class="fas fa-sign-out-alt"></i> Quitter
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>login.php"><i class="fas fa-sign-in-alt me-1"></i> Connexion</a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-warning btn-sm fw-bold px-3" href="<?php echo BASE_URL; ?>register.php">
                                <i class="fas fa-user-plus me-1"></i> Inscription
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4 flex-grow-1">
