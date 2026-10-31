<?php
class Database {
    private $host = "localhost";
    private $db_name = "Databasevg";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                  $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");

            // Migration transparente pour les colonnes tchadiennes
            static $migrated = false;
            if (!$migrated) {
                $migrated = true;
                try {
                    $cols = $this->conn->query("SHOW COLUMNS FROM voyages LIKE 'compagnie'")->fetchAll();
                    if (empty($cols)) {
                        ob_start();
                        @include __DIR__ . '/../setup_db.php';
                        ob_end_clean();
                    }
                } catch (Exception $ex) {}
            }
        } catch(PDOException $exception) {
            try {
                ob_start();
                @include __DIR__ . '/../setup_db.php';
                ob_end_clean();
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                      $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->exec("set names utf8");
            } catch(Exception $ex) {
                echo "Erreur de connexion : " . $exception->getMessage();
            }
        }
        return $this->conn;
    }
}

// Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/gestionVoyage/');
}

// Fonctions d'authentification globales
require_once __DIR__ . '/../includes/auth.php';
?>