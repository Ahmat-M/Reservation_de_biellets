<?php
require_once 'config/database.php';
include 'includes/header.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: mes_reservations.php");
    exit();
}

$reservation_id = intval($_GET['id']);

// Récupérer toutes les informations du ticket et de la réservation
$query = "SELECT t.numero_ticket, t.statut as ticket_statut,
          r.*, u.name as client_nom, u.email as client_email, u.telephone as client_tel,
          v.ville_depart, v.ville_arrivee, v.gare_depart, v.date_depart, v.date_arrivee, v.prix, v.compagnie,
          ve.marque as vehicule_marque, ve.immatriculation,
          c.nom as chauffeur_nom, c.telephone as chauffeur_tel,
          p.montant, p.methode, p.numero_transaction, p.date_paiement
          FROM reservations r
          JOIN voyages v ON r.voyage_id = v.id
          JOIN users u ON r.user_id = u.id
          LEFT JOIN tickets t ON r.id = t.reservation_id
          LEFT JOIN vehicules ve ON v.vehicule_id = ve.id
          LEFT JOIN chauffeurs c ON v.chauffeur_id = c.id
          LEFT JOIN paiements p ON r.id = p.reservation_id
          WHERE r.id = :id AND (r.user_id = :user_id OR :is_admin = 'admin')";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $reservation_id);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$user_role = $_SESSION['user_role'] ?? 'client';
$stmt->bindParam(':is_admin', $user_role);
$stmt->execute();
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$ticket) {
    echo "<div class='alert alert-danger text-center my-5'><h4>Billet introuvable.</h4><a href='mes_reservations.php' class='btn btn-primary mt-3'>Mes réservations</a></div>";
    include 'includes/footer.php';
    exit();
}

$estGuichet = ($ticket['mode_reservation'] == 'guichet');
$codeTicket = $ticket['numero_ticket'] ?? ('TD-RES-' . $ticket['id']);
$qrData = urlencode("Billet: " . $codeTicket . " | Passager: " . $ticket['client_nom'] . " | Trajet: " . $ticket['ville_depart'] . "-" . $ticket['ville_arrivee']);
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=" . $qrData;
?>

<style>
@media print {
    nav, footer, .btn-print-hide, .no-print {
        display: none !important;
    }
    .ticket-container {
        border: 2px solid #000 !important;
        box-shadow: none !important;
        margin: 0 !important;
        width: 100% !important;
    }
}
.ticket-card {
    border-radius: 12px;
    border: 2px dashed #0d6efd;
    background: #fff;
}
.ticket-header {
    background: linear-gradient(135deg, #0d3b66 0%, #004080 100%);
    color: white;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}
</style>

<div class="row justify-content-center my-4">
    <div class="col-lg-8">
        
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show no-print mb-4 shadow-sm" role="alert">
                <i class="fas fa-check-circle fa-lg me-2"></i> <strong>Félicitations !</strong> Votre paiement a été validé avec succès. Votre billet de bus est prêt.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($estGuichet): ?>
            <div class="alert alert-warning no-print mb-4 shadow-sm" role="alert">
                <i class="fas fa-info-circle fa-lg me-2"></i> <strong>Réservation au guichet enregistrée :</strong> Veuillez présenter ce bon à la gare de départ (<?php echo htmlspecialchars($ticket['gare_depart']); ?>) au moins 1 heure avant le départ pour régler et retirer votre place.
            </div>
        <?php endif; ?>

        <div class="card ticket-card shadow-lg ticket-container">
            <div class="ticket-header p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0 text-white"><i class="fas fa-bus-alt"></i> TCHAD-BUS EXPRESS</h3>
                    <small class="text-light opacity-75">Billet électronique officiel - Transport Interurbain République du Tchad</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-warning text-dark px-3 py-2 fw-bold">
                        <?php echo $estGuichet ? 'À RÉGLER EN GARE' : 'BILLET CONFIRMÉ / PAYÉ'; ?>
                    </span>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row align-items-center border-bottom pb-3 mb-3">
                    <div class="col-md-8">
                        <span class="text-muted small">Numéro officiel du ticket :</span>
                        <div class="h3 fw-bold text-primary mb-1"><?php echo htmlspecialchars($codeTicket); ?></div>
                        <span class="text-muted small">Réservation #<?php echo $ticket['id']; ?> | Émis le <?php echo date('d/m/Y à H:i'); ?></span>
                    </div>
                    <div class="col-md-4 text-center text-md-end mt-3 mt-md-0">
                        <img src="<?php echo $qrUrl; ?>" alt="QR Code Ticket" class="img-thumbnail" style="width: 120px; height: 120px;">
                        <div class="small text-muted mt-1">Scanner à l'embarquement</div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="bg-light p-3 rounded h-100">
                            <h6 class="fw-bold text-dark border-bottom pb-1"><i class="fas fa-user text-primary me-2"></i> Passager</h6>
                            <p class="mb-1"><strong>Nom complet :</strong> <?php echo htmlspecialchars($ticket['client_nom']); ?></p>
                            <p class="mb-1"><strong>Téléphone :</strong> <?php echo htmlspecialchars($ticket['client_tel'] ?? 'Non renseigné'); ?></p>
                            <p class="mb-0"><strong>Email :</strong> <?php echo htmlspecialchars($ticket['client_email']); ?></p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="bg-light p-3 rounded h-100">
                            <h6 class="fw-bold text-dark border-bottom pb-1"><i class="fas fa-route text-success me-2"></i> Trajet & Horaires</h6>
                            <p class="mb-1"><strong>Départ :</strong> <?php echo htmlspecialchars($ticket['ville_depart']); ?> → <?php echo htmlspecialchars($ticket['ville_arrivee']); ?></p>
                            <p class="mb-1"><strong>Lieu d'embarquement :</strong> <?php echo htmlspecialchars($ticket['gare_depart'] ?? 'Gare Routière'); ?></p>
                            <p class="mb-0"><strong>Date & heure :</strong> <span class="text-danger fw-bold"><?php echo date('d/m/Y à H:i', strtotime($ticket['date_depart'])); ?></span></p>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="border p-3 rounded">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-truck-moving text-secondary me-2"></i> Véhicule & Compagnie</h6>
                            <p class="mb-1"><strong>Compagnie :</strong> <?php echo htmlspecialchars($ticket['compagnie'] ?? 'Abou Simbil Transport'); ?></p>
                            <p class="mb-1"><strong>Bus :</strong> <?php echo htmlspecialchars($ticket['vehicule_marque'] ?? 'Bus VIP'); ?> (<?php echo htmlspecialchars($ticket['immatriculation'] ?? '-'); ?>)</p>
                            <p class="mb-0"><strong>Chauffeur :</strong> <?php echo htmlspecialchars($ticket['chauffeur_nom'] ?? 'Équipe Compagnie'); ?></p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="border p-3 rounded">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-money-bill-wave text-success me-2"></i> Facturation</h6>
                            <p class="mb-1"><strong>Nombre de places :</strong> <?php echo $ticket['nombre_places']; ?> place(s)</p>
                            <p class="mb-1"><strong>Mode règlement :</strong> 
                                <?php 
                                    if($ticket['methode'] == 'airtel_money') echo 'Airtel Money Tchad';
                                    elseif($ticket['methode'] == 'moov_money') echo 'Moov Africa Tchad';
                                    elseif($ticket['methode'] == 'carte') echo 'Carte Bancaire';
                                    else echo 'Espèces au guichet';
                                ?>
                            </p>
                            <p class="mb-0"><strong>Total payé :</strong> <span class="h5 fw-bold text-success"><?php echo number_format($ticket['prix'] * $ticket['nombre_places'], 0, ',', ' '); ?> FCFA</span></p>
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border small text-muted">
                    <h6 class="fw-bold text-dark mb-1"><i class="fas fa-exclamation-triangle text-warning me-1"></i> Consignes importantes aux voyageurs (Tchad) :</h6>
                    <ul class="mb-0 ps-3">
                        <li>Présentez-vous à la gare routière au moins <strong>45 minutes</strong> avant l'heure de départ.</li>
                        <li>Munissez-vous obligatoirement d'une <strong>pièce d'identité valide (CNI tchadienne ou passeport)</strong> pour les contrôles en route.</li>
                        <li>Chaque passager a droit à une franchise bagage de 20 kg.</li>
                    </ul>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 no-print">
                    <a href="mes_reservations.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Mes réservations
                    </a>
                    <button onclick="window.print()" class="btn btn-primary fw-bold px-4 py-2 shadow-sm">
                        <i class="fas fa-print"></i> Imprimer / Télécharger en PDF
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
