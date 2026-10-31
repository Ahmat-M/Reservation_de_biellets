<?php
require_once '../../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    // Détacher le chauffeur des voyages existants (si la clé étrangère ne le fait pas déjà)
    $db->prepare("UPDATE voyages SET chauffeur_id = NULL WHERE chauffeur_id = :id")->execute([':id' => $id]);
    // Supprimer le chauffeur
    $db->prepare("DELETE FROM chauffeurs WHERE id = :id")->execute([':id' => $id]);
}

header("Location: index.php?deleted=1");
exit();

