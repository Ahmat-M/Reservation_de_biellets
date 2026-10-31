<?php
require_once '../config/database.php';
include '../includes/header.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$stats = [];

// Nombre de voyages à venir
$query = "SELECT COUNT(*) as total FROM voyages WHERE date_depart > NOW()";
$stmt = $db->query($query);
$stats['voyages'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Nombre de réservations aujourd'hui
$query = "SELECT COUNT(*) as total FROM reservations WHERE DATE(date_reservation) = CURDATE()";
$stmt = $db->query($query);
$stats['reservations_jour'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Chiffre d'affaires global ou du mois
$query = "SELECT SUM(p.montant) as total FROM paiements p WHERE p.statut = 'payé'";
$stmt = $db->query($query);
$stats['ca_total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Nombre d'utilisateurs clients
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'client'";
$stmt = $db->query($query);
$stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Récupérer les 10 dernières réservations
$query = "SELECT r.*, u.name as user_name, u.telephone as user_tel, 
                 v.ville_depart, v.ville_arrivee, v.prix,
                 p.methode as paiement_methode, p.statut as paiement_statut
          FROM reservations r
          JOIN users u ON r.user_id = u.id
          JOIN voyages v ON r.voyage_id = v.id
          LEFT JOIN paiements p ON r.id = p.reservation_id
          ORDER BY r.date_reservation DESC
          LIMIT 10";
$stmt = $db->query($query);
$recent_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-chart-line text-primary"></i> Tableau de bord administrateur</h2>
            <p class="text-muted mb-0">Supervision du réseau de transport interurbain au Tchad</p>
        </div>
        <div>
            <a href="voyages/index.php" class="btn btn-primary fw-bold">
                <i class="fas fa-plus-circle"></i> Nouveau départ
            </a>
        </div>
    </div>
</div>

<!-- Cartes Statistiques (KPIs) -->
<div class="row mb-4 g-3">
    <div class="col-md-3">
        <div class="kpi-card kpi-primary">
            <span class="small text-white-50 text-uppercase fw-bold">Voyages actifs</span>
            <h2 class="fw-bold mb-0 mt-1"><?php echo $stats['voyages']; ?></h2>
            <i class="fas fa-bus kpi-icon"></i>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="kpi-card kpi-success">
            <span class="small text-white-50 text-uppercase fw-bold">Réservations du jour</span>
            <h2 class="fw-bold mb-0 mt-1"><?php echo $stats['reservations_jour']; ?></h2>
            <i class="fas fa-ticket-alt kpi-icon"></i>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="kpi-card kpi-info">
            <span class="small text-white-50 text-uppercase fw-bold">Recettes encaissées</span>
            <h3 class="fw-bold mb-0 mt-1"><?php echo number_format($stats['ca_total'], 0, ',', ' '); ?> <small style="font-size: 14px;">FCFA</small></h3>
            <i class="fas fa-money-bill-wave kpi-icon"></i>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="kpi-card kpi-dark">
            <span class="small text-white-50 text-uppercase fw-bold">Clients enregistrés</span>
            <h2 class="fw-bold mb-0 mt-1"><?php echo $stats['users']; ?></h2>
            <i class="fas fa-users kpi-icon"></i>
        </div>
    </div>
</div>

<!-- Dernières réservations -->
<div class="card shadow-sm border-0 rounded-3">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-history text-primary me-2"></i> Dernières réservations passagers</h5>
        <a href="reservations/index.php" class="btn btn-sm btn-outline-primary">Voir toutes</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Voyageur</th>
                        <th>Trajet</th>
                        <th>Places</th>
                        <th>Montant</th>
                        <th>Mode règlement</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($recent_reservations)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Aucune réservation pour le moment.</td></tr>
                    <?php else: ?>
                        <?php foreach($recent_reservations as $r): 
                            $total = $r['prix'] * $r['nombre_places'];
                        ?>
                        <tr>
                            <td>#<?php echo $r['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($r['user_name']); ?></strong><br>
                                <small class="text-muted"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($r['user_tel'] ?? '-'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($r['ville_depart']); ?> → <?php echo htmlspecialchars($r['ville_arrivee']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo $r['nombre_places']; ?></span></td>
                            <td><strong class="text-success"><?php echo number_format($total, 0, ',', ' '); ?> FCFA</strong></td>
                            <td>
                                <?php 
                                    if($r['paiement_methode'] == 'airtel_money') echo '<span class="badge bg-danger">Airtel Money</span>';
                                    elseif($r['paiement_methode'] == 'moov_money') echo '<span class="badge bg-primary">Moov Money</span>';
                                    elseif($r['paiement_methode'] == 'carte') echo '<span class="badge bg-info">Carte bancaire</span>';
                                    else echo '<span class="badge bg-secondary">Guichet</span>';
                                ?>
                            </td>
                            <td>
                                <?php if($r['statut'] == 'confirmée'): ?>
                                    <span class="badge bg-success">Confirmée</span>
                                <?php elseif($r['statut'] == 'en_attente_guichet'): ?>
                                    <span class="badge bg-info text-dark">Au guichet</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">En attente</span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?php echo date('d/m H:i', strtotime($r['date_reservation'])); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
