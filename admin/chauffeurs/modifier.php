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
$stmt = $db->prepare("SELECT * FROM chauffeurs WHERE id = :id");
$stmt->execute([':id' => $id]);
$chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$chauffeur) {
    header("Location: index.php");
    exit();
}

$error = null;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $permis = trim($_POST['permis'] ?? '');
    
    if(empty($nom)) {
        $error = "Le nom du chauffeur est obligatoire.";
    } else {
        $query = "UPDATE chauffeurs SET nom = :nom, telephone = :telephone, permis = :permis WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':telephone', $telephone);
        $stmt->bindParam(':permis', $permis);
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
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-warning text-dark py-3">
                <h4 class="mb-0 fw-bold"><i class="fas fa-edit me-1"></i> Modifier le Chauffeur #<?php echo $chauffeur['id']; ?></h4>
            </div>
            <div class="card-body p-4">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nom" class="form-label fw-bold">Nom complet *</label>
                        <input type="text" class="form-control form-control-lg" id="nom" name="nom" value="<?php echo htmlspecialchars($chauffeur['nom']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="telephone" class="form-label fw-bold">Numéro de téléphone (+235)</label>
                        <input type="tel" class="form-control form-control-lg" id="telephone" name="telephone" value="<?php echo htmlspecialchars($chauffeur['telephone'] ?? ''); ?>" placeholder="+235 66 12 34 56">
                    </div>
                    
                    <div class="mb-4">
                        <label for="permis" class="form-label fw-bold">Numéro de permis (Catégorie D)</label>
                        <input type="text" class="form-control form-control-lg" id="permis" name="permis" value="<?php echo htmlspecialchars($chauffeur['permis'] ?? ''); ?>">
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

