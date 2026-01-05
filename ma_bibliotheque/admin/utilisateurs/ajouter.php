<?php
include __DIR__ . '/../../includes/auth.php';
include __DIR__ . '/../../config/db.php';
require_admin();

// Récupérer les rôles pour le select
$roles = [];
$stmt = mysqli_prepare($conn, 'SELECT role_id, nom_role FROM roles ORDER BY role_id');
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $role_id, $nom_role);
while (mysqli_stmt_fetch($stmt)) {
    $roles[] = ['role_id' => $role_id, 'nom_role' => $nom_role];
}
mysqli_stmt_close($stmt);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id_post = (int)($_POST['role_id'] ?? 0);
    $est_actif = !empty($_POST['est_actif']) ? 1 : 0;

    if (empty($prenom) || empty($nom) || empty($email) || empty($password) || empty($role_id_post)) {
        $errors[] = 'Veuillez remplir tous les champs.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'adresse email n\'est pas valide.';
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
            $sql = 'INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe_hash, role_id, est_actif) VALUES (?, ?, ?, ?, ?, ?)';
            $stmt_insert = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt_insert, 'ssssii', $prenom, $nom, $email, $password_hash, $role_id_post, $est_actif);
            if (mysqli_stmt_execute($stmt_insert)) {
                flash_set('success', 'Utilisateur ajouté avec succès.');
                header('Location: ' . BASE_URL . '/admin/utilisateurs/index.php');
                exit;
            } else {
                $errors[] = 'Erreur lors de l\'ajout de l\'utilisateur.';
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<h1>Ajouter un utilisateur</h1>

<?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?php echo esc($e); ?></div>
<?php endforeach; ?>

<form method="post" class="form">
    <div class="form-group">
        <label for="prenom">Prénom</label>
        <input id="prenom" name="prenom" required value="<?php echo esc($_POST['prenom'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="nom">Nom</label>
        <input id="nom" name="nom" required value="<?php echo esc($_POST['nom'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?php echo esc($_POST['email'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="password">Mot de passe</label>
        <input id="password" name="password" type="password" required>
    </div>
    <div class="form-group">
        <label for="role_id">Rôle</label>
        <select id="role_id" name="role_id" required>
            <option value="">-- Choisir un rôle --</option>
            <?php foreach ($roles as $role): ?>
                <option value="<?php echo (int)$role['role_id']; ?>" <?php echo (isset($_POST['role_id']) && (int)$_POST['role_id'] === (int)$role['role_id']) ? 'selected' : ''; ?>><?php echo esc($role['nom_role']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="est_actif" value="1" <?php echo (isset($_POST['est_actif']) || !isset($_POST)) ? 'checked' : ''; ?>> Compte actif</label>
    </div>
    <div class="form-group form-actions">
        <button class="btn" type="submit">Ajouter</button>
        <a class="btn" href="index.php">Annuler</a>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>