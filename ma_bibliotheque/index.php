<?php
include __DIR__ . '/includes/auth.php';
include __DIR__ . '/config/db.php';

// Rediriger les admins vers le dashboard
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

include __DIR__ . '/includes/header.php';

// Recherche (si paramètre q)
$q = trim($_GET['q'] ?? '');
$search_results = [];
$search_count = 0;
if ($q !== '') {
    // recherches partielles et score simple : priorité titre début, titre contiens, auteur, catégorie
    $like = '%' . $q . '%';
    $start = $q . '%';
    $sql_search = "SELECT l.livre_id, l.titre, l.url_image_couverture, a.prenom AS auteur_prenom, a.nom AS auteur_nom, c.nom AS categorie_nom, l.exemplaires_disponibles,
        ((l.titre LIKE ?) * 4 + (l.titre LIKE ?) * 2 + ((a.prenom LIKE ?) OR (a.nom LIKE ?)) * 2 + (c.nom LIKE ?) * 1) AS score
        FROM livres l
        LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
        LEFT JOIN categories c ON l.categorie_id = c.categorie_id
        WHERE (l.titre LIKE ? OR a.prenom LIKE ? OR a.nom LIKE ? OR c.nom LIKE ?)
        ORDER BY score DESC, l.titre
        LIMIT 50";
    $stmtS = mysqli_prepare($conn, $sql_search);
    // Bind params: start,title-like, author-like, category-like, then for WHERE
    mysqli_stmt_bind_param($stmtS, 'sssssssss', $start, $like, $like, $like, $like, $like, $like, $like, $like);
    mysqli_stmt_execute($stmtS);
    $resS = mysqli_stmt_get_result($stmtS);
    while ($r = mysqli_fetch_assoc($resS)) $search_results[] = $r;
    $search_count = count($search_results);
}

// Requête pour afficher TOUS les livres (au lieu de juste 6)
$sql_featured = "SELECT l.livre_id, l.titre, l.url_image_couverture, a.prenom AS auteur_prenom, a.nom AS auteur_nom, l.exemplaires_disponibles
		FROM livres l
		LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
		ORDER BY l.titre";
$stmtF = mysqli_prepare($conn, $sql_featured);
mysqli_stmt_execute($stmtF);
$resF = mysqli_stmt_get_result($stmtF);

?>

<section class="hero" aria-label="Héros de la page">
	<div class="hero-inner container">
		<h1>Explorez notre collection</h1>
		<p class="lead">Des milliers de livres à découvrir : romans, documentaires, jeunesse et bien plus.</p>
		<form class="hero-search" action="<?php echo BASE_URL; ?>/search.php" method="get" role="search">
			<input type="search" name="q" placeholder="Rechercher un titre, un auteur ou une catégorie" aria-label="Recherche" />
			<button class="btn" type="submit">Rechercher</button>
		</form>
	</div>
</section>

<section class="featured container" aria-label="Tous les livres"> 
	<h2 class="page-title">Tous nos livres</h2>
	<div class="featured-grid all-books">
		<?php while ($rowF = mysqli_fetch_assoc($resF)): ?>
			<a href="<?php echo BASE_URL; ?>/livre.php?id=<?php echo (int)$rowF['livre_id']; ?>" target="_blank" class="book-card book-link">
				<div class="cover">
					<?php if (!empty($rowF['url_image_couverture'])): ?>
						<img src="<?php echo esc($rowF['url_image_couverture']); ?>" alt="<?php echo esc($rowF['titre']); ?>" />
					<?php else: ?>
						<div class="cover-placeholder" aria-hidden="true"></div>
					<?php endif; ?>
				</div>
				<div>
					<h3><?php echo esc($rowF['titre']); ?></h3> 
					<p><?php echo esc(trim($rowF['auteur_prenom'].' '.$rowF['auteur_nom'])); ?></p>
				</div>
			</a>
		<?php endwhile; ?>
	</div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
