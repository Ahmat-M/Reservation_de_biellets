<?php
require_once 'config/database.php';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

$depart = isset($_GET['depart']) ? trim($_GET['depart']) : '';
$arrivee = isset($_GET['arrivee']) ? trim($_GET['arrivee']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : '';

$query = "SELECT v.*, 
          ve.marque as vehicule_marque, ve.immatriculation,
          c.nom as chauffeur_nom, c.telephone as chauffeur_tel
          FROM voyages v
          LEFT JOIN vehicules ve ON v.vehicule_id = ve.id
          LEFT JOIN chauffeurs c ON v.chauffeur_id = c.id
          WHERE v.date_depart > NOW() 
          AND v.places_disponibles > 0";

if(!empty($depart)) {
    $query .= " AND v.ville_depart LIKE :depart";
}
if(!empty($arrivee)) {
    $query .= " AND v.ville_arrivee LIKE :arrivee";
}
if(!empty($date)) {
    $query .= " AND DATE(v.date_depart) = :date";
}

$query .= " ORDER BY v.date_depart ASC";

$stmt = $db->prepare($query);

if(!empty($depart)) {
    $depart_param = "%$depart%";
    $stmt->bindParam(':depart', $depart_param);
}
if(!empty($arrivee)) {
    $arrivee_param = "%$arrivee%";
    $stmt->bindParam(':arrivee', $arrivee_param);
}
if(!empty($date)) {
    $stmt->bindParam(':date', $date);
}

$stmt->execute();
$voyages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$villesTchad = [
    "N'Djaména", "Moundou", "Sarh", "Abéché", "Kélo", 
    "Bongor", "Doba", "Pala", "Koumra", "Mongo", "Ati", "Am Timan"
];
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body p-4">
                <h4 class="mb-3 text-primary"><i class="fas fa-filter"></i> Modifier votre recherche</h4>
                <form action="recherche.php" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Départ</label>
                        <input list="villes_list" type="text" name="depart" class="form-control" value="<?php echo htmlspecialchars($depart); ?>" placeholder="Ville de départ">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Arrivée</label>
                        <input list="villes_list" type="text" name="arrivee" class="form-control" value="<?php echo htmlspecialchars($arrivee); ?>" placeholder="Ville d'arrivée">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Date</label>
                        <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="fas fa-search"></i> Filtrer
                        </button>
                    </div>
                    <datalist id="villes_list">
                        <?php foreach($villesTchad as $ville): ?>
                            <option value="<?php echo htmlspecialchars($ville); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-3 d-flex justify-content-between align-items-center">
        <h3>
            <i class="fas fa-bus text-primary"></i> 
            <?php echo count($voyages); ?> voyage(s) trouvé(s)
        </h3>
        <?php if($depart || $arrivee): ?>
            <span class="badge bg-secondary p-2">
                <?php if($depart) echo "De : " . htmlspecialchars($depart); ?>
                <?php if($arrivee) echo " → Vers : " . htmlspecialchars($arrivee); ?>
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if(count($voyages) > 0): ?>
<div class="row">
    <?php foreach($voyages as $voyage): ?>
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="fas fa-building"></i> <?php echo htmlspecialchars($voyage['compagnie'] ?? 'Tchad Transport'); ?></span>
                <span class="badge bg-warning text-dark"><i class="fas fa-chair"></i> <?php echo $voyage['places_disponibles']; ?> places disponibles</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="text-dark fw-bold mb-2">
                            <?php echo htmlspecialchars($voyage['ville_depart']); ?> 
                            <i class="fas fa-long-arrow-alt-right text-primary"></i> 
                            <?php echo htmlspecialchars($voyage['ville_arrivee']); ?>
                        </h4>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-map-marker-alt text-danger"></i> <?php echo htmlspecialchars($voyage['gare_depart'] ?? 'Gare Routière'); ?>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-calendar-alt text-primary"></i> 
                            <strong>Départ :</strong> <?php echo date('d/m/Y H:i', strtotime($voyage['date_depart'])); ?>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-clock text-muted"></i> 
                            <strong>Arrivée est. :</strong> <?php echo date('d/m/Y H:i', strtotime($voyage['date_arrivee'])); ?>
                        </p>
                        <p class="mb-1 small text-muted">
                            <i class="fas fa-car-side"></i> 
                            <?php echo htmlspecialchars($voyage['vehicule_marque'] ?? 'Bus VIP'); ?> (<?php echo htmlspecialchars($voyage['immatriculation'] ?? 'Tchad'); ?>)
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end text-start mt-3 mt-md-0 d-flex flex-column justify-content-between">
                        <div>
                            <span class="text-muted small">Tarif</span>
                            <h3 class="text-success fw-bold"><?php echo number_format($voyage['prix'], 0, ',', ' '); ?> <small style="font-size: 14px;">FCFA</small></h3>
                        </div>
                        <a href="reservation.php?id=<?php echo $voyage['id']; ?>" class="btn btn-primary w-100 fw-bold py-2 mt-2">
                            <i class="fas fa-ticket-alt"></i> Réserver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="alert alert-info text-center py-5 shadow-sm rounded-3">
    <i class="fas fa-info-circle fa-3x text-primary mb-3"></i>
    <h4>Aucun départ correspondant à vos critères de recherche.</h4>
    <p class="text-muted">Essayez avec une autre date ou vérifiez les villes sélectionnées.</p>
    <a href="index.php" class="btn btn-outline-primary mt-2"><i class="fas fa-arrow-left"></i> Retour à tous les départs</a>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
