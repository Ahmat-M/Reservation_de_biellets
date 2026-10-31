<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$voyage_id = intval($_GET['id']);

// Récupérer les détails du voyage
$query = "SELECT v.*, ve.marque, ve.immatriculation, c.nom as chauffeur_nom, c.telephone as chauffeur_tel
          FROM voyages v
          LEFT JOIN vehicules ve ON v.vehicule_id = ve.id
          LEFT JOIN chauffeurs c ON v.chauffeur_id = c.id
          WHERE v.id = :id AND v.date_depart > NOW() AND v.places_disponibles > 0";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $voyage_id);
$stmt->execute();
$voyage = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$voyage) {
    echo "<div class='alert alert-danger text-center my-5'><h4>Voyage introuvable ou complet.</h4><a href='index.php' class='btn btn-primary mt-3'>Retour à l'accueil</a></div>";
    include 'includes/footer.php';
    exit();
}

$error = null;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_places = intval($_POST['nombre_places']);
    $mode_reservation = in_array($_POST['mode_reservation'], ['en_ligne', 'guichet']) ? $_POST['mode_reservation'] : 'en_ligne';
    
    if($nombre_places < 1) {
        $error = "Veuillez sélectionner au moins une place.";
    } elseif($nombre_places > $voyage['places_disponibles']) {
        $error = "Désolé, il ne reste que " . $voyage['places_disponibles'] . " place(s) disponible(s).";
    } else {
        try {
            $db->beginTransaction();

            // Insérer la réservation
            $statutInitial = ($mode_reservation == 'guichet') ? 'en_attente_guichet' : 'en_attente';
            $query_res = "INSERT INTO reservations (user_id, voyage_id, nombre_places, date_reservation, statut, mode_reservation) 
                          VALUES (:user_id, :voyage_id, :nombre_places, NOW(), :statut, :mode_reservation)";
            $stmt_res = $db->prepare($query_res);
            $stmt_res->bindParam(':user_id', $_SESSION['user_id']);
            $stmt_res->bindParam(':voyage_id', $voyage_id);
            $stmt_res->bindParam(':nombre_places', $nombre_places);
            $stmt_res->bindParam(':statut', $statutInitial);
            $stmt_res->bindParam(':mode_reservation', $mode_reservation);
            $stmt_res->execute();
            
            $reservation_id = $db->lastInsertId();
            
            // Décrémenter les places disponibles
            $update_query = "UPDATE voyages SET places_disponibles = places_disponibles - :places WHERE id = :id AND places_disponibles >= :places";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':places', $nombre_places);
            $update_stmt->bindParam(':id', $voyage_id);
            $update_stmt->execute();

            if($update_stmt->rowCount() == 0) {
                throw new Exception("Les places ne sont plus disponibles. Veuillez réessayer.");
            }

            // Si réservation au guichet, générer immédiatement un bon de réservation / ticket en attente
            if($mode_reservation == 'guichet') {
                $numero_ticket = 'TD-GKT-' . date('Ymd') . '-' . rand(100, 999) . '-' . $reservation_id;
                $query_ticket = "INSERT INTO tickets (reservation_id, numero_ticket, statut) 
                                 VALUES (:reservation_id, :numero_ticket, 'en_attente_guichet')";
                $stmt_ticket = $db->prepare($query_ticket);
                $stmt_ticket->execute([
                    ':reservation_id' => $reservation_id,
                    ':numero_ticket' => $numero_ticket
                ]);
            }

            $db->commit();
            
            if($mode_reservation == 'en_ligne') {
                header("Location: paiement.php?id=" . $reservation_id);
            } else {
                header("Location: ticket.php?id=" . $reservation_id . "&guichet=1");
            }
            exit();
            
        } catch(Exception $e) {
            $db->rollBack();
            $error = "Erreur lors de la réservation : " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-9 col-lg-8">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0"><i class="fas fa-ticket-alt"></i> Réservation de billet au Tchad</h4>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4 p-3 bg-light rounded-3">
                    <div class="col-md-7">
                        <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($voyage['compagnie'] ?? 'Tchad Transport'); ?></span>
                        <h4 class="fw-bold text-dark">
                            <?php echo htmlspecialchars($voyage['ville_depart']); ?> 
                            <i class="fas fa-long-arrow-alt-right text-primary"></i> 
                            <?php echo htmlspecialchars($voyage['ville_arrivee']); ?>
                        </h4>
                        <p class="mb-1 text-muted">
                            <i class="fas fa-map-marker-alt text-danger"></i> <strong>Lieu de départ :</strong> <?php echo htmlspecialchars($voyage['gare_depart'] ?? 'Gare Routière'); ?>
                        </p>
                        <p class="mb-1 text-muted">
                            <i class="fas fa-calendar-alt text-primary"></i> <strong>Départ prévu :</strong> <?php echo date('d/m/Y à H:i', strtotime($voyage['date_depart'])); ?>
                        </p>
                        <p class="mb-0 text-muted">
                            <i class="fas fa-bus"></i> <strong>Bus :</strong> <?php echo htmlspecialchars($voyage['marque']); ?> (<?php echo htmlspecialchars($voyage['immatriculation']); ?>)
                        </p>
                    </div>
                    <div class="col-md-5 text-md-end text-start mt-3 mt-md-0 border-start ps-md-4">
                        <div class="mb-2">
                            <span class="text-muted small">Tarif unitaire :</span>
                            <div class="h3 fw-bold text-success mb-0"><?php echo number_format($voyage['prix'], 0, ',', ' '); ?> FCFA</div>
                        </div>
                        <span class="badge bg-success-soft text-success border border-success p-2">
                            <i class="fas fa-chair"></i> <?php echo $voyage['places_disponibles']; ?> places restantes
                        </span>
                    </div>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-danger shadow-sm"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nombre_places" class="form-label fw-bold">Nombre de places souhaité *</label>
                        <input type="number" class="form-control form-control-lg" id="nombre_places" name="nombre_places" 
                               min="1" max="<?php echo $voyage['places_disponibles']; ?>" value="1" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Mode de règlement *</label>
                        
                        <div class="card p-3 mb-2 border-primary">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode_reservation" id="opt_online" value="en_ligne" checked>
                                <label class="form-check-label fw-bold" for="opt_online">
                                    <i class="fas fa-mobile-alt text-primary"></i> Paiement Mobile Tchad (Airtel Money / Moov Money)
                                </label>
                                <div class="text-muted small ps-1">Validation immédiate et délivrance du billet électronique officiel avec QR Code.</div>
                            </div>
                        </div>

                        <div class="card p-3 border">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode_reservation" id="opt_counter" value="guichet">
                                <label class="form-check-label fw-bold" for="opt_counter">
                                    <i class="fas fa-store text-secondary"></i> Paiement en espèces au guichet de la gare
                                </label>
                                <div class="text-muted small ps-1">Votre place est pré-réservée. Vous réglerez directement à la gare avant le départ.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-primary d-flex justify-content-between align-items-center p-3 mb-4">
                        <div>
                            <span class="text-muted">Total estimé à payer :</span>
                            <div class="h3 fw-bold text-primary mb-0"><span id="total"><?php echo number_format($voyage['prix'], 0, ',', ' '); ?></span> FCFA</div>
                        </div>
                        <i class="fas fa-receipt fa-2x text-primary opacity-50"></i>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold py-3 shadow-sm">
                            <i class="fas fa-arrow-right"></i> Continuer vers la confirmation
                        </button>
                        <a href="index.php" class="btn btn-link text-muted text-center">Annuler et revenir aux voyages</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('nombre_places').addEventListener('input', function() {
    var prix = <?php echo $voyage['prix']; ?>;
    var places = parseInt(this.value) || 0;
    var total = prix * places;
    document.getElementById('total').innerHTML = total.toLocaleString('fr-FR');
});
</script>

<?php include 'includes/footer.php'; ?>
