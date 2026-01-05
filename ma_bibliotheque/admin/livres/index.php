<?php
include __DIR__ . '/../../includes/auth.php';
include __DIR__ . '/../../config/db.php';
require_admin();

$has_url_image_col = false;
$r = mysqli_query($conn, "SHOW COLUMNS FROM livres LIKE 'url_image_couverture'");
if ($r && mysqli_num_rows($r) > 0) $has_url_image_col = true;

$sql = "SELECT l.livre_id, l.titre, l.total_exemplaires, l.exemplaires_disponibles, a.prenom AS auteur_prenom, a.nom AS auteur_nom, c.nom AS categorie_nom" . ($has_url_image_col ? ", l.url_image_couverture" : "") . "
        FROM livres l
        LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
        LEFT JOIN categories c ON l.categorie_id = c.categorie_id
        ORDER BY l.titre";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

include __DIR__ . '/../../includes/header.php';
?>

<h1>Gérer les livres</h1>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?php echo $msg; ?></div>
<?php endif; ?>
<?php if ($err = flash_get('error')): ?>
    <div class="alert alert-error"><?php echo $err; ?></div>
<?php endif; ?>

<p><a class="btn" href="ajouter.php">Ajouter un livre</a></p>

<table class="table">
    <thead>
        <tr>
            <?php if ($has_url_image_col): ?><th></th><?php endif; ?>
            <th>Titre</th>
            <th>Auteur</th>
            <th>Catégorie</th>
            <th>Exemplaires (Dispo/Total)</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($livre = mysqli_fetch_assoc($res)): ?>
        <tr>
            <?php if ($has_url_image_col): ?>
                <td>
                    <?php if (!empty($livre['url_image_couverture'])): ?>
                        <img src="<?php echo esc($livre['url_image_couverture']); ?>" alt="" width="40">
                    <?php else: ?>
                        <div class="thumb-placeholder" style="width:40px; height:60px;"></div>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
            <td><?php echo esc($livre['titre']); ?></td>
            <td><?php echo esc(trim($livre['auteur_prenom'] . ' ' . $livre['auteur_nom'])); ?></td>
            <td><?php echo esc($livre['categorie_nom']); ?></td>
            <td><?php echo (int)$livre['exemplaires_disponibles']; ?> / <?php echo (int)$livre['total_exemplaires']; ?></td>
            <td>
                <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <a class="btn small" href="modifier.php?id=<?php echo (int)$livre['livre_id']; ?>">Modifier</a>
                    <a class="btn small danger" href="supprimer.php?id=<?php echo (int)$livre['livre_id']; ?>">Supprimer</a>
                </div>
                <div style="display: flex; gap: 0.5rem; justify-content: space-between;">
                    <a class="btn small" href="augmenter.php?id=<?php echo (int)$livre['livre_id']; ?>">+ex</a>
                    <a class="btn small danger" href="diminuer.php?id=<?php echo (int)$livre['livre_id']; ?>">-ex</a>
                </div>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php include __DIR__ . '/../../includes/footer.php'; ?>