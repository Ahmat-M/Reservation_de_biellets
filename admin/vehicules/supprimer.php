<?php
require_once '../../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

if(isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("DELETE FROM vehicules WHERE id = :id");
    $stmt->execute([':id' => $id]);
}

header("Location: index.php?deleted=1");
exit();

