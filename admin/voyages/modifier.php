<?php
require_once '../../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $db->prepare("SELECT * FROM voyages WHERE id = :id");
$stmt->execute([':id' => $id]);
$voyage = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$voyage) {
    header("Location: index.php");
    exit();
}

// Récupérer les véhicules et chauffeurs
$vehicules = $db->query("SELECT * FROM vehicules ORDER BY marque ASC")->fetchAll(PDO::FETCH_ASSOC);
$chauffeurs = $db->query("SELECT * FROM chauffeurs ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ville_depart = trim($_POST['ville_depart'] ?? '');
    $ville_arrivee = trim($_POST['ville_arrivee'] ?? '');
    $gare_depart = trim($_POST['gare_depart'] ?? 'Gare Routière');
    $compagnie = trim($_POST['compagnie'] ?? 'TchadBus Express');
    $date_depart = $_POST['date_depart'] ?? '';
    $date_arrivee = $_POST['date_arrivee'] ?? '';
    $prix = floatval($_POST['prix'] ?? 0);
    $places_disponibles = intval($_POST['places_disponibles'] ?? 0);
    $vehicule_id = !empty($_POST['vehicule_id']) ? intval($_POST['vehicule_id']) : null;
    $chauffeur_id = !empty($_POST['chauffeur_id']) ? intval($_POST['chauffeur_id']) : null;
    
    if (empty($ville_depart) || empty($ville_arrivee) || empty($date_depart) || $prix <= 0) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        $query = "UPDATE voyages SET ville_depart = :ville_depart, ville_arrivee = :ville_arrivee, gare_depart = :gare_depart,
                  compagnie = :compagnie, date_depart = :date_depart, date_arrivee = :date_arrivee, prix = :prix,
                  places_disponibles = :places_disponibles, vehicule_id = :vehicule_id, chauffeur_id = :chauffeur_id
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ville_depart', $ville_depart);
        $stmt->bindParam(':ville_arrivee', $ville_arrivee);
        $stmt->bindParam(':gare_depart', $gare_depart);
        $stmt->bindParam(':compagnie', $compagnie);
        $stmt->bindParam(':date_depart', $date_depart);
        $stmt->bindParam(':date_arrivee', $date_arrivee);
        $stmt->bindParam(':prix', $prix);
        $stmt->bindParam(':places_disponibles', $places_disponibles);
        $stmt->bindParam(':vehicule_id', $vehicule_id);
        $stmt->bindParam(':chauffeur_id', $chauffeur_id);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            header("Location: index.php?success=1");
            exit();
        } else {
            $error = "Erreur lors de la mise à jour du voyage.";
        }
    }
}

$villesTchad = ["N'Djaména", "Moundou", "Sarh", "Abéché", "Kélo", "Bongor", "Doba", "Pala", "Koumra", "Mongo", "Ati", "Am Timan"];

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-warning text-dark py-3">
                <h4 class="mb-0 fw-bold"><i class="fas fa-edit me-1"></i> Modifier le Voyage #<?php echo $voyage['id']; ?></h4>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Ville de départ *</label>
                            <input list="villes_tchad" type="text" class="form-control" name="ville_depart" value="<?php echo htmlspecialchars($voyage['ville_depart']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Ville d'arrivée *</label>
                            <input list="villes_tchad" type="text" class="form-control" name="ville_arrivee" value="<?php echo htmlspecialchars($voyage['ville_arrivee']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Gare d'embarquement</label>
                            <input type="text" class="form-control" name="gare_depart" value="<?php echo htmlspecialchars($voyage['gare_depart'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Compagnie de transport</label>
                            <input type="text" class="form-control" name="compagnie" value="<?php echo htmlspecialchars($voyage['compagnie'] ?? 'TchadBus'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date & heure départ *</label>
                            <input type="datetime-local" class="form-control" name="date_depart" value="<?php echo date('Y-m-d\TH:i', strtotime($voyage['date_depart'])); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date & heure arrivée *</label>
                            <input type="datetime-local" class="form-control" name="date_arrivee" value="<?php echo date('Y-m-d\TH:i', strtotime($voyage['date_arrivee'])); ?>" required>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Prix du billet (FCFA) *</label>
                            <input type="number" class="form-control" name="prix" value="<?php echo $voyage['prix']; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Places disponibles *</label>
                            <input type="number" class="form-control" name="places_disponibles" value="<?php echo $voyage['places_disponibles']; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Véhicule assigné</label>
                            <select class="form-select" name="vehicule_id">
                                <option value="">Aucun bus</option>
                                <?php foreach ($vehicules as $ve): ?>
                                    <option value="<?php echo $ve['id']; ?>" <?php if($voyage['vehicule_id'] == $ve['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($ve['marque']); ?> (<?php echo htmlspecialchars($ve['immatriculation']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Chauffeur assigné</label>
                            <select class="form-select" name="chauffeur_id">
                                <option value="">Aucun chauffeur</option>
                                <?php foreach ($chauffeurs as $ch): ?>
                                    <option value="<?php echo $ch['id']; ?>" <?php if($voyage['chauffeur_id'] == $ch['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($ch['nom']); ?> (<?php echo htmlspecialchars($ch['telephone']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <datalist id="villes_tchad">
                        <?php foreach ($villesTchad as $vt): ?>
                            <option value="<?php echo htmlspecialchars($vt); ?>">
                        <?php endforeach; ?>
                    </datalist>
                    
                    <div class="mt-4 d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary fw-bold px-4">Mettre à jour le voyage</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

