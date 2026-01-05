<?php
include __DIR__ . '/../../includes/auth.php';
include __DIR__ . '/../../config/db.php';
require_admin();

$livre_id = (int)($_GET['id'] ?? 0);

if ($livre_id <= 0) {
    header('Location: ' . BASE_URL . '/admin/livres/index.php');
    exit;
}

// Récupérer le livre
$sql = "SELECT l.livre_id, l.titre, l.exemplaires_disponibles, l.total_exemplaires FROM livres l WHERE l.livre_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $livre_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($res) === 0) {
    header('Location: ' . BASE_URL . '/admin/livres/index.php');
    exit;
}

$livre = mysqli_fetch_assoc($res);

// Traiter la suppression d'exemplaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantite = (int)($_POST['quantite'] ?? 0);
    
    if ($quantite <= 0) {
        flash_set('error', 'La quantité doit être supérieure à 0');
    } else if ($quantite > $livre['total_exemplaires']) {
        flash_set('error', 'Impossible de supprimer plus d\'exemplaires que le total disponible');
    } else if ($quantite > $livre['exemplaires_disponibles']) {
        flash_set('error', 'Vous ne pouvez supprimer que les exemplaires disponibles (' . $livre['exemplaires_disponibles'] . ' max).');
    } else {
        $nouveau_total = $livre['total_exemplaires'] - $quantite;
        $nouveau_dispo = $livre['exemplaires_disponibles'] - $quantite;
        
        $update_sql = "UPDATE livres SET total_exemplaires = ?, exemplaires_disponibles = ? WHERE livre_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, 'iii', $nouveau_total, $nouveau_dispo, $livre_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            flash_set('success', 'Exemplaires supprimés avec succès');
            header('Location: ' . BASE_URL . '/admin/livres/index.php');
            exit;
        } else {
            flash_set('error', 'Erreur lors de la mise à jour');
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<h1>Supprimer des exemplaires - <?php echo esc($livre['titre']); ?></h1>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo $msg; ?></div>
<?php endif; ?>
<?php if ($err = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo $err; ?></div>
<?php endif; ?>

<div class="form">
    <div class="info-box">
        <p><strong>Titre :</strong> <?php echo esc($livre['titre']); ?></p>
        <p><strong>Exemplaires actuels :</strong> <?php echo (int)$livre['exemplaires_disponibles']; ?> disponibles / <?php echo (int)$livre['total_exemplaires']; ?> total</p>
        <p style="color: #d32f2f; font-weight: bold;">⚠️ Vous ne pouvez supprimer que les exemplaires disponibles (non empruntés)</p>
    </div>

    <form method="post">
        <div class="form-group">
            <label for="quantite">Nombre d'exemplaires à supprimer :</label>
            <input type="number" id="quantite" name="quantite" min="1" max="<?php echo (int)$livre['exemplaires_disponibles']; ?>" required>
            <small>Maximum : <?php echo (int)$livre['exemplaires_disponibles']; ?> exemplaire(s) disponible(s)</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn" style="background-color: #d32f2f;">Supprimer</button>
            <a href="index.php" class="btn secondary">Annuler</a>
        </div>
    </form>
</div>

<style>
.info-box {
    background: #fff3cd;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border-left: 4px solid #ff9800;
}
.info-box p {
    margin: 0.5rem 0;
}
.form-group small {
    display: block;
    margin-top: 0.5rem;
    color: #666;
    font-size: 0.9rem;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
