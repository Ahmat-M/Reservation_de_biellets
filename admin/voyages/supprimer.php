<?php
require_once '../../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    // Supprimer le voyage
    $db->prepare("DELETE FROM voyages WHERE id = :id")->execute([':id' => $id]);
}

header("Location: index.php?deleted=1");
exit();

