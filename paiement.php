<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: mes_reservations.php");
    exit();
}

$reservation_id = intval($_GET['id']);

// Récupérer les détails de la réservation
$query = "SELECT r.*, v.ville_depart, v.ville_arrivee, v.gare_depart, v.date_depart, v.prix, v.compagnie
          FROM reservations r
          JOIN voyages v ON r.voyage_id = v.id
          WHERE r.id = :id AND r.user_id = :user_id AND r.statut = 'en_attente'";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $reservation_id);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$reservation) {
    include 'includes/header.php';
    echo "<div class='alert alert-warning text-center my-5'><h4>Réservation introuvable ou déjà réglée.</h4><a href='mes_reservations.php' class='btn btn-primary mt-2'>Voir mes réservations</a></div>";
    include 'includes/footer.php';
    exit();
}

$montant_total = $reservation['prix'] * $reservation['nombre_places'];
$error = null;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $methode = $_POST['methode'] ?? '';
    $telephone_paiement = trim($_POST['telephone_paiement'] ?? '');
    
    // Validation
    $methodes_valides = ['airtel_money', 'moov_money', 'carte'];
    if(!in_array($methode, $methodes_valides)) {
        $error = "Veuillez choisir un moyen de paiement valide au Tchad.";
    } elseif(($methode == 'airtel_money' || $methode == 'moov_money') && empty($telephone_paiement)) {
        $error = "Veuillez renseigner votre numéro de téléphone tchadien (+235).";
    } else {
        try {
            $db->beginTransaction();

            $num_trans = 'TX-TD-' . date('YmdHis') . '-' . rand(1000, 9999);

            // 1. Enregistrer le paiement
            $query_paiement = "INSERT INTO paiements (reservation_id, montant, methode, numero_transaction, telephone_paiement, statut, date_paiement) 
                               VALUES (:reservation_id, :montant, :methode, :num_trans, :telephone, 'payé', NOW())";
            $stmt_paiement = $db->prepare($query_paiement);
            $stmt_paiement->execute([
                ':reservation_id' => $reservation_id,
                ':montant' => $montant_total,
                ':methode' => $methode,
                ':num_trans' => $num_trans,
                ':telephone' => $telephone_paiement
            ]);

            // 2. Mettre à jour le statut de la réservation
            $update_res = "UPDATE reservations SET statut = 'confirmée' WHERE id = :id";
            $stmt_update = $db->prepare($update_res);
            $stmt_update->bindParam(':id', $reservation_id);
            $stmt_update->execute();
            
            // 3. Générer un numéro de ticket officiel tchadien
            $numero_ticket = 'TD-TKT-' . date('Ymd') . '-' . rand(100, 999) . '-' . $reservation_id;
            
            // 4. Créer le ticket
            $query_ticket = "INSERT INTO tickets (reservation_id, numero_ticket, statut) 
                             VALUES (:reservation_id, :numero_ticket, 'valide')";
            $stmt_ticket = $db->prepare($query_ticket);
            $stmt_ticket->execute([
                ':reservation_id' => $reservation_id,
                ':numero_ticket' => $numero_ticket
            ]);

            $db->commit();
            
            header("Location: ticket.php?id=" . $reservation_id . "&success=1");
            exit();

        } catch(Exception $e) {
            $db->rollBack();
            $error = "Erreur lors du traitement : " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-success text-white py-3">
                <h4 class="mb-0"><i class="fas fa-lock"></i> Paiement Sécurisé - Tchad</h4>
            </div>
            <div class="card-body p-4">
                <div class="bg-light p-3 rounded-3 mb-4">
                    <h5 class="fw-bold text-dark border-bottom pb-2">Récapitulatif de la commande</h5>
                    <div class="row mt-2">
                        <div class="col-sm-6">
                            <p class="mb-1"><strong>Trajet :</strong> <?php echo htmlspecialchars($reservation['ville_depart']); ?> → <?php echo htmlspecialchars($reservation['ville_arrivee']); ?></p>
                            <p class="mb-1"><strong>Départ :</strong> <?php echo date('d/m/Y à H:i', strtotime($reservation['date_depart'])); ?></p>
                            <p class="mb-1"><strong>Agence / Gare :</strong> <?php echo htmlspecialchars($reservation['gare_depart'] ?? 'Gare Routière'); ?></p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <p class="mb-1"><strong>Places réservées :</strong> <?php echo $reservation['nombre_places']; ?></p>
                            <p class="mb-1"><strong>Tarif unitaire :</strong> <?php echo number_format($reservation['prix'], 0, ',', ' '); ?> FCFA</p>
                            <div class="h4 text-success fw-bold mt-2">
                                Total : <?php echo number_format($montant_total, 0, ',', ' '); ?> FCFA
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <h5 class="fw-bold mb-3">Choisissez votre mode de paiement au Tchad :</h5>
                    
                    <div class="row g-3 mb-4">
                        <!-- Airtel Money -->
                        <div class="col-12">
                            <div class="border rounded-3 p-3 option-card" style="cursor: pointer;">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="methode" id="pay_airtel" value="airtel_money" checked>
                                    <label class="form-check-label w-100 fw-bold d-flex justify-content-between align-items-center" for="pay_airtel">
                                        <span><i class="fas fa-mobile-alt text-danger me-2"></i> Airtel Money Tchad</span>
                                        <span class="badge bg-danger">N°1 au Tchad</span>
                                    </label>
                                    <p class="small text-muted mb-0 mt-1">Paiement instantané via votre compte Airtel Tchad (*444#).</p>
                                </div>
                            </div>
                        </div>

                        <!-- Moov Money -->
                        <div class="col-12">
                            <div class="border rounded-3 p-3 option-card" style="cursor: pointer;">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="methode" id="pay_moov" value="moov_money">
                                    <label class="form-check-label w-100 fw-bold d-flex justify-content-between align-items-center" for="pay_moov">
                                        <span><i class="fas fa-mobile-alt text-primary me-2"></i> Moov Africa Tchad (Moov Money)</span>
                                        <span class="badge bg-primary">Moov Tchad</span>
                                    </label>
                                    <p class="small text-muted mb-0 mt-1">Paiement via votre compte Moov Money (*155#).</p>
                                </div>
                            </div>
                        </div>

                        <!-- Carte bancaire -->
                        <div class="col-12">
                            <div class="border rounded-3 p-3 option-card" style="cursor: pointer;">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="methode" id="pay_card" value="carte">
                                    <label class="form-check-label w-100 fw-bold" for="pay_card">
                                        <i class="fas fa-credit-card text-success me-2"></i> Carte Bancaire (Visa / Mastercard)
                                    </label>
                                    <p class="small text-muted mb-0 mt-1">UBA Tchad, Ecobank Tchad, CBT, Orabank ou cartes internationales.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Champ numéro téléphone Mobile Money -->
                    <div class="mb-4" id="phone_group">
                        <label class="form-label fw-bold">Numéro de téléphone de paiement (+235) *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">+235</span>
                            <input type="tel" name="telephone_paiement" class="form-control form-control-lg" placeholder="Ex: 66 12 34 56 ou 99 12 34 56" value="<?php echo htmlspecialchars($_SESSION['user_tel'] ?? ''); ?>" required>
                        </div>
                        <small class="text-muted">Un code de confirmation ou un prompt USSD sera envoyé sur ce numéro.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold py-3 shadow-sm">
                        <i class="fas fa-check-circle"></i> Valider et payer <?php echo number_format($montant_total, 0, ',', ' '); ?> FCFA
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('input[name="methode"]').forEach(function(el) {
    el.addEventListener('change', function() {
        var phoneGroup = document.getElementById('phone_group');
        var phoneInput = phoneGroup.querySelector('input');
        if (this.value === 'carte') {
            phoneInput.removeAttribute('required');
            phoneGroup.style.display = 'none';
        } else {
            phoneInput.setAttribute('required', 'required');
            phoneGroup.style.display = 'block';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
