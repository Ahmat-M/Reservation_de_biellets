<?php
require_once '../../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();
$error = null;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $permis = trim($_POST['permis'] ?? '');
    
    if(empty($nom)) {
        $error = "Le nom du chauffeur est obligatoire.";
    } else {
        $query = "INSERT INTO chauffeurs (nom, telephone, permis) VALUES (:nom, :telephone, :permis)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':telephone', $telephone);
        $stmt->bindParam(':permis', $permis);
        
        if($stmt->execute()) {
            header("Location: index.php?success=1");
            exit();
        } else {
            $error = "Erreur lors de l'enregistrement du chauffeur.";
        }
    }
}

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0"><i class="fas fa-user-plus"></i> Ajouter un chauffeur</h4>
            </div>
            <div class="card-body p-4">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nom" class="form-label fw-bold">Nom complet *</label>
                        <input type="text" class="form-control" id="nom" name="nom" placeholder="Ex: Abdelkerim Mahamat" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="telephone" class="form-label fw-bold">Numéro de téléphone (+235)</label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="+235 66 12 34 56">
                    </div>
                    
                    <div class="mb-4">
                        <label for="permis" class="form-label fw-bold">Numéro de permis de conduire (Catégorie D)</label>
                        <input type="text" class="form-control" id="permis" name="permis" placeholder="Ex: CH-TD-2023-045">
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Retour à la liste</a>
                        <button type="submit" class="btn btn-primary fw-bold">Enregistrer le chauffeur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

