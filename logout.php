<?php
// logout.php - Déconnexion propre et sécurisée
require_once __DIR__ . '/config/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Vider le tableau de session
$_SESSION = [];

// 2. Supprimer le cookie de session si présent
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Détruire la session côté serveur
session_destroy();

// 4. Redirection vers la page de connexion avec message
header("Location: " . BASE_URL . "login.php?logged_out=1");
exit();
