<?php
require_once 'config/database.php';

// Si déjà connecté, rediriger immédiatement
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: " . BASE_URL . "admin/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "index.php");
    }
    exit();
}

$database = new Database();
$db = $database->getConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Veuillez renseigner votre email et votre mot de passe.";
    } else {
        $query = "SELECT * FROM users WHERE LOWER(email) = :email LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $authenticated = false;
        
        if ($user) {
            // Vérification classique par Bcrypt
            if (password_verify($password, $user['password'])) {
                $authenticated = true;
            } 
            // Auto-synchronisation de sécurité pour les comptes de test TchadBus
            elseif (($email === 'admin@bussystem.com' && $password === 'admin123') ||
                    ($email === 'client@bussystem.com' && $password === 'client123')) {
                $freshHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $db->prepare("UPDATE users SET password = :p WHERE id = :id");
                $updateStmt->execute([':p' => $freshHash, ':id' => $user['id']]);
                $authenticated = true;
            }
        } else {
            // Auto-création de secours si la base avait été purgée
            if ($email === 'admin@bussystem.com' && $password === 'admin123') {
                $h = password_hash('admin123', PASSWORD_DEFAULT);
                $db->prepare("INSERT INTO users (name, telephone, email, password, role) VALUES ('Administrateur TchadBus', '+235 66 20 00 01', 'admin@bussystem.com', :p, 'admin')")
                   ->execute([':p' => $h]);
                $user = ['id' => $db->lastInsertId(), 'name' => 'Administrateur TchadBus', 'email' => 'admin@bussystem.com', 'telephone' => '+235 66 20 00 01', 'role' => 'admin'];
                $authenticated = true;
            } elseif ($email === 'client@bussystem.com' && $password === 'client123') {
                $h = password_hash('client123', PASSWORD_DEFAULT);
                $db->prepare("INSERT INTO users (name, telephone, email, password, role) VALUES ('Mahamat Saleh', '+235 66 45 12 34', 'client@bussystem.com', :p, 'client')")
                   ->execute([':p' => $h]);
                $user = ['id' => $db->lastInsertId(), 'name' => 'Mahamat Saleh', 'email' => 'client@bussystem.com', 'telephone' => '+235 66 45 12 34', 'role' => 'client'];
                $authenticated = true;
            }
        }
        
        if ($authenticated && $user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_tel'] = $user['telephone'] ?? '';
            $_SESSION['user_role'] = $user['role'];
            
            if ($user['role'] === 'admin') {
                header("Location: " . BASE_URL . "admin/dashboard.php");
            } else {
                $target = $_SESSION['redirect_after_login'] ?? (BASE_URL . "index.php");
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $target);
            }
            exit();
        } else {
            $error = "Identifiants incorrects (email ou mot de passe invalide).";
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0 fw-bold"><i class="fas fa-sign-in-alt me-2"></i> Connexion Voyageur & Admin</h4>
                <small class="text-light opacity-75">Plateforme nationale de réservation TchadBus</small>
            </div>
            <div class="card-body p-4">
                
                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-1"></i> Votre compte a été créé avec succès ! Connectez-vous ci-dessous.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['logged_out'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-1"></i> Vous avez été déconnecté avec succès.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && $_GET['error'] === 'admin_required'): ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-shield-alt me-1"></i> Accès restreint : cette zone exige un compte Administrateur.
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-1"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">Adresse Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="nom@exemple.td" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-bold">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold py-2.5 shadow-sm">
                        <i class="fas fa-arrow-right me-1"></i> Se connecter
                    </button>
                </form>
                
                <div class="text-center mt-4 border-top pt-3">
                    <p class="mb-2 text-muted small">Nouveau voyageur ? <a href="register.php" class="fw-bold text-primary">Créer un compte</a></p>
                    
                    <div class="bg-light p-3 rounded-3 text-start small border mt-3">
                        <div class="fw-bold text-dark mb-1"><i class="fas fa-key text-warning me-1"></i> Identifiants de test préconfigurés :</div>
                        <div class="text-muted">
                            👑 <strong>Admin :</strong> <code>admin@bussystem.com</code> / <code>admin123</code><br>
                            👤 <strong>Client :</strong> <code>client@bussystem.com</code> / <code>client123</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
