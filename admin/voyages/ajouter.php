<?php
require_once '../../config/database.php';
include '../../includes/header.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Récupérer les véhicules et chauffeurs
$vehicules = $db->query("SELECT * FROM vehicules")->fetchAll();
$chauffeurs = $db->query("SELECT * FROM chauffeurs")->fetchAll();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ville_depart = $_POST['ville_depart'];
    $ville_arrivee = $_POST['ville_arrivee'];
    $date_depart = $_POST['date_depart'];
    $date_arrivee = $_POST['date_arrivee'];
    $prix = $_POST['prix'];
    $places_disponibles = $_POST['places_disponibles'];
    $vehicule_id = $_POST['vehicule_id'];
    $chauffeur_id = $_POST['chauffeur_id'];
    
    $query = "INSERT INTO voyages (ville_depart, ville_arrivee, date_depart, date_arrivee, prix, places_disponibles, vehicule_id, chauffeur_id) 
              VALUES (:ville_depart, :ville_arrivee, :date_depart, :date_arrivee, :prix, :places_disponibles, :vehicule_id, :chauffeur_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':ville_depart', $ville_depart);
    $stmt->bindParam(':ville_arrivee', $ville_arrivee);
    $stmt->bindParam(':date_depart', $date_depart);
    $stmt->bindParam(':date_arrivee', $date_arrivee);
    $stmt->bindParam(':prix', $prix);
    $stmt->bindParam(':places_disponibles', $places_disponibles);
    $stmt->bindParam(':vehicule_id', $vehicule_id);
    $stmt->bindParam(':chauffeur_id', $chauffeur_id);
    
    if($stmt->execute()) {
        header("Location: index.php?success=1");
        exit();
    } else {
        $error = "Erreur lors de l'ajout du voyage.";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Ajouter un voyage</h3>
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ville_depart" class="form-label">Ville de départ</label>
                            <input type="text" class="form-control" id="ville_depart" name="ville_depart" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="ville_arrivee" class="form-label">Ville d'arrivée</label>
                            <input type="text" class="form-control" id="ville_arrivee" name="ville_arrivee" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_depart" class="form-label">Date et heure de départ</label>
                            <input type="datetime-local" class="form-control" id="date_depart" name="date_depart" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="date_arrivee" class="form-label">Date et heure d'arrivée</label>
                            <input type="datetime-local" class="form-control" id="date_arrivee" name="date_arrivee" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="prix" class="form-label">Prix (FCFA)</label>
                            <input type="number" class="form-control" id="prix" name="prix" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="places_disponibles" class="form-label">Places disponibles</label>
                            <input type="number" class="form-control" id="places_disponibles" name="places_disponibles" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="vehicule_id" class="form-label">Véhicule</label>
                            <select class="form-select" id="vehicule_id" name="vehicule_id" required>
                                <option value="">Sélectionnez</option>
                                <?php foreach($vehicules as $vehicule): ?>
                                <option value="<?php echo $vehicule['id']; ?>">
                                    <?php echo htmlspecialchars($vehicule['marque']); ?> - <?php echo htmlspecialchars($vehicule['immatriculation']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="chauffeur_id" class="form-label">Chauffeur</label>
                        <select class="form-select" id="chauffeur_id" name="chauffeur_id" required>
                            <option value="">Sélectionnez</option>
                            <?php foreach($chauffeurs as $chauffeur): ?>
                            <option value="<?php echo $chauffeur['id']; ?>">
                                <?php echo htmlspecialchars($chauffeur['nom']); ?> - <?php echo htmlspecialchars($chauffeur['telephone']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Ajouter le voyage</button>
                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>