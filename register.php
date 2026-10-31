<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password_raw = $_POST['password'] ?? '';
    $confirm_raw = $_POST['confirm_password'] ?? '';
    $role = 'client';
    
    if (empty($name) || empty($email) || empty($password_raw) || empty($telephone)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } elseif (strlen($password_raw) < 6) {
        $error = "Le mot de passe doit comporter au moins 6 caractères.";
    } elseif ($password_raw !== $confirm_raw) {
        $error = "Les deux mots de passe ne correspondent pas.";
    } else {
        // Vérifier si l'email existe déjà
        $check_query = "SELECT id FROM users WHERE LOWER(email) = :email LIMIT 1";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error = "Cette adresse email est déjà associée à un compte.";
        } else {
            $password = password_hash($password_raw, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (name, telephone, email, password, role) VALUES (:name, :telephone, :email, :password, :role)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':role', $role);
            
            if ($stmt->execute()) {
                $newId = (int)$db->lastInsertId();
                
                // Connexion automatique immédiate après inscription
                session_regenerate_id(true);
                $_SESSION['user_id'] = $newId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_tel'] = $telephone;
                $_SESSION['user_role'] = 'client';
                
                $target = $_SESSION['redirect_after_login'] ?? (BASE_URL . "index.php?registered=1");
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $target);
                exit();
            } else {
                $error = "Erreur lors de la création du compte. Veuillez réessayer.";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0 fw-bold"><i class="fas fa-user-plus me-2"></i> Inscription Passager</h4>
                <small class="text-light opacity-75">Créez votre compte en 1 minute pour réserver au Tchad</small>
            </div>
            <div class="card-body p-4">
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-1"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">Nom et Prénom *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" placeholder="Ex: Mahamat Saleh" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="telephone" class="form-label fw-bold">Numéro de téléphone tchadien (+235) *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light font-monospace fw-bold">+235</span>
                            <input type="tel" class="form-control form-control-lg" id="telephone" name="telephone" placeholder="66 12 34 56 ou 99 12 34 56" value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>" required>
                        </div>
                        <small class="text-muted">Utilisé pour les alertes SMS et le paiement Airtel/Moov.</small>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">Adresse Email *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="nom@exemple.td" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="password" class="form-label fw-bold">Mot de passe *</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" minlength="6" placeholder="Min. 6 car." required>
                        </div>
                        <div class="col-6">
                            <label for="confirm_password" class="form-label fw-bold">Confirmation *</label>
                            <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" minlength="6" placeholder="Répéter" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold py-2.5 shadow-sm mt-2">
                        <i class="fas fa-check me-1"></i> Valider mon inscription
                    </button>
                </form>

                <div class="text-center mt-4 border-top pt-3">
                    <p class="mb-0 text-muted small">Vous avez déjà un compte ? <a href="login.php" class="fw-bold text-primary">Se connecter</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    var p = document.getElementById('password').value;
    var c = document.getElementById('confirm_password').value;
    if (p !== c) {
        e.preventDefault();
        alert('Attention : Les deux mots de passe saisis ne sont pas identiques.');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
