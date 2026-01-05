<?php
include __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../config/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($prenom) || empty($nom) || empty($email) || empty($password)) {
        $errors[] = 'Veuillez remplir tous les champs obligatoires.';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'adresse email n\'est pas valide.';
    }
    // Validation du téléphone seulement s'il est fourni
    if (!empty($telephone) && !preg_match('/^\+?[0-9\s]{9,}$/', preg_replace('/[^0-9+]/', '', $telephone))) {
        $errors[] = 'Le numéro de téléphone n\'est pas valide.';
    }

    if (empty($errors)) {
        // Vérifier si l'email existe déjà
        $stmt = mysqli_prepare($conn, "SELECT utilisateur_id FROM utilisateurs WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'Cette adresse email est déjà utilisée.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role_id = 2; // ID pour "Membre"
            $est_actif = 1; // Actif par défaut

            $sql = 'INSERT INTO utilisateurs (prenom, nom, email, telephone, mot_de_passe_hash, role_id, est_actif) VALUES (?, ?, ?, ?, ?, ?, ?)';
            $stmt_insert = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt_insert, 'sssssii', $prenom, $nom, $email, $telephone, $password_hash, $role_id, $est_actif);
            if (mysqli_stmt_execute($stmt_insert)) {
                // Récupérer l'utilisateur juste créé et le connecter automatiquement
                $new_user_id = mysqli_insert_id($conn);
                $stmt_get = mysqli_prepare($conn, 'SELECT utilisateur_id, role_id, prenom, nom FROM utilisateurs WHERE utilisateur_id = ? LIMIT 1');
                mysqli_stmt_bind_param($stmt_get, 'i', $new_user_id);
                mysqli_stmt_execute($stmt_get);
                $res_user = mysqli_stmt_get_result($stmt_get);
                $user = mysqli_fetch_assoc($res_user);
                
                if ($user) {
                    // Connecter l'utilisateur
                    $_SESSION['user_id'] = (int)$user['utilisateur_id'];
                    $_SESSION['role_id'] = (int)$user['role_id'];
                    $_SESSION['prenom'] = $user['prenom'];
                    $_SESSION['nom'] = $user['nom'];
                    
                    flash_set('success', 'Bienvenue! Votre compte a été créé avec succès.');
                    header('Location: ' . BASE_URL . '/index.php');
                    exit;
                }
            } else {
                $errors[] = 'Une erreur est survenue lors de la création de votre compte.';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h1>Créer un compte</h1>

<?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?php echo esc($e); ?></div>
<?php endforeach; ?>

<form method="post" class="form">
    <div class="form-group"><label for="prenom">Prénom</label><input id="prenom" name="prenom" required value="<?php echo esc($_POST['prenom'] ?? ''); ?>"></div>
    <div class="form-group"><label for="nom">Nom</label><input id="nom" name="nom" required value="<?php echo esc($_POST['nom'] ?? ''); ?>"></div>
    <div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" required value="<?php echo esc($_POST['email'] ?? ''); ?>"></div>
    <div class="form-group"><label for="telephone">Téléphone</label><input id="telephone" name="telephone" type="tel" placeholder="+228 xx xx xx xx ou +33 x xx xx xx xx" value="<?php echo esc($_POST['telephone'] ?? ''); ?>"></div>
    <div class="form-group"><label for="password">Mot de passe (8 caractères min.)</label><input id="password" name="password" type="password" required></div>
    <div class="form-group"><label for="password_confirm">Confirmer le mot de passe</label><input id="password_confirm" name="password_confirm" type="password" required></div>
    <div class="form-group form-actions">
        <button class="btn" type="submit">S'inscrire</button>
        <a href="login.php">Déjà un compte ?</a>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>