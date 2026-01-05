<?php
include __DIR__ . '/../../includes/auth.php';
include __DIR__ . '/../../config/db.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
	flash_set('error', 'ID utilisateur invalide.');
	header('Location: ' . BASE_URL . '/admin/utilisateurs/index.php');
	exit;
}

// Récup utilisateur
$stmt = mysqli_prepare($conn, 'SELECT utilisateur_id, prenom, nom, email, role_id, est_actif, telephone FROM utilisateurs WHERE utilisateur_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
if (!$user) {
	flash_set('error', 'Utilisateur introuvable.');
	header('Location: ' . BASE_URL . '/admin/utilisateurs/index.php');
	exit;
}

// Récupérer les rôles disponibles
$roles = [];
$stmt = mysqli_prepare($conn, 'SELECT role_id, nom_role FROM roles ORDER BY role_id');
mysqli_stmt_execute($stmt);
$rres = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($rres)) $roles[] = $r;

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$prenom = trim($_POST['prenom'] ?? '');
	$nom = trim($_POST['nom'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$telephone = trim($_POST['telephone'] ?? '');
	$role_id = (int)($_POST['role_id'] ?? 0);
	$est_actif = !empty($_POST['est_actif']) ? 1 : 0;

	if ($prenom === '' || $nom === '' || $email === '') {
		$errors[] = 'Veuillez remplir tous les champs obligatoires.';
	}

	if (empty($errors)) {
		$sql = 'UPDATE utilisateurs SET prenom = ?, nom = ?, email = ?, telephone = ?, role_id = ?, est_actif = ? WHERE utilisateur_id = ?';
		$stmt = mysqli_prepare($conn, $sql);
		mysqli_stmt_bind_param($stmt, 'ssssiii', $prenom, $nom, $email, $telephone, $role_id, $est_actif, $id);
		$ok = mysqli_stmt_execute($stmt);
		if ($ok) {
			flash_set('success', 'Utilisateur mis à jour.');
			header('Location: ' . BASE_URL . '/admin/utilisateurs/index.php');
			exit;
		} else {
			$errors[] = 'Échec lors de la mise à jour.';
		}
	}
}

include __DIR__ . '/../../includes/header.php';

?>

<h1>Modifier utilisateur</h1>

<?php foreach ($errors as $e): ?>
	<div class="alert alert-error"><?php echo esc($e); ?></div>
<?php endforeach; ?>

<form method="post" class="form">
	<div class="form-group">
		<label for="prenom">Prénom</label>
		<input id="prenom" name="prenom" required value="<?php echo esc($_POST['prenom'] ?? $user['prenom']); ?>">
	</div>
	<div class="form-group">
		<label for="nom">Nom</label>
		<input id="nom" name="nom" required value="<?php echo esc($_POST['nom'] ?? $user['nom']); ?>">
	</div>
	<div class="form-group">
		<label for="email">Email</label>
		<input id="email" name="email" type="email" required value="<?php echo esc($_POST['email'] ?? $user['email']); ?>">
	</div>
	<div class="form-group">
		<label for="telephone">Téléphone</label>
		<input id="telephone" name="telephone" type="tel" placeholder="+228 xx xx xx xx ou +33 x xx xx xx xx" value="<?php echo esc($_POST['telephone'] ?? $user['telephone']); ?>">
	</div>
	<div class="form-group">
		<label for="role_id">Rôle</label>
		<select id="role_id" name="role_id">
			<?php foreach ($roles as $role): ?>
				<option value="<?php echo (int)$role['role_id']; ?>" <?php echo ((isset($_POST['role_id']) && (int)$_POST['role_id'] === (int)$role['role_id']) || (!isset($_POST['role_id']) && (int)$user['role_id'] === (int)$role['role_id'])) ? 'selected' : ''; ?>><?php echo esc($role['nom_role']); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="form-group">
		<label><input type="checkbox" name="est_actif" <?php echo ((isset($_POST['est_actif']) && $_POST['est_actif']) || (!isset($_POST['est_actif']) && $user['est_actif'])) ? 'checked' : ''; ?>> Compte actif</label>
	</div>
	<div class="form-group">
		<button class="btn" type="submit">Enregistrer</button>
		<a class="btn" href="index.php">Annuler</a>
	</div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

