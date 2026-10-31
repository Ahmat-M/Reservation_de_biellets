<?php
require_once '../../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM chauffeurs ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$chauffeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1"><i class="fas fa-id-card text-primary"></i> Gestion des Chauffeurs</h2>
                <small class="text-muted">Conducteurs professionnels habilités transport voyageurs Tchad</small>
            </div>
            <a href="ajouter.php" class="btn btn-primary fw-bold">
                <i class="fas fa-plus"></i> Ajouter un chauffeur
            </a>
        </div>
        
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> Opération effectuée avec succès !
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['deleted'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i> Le chauffeur a été supprimé de la liste.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>#</th>
                                <th>Nom complet</th>
                                <th>Téléphone (+235)</th>
                                <th>Numéro de Permis</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($chauffeurs)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Aucun chauffeur enregistré.</td></tr>
                            <?php else: ?>
                                <?php foreach($chauffeurs as $chauffeur): ?>
                                <tr>
                                    <td>#<?php echo $chauffeur['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($chauffeur['nom']); ?></strong></td>
                                    <td>
                                        <i class="fas fa-phone-alt text-success me-1"></i> 
                                        <?php echo htmlspecialchars($chauffeur['telephone'] ?? '-'); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">
                                            <?php echo htmlspecialchars($chauffeur['permis'] ?? 'Catégorie D'); ?>
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="modifier.php?id=<?php echo $chauffeur['id']; ?>" class="btn btn-sm btn-outline-warning me-1">
                                            <i class="fas fa-edit"></i> Modifier
                                        </a>
                                        <a href="supprimer.php?id=<?php echo $chauffeur['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Confirmez-vous la suppression de ce chauffeur ?')">
                                            <i class="fas fa-trash"></i> Supprimer
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
