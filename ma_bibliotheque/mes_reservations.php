<?php
include __DIR__ . '/includes/auth.php';
include __DIR__ . '/config/db.php';
require_login();

$utilisateur_id = $_SESSION['user_id'];

// Récupérer les emprunts et réservations de l'utilisateur
 $sql = "SELECT e.emprunt_id as id, l.livre_id, l.titre, l.url_image_couverture, a.prenom AS auteur_prenom, a.nom AS auteur_nom, e.date_emprunt as date, e.date_echeance, e.statut, 'emprunt' as type
        FROM emprunts e
        JOIN livres l ON e.livre_id = l.livre_id
        LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
        WHERE e.utilisateur_id = ?
        
        UNION ALL
        
        SELECT r.reservation_id as id, l.livre_id, l.titre, l.url_image_couverture, a.prenom AS auteur_prenom, a.nom AS auteur_nom, r.date_reservation as date, r.date_reservation as date_echeance, r.statut, 'reservation' as type
        FROM reservations r
        JOIN livres l ON r.livre_id = l.livre_id
        LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
        WHERE r.utilisateur_id = ?
        
        ORDER BY date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $utilisateur_id, $utilisateur_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

include __DIR__ . '/includes/header.php';
?>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo $msg; ?></div>
<?php endif; ?>
<?php if ($err = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo $err; ?></div>
<?php endif; ?>

<?php if (mysqli_num_rows($res) === 0): ?>
    <p>Vous n'avez aucun emprunt ou réservation actuellement. <a href="<?php echo BASE_URL; ?>/index.php">Découvrez notre catalogue</a></p>
<?php else: ?>
    <section class="featured container" aria-label="Ma Liste de Lecture">
        <h2 class="page-title">Ma Liste de Lecture</h2>
        <div class="featured-grid all-books">
            <?php while ($item = mysqli_fetch_assoc($res)): ?>
                <a href="<?php echo BASE_URL; ?>/livre.php?id=<?php echo (int)$item['livre_id']; ?>" class="book-card book-link" target="_blank">
                    <div class="cover">
                        <?php if (!empty($item['url_image_couverture'])): ?>
                            <img src="<?php echo esc($item['url_image_couverture']); ?>" alt="<?php echo esc($item['titre']); ?>" style="width:100%; height:100%; object-fit:cover; border-radius:6px;" />
                        <?php else: ?>
                            <div class="cover-placeholder">N/A</div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3><?php echo esc($item['titre']); ?></h3>
                        <p><?php echo esc(trim($item['auteur_prenom'] . ' ' . $item['auteur_nom'])); ?></p>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
