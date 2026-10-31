<?php
require_once '../../config/database.php';
include '../../includes/header.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM vehicules ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1"><i class="fas fa-bus text-primary"></i> Flotte de Bus</h2>
                <small class="text-muted">Parc de véhicules affectés aux lignes interurbaines au Tchad</small>
            </div>
            <a href="ajouter.php" class="btn btn-primary fw-bold">
                <i class="fas fa-plus"></i> Ajouter un véhicule
            </a>
        </div>
        
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> Opération effectuée avec succès !
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif(isset($_GET['deleted'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i> Véhicule supprimé de la flotte.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>#</th>
                                <th>Modèle / Marque</th>
                                <th>Immatriculation (Tchad)</th>
                                <th>Capacité</th>
                                <th>Confort</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($vehicules)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">Aucun véhicule enregistré.</td></tr>
                            <?php else: ?>
                                <?php foreach($vehicules as $vehicule): ?>
                                <tr>
                                    <td>#<?php echo $vehicule['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($vehicule['marque']); ?></strong></td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?php echo htmlspecialchars($vehicule['immatriculation']); ?></span></td>
                                    <td><?php echo $vehicule['capacite']; ?> places</td>
                                    <td>
                                        <?php if(!empty($vehicule['climatise'])): ?>
                                            <span class="badge bg-info text-dark"><i class="fas fa-snowflake"></i> Climatisé</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted">Standard</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="modifier.php?id=<?php echo $vehicule['id']; ?>" class="btn btn-sm btn-outline-warning me-1">
                                            <i class="fas fa-edit"></i> Modifier
                                        </a>
                                        <a href="supprimer.php?id=<?php echo $vehicule['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce bus ?')">
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
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
