<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$message = null;
$errorAnnul = null;

// Action Client : Annulation d'une réservation (Règle RG-04 : délai > 24h avant départ)
if (isset($_GET['annuler_id'])) {
    $annuler_id = intval($_GET['annuler_id']);
    
    // Vérifier que la réservation appartient bien à l'utilisateur connecté
    $chk = $db->prepare("SELECT r.*, v.date_depart FROM reservations r JOIN voyages v ON r.voyage_id = v.id WHERE r.id = :id AND r.user_id = :uid AND r.statut != 'annulée'");
    $chk->execute([':id' => $annuler_id, ':uid' => $_SESSION['user_id']]);
    $resAnnul = $chk->fetch(PDO::FETCH_ASSOC);
    
    if ($resAnnul) {
        $departTime = strtotime($resAnnul['date_depart']);
        $now = time();
        $diffHeures = ($departTime - $now) / 3600;
        
        if ($diffHeures >= 24) {
            try {
                $db->beginTransaction();
                // 1. Mettre le statut à 'annulée'
                $db->prepare("UPDATE reservations SET statut = 'annulée' WHERE id = :id")->execute([':id' => $annuler_id]);
                // 2. Libérer immédiatement les places (RG-05)
                $db->prepare("UPDATE voyages SET places_disponibles = places_disponibles + :p WHERE id = :vid")
                   ->execute([':p' => $resAnnul['nombre_places'], ':vid' => $resAnnul['voyage_id']]);
                // 3. Mettre à jour le ticket
                $db->prepare("UPDATE tickets SET statut = 'annulé' WHERE reservation_id = :id")->execute([':id' => $annuler_id]);
                $db->commit();
                
                header("Location: mes_reservations.php?cancelled=1");
                exit();
            } catch(Exception $e) {
                $db->rollBack();
                $errorAnnul = "Erreur lors de l'annulation : " . $e->getMessage();
            }
        } else {
            $errorAnnul = "Conformément à la règle RG-04, l'annulation en ligne n'est possible qu'au moins 24 heures avant le départ. Veuillez contacter la gare d'embarquement.";
        }
    }
}

// Récupérer les réservations de l'utilisateur
$query = "SELECT r.*, v.ville_depart, v.ville_arrivee, v.gare_depart, v.date_depart, v.date_arrivee, v.prix, v.compagnie,
          t.numero_ticket, t.statut as ticket_statut,
          p.methode, p.statut as paiement_statut
          FROM reservations r
          JOIN voyages v ON r.voyage_id = v.id
          LEFT JOIN tickets t ON r.id = t.reservation_id
          LEFT JOIN paiements p ON r.id = p.reservation_id
          WHERE r.user_id = :user_id
          ORDER BY r.date_reservation DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1"><i class="fas fa-ticket-alt text-primary"></i> Mes Réservations de Bus</h2>
                <p class="text-muted mb-0">Consultez vos billets confirmés et vos voyages à venir au Tchad.</p>
            </div>
            <a href="index.php" class="btn btn-primary fw-bold">
                <i class="fas fa-plus-circle"></i> Réserver un autre trajet
            </a>
        </div>
    </div>
    
    <div class="col-12">
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> Votre réservation a été enregistrée avec succès !
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['cancelled'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i> Votre réservation a été annulée et vos places ont été libérées.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if($errorAnnul): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($errorAnnul); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if(count($reservations) > 0): ?>
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-primary text-nowrap">
                                <tr>
                                    <th>Réf</th>
                                    <th>Trajet & Compagnie</th>
                                    <th>Gare d'embarquement</th>
                                    <th>Départ</th>
                                    <th>Places</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions Disponibles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($reservations as $res): 
                                    $montant = $res['prix'] * $res['nombre_places'];
                                    $departTime = strtotime($res['date_depart']);
                                    $diffHeures = ($departTime - time()) / 3600;
                                    $peutAnnuler = ($res['statut'] != 'annulée' && $diffHeures >= 24);
                                ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border font-monospace">#<?php echo $res['id']; ?></span></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($res['ville_depart']); ?> → <?php echo htmlspecialchars($res['ville_arrivee']); ?></strong><br>
                                        <small class="text-muted"><i class="fas fa-bus"></i> <?php echo htmlspecialchars($res['compagnie'] ?? 'Tchad Transport'); ?></small>
                                    </td>
                                    <td>
                                        <small><i class="fas fa-map-marker-alt text-danger"></i> <?php echo htmlspecialchars($res['gare_depart'] ?? 'Gare Routière'); ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?php echo date('d/m/Y H:i', $departTime); ?></span>
                                        <?php if($diffHeures > 0 && $diffHeures < 24): ?>
                                            <br><small class="text-danger"><i class="fas fa-clock"></i> Départ imminente (< 24h)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo $res['nombre_places']; ?></span></td>
                                    <td><strong class="text-success"><?php echo number_format($montant, 0, ',', ' '); ?> FCFA</strong></td>
                                    <td>
                                        <?php if($res['statut'] == 'confirmée'): ?>
                                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Confirmé</span>
                                        <?php elseif($res['statut'] == 'en_attente_guichet'): ?>
                                            <span class="badge bg-info text-dark"><i class="fas fa-clock"></i> Au guichet</span>
                                        <?php elseif($res['statut'] == 'annulée'): ?>
                                            <span class="badge bg-danger"><i class="fas fa-ban"></i> Annulé</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half"></i> En attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <!-- Action Voir Billet -->
                                        <?php if($res['statut'] == 'confirmée' || $res['statut'] == 'en_attente_guichet'): ?>
                                            <a href="ticket.php?id=<?php echo $res['id']; ?>" class="btn btn-sm btn-outline-primary fw-bold me-1">
                                                <i class="fas fa-print"></i> Billet
                                            </a>
                                        <?php elseif($res['statut'] == 'en_attente'): ?>
                                            <a href="paiement.php?id=<?php echo $res['id']; ?>" class="btn btn-sm btn-success fw-bold me-1">
                                                <i class="fas fa-credit-card"></i> Payer
                                            </a>
                                        <?php endif; ?>

                                        <!-- Action Annuler Réservation (RG-04) -->
                                        <?php if($peutAnnuler): ?>
                                            <a href="mesreservation.php?annuler_id=<?php echo $res['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Confirmez-vous l\'annulation de cette réservation ? Vos places seront immédiatement libérées.')">
                                                <i class="fas fa-times"></i> Annuler
                                            </a>
                                        <?php elseif($res['statut'] != 'annulée'): ?>
                                            <button class="btn btn-sm btn-light border text-muted" disabled title="Annulation non autorisée à moins de 24h du départ">
                                                <i class="fas fa-lock"></i> Fixé
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0 text-center p-5 rounded-4">
                <i class="fas fa-ticket-alt fa-4x text-muted mb-3 opacity-50"></i>
                <h4 class="fw-bold">Vous n'avez encore aucune réservation.</h4>
                <p class="text-muted">Explorez nos départs réguliers vers toutes les régions du Tchad.</p>
                <div class="mt-2">
                    <a href="index.php" class="btn btn-primary btn-lg fw-bold px-4 shadow-sm">
                        <i class="fas fa-search"></i> Réserver mon premier voyage
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
