<?php
include __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
            // Recherche d'utilisateur en insensitif à la casse pour l'email
        $sql = "SELECT u.utilisateur_id, u.role_id, u.prenom, u.nom, u.mot_de_passe_hash, u.est_actif, r.nom_role
                FROM utilisateurs u
                LEFT JOIN roles r ON u.role_id = r.role_id
                WHERE LOWER(u.email) = LOWER(?) LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($res);

        $authenticated = false;
        if ($user) {
            $stored = $user['mot_de_passe_hash'];
            // Méthode moderne
            if (password_verify($password, $stored)) {
                $authenticated = true;
            } else {
                // Fallback : mot de passe en clair (ancienne base) -> migrer
                if ($stored === $password) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $up = mysqli_prepare($conn, 'UPDATE utilisateurs SET mot_de_passe_hash = ? WHERE utilisateur_id = ?');
                    mysqli_stmt_bind_param($up, 'si', $newHash, $user['utilisateur_id']);
                    mysqli_stmt_execute($up);
                    $authenticated = true;
                }
                // Fallback : md5 ancien -> migrer
                if (!$authenticated && strlen($stored) === 32 && md5($password) === $stored) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $up = mysqli_prepare($conn, 'UPDATE utilisateurs SET mot_de_passe_hash = ? WHERE utilisateur_id = ?');
                    mysqli_stmt_bind_param($up, 'si', $newHash, $user['utilisateur_id']);
                    mysqli_stmt_execute($up);
                    $authenticated = true;
                }
            }
        }

        if ($authenticated) {
            if (empty($user['est_actif']) || $user['est_actif'] == 0) {
                $error = 'Votre compte est désactivé.';
            } else {
                // Connexion réussie
                $_SESSION['user_id'] = (int)$user['utilisateur_id'];
                $_SESSION['role_id'] = (int)$user['role_id'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['nom_role'] = $user['nom_role'];

                // Redirection selon le rôle
                if (!empty($user['role_id']) && $user['role_id'] == 1) {
                    header('Location: ' . BASE_URL . '/admin/index.php');
                } else {
                    header('Location: ' . BASE_URL . '/index.php');
                }
                exit;
            }
        } else {
            $error = 'Identifiants incorrects.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h1>Connexion</h1>

<?php if ($err = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo $err; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?php echo esc($error); ?></div>
<?php endif; ?>

<form method="post" class="form">
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required value="<?php echo esc($_POST['email'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="password">Mot de passe</label>
        <input type="password" name="password" id="password" required>
    </div>
    <div class="form-group form-actions">
        <button class="btn" type="submit">Se connecter</button>
        <a href="register.php" class="btn">S'inscrire</a>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
