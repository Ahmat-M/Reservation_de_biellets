<?php
require_once '../../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $marque = $_POST['marque'];
    $immatriculation = $_POST['immatriculation'];
    $capacite = $_POST['capacite'];
    
    $query = "INSERT INTO vehicules (marque, immatriculation, capacite) VALUES (:marque, :immatriculation, :capacite)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':marque', $marque);
    $stmt->bindParam(':immatriculation', $immatriculation);
    $stmt->bindParam(':capacite', $capacite);
    
    if($stmt->execute()) {
        header("Location: index.php?success=1");
        exit();
    } else {
        $error = "Erreur lors de l'ajout.";
    }
}

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Ajouter un véhicule</h3>
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="marque" class="form-label">Marque</label>
                        <input type="text" class="form-control" id="marque" name="marque" required>
                    </div>
                    <div class="mb-3">
                        <label for="immatriculation" class="form-label">Immatriculation</label>
                        <input type="text" class="form-control" id="immatriculation" name="immatriculation" required>
                    </div>
                    <div class="mb-3">
                        <label for="capacite" class="form-label">Capacité (nombre de places)</label>
                        <input type="number" class="form-control" id="capacite" name="capacite" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>