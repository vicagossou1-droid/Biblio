<?php
include __DIR__ . '/../../includes/auth.php';
include __DIR__ . '/../../config/db.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    flash_set('error', 'ID de livre invalide.');
		header('Location: ' . BASE_URL . '/admin/livres/index.php');
		exit;
}

// Récup pour afficher l'élément
$has_url_image_col = false;
$r = mysqli_query($conn, "SHOW COLUMNS FROM livres LIKE 'url_image_couverture'");
if ($r && mysqli_num_rows($r) > 0) $has_url_image_col = true;

if ($has_url_image_col) {
    $stmt = mysqli_prepare($conn, 'SELECT titre, url_image_couverture FROM livres WHERE livre_id = ? LIMIT 1');
} else {
    $stmt = mysqli_prepare($conn, 'SELECT titre FROM livres WHERE livre_id = ? LIMIT 1');
}
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$livre = mysqli_fetch_assoc($res);
if (!$livre) {
    flash_set('error', 'Le livre demandé n\'existe pas ou a été supprimé.');
		header('Location: ' . BASE_URL . '/admin/livres/index.php');
		exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Confirmation reçue - vérifier d'abord les références (emprunts, réservations)
    $stmt_check = mysqli_prepare($conn, 'SELECT COUNT(*) AS cnt FROM emprunts WHERE livre_id = ?');
    mysqli_stmt_bind_param($stmt_check, 'i', $id);
    mysqli_stmt_execute($stmt_check);
    $res_check = mysqli_stmt_get_result($stmt_check);
    $cnt_emprunts = (int)mysqli_fetch_assoc($res_check)['cnt'];

    $stmt_check2 = mysqli_prepare($conn, 'SELECT COUNT(*) AS cnt FROM reservations WHERE livre_id = ?');
    mysqli_stmt_bind_param($stmt_check2, 'i', $id);
    mysqli_stmt_execute($stmt_check2);
    $res_check2 = mysqli_stmt_get_result($stmt_check2);
    $cnt_reservations = (int)mysqli_fetch_assoc($res_check2)['cnt'];

    if ($cnt_emprunts > 0 || $cnt_reservations > 0) {
        $parts = [];
        if ($cnt_emprunts > 0) $parts[] = $cnt_emprunts . ' emprunt(s)';
        if ($cnt_reservations > 0) $parts[] = $cnt_reservations . ' réservation(s)';
        flash_set('error', 'Impossible de supprimer le livre : il existe ' . implode(' et ', $parts) . ' référencées. Supprimez d\'abord ces enregistrements.');
        header('Location: ' . BASE_URL . '/admin/livres/index.php');
        exit;
    }

    $stmt = mysqli_prepare($conn, 'DELETE FROM livres WHERE livre_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok) {
        // supprimer le fichier de couverture si présent
        if (!empty($livre['url_image_couverture'])) {
            $file_path = __DIR__ . '/../../public/images/covers/' . basename($livre['url_image_couverture']);
            if (file_exists($file_path)) @unlink($file_path);
        }
        flash_set('success', '"' . $livre['titre'] . '" a été supprimé avec succès.');
    } else {
        flash_set('error', 'Impossible de supprimer le livre.');
    }
    header('Location: ' . BASE_URL . '/admin/livres/index.php');
    exit;
}

include __DIR__ . '/../../includes/header.php';

?>

<h1>Supprimer un livre</h1>

<p>Êtes-vous certain de vouloir supprimer <strong><?php echo esc($livre['titre']); ?></strong> ? Cette action est définitive.</p>

<form method="post">
    <button class="btn danger" type="submit">Oui, supprimer définitivement</button>
    <a class="btn" href="index.php">Annuler</a>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>