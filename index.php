<?php
require_once 'config/database.php';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Récupérer les prochains voyages au Tchad
$query = "SELECT v.*, 
          ve.marque as vehicule_marque, ve.immatriculation,
          c.nom as chauffeur_nom, c.telephone as chauffeur_tel
          FROM voyages v
          LEFT JOIN vehicules ve ON v.vehicule_id = ve.id
          LEFT JOIN chauffeurs c ON v.chauffeur_id = c.id
          WHERE v.date_depart > NOW() 
          ORDER BY v.date_depart ASC 
          LIMIT 9";
$stmt = $db->prepare($query);
$stmt->execute();
$voyages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Liste des principales villes du Tchad pour la recherche
$villesTchad = [
    "N'Djaména", "Moundou", "Sarh", "Abéché", "Kélo", 
    "Bongor", "Doba", "Pala", "Koumra", "Mongo", "Ati", "Am Timan"
];
?>

<!-- Bannière d'accueil -->
<div class="p-5 mb-4 bg-light rounded-3 shadow-sm border text-center text-md-start" style="background: linear-gradient(135deg, #0d3b66 0%, #004080 100%); color: white;">
    <div class="container-fluid py-3">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-warning text-dark mb-2 px-3 py-2"><i class="fas fa-flag"></i> Réseau National du Tchad</span>
                <h1 class="display-5 fw-bold">Voyagez partout au Tchad en toute sérénité</h1>
                <p class="col-md-11 fs-5">Réservez vos billets de bus en ligne entre N'Djaména, Moundou, Abéché, Sarh et toutes les grandes villes. Paiement facile par <strong>Airtel Money</strong>, <strong>Moov Money</strong> ou au guichet.</p>
            </div>
            <div class="col-md-4 text-center d-none d-md-block">
                <i class="fas fa-bus-alt fa-7x text-white opacity-75"></i>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de recherche de trajet -->
<div class="row justify-content-center mb-5" style="margin-top: -30px;">
    <div class="col-md-11">
        <div class="card shadow-lg border-0 rounded-3">
            <div class="card-body p-4">
                <h4 class="card-title text-center text-primary mb-4 fw-bold">
                    <i class="fas fa-search-location"></i> Rechercher un billet de bus au Tchad
                </h4>
                <form action="recherche.php" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-map-marker-alt text-danger"></i> Ville de départ</label>
                            <input list="villes_list" type="text" name="depart" class="form-control form-control-lg" placeholder="Ex: N'Djaména" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-map-pin text-success"></i> Ville d'arrivée</label>
                            <input list="villes_list" type="text" name="arrivee" class="form-control form-control-lg" placeholder="Ex: Moundou, Abéché" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold"><i class="fas fa-calendar-day text-primary"></i> Date</label>
                            <input type="date" name="date" class="form-control form-control-lg" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                                <i class="fas fa-search"></i> Trouver
                            </button>
                        </div>
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

<!-- Avantages clients -->
<div class="row text-center mb-5 g-4">
    <div class="col-md-4">
        <div class="p-3 border rounded-3 bg-white h-100 shadow-sm">
            <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
            <h5 class="fw-bold">Paiement Mobile Tchadien</h5>
            <p class="text-muted">Payez instantanément par <strong>Airtel Money</strong> ou <strong>Moov Money</strong> sans vous déplacer.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 border rounded-3 bg-white h-100 shadow-sm">
            <i class="fas fa-ticket-alt fa-3x text-success mb-3"></i>
            <h5 class="fw-bold">E-Ticket & Embarquement Rapide</h5>
            <p class="text-muted">Recevez votre ticket numérique instantanément et présentez-le à la gare routière.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 border rounded-3 bg-white h-100 shadow-sm">
            <i class="fas fa-shield-alt fa-3x text-warning mb-3"></i>
            <h5 class="fw-bold">Flotte & Chauffeurs Vérifiés</h5>
            <p class="text-muted">Voyages assurés par des agences reconnues (Abou Simbil, Toumaï Express, SNDT...).</p>
        </div>
    </div>
</div>

<!-- Prochains départs disponibles -->
<div class="row mb-5">
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark"><i class="fas fa-clock text-primary"></i> Prochains départs interurbains</h2>
            <small class="text-muted">Lignes régulières au départ de N'Djaména et des provinces</small>
        </div>
        <a href="recherche.php" class="btn btn-outline-primary">Voir tous les départs <i class="fas fa-arrow-right"></i></a>
    </div>

    <?php if(empty($voyages)): ?>
        <div class="col-12">
            <div class="alert alert-info text-center py-4">
                <i class="fas fa-info-circle fa-2x mb-2"></i>
                <h5>Aucun départ immédiat programmé pour l'instant.</h5>
                <p>Consultez la recherche pour d'autres dates ou contactez votre agence.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach($voyages as $voyage): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 shadow-sm border-0 rounded-3 hover-shadow">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <span class="badge bg-primary px-3 py-2">
                        <i class="fas fa-bus"></i> <?php echo htmlspecialchars($voyage['compagnie'] ?? 'Tchad Transport'); ?>
                    </span>
                    <span class="badge bg-light text-dark border">
                        <i class="fas fa-chair text-success"></i> <?php echo $voyage['places_disponibles']; ?> places libres
                    </span>
                </div>
                <div class="card-body">
                    <div class="my-3 text-center">
                        <h5 class="card-title fw-bold text-dark mb-1">
                            <?php echo htmlspecialchars($voyage['ville_depart']); ?> 
                            <i class="fas fa-long-arrow-alt-right text-primary mx-2"></i> 
                            <?php echo htmlspecialchars($voyage['ville_arrivee']); ?>
                        </h5>
                        <small class="text-muted">
                            <i class="fas fa-map-pin text-danger"></i> <?php echo htmlspecialchars($voyage['gare_depart'] ?? 'Gare Routière'); ?>
                        </small>
                    </div>

                    <hr class="my-3 text-muted">

                    <div class="d-flex justify-content-between small mb-2">
                        <span><i class="fas fa-calendar-alt text-primary"></i> <strong>Départ :</strong></span>
                        <span><?php echo date('d/m/Y H:i', strtotime($voyage['date_depart'])); ?></span>
                    </div>
                    <div class="d-flex justify-content-between small mb-2">
                        <span><i class="fas fa-clock text-muted"></i> <strong>Arrivée est. :</strong></span>
                        <span><?php echo date('d/m/Y H:i', strtotime($voyage['date_arrivee'])); ?></span>
                    </div>
                    <div class="d-flex justify-content-between small mb-3">
                        <span><i class="fas fa-car-side text-muted"></i> <strong>Bus :</strong></span>
                        <span><?php echo htmlspecialchars($voyage['vehicule_marque'] ?? 'Bus VIP'); ?></span>
                    </div>

                    <div class="bg-light p-2 rounded text-center mb-3">
                        <span class="text-muted small">Tarif par passager</span><br>
                        <span class="h4 fw-bold text-success mb-0"><?php echo number_format($voyage['prix'], 0, ',', ' '); ?> FCFA</span>
                    </div>

                    <a href="reservation.php?id=<?php echo $voyage['id']; ?>" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                        <i class="fas fa-ticket-alt"></i> Réserver ma place
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
