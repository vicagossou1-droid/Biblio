<?php
include __DIR__ . '/includes/auth.php';
include __DIR__ . '/config/db.php';

// Vérifier si c'est une requête AJAX pour les suggestions
if (!empty($_GET['suggest'])) {
    header('Content-Type: application/json');
    
    $query = trim($_GET['q'] ?? '');
    
    if (strlen($query) < 3) {
        echo json_encode([]);
        exit;
    }
    
    // Recherche avec LIKE sur les 3 premiers caractères
    $search_term = $query . '%';
    
    $sql = "SELECT DISTINCT l.livre_id, l.titre, a.prenom AS auteur_prenom, a.nom AS auteur_nom, c.nom AS categorie_nom, l.exemplaires_disponibles
            FROM livres l
            LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
            LEFT JOIN categories c ON l.categorie_id = c.categorie_id
            WHERE l.titre LIKE ? OR a.prenom LIKE ? OR a.nom LIKE ? OR c.nom LIKE ?
            ORDER BY l.titre
            LIMIT 8";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssss', $search_term, $search_term, $search_term, $search_term);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $suggestions = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $suggestions[] = [
            'livre_id' => (int)$row['livre_id'],
            'titre' => $row['titre'],
            'auteur' => trim($row['auteur_prenom'] . ' ' . $row['auteur_nom']),
            'categorie' => $row['categorie_nom'],
            'disponible' => (int)$row['exemplaires_disponibles'] > 0
        ];
    }
    
    echo json_encode($suggestions);
    exit;
}

// Recherche normale (avec formulaire soumis)
include __DIR__ . '/includes/header.php';

$q = trim($_GET['q'] ?? '');
$search_results = [];
$search_count = 0;

if ($q !== '') {
    $like = '%' . $q . '%';
    $sql = "SELECT l.livre_id, l.titre, l.url_image_couverture, a.prenom AS auteur_prenom, a.nom AS auteur_nom, c.nom AS categorie_nom, l.exemplaires_disponibles
            FROM livres l
            LEFT JOIN auteurs a ON l.auteur_id = a.auteur_id
            LEFT JOIN categories c ON l.categorie_id = c.categorie_id
            WHERE l.titre LIKE ? OR a.prenom LIKE ? OR a.nom LIKE ? OR c.nom LIKE ?
            ORDER BY l.titre";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssss', $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    while ($r = mysqli_fetch_assoc($res)) {
        $search_results[] = $r;
    }
    $search_count = count($search_results);
}

?>

<h1>Résultats de recherche pour "<?php echo esc($q); ?>"</h1>

<?php if ($search_count === 0 && $q !== ''): ?>
    <p>Aucun livre trouvé correspondant à votre recherche.</p>
<?php elseif ($search_count > 0): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Auteur</th>
                <th>Catégorie</th>
                <th>Disponibilité</th>
                <?php if (is_logged_in()): ?>
                <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($search_results as $livre): ?>
            <tr>
                <td><?php echo esc($livre['titre']); ?></td>
                <td><?php echo esc(trim($livre['auteur_prenom'] . ' ' . $livre['auteur_nom'])); ?></td>
                <td><?php echo esc($livre['categorie_nom']); ?></td>
                <td>
                    <?php if ((int)$livre['exemplaires_disponibles'] > 0): ?>
                        <span style="color: green; font-weight: bold;">Disponible (<?php echo (int)$livre['exemplaires_disponibles']; ?>)</span>
                    <?php else: ?>
                        <span style="color: red; font-weight: bold;">Indisponible</span>
                    <?php endif; ?>
                </td>
                <?php if (is_logged_in()): ?>
                <td>
                    <?php if ((int)$livre['exemplaires_disponibles'] > 0): ?>
                        <a class="btn small" href="<?php echo BASE_URL; ?>/emprunter.php?livre_id=<?php echo (int)$livre['livre_id']; ?>">Emprunter</a>
                    <?php else: ?>
                        <?php 
                        $stmt_user_res = mysqli_prepare($conn, "SELECT reservation_id FROM reservations WHERE utilisateur_id = ? AND livre_id = ? AND (statut = 'en_attente' OR statut = 'disponible')");
                        mysqli_stmt_bind_param($stmt_user_res, 'ii', $_SESSION['user_id'], $livre['livre_id']);
                        mysqli_stmt_execute($stmt_user_res);
                        mysqli_stmt_store_result($stmt_user_res);
                        if (mysqli_stmt_num_rows($stmt_user_res) > 0): ?>
                            <span>Déjà réservé</span>
                        <?php else: ?>
                            <a class="btn small" href="<?php echo BASE_URL; ?>/reserver.php?livre_id=<?php echo (int)$livre['livre_id']; ?>">Réserver</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
