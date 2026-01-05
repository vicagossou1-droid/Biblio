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

// Empêcher la suppression de son propre compte
if ($id === ($_SESSION['user_id'] ?? 0)) {
    flash_set('error', 'Vous ne pouvez pas supprimer votre propre compte.');
    header('Location: ' . BASE_URL . '/admin/utilisateurs/index.php');
    exit;
}

// Récupérer l'utilisateur pour afficher son nom
$stmt = mysqli_prepare($conn, 'SELECT prenom, nom FROM utilisateurs WHERE utilisateur_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);

if (!$user) {
    flash_set('error', 'L\'utilisateur demandé n\'existe pas ou a été supprimé.');
    header('Location: ' . BASE_URL . '/admin/utilisateurs/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = mysqli_prepare($conn, 'DELETE FROM utilisateurs WHERE utilisateur_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    flash_set('success', $user['prenom'] . ' ' . $user['nom'] . ' a été supprimé du système.');
    header('Location: ' . BASE_URL . '/admin/utilisateurs/index.php');
    exit;
}

include __DIR__ . '/../../includes/header.php';
?>
<h1>Supprimer un utilisateur</h1>
<p>Êtes-vous certain de vouloir supprimer <strong><?php echo esc($user['prenom'] . ' ' . $user['nom']); ?></strong> ? Toutes ses données seront supprimées.</p>
<form method="post">
    <button class="btn danger" type="submit">Oui, supprimer définitivement</button>
    <a class="btn" href="index.php">Annuler</a>
</form>
<?php include __DIR__ . '/../../includes/footer.php'; ?>