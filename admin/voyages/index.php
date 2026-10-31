<?php
require_once '../../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Récupérer les véhicules et chauffeurs
$vehicules = $db->query("SELECT * FROM vehicules ORDER BY marque ASC")->fetchAll(PDO::FETCH_ASSOC);
$chauffeurs = $db->query("SELECT * FROM chauffeurs ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

$error = null;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    
    if(empty($ville_depart) || empty($ville_arrivee) || empty($date_depart) || $prix <= 0) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        $query = "INSERT INTO voyages (ville_depart, ville_arrivee, gare_depart, compagnie, date_depart, date_arrivee, prix, places_disponibles, vehicule_id, chauffeur_id) 
                  VALUES (:ville_depart, :ville_arrivee, :gare_depart, :compagnie, :date_depart, :date_arrivee, :prix, :places_disponibles, :vehicule_id, :chauffeur_id)";
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
        
        if($stmt->execute()) {
            header("Location: index.php?success=1");
            exit();
        } else {
            $error = "Erreur lors de l'ajout du voyage.";
        }
    }
}

// Récupérer la liste des voyages
$liste_voyages = $db->query("SELECT v.*, ve.marque as vehicule_nom, c.nom as chauffeur_nom 
                             FROM voyages v 
                             LEFT JOIN vehicules ve ON v.vehicule_id = ve.id 
                             LEFT JOIN chauffeurs c ON v.chauffeur_id = c.id 
                             ORDER BY v.date_depart DESC")->fetchAll(PDO::FETCH_ASSOC);

$villesTchad = ["N'Djaména", "Moundou", "Sarh", "Abéché", "Kélo", "Bongor", "Doba", "Pala", "Koumra", "Mongo", "Ati", "Am Timan"];

include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-0"><i class="fas fa-route text-primary"></i> Gestion des Voyages au Tchad</h2>
            <small class="text-muted">Programmez les départs et gérez la flotte</small>
        </div>
        <button class="btn btn-primary fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#nouveauVoyage">
            <i class="fas fa-plus"></i> Nouveau départ
        </button>
    </div>
</div>

<?php if(isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> Le voyage a été programmé ou mis à jour avec succès !
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(isset($_GET['deleted'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle me-2"></i> Le voyage a été supprimé du planning.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Formulaire d'ajout repliable -->
<div class="collapse mb-4 <?php if($error) echo 'show'; ?>" id="nouveauVoyage">
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0"><i class="fas fa-calendar-plus"></i> Programmer un nouveau voyage</h5>
        </div>
        <div class="card-body p-4">
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Ville de départ *</label>
                        <input list="villes_tchad" type="text" class="form-control" name="ville_depart" placeholder="Ex: N'Djaména" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Ville d'arrivée *</label>
                        <input list="villes_tchad" type="text" class="form-control" name="ville_arrivee" placeholder="Ex: Moundou, Abéché" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Gare d'embarquement</label>
                        <input type="text" class="form-control" name="gare_depart" placeholder="Ex: Gare Routière de Dembé" value="Gare Routière de Dembé">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Compagnie de transport</label>
                        <input type="text" class="form-control" name="compagnie" value="Abou Simbil Transport">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Date & heure départ *</label>
                        <input type="datetime-local" class="form-control" name="date_depart" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Date & heure arrivée *</label>
                        <input type="datetime-local" class="form-control" name="date_arrivee" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Prix du billet (FCFA) *</label>
                        <input type="number" class="form-control" name="prix" placeholder="Ex: 12000" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Places disponibles *</label>
                        <input type="number" class="form-control" name="places_disponibles" placeholder="Ex: 30" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Véhicule assigné</label>
                        <select class="form-select" name="vehicule_id">
                            <option value="">Sélectionnez un bus</option>
                            <?php foreach($vehicules as $ve): ?>
                                <option value="<?php echo $ve['id']; ?>">
                                    <?php echo htmlspecialchars($ve['marque']); ?> (<?php echo htmlspecialchars($ve['immatriculation']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Chauffeur assigné</label>
                        <select class="form-select" name="chauffeur_id">
                            <option value="">Sélectionnez un chauffeur</option>
                            <?php foreach($chauffeurs as $ch): ?>
                                <option value="<?php echo $ch['id']; ?>">
                                    <?php echo htmlspecialchars($ch['nom']); ?> (<?php echo htmlspecialchars($ch['telephone']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <datalist id="villes_tchad">
                    <?php foreach($villesTchad as $vt): ?>
                        <option value="<?php echo htmlspecialchars($vt); ?>">
                    <?php endforeach; ?>
                </datalist>
                
                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-primary fw-bold px-4 py-2">
                        <i class="fas fa-check"></i> Enregistrer le voyage
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tableau de la liste des voyages -->
<div class="card shadow-sm border-0 rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Trajet</th>
                        <th>Compagnie / Gare</th>
                        <th>Départ</th>
                        <th>Prix</th>
                        <th>Places</th>
                        <th>Bus / Chauffeur</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($liste_voyages)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Aucun voyage programmé.</td></tr>
                    <?php else: ?>
                        <?php foreach($liste_voyages as $v): ?>
                        <tr>
                            <td>#<?php echo $v['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($v['ville_depart']); ?> → <?php echo htmlspecialchars($v['ville_arrivee']); ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($v['compagnie'] ?? 'TchadBus'); ?></span><br>
                                <small class="text-muted"><?php echo htmlspecialchars($v['gare_depart'] ?? '-'); ?></small>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($v['date_depart'])); ?></td>
                            <td><strong class="text-success"><?php echo number_format($v['prix'], 0, ',', ' '); ?> FCFA</strong></td>
                            <td>
                                <span class="badge bg-<?php echo ($v['places_disponibles'] > 5) ? 'success' : 'danger'; ?>">
                                    <?php echo $v['places_disponibles']; ?>
                                </span>
                            </td>
                            <td>
                                <small>
                                    <i class="fas fa-bus"></i> <?php echo htmlspecialchars($v['vehicule_nom'] ?? 'Non assigné'); ?><br>
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($v['chauffeur_nom'] ?? 'Non assigné'); ?>
                                </small>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="modifier.php?id=<?php echo $v['id']; ?>" class="btn btn-sm btn-outline-warning me-1">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <a href="supprimer.php?id=<?php echo $v['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Confirmez-vous la suppression de ce voyage ?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
