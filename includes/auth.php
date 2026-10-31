<?php
// includes/auth.php - Fonctions d'authentification et de contrôle d'accès

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie si l'utilisateur est authentifié
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur a le rôle administrateur
 */
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Vérifie si l'utilisateur a le rôle client
 */
function isClient(): bool {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client';
}

/**
 * Récupère les données de l'utilisateur courant
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'name'      => $_SESSION['user_name'] ?? '',
        'email'     => $_SESSION['user_email'] ?? '',
        'telephone' => $_SESSION['user_tel'] ?? '',
        'role'      => $_SESSION['user_role'] ?? 'client'
    ];
}

/**
 * Exige une authentification pour accéder à la page
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        if (!empty($_SERVER['REQUEST_URI'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        }
        $target = defined('BASE_URL') ? BASE_URL . 'login.php' : '/gestionVoyage/login.php';
        header("Location: " . $target);
        exit();
    }
}

/**
 * Exige les droits administrateur pour accéder à la page
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        $target = defined('BASE_URL') ? BASE_URL . 'login.php?error=admin_required' : '/gestionVoyage/login.php?error=admin_required';
        header("Location: " . $target);
        exit();
    }
}
?>