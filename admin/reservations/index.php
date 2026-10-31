<?php
require_once '../../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Action : Confirmer une réservation au guichet
if(isset($_GET['valider_id'])) {
    $valider_id = intval($_GET['valider_id']);
    
    // Mettre à jour la réservation
    $stmt = $db->prepare("UPDATE reservations SET statut = 'confirmée' WHERE id = :id");
    $stmt->execute([':id' => $valider_id]);

    // Enregistrer un paiement guichet si pas encore enregistré
    $chkPaiement = $db->prepare("SELECT id FROM paiements WHERE reservation_id = :id");
    $chkPaiement->execute([':id' => $valider_id]);
    if(!$chkPaiement->fetch()) {
        $resQuery = $db->prepare("SELECT r.*, v.prix FROM reservations r JOIN voyages v ON r.voyage_id = v.id WHERE r.id = :id");
        $resQuery->execute([':id' => $valider_id]);
        $resData = $resQuery->fetch();
        if($resData) {
            $montant = $resData['prix'] * $resData['nombre_places'];
            $insP = $db->prepare("INSERT INTO paiements (reservation_id, montant, methode, statut, date_paiement) VALUES (:res_id, :montant, 'especes_guichet', 'payé', NOW())");
            $insP->execute([':res_id' => $valider_id, ':montant' => $montant]);
        }
    }
    
    // Mettre à jour le statut du ticket
    $db->prepare("UPDATE tickets SET statut = 'valide' WHERE reservation_id = :id")->execute([':id' => $valider_id]);

    header("Location: index.php?validated=1");
    exit();
}

// Action : Annuler une réservation par l'administrateur
if(isset($_GET['annuler_id'])) {
    $annuler_id = intval($_GET['annuler_id']);
    $resQ = $db->prepare("SELECT * FROM reservations WHERE id = :id AND statut != 'annulée'");
    $resQ->execute([':id' => $annuler_id]);
    $res = $resQ->fetch();
    if($res) {
        $db->beginTransaction();
        $db->prepare("UPDATE reservations SET statut = 'annulée' WHERE id = :id")->execute([':id' => $annuler_id]);
        $db->prepare("UPDATE voyages SET places_disponibles = places_disponibles + :p WHERE id = :vid")
           ->execute([':p' => $res['nombre_places'], ':vid' => $res['voyage_id']]);
        $db->prepare("UPDATE tickets SET statut = 'annulé' WHERE reservation_id = :id")->execute([':id' => $annuler_id]);
        $db->commit();
    }
    header("Location: index.php?cancelled=1");
    exit();
}

// Action : Marquer une réservation comme terminée
if(isset($_GET['terminer_id'])) {
    $terminer_id = intval($_GET['terminer_id']);
    $db->prepare("UPDATE reservations SET statut = 'terminée' WHERE id = :id")->execute([':id' => $terminer_id]);
    header("Location: index.php?completed=1");
    exit();
}

$query = "SELECT r.*, u.name as user_name, u.email as user_email, u.telephone as user_tel, 
                 v.ville_depart, v.ville_arrivee, v.gare_depart, v.date_depart, v.prix,
                 p.montant, p.methode as paiement_methode, p.statut as paiement_statut
          FROM reservations r
          JOIN users u ON r.user_id = u.id
          JOIN voyages v ON r.voyage_id = v.id
          LEFT JOIN paiements p ON r.id = p.reservation_id
          ORDER BY r.date_reservation DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1"><i class="fas fa-ticket-alt text-primary"></i> Gestion des Réservations</h2>
                <small class="text-muted">Suivi des passagers et encaissements aux gares routières du Tchad</small>
            </div>
        </div>

        <?php if(isset($_GET['validated'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> La réservation a été validée et encaissée avec succès !
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary text-nowrap">
                            <tr>
                                <th>#</th>
                                <th>Passager</th>
                                <th>Trajet & Embarquement</th>
                                <th>Départ</th>
                                <th>Places</th>
                                <th>Règlement</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($reservations)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">Aucune réservation trouvée.</td></tr>
                            <?php else: ?>
                                <?php foreach($reservations as $res): 
                                    $montant = $res['prix'] * $res['nombre_places'];
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $res['id']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($res['user_name']); ?></strong><br>
                                        <small class="text-muted"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($res['user_tel'] ?? '-'); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($res['ville_depart']); ?> → <?php echo htmlspecialchars($res['ville_arrivee']); ?></strong><br>
                                        <small class="text-muted"><i class="fas fa-map-marker-alt text-danger"></i> <?php echo htmlspecialchars($res['gare_depart'] ?? 'Gare'); ?></small>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($res['date_depart'])); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $res['nombre_places']; ?></span></td>
                                    <td>
                                        <strong class="text-success"><?php echo number_format($montant, 0, ',', ' '); ?> FCFA</strong><br>
                                        <small>
                                            <?php 
                                                if($res['paiement_methode'] == 'airtel_money') echo '<span class="badge bg-danger">Airtel Money</span>';
                                                elseif($res['paiement_methode'] == 'moov_money') echo '<span class="badge bg-primary">Moov Money</span>';
                                                elseif($res['paiement_methode'] == 'carte') echo '<span class="badge bg-info">Carte bancaire</span>';
                                                else echo '<span class="badge bg-secondary">Espèces guichet</span>';
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if($res['statut'] == 'confirmée'): ?>
                                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Confirmée</span>
                                        <?php elseif($res['statut'] == 'en_attente_guichet'): ?>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Au guichet</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">En attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <?php if($res['statut'] == 'en_attente_guichet'): ?>
                                            <a href="index.php?valider_id=<?php echo $res['id']; ?>" class="btn btn-sm btn-success fw-bold" onclick="return confirm('Confirmer l\'encaissement en espèces et valider le billet ?')">
                                                <i class="fas fa-cash-register"></i> Encaisser
                                            </a>
                                        <?php endif; ?>
                                        <a href="../../ticket.php?id=<?php echo $res['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fas fa-print"></i> Billet
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
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
