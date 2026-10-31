<?php
require_once '../../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $db->prepare("SELECT * FROM vehicules WHERE id = :id");
$stmt->execute([':id' => $id]);
$vehicule = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$vehicule) {
    header("Location: index.php");
    exit();
}

$error = null;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $marque = trim($_POST['marque'] ?? '');
    $immatriculation = trim($_POST['immatriculation'] ?? '');
    $capacite = intval($_POST['capacite'] ?? 0);
    
    if(empty($marque) || empty($immatriculation) || $capacite <= 0) {
        $error = "Veuillez remplir tous les champs correctement.";
    } else {
        $query = "UPDATE vehicules SET marque = :marque, immatriculation = :immatriculation, capacite = :capacite WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':marque', $marque);
        $stmt->bindParam(':immatriculation', $immatriculation);
        $stmt->bindParam(':capacite', $capacite);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            header("Location: index.php?success=1");
            exit();
        } else {
            $error = "Erreur lors de la mise à jour.";
        }
    }
}

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-warning text-dark py-3">
                <h4 class="mb-0 fw-bold"><i class="fas fa-edit"></i> Modifier le véhicule #<?php echo $vehicule['id']; ?></h4>
            </div>
            <div class="card-body p-4">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="marque" class="form-label fw-bold">Modèle / Marque</label>
                        <input type="text" class="form-control" id="marque" name="marque" value="<?php echo htmlspecialchars($vehicule['marque']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="immatriculation" class="form-label fw-bold">Immatriculation (Ex: 14-A-1234-TD)</label>
                        <input type="text" class="form-control" id="immatriculation" name="immatriculation" value="<?php echo htmlspecialchars($vehicule['immatriculation']); ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="capacite" class="form-label fw-bold">Capacité en places</label>
                        <input type="number" class="form-control" id="capacite" name="capacite" value="<?php echo $vehicule['capacite']; ?>" min="1" max="100" required>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary fw-bold">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

